<?php
/**
 * @var array $relatorios
 * @var array $contadores
 * @var array $filtros
 * @var array $ufs
 */
$this->load->view('admin/relatorios_municipais/_estilos');

$cores_progresso = [
    'processing' => '#3b82c4', 'awaiting_review' => '#f0ad4e', 'in_review' => '#f0ad4e',
    'approved' => '#8fae5d', 'published' => '#8fae5d',
];
$eh_admin = $this->session->userdata('admin_role') === 'administrador';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0"><i class="fas fa-globe-americas me-2" style="color: var(--primary-color);"></i>Relatórios Automatizados</h2>
    <span class="text-muted"><i class="fas fa-calendar-day me-1"></i><?= date('d/m/Y H:i') ?></span>
</div>

<form method="get" action="<?= base_url('relatorios-automatizados') ?>" class="row g-2 align-items-end mb-4">
    <div class="col-md-4">
        <label class="form-label small mb-1" for="f-municipio">Município</label>
        <input type="text" class="form-control" id="f-municipio" name="municipio" value="<?= html_escape($filtros['municipio']) ?>"
               placeholder="Todos os municípios">
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1" for="f-uf">UF</label>
        <select class="form-select" id="f-uf" name="uf">
            <option value="">Todas as UF</option>
            <?php foreach ($ufs as $uf): ?>
                <option value="<?= $uf ?>" <?= $filtros['uf'] === $uf ? 'selected' : '' ?>><?= $uf ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1" for="f-periodo">Período</label>
        <input type="text" class="form-control" id="f-periodo" name="periodo" value="<?= html_escape($filtros['periodo']) ?>" placeholder="Ex.: 2026">
    </div>
    <div class="col-md-1 d-grid">
        <button type="submit" class="btn btn-outline-secondary" title="Filtrar"><i class="fas fa-search"></i></button>
    </div>
    <div class="col-md-3 d-grid">
        <a href="<?= base_url('relatorios-automatizados/novo') ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Gerar novo relatório
        </a>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['total', 'Relatórios', 'Total gerados', '#8fae5d', 'fa-clipboard-list'],
        ['processamento', 'Em processamento', 'Consultando as fontes', '#3b82c4', 'fa-sync-alt'],
        ['revisao', 'Aguardando revisão', 'Pendentes de análise', '#f0ad4e', 'fa-clock'],
        ['aprovados', 'Aprovados', 'Revisão concluída', '#5a7536', 'fa-check'],
    ] as $k): ?>
        <div class="col-6 col-lg-3">
            <div class="rm-card h-100 mb-0">
                <div class="rm-kpi">
                    <div class="rm-kpi-icone" style="background: <?= $k[3] ?>;"><i class="fas <?= $k[4] ?>"></i></div>
                    <div>
                        <div class="rm-kpi-valor"><?= (int) $contadores[$k[0]] ?></div>
                        <div class="rm-kpi-rotulo"><?= $k[1] ?></div>
                    </div>
                </div>
                <div class="small text-muted mt-2"><?= $k[2] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="rm-card p-0">
    <?php if (empty($relatorios)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-globe-americas fa-3x mb-3 d-block" style="opacity: .3"></i>
            Nenhum relatório<?= array_filter($filtros) ? ' com esses filtros' : '' ?>.
            <div class="mt-2"><a href="<?= base_url('relatorios-automatizados/novo') ?>">Gerar o primeiro relatório</a></div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table rm-tabela mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Município</th>
                        <th>Período</th>
                        <th>Progresso</th>
                        <th>Situação</th>
                        <th>Última atualização</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($relatorios as $r): ?>
                    <tr>
                        <td>
                            <strong><?= html_escape($r->municipio_nome) ?> - <?= html_escape($r->uf) ?></strong>
                            <div class="small text-muted">
                                IBGE <?= (int) $r->codigo_ibge ?>
                                <?= $r->projeto_nome ? ' · ' . html_escape($r->projeto_nome) : '' ?>
                            </div>
                        </td>
                        <td><?= html_escape($r->periodo_referencia) ?></td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rm-progresso flex-grow-1">
                                    <div style="width: <?= (int) $r->progresso ?>%; background: <?= $cores_progresso[$r->status] ?? '#adb5bd' ?>;"></div>
                                </div>
                                <small class="text-muted"><?= (int) $r->progresso ?>%</small>
                            </div>
                        </td>
                        <td>
                            <?= relatorio_status_badge($r->status) ?>
                            <?php if ((int) $r->versao_atual > 0 && $r->status !== 'published'): ?>
                                <div class="small text-muted mt-1">v<?= (int) $r->versao_atual ?> publicada</div>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= date('d/m/Y H:i', strtotime($r->updated_at)) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ((int) $r->versao_atual > 0): ?>
                                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"
                                   href="<?= base_url('relatorios-automatizados/documento/' . $r->id . '/' . (int) $r->versao_atual) ?>">
                                    <i class="fas fa-eye me-1"></i>Visualizar
                                </a>
                            <?php endif; ?>

                            <?php if ($r->status === 'processing'): ?>
                                <a class="btn btn-sm btn-primary" href="<?= base_url('relatorios-automatizados/processar/' . $r->id) ?>">
                                    <i class="fas fa-sync-alt me-1"></i>Processar
                                </a>
                            <?php elseif ($r->status === 'approved'): ?>
                                <a class="btn btn-sm btn-success" href="<?= base_url('relatorios-automatizados/aprovacao/' . $r->id) ?>">
                                    <i class="fas fa-check-double me-1"></i>Aprovação final
                                </a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('relatorios-automatizados/revisar/' . $r->id) ?>">
                                    <i class="fas fa-user-check me-1"></i>Revisar
                                </a>
                            <?php endif; ?>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-label="Mais ações">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= base_url('relatorios-automatizados/dados/' . $r->id) ?>"><i class="fas fa-table me-2"></i>Dados</a></li>
                                    <li><a class="dropdown-item" href="<?= base_url('relatorios-automatizados/processar/' . $r->id) ?>"><i class="fas fa-plug me-2"></i>Fontes de dados</a></li>
                                    <li><a class="dropdown-item" href="<?= base_url('relatorios-automatizados/revisar/' . $r->id) ?>"><i class="fas fa-user-check me-2"></i>Revisão</a></li>
                                    <li><a class="dropdown-item" href="<?= base_url('relatorios-automatizados/aprovacao/' . $r->id) ?>"><i class="fas fa-file-signature me-2"></i>Aprovação final</a></li>
                                    <?php if ($eh_admin): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <?= form_open('relatorios-automatizados/excluir/' . $r->id, ['onsubmit' => "return confirm('Excluir o relatório de " . html_escape(addslashes($r->municipio_nome)) . "? Os dados, a revisão e as versões publicadas serão apagados.');"]) ?>
                                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Excluir</button>
                                            <?= form_close() ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
