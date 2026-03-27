-- Adicionar colunas para transcrições sincronizadas do app
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS app_id VARCHAR(36) UNIQUE;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS questionnaire_id INTEGER REFERENCES questionnaires(id) ON DELETE SET NULL;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS question_text TEXT;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS edited_text TEXT;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS duration_ms INTEGER;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS recording_duration_secs INTEGER DEFAULT 0;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS applicator_name VARCHAR(255);
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS applicator_id INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS timestamp_app TIMESTAMP;
ALTER TABLE ai_transcriptions ADD COLUMN IF NOT EXISTS source VARCHAR(20) DEFAULT 'upload';

CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_app_id ON ai_transcriptions(app_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_questionnaire ON ai_transcriptions(questionnaire_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_applicator ON ai_transcriptions(applicator_id);
CREATE INDEX IF NOT EXISTS idx_ai_transcriptions_timestamp_app ON ai_transcriptions(timestamp_app);
