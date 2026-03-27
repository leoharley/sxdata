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
    .suggestion-card {
        background: white; border: 1px solid #e9ecef; border-radius: 0.75rem;
        overflow: hidden; margin-bottom: 1rem; transition: border-color 0.2s;
    }
    .suggestion-card:hover { border-color: var(--primary-color); }
    .suggestion-card .card-top {
        padding: 1rem 1.25rem; border-bottom: 1px solid #f0f0f0;
    }
    .suggestion-card .card-bottom {
        padding: 0.75rem 1.25rem; background: #fafbfc;
    }
    .type-badge {
        font-size: 0.75rem; padding: 0.25em 0.65em; border-radius: 0.35rem;
    }
    .option-tag {
        display: inline-block; background: #e9ecef; color: #495057;
        padding: 0.2em 0.5em; border-radius: 0.25rem; font-size: 0.8rem;
        margin: 0.15rem;
    }
    .rationale-text {
        font-size: 0.9rem; color: #6c757d; font-style: italic;
        border-left: 3px solid var(--primary-color); padding-left: 0.75rem;
        margin-top: 0.5rem;
    }
    .status-pending { background: #f0ad4e; color: #fff; }
    .status-approved { background: #8fae5d; color: #fff; }
    .status-rejected { background: #dc3545; color: #fff; }
    .status-edited { background: #5bc0de; color: #fff; }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Sugestões de Follow-up</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-comments me-2" style="color: var(--primary-color);"></i>Sugestões de Follow-up</h2>
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
        <h5><i class="fas fa-wand-magic-sparkles me-2"></i>Gerar Sugestões de Follow-up</h5>
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
    <p class="text-muted">Analisando questionário e gerando sugestões de follow-up...</p>
</div>

<!-- Lista de Sugestões -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-lightbulb me-2"></i>Sugestões</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?= count($suggestions) ?> sugestão(ões)</span>
            <?php if (!empty($suggestions)): ?>
            <button class="btn btn-sm btn-outline-danger" id="btnClearFollowups">
                <i class="fas fa-trash me-1"></i>Limpar Todas
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-3">
        <?php if (empty($suggestions)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-dots d-block"></i>
                <h5>Nenhuma sugestão encontrada</h5>
                <p>Selecione um questionário e clique em "Gerar Sugestões" para obter perguntas de follow-up.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $s): ?>
            <div class="suggestion-card" id="suggestion-<?= $s['id'] ?>">
                <div class="card-top">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-1" style="color: var(--secondary-color);">
                                <i class="fas fa-lightbulb me-1" style="color: var(--primary-color);"></i>
                                <?= htmlspecialchars($s['suggested_question_text']) ?>
                            </h6>
                            <?php if (!empty($s['question_id'])): ?>
                            <div class="mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-link me-1"></i>Pergunta #<?= $s['question_id'] ?>
                                    <?php if (!empty($s['question_text_ref'])): ?>
                                        — <?= htmlspecialchars(mb_strimwidth($s['question_text_ref'], 0, 60, '...')) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if (!empty($s['questionnaire_title'])): ?>
                                    <small class="text-muted"><i class="fas fa-clipboard-list me-1"></i><?= htmlspecialchars($s['questionnaire_title']) ?></small>
                                <?php endif; ?>
                                <?php
                                    $statusClass = 'status-pending';
                                    $statusLabel = 'Pendente';
                                    if ($s['status'] === 'approved') { $statusClass = 'status-approved'; $statusLabel = 'Aprovada'; }
                                    elseif ($s['status'] === 'rejected' || $s['status'] === 'discarded') { $statusClass = 'status-rejected'; $statusLabel = 'Descartada'; }
                                    elseif ($s['status'] === 'edited') { $statusClass = 'status-edited'; $statusLabel = 'Editada'; }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </div>
                        </div>
                        <small class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></small>
                    </div>

                    <?php
                        $options = $s['suggested_options'];
                        if (is_string($options)) $options = json_decode($options, true);
                    ?>
                    <?php if (!empty($options) && is_array($options)): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">Opções sugeridas:</small>
                        <?php foreach ($options as $opt): ?>
                            <span class="option-tag"><?= htmlspecialchars(is_array($opt) ? ($opt['text'] ?? $opt['label'] ?? '') : $opt) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($s['rationale'])): ?>
                    <div class="rationale-text">
                        <i class="fas fa-quote-left me-1" style="font-size: 0.75rem;"></i>
                        <?= htmlspecialchars($s['rationale']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($s['status'] === 'pending'): ?>
                <div class="card-bottom d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-danger btn-sm" onclick="actionFollowup(<?= $s['id'] ?>, 'discard')">
                        <i class="fas fa-trash me-1"></i>Descartar
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="editFollowup(<?= $s['id'] ?>)">
                        <i class="fas fa-pen me-1"></i>Editar
                    </button>
                    <button class="btn btn-success btn-sm" onclick="actionFollowup(<?= $s['id'] ?>, 'approve')">
                        <i class="fas fa-check me-1"></i>Aprovar
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 2px solid var(--primary-color);">
                <h5 class="modal-title" style="color: var(--secondary-color);"><i class="fas fa-pen me-2"></i>Editar Sugestão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_suggestion_id">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="color: var(--secondary-color);">Texto da Pergunta</label>
                    <textarea class="form-control" id="edit_question_text" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-ai-primary" onclick="saveEdit()">
                    <i class="fas fa-save me-1"></i>Salvar
                </button>
            </div>
        </div>
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
        url: '<?= base_url("ai/generate_followup") ?>',
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
        error: function() { showToast('Erro de rede. Tente novamente.', 'danger'); },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar Sugestões';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function actionFollowup(id, action) {
    $.ajax({
        url: '<?= base_url("ai/action_followup") ?>',
        type: 'POST',
        data: { suggestion_id: id, action: action },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Sugestão ' + (action === 'approve' ? 'aprovada' : 'descartada') + '.', 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message || 'Erro ao processar.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede.', 'danger'); }
    });
}

function editFollowup(id) {
    var card = document.getElementById('suggestion-' + id);
    var text = card.querySelector('h6').textContent;
    document.getElementById('edit_suggestion_id').value = id;
    document.getElementById('edit_question_text').value = text;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function saveEdit() {
    var id = document.getElementById('edit_suggestion_id').value;
    var text = document.getElementById('edit_question_text').value.trim();
    if (!text) { showToast('Informe o texto da pergunta.', 'warning'); return; }

    $.ajax({
        url: '<?= base_url("ai/edit_followup") ?>',
        type: 'POST',
        data: { suggestion_id: id, question_text: text },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Sugestão atualizada.', 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message || 'Erro ao salvar.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede.', 'danger'); }
    });
}

$('#btnClearFollowups').on('click', function() {
    if (!confirm('Excluir todas as sugestões de follow-up? Esta ação não pode ser desfeita.')) return;
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Limpando...';
    $.ajax({
        url: '<?= base_url("ai/clear_followups") ?>',
        type: 'POST',
        dataType: 'json',
        success: function(data) {
            if (data.success) location.reload();
            else { alert(data.message || 'Erro.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash me-1"></i>Limpar Todas'; }
        },
        error: function() { alert('Erro de comunicação.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash me-1"></i>Limpar Todas'; }
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
