-- ============================================================
-- Coleta pública por QR Code (proposta de evolução, frente H)
-- ============================================================
-- PostgreSQL. Pode ser executado mais de uma vez.
--
-- O painel gera um link público (e o QR Code dele) para um questionário.
-- Quem lê o código responde pelo navegador, sem login, e só tem acesso ao
-- formulário daquele questionário.

CREATE TABLE IF NOT EXISTS questionnaire_public_links (
    id               SERIAL PRIMARY KEY,
    questionnaire_id INTEGER NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    token            VARCHAR(64) NOT NULL UNIQUE,      -- aleatório, vai na URL
    label            VARCHAR(200),                     -- identificação interna (ex.: "Cartaz UBS Centro")
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    expires_at       TIMESTAMP,                        -- NULL = sem data de encerramento
    created_by       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at   TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_public_links_questionnaire
    ON questionnaire_public_links (questionnaire_id);

-- Origem da coleta: 'app' (aplicador em campo) ou 'qrcode' (resposta pública).
-- Coletas públicas não têm aplicador: applied_by fica NULL.
ALTER TABLE form_responses ADD COLUMN IF NOT EXISTS origin VARCHAR(20) NOT NULL DEFAULT 'app';
ALTER TABLE form_responses ADD COLUMN IF NOT EXISTS public_link_id INTEGER
    REFERENCES questionnaire_public_links(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_form_responses_origin ON form_responses (origin);

-- Limite de envios por endereço de rede, contra robôs e envio em massa.
-- Guarda apenas o hash do IP, nunca o IP.
CREATE TABLE IF NOT EXISTS public_submission_log (
    id          SERIAL PRIMARY KEY,
    link_id     INTEGER NOT NULL REFERENCES questionnaire_public_links(id) ON DELETE CASCADE,
    ip_hash     CHAR(64) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_public_submission_log
    ON public_submission_log (link_id, ip_hash, created_at);
