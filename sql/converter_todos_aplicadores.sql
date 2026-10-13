-- ============================================================
-- Converte o "Todos os Aplicadores" antigo para o formato atual
-- ============================================================
-- PostgreSQL. Roda dentro de uma transação que termina em ROLLBACK:
-- executar como está NÃO altera nada, só mostra o que seria alterado.
--
-- Por quê
-- -------
-- Antes da correção de setembro, escolher "Todos os Aplicadores" gravava a
-- LISTA dos aplicadores ativos naquele momento. Quem foi cadastrado depois
-- (e supervisores/administradores) não enxerga esses questionários no app.
-- Hoje "Todos" é gravado como NULL, que vale para qualquer usuário, inclusive
-- os criados depois.
--
-- Como os candidatos são escolhidos
-- ---------------------------------
-- Questionário cuja lista contém TODOS os aplicadores ativos que já existiam
-- quando ele foi salvo pela última vez. É o retrato que o "Todos" antigo
-- gravava. Uma restrição feita de propósito a um grupo menor não entra.
--
-- Caso-limite: uma restrição de propósito que, por coincidência, incluía todos
-- os aplicadores da época também aparece como candidata. Confira a lista
-- antes de confirmar.
--
-- Como usar
-- ---------
-- 1. Rode o arquivo inteiro e confira o resultado da consulta de conferência.
-- 2. Se algum candidato foi restrito de propósito, acrescente o id dele em
--    "ids_manter" (linha marcada abaixo).
-- 3. Troque o ROLLBACK do final por COMMIT e rode de novo.

BEGIN;

CREATE TEMP TABLE candidatos_todos ON COMMIT DROP AS
WITH listas AS (
    SELECT q.id, q.title, q.status, q.updated_at, q.aplicadores::jsonb AS lista
    FROM questionnaires q
    -- só listas de números válidas; NULL ("todos") e "[]" ("nenhum") ficam de fora
    WHERE q.aplicadores ~ '^\s*\[\s*\d+(\s*,\s*\d+)*\s*\]\s*$'
),
avaliacao AS (
    SELECT l.*,
           (SELECT COUNT(*) FROM users u
             WHERE u.role = 'aplicador' AND u.is_active
               AND u.created_at <= l.updated_at) AS aplicadores_da_epoca,
           (SELECT COUNT(*) FROM users u
             WHERE u.role = 'aplicador' AND u.is_active
               AND u.created_at <= l.updated_at
               AND l.lista @> to_jsonb(u.id)) AS na_lista,
           (SELECT STRING_AGG(u.full_name, ', ' ORDER BY u.full_name) FROM users u
             WHERE u.is_active AND u.role IN ('aplicador', 'supervisor', 'administrador')
               AND NOT (l.lista @> to_jsonb(u.id))) AS hoje_sem_acesso
    FROM listas l
)
SELECT *
FROM avaliacao
WHERE aplicadores_da_epoca > 0
  AND na_lista = aplicadores_da_epoca;

-- Questionários restritos de propósito que NÃO devem virar "Todos":
-- coloque os ids aqui, ex.: ARRAY[12, 15]
DELETE FROM candidatos_todos WHERE id = ANY (ARRAY[]::INTEGER[]);  -- << ids_manter

-- Conferência: o que vai virar "Todos os Aplicadores"
SELECT id,
       title                 AS questionario,
       status,
       updated_at            AS ultima_alteracao,
       lista                 AS lista_atual,
       hoje_sem_acesso       AS passam_a_ter_acesso
FROM candidatos_todos
ORDER BY title;

UPDATE questionnaires
   SET aplicadores = NULL
 WHERE id IN (SELECT id FROM candidatos_todos);

ROLLBACK;  -- troque por COMMIT depois de conferir a lista acima
