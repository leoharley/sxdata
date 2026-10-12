<?php
/**
 * @var object $r
 * @var array  $eixos
 * @var array  $indicadores  catálogo filtrado pelos eixos do relatório
 * @var array  $grupos       indicador => linhas gravadas
 * @var string $eixo_aberto
 */
$this->load->view('admin/relatorios_municipais/_estilos');
$abrangencias = ['municipio' => 'Município', 'estado' => 'Estado', 'brasil' => 'Brasil'];
$primeiro = $eixo_aberto !== '' ? $eixo_aberto : ($r->eixos_lista[0] ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Dados do relatório</h2>
        <div class="text-muted"><?= html_escape($r->municipio_nome) ?> - <?= html_escape($r->uf) ?> · <?= html_escape($r->periodo_referencia) ?></div>
    </div>
    <div class="d-flex gap-2">
        <?php if ($r->status === 'processing'): ?>
            <a href="<?= base_url('relatorios-automatizados/processar/' . $r->id) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Processamento</a>
        <?php else: ?>
            <a href="<?= base_url('relatorios-automatizados/revisar/' . $r->id) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Revisão</a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-light border small">
    <i class="fas fa-info-circle me-1"></i>
    Valores das integrações aparecem como <span class="badge bg-primary">API</span>; os inseridos aqui, como
    <span class="badge bg-warning text-dark">Manual</span>. Gravar o mesmo indicador, abrangência, período e categoria
    substitui o valor anterior. Gráficos e textos refletem os dados na hora da revisão.
</div>

<div class="accordion mb-4" id="eixos">
<?php foreach ($r->eixos_lista as $eixo):
    $inds = array_filter($indicadores, function ($i) use ($eixo) { return $i['eixo'] === $eixo; });
    $total = 0;
    foreach ($inds as $k => $_) { $total += count($grupos[$k] ?? []); }
?>
    <div class="accordion-item" id="eixo-<?= $eixo ?>">
        <h2 class="accordion-header">
            <button class="accordion-button <?= $eixo === $primeiro ? '' : 'collapsed' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#corpo-<?= $eixo ?>">
                <i class="fas <?= $eixos[$eixo]['icone'] ?> me-2 text-secondary"></i>
                <?= html_escape($eixos[$eixo]['titulo']) ?>
                <span class="badge bg-light text-dark border ms-2"><?= $total ?> valor(es)</span>
            </button>
        </h2>
        <div id="corpo-<?= $eixo ?>" class="accordion-collapse collapse <?= $eixo === $primeiro ? 'show' : '' ?>" data-bs-parent="#eixos">
            <div class="accordion-body">

                <?php foreach ($inds as $chave => $ind):
                    $linhas = $grupos[$chave] ?? [];
                    $texto = ($ind['tipo'] ?? 'numero') === 'texto';
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between flex-wrap">
                            <strong class="small"><?= html_escape($ind['rotulo']) ?></strong>
                            <span class="small text-muted"><?= html_escape(($ind['unidade'] ?? '') ?: ($texto ? 'texto' : '')) ?> · <?= html_escape($ind['fonte'] ?? '') ?></span>
                        </div>
                        <?php if (empty($linhas)): ?>
                            <div class="small text-muted fst-italic">Sem dados.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm small mb-1">
                                    <thead class="table-light">
                                        <tr><th>Abrangência</th><th>Período</th><th>Categoria</th><th>Valor</th><th>Fonte</th><th>Origem</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($linhas as $l): ?>
                                        <tr>
                                            <td><?= $abrangencias[$l->abrangencia] ?? html_escape($l->abrangencia) ?></td>
                                            <td><?= html_escape($l->periodo) ?></td>
                                            <td><?= html_escape($l->categoria) ?></td>
                                            <td>
                                                <?php if ($l->valor !== null): ?>
                                                    <?= html_escape($this->relatorio_graficos->formatar($l->valor, $ind['formato'] ?? 'decimal2', $ind['unidade'] ?? '')) ?>
                                                <?php else: ?>
                                                    <span title="<?= html_escape($l->valor_texto) ?>"><?= html_escape(mb_strimwidth((string) $l->valor_texto, 0, 90, '...')) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= html_escape($l->fonte) ?><?= $l->periodo_fonte ? ' (' . html_escape($l->periodo_fonte) . ')' : '' ?></td>
                                            <td><?= $l->origem === 'manual' ? '<span class="badge bg-warning text-dark">Manual</span>' : '<span class="badge bg-primary">API</span>' ?></td>
                                            <td class="text-end">
                                                <?= form_open('relatorios-automatizados/excluir_dado/' . $r->id . '/' . $l->id, ['onsubmit' => "return confirm('Excluir este valor?');", 'class' => 'd-inline']) ?>
                                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Excluir"><i class="fas fa-trash"></i></button>
                                                <?= form_close() ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="border rounded p-3 bg-light">
                    <h6 class="small fw-bold mb-2"><i class="fas fa-plus-circle me-1"></i>Adicionar ou corrigir valor</h6>
                    <?= form_open('relatorios-automatizados/salvar_dado/' . $r->id, ['class' => 'row g-2 form-dado']) ?>
                        <div class="col-md-4">
                            <select class="form-select form-select-sm sel-indicador" name="indicador" required>
                                <?php foreach ($inds as $chave => $ind): ?>
                                    <option value="<?= $chave ?>" data-texto="<?= ($ind['tipo'] ?? 'numero') === 'texto' ? '1' : '0' ?>">
                                        <?= html_escape($ind['rotulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="abrangencia">
                                <?php foreach ($abrangencias as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" name="periodo" maxlength="20" placeholder="Período (ex.: 2022)">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="categoria" maxlength="150" placeholder="Categoria (se houver)">
                        </div>
                        <div class="col-md-4 campo-valor">
                            <input type="text" class="form-control form-control-sm" name="valor" placeholder="Valor (ex.: 1.234,5)">
                        </div>
                        <div class="col-12 campo-texto" style="display:none">
                            <textarea class="form-control form-control-sm" name="valor_texto" rows="3" placeholder="Texto"></textarea>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="fonte" maxlength="200" placeholder="Fonte (padrão do catálogo)">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" name="periodo_fonte" maxlength="40" placeholder="Ref. da fonte">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-sm btn-success">Gravar</button>
                        </div>
                    <?= form_close() ?>
                </div>

            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.form-dado').forEach(function (form) {
    var sel = form.querySelector('.sel-indicador');
    var ajustar = function () {
        var texto = sel.options[sel.selectedIndex].dataset.texto === '1';
        form.querySelector('.campo-valor').style.display = texto ? 'none' : '';
        form.querySelector('.campo-texto').style.display = texto ? '' : 'none';
    };
    sel.addEventListener('change', ajustar);
    ajustar();
});
</script>
