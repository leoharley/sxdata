<?php
/**
 * Documento do relatório municipal (leitura, impressão e PDF).
 *
 * @var array $conteudo   conteúdo montado (prévia) ou congelado (versão publicada)
 * @var bool  $publicado
 * @var bool  $equipe
 */
$c = $conteudo;
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$versao = isset($c['versao']) ? 'v' . (int) $c['versao'] . '.0' : 'Prévia';
$data_ref = !empty($c['aprovado_em']) ? $c['aprovado_em'] : $c['gerado_em'];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Diagnóstico Municipal <?= $e($c['periodo']) ?> - <?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --verde: #8fae5d; --verde-escuro: #5a7536; --azul: #23345F; --cinza: #6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; color: #1f2937; background: #e9ecef; font-size: 10.5pt; line-height: 1.5; }
        .barra { position: sticky; top: 0; z-index: 10; background: var(--azul); color: #fff; padding: 10px 16px;
                 display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .barra button { background: var(--verde); color: #fff; border: 0; border-radius: 6px; padding: 8px 14px; font-weight: 600; cursor: pointer; }
        .aviso-previa { background: #fff3cd; color: #664d03; text-align: center; padding: 8px; font-size: 9.5pt; }
        .pagina { background: #fff; width: 210mm; min-height: 297mm; margin: 16px auto; padding: 18mm 16mm; position: relative; overflow: hidden;
                  box-shadow: 0 2px 10px rgba(0,0,0,.12); }

        /* Capa */
        .capa { display: flex; flex-direction: column; justify-content: center; }
        .capa .forma1 { position: absolute; top: 0; right: 0; width: 0; height: 0; border-top: 190px solid var(--azul); border-left: 190px solid transparent; }
        .capa .forma2 { position: absolute; top: 40px; right: 40px; width: 70px; height: 70px; background: var(--verde); transform: rotate(45deg); opacity: .9; }
        .capa .forma3 { position: absolute; bottom: 0; left: 0; width: 0; height: 0; border-bottom: 120px solid var(--verde); border-right: 260px solid transparent; opacity: .35; }
        .capa img { width: 210px; margin-bottom: 48px; }
        .capa h1 { font-size: 34pt; color: var(--azul); margin: 0; line-height: 1.1; }
        .capa .ano { font-size: 34pt; color: var(--verde); font-weight: 700; margin: 0 0 18px; }
        .capa .local { font-size: 18pt; color: #374151; }
        .capa .linha { width: 120px; height: 4px; background: var(--verde); margin: 28px 0; }
        .capa .meta { color: var(--cinza); font-size: 10pt; }

        h2.titulo-secao { color: var(--azul); font-size: 17pt; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
        h2.titulo-secao i { width: 36px; height: 36px; border-radius: 50%; background: var(--verde); color: #fff; display: inline-flex;
                            align-items: center; justify-content: center; font-size: 13pt; }
        .faixa { height: 3px; background: linear-gradient(90deg, var(--verde), transparent); margin-bottom: 14px; }
        .texto { text-align: justify; margin-bottom: 14px; }
        .kpis { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .kpi { flex: 1 1 150px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; }
        .kpi .valor { font-size: 17pt; font-weight: 700; color: var(--azul); line-height: 1.1; }
        .kpi .rotulo { font-size: 9pt; color: #374151; }
        .kpi .fonte, .grafico .fonte, .qualitativo .fonte { font-size: 8pt; color: var(--cinza); }
        .graficos { display: flex; flex-wrap: wrap; gap: 14px; }
        .grafico { flex: 1 1 calc(50% - 7px); min-width: 260px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; break-inside: avoid; }
        .grafico h3 { font-size: 11pt; color: var(--azul); margin: 0 0 8px; }
        .grafico .area { position: relative; height: 230px; }
        .grafico .interp { margin-top: 8px; font-size: 9.5pt; background: #f4f8ee; border-radius: 6px; padding: 7px 9px; }
        .qualitativo { border-left: 3px solid var(--verde); background: #f8faf6; padding: 8px 12px; margin-bottom: 10px; border-radius: 4px; }
        .qualitativo strong { color: var(--azul); }
        .sumario li { padding: 6px 0; border-bottom: 1px dotted #ced4da; list-style: none; display: flex; justify-content: space-between; }
        .sumario ol { padding: 0; }
        .rodape-pagina { position: absolute; bottom: 10mm; left: 16mm; right: 16mm; font-size: 8pt; color: var(--cinza);
                         display: flex; justify-content: space-between; border-top: 1px solid #eef0f2; padding-top: 4px; }
        .fontes li { margin-bottom: 4px; }

        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; }
            .barra, .aviso-previa { display: none; }
            .pagina { margin: 0; box-shadow: none; width: auto; min-height: 297mm; page-break-after: always; }
            .pagina:last-child { page-break-after: auto; }
        }
        @media (max-width: 230mm) {
            .pagina { width: auto; min-height: 0; margin: 8px; padding: 20px 16px 50px; }
            .grafico { flex-basis: 100%; }
        }
    </style>
</head>
<body>

<div class="barra">
    <span><i class="fas fa-file-alt me-1"></i> Diagnóstico Municipal · <?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?> · <?= $e($versao) ?></span>
    <button type="button" onclick="window.print()"><i class="fas fa-file-pdf"></i> Baixar PDF / Imprimir</button>
</div>
<?php if (!$publicado): ?>
    <div class="aviso-previa"><i class="fas fa-eye"></i> Prévia interna com o conteúdo aprovado até agora. Ainda não foi liberada para o cliente.</div>
<?php endif; ?>

<!-- Capa -->
<section class="pagina capa">
    <div class="forma1"></div><div class="forma2"></div><div class="forma3"></div>
    <img src="<?= img_url('logo_socialx.png') ?>" alt="SocialX">
    <h1>Diagnóstico Municipal</h1>
    <div class="ano"><?= $e($c['periodo']) ?></div>
    <div class="local"><?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?></div>
    <div class="linha"></div>
    <div class="meta">
        <?php if (!empty($c['projeto'])): ?><div>Projeto: <?= $e($c['projeto']) ?></div><?php endif; ?>
        <div>Código IBGE: <?= (int) $c['codigo_ibge'] ?></div>
        <div><?= $publicado ? 'Versão ' . $e($versao) . ' · aprovada em ' : 'Gerado em ' ?><?= date('d/m/Y', strtotime($data_ref)) ?></div>
    </div>
</section>

<!-- Sumário -->
<section class="pagina">
    <h2 class="titulo-secao"><i class="fas fa-list"></i>Sumário</h2>
    <div class="faixa"></div>
    <?php if (empty($c['secoes'])): ?>
        <p class="texto">Nenhuma seção aprovada ainda.</p>
    <?php else: ?>
        <ol class="sumario">
            <?php foreach ($c['secoes'] as $i => $s): ?>
                <li><span><?= $i + 1 ?>. <?= $e($s['titulo']) ?></span><span><?= count($s['graficos']) ?> gráfico(s)</span></li>
            <?php endforeach; ?>
            <li><span><?= count($c['secoes']) + 1 ?>. Fontes consultadas</span><span></span></li>
        </ol>
    <?php endif; ?>
    <p class="texto" style="margin-top: 24px; color: var(--cinza); font-size: 9.5pt;">
        As informações deste diagnóstico foram obtidas em bases públicas e, quando indicado, em bases locais do município,
        e foram conferidas pela equipe técnica antes da publicação. Fonte e período de referência são informados em cada indicador.
    </p>
    <div class="rodape-pagina"><span>SXData · SocialX</span><span><?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?></span></div>
</section>

<?php foreach ($c['secoes'] as $i => $s): ?>
<section class="pagina">
    <h2 class="titulo-secao"><i class="fas <?= $e($s['icone']) ?>"></i><?= $i + 1 ?>. <?= $e($s['titulo']) ?></h2>
    <div class="faixa"></div>

    <?php if (trim($s['texto']) !== ''): ?>
        <div class="texto"><?= nl2br($e($s['texto'])) ?></div>
    <?php endif; ?>

    <?php if (!empty($s['kpis'])): ?>
        <div class="kpis">
            <?php foreach ($s['kpis'] as $k): ?>
                <div class="kpi">
                    <div class="valor"><?= $e($k['valor']) ?></div>
                    <div class="rotulo"><?= $e($k['rotulo']) ?><?= $k['unidade'] ? ' (' . $e($k['unidade']) . ')' : '' ?></div>
                    <div class="fonte"><?= $e($k['fonte']) ?> · <?= $e($k['periodo']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($s['qualitativos'] as $q): ?>
        <div class="qualitativo">
            <strong><?= $e($q['rotulo']) ?></strong>
            <div><?= nl2br($e($q['texto'])) ?></div>
            <div class="fonte"><?= $e($q['fonte']) ?><?= $q['periodo'] ? ' · ' . $e($q['periodo']) : '' ?></div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($s['graficos'])): ?>
        <div class="graficos">
            <?php foreach ($s['graficos'] as $g): ?>
                <div class="grafico">
                    <h3><?= $e($g['titulo']) ?></h3>
                    <div class="area"><canvas data-grafico="<?= $e(json_encode($g['dados'], JSON_UNESCAPED_UNICODE)) ?>"></canvas></div>
                    <?php if (trim($g['texto']) !== ''): ?>
                        <div class="interp"><i class="fas fa-check-circle" style="color: var(--verde)"></i> <?= $e($g['texto']) ?></div>
                    <?php endif; ?>
                    <div class="fonte" style="margin-top: 6px;">
                        Fonte: <?= $e(implode('; ', $g['dados']['fontes']) ?: 'não informada') ?>
                        <?= $g['dados']['periodo'] !== '' ? ' · Referência: ' . $e($g['dados']['periodo']) : '' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="rodape-pagina"><span>SXData · SocialX</span><span><?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?> · <?= $e($versao) ?></span></div>
</section>
<?php endforeach; ?>

<?php if (!empty($c['secoes'])): ?>
<section class="pagina">
    <h2 class="titulo-secao"><i class="fas fa-book"></i><?= count($c['secoes']) + 1 ?>. Fontes consultadas</h2>
    <div class="faixa"></div>
    <ul class="fontes">
        <?php foreach ($c['fontes'] as $f): ?>
            <li><?= $e($f) ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="rodape-pagina"><span>SXData · SocialX</span><span><?= $e($c['municipio']) ?> - <?= $e($c['uf']) ?> · <?= $e($versao) ?></span></div>
</section>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('assets/js/relatorios/graficos.js') ?>"></script>
<script>
// Sem animação: a impressão captura o gráfico já desenhado
SXGraficos.desenharTodos(document, { estatico: true });
// Redesenha no tamanho da folha ao imprimir
window.addEventListener('beforeprint', function () {
    document.querySelectorAll('canvas[data-grafico]').forEach(function (c) { if (c._sxChart) c._sxChart.resize(); });
});
</script>
</body>
</html>
