<style>
    .ai-page-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }
    .ai-page-card .card-header {
        background: white;
        border-bottom: 2px solid var(--primary-color);
        padding: 1rem 1.25rem;
    }
    .ai-page-card .card-header h5 {
        margin: 0; color: var(--secondary-color); font-weight: 600;
    }
    .breadcrumb { background: transparent; padding: 0; margin: 0; }
    .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
    .breadcrumb-item.active { color: var(--secondary-color); }
    .confidence-high { background-color: #8fae5d; }
    .confidence-medium { background-color: #f0ad4e; }
    .confidence-low { background-color: #dc3545; }
    .btn-ai-primary {
        background: linear-gradient(135deg, #8fae5d, #6d8a45);
        border: none; color: white; font-weight: 500;
    }
    .btn-ai-primary:hover { opacity: 0.9; color: white; }
    .btn-ai-primary:disabled { opacity: 0.6; color: white; }
    .status-badge-pending { background: #f0ad4e; color: #fff; }
    .status-badge-approved { background: #8fae5d; color: #fff; }
    .status-badge-rejected { background: #dc3545; color: #fff; }
    .empty-state {
        text-align: center; padding: 3rem 1rem; color: #6c757d;
    }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }
    .loading-overlay {
        display: none; text-align: center; padding: 2rem;
    }
    .loading-overlay.active { display: block; }
    .table th { color: var(--secondary-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Preenchimento Inteligente</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-magic me-2" style="color: var(--primary-color);"></i>Preenchimento Inteligente</h2>
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

<!-- Gerar Sugestões -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-wand-magic-sparkles me-2"></i>Gerar Sugestões</h5>
    </div>
    <div class="card-body p-3">
        <div class="row align-items-end">
            <div class="col-md-8">
                <label for="questionnaire_id" class="form-label fw-bold" style="color: var(--secondary-color);">Questionário</label>
                <select class="form-select" id="questionnaire_id" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="">Selecione um questionário...</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= $q->id ?>"><?= htmlspecialchars($q->title ?? $q->name ?? 'Questionário #' . $q->id) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-ai-primary w-100" id="btnGenerate" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-bolt me-1"></i>Gerar Sugestões
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
    <p class="text-muted">Analisando dados e gerando sugestões de preenchimento...</p>
</div>

<!-- Tabela de Sugestões -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Sugestões</h5>
        <span class="badge bg-secondary" id="suggestionsCount"><?= count($suggestions) ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($suggestions)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <h5>Nenhuma sugestão encontrada</h5>
                <p>Selecione um questionário e clique em "Gerar Sugestões" para começar.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 110px;">ID Resposta</th>
                            <th>Pergunta</th>
                            <th>Valor Sugerido</th>
                            <th style="width: 160px;">Confiança</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 130px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="suggestionsBody">
                        <?php foreach ($suggestions as $s): ?>
                        <tr id="suggestion-row-<?= $s['id'] ?>">
                            <td>
                                <?php if (!empty($s['form_response_id'])): ?>
                                    <a href="<?= base_url('responses/view/' . $s['form_response_id']) ?>" target="_blank" title="Ver resposta">
                                        #<?= $s['form_response_id'] ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($s['question_text']) ?></strong>
                                <br><small class="text-muted">ID: <?= $s['question_id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($s['suggested_value']) ?></td>
                            <td>
                                <?php
                                    $score = floatval($s['confidence_score']) * 100;
                                    $barClass = $score >= 70 ? 'confidence-high' : ($score >= 40 ? 'confidence-medium' : 'confidence-low');
                                ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?= $barClass ?>" role="progressbar"
                                         style="width: <?= $score ?>%;" aria-valuenow="<?= $score ?>"
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?= number_format($score, 0) ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $statusClass = 'status-badge-pending';
                                    $statusLabel = 'Pendente';
                                    if ($s['status'] === 'approved') { $statusClass = 'status-badge-approved'; $statusLabel = 'Aprovada'; }
                                    elseif ($s['status'] === 'rejected') { $statusClass = 'status-badge-rejected'; $statusLabel = 'Rejeitada'; }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'pending'): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" onclick="actionSuggestion(<?= $s['id'] ?>, 'approve')" title="Aprovar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="actionSuggestion(<?= $s['id'] ?>, 'reject')" title="Rejeitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></small>
                                <?php endif; ?>
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
    if (!qId) {
        showToast('Selecione um questionário.', 'warning');
        return;
    }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';
    document.getElementById('loadingIndicator').classList.add('active');

    $.ajax({
        url: '<?= base_url("ai/generate_suggestions") ?>',
        type: 'POST',
        data: { questionnaire_id: qId },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Sugestões geradas com sucesso!', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast(data.message || 'Erro ao gerar sugestões.', 'danger');
            }
        },
        error: function() {
            showToast('Erro de rede. Tente novamente.', 'danger');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar Sugestões';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function actionSuggestion(id, action) {
    var url = action === 'approve' ? '<?= base_url("ai/approve_suggestion") ?>' : '<?= base_url("ai/reject_suggestion") ?>';

    $.ajax({
        url: url,
        type: 'POST',
        data: { suggestion_id: id },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                var row = document.getElementById('suggestion-row-' + id);
                var badge = row.querySelector('.badge');
                var actionsCell = row.querySelector('td:last-child');

                if (action === 'approve') {
                    badge.className = 'badge status-badge-approved';
                    badge.textContent = 'Aprovada';
                } else {
                    badge.className = 'badge status-badge-rejected';
                    badge.textContent = 'Rejeitada';
                }
                actionsCell.innerHTML = '<small class="text-muted">Agora</small>';
                showToast('Sugestão ' + (action === 'approve' ? 'aprovada' : 'rejeitada') + '.', 'success');
            } else {
                showToast(data.message || 'Erro ao processar ação.', 'danger');
            }
        },
        error: function() {
            showToast('Erro de rede. Tente novamente.', 'danger');
        }
    });
}

function showToast(message, type) {
    var existing = document.getElementById('aiToast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'aiToast';
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;';
    toast.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show border-0 shadow-lg mb-0" role="alert">' +
        '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') + ' me-2"></i>' +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 4000);
}
</script>
