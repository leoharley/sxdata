<style>
    .trans-stat-card {
        background: white; border-radius: 0.75rem; padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08); height: 100%;
    }
    .trans-stat-icon {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: white;
    }
    .trans-section-title {
        color: var(--secondary-color); font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem; margin-bottom: 1.25rem;
    }
    .trans-table th {
        background: var(--secondary-color); color: white;
        font-weight: 600; font-size: 0.8rem; white-space: nowrap; border: none;
    }
    .trans-table td { vertical-align: middle; font-size: 0.85rem; }
    .trans-table tbody tr:hover { background: #f8f9fa; }
    .badge-conf-high { background: #8fae5d; color: white; }
    .badge-conf-med { background: #f0ad4e; color: white; }
    .badge-conf-low { background: #dc3545; color: white; }
    .badge-edited { background: #fd7e14; color: white; }
    .badge-source-app { background: #0dcaf0; color: #333; }
    .badge-source-upload { background: #6c757d; color: white; }
    .filter-bar {
        background: white; border-radius: 0.5rem; padding: 1rem 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 1.25rem;
    }
    .upload-area {
        background: white; border: 2px dashed #ccc; border-radius: 0.75rem;
        padding: 1.5rem; margin-bottom: 1.5rem;
    }
    .upload-area:hover { border-color: var(--primary-color); }
    .text-preview { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pagination-sm .page-link { font-size: 0.8rem; padding: 0.25rem 0.5rem; }
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
    <strong>Recurso desabilitado.</strong> Ative nas <a href="<?= base_url('ai/settings') ?>" class="alert-link">configurações de IA</a>.
</div>
<?php endif; ?>

<!-- Stats Cards -->
<?php
    $st = $stats ?? (object)array('total' => 0, 'avg_confidence' => 0, 'edited_count' => 0, 'total_seconds' => 0);
    $totalSecs = (int)($st->total_seconds ?? 0);
    $hours = floor($totalSecs / 3600);
    $mins = floor(($totalSecs % 3600) / 60);
    $editedPct = ($st->total > 0) ? round(($st->edited_count / $st->total) * 100) : 0;
    $avgConf = round(($st->avg_confidence ?? 0) * 100);
?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, var(--primary-color), #1a2847);">
                    <i class="fas fa-file-audio"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $st->total ?></h3>
                    <small class="text-muted">Total de Transcrições</small>
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
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $avgConf ?>%</h3>
                    <small class="text-muted">Confiança Média</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #fd7e14, #d66a10);">
                    <i class="fas fa-pen"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $editedPct ?>%</h3>
                    <small class="text-muted">Editadas pelo Entrevistador</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="trans-stat-card">
            <div class="d-flex align-items-center">
                <div class="trans-stat-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="ms-3">
                    <h3 class="mb-0" style="color: var(--secondary-color);"><?= $hours ?>h <?= $mins ?>m</h3>
                    <small class="text-muted">Tempo Total de Áudio</small>
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
                    <?php if (!empty($form_responses)): foreach ($form_responses as $fr): ?>
                        <option value="<?= (int)$fr->id ?>"><?= htmlspecialchars($fr->label) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-bold">Pergunta</label>
                <select name="question_id" class="form-select form-select-sm">
                    <option value="">-- Opcional --</option>
                    <?php if (!empty($questions)): foreach ($questions as $q): ?>
                        <option value="<?= (int)$q->id ?>"><?= htmlspecialchars($q->label) ?></option>
                    <?php endforeach; endif; ?>
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

<!-- Filters -->
<div class="filter-bar">
    <form method="get" action="<?= base_url('ai/transcriptions') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Questionário</label>
                <select name="questionnaire_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php if (!empty($filter_questionnaires)): foreach ($filter_questionnaires as $q): ?>
                        <option value="<?= $q->id ?>" <?= ($q->id == $filter_questionnaire_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q->title) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Aplicador</label>
                <select name="applicator_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php if (!empty($filter_applicators)): foreach ($filter_applicators as $a): ?>
                        <option value="<?= $a->id ?>" <?= ($a->id == $filter_applicator_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a->applicator_name) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Data Início</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_from ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Data Fim</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_to ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Buscar</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Texto..." value="<?= htmlspecialchars($filter_search ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Transcriptions Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($transcriptions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-microphone-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-1">Nenhuma transcrição encontrada.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 trans-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Data/Hora</th>
                            <th>Questionário</th>
                            <th>Pergunta</th>
                            <th>Texto Transcrito (IA)</th>
                            <th>Texto Editado</th>
                            <th class="text-center">Confiança</th>
                            <th>Aplicador</th>
                            <th class="text-center">Duração</th>
                            <th class="text-center">Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transcriptions as $t):
                            $t = (object)$t;
                            $conf = $t->confidence_score !== null ? round((float)$t->confidence_score * 100) : null;
                            $confClass = $conf === null ? '' : ($conf >= 85 ? 'badge-conf-high' : ($conf >= 70 ? 'badge-conf-med' : 'badge-conf-low'));
                            $wasEdited = !empty($t->edited_text) && $t->edited_text !== $t->transcription_text;
                            $displayDate = !empty($t->timestamp_app) ? $t->timestamp_app : $t->created_at;
                            $source = $t->source ?? 'upload';
                            $durSecs = (int)($t->recording_duration_secs ?? $t->audio_duration_seconds ?? 0);
                        ?>
                        <tr style="cursor:pointer" onclick="showDetail(this)" data-transcription='<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>'>
                            <td class="ps-3">
                                <span class="text-nowrap"><?= date('d/m/Y', strtotime($displayDate)) ?></span>
                                <br><small class="text-muted"><?= date('H:i', strtotime($displayDate)) ?></small>
                            </td>
                            <td>
                                <span class="text-preview" title="<?= htmlspecialchars($t->questionnaire_title ?? '') ?>">
                                    <?= htmlspecialchars(mb_strimwidth($t->questionnaire_title ?? 'N/A', 0, 25, '...')) ?>
                                </span>
                            </td>
                            <td>
                                <?php $qtext = $t->resolved_question_text ?? $t->question_text ?? ''; ?>
                                <span class="text-preview" title="<?= htmlspecialchars($qtext) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($qtext ?: '-', 0, 30, '...')) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-preview" title="<?= htmlspecialchars($t->transcription_text ?? '') ?>">
                                    <?= htmlspecialchars(mb_strimwidth($t->transcription_text ?? '-', 0, 40, '...')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($wasEdited): ?>
                                    <span class="badge badge-edited me-1">Editado</span>
                                    <span class="text-preview" title="<?= htmlspecialchars($t->edited_text) ?>">
                                        <?= htmlspecialchars(mb_strimwidth($t->edited_text, 0, 30, '...')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($conf !== null): ?>
                                    <span class="badge <?= $confClass ?>"><?= $conf ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($t->applicator_name ?? 'N/A') ?></td>
                            <td class="text-center"><?= $durSecs > 0 ? $durSecs . 's' : '-' ?></td>
                            <td class="text-center">
                                <span class="badge badge-source-<?= $source ?>"><?= $source === 'app' ? 'App' : 'Upload' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted"><?= $pagination['total'] ?> transcrições encontradas</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                            $queryParams = array_filter(array(
                                'questionnaire_id' => $filter_questionnaire_id,
                                'applicator_id' => $filter_applicator_id,
                                'date_from' => $filter_date_from,
                                'date_to' => $filter_date_to,
                                'search' => $filter_search,
                            ));
                            $totalPages = $pagination['total_pages'];
                            $currentPage = $pagination['page'];
                        ?>
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?>&<?= http_build_query($queryParams) ?>">&laquo;</a></li>
                        <?php endif; ?>
                        <?php
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);
                        ?>
                        <?php for ($p = $start; $p <= $end; $p++): ?>
                        <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query($queryParams) ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?>&<?= http_build_query($queryParams) ?>">&raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="transcriptionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #1a2847); color: white;">
                <h5 class="modal-title"><i class="fas fa-microphone me-2"></i>Detalhe da Transcrição</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transcriptionDetailBody"></div>
        </div>
    </div>
</div>

<script>
function showDetail(row) {
    var t = JSON.parse(row.getAttribute('data-transcription'));
    var conf = t.confidence_score ? (parseFloat(t.confidence_score) * 100).toFixed(0) + '%' : 'N/A';
    var wasEdited = t.edited_text && t.edited_text !== t.transcription_text;

    var html = '<div class="row g-4">';

    // Info
    html += '<div class="col-md-5">';
    html += '<h6 style="color: var(--secondary-color);"><i class="fas fa-info-circle me-1"></i>Informações</h6>';
    html += '<table class="table table-sm mb-0">';
    html += '<tr><th class="text-muted" style="width:40%">Questionário</th><td>' + escapeHtml(t.questionnaire_title || 'N/A') + '</td></tr>';
    html += '<tr><th class="text-muted">Pergunta</th><td>' + escapeHtml(t.resolved_question_text || t.question_text || 'N/A') + '</td></tr>';
    html += '<tr><th class="text-muted">Aplicador</th><td>' + escapeHtml(t.applicator_name || 'N/A') + '</td></tr>';
    html += '<tr><th class="text-muted">Data/Hora</th><td>' + escapeHtml(t.timestamp_app || t.created_at || '') + '</td></tr>';
    html += '<tr><th class="text-muted">Confiança</th><td>' + conf + '</td></tr>';
    html += '<tr><th class="text-muted">Idioma</th><td>' + escapeHtml(t.language || 'N/A') + '</td></tr>';
    html += '<tr><th class="text-muted">Duração</th><td>' + (t.recording_duration_secs || t.audio_duration_seconds || 0) + 's</td></tr>';
    html += '<tr><th class="text-muted">Origem</th><td>' + (t.source === 'app' ? '<span class="badge badge-source-app">App</span>' : '<span class="badge badge-source-upload">Upload</span>') + '</td></tr>';
    html += '</table>';
    html += '</div>';

    // Textos
    html += '<div class="col-md-7">';
    html += '<h6 style="color: var(--secondary-color);"><i class="fas fa-robot me-1"></i>Texto Original (IA)</h6>';
    html += '<div class="p-3 rounded mb-3" style="background: #f8f9fa; white-space: pre-wrap; max-height: 200px; overflow-y: auto; font-size: 0.9rem;">' + escapeHtml(t.transcription_text || 'Sem texto.') + '</div>';

    if (wasEdited) {
        html += '<h6 style="color: var(--secondary-color);"><i class="fas fa-pen me-1"></i>Texto Editado <span class="badge badge-edited">Modificado</span></h6>';
        html += '<div class="p-3 rounded" style="background: #fff8f0; border: 1px solid #fd7e14; white-space: pre-wrap; max-height: 200px; overflow-y: auto; font-size: 0.9rem;">' + escapeHtml(t.edited_text) + '</div>';
    }

    html += '</div></div>';

    document.getElementById('transcriptionDetailBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('transcriptionDetailModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
