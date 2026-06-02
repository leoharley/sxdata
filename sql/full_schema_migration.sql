-- ============================================================================
-- SXDATA / Painel - Migration COMPLETA da estrutura do banco de dados
-- ----------------------------------------------------------------------------
-- SGBD........: PostgreSQL 13 (testado para 13.x; compativel com 9.5+)
-- Codificacao.: UTF-8
-- Gerado em...: 2026-06-01
-- ----------------------------------------------------------------------------
-- Cria TODAS as tabelas do sistema (painel administrativo + API do app movel +
-- modulo de IA), reconstruidas a partir dos models/controllers e consolidando
-- as migrations parciais existentes em sql/.
--
-- IDEMPOTENCIA: usa "CREATE TABLE IF NOT EXISTS" e "CREATE INDEX IF NOT EXISTS"
--   (ambos suportados pelo PostgreSQL 13). Pode ser executado em banco vazio OU
--   em banco que ja tenha as migrations parciais antigas aplicadas; tabelas e
--   indices ja existentes sao ignorados sem erro.
--
--   OBSERVACAO: se uma tabela ANTIGA ja existir porem com colunas faltando, este
--   script NAO adiciona colunas (CREATE TABLE IF NOT EXISTS apenas ignora a
--   tabela). Nesse cenario, aplique antes os ALTERs especificos
--   (ex.: sql/ai_transcriptions_app_sync.sql). Para instalacao NOVA, nao ha o
--   que se preocupar: a estrutura sai completa.
--
-- EXECUCAO:
--   psql -U <user> -d <database> -f sql/full_schema_migration.sql
-- ============================================================================

BEGIN;

-- ============================================================================
-- 1. NUCLEO - USUARIOS, PROJETOS, QUESTIONARIOS, RESPOSTAS
-- ============================================================================

-- 1.1 Usuarios (admin, supervisor, aplicador, cliente)
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(200) NOT NULL,
    email         VARCHAR(200) UNIQUE,
    role          VARCHAR(30)  NOT NULL DEFAULT 'aplicador', -- aplicador | supervisor | administrador | cliente
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_users_role   ON users(role, is_active);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);

-- 1.2 Projetos / campanhas de coleta
CREATE TABLE IF NOT EXISTS projects (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    client_name VARCHAR(200),
    status      VARCHAR(20)  NOT NULL DEFAULT 'active', -- active | paused | completed | cancelled
    start_date  DATE,
    end_date    DATE,
    budget      NUMERIC(15,2),
    created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_projects_status     ON projects(status);
CREATE INDEX IF NOT EXISTS idx_projects_created_by ON projects(created_by);

-- 1.3 Vinculo projeto <-> usuario cliente (acesso do cliente aos dados do projeto)
CREATE TABLE IF NOT EXISTS project_clients (
    id         SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id    INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (project_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_project_clients_project ON project_clients(project_id);
CREATE INDEX IF NOT EXISTS idx_project_clients_user    ON project_clients(user_id);

-- 1.4 Questionarios / formularios
CREATE TABLE IF NOT EXISTS questionnaires (
    id                SERIAL PRIMARY KEY,
    title             VARCHAR(300) NOT NULL,
    description       TEXT,
    status            VARCHAR(20)  NOT NULL DEFAULT 'active', -- active | inactive
    version           INTEGER      NOT NULL DEFAULT 1,
    requires_consent  BOOLEAN      DEFAULT FALSE,
    requires_location BOOLEAN      DEFAULT FALSE,
    requires_photo    BOOLEAN      DEFAULT FALSE,
    estimated_time    INTEGER,                 -- tempo estimado em minutos
    aplicadores       TEXT,                    -- JSON: array de IDs de aplicadores permitidos (NULL = todos)
    project_id        INTEGER REFERENCES projects(id) ON DELETE SET NULL,
    created_by        INTEGER REFERENCES users(id)    ON DELETE SET NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_questionnaires_status  ON questionnaires(status);
CREATE INDEX IF NOT EXISTS idx_questionnaires_project ON questionnaires(project_id);

-- 1.5 Perguntas
CREATE TABLE IF NOT EXISTS questions (
    id                SERIAL PRIMARY KEY,
    questionnaire_id  INTEGER NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_text     TEXT    NOT NULL,
    question_type     VARCHAR(30) NOT NULL, -- text|textarea|number|radio|checkbox|select|date|time|datetime|email|phone
    is_required       BOOLEAN DEFAULT FALSE,
    order_index       INTEGER NOT NULL DEFAULT 0,
    conditional_logic TEXT,                 -- JSON: regras de visibilidade/obrigatoriedade
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_questions_questionnaire ON questions(questionnaire_id, order_index);

-- 1.6 Opcoes de perguntas (radio/checkbox/select)
CREATE TABLE IF NOT EXISTS question_options (
    id           SERIAL PRIMARY KEY,
    question_id  INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    option_text  TEXT    NOT NULL,
    option_value VARCHAR(255),
    order_index  INTEGER NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_question_options_question ON question_options(question_id, order_index);

-- 1.7 Respostas de formulario (uma submissao = uma linha)
CREATE TABLE IF NOT EXISTS form_responses (
    id               SERIAL PRIMARY KEY,
    questionnaire_id INTEGER NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    applied_by       INTEGER REFERENCES users(id) ON DELETE SET NULL, -- aplicador
    respondent_name  VARCHAR(255),
    respondent_email VARCHAR(255),
    latitude         NUMERIC(10,7),
    longitude        NUMERIC(10,7),
    location_name    VARCHAR(255),
    photo_path       VARCHAR(500),
    photo_path_2     VARCHAR(500),
    consent_given    BOOLEAN     DEFAULT FALSE,
    sync_status      VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending | synced | error
    started_at       TIMESTAMP,
    completed_at     TIMESTAMP,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_form_responses_questionnaire ON form_responses(questionnaire_id);
CREATE INDEX IF NOT EXISTS idx_form_responses_applied_by    ON form_responses(applied_by);
CREATE INDEX IF NOT EXISTS idx_form_responses_sync_status   ON form_responses(sync_status);
CREATE INDEX IF NOT EXISTS idx_form_responses_completed_at  ON form_responses(completed_at);
CREATE INDEX IF NOT EXISTS idx_form_responses_location      ON form_responses(latitude, longitude);

-- 1.8 Respostas individuais por pergunta
CREATE TABLE IF NOT EXISTS question_responses (
    id                SERIAL PRIMARY KEY,
    form_response_id  INTEGER NOT NULL REFERENCES form_responses(id) ON DELETE CASCADE,
    question_id       INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    response_text     TEXT,
    response_number   NUMERIC,
    response_date     DATE,
    response_datetime TIMESTAMP,
    selected_options  TEXT,   -- JSON: array de opcoes selecionadas (radio/checkbox)
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_question_responses_form     ON question_responses(form_response_id);
CREATE INDEX IF NOT EXISTS idx_question_responses_question ON question_responses(question_id);

-- 1.9 Configuracoes do sistema (chave/valor)
CREATE TABLE IF NOT EXISTS system_settings (
    id            SERIAL PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- 2. AUTENTICACAO / AUDITORIA DE ACESSO
-- ============================================================================

-- 2.1 Log de atividades (exportacoes, acoes administrativas via API)
CREATE TABLE IF NOT EXISTS activity_logs (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action        VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id   INTEGER,
    details       TEXT,            -- JSON
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user   ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action, created_at);

-- 2.2 Tokens de API (autenticacao do app movel)
CREATE TABLE IF NOT EXISTS user_tokens (
    id         SERIAL PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token      VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_user_tokens_token   ON user_tokens(token);
CREATE INDEX IF NOT EXISTS idx_user_tokens_expires ON user_tokens(expires_at);

-- 2.3 Sessoes do CodeIgniter (driver de sessao em banco - schema oficial CI3/PostgreSQL)
--     Mantido para o caso de sess_driver = 'database'. Se o projeto usa sessao em
--     arquivos, esta tabela fica ociosa, sem impacto.
CREATE TABLE IF NOT EXISTS ci_sessions (
    id         VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45)  NOT NULL,
    timestamp  BIGINT       NOT NULL DEFAULT 0,
    data       TEXT         NOT NULL DEFAULT '',
    CONSTRAINT ci_sessions_pkey PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_ci_sessions_timestamp ON ci_sessions(timestamp);

-- ============================================================================
-- 3. MODULO DE IA
-- ============================================================================

-- 3.1 Configuracoes de IA (feature toggles + parametros do modelo)
CREATE TABLE IF NOT EXISTS ai_settings (
    id              SERIAL PRIMARY KEY,
    feature_key     VARCHAR(100) NOT NULL UNIQUE,
    feature_name    VARCHAR(200) NOT NULL,
    category        VARCHAR(50)  NOT NULL DEFAULT 'general',
    is_enabled      BOOLEAN      NOT NULL DEFAULT FALSE,
    model           VARCHAR(100) DEFAULT 'gpt-4o-mini',
    temperature     DECIMAL(3,2) DEFAULT 0.7,
    max_tokens      INTEGER      DEFAULT 2000,
    timeout_seconds INTEGER      DEFAULT 30,
    retry_attempts  INTEGER      DEFAULT 2,
    custom_params   TEXT         DEFAULT '{}',
    environment     VARCHAR(20)  DEFAULT 'production',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3.2 Prompts versionados
CREATE TABLE IF NOT EXISTS ai_prompts (
    id                   SERIAL PRIMARY KEY,
    feature_key          VARCHAR(100) NOT NULL,
    prompt_name          VARCHAR(200) NOT NULL,
    system_prompt        TEXT,
    user_prompt_template TEXT NOT NULL,
    version              INTEGER NOT NULL DEFAULT 1,
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_by           INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_prompts_feature ON ai_prompts(feature_key, is_active);

-- 3.3 Log de execucoes de IA (custos/tokens/latencia)
CREATE TABLE IF NOT EXISTS ai_execution_logs (
    id            SERIAL PRIMARY KEY,
    feature_key   VARCHAR(100) NOT NULL,
    model_used    VARCHAR(100),
    prompt_id     INTEGER REFERENCES ai_prompts(id) ON DELETE SET NULL,
    input_data    TEXT,
    output_data   TEXT,
    tokens_input  INTEGER DEFAULT 0,
    tokens_output INTEGER DEFAULT 0,
    cost_usd      DECIMAL(10,6) DEFAULT 0,
    duration_ms   INTEGER DEFAULT 0,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_message TEXT,
    executed_by   INTEGER REFERENCES users(id) ON DELETE SET NULL,
    resource_type VARCHAR(50),
    resource_id   INTEGER,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_exec_feature  ON ai_execution_logs(feature_key, status);
CREATE INDEX IF NOT EXISTS idx_ai_exec_created  ON ai_execution_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_ai_exec_resource ON ai_execution_logs(resource_type, resource_id);

-- 3.4 Transcricoes de audio (inclui colunas de sincronizacao do app movel)
CREATE TABLE IF NOT EXISTS ai_transcriptions (
    id                      SERIAL PRIMARY KEY,
    form_response_id        INTEGER REFERENCES form_responses(id) ON DELETE SET NULL,
    question_id             INTEGER REFERENCES questions(id)      ON DELETE SET NULL,
    questionnaire_id        INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    audio_file_path         VARCHAR(500) NOT NULL,
    audio_duration_seconds  DECIMAL(10,2),
    transcription_text      TEXT,
    transcription_edited    TEXT,
    language                VARCHAR(10) DEFAULT 'pt-BR',
    confidence_score        DECIMAL(5,4),
    status                  VARCHAR(20) NOT NULL DEFAULT 'pending',
    model_used              VARCHAR(100),
    processed_at            TIMESTAMP,
    processed_by            INTEGER REFERENCES users(id) ON DELETE SET NULL,
    error_message           TEXT,
    -- colunas de sincronizacao do app (ai_transcriptions_app_sync.sql)
    app_id                  VARCHAR(36) UNIQUE,
    question_text           TEXT,
    edited_text             TEXT,
    duration_ms             INTEGER,
    recording_duration_secs INTEGER DEFAULT 0,
    applicator_name         VARCHAR(255),
    applicator_id           INTEGER REFERENCES users(id) ON DELETE SET NULL,
    timestamp_app           TIMESTAMP,
    source                  VARCHAR(20) DEFAULT 'upload',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_status        ON ai_transcriptions(status);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_response      ON ai_transcriptions(form_response_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_app_id        ON ai_transcriptions(app_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_questionnaire ON ai_transcriptions(questionnaire_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_applicator    ON ai_transcriptions(applicator_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_timestamp_app ON ai_transcriptions(timestamp_app);

-- 3.5 Deteccao de inconsistencias
CREATE TABLE IF NOT EXISTS ai_inconsistencies (
    id                  SERIAL PRIMARY KEY,
    form_response_id    INTEGER REFERENCES form_responses(id) ON DELETE CASCADE,
    questionnaire_id    INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    inconsistency_type  VARCHAR(50) NOT NULL,
    severity            VARCHAR(20) NOT NULL DEFAULT 'medium',
    consistency_score   DECIMAL(5,2),
    description         TEXT NOT NULL,
    ai_justification    TEXT,
    affected_questions  TEXT DEFAULT '[]',
    suggested_action    TEXT,
    resolution_status   VARCHAR(20) NOT NULL DEFAULT 'pending',
    resolved_by         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    resolved_at         TIMESTAMP,
    resolution_notes    TEXT,
    execution_log_id    INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_inconsistencies_response ON ai_inconsistencies(form_response_id);
CREATE INDEX IF NOT EXISTS idx_ai_inconsistencies_status   ON ai_inconsistencies(resolution_status, severity);

-- 3.6 Correcoes / padronizacao de dados
CREATE TABLE IF NOT EXISTS ai_corrections (
    id                   SERIAL PRIMARY KEY,
    form_response_id     INTEGER REFERENCES form_responses(id) ON DELETE CASCADE,
    question_id          INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    question_response_id INTEGER REFERENCES question_responses(id) ON DELETE SET NULL,
    field_name           VARCHAR(200),
    original_value       TEXT,
    suggested_value      TEXT,
    correction_type      VARCHAR(50) NOT NULL,
    confidence_score     DECIMAL(5,4),
    status               VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by          INTEGER REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at          TIMESTAMP,
    applied_value        TEXT,
    execution_log_id     INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_corrections_response ON ai_corrections(form_response_id);
CREATE INDEX IF NOT EXISTS idx_ai_corrections_status   ON ai_corrections(status);

-- 3.7 Sugestoes de preenchimento inteligente
CREATE TABLE IF NOT EXISTS ai_field_suggestions (
    id               SERIAL PRIMARY KEY,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_id      INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    form_response_id INTEGER REFERENCES form_responses(id) ON DELETE SET NULL,
    suggested_value  TEXT NOT NULL,
    context_data     TEXT DEFAULT '{}',
    confidence_score DECIMAL(5,4),
    status           VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at      TIMESTAMP,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_suggestions_question ON ai_field_suggestions(question_id);

-- 3.8 Reformulacao de perguntas
CREATE TABLE IF NOT EXISTS ai_reformulations (
    id                SERIAL PRIMARY KEY,
    question_id       INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    original_text     TEXT NOT NULL,
    reformulated_text TEXT NOT NULL,
    objective         VARCHAR(100),
    status            VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_by       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approved_at       TIMESTAMP,
    execution_log_id  INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_reformulations_question ON ai_reformulations(question_id, status);

-- 3.9 Regras de questionario adaptativo
CREATE TABLE IF NOT EXISTS ai_adaptive_rules (
    id                 SERIAL PRIMARY KEY,
    questionnaire_id   INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    source_question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    target_question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    condition_logic    TEXT NOT NULL,
    ai_generated       BOOLEAN DEFAULT TRUE,
    fallback_target_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    priority           INTEGER DEFAULT 0,
    is_active          BOOLEAN DEFAULT TRUE,
    approved_by        INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approved_at        TIMESTAMP,
    execution_log_id   INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_adaptive_questionnaire ON ai_adaptive_rules(questionnaire_id, is_active);

-- 3.10 Sugestoes de follow-up
CREATE TABLE IF NOT EXISTS ai_followup_suggestions (
    id                      SERIAL PRIMARY KEY,
    questionnaire_id        INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_id             INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    suggested_question_text TEXT NOT NULL,
    question_type           VARCHAR(50) DEFAULT 'text',
    suggested_options       TEXT DEFAULT '[]',
    rationale               TEXT,
    status                  VARCHAR(20) NOT NULL DEFAULT 'pending',
    added_as_question_id    INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    reviewed_by             INTEGER REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at             TIMESTAMP,
    execution_log_id        INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_followup_questionnaire ON ai_followup_suggestions(questionnaire_id, status);

-- 3.11 Analises estatisticas geradas por IA
CREATE TABLE IF NOT EXISTS ai_statistical_analyses (
    id                SERIAL PRIMARY KEY,
    questionnaire_id  INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    analysis_type     VARCHAR(50) NOT NULL,
    filters_applied   TEXT DEFAULT '{}',
    summary_text      TEXT,
    patterns          TEXT DEFAULT '[]',
    outliers          TEXT DEFAULT '[]',
    trends            TEXT DEFAULT '[]',
    insights          TEXT DEFAULT '[]',
    chart_suggestions TEXT DEFAULT '[]',
    status            VARCHAR(20) NOT NULL DEFAULT 'completed',
    generated_by      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    execution_log_id  INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_stats_questionnaire ON ai_statistical_analyses(questionnaire_id);

-- 3.12 Relatorios em linguagem natural
CREATE TABLE IF NOT EXISTS ai_reports (
    id               SERIAL PRIMARY KEY,
    title            VARCHAR(300) NOT NULL,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    report_type      VARCHAR(50) NOT NULL DEFAULT 'general',
    filters_applied  TEXT DEFAULT '{}',
    narrative_text   TEXT NOT NULL,
    sections         TEXT DEFAULT '[]',
    charts_data      TEXT DEFAULT '[]',
    status           VARCHAR(20) NOT NULL DEFAULT 'draft',
    generated_by     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id) ON DELETE SET NULL,
    exported_at      TIMESTAMP,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_reports_type ON ai_reports(report_type, status);

-- 3.13 Diretrizes / instrucoes do admin para o agente de IA
CREATE TABLE IF NOT EXISTS ai_directives (
    id             SERIAL PRIMARY KEY,
    category       VARCHAR(50)  NOT NULL DEFAULT 'general',
    title          VARCHAR(200) NOT NULL,
    content        TEXT NOT NULL,
    directive_type VARCHAR(30)  NOT NULL DEFAULT 'instruction',
    priority       INTEGER      NOT NULL DEFAULT 0,
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    applies_to     TEXT         DEFAULT '[]',
    created_by     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_directives_category ON ai_directives(category, is_active);
CREATE INDEX IF NOT EXISTS idx_ai_directives_type     ON ai_directives(directive_type, is_active);

-- 3.14 Dicas de follow-up vinculadas a perguntas (exibidas no app)
CREATE TABLE IF NOT EXISTS question_followup_tips (
    id                SERIAL PRIMARY KEY,
    question_id       INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    tip               TEXT NOT NULL,
    source            VARCHAR(20) NOT NULL DEFAULT 'manual', -- manual | ai
    ai_suggestion_id  INTEGER DEFAULT NULL,
    created_by        INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_followup_tips_question ON question_followup_tips(question_id);

-- 3.15 Auditoria de uso de IA (eventos do app movel)
CREATE TABLE IF NOT EXISTS ai_audit_logs (
    id               SERIAL PRIMARY KEY,
    event_type       VARCHAR(50) NOT NULL,
    question_id      INTEGER,
    questionnaire_id INTEGER,
    form_id          INTEGER,
    user_id          INTEGER,
    metadata         TEXT,
    timestamp        TIMESTAMP NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_audit_event_type    ON ai_audit_logs(event_type);
CREATE INDEX IF NOT EXISTS idx_ai_audit_questionnaire ON ai_audit_logs(questionnaire_id);
CREATE INDEX IF NOT EXISTS idx_ai_audit_user          ON ai_audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_audit_timestamp     ON ai_audit_logs(timestamp);

-- 3.16 Cache de respostas da IA
CREATE TABLE IF NOT EXISTS ai_cache (
    id          SERIAL PRIMARY KEY,
    cache_key   VARCHAR(255) NOT NULL UNIQUE,
    cache_value TEXT NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_cache_key     ON ai_cache(cache_key);
CREATE INDEX IF NOT EXISTS idx_ai_cache_expires ON ai_cache(expires_at);

-- 3.17 Rate limiting da IA
CREATE TABLE IF NOT EXISTS ai_rate_limits (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER NOT NULL,
    endpoint      VARCHAR(100) NOT NULL,
    request_count INTEGER DEFAULT 1,
    window_start  TIMESTAMP NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_ai_rate_user_endpoint ON ai_rate_limits(user_id, endpoint, window_start);

-- ============================================================================
-- 4. SEED - DADOS INICIAIS
-- ============================================================================

-- 4.1 Usuario administrador inicial
--     Login: admin   Senha: admin123   (ALTERE A SENHA APOS O PRIMEIRO ACESSO!)
--     Hash bcrypt (PASSWORD_DEFAULT) de 'admin123'.
INSERT INTO users (username, password_hash, full_name, email, role, is_active)
SELECT 'admin', '$2y$10$yGoOSyqZqWy2HfhZZMT07OUPTmYbCCGMJYOx/TehB.Kpmx8ZPE3cq',
       'Administrador', 'admin@sxdata.local', 'administrador', TRUE
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- 4.2 Configuracoes padrao de IA (feature toggles)
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'transcription', 'Transcricao de Audio', 'coleta', FALSE, 'whisper-1', 0, 0, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'transcription');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'inconsistency_detection', 'Deteccao de Inconsistencias', 'qualidade', FALSE, 'gpt-4o-mini', 0.3, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'inconsistency_detection');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'data_correction', 'Correcao e Padronizacao', 'qualidade', FALSE, 'gpt-4o-mini', 0.2, 1500, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'data_correction');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'smart_fill', 'Preenchimento Inteligente', 'coleta', FALSE, 'gpt-4o-mini', 0.5, 1000, 20
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'smart_fill');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'question_reformulation', 'Reformulacao de Perguntas', 'questionario', FALSE, 'gpt-4o', 0.7, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'question_reformulation');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'adaptive_routing', 'Questionario Adaptativo', 'questionario', FALSE, 'gpt-4o-mini', 0.3, 1500, 25
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'adaptive_routing');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'followup_suggestions', 'Sugestoes de Follow-up', 'questionario', FALSE, 'gpt-4o-mini', 0.6, 1500, 25
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'followup_suggestions');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'statistical_analysis', 'Analise Estatistica', 'analise', FALSE, 'gpt-4o', 0.4, 4000, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'statistical_analysis');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'smart_charts', 'Graficos Inteligentes', 'analise', FALSE, 'gpt-4o-mini', 0.3, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'smart_charts');
INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'natural_reports', 'Relatorios em Linguagem Natural', 'analise', FALSE, 'gpt-4o', 0.6, 4000, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'natural_reports');

-- 4.3 Prompts padrao iniciais
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'inconsistency_detection', 'Deteccao padrao',
 'Voce e um analista de qualidade de dados. Analise respostas de questionarios e identifique inconsistencias logicas entre as respostas. Responda sempre em JSON valido.',
 'Analise as seguintes respostas do questionario "{{questionnaire_title}}" e identifique inconsistencias:\n\n{{responses_data}}\n\nRetorne um JSON com: [{inconsistency_type, severity (low/medium/high/critical), description, affected_questions: [ids], suggested_action, consistency_score}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'inconsistency_detection' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'data_correction', 'Correcao padrao',
 'Voce e um especialista em padronizacao de dados. Corrija e padronize valores de campos de formularios. Mantenha a semantica original. Responda sempre em JSON valido.',
 'Analise e sugira correcoes para os seguintes dados do questionario "{{questionnaire_title}}":\n\n{{field_data}}\n\nRetorne JSON: [{question_id, original_value, suggested_value, correction_type (ortografia/formato/padronizacao/completude), confidence}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'data_correction' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'smart_fill', 'Preenchimento padrao',
 'Voce e um assistente de preenchimento de formularios. Com base no contexto fornecido, sugira valores para campos vazios. Responda sempre em JSON valido.',
 'Com base nas respostas ja preenchidas do questionario "{{questionnaire_title}}":\n\n{{existing_answers}}\n\nSugira valores para os campos pendentes:\n{{pending_fields}}\n\nRetorne JSON: [{question_id, suggested_value, confidence, rationale}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'smart_fill' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'question_reformulation', 'Reformulacao padrao',
 'Voce e um especialista em design de questionarios. Reescreva perguntas para melhorar clareza, reduzir vies ou adaptar ao publico-alvo. Responda sempre em JSON valido.',
 'Reformule a seguinte pergunta com o objetivo de "{{objective}}":\n\nPergunta original: "{{original_question}}"\nContexto do questionario: {{context}}\n\nRetorne JSON: {reformulated_text, changes_made, rationale}',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'question_reformulation' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'followup_suggestions', 'Follow-up padrao',
 'Voce e um pesquisador especialista em metodologia de pesquisa. Para cada pergunta do questionario, sugira dicas de follow-up que o entrevistador deve usar durante a entrevista. As dicas devem ajudar o entrevistador a aprofundar ou esclarecer as respostas. IMPORTANTE: cada dica deve estar vinculada a uma pergunta existente pelo seu ID. Responda sempre em JSON valido.',
 'Questionario: "{{questionnaire_title}}" ({{total_responses}} respostas coletadas)\n\nPerguntas do questionario:\n{{questions_data}}\n\nPara cada pergunta relevante, sugira 1 a 3 dicas de follow-up para o entrevistador.\n\nRetorne JSON: [{"question_id": <id da pergunta existente>, "tip": "<texto da dica>", "rationale": "<por que esta dica e util>"}]\n\nNAO invente perguntas novas. Gere APENAS dicas para as perguntas existentes listadas acima, usando os IDs reais.',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'followup_suggestions' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'statistical_analysis', 'Analise estatistica padrao',
 'Voce e um analista de dados. Analise os dados fornecidos e gere insights estatisticos em portugues. Responda sempre em JSON valido.',
 'Analise os seguintes dados do questionario "{{questionnaire_title}}" ({{total_responses}} respostas, periodo: {{date_range}}):\n\n{{data_summary}}\n\nRetorne JSON: {summary, patterns: [], outliers: [], trends: [], insights: [], chart_suggestions: [{type, title, data_keys}]}',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'statistical_analysis' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'natural_reports', 'Relatorio narrativo padrao',
 'Voce e um redator de relatorios tecnicos. Gere relatorios descritivos em linguagem natural em portugues brasileiro, com base nos dados fornecidos. Use tom profissional e objetivo.',
 'Gere um relatorio descritivo sobre os dados do questionario "{{questionnaire_title}}":\n\nPeriodo: {{date_range}}\nTotal de respostas: {{total_responses}}\nDados agregados:\n{{aggregated_data}}\n\nInclua: resumo executivo, principais achados, padroes identificados, recomendacoes.',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'natural_reports' AND version = 1);
INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'smart_charts', 'Graficos inteligentes padrao',
 'Voce e um especialista em visualizacao de dados. Analise os dados e sugira os melhores tipos de graficos. Responda sempre em JSON valido.',
 'Com base nos seguintes dados do questionario "{{questionnaire_title}}":\n\n{{data_summary}}\n\nSugira visualizacoes adequadas.\n\nRetorne JSON: [{chart_type (bar/line/pie/doughnut/radar/scatter), title, description, data_config: {labels: [], datasets: [{label, data: []}]}, rationale}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'smart_charts' AND version = 1);

COMMIT;

-- ============================================================================
-- BLOCO DE RESET (opcional) - descomente para apagar TUDO antes de recriar.
-- ATENCAO: isto remove permanentemente todos os dados.
-- ----------------------------------------------------------------------------
-- DROP TABLE IF EXISTS
--   ai_rate_limits, ai_cache, ai_audit_logs, question_followup_tips,
--   ai_directives, ai_reports, ai_statistical_analyses, ai_followup_suggestions,
--   ai_adaptive_rules, ai_reformulations, ai_field_suggestions, ai_corrections,
--   ai_inconsistencies, ai_transcriptions, ai_execution_logs, ai_prompts,
--   ai_settings, ci_sessions, user_tokens, activity_logs, system_settings,
--   question_responses, form_responses, question_options, questions,
--   questionnaires, project_clients, projects, users
--   CASCADE;
-- ============================================================================
