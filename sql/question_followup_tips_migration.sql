CREATE TABLE question_followup_tips (
    id SERIAL PRIMARY KEY,
    question_id INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    tip TEXT NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'manual',
    ai_suggestion_id INTEGER DEFAULT NULL,
    created_by INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_followup_tips_question ON question_followup_tips(question_id);
