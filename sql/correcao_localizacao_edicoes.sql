-- ============================================================
-- Correção: coordenadas repetidas após edição no app
-- ============================================================
-- PostgreSQL. Rode ANTES o diagnóstico (sql/diagnostico_localizacao_edicoes.sql)
-- e confira o resultado.
--
-- Roda dentro de uma transação que termina em ROLLBACK: executar como está
-- NÃO altera nada, só mostra o que seria corrigido.
--
-- O que corrige
-- -------------
-- Versões antigas do app gravavam, ao editar uma coleta já sincronizada, um
-- registro NOVO com a posição e a data do momento da edição. O registro
-- original continuou no banco, com a coordenada certa.
--
-- Para cada par (editado, original), mesma regra da consulta 2 do
-- diagnóstico, o registro EDITADO — que tem as respostas corrigidas —
-- recebe do original:
--   - latitude, longitude e nome do local da entrevista;
--   - data de início e de conclusão da entrevista (a edição as trocava pela
--     data da correção, por isso a planilha mostrava 29/09).
--
-- O registro original continua no banco, agora como duplicata com as
-- respostas antigas. A etapa 4, opcional, remove esses originais.
--
-- Como usar
-- ---------
-- 1. Rode o arquivo inteiro e confira a consulta de conferência (etapa 2).
--    Linhas com candidatos > 1 tinham mais de um original possível: foi
--    escolhido o com mais respostas iguais. Confira essas com atenção.
-- 2. Para deixar algum par de fora, coloque o id do registro editado em
--    "ids_ignorar" (linha marcada abaixo).
-- 3. Decida sobre a etapa 4 (remover originais duplicados).
-- 4. Troque o ROLLBACK do final por COMMIT e rode de novo.

BEGIN;

-- 1. Pares editado -> original ------------------------------------------
CREATE TEMP TABLE pares_localizacao ON COMMIT DROP AS
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
),
candidatos AS (
    SELECT s.id AS editado,
           o.id AS original,
           o.latitude, o.longitude, o.location_name,
           o.started_at, o.completed_at,
           COUNT(*) FILTER (WHERE rs.valor IS NOT DISTINCT FROM ro.valor) AS respostas_iguais,
           COUNT(*) AS perguntas_comparadas
    FROM suspeitas s
    JOIN form_responses o
      ON o.questionnaire_id = s.questionnaire_id
     AND o.applied_by       = s.applied_by
     AND o.id              <> s.id
     AND o.created_at       < s.created_at
     AND (o.latitude IS DISTINCT FROM s.latitude OR o.longitude IS DISTINCT FROM s.longitude)
    JOIN respostas rs ON rs.form_response_id = s.id
    JOIN respostas ro ON ro.form_response_id = o.id AND ro.question_id = rs.question_id
    GROUP BY s.id, o.id, o.latitude, o.longitude, o.location_name, o.started_at, o.completed_at
    HAVING COUNT(*) FILTER (WHERE rs.valor IS NOT DISTINCT FROM ro.valor) >= 0.7 * COUNT(*)
),
ranqueados AS (
    SELECT c.*,
           ROW_NUMBER() OVER (PARTITION BY editado ORDER BY respostas_iguais DESC, original DESC) AS posicao,
           COUNT(*)     OVER (PARTITION BY editado) AS candidatos
    FROM candidatos c
)
SELECT * FROM ranqueados WHERE posicao = 1;

-- Pares a deixar de fora: ids dos registros EDITADOS, ex.: ARRAY[512, 518]
DELETE FROM pares_localizacao WHERE editado = ANY (ARRAY[]::INTEGER[]);  -- << ids_ignorar

-- 2. Conferência -----------------------------------------------------------
SELECT p.editado                 AS registro_editado,
       p.original                AS registro_original,
       u.full_name               AS aplicador,
       q.title                   AS questionario,
       e.latitude || ', ' || e.longitude AS coordenada_atual,
       p.latitude || ', ' || p.longitude AS coordenada_corrigida,
       e.completed_at            AS data_atual,
       p.completed_at            AS data_corrigida,
       p.respostas_iguais || ' de ' || p.perguntas_comparadas AS respostas_iguais,
       p.candidatos
FROM pares_localizacao p
JOIN form_responses e   ON e.id = p.editado
JOIN users u            ON u.id = e.applied_by
JOIN questionnaires q   ON q.id = e.questionnaire_id
ORDER BY u.full_name, p.completed_at;

-- 3. Correção ----------------------------------------------------------------
UPDATE form_responses fr
   SET latitude      = p.latitude,
       longitude     = p.longitude,
       location_name = COALESCE(p.location_name, fr.location_name),
       started_at    = COALESCE(p.started_at, fr.started_at),
       completed_at  = COALESCE(p.completed_at, fr.completed_at)
  FROM pares_localizacao p
 WHERE fr.id = p.editado;

-- 4. (Opcional) Remover os originais, que ficaram duplicados -----------------
-- Cada original é a mesma entrevista, com as respostas ANTERIORES à correção.
-- As respostas dele (question_responses) são apagadas junto, em cascata.
-- Só descomente depois de conferir a etapa 2.
--
-- DELETE FROM form_responses
--  WHERE id IN (SELECT original FROM pares_localizacao)
--    AND id NOT IN (SELECT editado FROM pares_localizacao);

ROLLBACK;  -- troque por COMMIT depois de conferir a etapa 2
