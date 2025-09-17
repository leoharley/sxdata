<?php
// admin/reports/index.php

$current_filters = http_build_query($filters ?? []);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Relatórios e Análises</h2>
            <div>
                <button class="btn btn-outline-primary me-2" onclick="exportAllData()">
                    <i class="fas fa-download me-2"></i>
                    Exportar Tudo
                </button>
                <button class="btn btn-success" onclick="openKMZModal()">
                    <i class="fas fa-map-marked-alt me-2"></i>
                    Gerar KMZ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Relatório -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filtros de Análise</h5>
    </div>
    <div class="card-body">
        <?= form_open('reports', ['method' => 'GET', 'id' => 'reportFilters']) ?>
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Período</label>
                <select class="form-select" name="period" onchange="toggleCustomDates(this.value)">
                    <option value="last_7_days" <?= set_select('period', 'last_7_days', $filters['period'] ?? '' == 'last_7_days') ?>>Últimos 7 dias</option>
                    <option value="last_30_days" <?= set_select('period', 'last_30_days', $filters['period'] ?? '' == 'last_30_days') ?>>Últimos 30 dias</option>
                    <option value="last_3_months" <?= set_select('period', 'last_3_months', $filters['period'] ?? '' == 'last_3_months') ?>>Últimos 3 meses</option>
                    <option value="custom" <?= set_select('period', 'custom', $filters['period'] ?? '' == 'custom') ?>>Personalizado</option>
                </select>
            </div>
            
            <div class="col-md-2" id="customDatesFrom" style="display: <?= ($filters['period'] ?? '') == 'custom' ? 'block' : 'none' ?>;">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" name="date_from" value="<?= $filters['date_from'] ?? '' ?>">
            </div>
            
            <div class="col-md-2" id="customDatesTo" style="display: <?= ($filters['period'] ?? '') == 'custom' ? 'block' : 'none' ?>;">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" name="date_to" value="<?= $filters['date_to'] ?? '' ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Questionário</label>
                <select class="form-select" name="questionnaire_id">
                    <option value="">Todos</option>
                    <?php foreach ($questionnaires as $questionnaire): ?>
                    <option value="<?= $questionnaire->id ?>" 
                            <?= set_select('questionnaire_id', $questionnaire->id, 
                               isset($filters['questionnaire_id']) && $filters['questionnaire_id'] == $questionnaire->id) ?>>
                        <?= $questionnaire->title ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-chart-line me-1"></i>
                    Gerar
                </button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<!-- Estatísticas do Período -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h3 class="stat-number"><?= $period_stats['total_responses'] ?? 0 ?></h3>
            <p class="stat-label">Respostas no Período</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #8fae5d, #a8c46a);">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="stat-number"><?= $period_stats['unique_respondents'] ?? 0 ?></h3>
            <p class="stat-label">Respondentes Únicos</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                <i class="fas fa-camera"></i>
            </div>
            <h3 class="stat-number"><?= $period_stats['photos_captured'] ?? 0 ?></h3>
            <p class="stat-label">Fotos Capturadas</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #17a2b8, #138496);">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3 class="stat-number"><?= $period_stats['locations_captured'] ?? 0 ?></h3>
            <p class="stat-label">Localizações</p>
        </div>
    </div>
</div>

<!-- Modal KMZ -->
<div class="modal fade" id="kmzModal" tabindex="-1" aria-labelledby="kmzModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #8fae5d, #a8c46a); color: white;">
                <h5 class="modal-title" id="kmzModalLabel">
                    <i class="fas fa-map-marked-alt me-2"></i>
                    Gerar Arquivo KMZ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body">
                <form id="kmzForm">
                    <!-- Seção: Filtros de Dados -->
                    <div class="filter-section" style="background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #e9ecef;">
                        <h6 style="color: #23345F; margin-bottom: 1rem; font-weight: 700;">
                            <i class="fas fa-filter me-2"></i>
                            Filtros de Dados
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="kmz_questionnaires" class="form-label" style="font-weight: 600; color: #495057;">
                                    Questionários
                                </label>
                                <select class="form-select" id="kmz_questionnaires" name="questionnaires[]" multiple>
                                    <option value="all">Todos os Questionários</option>
                                </select>
                                <div class="form-text">
                                    Use Ctrl+clique para selecionar múltiplos questionários. Se nenhum for selecionado, todos serão incluídos.
                                </div>
                                <div id="selectedQuestionnairesBadges" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="kmz_date_from" class="form-label" style="font-weight: 600; color: #495057;">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Data Inicial
                                </label>
                                <input type="date" class="form-control" id="kmz_date_from" name="date_from">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="kmz_date_to" class="form-label" style="font-weight: 600; color: #495057;">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Data Final
                                </label>
                                <input type="date" class="form-control" id="kmz_date_to" name="date_to">
                            </div>
                        </div>
                        
                        <div id="dateRangeInfo" style="display: none; background: #fff3cd; color: #664d03; padding: 0.5rem; border-radius: 4px; font-size: 0.9rem; margin-top: 0.5rem;"></div>
                    </div>

                    <!-- Seção: Configurações do KMZ -->
                    <div class="filter-section" style="background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #e9ecef;">
                        <h6 style="color: #23345F; margin-bottom: 1rem; font-weight: 700;">
                            <i class="fas fa-cog me-2"></i>
                            Configurações do Arquivo
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="kmz_filename" class="form-label" style="font-weight: 600; color: #495057;">Nome do Arquivo</label>
                                <input type="text" class="form-control" id="kmz_filename" name="filename" 
                                       placeholder="localizacoes_sxdata" value="localizacoes_sxdata">
                                <div class="form-text">O arquivo será salvo como: [nome]_YYYY-MM-DD.kmz</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="font-weight: 600; color: #495057;">Incluir Dados</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="kmz_include_photos" name="include_photos" checked>
                                    <label class="form-check-label" for="kmz_include_photos">
                                        Incluir referências de fotos
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="kmz_include_respondent" name="include_respondent" checked>
                                    <label class="form-check-label" for="kmz_include_respondent">
                                        Incluir dados do respondente
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="kmz_include_applicator" name="include_applicator" checked>
                                    <label class="form-check-label" for="kmz_include_applicator">
                                        Incluir dados do aplicador
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview dos Dados -->
                    <div id="kmzPreview" class="preview-section" style="display: none; background: #e8f5e8; border-radius: 8px; padding: 1rem; margin-top: 1rem; border-left: 4px solid #8fae5d;"></div>

                    <!-- Avisos -->
                    <div id="noDataWarning" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção:</strong> Nenhuma localização foi encontrada com os filtros selecionados.
                    </div>

                    <div id="successFeedback" class="alert alert-success" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Arquivo gerado com sucesso!</strong> O download deve iniciar automaticamente.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="previewKMZData()">
                    <i class="fas fa-eye me-1"></i>Visualizar
                </button>
                <button type="button" class="btn btn-success" onclick="generateKMZFromModal()" id="generateKMZBtn">
                    <i class="fas fa-download me-1"></i>Gerar KMZ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Resto do conteúdo original do relatório -->
<!-- Gráficos e Análises -->
<div class="row">
    <!-- Todo o conteúdo existente dos gráficos permanece igual -->
    <!-- ... -->
</div>

<style>
.filter-section h6 {
    color: #23345F !important;
    margin-bottom: 1rem !important;
    font-weight: 700 !important;
}

.form-label {
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.form-control:focus,
.form-select:focus {
    border-color: #8fae5d !important;
    box-shadow: 0 0 0 0.2rem rgba(143, 174, 93, 0.25) !important;
}

.btn-success {
    background: linear-gradient(135deg, #8fae5d, #a8c46a) !important;
    border: none !important;
    color: white !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
}

.btn-success:hover {
    background: linear-gradient(135deg, #7a9851, #95b157) !important;
    color: white !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(143, 174, 93, 0.3) !important;
}

.questionnaire-badge {
    background: #8fae5d !important;
    color: white !important;
    padding: 0.25rem 0.5rem !important;
    border-radius: 12px !important;
    font-size: 0.8rem !important;
    margin-right: 0.5rem !important;
    margin-bottom: 0.25rem !important;
    display: inline-block !important;
}

.preview-stats {
    display: flex;
    justify-content: space-around;
    text-align: center;
    margin-top: 1rem;
}

.preview-stat {
    flex: 1;
}

.preview-stat .number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #8fae5d;
}

.preview-stat .label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 500;
}
</style>

<script>
// Variáveis globais para KMZ
let kmzModal;
let currentPreviewData = null;

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modal KMZ
    const kmzModalElement = document.getElementById('kmzModal');
    if (kmzModalElement) {
        kmzModal = new bootstrap.Modal(kmzModalElement);
        initializeKMZForm();
    }
    
    // Resto da inicialização existente...
    window.chartInstances = {};
    // ... código existente dos gráficos ...
});

function initializeKMZForm() {
    // Configurar datas padrão (últimos 30 dias)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('kmz_date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('kmz_date_to').value = today.toISOString().split('T')[0];
    
    // Event listeners
    document.getElementById('kmz_questionnaires').addEventListener('change', updateSelectedQuestionnaires);
    document.getElementById('kmz_date_from').addEventListener('change', updateKMZDateRangeInfo);
    document.getElementById('kmz_date_to').addEventListener('change', updateKMZDateRangeInfo);
    document.getElementById('kmz_filename').addEventListener('input', validateFilename);
    
    // Atualizar informações iniciais
    updateSelectedQuestionnaires();
    updateKMZDateRangeInfo();
}

function openKMZModal() {
    // Limpar estado anterior
    clearKMZModalState();
    
    // Carregar questionários disponíveis
    loadQuestionnaireOptions();
    
    // Abrir modal
    kmzModal.show();
}

function clearKMZModalState() {
    document.getElementById('kmzPreview').style.display = 'none';
    document.getElementById('noDataWarning').style.display = 'none';
    document.getElementById('successFeedback').style.display = 'none';
    
    const btn = document.getElementById('generateKMZBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-download me-1"></i>Gerar KMZ';
    
    currentPreviewData = null;
}

function loadQuestionnaireOptions() {
    const questionnaires = <?= json_encode($questionnaires ?? []) ?>;
    const select = document.getElementById('kmz_questionnaires');
    
    // Limpar opções existentes (exceto "Todos")
    while (select.children.length > 1) {
        select.removeChild(select.lastChild);
    }
    
    // Adicionar questionários
    questionnaires.forEach(q => {
        const option = document.createElement('option');
        option.value = q.id;
        option.textContent = q.title;
        select.appendChild(option);
    });
}

function updateSelectedQuestionnaires() {
    const select = document.getElementById('kmz_questionnaires');
    const badgesContainer = document.getElementById('selectedQuestionnairesBadges');
    const selectedValues = Array.from(select.selectedOptions).map(option => option.value);
    
    badgesContainer.innerHTML = '';
    
    if (selectedValues.includes('all') || selectedValues.length === 0) {
        const badge = document.createElement('span');
        badge.className = 'questionnaire-badge';
        badge.innerHTML = '<i class="fas fa-globe me-1"></i>Todos os Questionários';
        badgesContainer.appendChild(badge);
    } else {
        selectedValues.forEach(value => {
            const option = select.querySelector(`option[value="${value}"]`);
            if (option) {
                const badge = document.createElement('span');
                badge.className = 'questionnaire-badge';
                badge.innerHTML = `<i class="fas fa-file-alt me-1"></i>${option.textContent}`;
                badgesContainer.appendChild(badge);
            }
        });
    }
}

function updateKMZDateRangeInfo() {
    const dateFrom = document.getElementById('kmz_date_from').value;
    const dateTo = document.getElementById('kmz_date_to').value;
    const infoDiv = document.getElementById('dateRangeInfo');
    
    if (dateFrom && dateTo) {
        const startDate = new Date(dateFrom);
        const endDate = new Date(dateTo);
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (startDate > endDate) {
            infoDiv.style.display = 'block';
            infoDiv.className = 'alert alert-warning';
            infoDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Data inicial deve ser anterior à data final.';
        } else {
            infoDiv.style.display = 'block';
            infoDiv.style.background = '#fff3cd';
            infoDiv.style.color = '#664d03';
            infoDiv.innerHTML = `<i class="fas fa-info-circle me-1"></i>Período selecionado: ${diffDays} dia(s) (${formatDate(startDate)} até ${formatDate(endDate)})`;
        }
    } else {
        infoDiv.style.display = 'none';
    }
}

function validateFilename() {
    const input = document.getElementById('kmz_filename');
    const filename = input.value;
    
    // Remover caracteres inválidos
    const cleanFilename = filename.replace(/[^a-zA-Z0-9_-]/g, '_');
    
    if (filename !== cleanFilename) {
        input.value = cleanFilename;
    }
}

function getKMZFormData() {
    const select = document.getElementById('kmz_questionnaires');
    const selectedQuestionnaires = Array.from(select.selectedOptions).map(option => option.value);
    
    return {
        questionnaires: selectedQuestionnaires,
        date_from: document.getElementById('kmz_date_from').value,
        date_to: document.getElementById('kmz_date_to').value,
        filename: document.getElementById('kmz_filename').value.trim() || 'localizacoes_sxdata',
        include_photos: document.getElementById('kmz_include_photos').checked,
        include_respondent: document.getElementById('kmz_include_respondent').checked,
        include_applicator: document.getElementById('kmz_include_applicator').checked
    };
}

function validateKMZFormData(data) {
    if (data.date_from && data.date_to) {
        if (new Date(data.date_from) > new Date(data.date_to)) {
            alert('Data inicial deve ser anterior à data final.');
            return false;
        }
    }
    
    if (!data.filename.trim()) {
        alert('Nome do arquivo é obrigatório.');
        return false;
    }
    
    return true;
}

function previewKMZData() {
    const formData = getKMZFormData();
    
    if (!validateKMZFormData(formData)) {
        return;
    }
    
    showKMZPreviewLoading();
    
    fetch('<?= base_url('reports/preview_kmz_data') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayKMZPreviewData(data.preview);
            currentPreviewData = data.preview;
        } else {
            showKMZError(data.message || 'Erro ao carregar preview dos dados.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showKMZError('Erro de conexão. Tente novamente.');
    });
}

function showKMZPreviewLoading() {
    const previewDiv = document.getElementById('kmzPreview');
    previewDiv.style.display = 'block';
    previewDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="text-muted mb-0">Carregando preview dos dados...</p>
        </div>
    `;
}

function displayKMZPreviewData(data) {
    const previewDiv = document.getElementById('kmzPreview');
    const noDataWarning = document.getElementById('noDataWarning');
    
    if (data.total_locations > 0) {
        previewDiv.innerHTML = `
            <h6>
                <i class="fas fa-eye me-2"></i>
                Preview dos Dados
            </h6>
            <div class="preview-stats">
                <div class="preview-stat">
                    <div class="number">${data.total_locations}</div>
                    <div class="label">Localizações</div>
                </div>
                <div class="preview-stat">
                    <div class="number">${data.questionnaires_count}</div>
                    <div class="label">Questionários</div>
                </div>
                <div class="preview-stat">
                    <div class="number">${data.photos_count}</div>
                    <div class="label">Fotos</div>
                </div>
                <div class="preview-stat">
                    <div class="number">${data.date_range}</div>
                    <div class="label">Período</div>
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Última atualização: ${data.last_update || 'Agora'}
            </div>
        `;
        previewDiv.style.display = 'block';
        noDataWarning.style.display = 'none';
        document.getElementById('generateKMZBtn').disabled = false;
    } else {
        previewDiv.style.display = 'none';
        noDataWarning.style.display = 'block';
        document.getElementById('generateKMZBtn').disabled = true;
    }
}

function generateKMZFromModal() {
    const formData = getKMZFormData();
    
    if (!validateKMZFormData(formData)) {
        return;
    }
    
    if (!currentPreviewData || currentPreviewData.total_locations === 0) {
        alert('Execute o preview primeiro para verificar se há dados disponíveis.');
        return;
    }
    
    const btn = document.getElementById('generateKMZBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Gerando KMZ...';
    btn.disabled = true;
    
    // Construir URL com parâmetros
    const params = new URLSearchParams();
    
    if (formData.questionnaires && formData.questionnaires.length > 0 && !formData.questionnaires.includes('all')) {
        formData.questionnaires.forEach(id => {
            params.append('questionnaire_ids[]', id);
        });
    }
    
    if (formData.date_from) {
        params.append('date_from', formData.date_from);
    }
    
    if (formData.date_to) {
        params.append('date_to', formData.date_to);
    }
    
    if (formData.filename) {
        params.append('filename', formData.filename);
    }
    
    params.append('include_photos', formData.include_photos ? '1' : '0');
    params.append('include_respondent', formData.include_respondent ? '1' : '0');
    params.append('include_applicator', formData.include_applicator ? '1' : '0');
    
    // Fazer download
    window.location.href = '<?= base_url('reports/generate_kmz_filtered') ?>?' + params.toString();
    
    // Mostrar feedback de sucesso
    showKMZSuccessMessage();
    
    // Restaurar botão após delay
    setTimeout(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }, 3000);
}

function showKMZSuccessMessage() {
    const successDiv = document.getElementById('successFeedback');
    successDiv.style.display = 'block';
    
    setTimeout(() => {
        successDiv.style.display = 'none';
    }, 5000);
}

function showKMZError(message) {
    const previewDiv = document.getElementById('kmzPreview');
    previewDiv.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
    previewDiv.style.display = 'block';
}

// Funções auxiliares
function formatDate(date) {
    return date.toLocaleDateString('pt-BR');
}

// Função original para exportar todos os dados (mantida)
function exportAllData() {
    const exportBtn = document.querySelector('button[onclick="exportAllData()"]');
    const originalContent = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando Relatório...';
    exportBtn.disabled = true;
    
    window.location.href = '<?= base_url('reports/export_all?' . $current_filters) ?>';
    
    setTimeout(() => {
        exportBtn.innerHTML = originalContent;
        exportBtn.disabled = false;
    }, 5000);
}

// Função para alternar datas personalizadas (mantida do código original)
function toggleCustomDates(period) {
    const fromDiv = document.getElementById('customDatesFrom');
    const toDiv = document.getElementById('customDatesTo');
    
    if (period === 'custom') {
        fromDiv.style.display = 'block';
        toDiv.style.display = 'block';
    } else {
        fromDiv.style.display = 'none';
        toDiv.style.display = 'none';
    }
}

// Resto do JavaScript existente dos gráficos...
// (manter todo o código existente dos gráficos Chart.js)
</script>