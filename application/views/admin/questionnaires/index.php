<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Questionários</h2>
            <a href="<?= base_url('questionnaires/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Novo Questionário
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Perguntas</th>
                        <th>Respostas</th>
                        <th>Aplicadores</th>
                        <th>Criado por</th>
                        <th>Data Criação</th>
                        <th>Última Resposta</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questionnaires as $questionnaire): ?>
                    <tr>
                        <td>
                            <strong><?= $questionnaire->title ?></strong>
                            <?php if ($questionnaire->description): ?>
                                <br><small class="text-muted"><?= character_limiter($questionnaire->description, 60) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status_class = $questionnaire->status == 'active' ? 'success' : 'warning';
                            $status_text = $questionnaire->status == 'active' ? 'Ativo' : 'Pausado';
                            ?>
                            <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $questionnaire->question_count ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= $questionnaire->response_count ?></span>
                        </td>
                        <td>
                            <?php 
                            // Exibir informações sobre aplicadores
                            if (empty($questionnaire->aplicadores) || $questionnaire->aplicadores === null):
                            ?>
                                <span class="badge bg-success" title="Todos os aplicadores podem usar este questionário">
                                    <i class="fas fa-users me-1"></i>
                                    Todos
                                </span>
                            <?php 
                            else:
                                $aplicadores_ids = json_decode($questionnaire->aplicadores, true);
                                if (is_array($aplicadores_ids)):
                                    $count = count($aplicadores_ids);
                            ?>
                                <span class="badge bg-info" title="<?= $count ?> aplicadores específicos selecionados">
                                    <i class="fas fa-user me-1"></i>
                                    <?= $count ?>
                                </span>
                            <?php 
                                endif;
                            endif; 
                            ?>
                        </td>
                        <td><?= $questionnaire->created_by_name ?></td>
                        <td>
                            <small><?= date('d/m/Y', strtotime($questionnaire->created_at)) ?></small>
                        </td>
                        <td>
                            <?php if ($questionnaire->last_response): ?>
                                <small><?= date('d/m/Y H:i', strtotime($questionnaire->last_response)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('questionnaires/edit/' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('questionnaires/duplicate/' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Duplicar">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <a href="<?= base_url('responses?questionnaire_id=' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-success" title="Ver Respostas">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <?php if ($questionnaire->response_count == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteQuestionnaire(<?= $questionnaire->id ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteQuestionnaire(id) {
    if (confirm('Tem certeza que deseja excluir este questionário? Esta ação não pode ser desfeita.')) {
        window.location.href = '<?= base_url('questionnaires/delete/') ?>' + id;
    }
}
</script>