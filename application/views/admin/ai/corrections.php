<style>
    .correction-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        transition: transform 0.2s;
    }
    .correction-card:hover { transform: translateY(-2px); }
    .correction-filter-bar {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 1.25rem;
    }
    .correction-action-bar {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 1.25rem;
    }
    .correction-section-title {
        color: var(--secondary-color);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .correction-table th {
        background: var(--secondary-color);
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
        border: none;
    }
    .correction-table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .correction-table tbody tr {
        transition: background 0.15s;
    }
    .correction-table tbody tr:hover {
        background: #f8f9fa;
    }
    .original-value {
        color: #dc3545;
        text-decoration: line-through;
        background: #fff5f5;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .suggested-value {
        color: #198754;
        background: #f0fdf4;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .badge-type-ortografia { background: #6f42c1; color: white; }
    .badge-type-formato { background: #0d6efd; color: white; }
    .badge-type-padronizacao { background: #fd7e14; color: white; }
    .badge-type-completude { background: #20c997; color: white; }
    .badge-status-pending { background: #6c757d; color: white; }
    .badge-status-accepted { background: #8fae5d; color: white; }
    .badge-status-rejected { background: #dc3545; color: white; }
    .badge-status-edited { background: #0d6efd; color: white; }
    .confidence-bar {
        height: 6px;
        border-radius: 3px;
        background: #e9ecef;
        overflow: hidden;
        min-width: 60px;
    }
    .confidence-bar .bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s;
    }
    .edit-input-group {
        display: none;
    }
    .edit-input-group.active {
        display: flex;
    }
    .correction-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.78rem;
    }
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.7);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .loading-overlay.active {
        display: flex;
    }
    .disabled-overlay {
        opacity: 0.6;
        pointer-events: none;
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
        <p class="mt-3 fw-semibold" style="color: var(--secondary-color);">Processando...</p>
    </div>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-spell-check me-2" style="color: var(--primary-color);"></i>Correção e Padronização de Dados</h2>
    <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel de IA
    </a>
</div>

<?php if (!$is_enabled): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Atenção:</strong> A funcionalidade de correção de dados está desabilitada.
    Ative-a nas <a href="<?= base_url('ai/settings') ?>">configurações de IA</a>.
</div>
<?php endif; ?>

<div class="<?= !$is_enabled ? 'disabled-overlay' : '' ?>">

    <!-- Filter Bar -->
    <div class="correction-filter-bar">
        <form method="get" action="<?= base_url('ai/corrections') ?>" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-bold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pending" <?= (!empty($filters['status']) && $filters['status'] == 'pending') ? 'selected' : '' ?>>Pendente</option>
                        <option value="accepted" <?= (!empty($filters['status']) && $filters['status'] == 'accepted') ? 'selected' : '' ?>>Aceita</option>
                        <option value="rejected" <?= (!empty($filters['status']) && $filters['status'] == 'rejected') ? 'selected' : '' ?>>Rejeitada</option>
                        <option value="edited" <?= (!empty($filters['status']) && $filters['status'] == 'edited') ? 'selected' : '' ?>>Editada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-bold">Tipo de Correção</label>
                    <select name="correction_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ortografia" <?= (!empty($filters['correction_type']) && $filters['correction_type'] == 'ortografia') ? 'selected' : '' ?>>Ortografia</option>
                        <option value="formato" <?= (!empty($filters['correction_type']) && $filters['correction_type'] == 'formato') ? 'selected' : '' ?>>Formato</option>
                        <option value="padronizacao" <?= (!empty($filters['correction_type']) && $filters['correction_type'] == 'padronizacao') ? 'selected' : '' ?>>Padronização</option>
                        <option value="completude" <?= (!empty($filters['correction_type']) && $filters['correction_type'] == 'completude') ? 'selected' : '' ?>>Completude</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="<?= base_url('ai/corrections') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Action Bar -->
    <div class="correction-action-bar">
        <h6 class="fw-bold mb-3" style="color: var(--secondary-color);">
            <i class="fas fa-play-circle me-2"></i>Analisar Correções
        </h6>
        <div class="row g-3">
            <!-- Single Response Analysis -->
            <div class="col-md-6">
                <label class="form-label mb-1 small fw-bold">Análise Individual</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                    <input type="number" class="form-control" id="analyzeResponseId" placeholder="ID da resposta do formulário" min="1">
                    <button class="btn btn-primary" type="button" id="btnAnalyzeSingle">
                        <i class="fas fa-search me-1"></i>Analisar
                    </button>
                </div>
            </div>
            <!-- Batch Analysis -->
            <div class="col-md-6">
                <label class="form-label mb-1 small fw-bold">Análise em Lote</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-clipboard-list"></i></span>
                    <select class="form-select" id="batchQuestionnaireId">
                        <option value="">Selecione um questionário...</option>
                        <?php if (!empty($questionnaires)): ?>
                            <?php foreach ($questionnaires as $q): ?>
                                <option value="<?= htmlspecialchars($q->id ?? $q['id'] ?? '') ?>">
                                    <?= htmlspecialchars($q->title ?? $q['title'] ?? $q->name ?? $q['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-primary" type="button" id="btnAnalyzeBatch">
                        <i class="fas fa-layer-group me-1"></i>Analisar Lote
                    </button>
                </div>
            </div>
        </div>
        <div id="analyzeResult" class="mt-2" style="display:none;"></div>
    </div>

    <!-- Corrections Table -->
    <h5 class="correction-section-title">
        <i class="fas fa-list-check me-2"></i>Correções Sugeridas
        <?php if (!empty($corrections)): ?>
            <span class="badge bg-secondary ms-2"><?= count($corrections) ?></span>
        <?php endif; ?>
    </h5>

    <?php if (empty($corrections)): ?>
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-double fa-3x mb-3" style="color: #ccc;"></i>
                <p class="text-muted mb-1">Nenhuma correção encontrada.</p>
                <small class="text-muted">Utilize a seção acima para analisar respostas e identificar correções.</small>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 correction-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Pergunta</th>
                                <th>Valor Original</th>
                                <th>Valor Sugerido</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Confiança</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($corrections as $correction):
                                $c = (object)$correction;
                                $confidence = (float)($c->confidence_score ?? 0);
                                $confidence_pct = round($confidence * 100);
                                $confidence_color = $confidence >= 0.8 ? '#8fae5d' : ($confidence >= 0.5 ? '#f0ad4e' : '#dc3545');

                                $type_labels = [
                                    'ortografia' => 'Ortografia',
                                    'formato' => 'Formato',
                                    'padronizacao' => 'Padronização',
                                    'completude' => 'Completude',
                                ];
                                $type_label = $type_labels[$c->correction_type ?? ''] ?? ucfirst($c->correction_type ?? '-');

                                $status_labels = [
                                    'pending' => 'Pendente',
                                    'accepted' => 'Aceita',
                                    'rejected' => 'Rejeitada',
                                    'edited' => 'Editada',
                                ];
                                $status_icons = [
                                    'pending' => 'fa-clock',
                                    'accepted' => 'fa-check-circle',
                                    'rejected' => 'fa-times-circle',
                                    'edited' => 'fa-pen',
                                ];
                                $status = $c->status ?? 'pending';
                                $status_label = $status_labels[$status] ?? ucfirst($status);
                                $status_icon = $status_icons[$status] ?? 'fa-circle';
                                $is_pending = ($status === 'pending');
                            ?>
                            <tr id="correction-row-<?= (int)$c->id ?>">
                                <td class="ps-3" style="max-width: 250px;">
                                    <span class="fw-semibold"><?= htmlspecialchars($c->question_text ?? '-') ?></span>
                                    <br>
                                    <small class="text-muted">
                                        Resposta #<?= (int)($c->form_response_id ?? 0) ?>
                                        <?php if (!empty($c->reviewed_by)): ?>
                                            | Revisado por: <?= htmlspecialchars($c->reviewed_by) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="original-value"><?= htmlspecialchars($c->original_value ?? '-') ?></span>
                                </td>
                                <td>
                                    <span class="suggested-value"><?= htmlspecialchars($c->suggested_value ?? '-') ?></span>
                                    <?php if (!empty($c->applied_value) && $c->applied_value !== ($c->suggested_value ?? '')): ?>
                                        <br><small class="text-muted mt-1 d-inline-block">
                                            <i class="fas fa-pen-fancy me-1"></i>Aplicado: <strong><?= htmlspecialchars($c->applied_value) ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-type-<?= htmlspecialchars($c->correction_type ?? 'ortografia') ?> px-2 py-1">
                                        <?= $type_label ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <small class="fw-bold mb-1"><?= $confidence_pct ?>%</small>
                                        <div class="confidence-bar">
                                            <div class="bar-fill" style="width: <?= $confidence_pct ?>%; background: <?= $confidence_color ?>;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-status-<?= $status ?> px-2 py-1">
                                        <i class="fas <?= $status_icon ?> me-1"></i><?= $status_label ?>
                                    </span>
                                    <?php if (!empty($c->reviewed_at)): ?>
                                        <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($c->reviewed_at)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center correction-actions">
                                    <?php if ($is_pending): ?>
                                        <div class="d-flex flex-column gap-1 align-items-center" id="actions-<?= (int)$c->id ?>">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success btn-accept" data-id="<?= (int)$c->id ?>" data-suggested="<?= htmlspecialchars($c->suggested_value ?? '') ?>" title="Aceitar sugestão">
                                                    <i class="fas fa-check me-1"></i>Aceitar
                                                </button>
                                                <button class="btn btn-danger btn-reject" data-id="<?= (int)$c->id ?>" title="Rejeitar sugestão">
                                                    <i class="fas fa-times me-1"></i>Rejeitar
                                                </button>
                                            </div>
                                            <button class="btn btn-outline-primary btn-edit-toggle btn-sm" data-id="<?= (int)$c->id ?>" title="Editar valor">
                                                <i class="fas fa-pen me-1"></i>Editar
                                            </button>
                                            <div class="edit-input-group mt-1" id="edit-group-<?= (int)$c->id ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control edit-value-input" id="edit-value-<?= (int)$c->id ?>"
                                                           placeholder="Valor personalizado" value="<?= htmlspecialchars($c->suggested_value ?? '') ?>">
                                                    <button class="btn btn-primary btn-edit-save" data-id="<?= (int)$c->id ?>" title="Salvar edição">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
$(document).ready(function() {
    var baseUrl = '<?= base_url() ?>';

    function showLoading() {
        $('#loadingOverlay').addClass('active');
    }

    function hideLoading() {
        $('#loadingOverlay').removeClass('active');
    }

    function showAnalyzeResult(success, message) {
        var $result = $('#analyzeResult');
        $result.show();
        if (success) {
            $result.attr('class', 'mt-2 alert alert-success py-2 px-3 mb-0');
            $result.html('<small><i class="fas fa-check-circle me-1"></i>' + message + '</small>');
        } else {
            $result.attr('class', 'mt-2 alert alert-danger py-2 px-3 mb-0');
            $result.html('<small><i class="fas fa-times-circle me-1"></i>' + message + '</small>');
        }
        setTimeout(function() { $result.fadeOut(); }, 5000);
    }

    // Single Response Analysis
    $('#btnAnalyzeSingle').on('click', function() {
        var responseId = $('#analyzeResponseId').val();
        if (!responseId || responseId < 1) {
            showAnalyzeResult(false, 'Informe um ID de resposta válido.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Analisando...');
        showLoading();

        $.ajax({
            url: baseUrl + 'ai/analyze_corrections',
            type: 'POST',
            data: { form_response_id: responseId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showAnalyzeResult(true, data.message || 'Análise concluída com sucesso! Recarregando...');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showAnalyzeResult(false, data.message || 'Erro ao analisar resposta.');
                }
            },
            error: function(xhr) {
                var msg = 'Erro de comunicação com o servidor.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                showAnalyzeResult(false, msg);
            },
            complete: function() {
                hideLoading();
                $btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i>Analisar');
            }
        });
    });

    // Batch Analysis
    $('#btnAnalyzeBatch').on('click', function() {
        var questionnaireId = $('#batchQuestionnaireId').val();
        if (!questionnaireId) {
            showAnalyzeResult(false, 'Selecione um questionário.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Analisando...');
        showLoading();

        $.ajax({
            url: baseUrl + 'ai/batch_analyze',
            type: 'POST',
            data: { type: 'corrections', questionnaire_id: questionnaireId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showAnalyzeResult(true, data.message || 'Análise em lote concluída! Recarregando...');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showAnalyzeResult(false, data.message || 'Erro na análise em lote.');
                }
            },
            error: function(xhr) {
                var msg = 'Erro de comunicação com o servidor.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                showAnalyzeResult(false, msg);
            },
            complete: function() {
                hideLoading();
                $btn.prop('disabled', false).html('<i class="fas fa-layer-group me-1"></i>Analisar Lote');
            }
        });
    });

    // Review Correction - Accept
    $(document).on('click', '.btn-accept', function() {
        var id = $(this).data('id');
        var suggestedValue = $(this).data('suggested');
        reviewCorrection(id, 'accepted', suggestedValue);
    });

    // Review Correction - Reject
    $(document).on('click', '.btn-reject', function() {
        var id = $(this).data('id');
        reviewCorrection(id, 'rejected', '');
    });

    // Toggle Edit Input
    $(document).on('click', '.btn-edit-toggle', function() {
        var id = $(this).data('id');
        var $group = $('#edit-group-' + id);
        $group.toggleClass('active');
        if ($group.hasClass('active')) {
            $group.find('input').focus();
        }
    });

    // Save Edited Value
    $(document).on('click', '.btn-edit-save', function() {
        var id = $(this).data('id');
        var appliedValue = $('#edit-value-' + id).val();
        if (!appliedValue.trim()) {
            alert('Informe um valor para aplicar.');
            return;
        }
        reviewCorrection(id, 'edited', appliedValue);
    });

    // Enter key on edit input
    $(document).on('keypress', '.edit-value-input', function(e) {
        if (e.which === 13) {
            $(this).closest('.edit-input-group').find('.btn-edit-save').click();
        }
    });

    function reviewCorrection(id, action, appliedValue) {
        var $row = $('#correction-row-' + id);
        var $actions = $('#actions-' + id);

        $actions.html('<i class="fas fa-spinner fa-spin text-muted"></i>');

        $.ajax({
            url: baseUrl + 'ai/review_correction',
            type: 'POST',
            data: {
                id: id,
                action: action,
                applied_value: appliedValue
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var statusMap = {
                        'accepted': { label: 'Aceita', badge: 'badge-status-accepted', icon: 'fa-check-circle' },
                        'rejected': { label: 'Rejeitada', badge: 'badge-status-rejected', icon: 'fa-times-circle' },
                        'edited': { label: 'Editada', badge: 'badge-status-edited', icon: 'fa-pen' }
                    };
                    var info = statusMap[action];

                    // Update status badge in the row
                    $row.find('.badge-status-pending')
                        .removeClass('badge-status-pending')
                        .addClass(info.badge)
                        .html('<i class="fas ' + info.icon + ' me-1"></i>' + info.label);

                    // Replace action buttons
                    $actions.html('<small class="text-muted">-</small>');

                    // If edited, show applied value
                    if (action === 'edited' && appliedValue) {
                        var $suggestedCell = $row.find('.suggested-value').parent();
                        if (!$suggestedCell.find('.fa-pen-fancy').length) {
                            $suggestedCell.append(
                                '<br><small class="text-muted mt-1 d-inline-block">' +
                                '<i class="fas fa-pen-fancy me-1"></i>Aplicado: <strong>' +
                                $('<span>').text(appliedValue).html() +
                                '</strong></small>'
                            );
                        }
                    }

                    // Brief highlight
                    $row.css('background', action === 'accepted' ? '#f0fdf4' : (action === 'rejected' ? '#fff5f5' : '#eff6ff'));
                    setTimeout(function() { $row.css('background', ''); }, 2000);
                } else {
                    $actions.html('<small class="text-danger">' + (data.message || 'Erro') + '</small>');
                    setTimeout(function() { location.reload(); }, 2000);
                }
            },
            error: function() {
                $actions.html('<small class="text-danger">Erro de rede</small>');
                setTimeout(function() { location.reload(); }, 2000);
            }
        });
    }
});
</script>
