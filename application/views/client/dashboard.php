<style>
    .client-welcome {
        background: linear-gradient(135deg, rgba(143,174,93,0.1), rgba(35,52,95,0.05));
        border: 1px solid rgba(143,174,93,0.2);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .analysis-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-bottom: 1rem;
    }
    .analysis-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }
    .analysis-card .card-top {
        padding: 1.25rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .analysis-card .card-bottom {
        padding: 0.75rem 1.25rem;
        background: #fafbfc;
    }
    .type-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        color: white; font-size: 1.1rem; flex-shrink: 0;
    }
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-chart-pie me-2" style="color: var(--primary-color);"></i>Painel de Análises</h2>
        <small class="text-muted">Visualize os resultados das análises estatísticas</small>
    </div>
</div>

<div class="client-welcome">
    <div class="d-flex align-items-center">
        <i class="fas fa-user-circle me-3" style="font-size: 2rem; color: var(--primary-color);"></i>
        <div>
            <h5 class="mb-1" style="color: var(--secondary-color);">Bem-vindo(a), <?= htmlspecialchars($this->session->userdata('admin_name')) ?>!</h5>
            <p class="mb-0 text-muted">Aqui você encontra os gráficos e análises gerados a partir dos dados coletados.</p>
        </div>
    </div>
</div>

<?php if (empty($analyses)): ?>
    <div class="empty-state">
        <i class="fas fa-chart-bar d-block"></i>
        <h5>Nenhuma análise disponível</h5>
        <p>Ainda não há análises estatísticas geradas. Aguarde o administrador gerar as análises.</p>
    </div>
<?php else: ?>
    <div class="row">
        <?php
            $typeLabels = array('statistical' => 'Estatística', 'sentiment' => 'Sentimento', 'correlation' => 'Correlação', 'general' => 'Geral');
            $typeIcons = array('statistical' => 'fa-chart-bar', 'sentiment' => 'fa-heart', 'correlation' => 'fa-project-diagram', 'general' => 'fa-chart-line');
            $typeColors = array('statistical' => '#8fae5d', 'sentiment' => '#e74c3c', 'correlation' => '#3498db', 'general' => '#f39c12');
        ?>
        <?php foreach ($analyses as $a):
            $a = (object)$a;
            $type = $a->analysis_type ?? 'general';
            $chart_suggestions = json_decode($a->chart_suggestions ?? '[]', true);
            $chart_count = is_array($chart_suggestions) ? count($chart_suggestions) : 0;
        ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= base_url('client/view_analysis/' . $a->id) ?>" class="text-decoration-none">
                <div class="analysis-card">
                    <div class="card-top">
                        <div class="d-flex align-items-start">
                            <div class="type-icon me-3" style="background: linear-gradient(135deg, <?= $typeColors[$type] ?? '#8fae5d' ?>, <?= $typeColors[$type] ?? '#6d8a45' ?>cc);">
                                <i class="fas <?= $typeIcons[$type] ?? 'fa-chart-line' ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-1" style="color: var(--secondary-color);">
                                    <?= htmlspecialchars($a->questionnaire_title ?? 'Análise #' . $a->id) ?>
                                </h6>
                                <span class="badge" style="background: <?= $typeColors[$type] ?? '#8fae5d' ?>; color: white;">
                                    <?= $typeLabels[$type] ?? ucfirst($type) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-bottom d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($a->created_at)) ?>
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-chart-pie me-1"></i><?= $chart_count ?> gráfico(s)
                        </small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
