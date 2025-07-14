<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Questionário</h2>
            <a href="<?= base_url('questionnaires') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<?php
// Debug: Verificar valores dos checkboxes (remover em produção)
// echo "<!-- DEBUG: requires_consent = " . var_export($questionnaire->requires_consent, true) . " -->";
// echo "<!-- DEBUG: requires_location = " . var_export($questionnaire->requires_location, true) . " -->";
// echo "<!-- DEBUG: requires_photo = " . var_export($questionnaire->requires_photo, true) . " -->";

// Função helper para checkbox - garantir valores booleanos corretos
function is_checkbox_checked($value) {
    return $value === true || $value === 1 || $value === '1' || $value === 'true';
}
?>

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
                    <div class="col-md-6">
                        <label for="estimated_time" class="form-label">Tempo Estimado (minutos)</label>
                        <input type="number" class="form-control" id="estimated_time" name="estimated_time" 
                               value="<?= set_value('estimated_time', $questionnaire->estimated_time) ?>" min="1" max="120">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= set_select('status', 'active', $questionnaire->status == 'active') ?>>Ativo</option>
                            <option value="paused" <?= set_select('status', 'paused', $questionnaire->status == 'paused') ?>>Pausado</option>
                            <option value="inactive" <?= set_select('status', 'inactive', $questionnaire->status == 'inactive') ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <!-- NOVO: Select de Aplicadores -->
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
        
        <!-- Perguntas Existentes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Perguntas (<?= count($questions) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($questions)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Atenção:</strong> Para editar perguntas, você precisa criar uma nova versão do questionário ou duplicá-lo.
                        Editar perguntas pode afetar respostas já coletadas.
                    </div>
                    
                    <?php foreach ($questions as $index => $question): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">Pergunta <?= $index + 1 ?></h6>
                            <span class="badge bg-secondary"><?= ucfirst($question->question_type) ?></span>
                        </div>
                        
                        <p class="mb-2"><?= $question->question_text ?></p>
                        
                        <?php if ($question->is_required): ?>
                            <small class="text-danger">
                                <i class="fas fa-asterisk me-1"></i>
                                Obrigatória
                            </small>
                        <?php endif; ?>
                        
                        <?php if (!empty($question->options)): ?>
                            <div class="mt-2">
                                <small class="text-muted">Opções:</small>
                                <ul class="list-unstyled ms-3 mt-1">
                                    <?php foreach ($question->options as $option): ?>
                                    <li><small>• <?= $option->option_text ?></small></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma pergunta encontrada.</p>
                    </div>
                <?php endif; ?>
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
                <!-- Checkbox: Requer Consentimento -->
                <div class="form-check mb-3">
                    <?php 
                    // Método 1: Usando set_checkbox() com verificação robusta
                    $consent_checked = set_checkbox('requires_consent', '1', is_checkbox_checked($questionnaire->requires_consent));
                    
                    // Método 2: Verificação manual como fallback
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

                <!-- Debug (remover em produção) -->
                <?php if (ENVIRONMENT === 'development'): ?>
                <div class="mt-3 p-2 bg-light border rounded">
                    <small class="text-muted">
                        <strong>Debug:</strong><br>
                        Consentimento: <?= var_export($questionnaire->requires_consent, true) ?><br>
                        Localização: <?= var_export($questionnaire->requires_location, true) ?><br>
                        Foto: <?= var_export($questionnaire->requires_photo, true) ?>
                    </small>
                </div>
                <?php endif; ?>
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
                        Criado em <?= date('d/m/Y', strtotime($questionnaire->created_at)) ?>
                        <br>por <?= $questionnaire->created_by_name ?>
                    </small>
                </div>
                
                <!-- Mostrar aplicadores atuais -->
                <?php if (!empty($aplicadores_selecionados)): ?>
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <strong>Aplicadores:</strong><br>
                        <?php if ($todos_selecionados): ?>
                            <span class="text-success">Todos (<?= count($aplicadores) ?>)</span>
                        <?php else: ?>
                            <span class="text-info"><?= count($aplicadores_selecionados) ?> selecionados</span>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endif; ?>
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
                <a href="<?= base_url('questionnaires') ?>" class="btn btn-outline-secondary w-100">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
// Gerenciamento do select de aplicadores
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

    // Debug: Log dos valores dos checkboxes no carregamento da página
    console.log('Estado inicial dos checkboxes:');
    console.log('Consentimento:', document.getElementById('requires_consent').checked);
    console.log('Localização:', document.getElementById('requires_location').checked);
    console.log('Foto:', document.getElementById('requires_photo').checked);
});

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    // Validar se pelo menos um aplicador foi selecionado
    const aplicadoresSelect = document.getElementById('aplicadores');
    if (!aplicadoresSelect.selectedOptions.length) {
        e.preventDefault();
        alert('Selecione pelo menos um aplicador para este questionário.');
        return false;
    }
});
</script>