-- ============================================================
-- SXDATA - Módulo de IA - Migration
-- Data: 2026-03-16
-- Compatível com PostgreSQL 9.0+
-- Descrição: Criação de todas as tabelas necessárias para
-- o módulo de Inteligência Artificial do SXDATA
--
-- NOTA: Execute este script apenas uma vez.
-- Se precisar reexecutar, rode antes:
--   DROP TABLE IF EXISTS ai_reports, ai_statistical_analyses,
--     ai_followup_suggestions, ai_adaptive_rules, ai_reformulations,
--     ai_field_suggestions, ai_corrections, ai_inconsistencies,
--     ai_transcriptions, ai_execution_logs, ai_prompts, ai_settings
--     CASCADE;
-- ============================================================

-- 1. Configurações de IA (feature toggles + parâmetros)
CREATE TABLE ai_settings (
    id SERIAL PRIMARY KEY,
    feature_key VARCHAR(100) NOT NULL UNIQUE,
    feature_name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    is_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    model VARCHAR(100) DEFAULT 'gpt-4o-mini',
    temperature DECIMAL(3,2) DEFAULT 0.7,
    max_tokens INTEGER DEFAULT 2000,
    timeout_seconds INTEGER DEFAULT 30,
    retry_attempts INTEGER DEFAULT 2,
    custom_params TEXT DEFAULT '{}',
    environment VARCHAR(20) DEFAULT 'production',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Prompts versionados
CREATE TABLE ai_prompts (
    id SERIAL PRIMARY KEY,
    feature_key VARCHAR(100) NOT NULL,
    prompt_name VARCHAR(200) NOT NULL,
    system_prompt TEXT,
    user_prompt_template TEXT NOT NULL,
    version INTEGER NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_prompts_feature ON ai_prompts(feature_key, is_active);

-- 3. Log de execuções de IA
CREATE TABLE ai_execution_logs (
    id SERIAL PRIMARY KEY,
    feature_key VARCHAR(100) NOT NULL,
    model_used VARCHAR(100),
    prompt_id INTEGER REFERENCES ai_prompts(id),
    input_data TEXT,
    output_data TEXT,
    tokens_input INTEGER DEFAULT 0,
    tokens_output INTEGER DEFAULT 0,
    cost_usd DECIMAL(10,6) DEFAULT 0,
    duration_ms INTEGER DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_message TEXT,
    executed_by INTEGER REFERENCES users(id),
    resource_type VARCHAR(50),
    resource_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_exec_feature ON ai_execution_logs(feature_key, status);
CREATE INDEX idx_ai_exec_created ON ai_execution_logs(created_at);
CREATE INDEX idx_ai_exec_resource ON ai_execution_logs(resource_type, resource_id);

-- 4. Transcrições de áudio
CREATE TABLE ai_transcriptions (
    id SERIAL PRIMARY KEY,
    form_response_id INTEGER REFERENCES form_responses(id) ON DELETE SET NULL,
    question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    audio_file_path VARCHAR(500) NOT NULL,
    audio_duration_seconds DECIMAL(10,2),
    transcription_text TEXT,
    transcription_edited TEXT,
    language VARCHAR(10) DEFAULT 'pt-BR',
    confidence_score DECIMAL(5,4),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    model_used VARCHAR(100),
    processed_at TIMESTAMP,
    processed_by INTEGER REFERENCES users(id),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_transcriptions_status ON ai_transcriptions(status);
CREATE INDEX idx_ai_transcriptions_response ON ai_transcriptions(form_response_id);

-- 5. Detecção de inconsistências
CREATE TABLE ai_inconsistencies (
    id SERIAL PRIMARY KEY,
    form_response_id INTEGER REFERENCES form_responses(id) ON DELETE CASCADE,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    inconsistency_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    consistency_score DECIMAL(5,2),
    description TEXT NOT NULL,
    ai_justification TEXT,
    affected_questions TEXT DEFAULT '[]',
    suggested_action TEXT,
    resolution_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    resolved_by INTEGER REFERENCES users(id),
    resolved_at TIMESTAMP,
    resolution_notes TEXT,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_inconsistencies_response ON ai_inconsistencies(form_response_id);
CREATE INDEX idx_ai_inconsistencies_status ON ai_inconsistencies(resolution_status, severity);

-- 6. Correções e padronização de dados
CREATE TABLE ai_corrections (
    id SERIAL PRIMARY KEY,
    form_response_id INTEGER REFERENCES form_responses(id) ON DELETE CASCADE,
    question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    question_response_id INTEGER,
    field_name VARCHAR(200),
    original_value TEXT,
    suggested_value TEXT,
    correction_type VARCHAR(50) NOT NULL,
    confidence_score DECIMAL(5,4),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TIMESTAMP,
    applied_value TEXT,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_corrections_response ON ai_corrections(form_response_id);
CREATE INDEX idx_ai_corrections_status ON ai_corrections(status);

-- 7. Sugestões de preenchimento inteligente
CREATE TABLE ai_field_suggestions (
    id SERIAL PRIMARY KEY,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    form_response_id INTEGER REFERENCES form_responses(id) ON DELETE SET NULL,
    suggested_value TEXT NOT NULL,
    context_data TEXT DEFAULT '{}',
    confidence_score DECIMAL(5,4),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TIMESTAMP,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_suggestions_question ON ai_field_suggestions(question_id);

-- 8. Reformulação de perguntas
CREATE TABLE ai_reformulations (
    id SERIAL PRIMARY KEY,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    original_text TEXT NOT NULL,
    reformulated_text TEXT NOT NULL,
    objective VARCHAR(100),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_reformulations_question ON ai_reformulations(question_id, status);

-- 9. Questionário adaptativo (regras de roteamento inteligente)
CREATE TABLE ai_adaptive_rules (
    id SERIAL PRIMARY KEY,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    source_question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    target_question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    condition_logic TEXT NOT NULL,
    ai_generated BOOLEAN DEFAULT TRUE,
    fallback_target_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    priority INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_adaptive_questionnaire ON ai_adaptive_rules(questionnaire_id, is_active);

-- 10. Sugestões de follow-up
CREATE TABLE ai_followup_suggestions (
    id SERIAL PRIMARY KEY,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    suggested_question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'text',
    suggested_options TEXT DEFAULT '[]',
    rationale TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    added_as_question_id INTEGER REFERENCES questions(id) ON DELETE SET NULL,
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TIMESTAMP,
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_followup_questionnaire ON ai_followup_suggestions(questionnaire_id, status);

-- 11. Análises estatísticas geradas por IA
CREATE TABLE ai_statistical_analyses (
    id SERIAL PRIMARY KEY,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    analysis_type VARCHAR(50) NOT NULL,
    filters_applied TEXT DEFAULT '{}',
    summary_text TEXT,
    patterns TEXT DEFAULT '[]',
    outliers TEXT DEFAULT '[]',
    trends TEXT DEFAULT '[]',
    insights TEXT DEFAULT '[]',
    chart_suggestions TEXT DEFAULT '[]',
    status VARCHAR(20) NOT NULL DEFAULT 'completed',
    generated_by INTEGER REFERENCES users(id),
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_stats_questionnaire ON ai_statistical_analyses(questionnaire_id);

-- 12. Relatórios em linguagem natural
CREATE TABLE ai_reports (
    id SERIAL PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL,
    report_type VARCHAR(50) NOT NULL DEFAULT 'general',
    filters_applied TEXT DEFAULT '{}',
    narrative_text TEXT NOT NULL,
    sections TEXT DEFAULT '[]',
    charts_data TEXT DEFAULT '[]',
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    generated_by INTEGER REFERENCES users(id),
    execution_log_id INTEGER REFERENCES ai_execution_logs(id),
    exported_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_reports_type ON ai_reports(report_type, status);

-- ============================================================
-- DADOS INICIAIS - Configurações padrão de IA
-- ============================================================

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'transcription', 'Transcrição de Áudio', 'coleta', FALSE, 'whisper-1', 0, 0, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'transcription');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'inconsistency_detection', 'Detecção de Inconsistências', 'qualidade', FALSE, 'gpt-4o-mini', 0.3, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'inconsistency_detection');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'data_correction', 'Correção e Padronização', 'qualidade', FALSE, 'gpt-4o-mini', 0.2, 1500, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'data_correction');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'smart_fill', 'Preenchimento Inteligente', 'coleta', FALSE, 'gpt-4o-mini', 0.5, 1000, 20
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'smart_fill');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'question_reformulation', 'Reformulação de Perguntas', 'questionario', FALSE, 'gpt-4o', 0.7, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'question_reformulation');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'adaptive_routing', 'Questionário Adaptativo', 'questionario', FALSE, 'gpt-4o-mini', 0.3, 1500, 25
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'adaptive_routing');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'followup_suggestions', 'Sugestões de Follow-up', 'questionario', FALSE, 'gpt-4o-mini', 0.6, 1500, 25
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'followup_suggestions');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'statistical_analysis', 'Análise Estatística', 'analise', FALSE, 'gpt-4o', 0.4, 4000, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'statistical_analysis');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'smart_charts', 'Gráficos Inteligentes', 'analise', FALSE, 'gpt-4o-mini', 0.3, 2000, 30
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'smart_charts');

INSERT INTO ai_settings (feature_key, feature_name, category, is_enabled, model, temperature, max_tokens, timeout_seconds)
SELECT 'natural_reports', 'Relatórios em Linguagem Natural', 'analise', FALSE, 'gpt-4o', 0.6, 4000, 60
WHERE NOT EXISTS (SELECT 1 FROM ai_settings WHERE feature_key = 'natural_reports');

-- Prompts padrão iniciais

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'inconsistency_detection', 'Detecção padrão',
 'Você é um analista de qualidade de dados. Analise respostas de questionários e identifique inconsistências lógicas entre as respostas. Responda sempre em JSON válido.',
 'Analise as seguintes respostas do questionário "{{questionnaire_title}}" e identifique inconsistências:\n\n{{responses_data}}\n\nRetorne um JSON com: [{inconsistency_type, severity (low/medium/high/critical), description, affected_questions: [ids], suggested_action, consistency_score}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'inconsistency_detection' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'data_correction', 'Correção padrão',
 'Você é um especialista em padronização de dados. Corrija e padronize valores de campos de formulários. Mantenha a semântica original. Responda sempre em JSON válido.',
 'Analise e sugira correções para os seguintes dados do questionário "{{questionnaire_title}}":\n\n{{field_data}}\n\nRetorne JSON: [{question_id, original_value, suggested_value, correction_type (ortografia/formato/padronizacao/completude), confidence}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'data_correction' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'smart_fill', 'Preenchimento padrão',
 'Você é um assistente de preenchimento de formulários. Com base no contexto fornecido, sugira valores para campos vazios. Responda sempre em JSON válido.',
 'Com base nas respostas já preenchidas do questionário "{{questionnaire_title}}":\n\n{{existing_answers}}\n\nSugira valores para os campos pendentes:\n{{pending_fields}}\n\nRetorne JSON: [{question_id, suggested_value, confidence, rationale}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'smart_fill' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'question_reformulation', 'Reformulação padrão',
 'Você é um especialista em design de questionários. Reescreva perguntas para melhorar clareza, reduzir viés ou adaptar ao público-alvo. Responda sempre em JSON válido.',
 'Reformule a seguinte pergunta com o objetivo de "{{objective}}":\n\nPergunta original: "{{original_question}}"\nContexto do questionário: {{context}}\n\nRetorne JSON: {reformulated_text, changes_made, rationale}',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'question_reformulation' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'followup_suggestions', 'Follow-up padrão',
 'Você é um pesquisador especialista. Sugira perguntas complementares relevantes com base nas respostas coletadas. Responda sempre em JSON válido.',
 'Com base no questionário "{{questionnaire_title}}" e nas respostas coletadas:\n\n{{responses_summary}}\n\nSugira perguntas de follow-up relevantes.\n\nRetorne JSON: [{question_text, question_type (text/radio/checkbox/select), options: [], rationale}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'followup_suggestions' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'statistical_analysis', 'Análise estatística padrão',
 'Você é um analista de dados. Analise os dados fornecidos e gere insights estatísticos em português. Responda sempre em JSON válido.',
 'Analise os seguintes dados do questionário "{{questionnaire_title}}" ({{total_responses}} respostas, período: {{date_range}}):\n\n{{data_summary}}\n\nRetorne JSON: {summary, patterns: [], outliers: [], trends: [], insights: [], chart_suggestions: [{type, title, data_keys}]}',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'statistical_analysis' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'natural_reports', 'Relatório narrativo padrão',
 'Você é um redator de relatórios técnicos. Gere relatórios descritivos em linguagem natural em português brasileiro, com base nos dados fornecidos. Use tom profissional e objetivo.',
 'Gere um relatório descritivo sobre os dados do questionário "{{questionnaire_title}}":\n\nPeríodo: {{date_range}}\nTotal de respostas: {{total_responses}}\nDados agregados:\n{{aggregated_data}}\n\nInclua: resumo executivo, principais achados, padrões identificados, recomendações.',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'natural_reports' AND version = 1);

INSERT INTO ai_prompts (feature_key, prompt_name, system_prompt, user_prompt_template, version)
SELECT 'smart_charts', 'Gráficos inteligentes padrão',
 'Você é um especialista em visualização de dados. Analise os dados e sugira os melhores tipos de gráficos. Responda sempre em JSON válido.',
 'Com base nos seguintes dados do questionário "{{questionnaire_title}}":\n\n{{data_summary}}\n\nSugira visualizações adequadas.\n\nRetorne JSON: [{chart_type (bar/line/pie/doughnut/radar/scatter), title, description, data_config: {labels: [], datasets: [{label, data: []}]}, rationale}]',
 1
WHERE NOT EXISTS (SELECT 1 FROM ai_prompts WHERE feature_key = 'smart_charts' AND version = 1);
