<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Privacypolicy extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Política de Privacidade - SXData';

        $this->load->view('admin/header', $data);
        $this->load->view('admin/privacy_policy', $data);
        $this->load->view('admin/footer');
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}
