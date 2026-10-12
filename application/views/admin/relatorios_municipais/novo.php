<?php
/**
 * @var array $eixos
 * @var array $ufs
 * @var array $projetos
 * @var bool  $com_lista   tabela ibge_municipios carregada
 * @var array $conectores  ['js' => [...], 'php' => [...]]
 */
$this->load->view('admin/relatorios_municipais/_estilos');
$sem_conectores = empty($conectores['js']) && empty($conectores['php']);
?>
<style>
    .rm-eixo { display: flex; align-items: center; gap: .6rem; border: 1px solid #dee2e6; border-radius: .5rem;
               padding: .7rem .8rem; cursor: pointer; height: 100%; user-select: none; }
    .rm-eixo input { flex-shrink: 0; }
    .rm-eixo:has(input:checked) { border-color: #8fae5d; background: #f4f8ee; }
    .rm-sugestoes { position: absolute; z-index: 20; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #dee2e6;
                    border-radius: .5rem; box-shadow: 0 6px 18px rgba(0,0,0,.08); max-height: 260px; overflow-y: auto; display: none; }
    .rm-sugestoes button { display: block; width: 100%; text-align: left; border: 0; background: none; padding: .5rem .75rem; }
    .rm-sugestoes button:hover { background: #f4f8ee; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Gerar relatório municipal</h2>
    <a href="<?= base_url('relatorios-automatizados') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="rm-passos">
    <div class="rm-passo ativo"><div class="num">1</div><div>Município</div></div>
    <div class="rm-passo"><div class="num">2</div><div>Fontes de dados</div></div>
    <div class="rm-passo"><div class="num">3</div><div>Processamento</div></div>
    <div class="rm-passo"><div class="num">4</div><div>Revisão</div></div>
</div>

<?= form_open('relatorios-automatizados/criar', ['id' => 'form-novo']) ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="rm-card">
            <h6 class="mb-3">Município selecionado</h6>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="uf">UF *</label>
                    <select class="form-select" id="uf" name="uf" required>
                        <option value="">UF</option>
                        <?php foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>"><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 position-relative">
                    <label class="form-label small mb-1" for="municipio_nome">Município *</label>
                    <input type="text" class="form-control" id="municipio_nome" name="municipio_nome" required maxlength="150"
                           autocomplete="off" placeholder="<?= $com_lista ? 'Digite para buscar' : 'Nome do município' ?>">
                    <div class="rm-sugestoes" id="sugestoes"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1" for="codigo_ibge">Código IBGE *</label>
                    <input type="text" class="form-control" id="codigo_ibge" name="codigo_ibge" required
                           inputmode="numeric" pattern="\d{7}" maxlength="7" placeholder="7 dígitos">
                </div>
            </div>
            <?php if (!$com_lista): ?>
                <div class="small text-muted mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    A lista de municípios do IBGE ainda não foi carregada: informe nome e código manualmente
                    (o código está em <a href="https://cidades.ibge.gov.br" target="_blank" rel="noopener">cidades.ibge.gov.br</a>).
                </div>
            <?php endif; ?>

            <div class="row g-2 mt-1">
                <div class="col-md-4">
                    <label class="form-label small mb-1" for="periodo_referencia">Período de referência *</label>
                    <input type="text" class="form-control" id="periodo_referencia" name="periodo_referencia" required
                           maxlength="20" value="<?= date('Y') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label small mb-1" for="project_id">Projeto (para o cliente acessar o relatório)</label>
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">Sem projeto vinculado</option>
                        <?php foreach ($projetos as $p): ?>
                            <option value="<?= (int) $p->id ?>"><?= html_escape($p->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h6 class="mt-4 mb-2 d-flex justify-content-between align-items-center">
                Fontes temáticas
                <span>
                    <button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="marcarEixos(true)">Marcar todos</button>
                    <button type="button" class="btn btn-link btn-sm p-0" onclick="marcarEixos(false)">Desmarcar</button>
                </span>
            </h6>
            <div class="row g-2">
                <?php foreach ($eixos as $chave => $e): ?>
                    <div class="col-md-6 col-xl-4">
                        <label class="rm-eixo">
                            <input class="form-check-input eixo" type="checkbox" name="eixos[]" value="<?= $chave ?>" checked>
                            <i class="fas <?= $e['icone'] ?> text-secondary"></i>
                            <span class="small fw-semibold"><?= html_escape($e['titulo']) ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="small text-muted mt-2">
                <i class="fas fa-heartbeat me-1"></i>
                Os eixos de Atenção Primária e de Média e Alta Complexidade usam a base local do e-SUS do município.
                Sem esse acesso, ficam restritos ao que as fontes abertas publicam.
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="rm-card">
            <h6 class="mb-3">Fontes conectadas</h6>
            <?php if ($sem_conectores): ?>
                <div class="text-muted small">
                    <i class="fas fa-plug me-1"></i>
                    Nenhuma integração configurada ainda. O relatório pode ser montado com os dados preenchidos
                    manualmente na etapa de dados.
                </div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($conectores['js'] as $arquivo): ?>
                        <li class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-globe me-2 text-secondary"></i><?= html_escape(pathinfo($arquivo, PATHINFO_FILENAME)) ?></span>
                            <span class="text-success"><i class="fas fa-circle me-1" style="font-size:.5rem"></i>Navegador</span>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($conectores['php'] as $c): ?>
                        <li class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-server me-2 text-secondary"></i><?= html_escape($c['rotulo']) ?></span>
                            <span class="text-success"><i class="fas fa-circle me-1" style="font-size:.5rem"></i>Servidor</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?= base_url('relatorios-automatizados') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-success"><i class="fas fa-play me-1"></i>Iniciar geração automática</button>
</div>
<?= form_close() ?>

<script>
function marcarEixos(v) {
    document.querySelectorAll('input.eixo').forEach(function (c) { c.checked = v; });
}

(function () {
    var comLista = <?= $com_lista ? 'true' : 'false' ?>;
    var nome = document.getElementById('municipio_nome');
    var uf = document.getElementById('uf');
    var codigo = document.getElementById('codigo_ibge');
    var caixa = document.getElementById('sugestoes');

    document.getElementById('form-novo').addEventListener('submit', function (ev) {
        if (!document.querySelector('input.eixo:checked')) {
            ev.preventDefault();
            alert('Selecione pelo menos um eixo temático.');
        }
    });

    if (!comLista) return;

    var timer = null;
    nome.addEventListener('input', function () {
        clearTimeout(timer);
        var termo = nome.value.trim();
        if (termo.length < 2) { caixa.style.display = 'none'; return; }
        timer = setTimeout(function () {
            var url = <?= json_encode(base_url('relatorios-automatizados/municipios')) ?> + '?q=' + encodeURIComponent(termo) + '&uf=' + encodeURIComponent(uf.value);
            fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
                caixa.innerHTML = '';
                (j.municipios || []).forEach(function (m) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = m.nome + ' - ' + m.uf + ' (' + m.codigo + ')';
                    b.addEventListener('click', function () {
                        nome.value = m.nome;
                        uf.value = m.uf;
                        codigo.value = m.codigo;
                        caixa.style.display = 'none';
                    });
                    caixa.appendChild(b);
                });
                caixa.style.display = caixa.children.length ? 'block' : 'none';
            });
        }, 250);
    });
    document.addEventListener('click', function (e) { if (e.target !== nome) caixa.style.display = 'none'; });
})();
</script>
