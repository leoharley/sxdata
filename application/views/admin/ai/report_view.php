<style>
    .ai-page-card {
        background: white; border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;
    }
    .ai-page-card .card-header {
        background: white; border-bottom: 2px solid var(--primary-color);
        padding: 1rem 1.25rem;
    }
    .ai-page-card .card-header h5 { margin: 0; color: var(--secondary-color); font-weight: 600; }
    .breadcrumb { background: transparent; padding: 0; margin: 0; }
    .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
    .breadcrumb-item.active { color: var(--secondary-color); }
    .report-content {
        font-size: 1rem; line-height: 1.8; color: #333;
    }
    .report-content h1, .report-content h2, .report-content h3,
    .report-content h4, .report-content h5, .report-content h6 {
        color: var(--secondary-color); margin-top: 1.5rem; margin-bottom: 0.75rem;
    }
    .report-content p { margin-bottom: 1rem; }
    .report-content ul, .report-content ol { margin-bottom: 1rem; padding-left: 1.5rem; }
    .report-content li { margin-bottom: 0.35rem; }
    .report-content blockquote {
        border-left: 4px solid var(--primary-color);
        padding: 0.75rem 1rem; background: #f8f9fa;
        margin: 1rem 0; border-radius: 0 0.25rem 0.25rem 0;
    }
    .report-content table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    .report-content table th, .report-content table td {
        border: 1px solid #dee2e6; padding: 0.5rem 0.75rem;
    }
    .report-content table th { background: #f8f9fa; color: var(--secondary-color); }
    .report-meta {
        background: #f8f9fa; border-radius: 0.5rem; padding: 1rem;
        display: flex; gap: 2rem; flex-wrap: wrap;
    }
    .report-meta .meta-item { font-size: 0.9rem; color: #6c757d; }
    .report-meta .meta-item strong { color: var(--secondary-color); }
    .section-card {
        border: 1px solid #e9ecef; border-radius: 0.5rem;
        margin-bottom: 1rem; overflow: hidden;
    }
    .section-card .section-header {
        background: #f8f9fa; padding: 0.75rem 1rem;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600; color: var(--secondary-color);
    }
    .section-card .section-body { padding: 1rem; }
    .btn-ai-action {
        border: 1px solid #dee2e6; background: white; color: var(--secondary-color);
        border-radius: 0.375rem; padding: 0.4rem 0.8rem; font-size: 0.875rem;
        transition: all 0.2s;
    }
    .btn-ai-action:hover { background: var(--primary-color); border-color: var(--primary-color); color: white; }

    @media print {
        .no-print { display: none !important; }
        .ai-page-card { box-shadow: none; border: 1px solid #dee2e6; }
    }
</style>

<?php
    $sections = is_string($report->sections ?? '') ? json_decode($report->sections, true) : ($report->sections ?? []);
    $typeLabels = ['general' => 'Geral', 'executive' => 'Executivo', 'detailed' => 'Detalhado'];
?>

<div class="d-flex justify-content-between align-items-start mb-4 no-print">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= base_url('ai') ?>"><i class="fas fa-brain me-1"></i>Inteligência Artificial</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('ai/reports') ?>">Relatórios</a></li>
                <li class="breadcrumb-item active">Visualizar</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="fas fa-file-alt me-2" style="color: var(--primary-color);"></i>
            <?= htmlspecialchars($report->title ?? 'Relatório #' . $report->id) ?>
        </h2>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-ai-action" onclick="copyToClipboard()" title="Copiar para área de transferência">
            <i class="fas fa-copy me-1"></i>Copiar
        </button>
        <button type="button" class="btn-ai-action" onclick="window.print()" title="Imprimir">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="<?= base_url('ai/reports') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<!-- Meta informações -->
<div class="report-meta mb-4">
    <div class="meta-item">
        <i class="fas fa-clipboard-list me-1"></i>
        Questionário: <strong><?= htmlspecialchars($report->questionnaire_title ?? '') ?></strong>
    </div>
    <div class="meta-item">
        <i class="fas fa-tag me-1"></i>
        Tipo: <strong><?= $typeLabels[$report->report_type] ?? ucfirst($report->report_type ?? '') ?></strong>
    </div>
    <div class="meta-item">
        <i class="fas fa-calendar me-1"></i>
        Gerado em: <strong><?= date('d/m/Y H:i', strtotime($report->created_at)) ?></strong>
    </div>
</div>

<!-- Conteúdo Narrativo -->
<div class="ai-page-card" id="reportContent">
    <div class="card-header">
        <h5><i class="fas fa-align-left me-2"></i>Relatório Narrativo</h5>
    </div>
    <div class="card-body p-4">
        <div class="report-content" id="narrativeText">
            <?= $report->narrative_text ?? '' ?>
        </div>
    </div>
</div>

<!-- Seções -->
<?php if (!empty($sections)): ?>
<div class="ai-page-card">
    <div class="card-header">
        <h5><i class="fas fa-layer-group me-2"></i>Seções do Relatório</h5>
    </div>
    <div class="card-body p-3">
        <?php foreach ($sections as $section): ?>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-bookmark me-2" style="color: var(--primary-color);"></i>
                <?= htmlspecialchars($section['title'] ?? $section['name'] ?? 'Seção') ?>
            </div>
            <div class="section-body report-content">
                <?php if (is_array($section)): ?>
                    <?php if (!empty($section['content'])): ?>
                        <?= $section['content'] ?>
                    <?php elseif (!empty($section['text'])): ?>
                        <?= nl2br(htmlspecialchars($section['text'])) ?>
                    <?php endif; ?>
                    <?php if (!empty($section['items']) && is_array($section['items'])): ?>
                        <ul>
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?= htmlspecialchars(is_array($item) ? ($item['text'] ?? json_encode($item)) : $item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <?= nl2br(htmlspecialchars($section)) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Notificação de cópia -->
<div id="copyNotification" style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999;">
    <div class="alert alert-success border-0 shadow-lg mb-0">
        <i class="fas fa-check-circle me-2"></i>Conteúdo copiado para a área de transferência!
    </div>
</div>

<script>
function copyToClipboard() {
    var content = document.getElementById('reportContent');
    var textContent = content.innerText || content.textContent;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textContent).then(function() {
            showCopyNotification();
        }).catch(function() {
            fallbackCopy(textContent);
        });
    } else {
        fallbackCopy(textContent);
    }
}

function fallbackCopy(text) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showCopyNotification();
    } catch (err) {
        alert('Não foi possível copiar. Use Ctrl+C manualmente.');
    }
    document.body.removeChild(textarea);
}

function showCopyNotification() {
    var notif = document.getElementById('copyNotification');
    notif.style.display = 'block';
    setTimeout(function() { notif.style.display = 'none'; }, 3000);
}
</script>
