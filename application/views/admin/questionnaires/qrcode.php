<?php
/**
 * @var object $questionnaire
 * @var array  $links
 */
$truthy = function ($v) { return $v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true'; };
?>
<style>
    .qr-card { background: #fff; border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.08); padding: 1.25rem; margin-bottom: 1rem; }
    .qr-box { width: 180px; height: 180px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
              background: #fff; border: 1px solid #eef0f2; border-radius: .5rem; padding: 8px; }
    .qr-box img, .qr-box canvas { width: 100% !important; height: auto !important; }
    .qr-inativo .qr-box { opacity: .35; }
    .qr-url { font-family: Consolas, monospace; font-size: .85rem; word-break: break-all; }
</style>

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-qrcode me-2" style="color: var(--primary-color);"></i>Coleta por QR Code</h2>
        <div class="text-muted"><?= html_escape($questionnaire->title) ?></div>
    </div>
    <a href="<?= base_url('questionnaires') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar aos questionários
    </a>
</div>

<?php if ($questionnaire->status !== 'active'): ?>
    <div class="alert alert-warning">
        <i class="fas fa-pause-circle me-1"></i>
        Este questionário não está <strong>Ativo</strong>: os QR Codes abaixo mostram "pesquisa indisponível" até ele ser reativado.
    </div>
<?php endif; ?>

<div class="alert alert-light border small">
    <i class="fas fa-info-circle me-1"></i>
    Quem ler o QR Code responde pelo navegador, <strong>sem login</strong>, e só tem acesso a este questionário.
    As respostas entram no painel como as do aplicativo, identificadas como <em>Coleta pública (QR Code)</em>.
    Como o acesso é aberto, qualquer pessoa com o código pode responder.
</div>

<div class="qr-card">
    <h6 class="mb-3"><i class="fas fa-plus-circle me-1"></i>Novo QR Code</h6>
    <?= form_open('questionnaires/qrcode_criar/' . (int) $questionnaire->id, ['class' => 'row g-2 align-items-end']) ?>
        <div class="col-md-6">
            <label class="form-label small mb-1" for="label">Identificação (opcional)</label>
            <input type="text" class="form-control" id="label" name="label" maxlength="200"
                   placeholder="Ex.: Cartaz da UBS Centro">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1" for="expires_at">Aceitar respostas até (opcional)</label>
            <input type="date" class="form-control" id="expires_at" name="expires_at">
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-success"><i class="fas fa-qrcode me-1"></i>Gerar QR Code</button>
        </div>
    <?= form_close() ?>
    <small class="text-muted d-block mt-2">Gere um código por local de divulgação para saber de onde vieram as respostas.</small>
</div>

<?php if (empty($links)): ?>
    <div class="text-center text-muted py-5">
        <i class="fas fa-qrcode fa-3x mb-3 d-block" style="opacity:.3"></i>
        Nenhum QR Code criado para este questionário.
    </div>
<?php endif; ?>

<?php foreach ($links as $link):
    $url = base_url('responder/' . $link->token);
    $ativo = $truthy($link->is_active);
    $expirado = !empty($link->expires_at) && strtotime($link->expires_at) < time();
?>
    <div class="qr-card <?= ($ativo && !$expirado) ? '' : 'qr-inativo' ?>">
        <div class="d-flex gap-3 flex-wrap flex-md-nowrap">
            <div class="qr-box" id="qr-<?= (int) $link->id ?>" data-url="<?= html_escape($url) ?>"></div>
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
                    <h6 class="mb-0"><?= html_escape($link->label ?: 'QR Code #' . $link->id) ?></h6>
                    <div>
                        <?php if (!$ativo): ?>
                            <span class="badge bg-secondary">Desativado</span>
                        <?php elseif ($expirado): ?>
                            <span class="badge bg-warning text-dark">Prazo encerrado</span>
                        <?php else: ?>
                            <span class="badge bg-success">Recebendo respostas</span>
                        <?php endif; ?>
                        <span class="badge bg-light text-dark border"><?= (int) $link->response_count ?> resposta(s)</span>
                    </div>
                </div>
                <div class="qr-url text-muted mb-2"><?= html_escape($url) ?></div>
                <div class="small text-muted mb-3">
                    Criado em <?= date('d/m/Y H:i', strtotime($link->created_at)) ?>
                    <?= $link->created_by_name ? ' por ' . html_escape($link->created_by_name) : '' ?>
                    <?php if (!empty($link->expires_at)): ?>
                        &middot; aceita respostas até <?= date('d/m/Y', strtotime($link->expires_at)) ?>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copiarLink('<?= html_escape($url) ?>', this)">
                        <i class="fas fa-link me-1"></i>Copiar link
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="baixarPng(<?= (int) $link->id ?>)">
                        <i class="fas fa-download me-1"></i>Baixar imagem
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="imprimirCartaz(<?= (int) $link->id ?>)">
                        <i class="fas fa-print me-1"></i>Imprimir cartaz
                    </button>
                    <a href="<?= html_escape($url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-external-link-alt me-1"></i>Abrir formulário
                    </a>
                    <?= form_open('questionnaires/qrcode_status/' . (int) $link->id, ['class' => 'd-inline']) ?>
                        <input type="hidden" name="ativar" value="<?= $ativo ? '0' : '1' ?>">
                        <?php if ($ativo): ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Desativar este QR Code? Quem ler o código verá que a pesquisa foi encerrada. As respostas já recebidas continuam no painel.');">
                                <i class="fas fa-ban me-1"></i>Desativar
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-check me-1"></i>Reativar
                            </button>
                        <?php endif; ?>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
var TITULO_PESQUISA = <?= json_encode($questionnaire->title, JSON_UNESCAPED_UNICODE) ?>;

document.querySelectorAll('.qr-box').forEach(function (box) {
    new QRCode(box, {
        text: box.dataset.url,
        width: 600,
        height: 600,
        colorDark: '#23345F',
        colorLight: '#ffffff',
        // Correção alta: o código continua legível mesmo com a impressão amassada ou riscada
        correctLevel: QRCode.CorrectLevel.H
    });
});

function imagemQr(id) {
    var canvas = document.querySelector('#qr-' + id + ' canvas');
    return canvas ? canvas.toDataURL('image/png') : null;
}

function baixarPng(id) {
    var dados = imagemQr(id);
    if (!dados) return;
    var a = document.createElement('a');
    a.href = dados;
    a.download = 'qrcode-pesquisa-' + id + '.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
}

function copiarLink(url, botao) {
    var ok = function () {
        var original = botao.innerHTML;
        botao.innerHTML = '<i class="fas fa-check me-1"></i>Copiado';
        setTimeout(function () { botao.innerHTML = original; }, 1800);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(ok);
    } else {
        prompt('Copie o link:', url);
    }
}

function imprimirCartaz(id) {
    var dados = imagemQr(id);
    if (!dados) return;
    var url = document.getElementById('qr-' + id).dataset.url;
    var logo = <?= json_encode(img_url('logo_socialx.png')) ?>;
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var w = window.open('', '_blank');
    w.document.write(
        '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Cartaz - ' + esc(TITULO_PESQUISA) + '</title>' +
        '<style>@page{size:A4;margin:18mm}body{font-family:Arial,sans-serif;text-align:center;color:#23345F}' +
        'img.logo{width:200px;margin-bottom:24px}h1{font-size:28px;margin:0 0 10px}p{font-size:18px;color:#374151}' +
        'img.qr{width:320px;height:320px;margin:24px auto;display:block}small{color:#6b7280;word-break:break-all}</style></head><body>' +
        '<img class="logo" src="' + logo + '" alt="SocialX">' +
        '<h1>' + esc(TITULO_PESQUISA) + '</h1>' +
        '<p>Aponte a câmera do celular para o código e responda à pesquisa.<br>Não é preciso cadastro.</p>' +
        '<img class="qr" src="' + dados + '" alt="QR Code">' +
        '<small>' + esc(url) + '</small>' +
        '<script>window.onload=function(){window.print();}<\/script></body></html>'
    );
    w.document.close();
}
</script>
