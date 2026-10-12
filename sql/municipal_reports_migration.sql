-- ============================================================
-- Relatórios municipais automatizados (proposta de evolução, frentes A a F)
-- ============================================================
-- PostgreSQL 9.5+ (usa INSERT ... ON CONFLICT). Pode ser executado mais de
-- uma vez.
--
-- Fluxo: relatório criado -> conectores gravam indicadores -> equipe confere
-- seções e gráficos -> aprovação interna -> aprovação final gera uma versão
-- congelada, que é a que o cliente vê.

-- Municípios do IBGE, para a busca na criação do relatório.
-- Preenchida pela integração (ver docs/PROMPT_SERGIO_INTEGRACOES.md).
CREATE TABLE IF NOT EXISTS ibge_municipios (
    codigo     INTEGER      PRIMARY KEY,   -- código IBGE de 7 dígitos
    nome       VARCHAR(150) NOT NULL,
    uf         CHAR(2)      NOT NULL,
    codigo_uf  SMALLINT     NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_ibge_municipios_uf_nome ON ibge_municipios (uf, nome);

CREATE TABLE IF NOT EXISTS municipal_reports (
    id                 SERIAL       PRIMARY KEY,
    municipio_nome     VARCHAR(150) NOT NULL,
    uf                 CHAR(2)      NOT NULL,
    codigo_ibge        INTEGER      NOT NULL,
    periodo_referencia VARCHAR(20)  NOT NULL,          -- ex.: '2026'
    eixos              TEXT         NOT NULL,          -- JSON: chaves dos eixos incluídos
    project_id         INTEGER      REFERENCES projects(id) ON DELETE SET NULL,
    -- processing | awaiting_review | in_review | approved | published
    status             VARCHAR(30)  NOT NULL DEFAULT 'processing',
    versao_atual       INTEGER      NOT NULL DEFAULT 0, -- última versão aprovada para o cliente
    created_by         INTEGER      REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_municipal_reports_status ON municipal_reports (status);
CREATE INDEX IF NOT EXISTS idx_municipal_reports_ibge   ON municipal_reports (codigo_ibge);

-- Uma seção por eixo temático
CREATE TABLE IF NOT EXISTS report_sections (
    id                   SERIAL       PRIMARY KEY,
    report_id            INTEGER      NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    eixo                 VARCHAR(50)  NOT NULL,
    titulo               VARCHAR(200) NOT NULL,
    ordem                SMALLINT     NOT NULL DEFAULT 0,
    texto                TEXT,                         -- vai para o cliente
    observacoes_internas TEXT,                         -- NUNCA vai para o cliente
    checklist            TEXT,                         -- JSON {fonte, periodo, texto}
    -- pending | in_review | adjustment_requested | approved
    status               VARCHAR(30)  NOT NULL DEFAULT 'pending',
    incluir_no_final     BOOLEAN      NOT NULL DEFAULT TRUE,
    revisado_por         INTEGER      REFERENCES users(id) ON DELETE SET NULL,
    revisado_em          TIMESTAMP,
    UNIQUE (report_id, eixo)
);

-- Dados no formato longo: um valor por indicador, abrangência, período e
-- categoria. É o que os conectores gravam.
CREATE TABLE IF NOT EXISTS report_indicators (
    id            SERIAL        PRIMARY KEY,
    report_id     INTEGER       NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    indicador     VARCHAR(80)   NOT NULL,              -- chave do catálogo
    abrangencia   VARCHAR(10)   NOT NULL,              -- municipio | estado | brasil
    periodo       VARCHAR(20)   NOT NULL DEFAULT '',   -- '' quando não se aplica
    categoria     VARCHAR(150)  NOT NULL DEFAULT '',   -- '' quando não se aplica
    valor         NUMERIC(20,6),
    valor_texto   TEXT,                                -- informações qualitativas
    unidade       VARCHAR(40),
    fonte         VARCHAR(200),
    periodo_fonte VARCHAR(40),
    origem        VARCHAR(20)   NOT NULL DEFAULT 'api', -- api | manual
    conector      VARCHAR(80),
    atualizado_em TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (report_id, indicador, abrangencia, periodo, categoria)
);
CREATE INDEX IF NOT EXISTS idx_report_indicators_report ON report_indicators (report_id, indicador);

CREATE TABLE IF NOT EXISTS report_charts (
    id                   SERIAL       PRIMARY KEY,
    report_id            INTEGER      NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    section_id           INTEGER      NOT NULL REFERENCES report_sections(id) ON DELETE CASCADE,
    grafico              VARCHAR(80)  NOT NULL,        -- chave do catálogo
    titulo               VARCHAR(200) NOT NULL,
    texto                TEXT,                         -- texto interpretativo (vai para o cliente)
    observacoes_internas TEXT,
    -- pending | approved | rejected | adjustment_requested
    status               VARCHAR(30)  NOT NULL DEFAULT 'pending',
    incluir_no_final     BOOLEAN      NOT NULL DEFAULT TRUE,
    ordem                SMALLINT     NOT NULL DEFAULT 0,
    revisado_por         INTEGER      REFERENCES users(id) ON DELETE SET NULL,
    revisado_em          TIMESTAMP,
    UNIQUE (report_id, grafico)
);

-- Situação de cada fonte/conector no processamento do relatório
CREATE TABLE IF NOT EXISTS report_sources (
    id            SERIAL       PRIMARY KEY,
    report_id     INTEGER      NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    conector      VARCHAR(80)  NOT NULL,
    rotulo        VARCHAR(150),
    status        VARCHAR(20)  NOT NULL DEFAULT 'pending', -- pending | ok | error
    mensagem      TEXT,
    linhas        INTEGER      NOT NULL DEFAULT 0,
    executado_em  TIMESTAMP,
    UNIQUE (report_id, conector)
);

-- Linha do tempo do relatório (auditoria)
CREATE TABLE IF NOT EXISTS report_events (
    id          SERIAL      PRIMARY KEY,
    report_id   INTEGER     NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    tipo        VARCHAR(40) NOT NULL,
    descricao   TEXT,
    user_id     INTEGER     REFERENCES users(id) ON DELETE SET NULL,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_report_events_report ON report_events (report_id, created_at);

-- Versões aprovadas para o cliente: conteúdo congelado no momento da
-- aprovação. Edições posteriores não alteram o que o cliente já recebeu.
CREATE TABLE IF NOT EXISTS report_versions (
    id                  SERIAL    PRIMARY KEY,
    report_id           INTEGER   NOT NULL REFERENCES municipal_reports(id) ON DELETE CASCADE,
    versao              INTEGER   NOT NULL,
    conteudo            TEXT      NOT NULL,     -- JSON com seções, textos, gráficos e dados
    observacoes_finais  TEXT,                   -- interna
    aprovado_por        INTEGER   REFERENCES users(id) ON DELETE SET NULL,
    aprovado_em         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (report_id, versao)
);
