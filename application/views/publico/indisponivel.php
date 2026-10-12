<?php $this->load->view('publico/_topo', ['page_title' => 'Pesquisa indisponível']); ?>

<div class="text-center py-3">
    <div class="mb-3" style="font-size: 3rem; color: #9aa4ae;">
        <i class="fas fa-calendar-times"></i>
    </div>
    <h1 class="mb-2">Pesquisa indisponível</h1>
    <p class="text-muted mb-0"><?= html_escape($reason) ?></p>
</div>

<?php $this->load->view('publico/_rodape'); ?>
