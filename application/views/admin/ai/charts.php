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
    .btn-ai-primary {
        background: linear-gradient(135deg, #8fae5d, #6d8a45);
        border: none; color: white; font-weight: 500;
    }
    .btn-ai-primary:hover { opacity: 0.9; color: white; }
    .btn-ai-primary:disabled { opacity: 0.6; color: white; }
    .empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }
    .loading-overlay { display: none; text-align: center; padding: 2rem; }
    .loading-overlay.active { display: block; }
    .chart-wrapper {
        background: white; border: 1px solid #e9ecef; border-radius: 0.75rem;
        padding: 1.25rem; margin-bottom: 1.5rem;
    }
    .chart-wrapper .chart-title {
        font-weight: 600; color: var(--secondary-color); margin-bottom: 0.75rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    .chart-container { position: relative; height: 350px; }
    .chart-type-selector {
        font-size: 0.8rem; padding: 0.2rem 0.5rem;
        border: 1px solid #dee2e6; border-radius: 0.25rem;
        background: white; color: var(--secondary-color);
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Gráficos Inteligentes</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-chart-pie me-2" style="color: var(--primary-color);"></i>Gráficos Inteligentes</h2>
    </div>
    <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!$is_enabled): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Atenção:</strong> Este recurso está desativado. Ative-o nas <a href="<?= base_url('ai/settings') ?>">configurações</a>.
</div>
<?php endif; ?>

<!-- Formulário -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-cogs me-2"></i>Gerar Gráficos</h5>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="questionnaire_id" class="form-label fw-bold" style="color: var(--secondary-color);">Questionário</label>
                <select class="form-select" id="questionnaire_id" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="">Selecione um questionário...</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= $q->id ?>"><?= htmlspecialchars($q->title ?? $q->name ?? 'Questionário #' . $q->id) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label fw-bold" style="color: var(--secondary-color);">Data Início</label>
                <input type="date" class="form-control" id="date_from" <?= !$is_enabled ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label fw-bold" style="color: var(--secondary-color);">Data Fim</label>
                <input type="date" class="form-control" id="date_to" <?= !$is_enabled ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-ai-primary w-100" id="btnGenerate" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-bolt me-1"></i>Gerar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading -->
<div class="loading-overlay" id="loadingIndicator">
    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Carregando...</span>
    </div>
    <p class="text-muted">Analisando dados e gerando gráficos inteligentes...</p>
</div>

<!-- Container de Gráficos -->
<div id="chartsContainer">
    <div class="empty-state" id="emptyState">
        <i class="fas fa-chart-bar d-block"></i>
        <h5>Nenhum gráfico gerado</h5>
        <p>Selecione um questionário e clique em "Gerar" para visualizar gráficos sugeridos pela IA.</p>
    </div>
</div>

<script>
var chartInstances = [];

var colorPalette = [
    'rgba(143, 174, 93, 0.7)', 'rgba(35, 52, 95, 0.7)', 'rgba(91, 192, 222, 0.7)',
    'rgba(240, 173, 78, 0.7)', 'rgba(217, 83, 79, 0.7)', 'rgba(111, 66, 193, 0.7)',
    'rgba(32, 201, 151, 0.7)', 'rgba(253, 126, 20, 0.7)', 'rgba(102, 16, 242, 0.7)',
    'rgba(13, 110, 253, 0.7)'
];
var borderPalette = [
    'rgba(143, 174, 93, 1)', 'rgba(35, 52, 95, 1)', 'rgba(91, 192, 222, 1)',
    'rgba(240, 173, 78, 1)', 'rgba(217, 83, 79, 1)', 'rgba(111, 66, 193, 1)',
    'rgba(32, 201, 151, 1)', 'rgba(253, 126, 20, 1)', 'rgba(102, 16, 242, 1)',
    'rgba(13, 110, 253, 1)'
];

document.getElementById('btnGenerate').addEventListener('click', function() {
    var qId = document.getElementById('questionnaire_id').value;
    if (!qId) { showToast('Selecione um questionário.', 'warning'); return; }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';
    document.getElementById('loadingIndicator').classList.add('active');
    document.getElementById('emptyState').style.display = 'none';

    // Destroy existing charts
    chartInstances.forEach(function(c) { c.destroy(); });
    chartInstances = [];

    $.ajax({
        url: '<?= base_url("ai/generate_charts") ?>',
        type: 'POST',
        data: {
            questionnaire_id: qId,
            date_from: document.getElementById('date_from').value,
            date_to: document.getElementById('date_to').value
        },
        dataType: 'json',
        timeout: 120000,
        success: function(data) {
            if (data.success && data.charts && data.charts.length > 0) {
                renderCharts(data.charts);
                showToast('Gráficos gerados com sucesso!', 'success');
            } else if (data.success && (!data.charts || data.charts.length === 0)) {
                document.getElementById('emptyState').style.display = 'block';
                showToast('Nenhum gráfico sugerido para estes dados.', 'warning');
            } else {
                document.getElementById('emptyState').style.display = 'block';
                showToast(data.message || 'Erro ao gerar gráficos.', 'danger');
            }
        },
        error: function(xhr) {
            document.getElementById('emptyState').style.display = 'block';
            if (xhr.statusText === 'timeout') {
                showToast('A geração está demorando mais que o esperado.', 'warning');
            } else {
                showToast('Erro de rede. Tente novamente.', 'danger');
            }
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function renderCharts(charts) {
    var container = document.getElementById('chartsContainer');
    container.innerHTML = '<div class="row" id="chartsRow"></div>';
    var row = document.getElementById('chartsRow');

    charts.forEach(function(chart, index) {
        var config = chart.data_config || chart.config || chart;
        var chartType = chart.chart_type || config.type || 'bar';
        var title = chart.title || 'Gráfico ' + (index + 1);
        var labels = config.labels || [];
        var datasets = config.datasets || [];

        if (datasets.length === 0 && config.data) {
            datasets = [{
                label: title,
                data: config.data,
                backgroundColor: colorPalette.slice(0, (config.data || []).length),
                borderColor: borderPalette.slice(0, (config.data || []).length),
                borderWidth: 1
            }];
        }

        datasets.forEach(function(ds, i) {
            if (!ds.backgroundColor) {
                if (['pie', 'doughnut'].indexOf(chartType) >= 0) {
                    ds.backgroundColor = colorPalette.slice(0, (ds.data || []).length);
                    ds.borderColor = borderPalette.slice(0, (ds.data || []).length);
                } else {
                    ds.backgroundColor = colorPalette[i % colorPalette.length];
                    ds.borderColor = borderPalette[i % borderPalette.length];
                }
                ds.borderWidth = 1;
            }
        });

        var colSize = charts.length === 1 ? 'col-12' : 'col-md-6';
        var wrapper = document.createElement('div');
        wrapper.className = colSize;
        wrapper.innerHTML =
            '<div class="chart-wrapper">' +
                '<div class="chart-title">' +
                    '<span><i class="fas fa-chart-bar me-2" style="color: var(--primary-color);"></i>' + escapeHtml(title) + '</span>' +
                    '<select class="chart-type-selector" data-chart-index="' + index + '" onchange="changeChartType(' + index + ', this.value)">' +
                        '<option value="bar"' + (chartType === 'bar' ? ' selected' : '') + '>Barras</option>' +
                        '<option value="line"' + (chartType === 'line' ? ' selected' : '') + '>Linha</option>' +
                        '<option value="pie"' + (chartType === 'pie' ? ' selected' : '') + '>Pizza</option>' +
                        '<option value="doughnut"' + (chartType === 'doughnut' ? ' selected' : '') + '>Rosca</option>' +
                        '<option value="radar"' + (chartType === 'radar' ? ' selected' : '') + '>Radar</option>' +
                    '</select>' +
                '</div>' +
                '<div class="chart-container">' +
                    '<canvas id="ai-chart-' + index + '"></canvas>' +
                '</div>' +
            '</div>';
        row.appendChild(wrapper);

        var canvas = document.getElementById('ai-chart-' + index);
        var instance = new Chart(canvas, {
            type: chartType,
            data: { labels: labels, datasets: JSON.parse(JSON.stringify(datasets)) },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        chartInstances[index] = instance;
        // Store original data for chart type switching
        canvas.dataset.originalLabels = JSON.stringify(labels);
        canvas.dataset.originalDatasets = JSON.stringify(datasets);
    });
}

function changeChartType(index, newType) {
    var instance = chartInstances[index];
    if (!instance) return;

    var canvas = document.getElementById('ai-chart-' + index);
    var labels = JSON.parse(canvas.dataset.originalLabels);
    var datasets = JSON.parse(canvas.dataset.originalDatasets);

    // Update colors based on chart type
    datasets.forEach(function(ds, i) {
        if (['pie', 'doughnut'].indexOf(newType) >= 0) {
            ds.backgroundColor = colorPalette.slice(0, (ds.data || []).length);
            ds.borderColor = borderPalette.slice(0, (ds.data || []).length);
        } else {
            ds.backgroundColor = colorPalette[i % colorPalette.length];
            ds.borderColor = borderPalette[i % borderPalette.length];
        }
    });

    instance.destroy();
    chartInstances[index] = new Chart(canvas, {
        type: newType,
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function showToast(message, type) {
    var existing = document.getElementById('aiToast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.id = 'aiToast';
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;';
    toast.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show border-0 shadow-lg mb-0" role="alert">' +
        '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') + ' me-2"></i>' +
        message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 4000);
}
</script>
