<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Resposta #<?= $response->id ?></h2>
            <div>
                <a href="<?= base_url('responses/export?form_id=' . $response->id) ?>" 
                   class="btn btn-outline-success me-2">
                    <i class="fas fa-download me-2"></i>
                    Exportar
                </a>
                <a href="<?= base_url('responses') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Informações do Formulário -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações Gerais</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Questionário:</strong><br>
                        <span class="text-primary"><?= $questionnaire->title ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Aplicador:</strong><br>
                        <?= $applied_by->full_name ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Data/Hora Conclusão:</strong><br>
                        <?= $response->completed_at ? date('d/m/Y H:i:s', strtotime($response->completed_at)) : 'Não concluído' ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Status de Sincronização:</strong><br>
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
                    </div>
                </div>
                
                <?php if ($response->respondent_name || $response->respondent_email): ?>
                <hr>
                <div class="row">
                    <?php if ($response->respondent_name): ?>
                    <div class="col-md-6">
                        <strong>Nome do Respondente:</strong><br>
                        <?= $response->respondent_name ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($response->respondent_email): ?>
                    <div class="col-md-6">
                        <strong>Email do Respondente:</strong><br>
                        <?= $response->respondent_email ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($response->consent_given): ?>
                <hr>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Consentimento concedido</strong> - O respondente autorizou o uso dos dados conforme LGPD.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Respostas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Respostas do Questionário</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($answers)): ?>
                    <?php foreach ($answers as $index => $answer): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="text-primary mb-0">Pergunta <?= $index + 1 ?></h6>
                            <span class="badge bg-secondary"><?= ucfirst($answer->question_type) ?></span>
                        </div>
                        
                        <p class="mb-2"><strong><?= $answer->question_text ?></strong></p>
                        
                        <div class="ms-3">
                            <?php if ($answer->response_text): ?>
                                <div class="bg-light p-3 rounded">
                                    <?= nl2br(htmlspecialchars($answer->response_text)) ?>
                                </div>
                            <?php elseif ($answer->response_number !== null): ?>
                                <div class="bg-light p-3 rounded">
                                    <strong><?= number_format($answer->response_number, 2) ?></strong>
                                </div>
                            <?php elseif ($answer->response_date): ?>
                                <div class="bg-light p-3 rounded">
                                    <strong><?= date('d/m/Y', strtotime($answer->response_date)) ?></strong>
                                </div>
                            <?php elseif ($answer->response_datetime): ?>
                                <div class="bg-light p-3 rounded">
                                    <strong><?= date('d/m/Y H:i', strtotime($answer->response_datetime)) ?></strong>
                                </div>
                            <?php elseif ($answer->selected_options): ?>
                                <div class="bg-light p-3 rounded">
                                    <?php 
                                    $options = json_decode($answer->selected_options);
                                    if (is_array($options)):
                                        foreach ($options as $option): ?>
                                            <span class="badge bg-primary me-1"><?= htmlspecialchars($option) ?></span>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">
                                    <em>Não respondido</em>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma resposta encontrada para este formulário.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Localização -->
        <?php if ($response->latitude && $response->longitude): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Localização</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-map-marker-alt fa-2x text-success mb-2"></i>
                    <br>
                    <strong>Coordenadas GPS</strong>
                </div>
                
                <div class="text-center">
                    <code><?= number_format($response->latitude, 6) ?>, <?= number_format($response->longitude, 6) ?></code>
                </div>
                
                <?php if ($response->location_name): ?>
                <div class="text-center mt-2">
                    <small class="text-muted"><?= $response->location_name ?></small>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="https://maps.google.com/?q=<?= $response->latitude ?>,<?= $response->longitude ?>" 
                       target="_blank" class="btn btn-success btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Ver no Google Maps
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Foto -->
        <?php if ($response->photo_path): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Evidência Fotográfica</h5>
            </div>
            <div class="card-body text-center">
                <img src="<?= base_url('uploads/photos/' . $response->photo_path) ?>" 
                     class="img-fluid rounded mb-3" alt="Foto capturada" 
                     style="max-height: 200px; cursor: pointer;"
                     onclick="showFullPhoto(this.src)">
                <br>
                <button type="button" class="btn btn-outline-primary btn-sm" 
                        onclick="showFullPhoto('<?= base_url('uploads/photos/' . $response->photo_path) ?>')">
                    <i class="fas fa-expand me-1"></i>
                    Ver em Tamanho Real
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Metadados -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Metadados</h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>ID da Resposta:</strong> <?= $response->id ?><br>
                    <strong>Data de Criação:</strong> <?= date('d/m/Y H:i:s', strtotime($response->created_at)) ?><br>
                    <?php if ($response->started_at): ?>
                    <strong>Iniciado em:</strong> <?= date('d/m/Y H:i:s', strtotime($response->started_at)) ?><br>
                    <?php endif; ?>
                    <?php if ($response->completed_at): ?>
                    <strong>Concluído em:</strong> <?= date('d/m/Y H:i:s', strtotime($response->completed_at)) ?><br>
                    <?php endif; ?>
                    <strong>Versão do Questionário:</strong> <?= $questionnaire->version ?><br>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal para foto em tamanho real -->
<div class="modal fade" id="fullPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidência Fotográfica - Tamanho Real</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fullPhotoImage" src="" class="img-fluid" alt="Foto em tamanho real">
            </div>
        </div>
    </div>
</div>

<script>
function showFullPhoto(photoUrl) {
    document.getElementById('fullPhotoImage').src = photoUrl;
    new bootstrap.Modal(document.getElementById('fullPhotoModal')).show();
}
</script>