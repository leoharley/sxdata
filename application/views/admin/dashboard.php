<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <div class="text-muted">
                <i class="fas fa-calendar-day me-1"></i>
                <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #8fae5d, #a8c46a);">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="stat-number"><?= $stats['total_questionnaires'] ?></h3>
            <p class="stat-label">Total Questionários</p>
            <small class="text-success">
                <i class="fas fa-check-circle me-1"></i>
                <?= $stats['active_questionnaires'] ?> ativos
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="stat-number"><?= $stats['total_responses'] ?></h3>
            <p class="stat-label">Total Respostas</p>
            <small class="text-primary">
                <i class="fas fa-plus-circle me-1"></i>
                <?= $stats['responses_today'] ?> hoje
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                <i class="fas fa-sync-alt"></i>
            </div>
            <h3 class="stat-number"><?= $stats['pending_sync'] ?></h3>
            <p class="stat-label">Pendentes Sync</p>
            <small class="text-warning">
                <i class="fas fa-clock me-1"></i>
                Aguardando
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #6c757d, #545b62);">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="stat-number"><?= $stats['total_users'] ?></h3>
            <p class="stat-label">Usuários Ativos</p>
            <small class="text-info">
                <i class="fas fa-user-plus me-1"></i>
                Sistema
            </small>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Respostas por Dia (Últimos 30 dias)</h5>
            </div>
            <div class="card-body">
                <canvas id="responsesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status de Sincronização</h5>
            </div>
            <div class="card-body">
                <canvas id="syncChart" width="200" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Respostas Recentes</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_responses)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Questionário</th>
                                    <th>Aplicador</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_responses as $response): ?>
                                <tr>
                                    <td>
                                        <strong><?= $response->questionnaire_title ?></strong>
                                    </td>
                                    <td><?= $response->applied_by_name ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($response->completed_at)) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = $response->sync_status == 'synced' ? 'success' : 'warning';
                                        $status_text = $response->sync_status == 'synced' ? 'Sincronizado' : 'Pendente';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('responses/view/' . $response->id) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma resposta encontrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Questionários Mais Utilizados</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($charts['questionnaires_usage'])): ?>
                    <?php foreach ($charts['questionnaires_usage'] as $usage): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1"><?= character_limiter($usage->title, 30) ?></h6>
                                <small class="text-muted"><?= $usage->response_count ?> respostas</small>
                            </div>
                            <div class="text-end">
                                <div class="progress" style="width: 80px; height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= min(100, ($usage->response_count / max(1, $charts['questionnaires_usage'][0]->response_count)) * 100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Sem dados para exibir</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de Respostas por Dia
const responsesCtx = document.getElementById('responsesChart').getContext('2d');
const responsesChart = new Chart(responsesCtx, {
    type: 'line',
    data: {
        labels: [<?php 
            echo '"' . implode('", "', array_map(function($item) {
                return date('d/m', strtotime($item->date));
            }, $charts['responses_by_day'])) . '"';
        ?>],
        datasets: [{
            label: 'Respostas',
            data: [<?= implode(', ', array_column($charts['responses_by_day'], 'count')) ?>],
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
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Gráfico de Status de Sincronização
const syncCtx = document.getElementById('syncChart').getContext('2d');
const syncChart = new Chart(syncCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            echo '"' . implode('", "', array_map(function($item) {
                return ucfirst($item->sync_status);
            }, $charts['sync_status'])) . '"';
        ?>],
        datasets: [{
            data: [<?= implode(', ', array_column($charts['sync_status'], 'count')) ?>],
            backgroundColor: [
                '#8fae5d',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>