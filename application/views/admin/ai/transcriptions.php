<style>
    .trans-stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s;
        height: 100%;
    }
    .trans-stat-card:hover { transform: translateY(-2px); }
    .trans-stat-icon {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: white;
    }
    .trans-section-title {
        color: var(--secondary-color);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .trans-table th {
        background: var(--secondary-color);
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
        border: none;
    }
    .trans-table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .trans-table tbody tr {
        transition: background 0.15s;
    }
    .trans-table tbody tr:hover {
        background: #f8f9fa;
    }
    .badge-status-pending { background: #6c757d; color: white; }
    .badge-status-processing { background: #0d6efd; color: white; }
    .badge-status-completed { background: #8fae5d; color: white; }
    .badge-status-error { background: #dc3545; color: white; }
    .trans-detail-row {
        display: none;
        background: #fafbfc;
    }
    .trans-detail-row td {
        padding: 1rem 1.25rem !important;
    }
    .trans-detail-row .detail-inner {
        border-left: 3px solid var(--primary-color);
        padding-left: 1rem;
    }
    .upload-area {
        background: white;
        border: 2px dashed #ccc;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: border-color 0.2s;
    }
    .upload-area:hover {
        border-color: var(--primary-color);
    }
    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-microphone me-2" style="color: var(--primary-color);"></i>Transcrições de Áudio</h2>
    <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel de IA
    </a>
</div>

<?php if (!$is_enabled): ?>
<div class="alert alert-info border-0 mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Recurso desabilitado.</strong> A transcrição de áudio está desativada. Ative-a nas
    <a href="<?= base_url('ai/settings') ?>" class="alert-link">configurações de IA</a> para utilizar esta funcionalidade.
</div>
<?php endif; ?>

<!-- Status Summary Cards -->
<?php
    $counts = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'error' => 0];
    if (!empty($status_counts)) {
        foreach ($status_counts as $sc) {
            $sc = (object)$sc;
            if (isset($counts[$sc->status])) {
                $counts[$sc->status] = (int)$sc->count;
            }
        }
    }
?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $counts['pending'] ?></h3>
                    <small class="text-muted">Pendentes</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $counts['processing'] ?></h3>
                    <small class="text-muted">Processando</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #8fae5d, #6d8a45);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $counts['completed'] ?></h3>
                    <small class="text-muted">Concluídas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #dc3545, #b02a37);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $counts['error'] ?></h3>
                    <small class="text-muted">Erros</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Form -->
<?php if ($is_enabled): ?>
<div class="upload-area">
    <h5 class="trans-section-title"><i class="fas fa-upload me-2"></i>Enviar Áudio para Transcrição</h5>
    <form id="uploadForm" action="<?= base_url('ai/upload_audio') ?>" method="post" enctype="multipart/form-data">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1 small fw-bold">Arquivo de Áudio <span class="text-danger">*</span></label>
                <input type="file" name="audio_file" class="form-control form-control-sm" accept="audio/*" required>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-bold">Resposta do Formulário</label>
                <select name="form_response_id" class="form-select form-select-sm">
                    <option value="">-- Opcional --</option>
                    <?php if (!empty($form_responses)): ?>
                        <?php foreach ($form_responses as $fr): ?>
                            <option value="<?= (int)($fr->id ?? $fr['id'] ?? 0) ?>">
                                <?= htmlspecialchars($fr->label ?? $fr['label'] ?? '#' . ($fr->id ?? $fr['id'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-bold">Pergunta</label>
                <select name="question_id" class="form-select form-select-sm">
                    <option value="">-- Opcional --</option>
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $q): ?>
                            <option value="<?= (int)($q->id ?? $q['id'] ?? 0) ?>">
                                <?= htmlspecialchars($q->label ?? $q['label'] ?? $q->question_text ?? $q['question_text'] ?? '#' . ($q->id ?? $q['id'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-cloud-upload-alt me-1"></i>Enviar
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Transcriptions Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($transcriptions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-microphone-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-1">Nenhuma transcrição encontrada.</p>
                <?php if ($is_enabled): ?>
                    <small class="text-muted">Envie um arquivo de áudio acima para começar.</small>
                <?php else: ?>
                    <small class="text-muted">Ative o recurso nas configurações para começar.</small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 trans-table">
                    <thead>
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Arquivo</th>
                            <th>Duração</th>
                            <th class="text-center">Status</th>
                            <th>Data</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transcriptions as $t):
                            $t = (object)$t;
                            $status = $t->status ?? 'pending';
                            $duration = (int)($t->audio_duration_seconds ?? 0);
                            $dur_min = floor($duration / 60);
                            $dur_sec = $duration % 60;
                            $dur_display = $dur_min > 0
                                ? $dur_min . 'min ' . str_pad($dur_sec, 2, '0', STR_PAD_LEFT) . 's'
                                : $dur_sec . 's';
                            $filename = basename($t->audio_file_path ?? 'N/A');

                            $status_labels = [
                                'pending' => 'Pendente',
                                'processing' => 'Processando',
                                'completed' => 'Concluída',
                                'error' => 'Erro',
                            ];
                            $status_icons = [
                                'pending' => 'fa-clock',
                                'processing' => 'fa-spinner fa-spin',
                                'completed' => 'fa-check-circle',
                                'error' => 'fa-times-circle',
                            ];
                        ?>
                        <tr class="trans-row-clickable" data-trans-id="<?= (int)$t->id ?>" style="cursor: pointer;" title="Clique para expandir detalhes">
                            <td class="ps-3 fw-semibold">#<?= (int)$t->id ?></td>
                            <td>
                                <i class="fas fa-file-audio text-muted me-1"></i>
                                <span title="<?= htmlspecialchars($t->audio_file_path ?? '') ?>"><?= htmlspecialchars($filename) ?></span>
                            </td>
                            <td>
                                <?php if ($duration > 0): ?>
                                    <i class="fas fa-stopwatch text-muted me-1"></i><?= $dur_display ?>
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-status-<?= $status ?> px-2 py-1">
                                    <i class="fas <?= $status_icons[$status] ?? 'fa-circle' ?> me-1"></i><?= $status_labels[$status] ?? ucfirst($status) ?>
                                </span>
                                <?php if ($status === 'error' && !empty($t->error_message)): ?>
                                    <br><small class="text-danger" title="<?= htmlspecialchars($t->error_message) ?>"><?= htmlspecialchars(mb_substr($t->error_message, 0, 40)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-nowrap"><?= date('d/m/Y', strtotime($t->created_at)) ?></span>
                                <br><small class="text-muted"><?= date('H:i:s', strtotime($t->created_at)) ?></small>
                                <?php if (!empty($t->processed_at)): ?>
                                    <br><small class="text-muted" title="Processado em <?= date('d/m/Y H:i:s', strtotime($t->processed_at)) ?>">
                                        <i class="fas fa-cog me-1"></i><?= date('H:i:s', strtotime($t->processed_at)) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary btn-action toggle-detail-btn" data-trans-id="<?= (int)$t->id ?>" title="Expandir detalhes" onclick="event.stopPropagation();">
                                    <i class="fas fa-chevron-down" id="chevron-<?= (int)$t->id ?>"></i>
                                </button>
                                <?php if ($status !== 'processing'): ?>
                                <button class="btn btn-sm btn-outline-primary btn-action btn-reprocess" data-id="<?= (int)$t->id ?>" title="Reprocessar" onclick="event.stopPropagation();">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Detail Row -->
                        <tr class="trans-detail-row" id="detail-<?= (int)$t->id ?>">
                            <td colspan="6">
                                <div class="detail-inner">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex gap-3 mb-2">
                                                <?php if (!empty($t->language)): ?>
                                                    <small><strong>Idioma:</strong> <?= htmlspecialchars($t->language) ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($t->confidence_score)): ?>
                                                    <small><strong>Confiança:</strong> <?= number_format((float)$t->confidence_score * 100, 1) ?>%</small>
                                                <?php endif; ?>
                                                <?php if (!empty($t->processed_by_name)): ?>
                                                    <small><strong>Processado por:</strong> <?= htmlspecialchars($t->processed_by_name) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold mb-1">
                                                <i class="fas fa-robot me-1"></i>Transcrição Original
                                            </label>
                                            <div class="form-control form-control-sm bg-light" style="min-height: 100px; max-height: 200px; overflow-y: auto; font-size: 0.85rem; white-space: pre-wrap;">
                                                <?= htmlspecialchars($t->transcription_text ?? 'Aguardando processamento...') ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold mb-1">
                                                <i class="fas fa-pen me-1"></i>Transcrição Editada
                                            </label>
                                            <textarea class="form-control form-control-sm edited-text" id="edited-<?= (int)$t->id ?>"
                                                      style="min-height: 100px; max-height: 200px; font-size: 0.85rem;"
                                                      placeholder="Edite a transcrição aqui..."><?= htmlspecialchars($t->transcription_edited ?? $t->transcription_text ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2">
                                            <button class="btn btn-sm btn-success btn-save-edit" data-id="<?= (int)$t->id ?>">
                                                <i class="fas fa-save me-1"></i>Salvar Edição
                                            </button>
                                            <?php if ($status !== 'processing'): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-reprocess" data-id="<?= (int)$t->id ?>">
                                                <i class="fas fa-redo me-1"></i>Reprocessar
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
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
$(document).ready(function() {

    // Toggle detail rows
    $('.trans-row-clickable').on('click', function() {
        var id = $(this).data('trans-id');
        toggleDetail(id);
    });

    $('.toggle-detail-btn').on('click', function() {
        var id = $(this).data('trans-id');
        toggleDetail(id);
    });

    function toggleDetail(id) {
        var $detail = $('#detail-' + id);
        var $chevron = $('#chevron-' + id);
        $detail.slideToggle(200);
        $chevron.toggleClass('fa-chevron-down fa-chevron-up');
    }

    // Save edited transcription
    $(document).on('click', '.btn-save-edit', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var editedText = $('#edited-' + id).val();
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Salvando...');

        $.ajax({
            url: '<?= base_url('ai/save_transcription_edit') ?>',
            method: 'POST',
            data: {
                id: id,
                transcription_edited: editedText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $btn.html('<i class="fas fa-check me-1"></i>Salvo!').removeClass('btn-success').addClass('btn-outline-success');
                    setTimeout(function() {
                        $btn.html(originalHtml).removeClass('btn-outline-success').addClass('btn-success');
                    }, 2000);
                } else {
                    alert('Erro ao salvar: ' + (response.message || 'Erro desconhecido'));
                }
            },
            error: function() {
                alert('Erro de rede ao salvar a edição.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Reprocess transcription
    $(document).on('click', '.btn-reprocess', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var originalHtml = $btn.html();

        if (!confirm('Deseja reprocessar a transcrição #' + id + '?')) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Processando...');

        $.ajax({
            url: '<?= base_url('ai/process_transcription') ?>',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $btn.html('<i class="fas fa-check me-1"></i>Enviado!');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Erro ao reprocessar: ' + (response.message || 'Erro desconhecido'));
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                alert('Erro de rede ao reprocessar.');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

});
</script>
