<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Respostas dos Formulários</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </button>
                <a href="<?= base_url('responses/export?' . http_build_query($filters)) ?>" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>
                    Exportar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="collapse <?= !empty($filters) ? 'show' : '' ?>" id="filtersCollapse">
    <div class="card mb-4">
        <div class="card-body">
            <?= form_open('responses', ['method' => 'GET']) ?>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Questionário</label>
                    <select class="form-select" name="questionnaire_id">
                        <option value="">Todos</option>
                        <?php foreach ($questionnaires as $questionnaire): ?>
                        <option value="<?= $questionnaire->id ?>" 
                                <?= set_select('questionnaire_id', $questionnaire->id, 
                                   isset($filters['questionnaire_id']) && $filters['questionnaire_id'] == $questionnaire->id) ?>>
                            <?= $questionnaire->title ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Aplicador</label>
                    <select class="form-select" name="applied_by">
                        <option value="">Todos</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user->id ?>" 
                                <?= set_select('applied_by', $user->id, 
                                   isset($filters['applied_by']) && $filters['applied_by'] == $user->id) ?>>
                            <?= $user->full_name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?= $filters['date_from'] ?? '' ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?= $filters['date_to'] ?? '' ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status Sync</label>
                    <select class="form-select" name="sync_status">
                        <option value="">Todos</option>
                        <option value="synced" <?= set_select('sync_status', 'synced', 
                               isset($filters['sync_status']) && $filters['sync_status'] == 'synced') ?>>
                            Sincronizado
                        </option>
                        <option value="pending" <?= set_select('sync_status', 'pending', 
                               isset($filters['sync_status']) && $filters['sync_status'] == 'pending') ?>>
                            Pendente
                        </option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Tabela de Respostas -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($responses)): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Questionário</th>
                        <th>Respondente</th>
                        <th>Aplicador</th>
                        <th>Local</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response): ?>
                    <tr>
                        <td><strong>#<?= $response->id ?></strong></td>
                        <td>
                            <strong><?= $response->questionnaire_title ?></strong>
                        </td>
                        <td>
                            <?php if ($response->respondent_name): ?>
                                <?= $response->respondent_name ?>
                                <?php if ($response->respondent_email): ?>
                                    <br><small class="text-muted"><?= $response->respondent_email ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Não informado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $response->applied_by_name ?></td>
                        <td>
                            <?php if ($response->location_name): ?>
                                <?= $response->location_name ?>
                                <?php if ($response->latitude && $response->longitude): ?>
                                    <br><small class="text-muted">
                                        <?= number_format($response->latitude, 4) ?>, 
                                        <?= number_format($response->longitude, 4) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Não informado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($response->completed_at): ?>
                                <small><?= date('d/m/Y H:i', strtotime($response->completed_at)) ?></small>
                            <?php else: ?>
                                <span class="text-warning">Incompleto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status_classes = [
                                'synced' => 'success',
                                'pending' => 'warning',
                                'error' => 'danger'
                            ];
                            $status_texts = [
                                'synced' => 'Sincronizado',
                                'pending' => 'Pendente',
                                'error' => 'Erro'
                            ];
                            $class = $status_classes[$response->sync_status] ?? 'secondary';
                            $text = $status_texts[$response->sync_status] ?? $response->sync_status;
                            ?>
                            <span class="badge bg-<?= $class ?>"><?= $text ?></span>
                            
                            <?php if ($response->consent_given): ?>
                                <br><small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Consentimento
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('responses/view/' . $response->id) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($response->latitude && $response->longitude): ?>
                                <a href="https://maps.google.com/?q=<?= $response->latitude ?>,<?= $response->longitude ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-success" title="Ver no Mapa">
                                    <i class="fas fa-map-marker-alt"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($response->photo_path): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        onclick="showPhoto('<?= base_url('uploads/photos/' . $response->photo_path) ?>')" 
                                        title="Ver Foto">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhuma resposta encontrada</h5>
            <p class="text-muted">Tente ajustar os filtros ou aguarde novas respostas serem coletadas.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para exibir foto -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Capturada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="photoImage" src="" class="img-fluid" alt="Foto capturada">
            </div>
        </div>
    </div>
</div>

<script>
function showPhoto(photoUrl) {
    document.getElementById('photoImage').src = photoUrl;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}
</script>