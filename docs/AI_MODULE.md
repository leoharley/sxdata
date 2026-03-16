# Módulo de Inteligência Artificial - SXData

## Visão Geral

O módulo de IA do SXData integra funcionalidades de inteligência artificial ao painel administrativo, utilizando a API da OpenAI para processamento. A arquitetura foi projetada para ser escalável e preparada para integração futura com o aplicativo móvel.

## Arquitetura

```
┌─────────────────────────────────────────────┐
│              PAINEL ADMIN (Web)              │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐  │
│  │ Views   │  │Controller│  │ Libraries │  │
│  │ admin/ai│──│  Ai.php  │──│Ai_service │  │
│  └─────────┘  └──────────┘  │Ai_prompt  │  │
│                              └─────┬─────┘  │
├────────────────────────────────────┼────────┤
│              API Layer             │         │
│  ┌──────────┐                     │         │
│  │api/Ai.php│─────────────────────┘         │
│  └──────────┘          ┌────────────────┐   │
│       ▲                │   Ai_model.php │   │
│       │                └───────┬────────┘   │
├───────┼────────────────────────┼────────────┤
│  APP MÓVEL             ┌───────▼────────┐   │
│  (futuro)              │  PostgreSQL DB  │   │
│                        │  (12 tabelas)   │   │
│                        └────────────────┘   │
└─────────────────────────────────────────────┘
                         │
                    ┌────▼─────┐
                    │ OpenAI   │
                    │ API      │
                    └──────────┘
```

## Stack Técnica

| Componente | Tecnologia |
|---|---|
| Backend | CodeIgniter 3 (PHP) |
| Banco | PostgreSQL |
| Frontend | Bootstrap 5, Chart.js, jQuery, DataTables |
| IA | OpenAI API (GPT-4o, GPT-4o-mini, Whisper) |
| Autenticação | Session (painel) + Bearer Token (API) |

## Variáveis de Ambiente

| Variável | Obrigatória | Descrição |
|---|---|---|
| `OPENAI_API_KEY` | Sim | Chave da API OpenAI |

**Como configurar:**
- Apache (.htaccess ou httpd.conf): `SetEnv OPENAI_API_KEY sk-...`
- Nginx (fastcgi_params): `fastcgi_param OPENAI_API_KEY sk-...;`
- PHP-FPM (pool.d): `env[OPENAI_API_KEY] = sk-...`
- Variável de sistema: `export OPENAI_API_KEY=sk-...`

## Tabelas Criadas

| Tabela | Descrição | Linhas estimadas |
|---|---|---|
| `ai_settings` | Configurações por funcionalidade (10 registros iniciais) | Fixa |
| `ai_prompts` | Templates de prompt versionados (8 registros iniciais) | Baixa |
| `ai_execution_logs` | Log de todas as chamadas à IA | Alta |
| `ai_transcriptions` | Transcrições de áudio | Média |
| `ai_inconsistencies` | Inconsistências detectadas | Média |
| `ai_corrections` | Correções sugeridas | Média |
| `ai_field_suggestions` | Sugestões de preenchimento | Média |
| `ai_reformulations` | Reformulações de perguntas | Baixa |
| `ai_adaptive_rules` | Regras de roteamento inteligente | Baixa |
| `ai_followup_suggestions` | Sugestões de follow-up | Baixa |
| `ai_statistical_analyses` | Análises estatísticas geradas | Baixa |
| `ai_reports` | Relatórios narrativos gerados | Baixa |

**Migration:** `sql/ai_module_migration.sql`

## Endpoints do Painel Admin

### Páginas

| URL | Método | Descrição |
|---|---|---|
| `/ai` | GET | Dashboard de IA |
| `/ai/settings` | GET | Configurações |
| `/ai/prompts` | GET | Gerenciamento de prompts |
| `/ai/logs` | GET | Logs de execução |
| `/ai/transcriptions` | GET | Transcrições de áudio |
| `/ai/inconsistencies` | GET | Detecção de inconsistências |
| `/ai/corrections` | GET | Correção de dados |
| `/ai/smart_fill` | GET | Preenchimento inteligente |
| `/ai/reformulations` | GET | Reformulação de perguntas |
| `/ai/adaptive` | GET | Questionário adaptativo |
| `/ai/followup` | GET | Sugestões de follow-up |
| `/ai/analysis` | GET | Análise estatística |
| `/ai/charts` | GET | Gráficos inteligentes |
| `/ai/reports` | GET | Relatórios narrativos |

### Ações AJAX

| URL | Método | Descrição |
|---|---|---|
| `/ai/test_connection` | POST | Testa conexão com OpenAI |
| `/ai/toggle_feature` | POST | Ativa/desativa funcionalidade |
| `/ai/update_setting` | POST | Atualiza configuração |
| `/ai/save_prompt` | POST | Cria/atualiza prompt |
| `/ai/upload_audio` | POST | Upload de áudio para transcrição |
| `/ai/process_transcription` | POST | Processa transcrição |
| `/ai/save_transcription_edit` | POST | Salva edição de transcrição |
| `/ai/analyze_inconsistencies` | POST | Analisa inconsistências (individual) |
| `/ai/resolve_inconsistency` | POST | Resolve inconsistência |
| `/ai/analyze_corrections` | POST | Analisa correções (individual) |
| `/ai/review_correction` | POST | Revisa correção |
| `/ai/generate_suggestions` | POST | Gera sugestões de preenchimento |
| `/ai/generate_reformulation` | POST | Reformula pergunta |
| `/ai/approve_reformulation` | POST | Aprova/rejeita reformulação |
| `/ai/generate_followup` | POST | Gera sugestões de follow-up |
| `/ai/generate_analysis` | POST | Gera análise estatística |
| `/ai/generate_charts` | POST | Gera sugestões de gráficos |
| `/ai/generate_report` | POST | Gera relatório narrativo |
| `/ai/batch_analyze` | POST | Análise em lote |

## Endpoints da API (para app móvel)

| URL | Método | Descrição |
|---|---|---|
| `/api/ai/status` | GET | Status do módulo de IA |
| `/api/ai/transcribe` | POST | Transcreve áudio |
| `/api/ai/check-inconsistencies` | POST | Verifica inconsistências |
| `/api/ai/suggest-fill` | POST | Sugestões de preenchimento |

Autenticação: Bearer Token no header `Authorization`.

## Funcionalidades

### 1. Transcrição de Áudio
- Modelo: Whisper-1
- Formatos: MP3, WAV, M4A, OGG, WEBM, MP4
- Limite: 25MB por arquivo
- Idioma: Português (pt-BR) padrão
- Edição manual após transcrição
- Reprocessamento disponível

### 2. Detecção de Inconsistências
- Análise individual ou em lote
- Severidades: low, medium, high, critical
- Score de consistência (0-100)
- Ações: confirmar, descartar, resolver
- Justificativa da IA para cada inconsistência

### 3. Correção e Padronização
- Tipos: ortografia, formato, padronização, completude
- Mostra original vs sugerido lado a lado
- Score de confiança
- Ações: aceitar, rejeitar, editar manualmente
- Processamento individual e em lote

### 4. Preenchimento Inteligente
- Sugestões baseadas no contexto de respostas existentes
- Score de confiança por sugestão
- Aprovação/rejeição por item

### 5. Reformulação de Perguntas
- Objetivos: clareza, reduzir viés, simplificar, público específico
- Comparação lado a lado (original vs reformulada)
- Aprovação com versionamento

### 6. Questionário Adaptativo
- Regras de roteamento inteligente por questionário
- Fallback determinístico obrigatório
- Prioridade configurável
- Auditável

### 7. Sugestões de Follow-up
- Geradas por questionário
- Incluem tipo de pergunta e opções
- Aprovação, edição ou descarte

### 8. Análise Estatística
- Filtros por questionário, período
- Identifica: padrões, outliers, tendências
- Gera insights acionáveis
- Sugere visualizações

### 9. Gráficos Inteligentes
- Sugere tipo de gráfico adequado
- Renderiza via Chart.js
- Troca manual de tipo
- Tipos: bar, line, pie, doughnut, radar, scatter

### 10. Relatórios em Linguagem Natural
- Tipos: geral, executivo, detalhado
- Texto narrativo em português
- Copiar e imprimir
- Histórico de relatórios

## Fluxo de Teste

### Pré-requisitos
1. Executar a migration: `psql -U usuario -d banco -f sql/ai_module_migration.sql`
2. Configurar a variável `OPENAI_API_KEY`
3. Reiniciar o servidor web

### Teste de Conexão
1. Acessar `/ai`
2. Clicar em "Testar Conexão"
3. Verificar se retorna "Conexão bem-sucedida"

### Teste de Funcionalidade (Detecção de Inconsistências)
1. Acessar `/ai/settings` → Habilitar "Detecção de Inconsistências"
2. Acessar `/ai/inconsistencies`
3. Inserir um `form_response_id` existente
4. Clicar em "Analisar"
5. Verificar se inconsistências são detectadas e listadas
6. Testar ações: confirmar, descartar, resolver

### Teste de Transcrição
1. Habilitar "Transcrição de Áudio" nas configurações
2. Acessar `/ai/transcriptions`
3. Fazer upload de um arquivo de áudio
4. Verificar se a transcrição é gerada automaticamente
5. Testar edição manual e reprocessamento

### Teste de Relatório
1. Habilitar "Relatórios em Linguagem Natural"
2. Acessar `/ai/reports`
3. Selecionar questionário e período
4. Clicar em "Gerar Relatório"
5. Visualizar o relatório gerado
6. Testar copiar e imprimir

## Segurança

- API Key armazenada em variável de ambiente (nunca no código)
- Todas as páginas requerem autenticação
- Logs de auditoria para todas as execuções
- Dados de entrada/saída registrados nos logs
- CORS configurado nos endpoints de API
- Validação de input em todos os formulários
- Tratamento de erro com fallback

## Custos

O módulo registra automaticamente o custo estimado de cada chamada à API:

| Modelo | Input (1K tokens) | Output (1K tokens) |
|---|---|---|
| GPT-4o | $0.0025 | $0.01 |
| GPT-4o-mini | $0.00015 | $0.0006 |
| Whisper-1 | $0.006/min | - |

Custos são visíveis no dashboard e nos logs de execução.

## Próximos Passos (App Móvel)

A API já está preparada para a integração com o app móvel:

1. **Transcrição em tempo real**: O endpoint `/api/ai/transcribe` aceita upload de áudio
2. **Verificação de inconsistências**: `/api/ai/check-inconsistencies` valida respostas em tempo real
3. **Preenchimento inteligente**: `/api/ai/suggest-fill` sugere valores durante a coleta
4. **Status de features**: `/api/ai/status` informa quais funcionalidades estão ativas

### Para implementar no app:
- Integrar endpoints de API com autenticação Bearer Token
- Implementar gravação de áudio no app e envio para transcrição
- Adicionar verificação de inconsistências após submissão
- Implementar sugestões de preenchimento em campos vazios
- Adicionar reconhecimento de voz como input alternativo
- Cache local para funcionar offline com sync posterior

## Arquivos do Módulo

```
application/
├── controllers/
│   ├── Ai.php                          # Controller admin (30+ métodos)
│   └── api/Ai.php                      # Controller API (4 endpoints)
├── models/
│   └── Ai_model.php                    # Model principal (400+ linhas)
├── libraries/
│   ├── Ai_service.php                  # Integração OpenAI (300+ linhas)
│   └── Ai_prompt_service.php           # Gerenciamento de prompts (250+ linhas)
├── views/admin/ai/
│   ├── dashboard.php                   # Dashboard de IA
│   ├── settings.php                    # Configurações
│   ├── prompts.php                     # Gerenciamento de prompts
│   ├── logs.php                        # Logs de execução
│   ├── transcriptions.php             # Transcrições de áudio
│   ├── inconsistencies.php            # Detecção de inconsistências
│   ├── corrections.php                # Correção de dados
│   ├── smart_fill.php                 # Preenchimento inteligente
│   ├── reformulations.php             # Reformulação de perguntas
│   ├── adaptive.php                   # Questionário adaptativo
│   ├── followup.php                   # Sugestões de follow-up
│   ├── analysis.php                   # Análise estatística (lista)
│   ├── analysis_view.php             # Análise estatística (detalhe)
│   ├── charts.php                     # Gráficos inteligentes
│   ├── reports.php                    # Relatórios (lista)
│   └── report_view.php               # Relatório (detalhe)
├── config/
│   └── routes.php                     # Rotas adicionadas
sql/
└── ai_module_migration.sql            # Migration do banco
docs/
└── AI_MODULE.md                       # Esta documentação
.env.example                           # Template de variáveis de ambiente
```
