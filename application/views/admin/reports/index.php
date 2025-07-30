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
<!-- Substituir a seção do mapa de calor na view index.php -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mapa de Calor - Localizações</h5>
        <div>
            <span class="badge bg-info me-2">
                <i class="fas fa-fire me-1"></i>
                <?= isset($heatmap_locations['stats']['total_locations']) ? $heatmap_locations['stats']['total_locations'] : 0 ?> pontos de calor
            </span>
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleHeatmapIntensity()" id="intensityToggle">
                <i class="fas fa-adjust me-1"></i>
                Intensidade
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
                            // Ajustar zoom para mostrar todos os pontos com zoom mínimo para contexto
    <?php if ($heatmap_locations['stats']['bounds']): ?>
    const bounds = L.latLngBounds([
        [<?= $heatmap_locations['stats']['bounds']['southwest']['lat'] ?>, 
         <?= $heatmap_locations['stats']['bounds']['southwest']['lng'] ?>],
        [<?= $heatmap_locations['stats']['bounds']['northeast']['lat'] ?>, 
         <?= $heatmap_locations['stats']['bounds']['northeast']['lng'] ?>]
    ]);
    
    // Garantir zoom mínimo para mostrar contexto geográfico
    map.fitBounds(bounds, { 
        padding: [30, 30],
        maxZoom: 12  // Limitar zoom máximo para não ficar muito próximo
    });
    
    // Se a área for muito pequena, mostrar mais contexto
    const currentZoom = map.getZoom();
    if (currentZoom > 12) {
        map.setZoom(10);
    }
    <?php else: ?>
    // Fallback: mostrar região central do Brasil
    map.setView([-15.7942, -47.8822], 6);
    <?php endif; ?>
    
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

/* Estilos para o mapa Leaflet */
#heatmap {
    border-radius: 0;
    border: none;
}

/* Controles do mapa */
.leaflet-control-zoom a {
    background-color: #8fae5d !important;
    border-color: #8fae5d !important;
}

.leaflet-control-zoom a:hover {
    background-color: #7a9851 !important;
}

/* Tooltip personalizado para informações do heatmap */
.heatmap-info {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: rgba(255, 255, 255, 0.9);
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 12px;
    max-width: 200px;
}

/* Legenda do heatmap com cores mais escuras */
.heatmap-legend {
    background: linear-gradient(to right, 
        rgba(0, 0, 139, 0.7) 0%,      /* Azul escuro */
        rgba(0, 100, 255, 0.8) 20%,   /* Azul médio */
        rgba(0, 200, 255, 0.85) 40%,  /* Ciano */
        rgba(255, 200, 0, 0.9) 70%,   /* Amarelo/laranja */
        rgba(255, 100, 0, 0.95) 85%,  /* Laranja escuro */
        rgba(139, 0, 0, 1.0) 100%);   /* Vermelho escuro */
    height: 10px;
    width: 100px;
    border: 1px solid #ccc;
    margin: 5px 0;
}

/* Responsividade */
@media (max-width: 768px) {
    .heatmap-info {
        bottom: 10px;
        left: 10px;
        font-size: 11px;
        max-width: 150px;
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
let currentIntensity = 'high';

function initLeafletMap() {
    <?php if (!empty($heatmap_locations['points'])): ?>
    
    // Configurar o centro do mapa
    const mapCenter = [
        <?= $heatmap_locations['stats']['center']['lat'] ?>,
        <?= $heatmap_locations['stats']['center']['lng'] ?>
    ];
    
    // Criar o mapa com Leaflet
    map = L.map('heatmap').setView(mapCenter, 4);
    
    // Adicionar camada do OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Preparar dados para o heatmap com intensidade máxima
    const heatPoints = heatmapData.points.map(point => [
        point.lat, 
        point.lng, 
        1.5 // Intensidade aumentada para cores mais fortes
    ]);
    
    // Criar camada de heatmap com cores mais escuras e visíveis
    heatLayer = L.heatLayer(heatPoints, {
        radius: 50,
        blur: 20,
        maxZoom: 17,
        max: 0.8,
        minOpacity: 0.6,
        gradient: {
            0.0: 'rgba(0, 0, 139, 0)',      // Azul escuro transparente
            0.1: 'rgba(0, 0, 139, 0.7)',    // Azul escuro
            0.3: 'rgba(0, 100, 255, 0.8)',  // Azul médio
            0.5: 'rgba(0, 200, 255, 0.85)', // Ciano
            0.7: 'rgba(255, 200, 0, 0.9)',  // Amarelo/laranja
            0.9: 'rgba(255, 100, 0, 0.95)', // Laranja escuro
            1.0: 'rgba(139, 0, 0, 1.0)'     // Vermelho escuro
        }
    }).addTo(map);
    
    <?php else: ?>
    // Sem dados - mostrar mapa completo do Brasil
    map = L.map('heatmap').setView([-14.2350, -51.9253], 4);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Definir bounds do Brasil para mostrar todo o país
    const brasilBounds = L.latLngBounds(
        [-33.7683777, -73.9872354],  // Sudoeste (Rio Grande do Sul)
        [5.2717863, -28.847770]     // Nordeste (Roraima)
    );
    map.fitBounds(brasilBounds, { padding: [20, 20] });
    <?php endif; ?>
}

function toggleHeatmapIntensity() {
    if (!map || !heatmapData.points.length) return;
    
    const button = document.getElementById('intensityToggle');
    
    // Remover layer atual
    map.removeLayer(heatLayer);
    
    // Preparar dados com nova intensidade
    let heatPoints;
    
    if (currentIntensity === 'high') {
        // Intensidade média
        heatPoints = heatmapData.points.map(point => [point.lat, point.lng, 1.0]);
        heatLayer = L.heatLayer(heatPoints, {
            radius: 35,
            blur: 15,
            maxZoom: 17,
            max: 0.6,
            minOpacity: 0.4,
            gradient: {
                0.0: 'rgba(0, 0, 139, 0)',
                0.2: 'rgba(0, 0, 139, 0.5)',
                0.4: 'rgba(0, 100, 255, 0.6)',
                0.6: 'rgba(0, 200, 255, 0.7)',
                0.8: 'rgba(255, 200, 0, 0.75)',
                1.0: 'rgba(255, 100, 0, 0.8)'
            }
        });
        button.innerHTML = '<i class="fas fa-adjust me-1"></i>Média';
        currentIntensity = 'medium';
        
    } else if (currentIntensity === 'medium') {
        // Intensidade baixa
        heatPoints = heatmapData.points.map(point => [point.lat, point.lng, 0.7]);
        heatLayer = L.heatLayer(heatPoints, {
            radius: 25,
            blur: 12,
            maxZoom: 17,
            max: 0.4,
            minOpacity: 0.3,
            gradient: {
                0.0: 'rgba(0, 0, 139, 0)',
                0.3: 'rgba(0, 0, 139, 0.3)',
                0.5: 'rgba(0, 100, 255, 0.4)',
                0.7: 'rgba(0, 200, 255, 0.5)',
                0.9: 'rgba(255, 200, 0, 0.6)',
                1.0: 'rgba(255, 100, 0, 0.65)'
            }
        });
        button.innerHTML = '<i class="fas fa-adjust me-1"></i>Baixa';
        currentIntensity = 'low';
        
    } else {
        // Volta para intensidade alta (mais escura)
        heatPoints = heatmapData.points.map(point => [point.lat, point.lng, 1.5]);
        heatLayer = L.heatLayer(heatPoints, {
            radius: 50,
            blur: 20,
            maxZoom: 17,
            max: 0.8,
            minOpacity: 0.6,
            gradient: {
                0.0: 'rgba(0, 0, 139, 0)',
                0.1: 'rgba(0, 0, 139, 0.7)',
                0.3: 'rgba(0, 100, 255, 0.8)',
                0.5: 'rgba(0, 200, 255, 0.85)',
                0.7: 'rgba(255, 200, 0, 0.9)',
                0.9: 'rgba(255, 100, 0, 0.95)',
                1.0: 'rgba(139, 0, 0, 1.0)'
            }
        });
        button.innerHTML = '<i class="fas fa-adjust me-1"></i>Alta';
        currentIntensity = 'high';
    }
    
    // Adicionar nova layer
    heatLayer.addTo(map);
}

// Inicializar mapa quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    initLeafletMap();
});

</script>