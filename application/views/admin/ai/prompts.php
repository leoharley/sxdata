<style>
    .prompt-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 0.75rem;
        overflow: hidden;
    }
    .prompt-card-header {
        padding: 1rem 1.25rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.15s;
    }
    .prompt-card-header:hover {
        background: #f8f9fa;
    }
    .prompt-card-body {
        padding: 0 1.25rem 1.25rem;
        border-top: 1px solid #eee;
    }
    .prompt-content-box {
        background: #f4f6f9;
        border: 1px solid #e2e6ea;
        border-radius: 0.375rem;
        padding: 1rem;
        font-family: 'Courier New', Consolas, monospace;
        font-size: 0.85rem;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 300px;
        overflow-y: auto;
        color: #333;
    }
    .prompt-group-title {
        color: var(--secondary-color);
        font-weight: 600;
        font-size: 1.05rem;
        border-left: 4px solid var(--primary-color);
        padding-left: 0.75rem;
        margin-bottom: 1rem;
        margin-top: 1.5rem;
    }
    .prompt-group-title:first-of-type {
        margin-top: 0;
    }
    .badge-active {
        background: #8fae5d;
        color: white;
    }
    .badge-inactive {
        background: #dc3545;
        color: white;
    }
    .badge-version {
        background: var(--secondary-color);
        color: white;
    }
    .ai-section-title {
        color: var(--secondary-color);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .template-var-list {
        columns: 2;
        -webkit-columns: 2;
        column-gap: 2rem;
    }
    .template-var-list li {
        break-inside: avoid;
        margin-bottom: 0.35rem;
    }
    .template-var-list code {
        background: #e8edf2;
        padding: 0.15rem 0.4rem;
        border-radius: 3px;
        font-size: 0.82rem;
        color: var(--secondary-color);
    }
    .modal-prompt-textarea {
        font-family: 'Courier New', Consolas, monospace;
        font-size: 0.88rem;
        min-height: 160px;
        resize: vertical;
    }
    .chevron-icon {
        transition: transform 0.25s;
        color: #aaa;
    }
    .chevron-icon.rotated {
        transform: rotate(180deg);
    }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= base_url('ai') ?>" class="text-decoration-none text-muted me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="d-inline-block mb-0">
            <i class="fas fa-scroll me-2" style="color: var(--primary-color);"></i>Gerenciamento de Prompts
        </h2>
    </div>
    <button class="btn btn-primary" onclick="openPromptModal()">
        <i class="fas fa-plus me-1"></i>Novo Prompt
    </button>
</div>

<!-- Info Box: Template Variables -->
<div class="card mb-4 border-0" style="background: #f0f4e8;">
    <div class="card-body">
        <h6 class="mb-2" style="color: var(--secondary-color);">
            <i class="fas fa-info-circle me-1" style="color: var(--primary-color);"></i>
            Variáveis de Template Disponíveis
        </h6>
        <p class="text-muted mb-2" style="font-size: 0.88rem;">
            Utilize a sintaxe <code>{{variavel}}</code> nos templates de prompt do usuário. As variáveis serão substituídas automaticamente durante a execução.
        </p>
        <ul class="template-var-list mb-0 list-unstyled" style="font-size: 0.88rem;">
            <li><code>{{questionnaire_title}}</code> — Título do questionário</li>
            <li><code>{{responses_data}}</code> — Dados das respostas</li>
            <li><code>{{field_data}}</code> — Dados do campo</li>
            <li><code>{{existing_answers}}</code> — Respostas já existentes</li>
            <li><code>{{pending_fields}}</code> — Campos pendentes</li>
            <li><code>{{original_question}}</code> — Pergunta original</li>
            <li><code>{{objective}}</code> — Objetivo da pesquisa</li>
            <li><code>{{context}}</code> — Contexto geral</li>
            <li><code>{{responses_summary}}</code> — Resumo das respostas</li>
            <li><code>{{total_responses}}</code> — Total de respostas</li>
            <li><code>{{date_range}}</code> — Período de datas</li>
            <li><code>{{data_summary}}</code> — Resumo dos dados</li>
            <li><code>{{aggregated_data}}</code> — Dados agregados</li>
        </ul>
    </div>
</div>

<!-- Prompts List Grouped by Feature -->
<?php
    $grouped = [];
    if (!empty($prompts)) {
        foreach ($prompts as $prompt) {
            $grouped[$prompt->feature_key][] = $prompt;
        }
    }
?>

<?php if (empty($grouped)): ?>
    <div class="text-center py-5">
        <i class="fas fa-scroll fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">Nenhum prompt cadastrado ainda.</p>
        <button class="btn btn-primary" onclick="openPromptModal()">
            <i class="fas fa-plus me-1"></i>Criar Primeiro Prompt
        </button>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $featureKey => $featurePrompts): ?>
        <?php
            $featureLabel = $featureKey;
            if (!empty($settings)) {
                foreach ($settings as $s) {
                    if ($s->feature_key === $featureKey) {
                        $featureLabel = $s->feature_name ?? $featureKey;
                        break;
                    }
                }
            }
        ?>
        <div class="prompt-group-title">
            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($featureLabel) ?>
            <span class="badge rounded-pill bg-secondary ms-2" style="font-size: 0.7rem;"><?= count($featurePrompts) ?></span>
        </div>

        <?php foreach ($featurePrompts as $prompt): ?>
            <div class="prompt-card" id="prompt-card-<?= $prompt->id ?>">
                <div class="prompt-card-header" onclick="togglePrompt(<?= $prompt->id ?>)">
                    <div class="d-flex align-items-center">
                        <div>
                            <strong style="color: var(--secondary-color);"><?= htmlspecialchars($prompt->prompt_name) ?></strong>
                            <span class="badge badge-version ms-2">v<?= htmlspecialchars($prompt->version) ?></span>
                            <?php if ($prompt->is_active): ?>
                                <span class="badge badge-active ms-1">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-inactive ms-1">Inativo</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y H:i', strtotime($prompt->created_at)) ?>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-primary me-1" title="Editar"
                                onclick="event.stopPropagation(); editPrompt(<?= $prompt->id ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary me-2" title="Duplicar"
                                onclick="event.stopPropagation(); duplicatePrompt(<?= $prompt->id ?>)">
                            <i class="fas fa-copy"></i>
                        </button>
                        <i class="fas fa-chevron-down chevron-icon" id="chevron-<?= $prompt->id ?>"></i>
                    </div>
                </div>

                <div class="prompt-card-body" id="prompt-body-<?= $prompt->id ?>" style="display: none;">
                    <div class="mt-3">
                        <label class="fw-bold text-muted mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-cog me-1"></i>System Prompt
                        </label>
                        <div class="prompt-content-box"><?= htmlspecialchars($prompt->system_prompt) ?></div>
                    </div>
                    <div class="mt-3">
                        <label class="fw-bold text-muted mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-user me-1"></i>User Prompt Template
                        </label>
                        <div class="prompt-content-box"><?= htmlspecialchars($prompt->user_prompt_template) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal: Create/Edit Prompt -->
<div class="modal fade" id="promptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <?= form_open(base_url('ai/save_prompt'), ['id' => 'promptForm']) ?>
                <input type="hidden" name="id" id="prompt_id" value="">

                <div class="modal-header" style="background: var(--secondary-color); color: white;">
                    <h5 class="modal-title" id="promptModalTitle">
                        <i class="fas fa-scroll me-2"></i>Novo Prompt
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Feature Select -->
                        <div class="col-md-6">
                            <label for="feature_key" class="form-label fw-bold">
                                Funcionalidade <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="feature_key" id="feature_key" required>
                                <option value="">Selecione...</option>
                                <?php if (!empty($settings)): ?>
                                    <?php foreach ($settings as $setting): ?>
                                        <option value="<?= htmlspecialchars($setting->feature_key) ?>">
                                            <?= htmlspecialchars($setting->feature_name ?? $setting->feature_key) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Prompt Name -->
                        <div class="col-md-6">
                            <label for="prompt_name" class="form-label fw-bold">
                                Nome do Prompt <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="prompt_name" id="prompt_name"
                                   placeholder="Ex: Análise de Consistência v2" required>
                        </div>

                        <!-- System Prompt -->
                        <div class="col-12">
                            <label for="system_prompt" class="form-label fw-bold">
                                System Prompt <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control modal-prompt-textarea" name="system_prompt" id="system_prompt"
                                      rows="6" placeholder="Instruções de comportamento para a IA..." required></textarea>
                            <div class="form-text">Define o papel e comportamento da IA para esta tarefa.</div>
                        </div>

                        <!-- User Prompt Template -->
                        <div class="col-12">
                            <label for="user_prompt_template" class="form-label fw-bold">
                                Template do Prompt do Usuário <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control modal-prompt-textarea" name="user_prompt_template" id="user_prompt_template"
                                      rows="6" placeholder="Analise os seguintes dados: {{responses_data}}..." required></textarea>
                            <div class="form-text">
                                <i class="fas fa-lightbulb text-warning me-1"></i>
                                Use a sintaxe <code>{{variavel}}</code> para inserir dados dinamicamente.
                                Consulte a lista de variáveis disponíveis acima.
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label fw-bold" for="is_active">
                                    Prompt Ativo
                                </label>
                            </div>
                            <div class="form-text">Apenas um prompt ativo por funcionalidade será utilizado nas execuções.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Prompt
                    </button>
                </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
// Prompt data for editing
var promptsData = <?= json_encode($prompts ?? []) ?>;

function togglePrompt(id) {
    var body = document.getElementById('prompt-body-' + id);
    var chevron = document.getElementById('chevron-' + id);
    if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.classList.add('rotated');
    } else {
        body.style.display = 'none';
        chevron.classList.remove('rotated');
    }
}

function openPromptModal(data) {
    document.getElementById('prompt_id').value = '';
    document.getElementById('feature_key').value = '';
    document.getElementById('prompt_name').value = '';
    document.getElementById('system_prompt').value = '';
    document.getElementById('user_prompt_template').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('promptModalTitle').innerHTML = '<i class="fas fa-scroll me-2"></i>Novo Prompt';

    if (data) {
        document.getElementById('prompt_id').value = data.id || '';
        document.getElementById('feature_key').value = data.feature_key || '';
        document.getElementById('prompt_name').value = data.prompt_name || '';
        document.getElementById('system_prompt').value = data.system_prompt || '';
        document.getElementById('user_prompt_template').value = data.user_prompt_template || '';
        document.getElementById('is_active').checked = data.is_active == 1;

        if (data.id) {
            document.getElementById('promptModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Prompt';
        }
    }

    var modal = new bootstrap.Modal(document.getElementById('promptModal'));
    modal.show();
}

function findPromptById(id) {
    for (var i = 0; i < promptsData.length; i++) {
        if (promptsData[i].id == id) {
            return promptsData[i];
        }
    }
    return null;
}

function editPrompt(id) {
    var prompt = findPromptById(id);
    if (prompt) {
        openPromptModal(prompt);
    }
}

function duplicatePrompt(id) {
    var prompt = findPromptById(id);
    if (prompt) {
        var copy = {
            id: '',
            feature_key: prompt.feature_key,
            prompt_name: prompt.prompt_name + ' (cópia)',
            system_prompt: prompt.system_prompt,
            user_prompt_template: prompt.user_prompt_template,
            is_active: 0
        };
        openPromptModal(copy);
    }
}
</script>
