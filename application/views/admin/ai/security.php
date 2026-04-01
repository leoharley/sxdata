<style>
    .sec-hero {
        background: linear-gradient(135deg, var(--secondary-color), #1a2847);
        border-radius: 0.75rem;
        padding: 2.5rem;
        color: white;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .sec-hero::after {
        content: '';
        position: absolute;
        top: -50px; right: -50px;
        width: 200px; height: 200px;
        background: rgba(143,174,93,0.1);
        border-radius: 50%;
    }
    .sec-hero h2 { font-weight: 700; margin-bottom: 0.5rem; }
    .sec-hero p { opacity: 0.85; font-size: 1.05rem; margin-bottom: 0; }
    .sec-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
        height: 100%;
        border-top: 4px solid var(--primary-color);
        transition: transform 0.2s;
    }
    .sec-card:hover { transform: translateY(-3px); }
    .sec-card .sec-icon {
        width: 56px; height: 56px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: white; margin-bottom: 1rem;
    }
    .sec-card h5 { color: var(--secondary-color); font-weight: 600; margin-bottom: 0.5rem; }
    .sec-card p { color: #555; font-size: 0.9rem; line-height: 1.6; }
    .sec-card ul { padding-left: 1.2rem; margin: 0; }
    .sec-card ul li { color: #555; font-size: 0.85rem; margin-bottom: 0.3rem; }
    .sec-badge-row {
        display: flex; flex-wrap: wrap; gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .sec-badge {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: white; border: 1px solid #e9ecef;
        border-radius: 2rem; padding: 0.5rem 1rem;
        font-size: 0.85rem; color: var(--secondary-color); font-weight: 500;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .sec-badge i { font-size: 1rem; }
    .sec-section-title {
        color: var(--secondary-color); font-weight: 700; font-size: 1.2rem;
        margin-bottom: 1.25rem; padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-color);
    }
    .sec-commitment {
        background: linear-gradient(135deg, rgba(143,174,93,0.08), rgba(35,52,95,0.04));
        border: 1px solid rgba(143,174,93,0.2);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .sec-commitment h5 { color: var(--secondary-color); font-weight: 600; }
    .sec-commitment ul { padding-left: 1.5rem; }
    .sec-commitment ul li { margin-bottom: 0.4rem; color: #444; }
    .share-section {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
        text-align: center;
    }
    .share-section h5 { color: var(--secondary-color); font-weight: 600; margin-bottom: 1rem; }
    .share-btn {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.6rem 1.2rem; border-radius: 2rem;
        font-size: 0.85rem; font-weight: 500;
        border: none; color: white; cursor: pointer;
        transition: opacity 0.2s, transform 0.2s;
        text-decoration: none;
    }
    .share-btn:hover { opacity: 0.85; transform: translateY(-1px); color: white; }
    .share-btn.whatsapp { background: #25D366; }
    .share-btn.email { background: #6c757d; }
    .share-btn.copy { background: var(--secondary-color); }
    .share-btn.print { background: var(--primary-color); }
    .share-btn.pdf { background: #dc3545; }
    @media print {
        .no-print { display: none !important; }
        .sec-hero { color: #333 !important; background: #f8f9fa !important; border: 2px solid var(--primary-color); }
        .sec-card { box-shadow: none; border: 1px solid #dee2e6; }
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-4 no-print">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2" style="background:transparent;padding:0;margin:0;">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>" style="color:var(--primary-color);text-decoration:none;"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item active" style="color:var(--secondary-color);">Segurança e Privacidade</li>
            </ol>
        </nav>
    </div>
    <a href="<?= base_url('ai') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<!-- Hero -->
<div class="sec-hero">
    <div class="d-flex align-items-center mb-3">
        <i class="fas fa-shield-alt me-3" style="font-size: 2.5rem; color: var(--primary-color);"></i>
        <div>
            <h2>Segurança, Privacidade e Confidencialidade</h2>
            <p>Normas e protocolos de proteção de dados nas funcionalidades de Inteligência Artificial do SXDATA</p>
        </div>
    </div>
</div>

<!-- Badges de conformidade -->
<div class="sec-badge-row">
    <div class="sec-badge"><i class="fas fa-check-circle text-success"></i>Conformidade LGPD</div>
    <div class="sec-badge"><i class="fas fa-lock text-primary"></i>Criptografia em Trânsito (TLS/SSL)</div>
    <div class="sec-badge"><i class="fas fa-user-shield text-warning"></i>Controle de Acesso por Perfil</div>
    <div class="sec-badge"><i class="fas fa-eye-slash text-danger"></i>Dados Anonimizáveis</div>
    <div class="sec-badge"><i class="fas fa-server text-info"></i>Processamento via API Segura</div>
    <div class="sec-badge"><i class="fas fa-clock text-secondary"></i>Logs de Auditoria</div>
</div>

<!-- Cards de segurança -->
<h4 class="sec-section-title"><i class="fas fa-layer-group me-2"></i>Níveis de Proteção</h4>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h5>Dados em Trânsito</h5>
            <p>Todas as comunicações entre o painel, o aplicativo móvel e os serviços de IA são realizadas por canais criptografados.</p>
            <ul>
                <li>Protocolo HTTPS/TLS em todas as requisições</li>
                <li>Certificado SSL válido e atualizado</li>
                <li>Tokens de autenticação com expiração</li>
                <li>Headers de segurança configurados</li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #8fae5d, #6d8a45);">
                <i class="fas fa-database"></i>
            </div>
            <h5>Dados em Repouso</h5>
            <p>Os dados armazenados no banco de dados são protegidos por múltiplas camadas de segurança e acesso restrito.</p>
            <ul>
                <li>Banco PostgreSQL com acesso restrito</li>
                <li>Senhas armazenadas com hash bcrypt</li>
                <li>Backups periódicos criptografados</li>
                <li>Separação de ambientes (produção/teste)</li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #6f42c1, #5a359e);">
                <i class="fas fa-brain"></i>
            </div>
            <h5>Processamento por IA</h5>
            <p>As análises de IA utilizam a API OpenAI com políticas rigorosas de privacidade e não-retenção de dados.</p>
            <ul>
                <li>API OpenAI não treina modelos com seus dados</li>
                <li>Dados enviados apenas para processamento pontual</li>
                <li>Sem armazenamento permanente pela OpenAI</li>
                <li>Chave de API exclusiva por organização</li>
            </ul>
        </div>
    </div>
</div>

<!-- Controle de acesso -->
<h4 class="sec-section-title"><i class="fas fa-users-cog me-2"></i>Controle de Acesso</h4>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #fd7e14, #d66a10);">
                <i class="fas fa-user-lock"></i>
            </div>
            <h5>Perfis e Permissões</h5>
            <p>O sistema implementa controle de acesso baseado em perfis (RBAC), garantindo que cada usuário acesse apenas o que é permitido para sua função.</p>
            <ul>
                <li><strong>Administrador</strong> — Acesso total ao painel, configurações de IA, diretrizes e gerenciamento de usuários</li>
                <li><strong>Supervisor</strong> — Acesso ao painel com visualização de dados e relatórios, sem alterar configurações</li>
                <li><strong>Aplicador</strong> — Acesso exclusivo ao app móvel para coleta de dados em campo</li>
                <li><strong>Cliente</strong> — Acesso restrito apenas aos gráficos e análises dos projetos autorizados</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #20c997, #1aa179);">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h5>Auditoria e Rastreabilidade</h5>
            <p>Todas as operações de IA são registradas com detalhes completos para auditoria e conformidade regulatória.</p>
            <ul>
                <li>Log de cada execução de IA (modelo, tokens, custo, duração)</li>
                <li>Registro de quem aprovou/rejeitou sugestões da IA</li>
                <li>Histórico de alterações em perguntas e respostas</li>
                <li>Identificação do usuário em cada ação</li>
                <li>Timestamps em todos os registros</li>
                <li>Diretrizes configuráveis pelo administrador</li>
            </ul>
        </div>
    </div>
</div>

<!-- Confidencialidade -->
<h4 class="sec-section-title"><i class="fas fa-mask me-2"></i>Confidencialidade das Informações</h4>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #dc3545, #b02a37);">
                <i class="fas fa-file-contract"></i>
            </div>
            <h5>Dados de Pesquisa</h5>
            <p>Os dados coletados em campo são tratados com o mais alto nível de confidencialidade.</p>
            <ul>
                <li>Respostas dos entrevistados são confidenciais</li>
                <li>Acesso aos dados brutos restrito a administradores</li>
                <li>Clientes visualizam apenas gráficos agregados, sem dados individuais</li>
                <li>Transcrições de áudio são processadas e podem ser excluídas após uso</li>
                <li>Dados pessoais seguem as diretrizes da LGPD</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="sec-card">
            <div class="sec-icon" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0);">
                <i class="fas fa-robot"></i>
            </div>
            <h5>Uso Responsável de IA</h5>
            <p>A inteligência artificial é utilizada como ferramenta de apoio, com supervisão humana obrigatória.</p>
            <ul>
                <li>Toda sugestão da IA requer aprovação humana antes de ser aplicada</li>
                <li>Diretrizes personalizáveis limitam o comportamento da IA</li>
                <li>A IA não toma decisões autônomas sobre dados</li>
                <li>Resultados são sempre revisáveis e editáveis</li>
                <li>O administrador pode desativar qualquer funcionalidade de IA</li>
            </ul>
        </div>
    </div>
</div>

<!-- Compromisso -->
<div class="sec-commitment">
    <h5><i class="fas fa-handshake me-2" style="color: var(--primary-color);"></i>Nosso Compromisso</h5>
    <p class="mb-2">O SXDATA se compromete a:</p>
    <ul>
        <li>Tratar todos os dados coletados com confidencialidade e respeito à privacidade dos participantes</li>
        <li>Utilizar inteligência artificial de forma ética, transparente e supervisionada</li>
        <li>Manter conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei 13.709/2018)</li>
        <li>Garantir que os dados sejam utilizados exclusivamente para os fins definidos no projeto de pesquisa</li>
        <li>Implementar e manter controles técnicos e administrativos adequados de segurança</li>
        <li>Notificar imediatamente em caso de incidentes de segurança que afetem dados pessoais</li>
    </ul>
    <div class="mt-3 d-flex align-items-center">
        <i class="fas fa-calendar-check me-2 text-muted"></i>
        <small class="text-muted">Última atualização: <?= date('d/m/Y') ?></small>
    </div>
</div>

<!-- Compartilhamento -->
<div class="share-section no-print">
    <h5><i class="fas fa-share-alt me-2"></i>Compartilhar este documento</h5>
    <p class="text-muted small mb-3">Envie as normas de segurança para stakeholders, clientes ou equipe</p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="javascript:void(0)" class="share-btn whatsapp" onclick="shareWhatsApp()">
            <i class="fab fa-whatsapp"></i>WhatsApp
        </a>
        <a href="javascript:void(0)" class="share-btn email" onclick="shareEmail()">
            <i class="fas fa-envelope"></i>E-mail
        </a>
        <a href="javascript:void(0)" class="share-btn copy" onclick="copyLink()">
            <i class="fas fa-link"></i>Copiar Link
        </a>
        <a href="javascript:void(0)" class="share-btn print" onclick="window.print()">
            <i class="fas fa-print"></i>Imprimir
        </a>
        <a href="javascript:void(0)" class="share-btn pdf" onclick="exportPDF()">
            <i class="fas fa-file-pdf"></i>Salvar PDF
        </a>
    </div>
</div>

<script>
var pageUrl = window.location.href;
var pageTitle = 'SXDATA - Normas de Segurança e Privacidade na IA';
var pageDesc = 'Documento de normas de segurança, proteção de dados e confidencialidade das funcionalidades de Inteligência Artificial do painel SXDATA.';

function shareWhatsApp() {
    var text = pageTitle + '\n\n' + pageDesc + '\n\n' + pageUrl;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

function shareEmail() {
    var subject = encodeURIComponent(pageTitle);
    var body = encodeURIComponent(pageDesc + '\n\nAcesse: ' + pageUrl);
    window.open('mailto:?subject=' + subject + '&body=' + body);
}

function copyLink() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(pageUrl).then(function() { showShareToast('Link copiado!'); });
    } else {
        var t = document.createElement('textarea');
        t.value = pageUrl;
        t.style.position = 'fixed';
        t.style.opacity = '0';
        document.body.appendChild(t);
        t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
        showShareToast('Link copiado!');
    }
}

function exportPDF() {
    showShareToast('Preparando para impressão como PDF...');
    setTimeout(function() { window.print(); }, 500);
}

function showShareToast(msg) {
    var existing = document.getElementById('shareToast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.id = 'shareToast';
    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;';
    toast.innerHTML = '<div class="alert alert-success border-0 shadow-lg mb-0 py-2 px-3"><i class="fas fa-check-circle me-2"></i>' + msg + '</div>';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 3000);
}
</script>
