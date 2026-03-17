<style>
    .inc-stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        height: 100%;
    }
    .inc-stat-icon {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: white;
    }
    .inc-filter-bar {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 1.25rem;
    }
    .inc-section-title {
        color: var(--secondary-color);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .inc-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        border-left: 4px solid #ccc;
        transition: transform 0.15s;
    }
    .inc-card:hover { transform: translateY(-1px); }
    .inc-card.severity-critical { border-left-color: #7c1d1d; }
    .inc-card.severity-high { border-left-color: #dc3545; }
    .inc-card.severity-medium { border-left-color: #fd7e14; }
    .inc-card.severity-low { border-left-color: #ffc107; }

    .badge-severity-critical { background: #7c1d1d; color: white; }
    .badge-severity-high { background: #dc3545; color: white; }
    .badge-severity-medium { background: #fd7e14; color: white; }
    .badge-severity-low { background: #ffc107; color: #333; }

    .badge-status-pending { background: #6c757d; color: white; }
    .badge-status-confirmed { background: #0d6efd; color: white; }
    .badge-status-dismissed { background: #adb5bd; color: white; }
    .badge-status-resolved { background: #8fae5d; color: white; }

    .inc-score-bar {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
    }
    .inc-score-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s;
    }
    .inc-questions-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .inc-questions-list li {
        padding: 0.25rem 0;
        font-size: 0.85rem;
        color: #555;
    }
    .inc-questions-list li::before {
        content: "\f059";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: 0.4rem;
        color: var(--primary-color);
        font-size: 0.75rem;
    }
    .inc-action-section {
        background: white;
        border-radius: 0.5rem;
        padding: 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 1.25rem;
    }
    .inc-resolution-notes {
        display: none;
        margin-top: 0.75rem;
    }
    .inc-loading {
        display: none;
        text-align: center;
        padding: 2rem;
    }
    .inc-loading .spinner-border {
        width: 2.5rem;
        height: 2.5rem;
        color: var(--primary-color);
    }
    .inc-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    .inc-empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-search-minus me-2" style="color: var(--primary-color);"></i>Detecção de Inconsistências</h2>
    <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel de IA
    </a>
</div>

<?php if (!$is_enabled): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Atenção:</strong> A funcionalidade de detecção de inconsistências está desabilitada.
    Habilite-a nas <a href="<?= base_url('ai/settings') ?>">configurações</a> para utilizar este recurso.
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="inc-filter-bar">
    <form method="get" action="<?= base_url('ai/inconsistencies') ?>" id="filterForm">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Questionário</label>
                <select name="questionnaire_id" class="form-select form-select-sm">
                    <option value="">Todos os questionários</option>
                    <?php foreach ($questionnaires as $q):
                        $q = (object)$q;
                    ?>
                        <option value="<?= (int)$q->id ?>" <?= (isset($filters['questionnaire_id']) && $filters['questionnaire_id'] == $q->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q->title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Severidade</label>
                <select name="severity" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="low" <?= (isset($filters['severity']) && $filters['severity'] === 'low') ? 'selected' : '' ?>>Baixa</option>
                    <option value="medium" <?= (isset($filters['severity']) && $filters['severity'] === 'medium') ? 'selected' : '' ?>>Média</option>
                    <option value="high" <?= (isset($filters['severity']) && $filters['severity'] === 'high') ? 'selected' : '' ?>>Alta</option>
                    <option value="critical" <?= (isset($filters['severity']) && $filters['severity'] === 'critical') ? 'selected' : '' ?>>Crítica</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Status</label>
                <select name="resolution_status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="pending" <?= (isset($filters['resolution_status']) && $filters['resolution_status'] === 'pending') ? 'selected' : '' ?>>Pendente</option>
                    <option value="confirmed" <?= (isset($filters['resolution_status']) && $filters['resolution_status'] === 'confirmed') ? 'selected' : '' ?>>Confirmado</option>
                    <option value="dismissed" <?= (isset($filters['resolution_status']) && $filters['resolution_status'] === 'dismissed') ? 'selected' : '' ?>>Descartado</option>
                    <option value="resolved" <?= (isset($filters['resolution_status']) && $filters['resolution_status'] === 'resolved') ? 'selected' : '' ?>>Resolvido</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm w-100" style="background: var(--primary-color); color: white;">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Stats Row -->
<?php
    $total = 0;
    $pending_count = 0;
    $severity_totals = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
    if (!empty($severity_counts)) {
        foreach ($severity_counts as $sc) {
            $sc = (object)$sc;
            $count = (int)$sc->count;
            $total += $count;
            if (isset($severity_totals[$sc->severity])) {
                $severity_totals[$sc->severity] += $count;
            }
            if ($sc->resolution_status === 'pending') {
                $pending_count += $count;
            }
        }
    }
?>
<div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-6">
        <div class="inc-stat-card">
            <div class="d-flex align-items-center">
                <div class="inc-stat-icon" style="background: linear-gradient(135deg, #7c1d1d, #5a1515);">
                    <i class="fas fa-skull-crossbones"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #7c1d1d;"><?= $severity_totals['critical'] ?></h4>
                    <small class="text-muted">Críticas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="inc-stat-card">
            <div class="d-flex align-items-center">
                <div class="inc-stat-icon" style="background: linear-gradient(135deg, #dc3545, #b02a37);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #dc3545;"><?= $severity_totals['high'] ?></h4>
                    <small class="text-muted">Altas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="inc-stat-card">
            <div class="d-flex align-items-center">
                <div class="inc-stat-icon" style="background: linear-gradient(135deg, #fd7e14, #d66a10);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #fd7e14;"><?= $severity_totals['medium'] ?></h4>
                    <small class="text-muted">Médias</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="inc-stat-card">
            <div class="d-flex align-items-center">
                <div class="inc-stat-icon" style="background: linear-gradient(135deg, #ffc107, #d4a106);">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: #b58c00;"><?= $severity_totals['low'] ?></h4>
                    <small class="text-muted">Baixas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="inc-stat-card">
            <div class="d-flex align-items-center">
                <div class="inc-stat-icon" style="background: linear-gradient(135deg, #6c757d, #565e64);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0" style="color: var(--secondary-color);"><?= $pending_count ?></h4>
                    <small class="text-muted">Pendentes</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Section -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="inc-action-section">
            <h6 class="inc-section-title"><i class="fas fa-search me-2"></i>Análise Individual</h6>
            <div class="input-group">
                <input type="number" id="analyzeResponseId" class="form-control" placeholder="ID da resposta do formulário" min="1">
                <button class="btn" id="btnAnalyzeIndividual" style="background: var(--primary-color); color: white;" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-microscope me-1"></i>Analisar
                </button>
            </div>
            <div id="analyzeIndividualResult" class="mt-2"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="inc-action-section">
            <h6 class="inc-section-title"><i class="fas fa-layer-group me-2"></i>Análise em Lote</h6>
            <div class="input-group">
                <select id="batchQuestionnaireId" class="form-select">
                    <option value="">Selecione um questionário</option>
                    <?php foreach ($questionnaires as $q):
                        $q = (object)$q;
                    ?>
                        <option value="<?= (int)$q->id ?>"><?= htmlspecialchars($q->title) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" id="btnBatchAnalyze" style="background: var(--secondary-color); color: white;" <?= !$is_enabled ? 'disabled' : '' ?>>
                    <i class="fas fa-cogs me-1"></i>Analisar Lote
                </button>
            </div>
            <div id="batchAnalyzeResult" class="mt-2"></div>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="incLoading" class="inc-loading">
    <div class="spinner-border" role="status"></div>
    <p class="text-muted mt-2 mb-0">Processando análise de inconsistências...</p>
</div>

<!-- Inconsistencies List -->
<h5 class="inc-section-title"><i class="fas fa-list me-2"></i>Inconsistências Detectadas (<?= $total ?>)</h5>

<?php if (empty($inconsistencies)): ?>
    <div class="inc-empty-state">
        <i class="fas fa-check-circle d-block"></i>
        <h5>Nenhuma inconsistência encontrada</h5>
        <p class="mb-0">Não há inconsistências para os filtros selecionados. Utilize a análise acima para verificar respostas.</p>
    </div>
<?php else: ?>
    <div id="inconsistenciesList">
        <?php foreach ($inconsistencies as $inc):
            $inc = (object)$inc;
            $severity = $inc->severity ?? 'low';
            $status = $inc->resolution_status ?? 'pending';
            $score = (float)($inc->consistency_score ?? 0);
            $affected = json_decode($inc->affected_questions ?? '[]', true);

            $severity_labels = ['critical' => 'Crítica', 'high' => 'Alta', 'medium' => 'Média', 'low' => 'Baixa'];
            $status_labels = ['pending' => 'Pendente', 'confirmed' => 'Confirmado', 'dismissed' => 'Descartado', 'resolved' => 'Resolvido'];
            $type_labels = [
                'contradiction' => 'Contradição',
                'outlier' => 'Valor Atípico',
                'missing_data' => 'Dados Faltantes',
                'logical_error' => 'Erro Lógico',
                'pattern_break' => 'Quebra de Padrão'
            ];

            $score_color = '#dc3545';
            if ($score >= 70) $score_color = '#8fae5d';
            elseif ($score >= 40) $score_color = '#fd7e14';
        ?>
        <div class="inc-card severity-<?= $severity ?>" data-id="<?= (int)$inc->id ?>" id="inc-<?= (int)$inc->id ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge badge-severity-<?= $severity ?> me-1">
                            <?= $severity_labels[$severity] ?? ucfirst($severity) ?>
                        </span>
                        <span class="badge badge-status-<?= $status ?>">
                            <?= $status_labels[$status] ?? ucfirst($status) ?>
                        </span>
                        <?php if (!empty($inc->inconsistency_type)): ?>
                            <span class="badge bg-light text-dark border ms-1">
                                <?= $type_labels[$inc->inconsistency_type] ?? htmlspecialchars($inc->inconsistency_type) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">
                            #<?= (int)$inc->form_response_id ?> &middot;
                            <?= htmlspecialchars($inc->questionnaire_title ?? '') ?>
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($inc->created_at)) ?>
                        </small>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-2">
                    <strong class="small text-muted">Descrição:</strong>
                    <p class="mb-1"><?= htmlspecialchars($inc->description ?? '') ?></p>
                </div>

                <!-- AI Justification -->
                <?php if (!empty($inc->ai_justification)): ?>
                <div class="mb-2">
                    <strong class="small text-muted"><i class="fas fa-robot me-1"></i>Justificativa da IA:</strong>
                    <p class="mb-1 fst-italic" style="color: #555;"><?= htmlspecialchars($inc->ai_justification) ?></p>
                </div>
                <?php endif; ?>

                <!-- Affected Questions -->
                <?php if (!empty($affected)): ?>
                <div class="mb-2">
                    <strong class="small text-muted">Perguntas Afetadas:</strong>
                    <ul class="inc-questions-list">
                        <?php foreach ($affected as $aq): ?>
                            <li><?= htmlspecialchars(is_array($aq) ? ($aq['question'] ?? $aq['title'] ?? json_encode($aq)) : $aq) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Suggested Action -->
                <?php if (!empty($inc->suggested_action)): ?>
                <div class="mb-2">
                    <strong class="small text-muted"><i class="fas fa-lightbulb me-1"></i>Ação Sugerida:</strong>
                    <p class="mb-1" style="color: var(--secondary-color);"><?= htmlspecialchars($inc->suggested_action) ?></p>
                </div>
                <?php endif; ?>

                <!-- Consistency Score -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="small text-muted">Pontuação de Consistência</strong>
                        <span class="small fw-bold" style="color: <?= $score_color ?>;"><?= number_format($score, 1) ?>%</span>
                    </div>
                    <div class="inc-score-bar">
                        <div class="inc-score-fill" style="width: <?= $score ?>%; background: <?= $score_color ?>;"></div>
                    </div>
                </div>

                <!-- Resolution Info -->
                <?php if ($status !== 'pending'): ?>
                <div class="alert alert-light py-2 px-3 mb-2" style="font-size: 0.85rem;">
                    <i class="fas fa-user-check me-1"></i>
                    <strong><?= htmlspecialchars($inc->resolved_by_name ?? 'N/A') ?></strong>
                    <?php if (!empty($inc->resolved_at)): ?>
                        em <?= date('d/m/Y H:i', strtotime($inc->resolved_at)) ?>
                    <?php endif; ?>
                    <?php if (!empty($inc->resolution_notes)): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($inc->resolution_notes) ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($status === 'pending'): ?>
                <div class="d-flex gap-2 align-items-start flex-wrap">
                    <button class="btn btn-sm btn-outline-primary btn-resolve-action" data-id="<?= (int)$inc->id ?>" data-action="confirmed">
                        <i class="fas fa-check me-1"></i>Confirmar
                    </button>
                    <button class="btn btn-sm btn-outline-secondary btn-resolve-action" data-id="<?= (int)$inc->id ?>" data-action="dismissed">
                        <i class="fas fa-times me-1"></i>Descartar
                    </button>
                    <button class="btn btn-sm btn-outline-success btn-show-resolve" data-id="<?= (int)$inc->id ?>">
                        <i class="fas fa-check-double me-1"></i>Resolver
                    </button>
                </div>
                <div class="inc-resolution-notes" id="resolveNotes-<?= (int)$inc->id ?>">
                    <textarea class="form-control form-control-sm mb-2" rows="2" placeholder="Notas de resolução (opcional)..." id="notesText-<?= (int)$inc->id ?>"></textarea>
                    <button class="btn btn-sm btn-success btn-confirm-resolve" data-id="<?= (int)$inc->id ?>">
                        <i class="fas fa-save me-1"></i>Salvar Resolução
                    </button>
                    <button class="btn btn-sm btn-light btn-cancel-resolve" data-id="<?= (int)$inc->id ?>">
                        Cancelar
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function waitForJQuery() {
    if (typeof jQuery === 'undefined') {
        return setTimeout(waitForJQuery, 50);
    }
    jQuery(function($) {
    var BASE = '<?= base_url() ?>';

    function showAlert(container, type, msg) {
        $(container).html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show py-2 small" role="alert">' +
            msg + '<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>'
        );
    }

    // Individual Analysis
    $('#btnAnalyzeIndividual').on('click', function() {
        var responseId = $('#analyzeResponseId').val();
        if (!responseId) {
            showAlert('#analyzeIndividualResult', 'warning', '<i class="fas fa-exclamation-circle me-1"></i>Informe o ID da resposta.');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Analisando...');
        $('#analyzeIndividualResult').empty();

        $.ajax({
            url: BASE + 'ai/analyze_inconsistencies',
            type: 'POST',
            data: { form_response_id: responseId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showAlert('#analyzeIndividualResult', 'success',
                        '<i class="fas fa-check-circle me-1"></i>' + (res.message || 'Análise concluída com sucesso.') +
                        (res.count !== undefined ? ' <strong>' + res.count + '</strong> inconsistência(s) detectada(s).' : '')
                    );
                    if (res.count > 0) {
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                } else {
                    showAlert('#analyzeIndividualResult', 'danger',
                        '<i class="fas fa-times-circle me-1"></i>' + (res.message || 'Erro ao processar análise.')
                    );
                }
            },
            error: function(xhr) {
                var msg = 'Erro ao comunicar com o servidor.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                showAlert('#analyzeIndividualResult', 'danger', '<i class="fas fa-times-circle me-1"></i>' + msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-microscope me-1"></i>Analisar');
            }
        });
    });

    // Batch Analysis
    $('#btnBatchAnalyze').on('click', function() {
        var questionnaireId = $('#batchQuestionnaireId').val();
        if (!questionnaireId) {
            showAlert('#batchAnalyzeResult', 'warning', '<i class="fas fa-exclamation-circle me-1"></i>Selecione um questionário.');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processando...');
        $('#batchAnalyzeResult').empty();
        $('#incLoading').show();

        $.ajax({
            url: BASE + 'ai/batch_analyze',
            type: 'POST',
            data: { type: 'inconsistencies', questionnaire_id: questionnaireId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showAlert('#batchAnalyzeResult', 'success',
                        '<i class="fas fa-check-circle me-1"></i>' + (res.message || 'Análise em lote concluída.') +
                        (res.count !== undefined ? ' <strong>' + res.count + '</strong> inconsistência(s) detectada(s).' : '')
                    );
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    showAlert('#batchAnalyzeResult', 'danger',
                        '<i class="fas fa-times-circle me-1"></i>' + (res.message || 'Erro ao processar análise em lote.')
                    );
                }
            },
            error: function(xhr) {
                var msg = 'Erro ao comunicar com o servidor.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                showAlert('#batchAnalyzeResult', 'danger', '<i class="fas fa-times-circle me-1"></i>' + msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-cogs me-1"></i>Analisar Lote');
                $('#incLoading').hide();
            }
        });
    });

    // Resolve Actions (Confirmar / Descartar)
    $(document).on('click', '.btn-resolve-action', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var action = $btn.data('action');
        $btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-1"></span>');

        $.ajax({
            url: BASE + 'ai/resolve_inconsistency',
            type: 'POST',
            data: { id: id, action: action },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var $card = $('#inc-' + id);
                    var statusLabels = { confirmed: 'Confirmado', dismissed: 'Descartado', resolved: 'Resolvido' };
                    $card.find('.badge-status-pending')
                        .removeClass('badge-status-pending')
                        .addClass('badge-status-' + action)
                        .text(statusLabels[action] || action);
                    $card.find('.btn-resolve-action, .btn-show-resolve, .inc-resolution-notes').fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(res.message || 'Erro ao atualizar inconsistência.');
                    $btn.prop('disabled', false).find('.spinner-border').remove();
                }
            },
            error: function() {
                alert('Erro ao comunicar com o servidor.');
                $btn.prop('disabled', false).find('.spinner-border').remove();
            }
        });
    });

    // Show Resolve Notes
    $(document).on('click', '.btn-show-resolve', function() {
        var id = $(this).data('id');
        $('#resolveNotes-' + id).slideDown(200);
    });

    // Cancel Resolve
    $(document).on('click', '.btn-cancel-resolve', function() {
        var id = $(this).data('id');
        $('#resolveNotes-' + id).slideUp(200);
    });

    // Confirm Resolve with Notes
    $(document).on('click', '.btn-confirm-resolve', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var notes = $('#notesText-' + id).val();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Salvando...');

        $.ajax({
            url: BASE + 'ai/resolve_inconsistency',
            type: 'POST',
            data: { id: id, action: 'resolved', resolution_notes: notes },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var $card = $('#inc-' + id);
                    $card.find('.badge-status-pending')
                        .removeClass('badge-status-pending')
                        .addClass('badge-status-resolved')
                        .text('Resolvido');
                    $card.find('.btn-resolve-action, .btn-show-resolve, .inc-resolution-notes').fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(res.message || 'Erro ao resolver inconsistência.');
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Salvar Resolução');
                }
            },
            error: function() {
                alert('Erro ao comunicar com o servidor.');
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Salvar Resolução');
            }
        });
    });
    });
})();
</script>
