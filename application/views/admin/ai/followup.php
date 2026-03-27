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
    .suggestion-card .card-top { padding: 1rem 1.25rem; }
    .suggestion-card .card-bottom {
        padding: 0.75rem 1.25rem; background: #fafbfc;
        border-top: 1px solid #f0f0f0;
    }
    .question-ref-box {
        background: #f0f4fa; border-left: 4px solid var(--primary-color);
        border-radius: 0 0.375rem 0.375rem 0;
        padding: 0.6rem 1rem; margin-bottom: 0.75rem;
    }
    .question-ref-box .q-id { color: var(--primary-color); font-weight: 700; }
    .question-ref-box .q-text { color: var(--secondary-color); }
    .tip-text {
        font-size: 1rem; color: #333; line-height: 1.5;
        padding: 0.5rem 0;
    }
    .rationale-text {
        font-size: 0.85rem; color: #6c757d; font-style: italic;
        border-left: 3px solid #dee2e6; padding-left: 0.75rem;
        margin-top: 0.5rem;
    }
    .status-pending { background: #f0ad4e; color: #fff; }
    .status-approved { background: #8fae5d; color: #fff; }
    .status-rejected { background: #dc3545; color: #fff; }
    .status-edited { background: #5bc0de; color: #fff; }
    .no-question-warning {
        background: #fff3cd; border: 1px solid #ffc107;
        border-radius: 0.375rem; padding: 0.4rem 0.75rem;
        font-size: 0.8rem; color: #856404; margin-bottom: 0.5rem;
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Sugestões de Dicas</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-lightbulb me-2" style="color: var(--primary-color);"></i>Sugestões de Dicas</h2>
        <small class="text-muted">A IA gera dicas de follow-up para cada pergunta. Ao aprovar, a dica é cadastrada automaticamente na pergunta.</small>
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

<!-- Gerar Dicas -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-wand-magic-sparkles me-2"></i>Gerar Dicas por IA</h5>
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
                    <i class="fas fa-bolt me-1"></i>Gerar Dicas
                </button>
            </div>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="fas fa-info-circle me-1"></i>A IA analisa as perguntas do questionário e sugere dicas de follow-up para cada uma. Você pode aprovar, editar ou descartar cada sugestão.
        </small>
    </div>
</div>

<!-- Loading -->
<div class="loading-overlay" id="loadingIndicator">
    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Carregando...</span>
    </div>
    <p class="text-muted">Analisando perguntas e gerando dicas de follow-up...</p>
</div>

<!-- Lista de Sugestões -->
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Dicas Sugeridas</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?= count($suggestions) ?> dica(s)</span>
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
                <i class="fas fa-lightbulb d-block"></i>
                <h5>Nenhuma dica gerada</h5>
                <p>Selecione um questionário e clique em "Gerar Dicas" para que a IA sugira dicas de follow-up para as perguntas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $s): ?>
            <div class="suggestion-card" id="suggestion-<?= $s['id'] ?>">
                <div class="card-top">
                    <!-- Pergunta vinculada -->
                    <?php if (!empty($s['question_id']) && !empty($s['question_text_ref'])): ?>
                    <div class="question-ref-box">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-question-circle me-2 mt-1" style="color: var(--primary-color);"></i>
                            <div>
                                <span class="q-id">Pergunta #<?= $s['question_id'] ?></span>
                                <span class="q-text ms-1"><?= htmlspecialchars($s['question_text_ref']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php elseif (empty($s['question_id'])): ?>
                    <div class="no-question-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>Dica não vinculada a uma pergunta. Gerada antes da atualização do sistema.
                    </div>
                    <?php endif; ?>

                    <!-- Dica sugerida -->
                    <div class="tip-text">
                        <i class="fas fa-lightbulb me-2" style="color: #f0ad4e;"></i>
                        <?= htmlspecialchars($s['suggested_question_text']) ?>
                    </div>

                    <!-- Status e meta -->
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                        <?php if (!empty($s['questionnaire_title'])): ?>
                            <small class="text-muted"><i class="fas fa-clipboard-list me-1"></i><?= htmlspecialchars($s['questionnaire_title']) ?></small>
                        <?php endif; ?>
                        <?php
                            $statusClass = 'status-pending';
                            $statusLabel = 'Pendente';
                            if ($s['status'] === 'approved') { $statusClass = 'status-approved'; $statusLabel = 'Aprovada — cadastrada na pergunta'; }
                            elseif ($s['status'] === 'rejected' || $s['status'] === 'discarded') { $statusClass = 'status-rejected'; $statusLabel = 'Descartada'; }
                            elseif ($s['status'] === 'edited') { $statusClass = 'status-edited'; $statusLabel = 'Editada'; }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                        <small class="text-muted ms-auto"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></small>
                    </div>

                    <?php if (!empty($s['rationale'])): ?>
                    <div class="rationale-text">
                        <i class="fas fa-quote-left me-1" style="font-size: 0.7rem;"></i>
                        <?= htmlspecialchars($s['rationale']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($s['status'] === 'pending' || $s['status'] === 'edited'): ?>
                <div class="card-bottom d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-danger btn-sm" onclick="actionFollowup(<?= $s['id'] ?>, 'discard')">
                        <i class="fas fa-times me-1"></i>Descartar
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="editFollowup(<?= $s['id'] ?>)">
                        <i class="fas fa-pen me-1"></i>Editar
                    </button>
                    <button class="btn btn-success btn-sm" onclick="actionFollowup(<?= $s['id'] ?>, 'approve')" <?= empty($s['question_id']) ? 'disabled title="Dica sem pergunta vinculada"' : '' ?>>
                        <i class="fas fa-check me-1"></i>Aprovar e Cadastrar
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
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #1a2847); color: white;">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Editar Dica</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_suggestion_id">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="color: var(--secondary-color);">Texto da Dica</label>
                    <textarea class="form-control" id="edit_question_text" rows="3" placeholder="Ex: Se mencionar X, pergunte sobre Y..."></textarea>
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
                showToast('Dicas geradas com sucesso!', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast(data.message || 'Erro ao gerar dicas.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede. Tente novamente.', 'danger'); },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar Dicas';
            document.getElementById('loadingIndicator').classList.remove('active');
        }
    });
});

function actionFollowup(id, action) {
    var msg = action === 'approve' ? 'Aprovar dica e cadastrar na pergunta?' : 'Descartar esta dica?';
    if (!confirm(msg)) return;

    $.ajax({
        url: '<?= base_url("ai/action_followup") ?>',
        type: 'POST',
        data: { suggestion_id: id, action: action },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast(action === 'approve' ? 'Dica aprovada e cadastrada na pergunta!' : 'Dica descartada.', 'success');
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
    var tipEl = card.querySelector('.tip-text');
    var text = tipEl ? tipEl.textContent.trim() : '';
    document.getElementById('edit_suggestion_id').value = id;
    document.getElementById('edit_question_text').value = text;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function saveEdit() {
    var id = document.getElementById('edit_suggestion_id').value;
    var text = document.getElementById('edit_question_text').value.trim();
    if (!text) { showToast('Informe o texto da dica.', 'warning'); return; }

    $.ajax({
        url: '<?= base_url("ai/edit_followup") ?>',
        type: 'POST',
        data: { suggestion_id: id, question_text: text },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast('Dica atualizada.', 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message || 'Erro ao salvar.', 'danger');
            }
        },
        error: function() { showToast('Erro de rede.', 'danger'); }
    });
}

$('#btnClearFollowups').on('click', function() {
    if (!confirm('Excluir todas as dicas sugeridas? Esta ação não pode ser desfeita.')) return;
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
