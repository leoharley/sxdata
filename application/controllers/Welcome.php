<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}

	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function politica_de_privacidade() {
        $data['title'] = 'Política de Privacidade - SXData';
		
        $this->load->view('admin/privacy', $data);
        $this->load->view('admin/footer');
    }

}
