<style>
    .ai-page-card {
        background: white; border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;
    }
    .ai-page-card .card-header {
        background: white; border-bottom: 2px solid var(--primary-color);
        padding: 1rem 1.25rem;
    }
    .ai-page-card .card-header h5 { margin: 0; color: var(--secondary-color); font-weight: 600; }
    .breadcrumb { background: transparent; padding: 0; margin: 0; }
    .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
    .breadcrumb-item.active { color: var(--secondary-color); }
    .section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        color: white; font-size: 1rem; margin-right: 0.75rem; flex-shrink: 0;
    }
    .analysis-list-item {
        padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;
    }
    .analysis-list-item:last-child { border-bottom: none; }
    .chart-container {
        position: relative; height: 300px; margin-bottom: 1rem;
    }
    .summary-card {
        background: linear-gradient(135deg, rgba(143,174,93,0.08), rgba(35,52,95,0.05));
        border: 1px solid rgba(143,174,93,0.2); border-radius: 0.75rem;
        padding: 1.5rem;
    }
</style>

<?php
    $patterns = is_string($analysis->patterns ?? '') ? json_decode($analysis->patterns, true) : ($analysis->patterns ?? []);
    $outliers = is_string($analysis->outliers ?? '') ? json_decode($analysis->outliers, true) : ($analysis->outliers ?? []);
    $trends = is_string($analysis->trends ?? '') ? json_decode($analysis->trends, true) : ($analysis->trends ?? []);
    $insights = is_string($analysis->insights ?? '') ? json_decode($analysis->insights, true) : ($analysis->insights ?? []);
    $chart_suggestions = is_string($analysis->chart_suggestions ?? '') ? json_decode($analysis->chart_suggestions, true) : ($analysis->chart_suggestions ?? []);
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('ai/analysis') ?>">Análise Estatística</a></li>
                <li class="breadcrumb-item active">Visualizar</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="fas fa-chart-line me-2" style="color: var(--primary-color);"></i>
            <?= htmlspecialchars($analysis->questionnaire_title ?? 'Análise #' . $analysis->id) ?>
        </h2>
        <small class="text-muted">
            <?php
                $typeLabels = ['statistical' => 'Estatística', 'sentiment' => 'Sentimento', 'correlation' => 'Correlação', 'general' => 'Geral'];
            ?>
            Tipo: <?= $typeLabels[$analysis->analysis_type] ?? ucfirst($analysis->analysis_type ?? '') ?>
            | Gerada em: <?= date('d/m/Y H:i', strtotime($analysis->created_at)) ?>
        </small>
    </div>
    <a href="<?= base_url('ai/analysis') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<!-- Resumo -->
<div class="summary-card mb-4">
    <h5 style="color: var(--secondary-color); font-weight: 600;">
        <i class="fas fa-file-alt me-2" style="color: var(--primary-color);"></i>Resumo
    </h5>
    <?php
        $summary_raw = $analysis->summary_text ?? '';
        $summary_decoded = json_decode($summary_raw, true);
    ?>
    <?php if (is_array($summary_decoded)): ?>
        <div class="row g-3 mt-1">
        <?php foreach ($summary_decoded as $key => $value): ?>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded border">
                    <small class="text-muted d-block mb-1" style="text-transform: capitalize;">
                        <?= htmlspecialchars(str_replace('_', ' ', $key)) ?>
                    </small>
                    <?php if (is_array($value)): ?>
                        <ul class="mb-0 ps-3 small">
                        <?php foreach ($value as $k => $v): ?>
                            <li><?= htmlspecialchars(is_string($k) ? str_replace('_', ' ', $k) . ': ' : '') ?><strong><?= htmlspecialchars(is_scalar($v) ? $v : json_encode($v)) ?></strong></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <strong style="font-size: 1.4rem; color: var(--secondary-color);"><?= htmlspecialchars($value) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="mb-0" style="font-size: 1.05rem; line-height: 1.7; color: #333;">
            <?= nl2br(htmlspecialchars($summary_raw)) ?>
        </p>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Padrões -->
    <?php if (!empty($patterns)): ?>
    <div class="col-md-6">
        <div class="ai-page-card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="section-icon" style="background: linear-gradient(135deg, #8fae5d, #6d8a45);">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <h5>Padrões Identificados</h5>
                </div>
            </div>
            <div class="card-body p-3">
                <?php foreach ($patterns as $item): ?>
                <div class="analysis-list-item">
                    <?php if (is_array($item)): ?>
                        <strong style="color: var(--secondary-color);"><?= htmlspecialchars($item['title'] ?? $item['name'] ?? '') ?></strong>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($item['description'] ?? $item['text'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="mb-0"><i class="fas fa-check-circle me-2" style="color: var(--primary-color);"></i><?= htmlspecialchars($item) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Outliers -->
    <?php if (!empty($outliers)): ?>
    <div class="col-md-6">
        <div class="ai-page-card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f0ad4e, #ec971f);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Outliers</h5>
                </div>
            </div>
            <div class="card-body p-3">
                <?php foreach ($outliers as $item): ?>
                <div class="analysis-list-item">
                    <?php if (is_array($item)): ?>
                        <strong style="color: var(--secondary-color);"><?= htmlspecialchars($item['title'] ?? $item['name'] ?? '') ?></strong>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($item['description'] ?? $item['text'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="mb-0"><i class="fas fa-exclamation me-2 text-warning"></i><?= htmlspecialchars($item) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tendências -->
    <?php if (!empty($trends)): ?>
    <div class="col-md-6">
        <div class="ai-page-card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="section-icon" style="background: linear-gradient(135deg, #5bc0de, #46b8da);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Tendências</h5>
                </div>
            </div>
            <div class="card-body p-3">
                <?php foreach ($trends as $item): ?>
                <div class="analysis-list-item">
                    <?php if (is_array($item)): ?>
                        <strong style="color: var(--secondary-color);"><?= htmlspecialchars($item['title'] ?? $item['name'] ?? '') ?></strong>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($item['description'] ?? $item['text'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="mb-0"><i class="fas fa-arrow-trend-up me-2 text-info"></i><?= htmlspecialchars($item) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Insights -->
    <?php if (!empty($insights)): ?>
    <div class="col-md-6">
        <div class="ai-page-card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="section-icon" style="background: linear-gradient(135deg, #23345F, #1a2847);">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h5>Insights</h5>
                </div>
            </div>
            <div class="card-body p-3">
                <?php foreach ($insights as $item): ?>
                <div class="analysis-list-item">
                    <?php if (is_array($item)): ?>
                        <strong style="color: var(--secondary-color);"><?= htmlspecialchars($item['title'] ?? $item['name'] ?? '') ?></strong>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($item['description'] ?? $item['text'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="mb-0"><i class="fas fa-lightbulb me-2" style="color: var(--primary-color);"></i><?= htmlspecialchars($item) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Gráficos Sugeridos -->
<?php if (!empty($chart_suggestions)): ?>
<div class="ai-page-card">
    <div class="card-header">
        <div class="d-flex align-items-center">
            <div class="section-icon" style="background: linear-gradient(135deg, #8fae5d, #6d8a45);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h5>Gráficos Sugeridos</h5>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row">
            <?php foreach ($chart_suggestions as $index => $chart): ?>
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3">
                    <h6 class="mb-3" style="color: var(--secondary-color);">
                        <?= htmlspecialchars($chart['title'] ?? 'Gráfico ' . ($index + 1)) ?>
                    </h6>
                    <div class="chart-container">
                        <canvas id="chart-<?= $index ?>"></canvas>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!empty($chart_suggestions)): ?>
document.addEventListener('DOMContentLoaded', function() {
    var chartSuggestions = <?= json_encode($chart_suggestions) ?>;

    var colorPalette = [
        'rgba(143, 174, 93, 0.7)', 'rgba(35, 52, 95, 0.7)', 'rgba(91, 192, 222, 0.7)',
        'rgba(240, 173, 78, 0.7)', 'rgba(217, 83, 79, 0.7)', 'rgba(111, 66, 193, 0.7)',
        'rgba(32, 201, 151, 0.7)', 'rgba(253, 126, 20, 0.7)'
    ];
    var borderPalette = [
        'rgba(143, 174, 93, 1)', 'rgba(35, 52, 95, 1)', 'rgba(91, 192, 222, 1)',
        'rgba(240, 173, 78, 1)', 'rgba(217, 83, 79, 1)', 'rgba(111, 66, 193, 1)',
        'rgba(32, 201, 151, 1)', 'rgba(253, 126, 20, 1)'
    ];

    chartSuggestions.forEach(function(chart, index) {
        var canvas = document.getElementById('chart-' + index);
        if (!canvas) return;

        var config = chart.data_config || chart.config || chart;
        var chartType = chart.chart_type || config.type || 'bar';
        var labels = config.labels || [];
        var datasets = config.datasets || [];

        if (datasets.length === 0 && config.data) {
            datasets = [{
                label: chart.title || 'Dados',
                data: config.data,
                backgroundColor: colorPalette.slice(0, (config.data || []).length),
                borderColor: borderPalette.slice(0, (config.data || []).length),
                borderWidth: 1
            }];
        }

        datasets.forEach(function(ds, i) {
            if (!ds.backgroundColor) {
                ds.backgroundColor = colorPalette[i % colorPalette.length];
                ds.borderColor = borderPalette[i % borderPalette.length];
                ds.borderWidth = 1;
            }
        });

        new Chart(canvas, {
            type: chartType,
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });
});
<?php endif; ?>
</script>
