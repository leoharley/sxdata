<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Editar Questionário</h2>
                <?php if ($questionnaire->project_id && $questionnaire->project_name): ?>
                    <small class="text-muted d-block">
                        <i class="fas fa-project-diagram me-1"></i>
                        Projeto: <strong><?= $questionnaire->project_name ?></strong>
                    </small>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($questionnaire->project_id): ?>
                    <a href="<?= base_url('projects/view/' . $questionnaire->project_id) ?>" class="btn btn-info me-2">
                        <i class="fas fa-project-diagram me-2"></i>
                        Ver Projeto
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('questionnaires') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Função helper para checkbox - garantir valores booleanos corretos
function is_checkbox_checked($value) {
    return $value === true || $value === 1 || $value === '1' || $value === 'true';
}
?>

<?= form_open('questionnaires/edit/' . $questionnaire->id, ['id' => 'questionnaireForm']) ?>
<div class="row">
    <div class="col-lg-8">
        <!-- Informações Básicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Título *</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= set_value('title', $questionnaire->title) ?>" required maxlength="200">
                    <?= form_error('title', '<small class="text-danger">', '</small>') ?>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="3" maxlength="1000"><?= set_value('description', $questionnaire->description) ?></textarea>
                    <?= form_error('description', '<small class="text-danger">', '</small>') ?>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <label for="estimated_time" class="form-label">Tempo Estimado (minutos)</label>
                        <input type="number" class="form-control" id="estimated_time" name="estimated_time" 
                               value="<?= set_value('estimated_time', $questionnaire->estimated_time) ?>" min="1" max="120">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= set_select('status', 'active', $questionnaire->status == 'active') ?>>Ativo</option>
                            <option value="paused" <?= set_select('status', 'paused', $questionnaire->status == 'paused') ?>>Pausado</option>
                            <option value="inactive" <?= set_select('status', 'inactive', $questionnaire->status == 'inactive') ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <!-- Select de Projeto -->
                        <label for="project_id" class="form-label">Projeto</label>
                        <select class="form-select" id="project_id" name="project_id">
                            <option value="">Sem projeto</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?= $project->id ?>" 
                                    <?= set_select('project_id', $project->id, $questionnaire->project_id == $project->id) ?>>
                                <?= $project->name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Projeto atual: <?= $questionnaire->project_name ?: 'Nenhum' ?>
                        </small>
                    </div>
                </div>

                <!-- Select de Aplicadores -->
                <div class="mb-3 mt-3">
                    <label for="aplicadores" class="form-label">Aplicadores Permitidos *</label>
                    <select class="form-select" id="aplicadores" name="aplicadores[]" multiple size="6" required>
                        <?php 
                        // Verificar se todos os aplicadores estão selecionados ou se campo está vazio
                        $all_aplicadores_ids = array_column($aplicadores, 'id');
                        $todos_selecionados = empty($aplicadores_selecionados) || 
                                            (count(array_intersect($aplicadores_selecionados, $all_aplicadores_ids)) == count($all_aplicadores_ids));
                        ?>
                        
                        <option value="all" <?= $todos_selecionados ? 'selected' : '' ?>>
                            🌟 Todos os Aplicadores
                        </option>
                        
                        <?php foreach ($aplicadores as $aplicador): ?>
                        <option value="<?= $aplicador->id ?>" 
                                <?= (!$todos_selecionados && in_array($aplicador->id, $aplicadores_selecionados)) ? 'selected' : '' ?>>
                            <?= $aplicador->full_name ?> (<?= $aplicador->username ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos aplicadores. 
                        Selecione "Todos os Aplicadores" para permitir que qualquer aplicador use este questionário.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Perguntas - Seção Editável -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Perguntas (<span id="question-count"><?= count($questions) ?></span>)</h5>
                <button type="button" class="btn btn-success btn-sm" onclick="addQuestion()">
                    <i class="fas fa-plus me-1"></i>
                    Adicionar Pergunta
                </button>
            </div>
            <div class="card-body">
                <div id="questions-container">
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item border rounded p-3 mb-3" data-question-id="<?= $question->id ?>" data-question-index="<?= $index ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="question-number badge bg-primary me-2"><?= $index + 1 ?></span>
                                    <h6 class="mb-0">Pergunta</h6>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="moveQuestion(this, 'up')" title="Mover para cima">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="moveQuestion(this, 'down')" title="Mover para baixo">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeQuestion(this)" title="Remover pergunta">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" name="questions[<?= $index ?>][id]" value="<?= $question->id ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Texto da Pergunta *</label>
                                    <textarea class="form-control question-text" name="questions[<?= $index ?>][text]" 
                                              rows="2" required maxlength="500"><?= $question->question_text ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo *</label>
                                    <select class="form-select question-type" name="questions[<?= $index ?>][type]" 
                                            onchange="toggleOptions(this)" required>
                                        <option value="text" <?= $question->question_type == 'text' ? 'selected' : '' ?>>Texto</option>
                                        <option value="textarea" <?= $question->question_type == 'textarea' ? 'selected' : '' ?>>Texto Longo</option>
                                        <option value="number" <?= $question->question_type == 'number' ? 'selected' : '' ?>>Número</option>
                                        <option value="radio" <?= $question->question_type == 'radio' ? 'selected' : '' ?>>Múltipla Escolha</option>
                                        <option value="checkbox" <?= $question->question_type == 'checkbox' ? 'selected' : '' ?>>Múltipla Seleção</option>
                                        <option value="select" <?= $question->question_type == 'select' ? 'selected' : '' ?>>Lista Suspensa</option>
                                        <option value="date" <?= $question->question_type == 'date' ? 'selected' : '' ?>>Data</option>
                                        <option value="time" <?= $question->question_type == 'time' ? 'selected' : '' ?>>Hora</option>
                                        <option value="email" <?= $question->question_type == 'email' ? 'selected' : '' ?>>E-mail</option>
                                        <option value="phone" <?= $question->question_type == 'phone' ? 'selected' : '' ?>>Telefone</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="questions[<?= $index ?>][required]" 
                                       value="1" <?= $question->is_required ? 'checked' : '' ?>>
                                <label class="form-check-label">
                                    Pergunta obrigatória
                                </label>
                            </div>
                            
                            <!-- Opções para perguntas de múltipla escolha -->
                            <div class="options-container" <?= !in_array($question->question_type, ['radio', 'checkbox', 'select']) ? 'style="display: none;"' : '' ?>>
                                <label class="form-label">Opções de Resposta</label>
                                <div class="options-list">
                                    <?php if (!empty($question->options)): ?>
                                        <?php foreach ($question->options as $opt_index => $option): ?>
                                        <div class="option-item d-flex align-items-center mb-2">
                                            <input type="text" class="form-control me-2" 
                                                   name="questions[<?= $index ?>][options][<?= $opt_index ?>][text]" 
                                                   value="<?= $option->option_text ?>" 
                                                   placeholder="Texto da opção" required>
                                            <input type="hidden" 
                                                   name="questions[<?= $index ?>][options][<?= $opt_index ?>][value]" 
                                                   value="<?= $option->option_value ?>">
                                            <input type="hidden" 
                                                   name="questions[<?= $index ?>][options][<?= $opt_index ?>][id]" 
                                                   value="<?= $option->id ?>">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="addOption(this)">
                                    <i class="fas fa-plus me-1"></i>
                                    Adicionar Opção
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4" id="no-questions-message">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma pergunta encontrada. Clique em "Adicionar Pergunta" para começar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Informações do Projeto -->
        <?php if ($questionnaire->project_id): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-project-diagram me-2"></i>
                    Projeto Atual
                </h5>
            </div>
            <div class="card-body">
                <h6 class="mb-2"><?= $questionnaire->project_name ?></h6>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">ID: <?= $questionnaire->project_id ?></small>
                    <a href="<?= base_url('projects/view/' . $questionnaire->project_id) ?>" 
                       class="btn btn-sm btn-outline-info" title="Ver projeto">
                        <i class="fas fa-eye me-1"></i>
                        Visualizar
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Configurações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações</h5>
            </div>
            <div class="card-body">
                <!-- Checkbox: Requer Consentimento -->
                <div class="form-check mb-3">
                    <?php 
                    $consent_checked = set_checkbox('requires_consent', '1', is_checkbox_checked($questionnaire->requires_consent));
                    if (empty($consent_checked) && is_checkbox_checked($questionnaire->requires_consent)) {
                        $consent_checked = 'checked="checked"';
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="requires_consent" 
                           name="requires_consent" value="1" <?= $consent_checked ?>>
                    <label class="form-check-label" for="requires_consent">
                        <strong>Requer Consentimento</strong>
                        <br><small class="text-muted">Exibir termo de consentimento antes do questionário</small>
                    </label>
                </div>
                
                <!-- Checkbox: Capturar Localização -->
                <div class="form-check mb-3">
                    <?php 
                    $location_checked = set_checkbox('requires_location', '1', is_checkbox_checked($questionnaire->requires_location));
                    if (empty($location_checked) && is_checkbox_checked($questionnaire->requires_location)) {
                        $location_checked = 'checked="checked"';
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="requires_location" 
                           name="requires_location" value="1" <?= $location_checked ?>>
                    <label class="form-check-label" for="requires_location">
                        <strong>Capturar Localização</strong>
                        <br><small class="text-muted">Registrar coordenadas GPS automaticamente</small>
                    </label>
                </div>
                
                <!-- Checkbox: Requer Foto -->
                <div class="form-check mb-3">
                    <?php 
                    $photo_checked = set_checkbox('requires_photo', '1', is_checkbox_checked($questionnaire->requires_photo));
                    if (empty($photo_checked) && is_checkbox_checked($questionnaire->requires_photo)) {
                        $photo_checked = 'checked="checked"';
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="requires_photo" 
                           name="requires_photo" value="1" <?= $photo_checked ?>>
                    <label class="form-check-label" for="requires_photo">
                        <strong>Requer Foto</strong>
                        <br><small class="text-muted">Solicitar foto como evidência</small>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0" id="stats-questions"><?= count($questions) ?></h4>
                        <small class="text-muted">Perguntas</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0">0</h4>
                        <small class="text-muted">Respostas</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <strong>Versão:</strong> <?= $questionnaire->version ?><br>
                        <strong>Criado:</strong> <?= date('d/m/Y', strtotime($questionnaire->created_at)) ?>
                        <br>por <?= $questionnaire->created_by_name ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-save me-2"></i>
                    Salvar Alterações
                </button>
                <a href="<?= base_url('questionnaires/duplicate/' . $questionnaire->id) ?>" 
                   class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-copy me-2"></i>
                    Duplicar Questionário
                </a>
                <?php if ($questionnaire->project_id): ?>
                    <a href="<?= base_url('projects/view/' . $questionnaire->project_id) ?>" 
                       class="btn btn-outline-info w-100 mb-2">
                        <i class="fas fa-project-diagram me-2"></i>
                        Ver Projeto
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('questionnaires') ?>" class="btn btn-outline-secondary w-100">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="conditionalLogicModal" tabindex="-1" aria-labelledby="conditionalLogicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conditionalLogicModalLabel">
                    <i class="fas fa-sitemap me-2"></i>
                    Configurar Lógica Condicional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Lógica Condicional:</strong> Configure quando esta pergunta deve aparecer ou ser obrigatória baseado nas respostas de outras perguntas.
                </div>
                
                <!-- Configuração de Visibilidade -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-eye me-2"></i>
                            Regras de Visibilidade
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableVisibilityRules" onchange="toggleVisibilityRules()">
                            <label class="form-check-label" for="enableVisibilityRules">
                                <strong>Ativar regras de visibilidade</strong>
                                <br><small class="text-muted">Esta pergunta só aparecerá quando as condições forem atendidas</small>
                            </label>
                        </div>
                        
                        <div id="visibilityRulesContainer" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Operador Lógico</label>
                                <select class="form-select" id="visibilityOperator">
                                    <option value="AND">E (todas as condições devem ser verdadeiras)</option>
                                    <option value="OR">OU (pelo menos uma condição deve ser verdadeira)</option>
                                </select>
                            </div>
                            
                            <div id="visibilityConditions">
                                <!-- Condições serão adicionadas aqui -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addVisibilityCondition()">
                                <i class="fas fa-plus me-1"></i>
                                Adicionar Condição
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Configuração de Obrigatoriedade -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-asterisk me-2"></i>
                            Regras de Obrigatoriedade
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableRequiredRules" onchange="toggleRequiredRules()">
                            <label class="form-check-label" for="enableRequiredRules">
                                <strong>Ativar regras de obrigatoriedade condicional</strong>
                                <br><small class="text-muted">Esta pergunta será obrigatória apenas quando as condições forem atendidas</small>
                            </label>
                        </div>
                        
                        <div id="requiredRulesContainer" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Operador Lógico</label>
                                <select class="form-select" id="requiredOperator">
                                    <option value="AND">E (todas as condições devem ser verdadeiras)</option>
                                    <option value="OR">OU (pelo menos uma condição deve ser verdadeira)</option>
                                </select>
                            </div>
                            
                            <div id="requiredConditions">
                                <!-- Condições serão adicionadas aqui -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRequiredCondition()">
                                <i class="fas fa-plus me-1"></i>
                                Adicionar Condição
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview das Regras -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-preview me-2"></i>
                            Preview das Regras
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="rulesPreview" class="text-muted">
                            Nenhuma regra configurada ainda.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveConditionalLogic()">
                    <i class="fas fa-save me-1"></i>
                    Salvar Regras
                </button>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

<script>
// Dados dos projetos para JavaScript
const projectsData = <?= json_encode($projects) ?>;
let questionIndex = <?= count($questions) ?>;

// Gerenciamento do select de aplicadores
document.addEventListener('DOMContentLoaded', function() {
    const aplicadoresSelect = document.getElementById('aplicadores');
    const projectSelect = document.getElementById('project_id');
    
    aplicadoresSelect.addEventListener('change', function() {
        const allOption = this.querySelector('option[value="all"]');
        const otherOptions = Array.from(this.querySelectorAll('option:not([value="all"])'));
        
        if (allOption.selected) {
            otherOptions.forEach(option => option.selected = false);
        } else {
            const hasSpecificSelection = otherOptions.some(option => option.selected);
            if (hasSpecificSelection) {
                allOption.selected = false;
            }
        }
        
        if (!Array.from(this.selectedOptions).length) {
            allOption.selected = true;
        }
    });

    // Alerta ao mudar projeto
    projectSelect.addEventListener('change', function() {
        const currentProject = '<?= $questionnaire->project_id ?>';
        const newProject = this.value;
        
        if (currentProject && currentProject != newProject) {
            if (newProject) {
                const project = projectsData.find(p => p.id == newProject);
                const projectName = project ? project.name : 'projeto selecionado';
                
                if (!confirm(`Tem certeza que deseja mover este questionário para o projeto "${projectName}"?`)) {
                    this.value = currentProject;
                }
            } else {
                if (!confirm('Tem certeza que deseja remover este questionário do projeto atual?')) {
                    this.value = currentProject;
                }
            }
        }
    });

    updateQuestionNumbers();
    updateQuestionCount();
    
    // Verificar se há perguntas para esconder/mostrar mensagem
    checkEmptyQuestions();
});

// Função para adicionar nova pergunta
function addQuestion() {
    const container = document.getElementById('questions-container') || document.getElementById('questionsContainer');
    const questionHtml = `
        <div class="question-item border rounded p-3 mb-3" data-question-index="${questionIndex}" data-question-id="q_${questionIndex}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center">
                    <span class="question-number badge bg-primary me-2">${questionIndex + 1}</span>
                    <h6 class="mb-0">Pergunta</h6>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-info" onclick="openConditionalLogicModal(${questionIndex})" 
                            title="Configurar lógica condicional">
                        <i class="fas fa-sitemap"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="moveQuestion(this, 'up')" title="Mover para cima">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="moveQuestion(this, 'down')" title="Mover para baixo">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="removeQuestion(this)" title="Remover pergunta">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <input type="hidden" name="questions[${questionIndex}][id]" value="">
            <input type="hidden" name="questions[${questionIndex}][conditional_logic]" value="" data-conditional-logic="true">
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Texto da Pergunta *</label>
                    <textarea class="form-control question-text" name="questions[${questionIndex}][text]" 
                              rows="2" required maxlength="500" placeholder="Digite o texto da pergunta..."></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select question-type" name="questions[${questionIndex}][type]" 
                            onchange="toggleOptions(this)" required>
                        <option value="text">Texto</option>
                        <option value="textarea">Texto Longo</option>
                        <option value="number">Número</option>
                        <option value="radio">Múltipla Escolha</option>
                        <option value="checkbox">Múltipla Seleção</option>
                        <option value="select">Lista Suspensa</option>
                        <option value="date">Data</option>
                        <option value="time">Hora</option>
                        <option value="email">E-mail</option>
                        <option value="phone">Telefone</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="questions[${questionIndex}][required]" value="1">
                        <label class="form-check-label">
                            <strong>Pergunta obrigatória</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="questions[${questionIndex}][conditional_required]" value="1">
                        <label class="form-check-label">
                            <strong>Obrigatória condicionalmente</strong>
                            <br><small class="text-muted">Use lógica condicional para definir quando é obrigatória</small>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="options-container" style="display: none;">
                <label class="form-label">Opções de Resposta</label>
                <div class="options-list">
                    <div class="option-item d-flex align-items-center mb-2">
                        <input type="text" class="form-control me-2" 
                               name="questions[${questionIndex}][options][0][text]" 
                               placeholder="Texto da opção" required>
                        <input type="hidden" name="questions[${questionIndex}][options][0][value]" value="">
                        <input type="hidden" name="questions[${questionIndex}][options][0][id]" value="">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="addOption(this)">
                    <i class="fas fa-plus me-1"></i>
                    Adicionar Opção
                </button>
            </div>
            
            <!-- Área para preview da lógica condicional -->
            <div class="conditional-logic-preview mt-3" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-sitemap me-2"></i>
                    <strong>Lógica Condicional:</strong>
                    <div class="logic-preview-text mt-1"></div>
                </div>
            </div>
        </div>
    `;
    
    // Remover mensagem de "nenhuma pergunta" se existir
    const noQuestionsMessage = document.getElementById('no-questions-message') || document.getElementById('noQuestions');
    if (noQuestionsMessage) {
        noQuestionsMessage.style.display = 'none';
    }
    
    container.insertAdjacentHTML('beforeend', questionHtml);
    questionIndex++;
    
    updateQuestionNumbers();
    updateQuestionCount();
}

document.addEventListener('DOMContentLoaded', function() {
    // Para perguntas existentes (edit.php)
    const existingQuestions = document.querySelectorAll('.question-item');
    existingQuestions.forEach((question, index) => {
        // Adicionar data-question-id se não existir
        if (!question.dataset.questionId) {
            question.dataset.questionId = `q_${index}`;
        }
        
        // Adicionar botão de lógica condicional se não existir
        const header = question.querySelector('.d-flex.justify-content-between');
        const existingLogicBtn = header.querySelector('.btn-outline-info');
        
        if (!existingLogicBtn) {
            const btnGroup = header.querySelector('.btn-group');
            const logicBtn = document.createElement('button');
            logicBtn.type = 'button';
            logicBtn.className = 'btn btn-outline-info btn-sm';
            logicBtn.onclick = () => openConditionalLogicModal(index);
            logicBtn.title = 'Configurar lógica condicional';
            logicBtn.innerHTML = '<i class="fas fa-sitemap"></i>';
            
            btnGroup.insertBefore(logicBtn, btnGroup.firstChild);
        }
        
        // Adicionar campo hidden para lógica condicional se não existir
        let conditionalInput = question.querySelector('input[data-conditional-logic="true"]');
        if (!conditionalInput) {
            conditionalInput = document.createElement('input');
            conditionalInput.type = 'hidden';
            conditionalInput.name = `questions[${index}][conditional_logic]`;
            conditionalInput.setAttribute('data-conditional-logic', 'true');
            conditionalInput.value = '';
            question.appendChild(conditionalInput);
        }
        
        // Verificar se há lógica condicional existente
        const existingLogic = conditionalInput.value;
        if (existingLogic) {
            try {
                const logic = JSON.parse(existingLogic);
                if (Object.keys(logic).length > 0) {
                    updateConditionalLogicIndicator(index, true);
                    showConditionalLogicPreview(index, logic);
                }
            } catch (e) {
                console.warn('Erro ao parsing da lógica condicional:', e);
            }
        }
    });
    
    // Inicializar sistema de lógica condicional após um breve delay
    setTimeout(() => {
        if (typeof initConditionalLogic === 'function') {
            initConditionalLogic();
        }
    }, 1000);
});

// Atualizar validação do formulário
const originalFormSubmit = document.getElementById('questionnaireForm')?.addEventListener;

if (document.getElementById('questionnaireForm')) {
    document.getElementById('questionnaireForm').addEventListener('submit', function(e) {
        // Validações existentes...
        const aplicadoresSelect = document.getElementById('aplicadores');
        if (aplicadoresSelect && !aplicadoresSelect.selectedOptions.length) {
            e.preventDefault();
            alert('Selecione pelo menos um aplicador para este questionário.');
            return false;
        }
        
        const questions = document.querySelectorAll('.question-item');
        if (questions.length === 0) {
            e.preventDefault();
            alert('Adicione pelo menos uma pergunta ao questionário.');
            return false;
        }
        
        // Validar lógica condicional
        if (typeof validateConditionalLogic === 'function' && !validateConditionalLogic()) {
            e.preventDefault();
            return false;
        }
        
        // Validações de perguntas...
        let isValid = true;
        questions.forEach((question, index) => {
            const questionText = question.querySelector('.question-text');
            const questionType = question.querySelector('.question-type');
            
            if (!questionText.value.trim()) {
                isValid = false;
                questionText.focus();
                alert(`O texto da pergunta ${index + 1} é obrigatório.`);
                return;
            }
            
            // Validar opções para perguntas de múltipla escolha
            if (['radio', 'checkbox', 'select'].includes(questionType.value)) {
                const options = question.querySelectorAll('.option-item input[type="text"]');
                let hasValidOption = false;
                
                options.forEach(option => {
                    if (option.value.trim()) {
                        hasValidOption = true;
                        const valueInput = option.parentNode.querySelector('input[type="hidden"][name*="[value]"]');
                        if (!valueInput.value) {
                            valueInput.value = option.value.toLowerCase().replace(/\s+/g, '_');
                        }
                    }
                });
                
                if (!hasValidOption) {
                    isValid = false;
                    alert(`A pergunta ${index + 1} precisa ter pelo menos uma opção válida.`);
                    return;
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        updateQuestionNumbers();
    });
}

// Função para remover pergunta
function removeQuestion(button) {
    if (confirm('Tem certeza que deseja remover esta pergunta?')) {
        const questionItem = button.closest('.question-item');
        questionItem.remove();
        
        updateQuestionNumbers();
        updateQuestionCount();
        checkEmptyQuestions();
    }
}

// Função para mover pergunta para cima ou para baixo
function moveQuestion(button, direction) {
    const questionItem = button.closest('.question-item');
    
    if (direction === 'up') {
        const prevQuestion = questionItem.previousElementSibling;
        if (prevQuestion && prevQuestion.classList.contains('question-item')) {
            questionItem.parentNode.insertBefore(questionItem, prevQuestion);
        }
    } else if (direction === 'down') {
        const nextQuestion = questionItem.nextElementSibling;
        if (nextQuestion && nextQuestion.classList.contains('question-item')) {
            questionItem.parentNode.insertBefore(nextQuestion, questionItem);
        }
    }
    
    updateQuestionNumbers();
}

// Função para mostrar/esconder opções baseado no tipo da pergunta
function toggleOptions(select) {
    const questionItem = select.closest('.question-item');
    const optionsContainer = questionItem.querySelector('.options-container');
    const selectedType = select.value;
    
    if (['radio', 'checkbox', 'select'].includes(selectedType)) {
        optionsContainer.style.display = 'block';
        
        // Adicionar pelo menos uma opção se não houver nenhuma
        const optionsList = optionsContainer.querySelector('.options-list');
        if (optionsList.children.length === 0) {
            addOption(optionsContainer.querySelector('button'));
        }
    } else {
        optionsContainer.style.display = 'none';
    }
}

// Função para adicionar opção
function addOption(button) {
    const optionsContainer = button.closest('.options-container');
    const questionItem = button.closest('.question-item');
    const questionIdx = questionItem.dataset.questionIndex || Array.from(questionItem.parentNode.children).indexOf(questionItem);
    const optionsList = optionsContainer.querySelector('.options-list');
    const optionIndex = optionsList.children.length;
    
    const optionHtml = `
        <div class="option-item d-flex align-items-center mb-2">
            <input type="text" class="form-control me-2" 
                   name="questions[${questionIdx}][options][${optionIndex}][text]" 
                   placeholder="Texto da opção" required>
            <input type="hidden" name="questions[${questionIdx}][options][${optionIndex}][value]" value="">
            <input type="hidden" name="questions[${questionIdx}][options][${optionIndex}][id]" value="">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    optionsList.insertAdjacentHTML('beforeend', optionHtml);
}

// Função para remover opção
function removeOption(button) {
    const optionsList = button.closest('.options-list');
    const optionItem = button.closest('.option-item');
    
    // Não permitir remover se for a única opção
    if (optionsList.children.length <= 1) {
        alert('Deve haver pelo menos uma opção.');
        return;
    }
    
    optionItem.remove();
    
    // Reindexar as opções restantes
    const questionItem = button.closest('.question-item');
    const questionIdx = questionItem.dataset.questionIndex || Array.from(questionItem.parentNode.children).indexOf(questionItem);
    
    Array.from(optionsList.children).forEach((option, index) => {
        const textInput = option.querySelector('input[type="text"]');
        const valueInput = option.querySelector('input[type="hidden"][name*="[value]"]');
        const idInput = option.querySelector('input[type="hidden"][name*="[id]"]');
        
        textInput.name = `questions[${questionIdx}][options][${index}][text]`;
        valueInput.name = `questions[${questionIdx}][options][${index}][value]`;
        idInput.name = `questions[${questionIdx}][options][${index}][id]`;
    });
}

// Função para atualizar numeração das perguntas
function updateQuestionNumbers() {
    const questions = document.querySelectorAll('.question-item');
    
    questions.forEach((question, index) => {
        // Atualizar número visual
        const numberBadge = question.querySelector('.question-number');
        if (numberBadge) {
            numberBadge.textContent = index + 1;
        }
        
        // Atualizar índices nos nomes dos campos
        question.dataset.questionIndex = index;
        
        // Atualizar nomes dos inputs da pergunta
        const questionInputs = question.querySelectorAll('input, textarea, select');
        questionInputs.forEach(input => {
            if (input.name && input.name.includes('questions[')) {
                // Substituir o índice antigo pelo novo
                input.name = input.name.replace(/questions\[\d+\]/, `questions[${index}]`);
            }
        });
    });
}

// Função para atualizar contador de perguntas
function updateQuestionCount() {
    const count = document.querySelectorAll('.question-item').length;
    document.getElementById('question-count').textContent = count;
    document.getElementById('stats-questions').textContent = count;
}

// Função para verificar se há perguntas e mostrar/esconder mensagem
function checkEmptyQuestions() {
    const questions = document.querySelectorAll('.question-item');
    const container = document.getElementById('questions-container');
    
    if (questions.length === 0) {
        const noQuestionsHtml = `
            <div class="text-center py-4" id="no-questions-message">
                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhuma pergunta encontrada. Clique em "Adicionar Pergunta" para começar.</p>
            </div>
        `;
        container.innerHTML = noQuestionsHtml;
    }
}

// Validação do formulário
document.getElementById('questionnaireForm').addEventListener('submit', function(e) {
    // Validar se pelo menos um aplicador foi selecionado
    const aplicadoresSelect = document.getElementById('aplicadores');
    if (!aplicadoresSelect.selectedOptions.length) {
        e.preventDefault();
        alert('Selecione pelo menos um aplicador para este questionário.');
        return false;
    }
    
    // Validar se há pelo menos uma pergunta
    const questions = document.querySelectorAll('.question-item');
    if (questions.length === 0) {
        e.preventDefault();
        alert('Adicione pelo menos uma pergunta ao questionário.');
        return false;
    }
    
    // Validar cada pergunta
    let isValid = true;
    questions.forEach((question, index) => {
        const questionText = question.querySelector('.question-text');
        const questionType = question.querySelector('.question-type');
        
        if (!questionText.value.trim()) {
            isValid = false;
            questionText.focus();
            alert(`O texto da pergunta ${index + 1} é obrigatório.`);
            return;
        }
        
        // Validar opções para perguntas de múltipla escolha
        if (['radio', 'checkbox', 'select'].includes(questionType.value)) {
            const options = question.querySelectorAll('.option-item input[type="text"]');
            let hasValidOption = false;
            
            options.forEach(option => {
                if (option.value.trim()) {
                    hasValidOption = true;
                    // Definir value automaticamente baseado no text se estiver vazio
                    const valueInput = option.parentNode.querySelector('input[type="hidden"][name*="[value]"]');
                    if (!valueInput.value) {
                        valueInput.value = option.value.toLowerCase().replace(/\s+/g, '_');
                    }
                }
            });
            
            if (!hasValidOption) {
                isValid = false;
                alert(`A pergunta ${index + 1} precisa ter pelo menos uma opção válida.`);
                return;
            }
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        return false;
    }
    
    // Atualizar índices finais antes do submit
    updateQuestionNumbers();
});

// Inicializar opções ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar visibilidade das opções para perguntas existentes
    document.querySelectorAll('.question-type').forEach(select => {
        toggleOptions(select);
    });
    
    // Auto-preencher values das opções existentes se estiverem vazios
    document.querySelectorAll('.option-item').forEach(option => {
        const textInput = option.querySelector('input[type="text"]');
        const valueInput = option.querySelector('input[type="hidden"][name*="[value]"]');
        
        if (textInput.value && !valueInput.value) {
            valueInput.value = textInput.value.toLowerCase().replace(/\s+/g, '_');
        }
    });
});

// Event listener para auto-preencher value quando o texto da opção mudar
document.addEventListener('input', function(e) {
    if (e.target.matches('.option-item input[type="text"]')) {
        const valueInput = e.target.parentNode.querySelector('input[type="hidden"][name*="[value]"]');
        if (valueInput && !valueInput.dataset.userModified) {
            valueInput.value = e.target.value.toLowerCase().replace(/\s+/g, '_');
        }
    }
});

// Mostrar alertas de confirmação para ações destrutivas
window.addEventListener('beforeunload', function(e) {
    const form = document.getElementById('questionnaireForm');
    const formData = new FormData(form);
    
    // Verificar se houve alterações (implementação básica)
    // Em produção, você pode implementar uma verificação mais sofisticada
    const hasChanges = document.querySelector('.question-item[data-question-id=""]'); // Nova pergunta
    
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
</script>

<style>
.question-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.question-item:hover {
    background-color: #e9ecef;
}

.question-number {
    min-width: 2rem;
    text-align: center;
}

.options-container {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-top: 1rem;
}

.option-item {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-group .btn {
    border-radius: 0.375rem !important;
    margin-left: 2px;
}

.btn-group .btn:first-child {
    margin-left: 0;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.question-item .card-header {
    background-color: transparent;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 0;
}

.question-text:focus,
.question-type:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.no-questions-message {
    color: #6c757d;
}

/* Estilo para drag and drop (futuro) */
.question-item.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

.conditional-logic-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.condition-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.condition-item:hover {
    background-color: #e9ecef;
}

.btn-group .btn-outline-info {
    border-color: #0dcaf0;
    color: #0dcaf0;
}

.btn-group .btn-outline-info:hover {
    background-color: #0dcaf0;
    color: white;
}

.conditional-logic-preview .alert {
    margin-bottom: 0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.has-error {
    border: 2px solid #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.required-asterisk {
    color: #dc3545;
    font-weight: bold;
}

.conditional-hidden {
    display: none !important;
}

.question-item.conditional-question {
    border: 2px dashed #17a2b8;
    background-color: rgba(23, 162, 184, 0.05);
}
</style>