<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Relatórios e Análises</h2>
            <div>
                <button class="btn btn-outline-primary me-2" onclick="exportAllData()">
                    <i class="fas fa-download me-2"></i>
                    Exportar Tudo
                </button>
                <button class="btn btn-success" onclick="generateKMZ()">
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

<!-- Gráficos e Análises -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Respostas por Dia</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($charts_data) && !empty($charts_data['responses_by_day'])): ?>
                    <canvas id="responsesTimeChart" width="400" height="200"></canvas>
                <?php else: ?>
                    <div class="chart-placeholder" style="height: 200px;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Nenhum dado para exibir</p>
                                <small class="text-muted">Selecione um período com dados</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Aplicadores Mais Ativos</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($charts_data) && !empty($charts_data['top_applicators'])): ?>
                    <canvas id="applicatorsChart" width="400" height="200"></canvas>
                <?php else: ?>
                    <div class="chart-placeholder" style="height: 200px;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Nenhum aplicador ativo</p>
                                <small class="text-muted">Aguardando atividade dos usuários</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Questionários por Popularidade</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($charts_data) && !empty($charts_data['questionnaires_popularity'])): ?>
                    <canvas id="questionnairesPopularityChart" width="200" height="200"></canvas>
                <?php else: ?>
                    <div class="chart-placeholder" style="height: 200px;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-poll fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Sem dados de popularidade</p>
                                <small class="text-muted">Crie questionários e colete respostas</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Taxa de Consentimento</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <?php if (isset($period_stats['consent_rate']) && $period_stats['consent_rate'] !== null): ?>
                        <div class="progress-circle mb-3">
                            <span class="progress-value"><?= $period_stats['consent_rate'] ?>%</span>
                        </div>
                        <p class="text-muted mb-0">Respondentes que consentiram com o uso dos dados</p>
                    <?php else: ?>
                        <div class="progress-circle mb-3" style="background: conic-gradient(#e9ecef 360deg);">
                            <span class="progress-value">-</span>
                        </div>
                        <p class="text-muted mb-0">Nenhum dado de consentimento disponível</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mapa de Calor - Localizações</h5>
                <div>
                    <span class="badge bg-info me-2">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= isset($heatmap_locations['stats']['total_locations']) ? $heatmap_locations['stats']['total_locations'] : 0 ?> locais
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleHeatmapView()" id="heatmapToggle">
                        <i class="fas fa-layer-group me-1"></i>
                        Calor
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($heatmap_locations['points'])): ?>
                    <div id="heatmap" style="height: 300px; width: 100%;"></div>
                    
                    <!-- Informações do mapa -->
                    <div class="p-3 border-top bg-light">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Total de Pontos</small>
                                <strong class="text-primary"><?= count($heatmap_locations['points']) ?></strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Centro do Mapa</small>
                                <strong class="text-success">
                                    <?= number_format($heatmap_locations['stats']['center']['lat'], 4) ?>,
                                    <?= number_format($heatmap_locations['stats']['center']['lng'], 4) ?>
                                </strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Área Coberta</small>
                                <strong class="text-info">
                                    <?php if ($heatmap_locations['stats']['bounds']): ?>
                                        <?= number_format(
                                            abs($heatmap_locations['stats']['bounds']['northeast']['lat'] - 
                                                $heatmap_locations['stats']['bounds']['southwest']['lat']), 2
                                        ) ?>°
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="height: 300px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <i class="fas fa-map fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted mb-2">Nenhuma localização encontrada</h6>
                            <p class="text-muted mb-0 small">
                                Colete dados com GPS ativado para visualizar o mapa de calor
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    </div>
</div>

<!-- Tabela de Análise Detalhada -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Análise Detalhada por Questionário</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($detailed_analysis) && is_array($detailed_analysis)): ?>
            <?php 
            // Filtrar apenas objetos válidos
            $valid_analysis = array_filter($detailed_analysis, function($item) {
                return is_object($item) && isset($item->questionnaire_title);
            });
            ?>
            
            <?php if (!empty($valid_analysis)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Questionário</th>
                            <th>Total Respostas</th>
                            <th>Média por Dia</th>
                            <th>Taxa Conclusão</th>
                            <th>Tempo Médio</th>
                            <th>Localizações</th>
                            <th>Fotos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valid_analysis as $analysis): ?>
                        <tr>
                            <td><strong><?= isset($analysis->questionnaire_title) ? $analysis->questionnaire_title : 'N/A' ?></strong></td>
                            <td><span class="badge bg-primary"><?= isset($analysis->total_responses) ? $analysis->total_responses : 0 ?></span></td>
                            <td><?= isset($analysis->avg_per_day) ? number_format($analysis->avg_per_day, 1) : '0.0' ?></td>
                            <td>
                                <?php $completion_rate = isset($analysis->completion_rate) ? $analysis->completion_rate : 0; ?>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $completion_rate ?>%"></div>
                                </div>
                                <small><?= $completion_rate ?>%</small>
                            </td>
                            <td><?= isset($analysis->avg_time) ? $analysis->avg_time : '0' ?> min</td>
                            <td>
                                <?php $locations_count = isset($analysis->locations_count) ? $analysis->locations_count : 0; ?>
                                <?php if ($locations_count > 0): ?>
                                    <span class="text-success"><?= $locations_count ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $photos_count = isset($analysis->photos_count) ? $analysis->photos_count : 0; ?>
                                <?php if ($photos_count > 0): ?>
                                    <span class="text-success"><?= $photos_count ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?= base_url('responses?questionnaire_id=' . (isset($analysis->questionnaire_id) ? $analysis->questionnaire_id : '')) ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Ver Respostas">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('responses/export?questionnaire_id=' . (isset($analysis->questionnaire_id) ? $analysis->questionnaire_id : '')) ?>" 
                                       class="btn btn-sm btn-outline-success" title="Exportar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <!-- Estado vazio quando há dados inválidos -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                </div>
                <h5 class="text-muted mb-3">Dados inconsistentes encontrados</h5>
                <p class="text-muted mb-4">
                    Os dados retornados não estão no formato esperado.<br>
                    Tente recarregar a página ou entre em contato com o suporte.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="location.reload();">
                        <i class="fas fa-refresh me-1"></i>
                        Recarregar Página
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters();">
                        <i class="fas fa-filter me-1"></i>
                        Limpar Filtros
                    </button>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Estado vazio -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-chart-bar fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum dado encontrado</h5>
                <p class="text-muted mb-4">
                    Não há dados de análise para o período selecionado.<br>
                    Tente ajustar os filtros ou verificar se existem questionários ativos.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="resetFilters();">
                        <i class="fas fa-refresh me-1"></i>
                        Limpar Filtros
                    </button>
                    <a href="<?= base_url('questionnaires') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Criar Questionário
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.chart-placeholder {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.chart-placeholder:hover {
    border-color: #8fae5d;
    background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
}

.progress-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: conic-gradient(#8fae5d <?= isset($period_stats['consent_rate']) ? $period_stats['consent_rate'] * 3.6 : 0 ?>deg, #e9ecef 0deg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    position: relative;
}

.progress-circle::before {
    content: '';
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: white;
    position: absolute;
}

.progress-value {
    position: relative;
    z-index: 1;
    font-size: 18px;
    font-weight: 600;
    color: #23345F;
}

/* Animação sutil para os placeholders */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.chart-placeholder i {
    animation: pulse 2s infinite;
}

/* Estilos para os cards de estatísticas (se não existirem) */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    color: white;
    font-size: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #23345F;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

#heatmap {
    border-radius: 0;
    border: none;
}

/* Marcadores personalizados */
.custom-marker {
    background: transparent;
    border: none;
}

.marker-pin {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #8fae5d;
    border-radius: 50% 50% 50% 0;
    position: relative;
    transform: rotate(-45deg);
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.marker-pin i {
    color: white;
    font-size: 14px;
    transform: rotate(45deg);
}

/* Popups do Leaflet */
.leaflet-popup-content-wrapper {
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.leaflet-popup-content {
    margin: 12px !important;
}

/* Badge customizado para popups */
.badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    margin-right: 0.25rem;
}

/* Controles do mapa */
.leaflet-control-zoom a {
    background-color: #8fae5d !important;
    border-color: #8fae5d !important;
}

.leaflet-control-zoom a:hover {
    background-color: #7a9851 !important;
}

/* Responsividade */
@media (max-width: 768px) {
    .marker-pin {
        width: 25px;
        height: 25px;
    }
    
    .marker-pin i {
        font-size: 12px;
    }
}
</style>

<script>
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

function exportAllData() {
    window.location.href = '<?= base_url('reports/export_all?' . http_build_query($filters ?? [])) ?>';
}

function generateKMZ() {
    window.location.href = '<?= base_url('reports/generate_kmz?' . http_build_query($filters ?? [])) ?>';
}

function resetFilters() {
    document.getElementById('reportFilters').reset();
    // Remove parâmetros da URL e recarrega a página
    window.location.href = window.location.pathname;
}

// Gráficos (usando dados PHP)
<?php if (!empty($charts_data) && !empty($charts_data['responses_by_day'])): ?>
// Gráfico de Respostas por Dia
const responsesTimeCtx = document.getElementById('responsesTimeChart').getContext('2d');
new Chart(responsesTimeCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($charts_data['responses_by_day'], 'date')) ?>,
        datasets: [{
            label: 'Respostas',
            data: <?= json_encode(array_column($charts_data['responses_by_day'], 'count')) ?>,
            borderColor: '#8fae5d',
            backgroundColor: 'rgba(143, 174, 93, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false },
            title: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
<?php endif; ?>

<?php if (!empty($charts_data) && !empty($charts_data['top_applicators'])): ?>
// Gráfico de Aplicadores
const applicatorsCtx = document.getElementById('applicatorsChart').getContext('2d');
new Chart(applicatorsCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($charts_data['top_applicators'], 'name')) ?>,
        datasets: [{
            label: 'Respostas',
            data: <?= json_encode(array_column($charts_data['top_applicators'], 'count')) ?>,
            backgroundColor: '#8fae5d'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
<?php endif; ?>

<?php if (!empty($charts_data) && !empty($charts_data['questionnaires_popularity'])): ?>
// Gráfico de Popularidade dos Questionários
const questionnairesCtx = document.getElementById('questionnairesPopularityChart').getContext('2d');
new Chart(questionnairesCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($charts_data['questionnaires_popularity'], 'title')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($charts_data['questionnaires_popularity'], 'count')) ?>,
            backgroundColor: ['#8fae5d', '#007bff', '#ffc107', '#dc3545', '#17a2b8']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
<?php endif; ?>


// Dados do mapa de calor vindos do PHP
const heatmapData = <?= json_encode($heatmap_locations ?? ['points' => [], 'stats' => []]) ?>;

let map;
let heatLayer;
let markersLayer;
let isHeatmapView = true;

function initLeafletMap() {
    <?php if (!empty($heatmap_locations['points'])): ?>
    
    // Configurar o centro do mapa
    const mapCenter = [
        <?= $heatmap_locations['stats']['center']['lat'] ?>,
        <?= $heatmap_locations['stats']['center']['lng'] ?>
    ];
    
    // Criar o mapa com Leaflet
    map = L.map('heatmap').setView(mapCenter, 10);
    
    // Adicionar camada do OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Preparar dados para o heatmap
    const heatPoints = heatmapData.points.map(point => [
        point.lat, 
        point.lng, 
        0.8 // Intensidade
    ]);
    
    // Criar camada de heatmap
    heatLayer = L.heatLayer(heatPoints, {
        radius: 25,
        blur: 15,
        maxZoom: 17,
        gradient: {
            0.0: '#3182bd',
            0.2: '#6baed6', 
            0.4: '#9ecae1',
            0.6: '#c6dbef',
            0.8: '#fd8d3c',
            1.0: '#e6550d'
        }
    }).addTo(map);
    
    // Criar camada de marcadores
    markersLayer = L.layerGroup();
    
    // Adicionar marcadores
    heatmapData.points.forEach((point, index) => {
        // Criar ícone personalizado
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="marker-pin">
                      <i class="fas fa-map-marker-alt"></i>
                   </div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        
        const marker = L.marker([point.lat, point.lng], { icon: customIcon });
        
        // Popup com informações
        const popupContent = `
            <div style="max-width: 250px;">
                <h6 class="mb-2"><strong>Resposta #${point.info.id}</strong></h6>
                <p class="mb-1"><strong>Questionário:</strong> ${point.info.questionnaire_title || 'N/A'}</p>
                <p class="mb-1"><strong>Aplicador:</strong> ${point.info.applied_by_name || 'N/A'}</p>
                ${point.info.respondent_name ? `<p class="mb-1"><strong>Respondente:</strong> ${point.info.respondent_name}</p>` : ''}
                ${point.info.location_name ? `<p class="mb-1"><strong>Local:</strong> ${point.info.location_name}</p>` : ''}
                <p class="mb-1"><strong>Data:</strong> ${new Date(point.info.completed_at).toLocaleDateString('pt-BR')}</p>
                <div class="d-flex gap-1 mt-2">
                    <span class="badge ${point.info.consent_given ? 'bg-success' : 'bg-warning'}" style="font-size: 0.7rem;">
                        ${point.info.consent_given ? 'Com consentimento' : 'Sem consentimento'}
                    </span>
                    ${point.info.has_photo ? '<span class="badge bg-info" style="font-size: 0.7rem;">Com foto</span>' : ''}
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        markersLayer.addLayer(marker);
    });
    
    // Ajustar zoom para mostrar todos os pontos
    <?php if ($heatmap_locations['stats']['bounds']): ?>
    const bounds = L.latLngBounds([
        [<?= $heatmap_locations['stats']['bounds']['southwest']['lat'] ?>, 
         <?= $heatmap_locations['stats']['bounds']['southwest']['lng'] ?>],
        [<?= $heatmap_locations['stats']['bounds']['northeast']['lat'] ?>, 
         <?= $heatmap_locations['stats']['bounds']['northeast']['lng'] ?>]
    ]);
    map.fitBounds(bounds, { padding: [20, 20] });
    <?php endif; ?>
    
    <?php else: ?>
    // Sem dados - mostrar mapa padrão do Brasil
    map = L.map('heatmap').setView([-15.7942, -47.8822], 4);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    <?php endif; ?>
}

function toggleHeatmapView() {
    if (!map || !heatmapData.points.length) return;
    
    const button = document.getElementById('heatmapToggle');
    
    if (isHeatmapView) {
        // Mostrar marcadores
        map.removeLayer(heatLayer);
        map.addLayer(markersLayer);
        button.innerHTML = '<i class="fas fa-fire me-1"></i>Calor';
        isHeatmapView = false;
    } else {
        // Mostrar heatmap
        map.removeLayer(markersLayer);
        map.addLayer(heatLayer);
        button.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i>Pontos';
        isHeatmapView = true;
    }
}

// Inicializar mapa quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    initLeafletMap();
});

</script>