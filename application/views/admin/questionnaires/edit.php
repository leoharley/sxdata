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

<!-- CSS para Lógica Condicional -->
<style>
    .question-item {
        transition: all 0.3s ease;
    }
    
    .question-item.has-conditional {
        border-left: 4px solid #007bff;
    }
    
    .conditional-rules {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }
    
    .condition-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 8px;
    }
    
    .logic-type-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .logic-type-btn {
        flex: 1;
        text-align: center;
        padding: 8px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .logic-type-btn.active {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .logic-type-btn:hover {
        border-color: #007bff;
    }
    
    .operator-selector {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        margin: 5px 0;
    }
    
    .logic-preview {
        background: #e7f3ff;
        border: 1px solid #b3d7ff;
        border-radius: 4px;
        padding: 8px;
        font-size: 0.9em;
        margin-top: 10px;
    }
    
    .question-reference {
        background: #fff3cd;
        padding: 2px 6px;
        border-radius: 3px;
        font-weight: bold;
    }
    
    .validation-errors {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 8px;
        border-radius: 4px;
        margin-top: 5px;
    }
    
    .readonly-question {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    
    .edit-mode-toggle {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 20px;
    }
</style>

<?= form_open('questionnaires/edit/' . $questionnaire->id) ?>
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
                    
                    <?php if (!empty($aplicadores_selecionados) && !$todos_selecionados): ?>
                    <div class="mt-2">
                        <small class="text-info">
                            <i class="fas fa-users me-1"></i>
                            Aplicadores atuais: 
                            <?php 
                            $nomes_selecionados = array();
                            foreach ($aplicadores as $aplicador) {
                                if (in_array($aplicador->id, $aplicadores_selecionados)) {
                                    $nomes_selecionados[] = $aplicador->full_name;
                                }
                            }
                            echo implode(', ', $nomes_selecionados);
                            ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Modo de Edição -->
        <div class="edit-mode-toggle">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Modo de Edição:</strong>
                    <p class="mb-0 small text-muted">
                        Escolha como editar as perguntas. Editar perguntas pode afetar respostas já coletadas.
                    </p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editModeToggle">
                    <label class="form-check-label" for="editModeToggle">
                        Permitir Edição Completa
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Perguntas Existentes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Perguntas (<?= count($questions) ?>)</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-info me-2" onclick="previewLogic()">
                        <i class="fas fa-eye me-1"></i>
                        Visualizar Lógica
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addQuestion()" id="addQuestionBtn" disabled>
                        <i class="fas fa-plus me-1"></i>
                        Adicionar Pergunta
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Validação Global -->
                <div id="globalValidation" class="validation-errors" style="display: none;"></div>
                
                <div id="questionsContainer">
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item border rounded p-3 mb-3 readonly-question" data-index="<?= $index ?>" data-question-id="<?= $question->id ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="mb-0">Pergunta <?= $index + 1 ?></h6>
                                <div class="edit-controls" style="display: none;">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="toggleConditionalLogic(<?= $index ?>)" title="Editar Lógica Condicional">
                                        <i class="fas fa-project-diagram"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(<?= $index ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Modo Somente Leitura -->
                            <div class="readonly-content">
                                <div class="mb-3">
                                    <strong>Pergunta:</strong>
                                    <p class="mb-1"><?= $question->question_text ?></p>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Tipo:</strong> <?= ucfirst($question->question_type) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($question->is_required): ?>
                                            <span class="badge bg-danger">Obrigatória</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Opcional</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($question->options)): ?>
                                    <div class="mb-3">
                                        <strong>Opções:</strong>
                                        <ul class="list-unstyled ms-3 mt-1">
                                            <?php foreach ($question->options as $option): ?>
                                            <li><small>• <?= $option->option_text ?></small></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($question->logic_summary)): ?>
                                    <div class="mb-3">
                                        <strong>Lógica Condicional:</strong>
                                        <div class="logic-preview">
                                            <?= $question->logic_summary ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Modo de Edição -->
                            <div class="edit-content" style="display: none;">
                                <input type="hidden" name="questions[<?= $index ?>][id]" value="<?= $question->id ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Texto da Pergunta *</label>
                                    <textarea class="form-control" name="questions[<?= $index ?>][text]" 
                                              rows="2" required onchange="updateLogicPreview()"><?= $question->question_text ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo de Pergunta *</label>
                                        <select class="form-select" name="questions[<?= $index ?>][type]" 
                                                onchange="handleQuestionTypeChange(<?= $index ?>, this.value)" required>
                                            <option value="">Selecione...</option>
                                            <option value="text" <?= $question->question_type == 'text' ? 'selected' : '' ?>>Texto Simples</option>
                                            <option value="textarea" <?= $question->question_type == 'textarea' ? 'selected' : '' ?>>Texto Longo</option>
                                            <option value="number" <?= $question->question_type == 'number' ? 'selected' : '' ?>>Número</option>
                                            <option value="email" <?= $question->question_type == 'email' ? 'selected' : '' ?>>E-mail</option>
                                            <option value="date" <?= $question->question_type == 'date' ? 'selected' : '' ?>>Data</option>
                                            <option value="datetime" <?= $question->question_type == 'datetime' ? 'selected' : '' ?>>Data e Hora</option>
                                            <option value="radio" <?= $question->question_type == 'radio' ? 'selected' : '' ?>>Múltipla Escolha (única)</option>
                                            <option value="checkbox" <?= $question->question_type == 'checkbox' ? 'selected' : '' ?>>Múltipla Escolha (múltipla)</option>
                                            <option value="select" <?= $question->question_type == 'select' ? 'selected' : '' ?>>Lista Suspensa</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="questions[<?= $index ?>][required]" value="1" 
                                                   <?= $question->is_required ? 'checked' : '' ?>>
                                            <label class="form-check-label">
                                                Pergunta obrigatória
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Opções de Resposta -->
                                <div id="options-<?= $index ?>" class="mt-3" style="display: <?= in_array($question->question_type, ['radio', 'checkbox', 'select']) ? 'block' : 'none' ?>;">
                                    <label class="form-label">Opções de Resposta</label>
                                    <div id="optionsContainer-<?= $index ?>">
                                        <?php if (!empty($question->options)): ?>
                                            <?php foreach ($question->options as $opt_index => $option): ?>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" 
                                                       name="questions[<?= $index ?>][options][<?= $opt_index ?>][text]" 
                                                       value="<?= $option->option_text ?>" placeholder="Texto da opção" required onchange="updateLogicPreview()">
                                                <input type="hidden" 
                                                       name="questions[<?= $index ?>][options][<?= $opt_index ?>][value]" 
                                                       value="<?= $option->option_value ?>">
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="this.parentElement.remove(); updateLogicPreview();">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="addOption(<?= $index ?>)">
                                        <i class="fas fa-plus me-1"></i>
                                        Adicionar Opção
                                    </button>
                                </div>
                                
                                <!-- Lógica Condicional -->
                                <div id="conditionalLogic-<?= $index ?>" class="conditional-rules" style="display: none;">
                                    <h6 class="mb-3">
                                        <i class="fas fa-project-diagram me-2"></i>
                                        Lógica Condicional
                                    </h6>
                                    
                                    <!-- Seletor de Tipo de Lógica -->
                                    <div class="logic-type-selector">
                                        <div class="logic-type-btn" onclick="selectLogicType(<?= $index ?>, 'visibility')">
                                            <i class="fas fa-eye me-1"></i>
                                            <strong>Visibilidade</strong>
                                            <small class="d-block text-muted">Mostrar/ocultar pergunta</small>
                                        </div>
                                        <div class="logic-type-btn" onclick="selectLogicType(<?= $index ?>, 'required')">
                                            <i class="fas fa-asterisk me-1"></i>
                                            <strong>Obrigatoriedade</strong>
                                            <small class="d-block text-muted">Tornar obrigatória</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Regras de Visibilidade -->
                                    <div id="visibilityRules-<?= $index ?>" class="logic-rules" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Mostrar esta pergunta quando:</strong>
                                            <button type="button" class="btn btn-xs btn-outline-primary" 
                                                    onclick="addCondition(<?= $index ?>, 'visibility')">
                                                <i class="fas fa-plus"></i> Condição
                                            </button>
                                        </div>
                                        
                                        <div class="operator-selector mb-2">
                                            <select class="form-select form-select-sm" name="questions[<?= $index ?>][logic][visibility][operator]">
                                                <option value="AND">Todas as condições (E)</option>
                                                <option value="OR">Qualquer condição (OU)</option>
                                            </select>
                                        </div>
                                        
                                        <div id="visibilityConditions-<?= $index ?>">
                                            <!-- Condições serão carregadas aqui -->
                                        </div>
                                    </div>
                                    
                                    <!-- Regras de Obrigatoriedade -->
                                    <div id="requiredRules-<?= $index ?>" class="logic-rules" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Tornar obrigatória quando:</strong>
                                            <button type="button" class="btn btn-xs btn-outline-primary" 
                                                    onclick="addCondition(<?= $index ?>, 'required')">
                                                <i class="fas fa-plus"></i> Condição
                                            </button>
                                        </div>
                                        
                                        <div class="operator-selector mb-2">
                                            <select class="form-select form-select-sm" name="questions[<?= $index ?>][logic][required][operator]">
                                                <option value="AND">Todas as condições (E)</option>
                                                <option value="OR">Qualquer condição (OU)</option>
                                            </select>
                                        </div>
                                        
                                        <div id="requiredConditions-<?= $index ?>">
                                            <!-- Condições serão carregadas aqui -->
                                        </div>
                                    </div>
                                    
                                    <!-- Preview da Lógica -->
                                    <div id="logicPreview-<?= $index ?>" class="logic-preview" style="display: none;">
                                        <!-- Preview será gerado aqui -->
                                    </div>
                                    
                                    <div class="text-end mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="clearConditionalLogic(<?= $index ?>)">
                                            Limpar Lógica
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma pergunta encontrada.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="noQuestions" class="text-center py-4" style="display: none;">
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
                        <h4 class="text-primary mb-0"><?= count($questions) ?></h4>
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
<?= form_close() ?>

<!-- Modal para Visualização da Lógica -->
<div class="modal fade" id="logicPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sitemap me-2"></i>
                    Visualização da Lógica Condicional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logicPreviewContent">
                    <!-- Conteúdo será gerado dinamicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let questionIndex = <?= count($questions) ?>;
const existingQuestions = <?= json_encode($questions) ?>;

// Dados dos projetos para JavaScript
const projectsData = <?= json_encode($projects) ?>;

// Tipos de operadores para lógica condicional
const logicOperators = {
    'equals': 'Igual a',
    'not_equals': 'Diferente de',
    'contains': 'Contém',
    'not_contains': 'Não contém',
    'greater_than': 'Maior que',
    'less_than': 'Menor que',
    'is_empty': 'Está vazio',
    'is_not_empty': 'Não está vazio'
};

// Gerenciamento inicial
document.addEventListener('DOMContentLoaded', function() {
    const aplicadoresSelect = document.getElementById('aplicadores');
    const projectSelect = document.getElementById('project_id');
    const editModeToggle = document.getElementById('editModeToggle');
    
    // Gerenciamento do select de aplicadores
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

    // Gerenciamento do modo de edição
    editModeToggle.addEventListener('change', function() {
        toggleEditMode(this.checked);
    });

    // Carregar lógica condicional existente
    loadExistingConditionalLogic();
});

// Função para alternar modo de edição
function toggleEditMode(enableEdit) {
    const questionItems = document.querySelectorAll('.question-item');
    const addQuestionBtn = document.getElementById('addQuestionBtn');
    
    questionItems.forEach(question => {
        const readonlyContent = question.querySelector('.readonly-content');
        const editContent = question.querySelector('.edit-content');
        const editControls = question.querySelector('.edit-controls');
        
        if (enableEdit) {
            question.classList.remove('readonly-question');
            readonlyContent.style.display = 'none';
            editContent.style.display = 'block';
            editControls.style.display = 'block';
        } else {
            question.classList.add('readonly-question');
            readonlyContent.style.display = 'block';
            editContent.style.display = 'none';
            editControls.style.display = 'none';
            
            // Esconder lógica condicional se aberta
            const conditionalLogic = question.querySelector('.conditional-rules');
            if (conditionalLogic) {
                conditionalLogic.style.display = 'none';
            }
        }
    });
    
    addQuestionBtn.disabled = !enableEdit;
}

// Função para carregar lógica condicional existente
function loadExistingConditionalLogic() {
    existingQuestions.forEach((question, index) => {
        if (question.conditional_logic) {
            try {
                const logic = JSON.parse(question.conditional_logic);
                loadConditionalLogicForQuestion(index, logic);
            } catch (e) {
                console.error('Erro ao carregar lógica condicional da pergunta ' + (index + 1), e);
            }
        }
    });
}

// Função para carregar lógica condicional de uma pergunta
function loadConditionalLogicForQuestion(questionIndex, logic) {
    if (logic.visibility) {
        // Ativar seção de visibilidade
        selectLogicType(questionIndex, 'visibility');
        loadConditionsForRule(questionIndex, 'visibility', logic.visibility);
    }
    
    if (logic.required) {
        // Ativar seção de obrigatoriedade
        selectLogicType(questionIndex, 'required');
        loadConditionsForRule(questionIndex, 'required', logic.required);
    }
}

// Função para carregar condições de uma regra
function loadConditionsForRule(questionIndex, ruleType, ruleData) {
    const operatorSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][operator]"]`);
    if (operatorSelect) {
        operatorSelect.value = ruleData.operator || 'AND';
    }
    
    if (ruleData.conditions && ruleData.conditions.length > 0) {
        ruleData.conditions.forEach((conditionData, condIndex) => {
            // Adicionar nova condição
            addCondition(questionIndex, ruleType);
            
            // Aguardar um pouco para que o DOM seja atualizado
            setTimeout(() => {
                const conditionsContainer = document.getElementById(`${ruleType}Conditions-${questionIndex}`);
                const conditionItems = conditionsContainer.querySelectorAll('.condition-item');
                const lastCondition = conditionItems[conditionItems.length - 1];
                
                if (lastCondition) {
                    const questionSelect = lastCondition.querySelector('select[name*="[question]"]');
                    const operatorSelect = lastCondition.querySelector('select[name*="[operator]"]');
                    const valueField = lastCondition.querySelector('input[name*="[value]"], select[name*="[value]"]');
                    
                    if (questionSelect && conditionData.question !== undefined) {
                        questionSelect.value = conditionData.question;
                        
                        // Trigger change para atualizar operadores
                        updateConditionOperators(questionIndex, ruleType, conditionItems.length - 1);
                        
                        setTimeout(() => {
                            if (operatorSelect && conditionData.operator) {
                                operatorSelect.value = conditionData.operator;
                                
                                // Trigger change para atualizar campo de valor
                                updateConditionValue(questionIndex, ruleType, conditionItems.length - 1);
                                
                                setTimeout(() => {
                                    const updatedValueField = lastCondition.querySelector('input[name*="[value]"], select[name*="[value]"]');
                                    if (updatedValueField && conditionData.value !== undefined) {
                                        updatedValueField.value = conditionData.value;
                                    }
                                }, 100);
                            }
                        }, 100);
                    }
                }
            }, 50);
        });
    }
}

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const noQuestions = document.getElementById('noQuestions');
    
    const questionHtml = `
        <div class="question-item border rounded p-3 mb-3" data-index="${questionIndex}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Pergunta ${questionIndex + 1}</h6>
                <div class="edit-controls">
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                            onclick="toggleConditionalLogic(${questionIndex})" title="Adicionar Lógica Condicional">
                        <i class="fas fa-project-diagram"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${questionIndex})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="edit-content">
                <div class="mb-3">
                    <label class="form-label">Texto da Pergunta *</label>
                    <textarea class="form-control" name="questions[${questionIndex}][text]" 
                              rows="2" required onchange="updateLogicPreview()"></textarea>
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
                            <option value="email">E-mail</option>
                            <option value="date">Data</option>
                            <option value="datetime">Data e Hora</option>
                            <option value="radio">Múltipla Escolha (única)</option>
                            <option value="checkbox">Múltipla Escolha (múltipla)</option>
                            <option value="select">Lista Suspensa</option>
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
                
                <!-- Opções de Resposta -->
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
                
                <!-- Lógica Condicional -->
                <div id="conditionalLogic-${questionIndex}" class="conditional-rules" style="display: none;">
                    <h6 class="mb-3">
                        <i class="fas fa-project-diagram me-2"></i>
                        Lógica Condicional
                    </h6>
                    
                    <!-- Seletor de Tipo de Lógica -->
                    <div class="logic-type-selector">
                        <div class="logic-type-btn" onclick="selectLogicType(${questionIndex}, 'visibility')">
                            <i class="fas fa-eye me-1"></i>
                            <strong>Visibilidade</strong>
                            <small class="d-block text-muted">Mostrar/ocultar pergunta</small>
                        </div>
                        <div class="logic-type-btn" onclick="selectLogicType(${questionIndex}, 'required')">
                            <i class="fas fa-asterisk me-1"></i>
                            <strong>Obrigatoriedade</strong>
                            <small class="d-block text-muted">Tornar obrigatória</small>
                        </div>
                    </div>
                    
                    <!-- Regras de Visibilidade -->
                    <div id="visibilityRules-${questionIndex}" class="logic-rules" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Mostrar esta pergunta quando:</strong>
                            <button type="button" class="btn btn-xs btn-outline-primary" 
                                    onclick="addCondition(${questionIndex}, 'visibility')">
                                <i class="fas fa-plus"></i> Condição
                            </button>
                        </div>
                        
                        <div class="operator-selector mb-2">
                            <select class="form-select form-select-sm" name="questions[${questionIndex}][logic][visibility][operator]">
                                <option value="AND">Todas as condições (E)</option>
                                <option value="OR">Qualquer condição (OU)</option>
                            </select>
                        </div>
                        
                        <div id="visibilityConditions-${questionIndex}">
                            <!-- Condições serão adicionadas aqui -->
                        </div>
                    </div>
                    
                    <!-- Regras de Obrigatoriedade -->
                    <div id="requiredRules-${questionIndex}" class="logic-rules" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Tornar obrigatória quando:</strong>
                            <button type="button" class="btn btn-xs btn-outline-primary" 
                                    onclick="addCondition(${questionIndex}, 'required')">
                                <i class="fas fa-plus"></i> Condição
                            </button>
                        </div>
                        
                        <div class="operator-selector mb-2">
                            <select class="form-select form-select-sm" name="questions[${questionIndex}][logic][required][operator]">
                                <option value="AND">Todas as condições (E)</option>
                                <option value="OR">Qualquer condição (OU)</option>
                            </select>
                        </div>
                        
                        <div id="requiredConditions-${questionIndex}">
                            <!-- Condições serão adicionadas aqui -->
                        </div>
                    </div>
                    
                    <!-- Preview da Lógica -->
                    <div id="logicPreview-${questionIndex}" class="logic-preview" style="display: none;">
                        <!-- Preview será gerado aqui -->
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                onclick="clearConditionalLogic(${questionIndex})">
                            Limpar Lógica
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', questionHtml);
    noQuestions.style.display = 'none';
    questionIndex++;
    updateQuestionNumbers();
}

// Função para remover pergunta
function removeQuestion(index) {
    if (confirm('Tem certeza que deseja remover esta pergunta? Isso pode afetar as regras condicionais de outras perguntas.')) {
        const questionItem = document.querySelector(`[data-index="${index}"]`);
        questionItem.remove();
        
        const remainingQuestions = document.querySelectorAll('.question-item');
        if (remainingQuestions.length === 0) {
            document.getElementById('noQuestions').style.display = 'block';
        }
        updateQuestionNumbers();
        validateAllConditionalLogic();
    }
}

// Função para lidar com mudança de tipo de pergunta
function handleQuestionTypeChange(questionIndex, type) {
    const optionsDiv = document.getElementById(`options-${questionIndex}`);
    
    if (['radio', 'checkbox', 'select'].includes(type)) {
        optionsDiv.style.display = 'block';
        const optionsContainer = document.getElementById(`optionsContainer-${questionIndex}`);
        if (optionsContainer.children.length === 0) {
            addOption(questionIndex);
            addOption(questionIndex);
        }
    } else {
        optionsDiv.style.display = 'none';
    }
    
    updateLogicPreview();
}

// Função para adicionar opção
function addOption(questionIndex) {
    const container = document.getElementById(`optionsContainer-${questionIndex}`);
    const optionIndex = container.children.length;
    
    const optionHtml = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" 
                   name="questions[${questionIndex}][options][${optionIndex}][text]" 
                   placeholder="Texto da opção" required onchange="updateLogicPreview()">
            <input type="hidden" 
                   name="questions[${questionIndex}][options][${optionIndex}][value]" 
                   value="">
            <button type="button" class="btn btn-outline-danger" 
                    onclick="this.parentElement.remove(); updateLogicPreview();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', optionHtml);
}

// Função para atualizar numeração das perguntas
function updateQuestionNumbers() {
    const questions = document.querySelectorAll('.question-item');
    questions.forEach((question, index) => {
        const title = question.querySelector('h6');
        title.textContent = `Pergunta ${index + 1}`;
        
        // Atualizar referências nos selects de condições
        updateConditionQuestionOptions(question, index);
    });
}

function toggleConditionalLogic(questionIndex) {
    const logicDiv = document.getElementById(`conditionalLogic-${questionIndex}`);
    const questionItem = document.querySelector(`[data-index="${questionIndex}"]`);
    
    if (logicDiv.style.display === 'none' || logicDiv.style.display === '') {
        logicDiv.style.display = 'block';
        questionItem.classList.add('has-conditional');
    } else {
        logicDiv.style.display = 'none';
        questionItem.classList.remove('has-conditional');
    }
}

function selectLogicType(questionIndex, type) {
    const visibilityBtn = document.querySelector(`[data-index="${questionIndex}"] .logic-type-btn:first-child`);
    const requiredBtn = document.querySelector(`[data-index="${questionIndex}"] .logic-type-btn:last-child`);
    const visibilityRules = document.getElementById(`visibilityRules-${questionIndex}`);
    const requiredRules = document.getElementById(`requiredRules-${questionIndex}`);
    
    // Reset active states
    visibilityBtn.classList.remove('active');
    requiredBtn.classList.remove('active');
    visibilityRules.style.display = 'none';
    requiredRules.style.display = 'none';
    
    if (type === 'visibility') {
        visibilityBtn.classList.add('active');
        visibilityRules.style.display = 'block';
    } else if (type === 'required') {
        requiredBtn.classList.add('active');
        requiredRules.style.display = 'block';
    }
    
    updateLogicPreview();
}

function addCondition(questionIndex, ruleType) {
    const container = document.getElementById(`${ruleType}Conditions-${questionIndex}`);
    const conditionIndex = container.children.length;
    
    const availableQuestions = getAvailableQuestionsForCondition(questionIndex);
    
    if (availableQuestions.length === 0) {
        alert('Não há perguntas anteriores disponíveis para criar condições.');
        return;
    }
    
    const conditionHtml = `
        <div class="condition-item">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" 
                            name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][question]"
                            onchange="updateConditionOperators(${questionIndex}, '${ruleType}', ${conditionIndex})">
                        <option value="">Pergunta...</option>
                        ${availableQuestions.map(q => `<option value="${q.index}">${q.title}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" 
                            name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][operator]"
                            onchange="updateConditionValue(${questionIndex}, '${ruleType}', ${conditionIndex})">
                        <option value="">Operador...</option>
                        ${Object.entries(logicOperators).map(([key, value]) => `<option value="${key}">${value}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" 
                           name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][value]"
                           placeholder="Valor..." onchange="updateLogicPreview()">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                            onclick="removeCondition(this, ${questionIndex})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', conditionHtml);
    updateLogicPreview();
}

function removeCondition(button, questionIndex) {
    button.closest('.condition-item').remove();
    updateLogicPreview();
}

function getAvailableQuestionsForCondition(currentQuestionIndex) {
    const questions = document.querySelectorAll('.question-item');
    const available = [];
    
    questions.forEach((question, index) => {
        if (index < currentQuestionIndex) {
            const textArea = question.querySelector('textarea[name*="[text]"]');
            const title = textArea ? textArea.value.substring(0, 50) + '...' : `Pergunta ${index + 1}`;
            available.push({
                index: index,
                title: title || `Pergunta ${index + 1}`
            });
        }
    });
    
    return available;
}

function updateConditionOperators(questionIndex, ruleType, conditionIndex) {
    const questionSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][question]"]`);
    const operatorSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][operator]"]`);
    const valueInput = document.querySelector(`input[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][value]"]`);
    
    const selectedQuestionIndex = questionSelect.value;
    if (!selectedQuestionIndex) return;
    
    let targetQuestion = null;
    
    // Tentar encontrar a pergunta alvo
    if (existingQuestions[selectedQuestionIndex]) {
        targetQuestion = existingQuestions[selectedQuestionIndex];
    } else {
        const targetQuestionElement = document.querySelector(`[data-index="${selectedQuestionIndex}"]`);
        if (targetQuestionElement) {
            const typeSelect = targetQuestionElement.querySelector('select[name*="[type]"]');
            targetQuestion = {
                question_type: typeSelect ? typeSelect.value : 'text'
            };
        }
    }
    
    if (!targetQuestion) return;
    
    // Limpar operadores atuais
    operatorSelect.innerHTML = '<option value="">Operador...</option>';
    
    // Adicionar operadores baseados no tipo de pergunta
    let availableOperators = [];
    
    switch (targetQuestion.question_type) {
        case 'number':
            availableOperators = ['equals', 'not_equals', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
            break;
        case 'radio':
        case 'select':
            availableOperators = ['equals', 'not_equals', 'is_empty', 'is_not_empty'];
            break;
        case 'checkbox':
            availableOperators = ['contains', 'not_contains', 'is_empty', 'is_not_empty'];
            break;
        default:
            availableOperators = ['equals', 'not_equals', 'contains', 'not_contains', 'is_empty', 'is_not_empty'];
    }
    
    availableOperators.forEach(op => {
        const option = document.createElement('option');
        option.value = op;
        option.textContent = logicOperators[op];
        operatorSelect.appendChild(option);
    });
    
    updateConditionValue(questionIndex, ruleType, conditionIndex);
}

function updateConditionValue(questionIndex, ruleType, conditionIndex) {
    const questionSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][question]"]`);
    const operatorSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][operator]"]`);
    const valueInput = document.querySelector(`input[name="questions[${questionIndex}][logic][${ruleType}][conditions][${conditionIndex}][value]"]`);
    
    const selectedQuestionIndex = questionSelect.value;
    const selectedOperator = operatorSelect.value;
    
    if (!selectedQuestionIndex || !selectedOperator) return;
    
    // Se operador é "is_empty" ou "is_not_empty", esconder campo de valor
    if (['is_empty', 'is_not_empty'].includes(selectedOperator)) {
        valueInput.style.display = 'none';
        valueInput.value = '';
        return;
    } else {
        valueInput.style.display = 'block';
    }
    
    let targetQuestion = null;
    if (existingQuestions[selectedQuestionIndex]) {
        targetQuestion = existingQuestions[selectedQuestionIndex];
    }
    
    // Se é pergunta de múltipla escolha, converter para select
    if (targetQuestion && ['radio', 'checkbox', 'select'].includes(targetQuestion.question_type)) {
        let options = [];
        
        if (targetQuestion.options && targetQuestion.options.length > 0) {
            options = targetQuestion.options;
        } else {
            // Tentar buscar opções do DOM
            const targetQuestionElement = document.querySelector(`[data-index="${selectedQuestionIndex}"]`);
            if (targetQuestionElement) {
                const optionInputs = targetQuestionElement.querySelectorAll('input[name*="[options]"][name*="[text]"]');
                optionInputs.forEach(input => {
                    if (input.value.trim()) {
                        options.push({ option_text: input.value.trim() });
                    }
                });
            }
        }
        
        if (options.length > 0) {
            // Criar select com as opções
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            select.name = valueInput.name;
            select.onchange = () => updateLogicPreview();
            
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Selecione...';
            select.appendChild(defaultOption);
            
            options.forEach(option => {
                const optionText = option.option_text || option.text;
                if (optionText && optionText.trim()) {
                    const optionElement = document.createElement('option');
                    optionElement.value = optionText.trim();
                    optionElement.textContent = optionText.trim();
                    select.appendChild(optionElement);
                }
            });
            
            valueInput.parentNode.replaceChild(select, valueInput);
        }
    }
    
    updateLogicPreview();
}

function updateConditionQuestionOptions(questionElement, newIndex) {
    const conditionSelects = questionElement.querySelectorAll('select[name*="[question]"]');
    
    conditionSelects.forEach(select => {
        // Remover opções que referenciam perguntas posteriores ou a própria pergunta
        Array.from(select.options).forEach(option => {
            if (option.value && parseInt(option.value) >= newIndex) {
                option.remove();
            }
        });
    });
}

function clearConditionalLogic(questionIndex) {
    if (confirm('Tem certeza que deseja limpar toda a lógica condicional desta pergunta?')) {
        const visibilityConditions = document.getElementById(`visibilityConditions-${questionIndex}`);
        const requiredConditions = document.getElementById(`requiredConditions-${questionIndex}`);
        const logicDiv = document.getElementById(`conditionalLogic-${questionIndex}`);
        const questionItem = document.querySelector(`[data-index="${questionIndex}"]`);
        
        visibilityConditions.innerHTML = '';
        requiredConditions.innerHTML = '';
        logicDiv.style.display = 'none';
        questionItem.classList.remove('has-conditional');
        
        updateLogicPreview();
    }
}

function updateLogicPreview() {
    const questions = document.querySelectorAll('.question-item');
    
    questions.forEach((question, index) => {
        const previewDiv = question.querySelector(`#logicPreview-${index}`);
        if (!previewDiv) return;
        
        const visibilityConditions = question.querySelectorAll('#visibilityConditions-' + index + ' .condition-item');
        const requiredConditions = question.querySelectorAll('#requiredConditions-' + index + ' .condition-item');
        
        let previewText = '';
        
        if (visibilityConditions.length > 0) {
            previewText += '<strong>Visibilidade:</strong> ';
            previewText += generateConditionsPreview(visibilityConditions, index, 'visibility');
            previewText += '<br>';
        }
        
        if (requiredConditions.length > 0) {
            previewText += '<strong>Obrigatoriedade:</strong> ';
            previewText += generateConditionsPreview(requiredConditions, index, 'required');
        }
        
        if (previewText) {
            previewDiv.innerHTML = previewText;
            previewDiv.style.display = 'block';
        } else {
            previewDiv.style.display = 'none';
        }
    });
}

function generateConditionsPreview(conditions, questionIndex, ruleType) {
    if (conditions.length === 0) return '';
    
    const operatorSelect = document.querySelector(`select[name="questions[${questionIndex}][logic][${ruleType}][operator]"]`);
    const operator = operatorSelect ? operatorSelect.value : 'AND';
    
    const conditionTexts = [];
    
    conditions.forEach(condition => {
        const questionSelect = condition.querySelector('select[name*="[question]"]');
        const operatorSelect = condition.querySelector('select[name*="[operator]"]');
        const valueField = condition.querySelector('input[name*="[value]"], select[name*="[value]"]');
        
        if (questionSelect && operatorSelect) {
            const questionText = questionSelect.selectedOptions[0]?.text || 'Pergunta';
            const operatorText = logicOperators[operatorSelect.value] || 'operador';
            const valueText = valueField ? valueField.value : '';
            
            let conditionText = `<span class="question-reference">${questionText}</span> ${operatorText}`;
            
            if (valueText && !['is_empty', 'is_not_empty'].includes(operatorSelect.value)) {
                conditionText += ` "${valueText}"`;
            }
            
            conditionTexts.push(conditionText);
        }
    });
    
    if (conditionTexts.length === 0) return '';
    
    const operatorText = operator === 'AND' ? ' E ' : ' OU ';
    return conditionTexts.join(operatorText);
}

function validateAllConditionalLogic() {
    const questions = document.querySelectorAll('.question-item');
    const errors = [];
    const warnings = [];
    
    questions.forEach((question, index) => {
        const validation = validateQuestionConditionalLogic(question, index);
        errors.push(...validation.errors);
        warnings.push(...validation.warnings);
    });
    
    // Mostrar erros globais
    const globalValidation = document.getElementById('globalValidation');
    if (errors.length > 0 || warnings.length > 0) {
        let content = '';
        
        if (errors.length > 0) {
            content += '<strong>Erros:</strong><ul>';
            errors.forEach(error => {
                content += `<li>${error}</li>`;
            });
            content += '</ul>';
        }
        
        if (warnings.length > 0) {
            content += '<strong>Avisos:</strong><ul>';
            warnings.forEach(warning => {
                content += `<li>${warning}</li>`;
            });
            content += '</ul>';
        }
        
        globalValidation.innerHTML = content;
        globalValidation.style.display = 'block';
    } else {
        globalValidation.style.display = 'none';
    }
    
    return { valid: errors.length === 0, errors, warnings };
}

function validateQuestionConditionalLogic(question, questionIndex) {
    const errors = [];
    const warnings = [];
    
    const visibilityConditions = question.querySelectorAll('#visibilityConditions-' + questionIndex + ' .condition-item');
    const requiredConditions = question.querySelectorAll('#requiredConditions-' + questionIndex + ' .condition-item');
    
    [...visibilityConditions, ...requiredConditions].forEach((condition, condIndex) => {
        const questionSelect = condition.querySelector('select[name*="[question]"]');
        const operatorSelect = condition.querySelector('select[name*="[operator]"]');
        const valueField = condition.querySelector('input[name*="[value]"], select[name*="[value]"]');
        
        if (!questionSelect.value) {
            errors.push(`Pergunta ${questionIndex + 1}: Condição ${condIndex + 1} sem pergunta selecionada`);
        }
        
        if (!operatorSelect.value) {
            errors.push(`Pergunta ${questionIndex + 1}: Condição ${condIndex + 1} sem operador selecionado`);
        }
        
        if (questionSelect.value && parseInt(questionSelect.value) >= questionIndex) {
            errors.push(`Pergunta ${questionIndex + 1}: Não pode referenciar pergunta posterior ou a si mesma`);
        }
        
        if (operatorSelect.value && !['is_empty', 'is_not_empty'].includes(operatorSelect.value) && (!valueField || !valueField.value.trim())) {
            warnings.push(`Pergunta ${questionIndex + 1}: Condição ${condIndex + 1} sem valor definido`);
        }
    });
    
    return { errors, warnings };
}

function previewLogic() {
    const modal = new bootstrap.Modal(document.getElementById('logicPreviewModal'));
    const content = document.getElementById('logicPreviewContent');
    
    const questions = document.querySelectorAll('.question-item');
    let previewHtml = '';
    
    if (questions.length === 0) {
        previewHtml = '<p class="text-muted">Nenhuma pergunta criada ainda.</p>';
    } else {
        previewHtml = '<div class="row">';
        
        questions.forEach((question, index) => {
            const textArea = question.querySelector('textarea[name*="[text]"]');
            const typeSelect = question.querySelector('select[name*="[type]"]');
            const requiredCheckbox = question.querySelector('input[name*="[required]"]');
            
            const questionText = textArea ? textArea.value : `Pergunta ${index + 1}`;
            const questionType = typeSelect ? typeSelect.value : 'text';
            const isRequired = requiredCheckbox ? requiredCheckbox.checked : false;
            
            const visibilityConditions = question.querySelectorAll('#visibilityConditions-' + index + ' .condition-item');
            const requiredConditions = question.querySelectorAll('#requiredConditions-' + index + ' .condition-item');
            
            let cardClass = 'border-start border-3 ';
            if (visibilityConditions.length > 0 && requiredConditions.length > 0) {
                cardClass += 'border-warning';
            } else if (visibilityConditions.length > 0) {
                cardClass += 'border-info';
            } else if (requiredConditions.length > 0) {
                cardClass += 'border-primary';
            } else {
                cardClass += 'border-secondary';
            }
            
            previewHtml += `
                <div class="col-12 mb-3">
                    <div class="card ${cardClass}">
                        <div class="card-body">
                            <h6 class="card-title">
                                Pergunta ${index + 1}
                                ${isRequired ? '<i class="fas fa-asterisk text-danger ms-1" title="Obrigatória"></i>' : ''}
                            </h6>
                            <p class="card-text">${questionText || 'Texto não definido'}</p>
                            <small class="text-muted">Tipo: ${questionType || 'Não definido'}</small>
                            
                            ${visibilityConditions.length > 0 ? `
                                <div class="mt-2">
                                    <span class="badge bg-info">Lógica de Visibilidade</span>
                                    <div class="mt-1 small">
                                        ${generateConditionsPreview(visibilityConditions, index, 'visibility')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${requiredConditions.length > 0 ? `
                                <div class="mt-2">
                                    <span class="badge bg-primary">Lógica de Obrigatoriedade</span>
                                    <div class="mt-1 small">
                                        ${generateConditionsPreview(requiredConditions, index, 'required')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        previewHtml += '</div>';
        
        // Adicionar resumo geral
        const totalWithLogic = Array.from(questions).filter(q => {
            const visibilityConditions = q.querySelectorAll('[id*="visibilityConditions"] .condition-item');
            const requiredConditions = q.querySelectorAll('[id*="requiredConditions"] .condition-item');
            return visibilityConditions.length > 0 || requiredConditions.length > 0;
        }).length;
        
        if (totalWithLogic > 0) {
            previewHtml = `
                <div class="alert alert-info mb-3">
                    <h6><i class="fas fa-info-circle me-2"></i>Resumo da Lógica Condicional</h6>
                    <p class="mb-0">
                        <strong>${totalWithLogic}</strong> de <strong>${questions.length}</strong> perguntas possuem lógica condicional definida.
                    </p>
                </div>
                ${previewHtml}
            `;
        }
    }
    
    content.innerHTML = previewHtml;
    modal.show();
}

function serializeConditionalLogic() {
    const questions = document.querySelectorAll('.question-item');
    
    questions.forEach((question, index) => {
        const logicData = {
            visibility: null,
            required: null
        };
        
        // Serializar condições de visibilidade
        const visibilityConditions = question.querySelectorAll('#visibilityConditions-' + index + ' .condition-item');
        if (visibilityConditions.length > 0) {
            const visibilityOperator = question.querySelector(`select[name="questions[${index}][logic][visibility][operator]"]`);
            
            logicData.visibility = {
                operator: visibilityOperator ? visibilityOperator.value : 'AND',
                conditions: []
            };
            
            visibilityConditions.forEach(condition => {
                const questionSelect = condition.querySelector('select[name*="[question]"]');
                const operatorSelect = condition.querySelector('select[name*="[operator]"]');
                const valueField = condition.querySelector('input[name*="[value]"], select[name*="[value]"]');
                
                if (questionSelect.value && operatorSelect.value) {
                    logicData.visibility.conditions.push({
                        question: parseInt(questionSelect.value),
                        operator: operatorSelect.value,
                        value: valueField ? valueField.value : ''
                    });
                }
            });
        }
        
        // Serializar condições de obrigatoriedade
        const requiredConditions = question.querySelectorAll('#requiredConditions-' + index + ' .condition-item');
        if (requiredConditions.length > 0) {
            const requiredOperator = question.querySelector(`select[name="questions[${index}][logic][required][operator]"]`);
            
            logicData.required = {
                operator: requiredOperator ? requiredOperator.value : 'AND',
                conditions: []
            };
            
            requiredConditions.forEach(condition => {
                const questionSelect = condition.querySelector('select[name*="[question]"]');
                const operatorSelect = condition.querySelector('select[name*="[operator]"]');
                const valueField = condition.querySelector('input[name*="[value]"], select[name*="[value]"]');
                
                if (questionSelect.value && operatorSelect.value) {
                    logicData.required.conditions.push({
                        question: parseInt(questionSelect.value),
                        operator: operatorSelect.value,
                        value: valueField ? valueField.value : ''
                    });
                }
            });
        }
        
        // Adicionar campo hidden com a lógica serializada
        if (logicData.visibility || logicData.required) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `questions[${index}][conditional_logic]`;
            hiddenInput.value = JSON.stringify(logicData);
            question.appendChild(hiddenInput);
        }
    });
}

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    // Validar se pelo menos um aplicador foi selecionado
    const aplicadoresSelect = document.getElementById('aplicadores');
    if (!aplicadoresSelect.selectedOptions.length) {
        e.preventDefault();
        alert('Selecione pelo menos um aplicador para este questionário.');
        return false;
    }
    
    // Validar lógica condicional se estiver em modo de edição
    const editModeToggle = document.getElementById('editModeToggle');
    if (editModeToggle.checked) {
        const logicValidation = validateAllConditionalLogic();
        if (!logicValidation.valid) {
            e.preventDefault();
            alert('Existem erros na lógica condicional. Verifique as mensagens de erro e corrija-as antes de salvar.');
            return false;
        }
        
        // Serializar lógica condicional para envio
        serializeConditionalLogic();
    }
});

// Incluir Bootstrap JS para modals
if (typeof bootstrap === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js';
    document.head.appendChild(script);
}
</script>