<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Criar Questionário</h2>
                <?php if (isset($preselected_project_id) && $preselected_project_id): ?>
                    <?php 
                    // Buscar nome do projeto selecionado
                    $selected_project = null;
                    foreach ($projects as $proj) {
                        if ($proj->id == $preselected_project_id) {
                            $selected_project = $proj;
                            break;
                        }
                    }
                    ?>
                    <?php if ($selected_project): ?>
                        <small class="text-muted d-block">
                            <i class="fas fa-project-diagram me-1"></i>
                            Vinculando ao projeto: <strong><?= $selected_project->name ?></strong>
                        </small>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('questionnaires') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<?= form_open('questionnaires/create', ['id' => 'questionnaireForm']) ?>
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
                           value="<?= set_value('title') ?>" required maxlength="200">
                    <?= form_error('title', '<small class="text-danger">', '</small>') ?>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="3" maxlength="1000"><?= set_value('description') ?></textarea>
                    <?= form_error('description', '<small class="text-danger">', '</small>') ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="estimated_time" class="form-label">Tempo Estimado (minutos)</label>
                        <input type="number" class="form-control" id="estimated_time" name="estimated_time" 
                               value="<?= set_value('estimated_time') ?>" min="1" max="120">
                    </div>
                    <div class="col-md-6">
                        <!-- NOVO: Select de Projeto -->
                        <label for="project_id" class="form-label">Projeto</label>
                        <select class="form-select" id="project_id" name="project_id">
                            <option value="">Selecione um projeto (opcional)</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?= $project->id ?>" 
                                    <?= set_select('project_id', $project->id, (isset($preselected_project_id) && $preselected_project_id == $project->id)) ?>>
                                <?= $project->name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Vincule este questionário a um projeto específico para melhor organização
                        </small>
                    </div>
                </div>

                <!-- Select de Aplicadores -->
                <div class="mb-3 mt-3">
                    <label for="aplicadores" class="form-label">Aplicadores Permitidos *</label>
                    <select class="form-select" id="aplicadores" name="aplicadores[]" multiple size="6" required>
                        <option value="all">🌟 Todos os Aplicadores</option>
                        <?php foreach ($aplicadores as $aplicador): ?>
                        <option value="<?= $aplicador->id ?>" <?= set_select('aplicadores[]', $aplicador->id) ?>>
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
        
        <!-- Perguntas -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Perguntas</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addQuestion()">
                    <i class="fas fa-plus me-1"></i>
                    Adicionar Pergunta
                </button>
            </div>
            <div class="card-body">
                <div id="questionsContainer">
                    <!-- Perguntas serão adicionadas aqui via JavaScript -->
                </div>
                
                <div id="noQuestions" class="text-center py-4">
                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma pergunta adicionada ainda.</p>
                    <button type="button" class="btn btn-primary" onclick="addQuestion()">
                        Adicionar Primeira Pergunta
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Informações do Projeto Selecionado -->
        <div class="card mb-4" id="projectInfoCard" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-project-diagram me-2"></i>
                    Projeto Selecionado
                </h5>
            </div>
            <div class="card-body" id="projectInfo">
                <!-- Informações do projeto serão carregadas aqui -->
            </div>
        </div>
        
        <!-- Configurações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="requires_consent" 
                           name="requires_consent" value="1" <?= set_checkbox('requires_consent', '1') ?>>
                    <label class="form-check-label" for="requires_consent">
                        <strong>Requer Consentimento</strong>
                        <br><small class="text-muted">Exibir termo de consentimento antes do questionário</small>
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="requires_location" 
                           name="requires_location" value="1" <?= set_checkbox('requires_location', '1') ?>>
                    <label class="form-check-label" for="requires_location">
                        <strong>Capturar Localização</strong>
                        <br><small class="text-muted">Registrar coordenadas GPS automaticamente</small>
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="requires_photo" 
                           name="requires_photo" value="1" <?= set_checkbox('requires_photo', '1') ?>>
                    <label class="form-check-label" for="requires_photo">
                        <strong>Requer Foto</strong>
                        <br><small class="text-muted">Solicitar foto como evidência</small>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-save me-2"></i>
                    Salvar Questionário
                </button>
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
let questionIndex = 0;

// NOVO: Dados dos projetos para JavaScript
const projectsData = <?= json_encode($projects) ?>;

// Gerenciamento do select de aplicadores
document.addEventListener('DOMContentLoaded', function() {
    const aplicadoresSelect = document.getElementById('aplicadores');
    const projectSelect = document.getElementById('project_id');
    
    aplicadoresSelect.addEventListener('change', function() {
        const allOption = this.querySelector('option[value="all"]');
        const otherOptions = Array.from(this.querySelectorAll('option:not([value="all"])'));
        
        // Se "Todos" foi selecionado
        if (allOption.selected) {
            // Desmarcar todas as outras opções
            otherOptions.forEach(option => option.selected = false);
        } else {
            // Se alguma opção específica foi selecionada, desmarcar "Todos"
            const hasSpecificSelection = otherOptions.some(option => option.selected);
            if (hasSpecificSelection) {
                allOption.selected = false;
            }
        }
        
        // Se nenhuma opção está selecionada, selecionar "Todos" automaticamente
        if (!Array.from(this.selectedOptions).length) {
            allOption.selected = true;
        }
    });
    
    // NOVO: Gerenciamento do select de projeto
    projectSelect.addEventListener('change', function() {
        updateProjectInfo(this.value);
    });
    
    // Selecionar "Todos" por padrão
    aplicadoresSelect.querySelector('option[value="all"]').selected = true;
    
    // Verificar se há projeto pré-selecionado
    if (projectSelect.value) {
        updateProjectInfo(projectSelect.value);
    }
});

// NOVO: Função para atualizar informações do projeto
function updateProjectInfo(projectId) {
    const projectInfoCard = document.getElementById('projectInfoCard');
    const projectInfo = document.getElementById('projectInfo');
    
    if (!projectId) {
        projectInfoCard.style.display = 'none';
        return;
    }
    
    const project = projectsData.find(p => p.id == projectId);
    if (!project) {
        projectInfoCard.style.display = 'none';
        return;
    }
    
    projectInfo.innerHTML = `
        <h6 class="mb-2">${project.name}</h6>
        <p class="text-muted small mb-2">${project.description || 'Sem descrição'}</p>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">ID: ${project.id}</small>
            <a href="<?= base_url('projects/view/') ?>${project.id}" 
               class="btn btn-sm btn-outline-info" target="_blank" title="Ver projeto">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    `;
    
    projectInfoCard.style.display = 'block';
}

function addQuestion() {function addQuestion() {
    const container = document.getElementById('questions-container') || document.getElementById('questionsContainer');
    const questionHtml = `
        <div class="question-item border rounded p-3 mb-3" data-question-index="${questionIndex}">
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
            <input type="hidden" name="questions[${questionIndex}][conditional_logic]" value="">
            
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

// Função para atualizar template de pergunta existente (para edit.php)
function updateExistingQuestionTemplate(questionElement, index) {
    const header = questionElement.querySelector('.d-flex.justify-content-between');
    const existingLogicBtn = header.querySelector('.btn-outline-info');
    
    if (!existingLogicBtn) {
        const logicBtn = document.createElement('button');
        logicBtn.type = 'button';
        logicBtn.className = 'btn btn-outline-info btn-sm me-1';
        logicBtn.onclick = () => openConditionalLogicModal(index);
        logicBtn.title = 'Configurar lógica condicional';
        logicBtn.innerHTML = '<i class="fas fa-sitemap"></i>';
        
        const btnGroup = header.querySelector('.btn-group');
        btnGroup.insertBefore(logicBtn, btnGroup.firstChild);
    }
    
    // Adicionar campo hidden para lógica condicional se não existir
    let conditionalInput = questionElement.querySelector('input[name*="[conditional_logic]"]');
    if (!conditionalInput) {
        conditionalInput = document.createElement('input');
        conditionalInput.type = 'hidden';
        conditionalInput.name = `questions[${index}][conditional_logic]`;
        conditionalInput.value = '';
        questionElement.appendChild(conditionalInput);
    }
    
    // Adicionar checkbox para obrigatória condicionalmente se não existir
    const requiredSection = questionElement.querySelector('.form-check');
    if (requiredSection && !questionElement.querySelector('input[name*="[conditional_required]"]')) {
        const conditionalRequiredHtml = `
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="questions[${index}][conditional_required]" value="1">
                    <label class="form-check-label">
                        <strong>Obrigatória condicionalmente</strong>
                        <br><small class="text-muted">Use lógica condicional para definir quando é obrigatória</small>
                    </label>
                </div>
            </div>
        `;
        
        // Converter a seção atual em uma row
        const currentCheck = requiredSection.closest('.form-check');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-3';
        
        const col1 = document.createElement('div');
        col1.className = 'col-md-6';
        col1.appendChild(currentCheck.cloneNode(true));
        
        newRow.appendChild(col1);
        newRow.insertAdjacentHTML('beforeend', conditionalRequiredHtml);
        
        currentCheck.parentNode.replaceChild(newRow, currentCheck);
    }
    
    // Adicionar área de preview se não existir
    if (!questionElement.querySelector('.conditional-logic-preview')) {
        const previewHtml = `
            <div class="conditional-logic-preview mt-3" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-sitemap me-2"></i>
                    <strong>Lógica Condicional:</strong>
                    <div class="logic-preview-text mt-1"></div>
                </div>
            </div>
        `;
        
        questionElement.insertAdjacentHTML('beforeend', previewHtml);
    }
    
    // Verificar se há lógica condicional existente e mostrar indicador
    const conditionalLogic = conditionalInput.value;
    if (conditionalLogic) {
        try {
            const logic = JSON.parse(conditionalLogic);
            if (Object.keys(logic).length > 0) {
                updateConditionalLogicIndicator(index, true);
                showConditionalLogicPreview(index, logic);
            }
        } catch (e) {
            console.warn('Erro ao parsing da lógica condicional:', e);
        }
    }
}

// Função para mostrar preview da lógica condicional na pergunta
function showConditionalLogicPreview(questionIndex, logic) {
    const question = document.querySelector(`[data-question-index="${questionIndex}"]`);
    const preview = question.querySelector('.conditional-logic-preview');
    const previewText = question.querySelector('.logic-preview-text');
    
    if (!preview || !previewText) return;
    
    let text = '';
    
    if (logic.visibility) {
        text += '<strong>Visibilidade:</strong> Aparece quando ';
        const conditions = logic.visibility.conditions.map(c => {
            const questionElement = document.querySelector(`[data-question-index="${c.question}"]`);
            const questionText = questionElement ? 
                questionElement.querySelector('.question-text').value.substring(0, 30) + '...' : 
                `Pergunta ${c.question + 1}`;
            return `"${questionText}" ${c.operator} "${c.value}"`;
        });
        text += conditions.join(logic.visibility.operator === 'AND' ? ' E ' : ' OU ');
        text += '<br>';
    }
    
    if (logic.required) {
        text += '<strong>Obrigatória:</strong> Quando ';
        const conditions = logic.required.conditions.map(c => {
            const questionElement = document.querySelector(`[data-question-index="${c.question}"]`);
            const questionText = questionElement ? 
                questionElement.querySelector('.question-text').value.substring(0, 30) + '...' : 
                `Pergunta ${c.question + 1}`;
            return `"${questionText}" ${c.operator} "${c.value}"`;
        });
        text += conditions.join(logic.required.operator === 'AND' ? ' E ' : ' OU ');
    }
    
    previewText.innerHTML = text;
    preview.style.display = text ? 'block' : 'none';
}

// Atualizar validação do formulário para incluir lógica condicional
const originalFormValidation = document.getElementById('questionnaireForm')?.addEventListener;

document.addEventListener('DOMContentLoaded', function() {
    // Atualizar perguntas existentes (para edit.php)
    const existingQuestions = document.querySelectorAll('.question-item');
    existingQuestions.forEach((question, index) => {
        updateExistingQuestionTemplate(question, index);
    });
    
    // Sobrescrever validação do formulário
    const form = document.getElementById('questionnaireForm');
    if (form) {
        form.addEventListener('submit', function(e) {
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
            if (!validateConditionalLogic()) {
                e.preventDefault();
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
});

// CSS adicional para melhorar a aparência
const additionalCSS = `
<style>
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

.modal-lg {
    max-width: 900px;
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
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalCSS);

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

function removeQuestion(index) {
    if (confirm('Tem certeza que deseja remover esta pergunta?')) {
        const questionItem = document.querySelector(`[data-index="${index}"]`);
        questionItem.remove();
        
        const remainingQuestions = document.querySelectorAll('.question-item');
        if (remainingQuestions.length === 0) {
            document.getElementById('noQuestions').style.display = 'block';
        }
        updateQuestionNumbers();
    }
}

function handleQuestionTypeChange(questionIndex, type) {
    const optionsDiv = document.getElementById(`options-${questionIndex}`);
    
    if (type === 'radio' || type === 'checkbox') {
        optionsDiv.style.display = 'block';
        // Adicionar primeira opção automaticamente
        const optionsContainer = document.getElementById(`optionsContainer-${questionIndex}`);
        if (optionsContainer.children.length === 0) {
            addOption(questionIndex);
            addOption(questionIndex);
        }
    } else {
        optionsDiv.style.display = 'none';
    }
}

function addOption(questionIndex) {
    const container = document.getElementById(`optionsContainer-${questionIndex}`);
    const optionIndex = container.children.length;
    
    const optionHtml = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" 
                   name="questions[${questionIndex}][options][${optionIndex}][text]" 
                   placeholder="Texto da opção" required>
            <input type="hidden" 
                   name="questions[${questionIndex}][options][${optionIndex}][value]" 
                   value="">
            <button type="button" class="btn btn-outline-danger" 
                    onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', optionHtml);
}

function updateQuestionNumbers() {
    const questions = document.querySelectorAll('.question-item');
    questions.forEach((question, index) => {
        const title = question.querySelector('h6');
        title.textContent = `Pergunta ${index + 1}`;
    });
}

// Validação do formulário
document.getElementById('questionnaireForm').addEventListener('submit', function(e) {
    const questions = document.querySelectorAll('.question-item');
    if (questions.length === 0) {
        e.preventDefault();
        alert('Adicione pelo menos uma pergunta ao questionário.');
        return false;
    }
    
    // Validar se pelo menos um aplicador foi selecionado
    const aplicadoresSelect = document.getElementById('aplicadores');
    if (!aplicadoresSelect.selectedOptions.length) {
        e.preventDefault();
        alert('Selecione pelo menos um aplicador para este questionário.');
        return false;
    }
    
    // Validar se perguntas de múltipla escolha têm pelo menos 2 opções
    let valid = true;
    questions.forEach((question, index) => {
        const typeSelect = question.querySelector('select[name*="[type]"]');
        const type = typeSelect.value;
        
        if (type === 'radio' || type === 'checkbox') {
            const options = question.querySelectorAll('input[name*="[options]"][name*="[text]"]');
            if (options.length < 2) {
                alert(`A pergunta ${index + 1} deve ter pelo menos 2 opções.`);
                valid = false;
                return;
            }
        }
    });
    
    if (!valid) {
        e.preventDefault();
        return false;
    }
});
</script>