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
    .table th {
        color: var(--secondary-color); font-weight: 600; font-size: 0.85rem;
        text-transform: uppercase;
    }
    .status-draft { background: #6c757d; }
    .status-generating { background: #f0ad4e; }
    .status-completed { background: #8fae5d; }
    .status-error { background: #dc3545; }
    .report-type-badge {
        font-size: 0.75rem; padding: 0.25em 0.65em; border-radius: 0.35rem;
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Relatórios com IA</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-file-alt me-2" style="color: var(--primary-color);"></i>Relatórios com IA</h2>
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
        <h5><i class="fas fa-plus-circle me-2"></i>Gerar Novo Relatório</h5>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="questionnaire_id" class="form-label fw-bold" style="color: var(--secondary-color);">Questionário</label>
                <select class="form-select" id="questionnaire_id" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="">Selecione...</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= $q->id ?>"><?= htmlspecialchars($q->title ?? $q->name ?? 'Questionário #' . $q->id) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label fw-bold" style="color: var(--secondary-color);">Tipo de Relatório</label>
                <select class="form-select" id="report_type" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="general">Geral</option>
                    <option value="executive">Executivo</option>
                    <option value="detailed">Detalhado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label fw-bold" style="color: var(--secondary-color);">Data Início</label>
                <input type="date" class="form-control" id="date_from" <?= !$is_enabled ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-2">
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
    <p class="text-muted">Gerando relatório narrativo. Isso pode levar alguns minutos...</p>
</div>

<!-- Lista de Relatórios -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Relatórios Gerados</h5>
        <span class="badge bg-secondary"><?= count($reports) ?> relatório(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($reports)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt d-block"></i>
                <h5>Nenhum relatório encontrado</h5>
                <p>Selecione um questionário e tipo para gerar o primeiro relatório.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Questionário</th>
                            <th>Tipo</th>
                            <th>Gerado por</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
                            <td><small><?= htmlspecialchars($r['questionnaire_title']) ?></small></td>
                            <td>
                                <?php
                                    $typeLabels = ['general' => 'Geral', 'executive' => 'Executivo', 'detailed' => 'Detalhado'];
                                    $typeColors = ['general' => 'bg-secondary', 'executive' => 'bg-primary', 'detailed' => 'bg-info'];
                                ?>
                                <span class="badge report-type-badge <?= $typeColors[$r['report_type']] ?? 'bg-secondary' ?>">
                                    <?= $typeLabels[$r['report_type']] ?? ucfirst($r['report_type']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($r['generated_by_name'] ?? 'Sistema') ?></small></td>
                            <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small></td>
                            <td>
                                <?php
                                    $statusLabels = ['draft' => 'Rascunho', 'generating' => 'Gerando', 'completed' => 'Concluído', 'error' => 'Erro'];
                                    $statusClasses = ['draft' => 'status-draft', 'generating' => 'status-generating', 'completed' => 'status-completed', 'error' => 'status-error'];
                                    $status = $r['status'] ?? 'completed';
                                ?>
                                <span class="badge <?= $statusClasses[$status] ?? 'bg-secondary' ?>">
                                    <?= $statusLabels[$status] ?? ucfirst($status) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('ai/view_report/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('btnGenerate').addEventListener('click', function() {
    var qId = document.getElementById('questionnaire_id').value;
    if (!qId) { showToast('Selecione um questionário.', 'warning'); return; }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';
    document.getElementById('loadingIndicator').classList.add('active');

    $.ajax({
        url: '<?= base_url("ai/generate_report") ?>',
        type: 'POST',
        data: {
            questionnaire_id: qId,
            report_type: document.getElementById('report_type').value,
            date_from: document.getElementById('date_from').value,
            date_to: document.getElementById('date_to').value
        },
        dataType: 'json',
        timeout: 120000,
        success: function(data) {
            if (data.success) {
                showToast('Relatório gerado com sucesso!', 'success');
                if (data.report_id) {
                    setTimeout(function() {
                        window.location.href = '<?= base_url("ai/view_report/") ?>' + data.report_id;
                    }, 1000);
                } else {
                    setTimeout(function() { location.reload(); }, 1000);
                }
            } else {
                showToast(data.message || 'Erro ao gerar relatório.', 'danger');
            }
        },
        error: function(xhr) {
            if (xhr.statusText === 'timeout') {
                showToast('O relatório está demorando mais que o esperado. Tente novamente.', 'warning');
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
