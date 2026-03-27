-- Tabela de relacionamento entre projetos e usuários clientes
CREATE TABLE IF NOT EXISTS project_clients (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(project_id, user_id)
);

CREATE INDEX idx_project_clients_project ON project_clients(project_id);
CREATE INDEX idx_project_clients_user ON project_clients(user_id);
