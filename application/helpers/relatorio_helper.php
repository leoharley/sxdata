<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Selos de situação do módulo de relatórios municipais.
 */

if (!function_exists('relatorio_status_info')) {
    function relatorio_status_info($status) {
        $mapa = [
            'processing'      => ['Em processamento', 'primary', 'fa-sync-alt'],
            'awaiting_review' => ['Aguardando conferência', 'warning', 'fa-clock'],
            'in_review'       => ['Em revisão', 'info', 'fa-hourglass-half'],
            'approved'        => ['Revisão interna concluída', 'success', 'fa-check-circle'],
            'published'       => ['Aprovado para o cliente', 'success', 'fa-paper-plane'],
        ];

        return $mapa[$status] ?? [$status, 'secondary', 'fa-question-circle'];
    }
}

if (!function_exists('relatorio_status_badge')) {
    function relatorio_status_badge($status) {
        list($rotulo, $cor, $icone) = relatorio_status_info($status);
        $texto = $cor === 'warning' ? ' text-dark' : '';

        return '<span class="badge bg-' . $cor . $texto . '"><i class="fas ' . $icone . ' me-1"></i>' . html_escape($rotulo) . '</span>';
    }
}

if (!function_exists('secao_status_info')) {
    function secao_status_info($status) {
        $mapa = [
            'pending'              => ['Pendente', 'warning', 'fa-clock'],
            'in_review'            => ['Em revisão', 'info', 'fa-hourglass-half'],
            'adjustment_requested' => ['Ajuste solicitado', 'danger', 'fa-exclamation-circle'],
            'approved'             => ['Aprovado', 'success', 'fa-check-circle'],
            'rejected'             => ['Rejeitado', 'secondary', 'fa-ban'],
        ];

        return $mapa[$status] ?? [$status, 'secondary', 'fa-question-circle'];
    }
}

if (!function_exists('secao_status_badge')) {
    function secao_status_badge($status) {
        list($rotulo, $cor, $icone) = secao_status_info($status);
        $texto = $cor === 'warning' ? ' text-dark' : '';

        return '<span class="badge bg-' . $cor . $texto . '"><i class="fas ' . $icone . ' me-1"></i>' . html_escape($rotulo) . '</span>';
    }
}
