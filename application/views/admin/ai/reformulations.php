<style>
    .ai-page-card { background: white; border-radius: 0.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
    .ai-page-card .card-header { background: white; border-bottom: 2px solid var(--primary-color); padding: 1rem 1.25rem; }
    .ai-page-card .card-header h5 { margin: 0; color: var(--secondary-color); font-weight: 600; }
    .breadcrumb { background: transparent; padding: 0; margin: 0; }
    .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
    .btn-ai-primary { background: linear-gradient(135deg, #8fae5d, #6d8a45); border: none; color: white; font-weight: 500; }
    .btn-ai-primary:hover { opacity: 0.9; color: white; }
    .btn-ai-primary:disabled { opacity: 0.6; color: white; }
    .diff-block { border-radius: 0.5rem; overflow: hidden; margin-bottom: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
    .diff-header { background: #f8f9fa; padding: 0.75rem 1rem; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
    .diff-original { background: #fff5f5; border-left: 4px solid #dc3545; padding: 1rem; font-size: 0.95rem; }
    .diff-reformulated { background: #f0fff0; border-left: 4px solid #8fae5d; padding: 1rem; font-size: 0.95rem; }
    .diff-actions { padding: 0.6rem 1rem; background: #fff; border-top: 1px solid #e9ecef; text-align: right; }
    .empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }
    .loading-overlay { display: none; text-align: center; padding: 2rem; }
    .loading-overlay.active { display: block; }
    .status-pending  { color: #f0ad4e; }
    .status-approved { color: #8fae5d; }
    .status-rejected { color: #dc3545; }
    .objective-badge { font-size: 0.75rem; padding: 0.25em 0.65em; border-radius: 0.35rem; font-weight: 500; }
    .diff-block.status-approved-block { opacity: 0.6; }
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

<!-- Seletor de questionário + geração -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-pen-fancy me-2"></i>Gerar Reformulações por Questionário</h5>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="questionnaire_id" class="form-label fw-bold" style="color: var(--secondary-color);">Questionário</label>
                <select class="form-select" id="questionnaire_id" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="">Selecione...</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <?php $qid = is_array($q) ? $q['id'] : $q->id; $qtitle = is_array($q) ? $q['title'] : $q->title; ?>
                        <option value="<?= $qid ?>" <?= $selected_questionnaire_id == $qid ? 'selected' : '' ?>>
                            #<?= $qid ?> — <?= htmlspecialchars($qtitle) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="objective" class="form-label fw-bold" style="color: var(--secondary-color);">Objetivo</label>
                <select class="form-select" id="objective" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <option value="clareza">Clareza</option>
                    <option value="reduzir viés">Reduzir Viés</option>
                    <option value="simplificar">Simplificar</option>
                    <option value="público específico">Público Específico</option>
                    <option value="acessibilidade">Acessibilidade</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-ai-primary w-100" id="btnGenerate" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-magic me-1"></i>Gerar Reformulações
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" id="btnFilter">
                    <i class="fas fa-filter me-1"></i>Filtrar
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
    <p class="text-muted">Gerando reformulações com IA para todas as perguntas...</p>
</div>

<!-- Lista de reformulações -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list-alt me-2"></i>Reformulações
            <?php if ($selected_questionnaire_id): ?>
                <small class="text-muted fw-normal ms-2">— Questionário #<?= $selected_questionnaire_id ?></small>
            <?php endif; ?>
        </h5>
        <span class="badge bg-secondary"><?= count($reformulations) ?> registro(s)</span>
    </div>
    <div class="card-body p-3">

        <?php if (!$selected_questionnaire_id): ?>
            <div class="empty-state">
                <i class="fas fa-hand-point-up d-block"></i>
                <h5>Selecione um questionário</h5>
                <p>Escolha um questionário e clique em <strong>Filtrar</strong> para ver as reformulações existentes,<br>ou em <strong>Gerar Reformulações</strong> para criar novas com IA.</p>
            </div>

        <?php elseif (empty($reformulations)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <h5>Nenhuma reformulação encontrada</h5>
                <p>Clique em <strong>Gerar Reformulações</strong> para a IA sugerir novas versões das perguntas.</p>
            </div>

        <?php else: ?>
            <div id="reformulationsList">
            <?php foreach ($reformulations as $r): ?>
            <div class="diff-block <?= $r['status'] !== 'pending' ? 'status-approved-block' : '' ?>" id="reform-block-<?= $r['id'] ?>">
                <div class="diff-header">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <strong>Pergunta #<?= $r['question_id'] ?></strong>
                        <span class="badge objective-badge bg-info"><?= htmlspecialchars($r['objective'] ?? '') ?></span>
                        <?php
                            $statusIcon  = 'fas fa-clock status-pending';
                            $statusLabel = 'Pendente';
                            if ($r['status'] === 'approved') { $statusIcon = 'fas fa-check-circle status-approved'; $statusLabel = 'Aprovada — pergunta atualizada'; }
                            elseif ($r['status'] === 'rejected') { $statusIcon = 'fas fa-times-circle status-rejected'; $statusLabel = 'Rejeitada'; }
                        ?>
                        <span><i class="<?= $statusIcon ?> me-1"></i><?= $statusLabel ?></span>
                    </div>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
                </div>

                <div class="row g-0">
                    <div class="col-md-6">
                        <div class="diff-original">
                            <small class="text-muted d-block mb-1"><i class="fas fa-minus-circle me-1"></i>Texto Original</small>
                            <?= htmlspecialchars($r['original_text'] ?? '') ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="diff-reformulated">
                            <small class="text-muted d-block mb-1"><i class="fas fa-plus-circle me-1"></i>Texto Reformulado</small>
                            <?= htmlspecialchars($r['reformulated_text']) ?>
                        </div>
                    </div>
                </div>

                <?php if ($r['status'] === 'pending'): ?>
                <div class="diff-actions">
                    <button class="btn btn-outline-danger btn-sm me-2"
                            onclick="actionReformulation(<?= $r['id'] ?>, 'rejected')">
                        <i class="fas fa-times me-1"></i>Rejeitar
                    </button>
                    <button class="btn btn-success btn-sm"
                            onclick="actionReformulation(<?= $r['id'] ?>, 'approved')">
                        <i class="fas fa-check me-1"></i>Aprovar e Aplicar
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.getElementById('btnFilter').addEventListener('click', function() {
    var qId = document.getElementById('questionnaire_id').value;
    if (!qId) { showToast('Selecione um questionário.', 'warning'); return; }
    window.location.href = '<?= base_url('ai/reformulations') ?>?questionnaire_id=' + qId;
});

document.getElementById('btnGenerate').addEventListener('click', function() {
    var qId = document.getElementById('questionnaire_id').value;
    var objective = document.getElementById('objective').value;
    if (!qId) { showToast('Selecione um questionário.', 'warning'); return; }

    if (!confirm('Isso irá gerar reformulações para TODAS as perguntas do questionário selecionado. Continuar?')) return;

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';
    document.getElementById('loadingIndicator').classList.add('active');

    $.ajax({
        url: '<?= base_url("ai/generate_reformulations_batch") ?>',
        type: 'POST',
        data: { questionnaire_id: qId, objective: objective },
        dataType: 'json',
        success: function(data) {
            showToast(data.message || (data.success ? 'Gerado com sucesso!' : 'Erro ao gerar.'), data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(function() {
                    window.location.href = '<?= base_url('ai/reformulations') ?>?questionnaire_id=' + qId;
                }, 1200);
            }
        },
        error: function() { showToast('Erro de rede. Tente novamente.', 'danger'); },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-1"></i>Gerar Reformulações';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function actionReformulation(id, action) {
    var label = action === 'approved' ? 'aprovar e aplicar' : 'rejeitar';
    if (action === 'approved' && !confirm('Ao aprovar, o texto da pergunta original será substituído pelo reformulado. Confirmar?')) return;

    $.ajax({
        url: '<?= base_url("ai/approve_reformulation") ?>',
        type: 'POST',
        data: { reformulation_id: id, action: action },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                var block = document.getElementById('reform-block-' + id);
                var actionsRow = block.querySelector('.diff-actions');
                var statusSpan = block.querySelector('.diff-header span:last-of-type');

                if (action === 'approved') {
                    statusSpan.innerHTML = '<i class="fas fa-check-circle status-approved me-1"></i>Aprovada — pergunta atualizada';
                    block.classList.add('status-approved-block');
                } else {
                    statusSpan.innerHTML = '<i class="fas fa-times-circle status-rejected me-1"></i>Rejeitada';
                    block.classList.add('status-approved-block');
                }
                if (actionsRow) actionsRow.remove();

                showToast(action === 'approved'
                    ? 'Aprovada! O texto da pergunta foi atualizado.'
                    : 'Reformulação rejeitada.', 'success');
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
