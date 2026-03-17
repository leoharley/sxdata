-- ============================================================
-- SXDATA - Migration: Tabelas de Auditoria e Cache de IA
-- Data: 2026-03-17
-- Compatível com PostgreSQL 9.0+
-- ============================================================

-- 1. Tabela de auditoria de uso de IA (eventos do app móvel)
CREATE TABLE ai_audit_logs (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    question_id INTEGER,
    questionnaire_id INTEGER,
    form_id INTEGER,
    user_id INTEGER,
    metadata TEXT,
    timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_audit_event_type ON ai_audit_logs(event_type);
CREATE INDEX idx_ai_audit_questionnaire ON ai_audit_logs(questionnaire_id);
CREATE INDEX idx_ai_audit_user ON ai_audit_logs(user_id);
CREATE INDEX idx_ai_audit_timestamp ON ai_audit_logs(timestamp);

-- 2. Tabela de cache de respostas da IA
CREATE TABLE ai_cache (
    id SERIAL PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    cache_value TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_cache_key ON ai_cache(cache_key);
CREATE INDEX idx_ai_cache_expires ON ai_cache(expires_at);

-- 3. Tabela de rate limiting
CREATE TABLE ai_rate_limits (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_count INTEGER DEFAULT 1,
    window_start TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_rate_user_endpoint ON ai_rate_limits(user_id, endpoint, window_start);
