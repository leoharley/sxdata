<style>
    .dir-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        border-left: 4px solid #ccc;
        transition: transform 0.15s, opacity 0.3s;
    }
    .dir-card:hover { transform: translateY(-1px); }
    .dir-card.type-instruction { border-left-color: var(--primary-color); }
    .dir-card.type-restriction { border-left-color: #dc3545; }
    .dir-card.type-persona { border-left-color: #6f42c1; }
    .dir-card.type-format { border-left-color: #0dcaf0; }
    .dir-card.type-context { border-left-color: #fd7e14; }
    .dir-card.disabled-card { opacity: 0.5; }

    .badge-instruction { background: var(--primary-color); color: white; }
    .badge-restriction { background: #dc3545; color: white; }
    .badge-persona { background: #6f42c1; color: white; }
    .badge-format { background: #0dcaf0; color: #333; }
    .badge-context { background: #fd7e14; color: white; }

    .badge-cat-general { background: #6c757d; color: white; }
    .badge-cat-analysis { background: #0d6efd; color: white; }
    .badge-cat-quality { background: #8fae5d; color: white; }
    .badge-cat-collection { background: #fd7e14; color: white; }
    .badge-cat-security { background: #dc3545; color: white; }
    .badge-cat-tone { background: #6f42c1; color: white; }

    .dir-section-title {
        color: var(--secondary-color);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .dir-content-preview {
        background: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        color: #333;
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 120px;
        overflow: hidden;
        position: relative;
    }
    .dir-content-preview.expanded { max-height: none; }
    .dir-content-preview .fade-overlay {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 40px;
        background: linear-gradient(transparent, #f8f9fa);
        pointer-events: none;
    }
    .dir-stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        height: 100%;
    }
    .dir-stat-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: white;
    }
    .feature-check {
        display: inline-flex;
        align-items: center;
        background: #e9ecef;
        border-radius: 1rem;
        padding: 0.15rem 0.6rem;
        font-size: 0.75rem;
        margin: 0.15rem;
    }
    .feature-check.active { background: #d4edda; color: #155724; }
    .dir-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    .dir-empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    .priority-indicator {
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }
    .priority-indicator .dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #dee2e6;
    }
    .priority-indicator .dot.filled { background: #f0ad4e; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-shield-alt me-2" style="color: var(--primary-color);"></i>Diretrizes da IA</h2>
        <small class="text-muted">Configure instruções, regras e limites de segurança para o agente de IA</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#directiveModal" onclick="resetForm()">
            <i class="fas fa-plus me-1"></i>Nova Diretriz
        </button>
        <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<!-- Stats Row -->
<?php
    $total = count($directives);
    $active_count = 0;
    $type_counts = array('instruction' => 0, 'restriction' => 0, 'persona' => 0, 'format' => 0, 'context' => 0);
    foreach ($directives as $d) {
        $d = (object)$d;
        if ($d->is_active) $active_count++;
        if (isset($type_counts[$d->directive_type])) $type_counts[$d->directive_type]++;
    }
?>
<div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-6">
        <div class="dir-stat-card">
            <div class="d-flex align-items-center">
                <div class="dir-stat-icon" style="background: linear-gradient(135deg, var(--primary-color), #1a2847);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: var(--secondary-color);"><?= $total ?></h4>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="dir-stat-card">
            <div class="d-flex align-items-center">
                <div class="dir-stat-icon" style="background: linear-gradient(135deg, #8fae5d, #6d8a45);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #8fae5d;"><?= $active_count ?></h4>
                    <small class="text-muted">Ativas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="dir-stat-card">
            <div class="d-flex align-items-center">
                <div class="dir-stat-icon" style="background: linear-gradient(135deg, #dc3545, #b02a37);">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #dc3545;"><?= $type_counts['restriction'] ?></h4>
                    <small class="text-muted">Restrições</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="dir-stat-card">
            <div class="d-flex align-items-center">
                <div class="dir-stat-icon" style="background: linear-gradient(135deg, var(--primary-color), #1a2847);">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: var(--secondary-color);"><?= $type_counts['instruction'] ?></h4>
                    <small class="text-muted">Instruções</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="dir-stat-card">
            <div class="d-flex align-items-center">
                <div class="dir-stat-icon" style="background: linear-gradient(135deg, #6f42c1, #5a359e);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #6f42c1;"><?= $type_counts['persona'] ?></h4>
                    <small class="text-muted">Persona</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="alert border-0 mb-4" style="background: linear-gradient(135deg, #e8edf5, #f0f4fa); border-left: 4px solid var(--primary-color) !important;">
    <div class="d-flex">
        <i class="fas fa-info-circle me-3 mt-1" style="color: var(--primary-color); font-size: 1.2rem;"></i>
        <div>
            <strong>Como funciona?</strong>
            <p class="mb-1 small">As diretrizes ativas são injetadas automaticamente nos prompts enviados à IA, funcionando como um "ajuste fino" no comportamento do agente. Defina:</p>
            <ul class="mb-0 small">
                <li><strong>Instruções</strong> — O que a IA deve fazer (ex: "Sempre justifique suas análises com dados")</li>
                <li><strong>Restrições</strong> — O que a IA NUNCA deve fazer (ex: "Nunca inventar dados que não existem")</li>
                <li><strong>Persona</strong> — Como a IA deve se comportar (ex: "Aja como um pesquisador experiente")</li>
                <li><strong>Formato</strong> — Como estruturar respostas (ex: "Responda sempre em português formal")</li>
                <li><strong>Contexto</strong> — Informações de fundo (ex: "Este sistema é usado por pesquisadores acadêmicos")</li>
            </ul>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="dir-section-title mb-0"><i class="fas fa-list me-2"></i>Diretrizes Configuradas</h5>
    <div class="d-flex gap-2">
        <select id="filterType" class="form-select form-select-sm" style="width: auto;" onchange="filterDirectives()">
            <option value="">Todos os Tipos</option>
            <option value="instruction">Instruções</option>
            <option value="restriction">Restrições</option>
            <option value="persona">Persona</option>
            <option value="format">Formato</option>
            <option value="context">Contexto</option>
        </select>
        <select id="filterCategory" class="form-select form-select-sm" style="width: auto;" onchange="filterDirectives()">
            <option value="">Todas as Categorias</option>
            <option value="general">Geral</option>
            <option value="analysis">Análise</option>
            <option value="quality">Qualidade</option>
            <option value="collection">Coleta</option>
            <option value="security">Segurança</option>
            <option value="tone">Tom/Linguagem</option>
        </select>
        <?php if (!empty($directives)): ?>
        <button class="btn btn-sm btn-outline-danger" id="btnClearAll" onclick="clearAllDirectives()">
            <i class="fas fa-trash me-1"></i>Limpar Todas
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Directives List -->
<?php if (empty($directives)): ?>
    <div class="dir-empty-state">
        <i class="fas fa-shield-alt d-block"></i>
        <h5>Nenhuma diretriz configurada</h5>
        <p class="mb-3">Adicione diretrizes para personalizar o comportamento do agente de IA.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#directiveModal" onclick="resetForm()">
            <i class="fas fa-plus me-1"></i>Criar Primeira Diretriz
        </button>
    </div>
<?php else: ?>
    <div id="directivesList">
        <?php
            $type_labels = array(
                'instruction' => 'Instrução',
                'restriction' => 'Restrição',
                'persona' => 'Persona',
                'format' => 'Formato',
                'context' => 'Contexto',
            );
            $cat_labels = array(
                'general' => 'Geral',
                'analysis' => 'Análise',
                'quality' => 'Qualidade',
                'collection' => 'Coleta',
                'security' => 'Segurança',
                'tone' => 'Tom/Linguagem',
            );
            $feature_labels = array(
                'inconsistency_detection' => 'Inconsistências',
                'data_correction' => 'Correções',
                'smart_fill' => 'Preenchimento',
                'question_reformulation' => 'Reformulação',
                'adaptive_routing' => 'Adaptativo',
                'followup_suggestions' => 'Follow-up',
                'statistical_analysis' => 'Estatística',
                'smart_charts' => 'Gráficos',
                'natural_reports' => 'Relatórios',
            );
        ?>
        <?php foreach ($directives as $d):
            $d = (object)$d;
            $type = $d->directive_type ?? 'instruction';
            $cat = $d->category ?? 'general';
            $applies = json_decode($d->applies_to ?? '[]', true);
            if (!is_array($applies)) $applies = array();
            $priority = (int)($d->priority ?? 0);
        ?>
        <div class="dir-card type-<?= $type ?> <?= !$d->is_active ? 'disabled-card' : '' ?>"
             data-id="<?= (int)$d->id ?>"
             data-type="<?= $type ?>"
             data-category="<?= $cat ?>"
             id="dir-<?= (int)$d->id ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge badge-<?= $type ?>"><?= $type_labels[$type] ?? ucfirst($type) ?></span>
                        <span class="badge badge-cat-<?= $cat ?>"><?= $cat_labels[$cat] ?? ucfirst($cat) ?></span>
                        <?php if (!$d->is_active): ?>
                            <span class="badge bg-secondary">Inativa</span>
                        <?php endif; ?>
                        <div class="priority-indicator" title="Prioridade: <?= $priority ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="dot <?= $i <= $priority ? 'filled' : '' ?>"></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   <?= $d->is_active ? 'checked' : '' ?>
                                   onchange="toggleDirective(<?= (int)$d->id ?>, this.checked)"
                                   title="<?= $d->is_active ? 'Desativar' : 'Ativar' ?>">
                        </div>
                        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editDirective(<?= (int)$d->id ?>)" title="Editar">
                            <i class="fas fa-pen fa-xs"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteDirective(<?= (int)$d->id ?>)" title="Excluir">
                            <i class="fas fa-trash fa-xs"></i>
                        </button>
                    </div>
                </div>

                <h6 class="mb-2"><?= htmlspecialchars($d->title) ?></h6>

                <div class="dir-content-preview" id="content-<?= (int)$d->id ?>">
                    <?= htmlspecialchars($d->content) ?>
                    <div class="fade-overlay"></div>
                </div>
                <button class="btn btn-link btn-sm p-0 mt-1 btn-expand" onclick="toggleExpand(<?= (int)$d->id ?>)">
                    <small>Ver mais</small>
                </button>

                <?php if (!empty($applies)): ?>
                <div class="mt-2">
                    <small class="text-muted">Aplica-se a:</small>
                    <?php foreach ($applies as $fk): ?>
                        <span class="feature-check active"><?= $feature_labels[$fk] ?? $fk ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="mt-2">
                    <small class="text-muted"><i class="fas fa-globe me-1"></i>Aplica-se a todas as funcionalidades</small>
                </div>
                <?php endif; ?>

                <div class="mt-2 text-end">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($d->created_at)) ?>
                        <?php if (!empty($d->updated_at) && $d->updated_at !== $d->created_at): ?>
                            &middot; Atualizado em <?= date('d/m/Y H:i', strtotime($d->updated_at)) ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Criar/Editar Diretriz -->
<div class="modal fade" id="directiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #1a2847); color: white;">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-shield-alt me-2"></i>Nova Diretriz</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="directiveForm">
                    <input type="hidden" id="directiveId" name="id" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="directiveTitle" name="title" required
                                   placeholder="Ex: Nunca inventar dados inexistentes">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="directiveType" name="directive_type" required>
                                <option value="instruction">Instrução</option>
                                <option value="restriction">Restrição / Limite</option>
                                <option value="persona">Persona / Comportamento</option>
                                <option value="format">Formato de Resposta</option>
                                <option value="context">Contexto / Informação</option>
                            </select>
                            <small class="text-muted" id="typeHint">O que a IA deve fazer</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Categoria</label>
                            <select class="form-select" id="directiveCategory" name="category">
                                <option value="general">Geral</option>
                                <option value="analysis">Análise</option>
                                <option value="quality">Qualidade</option>
                                <option value="collection">Coleta</option>
                                <option value="security">Segurança</option>
                                <option value="tone">Tom / Linguagem</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Prioridade</label>
                            <select class="form-select" id="directivePriority" name="priority">
                                <option value="1">1 - Baixa</option>
                                <option value="2">2 - Normal</option>
                                <option value="3" selected>3 - Média</option>
                                <option value="4">4 - Alta</option>
                                <option value="5">5 - Máxima</option>
                            </select>
                            <small class="text-muted">Diretrizes de maior prioridade são processadas primeiro</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Conteúdo da Diretriz <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="directiveContent" name="content" rows="5" required
                                      placeholder="Descreva detalhadamente a instrução, restrição ou regra que a IA deve seguir..."></textarea>
                            <small class="text-muted">Seja claro e específico. Exemplo de restrição: "Nunca afirme que consegue enviar áudios ou e-mails. Sempre informe que é um assistente de análise de dados."</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Aplica-se a quais funcionalidades?</label>
                            <small class="text-muted d-block mb-2">Deixe tudo desmarcado para aplicar a todas as funcionalidades.</small>
                            <div class="row g-2">
                                <?php
                                $features = array(
                                    'inconsistency_detection' => array('icon' => 'fa-search', 'label' => 'Inconsistências'),
                                    'data_correction' => array('icon' => 'fa-spell-check', 'label' => 'Correções'),
                                    'smart_fill' => array('icon' => 'fa-magic', 'label' => 'Preenchimento Inteligente'),
                                    'question_reformulation' => array('icon' => 'fa-edit', 'label' => 'Reformulação'),
                                    'adaptive_routing' => array('icon' => 'fa-route', 'label' => 'Adaptativo'),
                                    'followup_suggestions' => array('icon' => 'fa-comments', 'label' => 'Follow-up'),
                                    'statistical_analysis' => array('icon' => 'fa-chart-line', 'label' => 'Estatística'),
                                    'smart_charts' => array('icon' => 'fa-chart-pie', 'label' => 'Gráficos'),
                                    'natural_reports' => array('icon' => 'fa-file-alt', 'label' => 'Relatórios'),
                                );
                                foreach ($features as $fk => $fi):
                                ?>
                                <div class="col-md-4 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input feature-checkbox" type="checkbox" value="<?= $fk ?>" id="feat_<?= $fk ?>">
                                        <label class="form-check-label small" for="feat_<?= $fk ?>">
                                            <i class="fas <?= $fi['icon'] ?> me-1 text-muted"></i><?= $fi['label'] ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="directiveActive" name="is_active" checked>
                                <label class="form-check-label fw-semibold" for="directiveActive">Diretriz ativa</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveDirective" onclick="saveDirective()">
                    <i class="fas fa-save me-1"></i>Salvar Diretriz
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Templates de exemplo -->
<div class="modal fade" id="templatesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lightbulb me-2"></i>Templates de Exemplo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button class="list-group-item list-group-item-action" onclick="applyTemplate('restriction', 'security', 'Nunca inventar dados', 'Nunca invente, fabrique ou suponha dados que não estejam explicitamente presentes nas respostas do questionário. Se a informação não existir, informe claramente que os dados não estão disponíveis.')">
                        <strong class="text-danger"><i class="fas fa-ban me-1"></i>Não inventar dados</strong>
                        <br><small class="text-muted">Restrição de segurança</small>
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="applyTemplate('instruction', 'tone', 'Responder em português formal', 'Sempre responda em português brasileiro formal. Use linguagem acadêmica e profissional. Evite gírias, abreviações ou linguagem coloquial.')">
                        <strong class="text-primary"><i class="fas fa-language me-1"></i>Português formal</strong>
                        <br><small class="text-muted">Tom e linguagem</small>
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="applyTemplate('persona', 'general', 'Pesquisador especialista', 'Comporte-se como um pesquisador experiente em metodologia de pesquisa e análise de dados qualitativos e quantitativos. Fundamente suas análises em evidências dos dados coletados.')">
                        <strong class="text-purple" style="color:#6f42c1;"><i class="fas fa-user-tie me-1"></i>Persona de pesquisador</strong>
                        <br><small class="text-muted">Comportamento</small>
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="applyTemplate('restriction', 'security', 'Limites do assistente', 'Nunca afirme que consegue enviar áudios, e-mails, mensagens ou realizar ações externas. Você é um assistente de análise de dados e seu escopo é limitado à análise, interpretação e sugestões baseadas nos dados do questionário.')">
                        <strong class="text-danger"><i class="fas fa-shield-alt me-1"></i>Limites de capacidade</strong>
                        <br><small class="text-muted">Segurança</small>
                    </button>
                    <button class="list-group-item list-group-item-action" onclick="applyTemplate('format', 'analysis', 'Formato de análise estruturada', 'Estruture todas as análises em seções claras: 1) Resumo Executivo, 2) Principais Descobertas, 3) Pontos de Atenção, 4) Recomendações. Use bullet points e numeração para facilitar a leitura.')">
                        <strong class="text-info"><i class="fas fa-align-left me-1"></i>Análise estruturada</strong>
                        <br><small class="text-muted">Formato</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function waitForJQuery() {
    if (typeof jQuery === 'undefined') return setTimeout(waitForJQuery, 50);
    jQuery(function($) {

    var BASE = '<?= base_url() ?>';

    var typeHints = {
        'instruction': 'O que a IA deve fazer',
        'restriction': 'O que a IA NUNCA deve fazer',
        'persona': 'Como a IA deve se comportar',
        'format': 'Como estruturar as respostas',
        'context': 'Informação de fundo para a IA'
    };

    $('#directiveType').on('change', function() {
        $('#typeHint').text(typeHints[$(this).val()] || '');
    });

    // Expand/collapse content
    window.toggleExpand = function(id) {
        var $el = $('#content-' + id);
        $el.toggleClass('expanded');
        var $btn = $el.next('.btn-expand');
        $btn.find('small').text($el.hasClass('expanded') ? 'Ver menos' : 'Ver mais');
        $el.find('.fade-overlay').toggle(!$el.hasClass('expanded'));
    };

    // Filter
    window.filterDirectives = function() {
        var type = $('#filterType').val();
        var cat = $('#filterCategory').val();
        $('.dir-card').each(function() {
            var show = true;
            if (type && $(this).data('type') !== type) show = false;
            if (cat && $(this).data('category') !== cat) show = false;
            $(this).toggle(show);
        });
    };

    // Reset form
    window.resetForm = function() {
        $('#directiveId').val('');
        $('#directiveForm')[0].reset();
        $('#directivePriority').val('3');
        $('#directiveActive').prop('checked', true);
        $('.feature-checkbox').prop('checked', false);
        $('#modalTitle').html('<i class="fas fa-shield-alt me-2"></i>Nova Diretriz');
    };

    // Apply template
    window.applyTemplate = function(type, category, title, content) {
        $('#directiveType').val(type).trigger('change');
        $('#directiveCategory').val(category);
        $('#directiveTitle').val(title);
        $('#directiveContent').val(content);
        var modal = bootstrap.Modal.getInstance(document.getElementById('templatesModal'));
        if (modal) modal.hide();
    };

    // Save
    window.saveDirective = function() {
        var title = $('#directiveTitle').val().trim();
        var content = $('#directiveContent').val().trim();
        if (!title || !content) {
            alert('Preencha título e conteúdo.');
            return;
        }

        var features = [];
        $('.feature-checkbox:checked').each(function() { features.push($(this).val()); });

        var payload = {
            id: $('#directiveId').val() || '',
            title: title,
            content: content,
            directive_type: $('#directiveType').val(),
            category: $('#directiveCategory').val(),
            priority: $('#directivePriority').val(),
            is_active: $('#directiveActive').is(':checked') ? 1 : 0,
            applies_to: JSON.stringify(features)
        };

        var $btn = $('#btnSaveDirective');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Salvando...');

        $.ajax({
            url: BASE + 'ai/save_directive',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro ao salvar.');
            },
            error: function() { alert('Erro de comunicação.'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Salvar Diretriz');
            }
        });
    };

    // Edit
    window.editDirective = function(id) {
        $.ajax({
            url: BASE + 'ai/get_directive',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (!res.success) { alert(res.message || 'Erro.'); return; }
                var d = res.directive;
                $('#directiveId').val(d.id);
                $('#directiveTitle').val(d.title);
                $('#directiveContent').val(d.content);
                $('#directiveType').val(d.directive_type).trigger('change');
                $('#directiveCategory').val(d.category);
                $('#directivePriority').val(d.priority);
                $('#directiveActive').prop('checked', d.is_active == 1);

                $('.feature-checkbox').prop('checked', false);
                var applies = [];
                try { applies = JSON.parse(d.applies_to || '[]'); } catch(e) {}
                applies.forEach(function(fk) {
                    $('#feat_' + fk).prop('checked', true);
                });

                $('#modalTitle').html('<i class="fas fa-pen me-2"></i>Editar Diretriz');
                var modal = new bootstrap.Modal(document.getElementById('directiveModal'));
                modal.show();
            },
            error: function() { alert('Erro ao carregar diretriz.'); }
        });
    };

    // Toggle active
    window.toggleDirective = function(id, active) {
        $.ajax({
            url: BASE + 'ai/toggle_directive',
            type: 'POST',
            data: { id: id, is_active: active ? 1 : 0 },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var $card = $('#dir-' + id);
                    $card.toggleClass('disabled-card', !active);
                    var $badge = $card.find('.badge.bg-secondary');
                    if (!active && $badge.length === 0) {
                        $card.find('.badge:first').after('<span class="badge bg-secondary ms-1">Inativa</span>');
                    } else if (active) {
                        $badge.remove();
                    }
                } else {
                    alert(res.message || 'Erro.');
                }
            },
            error: function() { alert('Erro de comunicação.'); }
        });
    };

    // Delete
    window.deleteDirective = function(id) {
        if (!confirm('Excluir esta diretriz?')) return;
        $.ajax({
            url: BASE + 'ai/delete_directive',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#dir-' + id).fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(res.message || 'Erro.');
                }
            },
            error: function() { alert('Erro de comunicação.'); }
        });
    };

    // Clear all
    window.clearAllDirectives = function() {
        if (!confirm('Excluir TODAS as diretrizes? Esta ação não pode ser desfeita.')) return;
        var $btn = $('#btnClearAll');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Limpando...');
        $.ajax({
            url: BASE + 'ai/clear_directives',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) location.reload();
                else { alert(res.message || 'Erro.'); $btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Limpar Todas'); }
            },
            error: function() { alert('Erro de comunicação.'); $btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Limpar Todas'); }
        });
    };

    });
})();
</script>
