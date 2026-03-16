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
    .diff-card {
        background: white; border-radius: 0.5rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 1rem;
        overflow: hidden;
    }
    .diff-card .diff-header {
        background: #f8f9fa; padding: 0.75rem 1rem;
        border-bottom: 1px solid #e9ecef;
        display: flex; justify-content: space-between; align-items: center;
    }
    .diff-original {
        background: #fff5f5; border-left: 4px solid #dc3545;
        padding: 1rem; font-size: 0.95rem;
    }
    .diff-reformulated {
        background: #f0fff0; border-left: 4px solid #8fae5d;
        padding: 1rem; font-size: 0.95rem;
    }
    .empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }
    .loading-overlay { display: none; text-align: center; padding: 2rem; }
    .loading-overlay.active { display: block; }
    .status-pending { color: #f0ad4e; }
    .status-approved { color: #8fae5d; }
    .status-rejected { color: #dc3545; }
    .objective-badge {
        font-size: 0.75rem; padding: 0.25em 0.65em;
        border-radius: 0.35rem; font-weight: 500;
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Reformulação de Perguntas</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-edit me-2" style="color: var(--primary-color);"></i>Reformulação de Perguntas</h2>
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

<!-- Formulário de Reformulação -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-pen-fancy me-2"></i>Nova Reformulação</h5>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="question_id" class="form-label fw-bold" style="color: var(--secondary-color);">ID da Pergunta</label>
                <input type="number" class="form-control" id="question_id" placeholder="Ex: 123" <?= !$is_enabled ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-4">
                <label for="objective" class="form-label fw-bold" style="color: var(--secondary-color);">Objetivo</label>
                <select class="form-select" id="objective" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="clareza">Clareza</option>
                    <option value="reduzir viés">Reduzir Viés</option>
                    <option value="simplificar">Simplificar</option>
                    <option value="público específico">Público Específico</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-ai-primary w-100" id="btnReformulate" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-sync-alt me-1"></i>Reformular
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
    <p class="text-muted">Processando reformulação com IA...</p>
</div>

<!-- Resultado da Reformulação (preenchido via AJAX) -->
<div id="reformulationResult" style="display: none;">
    <div class="ai-page-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-exchange-alt me-2"></i>Resultado</h5>
            <span class="badge bg-info" id="resultObjective"></span>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="diff-original">
                        <small class="text-muted d-block mb-2"><i class="fas fa-minus-circle me-1"></i>Texto Original</small>
                        <p class="mb-0" id="resultOriginal"></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="diff-reformulated">
                        <small class="text-muted d-block mb-2"><i class="fas fa-plus-circle me-1"></i>Texto Reformulado</small>
                        <p class="mb-0" id="resultReformulated"></p>
                    </div>
                </div>
            </div>
            <div class="p-3 text-end border-top">
                <button class="btn btn-outline-danger btn-sm me-2" id="btnReject" onclick="approveReformulation(false)">
                    <i class="fas fa-times me-1"></i>Rejeitar
                </button>
                <button class="btn btn-success btn-sm" id="btnApprove" onclick="approveReformulation(true)">
                    <i class="fas fa-check me-1"></i>Aprovar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Histórico -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-history me-2"></i>Histórico de Reformulações</h5>
        <span class="badge bg-secondary"><?= count($reformulations) ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($reformulations)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <h5>Nenhuma reformulação encontrada</h5>
                <p>Informe uma pergunta e objetivo para gerar a primeira reformulação.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reformulations as $r): ?>
            <div class="diff-card">
                <div class="diff-header">
                    <div>
                        <strong>Pergunta #<?= $r['question_id'] ?></strong>
                        <span class="badge objective-badge bg-info ms-2"><?= htmlspecialchars($r['objective']) ?></span>
                        <?php
                            $statusIcon = 'fas fa-clock status-pending';
                            $statusText = 'Pendente';
                            if ($r['status'] === 'approved') { $statusIcon = 'fas fa-check-circle status-approved'; $statusText = 'Aprovada'; }
                            elseif ($r['status'] === 'rejected') { $statusIcon = 'fas fa-times-circle status-rejected'; $statusText = 'Rejeitada'; }
                        ?>
                        <span class="ms-2"><i class="<?= $statusIcon ?> me-1"></i><?= $statusText ?></span>
                    </div>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
                </div>
                <div class="row g-0">
                    <div class="col-md-6">
                        <div class="diff-original">
                            <small class="text-muted d-block mb-1">Original</small>
                            <?= htmlspecialchars($r['original_text'] ?? $r['current_text'] ?? '') ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="diff-reformulated">
                            <small class="text-muted d-block mb-1">Reformulado</small>
                            <?= htmlspecialchars($r['reformulated_text']) ?>
                        </div>
                    </div>
                </div>
                <?php if ($r['status'] === 'pending'): ?>
                <div class="p-2 text-end border-top">
                    <button class="btn btn-outline-danger btn-sm me-1" onclick="approveHistoryItem(<?= $r['id'] ?>, false)">
                        <i class="fas fa-times me-1"></i>Rejeitar
                    </button>
                    <button class="btn btn-success btn-sm" onclick="approveHistoryItem(<?= $r['id'] ?>, true)">
                        <i class="fas fa-check me-1"></i>Aprovar
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
var currentReformulationId = null;

document.getElementById('btnReformulate').addEventListener('click', function() {
    var questionId = document.getElementById('question_id').value;
    var objective = document.getElementById('objective').value;

    if (!questionId) {
        showToast('Informe o ID da pergunta.', 'warning');
        return;
    }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Reformulando...';
    document.getElementById('loadingIndicator').classList.add('active');
    document.getElementById('reformulationResult').style.display = 'none';

    $.ajax({
        url: '<?= base_url("ai/generate_reformulation") ?>',
        type: 'POST',
        data: { question_id: questionId, objective: objective },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                currentReformulationId = data.reformulation_id || null;
                document.getElementById('resultOriginal').textContent = data.original_text || '';
                document.getElementById('resultReformulated').textContent = data.reformulated_text || '';
                document.getElementById('resultObjective').textContent = objective;
                document.getElementById('reformulationResult').style.display = 'block';
                showToast('Reformulação gerada com sucesso!', 'success');
            } else {
                showToast(data.message || 'Erro ao gerar reformulação.', 'danger');
            }
        },
        error: function() {
            showToast('Erro de rede. Tente novamente.', 'danger');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Reformular';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function approveReformulation(approved) {
    if (!currentReformulationId) return;
    $.ajax({
        url: '<?= base_url("ai/approve_reformulation") ?>',
        type: 'POST',
        data: { reformulation_id: currentReformulationId, approved: approved ? 1 : 0 },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Reformulação ' + (approved ? 'aprovada' : 'rejeitada') + '.', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast(data.message || 'Erro ao processar.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede.', 'danger'); }
    });
}

function approveHistoryItem(id, approved) {
    $.ajax({
        url: '<?= base_url("ai/approve_reformulation") ?>',
        type: 'POST',
        data: { reformulation_id: id, approved: approved ? 1 : 0 },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Reformulação ' + (approved ? 'aprovada' : 'rejeitada') + '.', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast(data.message || 'Erro ao processar.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede.', 'danger'); }
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
        message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 4000);
}
</script>
