-- ============================================================
-- Sincronizacao do app: identificacao de coletas e edicoes
-- ============================================================
-- PostgreSQL. Pode ser executado mais de uma vez sem efeito colateral.
--
-- client_id  - chave gerada pelo app quando a entrevista comeca. Faz um
--              reenvio (apos resposta perdida) reaproveitar o registro em
--              vez de duplicar, e faz uma edicao atualizar o registro
--              original.
-- edited_at  - quando a coleta foi editada no aparelho. Uma edicao so e
--              aplicada se for mais nova que a ultima recebida.
-- audio_path - nome do audio da entrevista, enviado por api/audios/upload.
--
-- api/forms/submit funciona antes e depois desta migracao; sem ela, os
-- reenvios e edicoes de coletas novas nao sao reconhecidos.

ALTER TABLE form_responses ADD COLUMN IF NOT EXISTS client_id  VARCHAR(64);
ALTER TABLE form_responses ADD COLUMN IF NOT EXISTS edited_at  TIMESTAMP;
ALTER TABLE form_responses ADD COLUMN IF NOT EXISTS audio_path VARCHAR(500);

-- UNIQUE permite varios NULL no PostgreSQL: registros antigos, sem
-- client_id, nao conflitam entre si
CREATE UNIQUE INDEX IF NOT EXISTS uq_form_responses_client_id
    ON form_responses (client_id);

-- Usado para reconhecer coletas de versoes antigas do app (sem client_id)
CREATE INDEX IF NOT EXISTS idx_form_responses_identity
    ON form_responses (questionnaire_id, applied_by, started_at);
