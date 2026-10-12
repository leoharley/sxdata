-- ============================================================
-- Diagnostico: coordenadas repetidas apos edicao no app
-- ============================================================
-- PostgreSQL. SOMENTE LEITURA: nenhuma consulta abaixo altera dados.
--
-- O que aconteceu
-- ---------------
-- Ao editar uma coleta, o app descartava a localizacao original e
-- capturava a posicao do momento da edicao. Varias coletas editadas no
-- mesmo lugar ficaram com a mesma coordenada.
--
-- Alem disso, o servidor gravava cada envio como um registro NOVO. Entao,
-- quando a coleta ja tinha sido sincronizada ANTES da edicao, existem dois
-- registros: o original (coordenada certa, respostas antigas) e o editado
-- (coordenada errada, respostas corrigidas). Nesses casos a coordenada
-- certa pode ser recuperada do registro original.
--
-- Quando a edicao aconteceu ANTES da primeira sincronizacao, so o registro
-- editado chegou ao servidor e a coordenada original nao existe mais.
--
-- Para ver so um aplicador:
--   consultas 1 e 3: acrescente ao final do WHERE
--       AND u.full_name ILIKE '%Débora%'
--   consulta 2: acrescente logo antes do GROUP BY
--       WHERE u.full_name ILIKE '%Débora%'
-- ============================================================


-- ------------------------------------------------------------
-- 1. Pontos "concentrados": coletas do mesmo aplicador com a
--    mesma coordenada exata
-- ------------------------------------------------------------
SELECT
    u.full_name                                     AS aplicador,
    fr.latitude,
    fr.longitude,
    COUNT(*)                                        AS coletas_no_mesmo_ponto,
    MIN(fr.completed_at)                            AS primeira,
    MAX(fr.completed_at)                            AS ultima,
    STRING_AGG(fr.id::text, ', ' ORDER BY fr.id)   AS ids
FROM form_responses fr
JOIN users u ON u.id = fr.applied_by
WHERE fr.latitude IS NOT NULL
  AND fr.longitude IS NOT NULL
GROUP BY u.full_name, fr.latitude, fr.longitude
HAVING COUNT(*) >= 2
ORDER BY coletas_no_mesmo_ponto DESC, aplicador;


-- ------------------------------------------------------------
-- 2. Para cada coleta num ponto concentrado, o provavel registro
--    ORIGINAL: mesmo questionario e aplicador, gravado antes, com
--    coordenada diferente e pelo menos 70% das respostas iguais.
--
--    Quando aparece um original aqui, a coordenada certa da entrevista
--    e lat_original/lng_original.
-- ------------------------------------------------------------
WITH concentrados AS (
    SELECT applied_by, latitude, longitude
    FROM form_responses
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    GROUP BY applied_by, latitude, longitude
    HAVING COUNT(*) >= 2
),
suspeitas AS (
    SELECT fr.*
    FROM form_responses fr
    JOIN concentrados c
      ON c.applied_by = fr.applied_by
     AND c.latitude   = fr.latitude
     AND c.longitude  = fr.longitude
),
respostas AS (
    SELECT form_response_id,
           question_id,
           COALESCE(response_text,
                    response_number::text,
                    response_date::text,
                    response_datetime::text,
                    selected_options) AS valor
    FROM question_responses
)
SELECT
    s.id                AS registro_editado,
    o.id                AS registro_original,
    u.full_name         AS aplicador,
    q.title             AS questionario,
    o.completed_at      AS data_entrevista,
    s.created_at        AS data_registro_editado,
    o.latitude          AS lat_original,
    o.longitude         AS lng_original,
    s.latitude          AS lat_editado,
    s.longitude         AS lng_editado,
    COUNT(*) FILTER (WHERE rs.valor IS NOT DISTINCT FROM ro.valor) AS respostas_iguais,
    COUNT(*)                                                       AS perguntas_comparadas
FROM suspeitas s
JOIN form_responses o
  ON o.questionnaire_id = s.questionnaire_id
 AND o.applied_by       = s.applied_by
 AND o.id              <> s.id
 AND o.created_at       < s.created_at
 AND (o.latitude IS DISTINCT FROM s.latitude OR o.longitude IS DISTINCT FROM s.longitude)
JOIN respostas rs ON rs.form_response_id = s.id
JOIN respostas ro ON ro.form_response_id = o.id AND ro.question_id = rs.question_id
JOIN users u        ON u.id = s.applied_by
JOIN questionnaires q ON q.id = s.questionnaire_id
GROUP BY s.id, o.id, u.full_name, q.title, o.completed_at, s.created_at,
         o.latitude, o.longitude, s.latitude, s.longitude
HAVING COUNT(*) FILTER (WHERE rs.valor IS NOT DISTINCT FROM ro.valor) >= 0.7 * COUNT(*)
ORDER BY s.id, respostas_iguais DESC;


-- ------------------------------------------------------------
-- 3. Coletas em ponto concentrado SEM original encontrado.
--    Aqui a coordenada original nao chegou ao servidor e nao pode ser
--    recuperada pelo banco.
-- ------------------------------------------------------------
WITH concentrados AS (
    SELECT applied_by, latitude, longitude
    FROM form_responses
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    GROUP BY applied_by, latitude, longitude
    HAVING COUNT(*) >= 2
)
SELECT
    fr.id,
    u.full_name     AS aplicador,
    q.title         AS questionario,
    fr.completed_at,
    fr.latitude,
    fr.longitude
FROM form_responses fr
JOIN concentrados c
  ON c.applied_by = fr.applied_by
 AND c.latitude   = fr.latitude
 AND c.longitude  = fr.longitude
JOIN users u          ON u.id = fr.applied_by
JOIN questionnaires q ON q.id = fr.questionnaire_id
WHERE NOT EXISTS (
    SELECT 1
    FROM form_responses o
    WHERE o.questionnaire_id = fr.questionnaire_id
      AND o.applied_by       = fr.applied_by
      AND o.id              <> fr.id
      AND o.created_at       < fr.created_at
      AND (o.latitude IS DISTINCT FROM fr.latitude OR o.longitude IS DISTINCT FROM fr.longitude)
)
ORDER BY u.full_name, fr.completed_at;
