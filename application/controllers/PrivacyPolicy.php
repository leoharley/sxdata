<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Privacypolicy extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function politica_de_privacidade() {
        exit;
        $data['title'] = 'Política de Privacidade - SXData';

        $this->load->view('admin/header', $data);
        $this->load->view('admin/privacy', $data);
        $this->load->view('admin/footer');
    }

}
