-- Tabela de diretrizes/instruções do admin para o agente de IA
CREATE TABLE IF NOT EXISTS ai_directives (
    id SERIAL PRIMARY KEY,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    directive_type VARCHAR(30) NOT NULL DEFAULT 'instruction',
    priority INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    applies_to TEXT DEFAULT '[]',
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_directives_category ON ai_directives(category, is_active);
CREATE INDEX idx_ai_directives_type ON ai_directives(directive_type, is_active);
