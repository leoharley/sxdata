<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Criar Questionário</h2>
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
                
                <div class="mb-3">
                    <label for="estimated_time" class="form-label">Tempo Estimado (minutos)</label>
                    <input type="number" class="form-control" id="estimated_time" name="estimated_time" 
                           value="<?= set_value('estimated_time') ?>" min="1" max="120">
                </div>

                <!-- NOVO: Select de Aplicadores -->
                <div class="mb-3">
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
<?= form_close() ?>

<script>
let questionIndex = 0;

// NOVO: Gerenciamento do select de aplicadores
document.addEventListener('DOMContentLoaded', function() {
    const aplicadoresSelect = document.getElementById('aplicadores');
    
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
    
    // Selecionar "Todos" por padrão
    aplicadoresSelect.querySelector('option[value="all"]').selected = true;
});

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const noQuestions = document.getElementById('noQuestions');
    
    const questionHtml = `
        <div class="question-item border rounded p-3 mb-3" data-index="${questionIndex}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Pergunta ${questionIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${questionIndex})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Texto da Pergunta *</label>
                <textarea class="form-control" name="questions[${questionIndex}][text]" 
                          rows="2" required></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Pergunta *</label>
                    <select class="form-select" name="questions[${questionIndex}][type]" 
                            onchange="handleQuestionTypeChange(${questionIndex}, this.value)" required>
                        <option value="">Selecione...</option>
                        <option value="text">Texto Simples</option>
                        <option value="textarea">Texto Longo</option>
                        <option value="number">Número</option>
                        <option value="date">Data</option>
                        <option value="datetime">Data e Hora</option>
                        <option value="radio">Múltipla Escolha (única)</option>
                        <option value="checkbox">Múltipla Escolha (múltipla)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="questions[${questionIndex}][required]" value="1">
                        <label class="form-check-label">
                            Pergunta obrigatória
                        </label>
                    </div>
                </div>
            </div>
            
            <div id="options-${questionIndex}" class="mt-3" style="display: none;">
                <label class="form-label">Opções de Resposta</label>
                <div id="optionsContainer-${questionIndex}">
                    <!-- Opções serão adicionadas aqui -->
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="addOption(${questionIndex})">
                    <i class="fas fa-plus me-1"></i>
                    Adicionar Opção
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', questionHtml);
    noQuestions.style.display = 'none';
    questionIndex++;
    updateQuestionNumbers();
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