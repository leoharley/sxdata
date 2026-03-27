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
    .rule-card {
        background: white; border: 1px solid #e9ecef; border-radius: 0.5rem;
        padding: 1rem; margin-bottom: 0.75rem; transition: border-color 0.2s;
    }
    .rule-card:hover { border-color: var(--primary-color); }
    .rule-card.ai-generated { border-left: 4px solid var(--primary-color); }
    .rule-card.manual { border-left: 4px solid var(--secondary-color); }
    .rule-arrow {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #8fae5d, #6d8a45);
        color: white; font-size: 0.9rem; margin: 0 0.75rem;
    }
    .rule-node {
        display: inline-block; background: #f8f9fa; border: 1px solid #dee2e6;
        border-radius: 0.375rem; padding: 0.35rem 0.75rem; font-size: 0.9rem;
        font-weight: 500; color: var(--secondary-color);
    }
    .condition-badge {
        font-size: 0.75rem; background: #e9ecef; color: #495057;
        padding: 0.25em 0.6em; border-radius: 0.25rem;
    }
    .info-card {
        background: linear-gradient(135deg, rgba(143,174,93,0.08), rgba(35,52,95,0.05));
        border: 1px solid rgba(143,174,93,0.2); border-radius: 0.75rem;
        padding: 1.25rem; margin-bottom: 1.5rem;
    }
    .table th {
        color: var(--secondary-color); font-weight: 600; font-size: 0.85rem;
        text-transform: uppercase;
    }
    .priority-badge { font-size: 0.75rem; padding: 0.2em 0.5em; border-radius: 0.25rem; }
    .rule-actions { white-space: nowrap; }
    .badge-approved { background: #8fae5d; color: white; }
    .badge-rejected { background: #dc3545; color: white; }
    .badge-pending { background: #f0ad4e; color: #333; }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active">Questionário Adaptativo</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="fas fa-route me-2" style="color: var(--primary-color);"></i>Questionário Adaptativo</h2>
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

<!-- Info -->
<div class="info-card">
    <div class="d-flex align-items-start">
        <i class="fas fa-info-circle me-3 mt-1" style="color: var(--primary-color); font-size: 1.5rem;"></i>
        <div>
            <h6 class="mb-1" style="color: var(--secondary-color); font-weight: 600;">Como funciona o roteamento adaptativo?</h6>
            <p class="mb-2 text-muted">
                O sistema usa IA para determinar qual pergunta exibir a seguir com base nas respostas anteriores do respondente.
                Regras de roteamento são avaliadas por prioridade, e a IA analisa o contexto para escolher o melhor caminho.
            </p>
            <div class="d-flex gap-3">
                <small><i class="fas fa-robot me-1" style="color: var(--primary-color);"></i><strong>Regra IA:</strong> Gerada automaticamente</small>
                <small><i class="fas fa-user me-1" style="color: var(--secondary-color);"></i><strong>Regra Manual:</strong> Definida pelo administrador</small>
                <small><i class="fas fa-shield-alt me-1 text-warning"></i><strong>Fallback:</strong> Caminho determinístico de segurança</small>
            </div>
        </div>
    </div>
</div>

<!-- Seleção de Questionário -->
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-clipboard-list me-2"></i>Selecionar Questionário</h5>
    </div>
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('ai/adaptive') ?>">
            <div class="row align-items-end">
                <div class="col-md-9">
                    <label for="questionnaire_id" class="form-label fw-bold" style="color: var(--secondary-color);">Questionário</label>
                    <select class="form-select" id="questionnaire_id" name="questionnaire_id">
                        <option value="">Selecione um questionário...</option>
                        <?php foreach ($questionnaires as $q):
                            $qid   = is_array($q) ? $q['id']    : $q->id;
                            $qtitle = is_array($q) ? $q['title'] : ($q->title ?? $q->name ?? 'Questionário #' . $q->id);
                        ?>
                            <option value="<?= $qid ?>" <?= $selected_questionnaire_id == $qid ? 'selected' : '' ?>>
                                #<?= $qid ?> — <?= htmlspecialchars($qtitle) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-ai-primary w-100">
                        <i class="fas fa-search me-1"></i>Carregar Regras
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Regras de Roteamento -->
<?php if (!empty($selected_questionnaire_id)): ?>
<div class="ai-page-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-project-diagram me-2"></i>Regras de Roteamento</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?= count($rules) ?> regra(s)</span>
            <?php if ($is_enabled): ?>
            <button class="btn btn-sm btn-ai-primary" id="btnGenerateRules">
                <i class="fas fa-robot me-1"></i>Gerar com IA
            </button>
            <?php endif; ?>
            <?php if (!empty($rules)): ?>
            <button class="btn btn-sm btn-outline-danger" id="btnClearRules">
                <i class="fas fa-trash me-1"></i>Limpar Todas
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-3">
        <?php if (empty($rules)): ?>
            <div class="empty-state">
                <i class="fas fa-sitemap d-block"></i>
                <h5>Nenhuma regra encontrada</h5>
                <p>Este questionário ainda não possui regras de roteamento adaptativo.</p>
            </div>
        <?php else: ?>
            <!-- Visualização em Tabela -->
            <div class="table-responsive mb-3">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Origem</th>
                            <th>Condição</th>
                            <th>Destino</th>
                            <th>Fallback</th>
                            <th>Prioridade</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td>
                                <span class="rule-node">
                                    <?php
                                        $sourceLabel = 'Pergunta #' . $rule['source_question_id'];
                                        if (!empty($questions)) {
                                            foreach ($questions as $q) {
                                                if (isset($q->id) && $q->id == $rule['source_question_id']) {
                                                    $sourceLabel = htmlspecialchars(mb_substr($q->text ?? $q->question_text ?? $sourceLabel, 0, 40)) . '...';
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <?= $sourceLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $condition = $rule['condition_logic'];
                                    if (is_string($condition)) {
                                        $decoded = json_decode($condition, true);
                                        if ($decoded) {
                                            echo '<span class="condition-badge">' . htmlspecialchars($decoded['operator'] ?? '') . ' ' . htmlspecialchars($decoded['value'] ?? '') . '</span>';
                                        } else {
                                            echo '<span class="condition-badge">' . htmlspecialchars(mb_substr($condition, 0, 50)) . '</span>';
                                        }
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="rule-node">
                                    <?php
                                        $targetLabel = 'Pergunta #' . $rule['target_question_id'];
                                        if (!empty($questions)) {
                                            foreach ($questions as $q) {
                                                if (isset($q->id) && $q->id == $rule['target_question_id']) {
                                                    $targetLabel = htmlspecialchars(mb_substr($q->text ?? $q->question_text ?? $targetLabel, 0, 40)) . '...';
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <?= $targetLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($rule['fallback_target_id'])): ?>
                                    <span class="text-warning"><i class="fas fa-shield-alt me-1"></i>#<?= $rule['fallback_target_id'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary priority-badge"><?= $rule['priority'] ?></span>
                            </td>
                            <td>
                                <?php if (!empty($rule['ai_generated'])): ?>
                                    <span class="badge" style="background: var(--primary-color); color: white;"><i class="fas fa-robot me-1"></i>IA</span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--secondary-color); color: white;"><i class="fas fa-user me-1"></i>Manual</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $approval = $rule['approved_by'] ?? null;
                                    $approved_at = $rule['approved_at'] ?? null;
                                    $is_rejected = isset($rule['is_active']) && !$rule['is_active'] && $approval;
                                ?>
                                <?php if ($approved_at && !$is_rejected): ?>
                                    <span class="badge badge-approved"><i class="fas fa-check me-1"></i>Aprovada</span>
                                <?php elseif ($is_rejected): ?>
                                    <span class="badge badge-rejected"><i class="fas fa-times me-1"></i>Rejeitada</span>
                                <?php elseif (!empty($rule['is_active'])): ?>
                                    <span class="badge badge-pending"><i class="fas fa-clock me-1"></i>Pendente</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td class="rule-actions">
                                <?php if (empty($approved_at) && !empty($rule['is_active'])): ?>
                                <button class="btn btn-sm btn-outline-success py-0 px-2 btn-approve-rule" data-id="<?= (int)$rule['id'] ?>" title="Aprovar e aplicar como lógica condicional">
                                    <i class="fas fa-check me-1"></i>Aprovar
                                </button>
                                <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-reject-rule" data-id="<?= (int)$rule['id'] ?>" title="Rejeitar regra">
                                    <i class="fas fa-times me-1"></i>Rejeitar
                                </button>
                                <?php elseif ($approved_at && !$is_rejected): ?>
                                <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Aplicada</small>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary py-0 px-2 btn-restore-rule" data-id="<?= (int)$rule['id'] ?>" title="Restaurar para pendente">
                                    <i class="fas fa-undo me-1"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Visualização em Fluxo -->
            <h6 class="mt-4 mb-3" style="color: var(--secondary-color); font-weight: 600;">
                <i class="fas fa-stream me-2"></i>Fluxo Visual
            </h6>
            <?php foreach ($rules as $rule):
                $approval2 = $rule['approved_by'] ?? null;
                $approved_at2 = $rule['approved_at'] ?? null;
                $is_rejected2 = isset($rule['is_active']) && !$rule['is_active'] && $approval2;
            ?>
            <div class="rule-card <?= !empty($rule['ai_generated']) ? 'ai-generated' : 'manual' ?>" id="flow-<?= (int)$rule['id'] ?>">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="rule-node">Q#<?= $rule['source_question_id'] ?></span>
                    <span class="rule-arrow"><i class="fas fa-arrow-right"></i></span>
                    <span class="rule-node">Q#<?= $rule['target_question_id'] ?></span>
                    <div class="ms-3">
                        <?php if (!empty($rule['ai_generated'])): ?>
                            <span class="badge" style="background: var(--primary-color); color: white;"><i class="fas fa-robot me-1"></i>IA</span>
                        <?php else: ?>
                            <span class="badge" style="background: var(--secondary-color); color: white;"><i class="fas fa-user me-1"></i>Manual</span>
                        <?php endif; ?>
                        <span class="badge bg-secondary ms-1">Prioridade: <?= $rule['priority'] ?></span>

                        <?php if ($approved_at2 && !$is_rejected2): ?>
                            <span class="badge badge-approved ms-1"><i class="fas fa-check me-1"></i>Aprovada</span>
                        <?php elseif ($is_rejected2): ?>
                            <span class="badge badge-rejected ms-1"><i class="fas fa-times me-1"></i>Rejeitada</span>
                        <?php elseif (!empty($rule['is_active'])): ?>
                            <span class="badge badge-pending ms-1"><i class="fas fa-clock me-1"></i>Pendente</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($rule['fallback_target_id'])): ?>
                    <div class="ms-auto me-2">
                        <small class="text-warning"><i class="fas fa-shield-alt me-1"></i>Fallback: Q#<?= $rule['fallback_target_id'] ?></small>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($approved_at2) && !empty($rule['is_active'])): ?>
                    <div class="ms-auto d-flex gap-1">
                        <button class="btn btn-sm btn-success py-0 px-2 btn-approve-rule" data-id="<?= (int)$rule['id'] ?>">
                            <i class="fas fa-check me-1"></i>Aprovar
                        </button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-reject-rule" data-id="<?= (int)$rule['id'] ?>">
                            <i class="fas fa-times me-1"></i>Rejeitar
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="ai-page-card">
    <div class="card-body">
        <div class="empty-state">
            <i class="fas fa-hand-pointer d-block"></i>
            <h5>Selecione um questionário</h5>
            <p>Escolha um questionário e clique em <strong>Carregar Regras</strong> para visualizar as regras de roteamento adaptativo.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('questionnaire_id').addEventListener('change', function() {
    if (this.value) this.closest('form').submit();
});

<?php if (!empty($rules)): ?>
document.getElementById('btnClearRules').addEventListener('click', function() {
    if (!confirm('Excluir todas as regras deste questionário? Esta ação não pode ser desfeita.')) return;
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Limpando...';
    $.ajax({
        url: '<?= base_url("ai/clear_adaptive_rules") ?>',
        type: 'POST',
        data: { questionnaire_id: <?= $selected_questionnaire_id ?> },
        dataType: 'json',
        success: function(data) {
            if (data.success) location.reload();
            else { alert(data.message || 'Erro.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash me-1"></i>Limpar Todas'; }
        },
        error: function() { alert('Erro de comunicação.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash me-1"></i>Limpar Todas'; }
    });
});
<?php endif; ?>

<?php if (!empty($selected_questionnaire_id) && $is_enabled): ?>
document.getElementById('btnGenerateRules').addEventListener('click', function() {
    if (!confirm('Gerar regras adaptativas com IA para este questionário? Regras existentes serão mantidas.')) return;
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';

    $.ajax({
        url: '<?= base_url("ai/generate_adaptive_rules") ?>',
        type: 'POST',
        data: { questionnaire_id: <?= $selected_questionnaire_id ?> },
        dataType: 'text',
        timeout: 60000,
        success: function(raw) {
            var data;
            try { var s = raw.indexOf('{'); data = JSON.parse(s >= 0 ? raw.substring(s) : raw); }
            catch(e) { alert('Erro ao processar resposta.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-robot me-1"></i>Gerar com IA'; return; }
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Erro ao gerar regras.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-robot me-1"></i>Gerar com IA';
            }
        },
        error: function() {
            alert('Erro de comunicação.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-robot me-1"></i>Gerar com IA';
        }
    });
});
<?php endif; ?>

<?php if (!empty($rules)): ?>
(function waitForJQuery() {
    if (typeof jQuery === 'undefined') return setTimeout(waitForJQuery, 50);
    jQuery(function($) {
        var BASE = '<?= base_url() ?>';

        // Aprovar regra
        $(document).on('click', '.btn-approve-rule', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            if (!confirm('Aprovar esta regra? Ela será aplicada como lógica condicional no questionário.')) return;

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $.ajax({
                url: BASE + 'ai/approve_adaptive_rule',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(res.message || 'Regra aprovada e aplicada como lógica condicional!');
                        location.reload();
                    } else {
                        alert(res.message || 'Erro ao aprovar.');
                        $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Aprovar');
                    }
                },
                error: function() {
                    alert('Erro de comunicação.');
                    $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Aprovar');
                }
            });
        });

        // Rejeitar regra
        $(document).on('click', '.btn-reject-rule', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            if (!confirm('Rejeitar esta regra?')) return;

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $.ajax({
                url: BASE + 'ai/reject_adaptive_rule',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else { alert(res.message || 'Erro.'); $btn.prop('disabled', false).html('<i class="fas fa-times me-1"></i>Rejeitar'); }
                },
                error: function() { alert('Erro de comunicação.'); $btn.prop('disabled', false).html('<i class="fas fa-times me-1"></i>Rejeitar'); }
            });
        });

        // Restaurar regra
        $(document).on('click', '.btn-restore-rule', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            $btn.prop('disabled', true);
            $.ajax({
                url: BASE + 'ai/restore_adaptive_rule',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else { alert(res.message || 'Erro.'); $btn.prop('disabled', false); }
                },
                error: function() { alert('Erro de comunicação.'); $btn.prop('disabled', false); }
            });
        });
    });
})();
<?php endif; ?>
</script>
