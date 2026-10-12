<?php $this->load->view('publico/_topo', ['page_title' => 'Resposta enviada']); ?>

<div class="text-center py-3">
    <div class="mb-3" style="font-size: 3rem; color: #8fae5d;">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1 class="mb-2">Obrigado pela participação!</h1>
    <p class="text-muted mb-0">
        Sua resposta para <strong><?= html_escape($link->questionnaire_title) ?></strong> foi registrada.
    </p>
    <p class="text-muted small mt-3 mb-0">Você já pode fechar esta página.</p>
</div>

<?php $this->load->view('publico/_rodape'); ?>
