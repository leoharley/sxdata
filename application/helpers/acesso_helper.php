<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controle de acesso por perfil no painel.
 *
 * O login do painel aceita administrador, supervisor e cliente. As telas
 * administrativas verificavam apenas se havia alguém logado, e um cliente
 * conseguia abri-las digitando a URL.
 */

if (!function_exists('exigir_equipe')) {
    /**
     * Permite apenas administrador e supervisor. Cliente vai para o painel
     * dele; qualquer outro caso, para o login.
     */
    function exigir_equipe() {
        $CI =& get_instance();
        $papel = $CI->session->userdata('admin_role');

        if (in_array($papel, ['administrador', 'supervisor'], true)) {
            return;
        }

        if ($papel === 'cliente') {
            $CI->session->set_flashdata('error', 'Esta área é restrita à equipe do SXData.');
            redirect('client/dashboard');
        }

        redirect('auth/login');
    }
}
