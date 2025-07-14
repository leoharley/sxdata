<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Responses extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Respostas - SXData';
        
        // Filtros
        $filters = array();
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        if ($this->input->get('applied_by')) {
            $filters['applied_by'] = $this->input->get('applied_by');
        }
        if ($this->input->get('date_from')) {
            $filters['date_from'] = $this->input->get('date_from');
        }
        if ($this->input->get('date_to')) {
            $filters['date_to'] = $this->input->get('date_to');
        }
        if ($this->input->get('sync_status')) {
            $filters['sync_status'] = $this->input->get('sync_status');
        }

        $data['responses'] = $this->Response_model->get_filtered($filters);
        $data['questionnaires'] = $this->Questionnaire_model->get_all();
        $data['users'] = $this->User_model->get_aplicadores();
        $data['filters'] = $filters;
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/responses/index', $data);
        $this->load->view('admin/footer');
    }

    public function view($id) {
        $response = $this->Response_model->get_by_id($id);
        if (!$response) {
            show_404();
        }

        $data['title'] = 'Visualizar Resposta - SXData';
        $data['response'] = $response;
        $data['questionnaire'] = $this->Questionnaire_model->get_by_id($response->questionnaire_id);
        $data['questions'] = $this->Question_model->get_by_questionnaire($response->questionnaire_id);
        $data['answers'] = $this->Response_model->get_answers($id);
        $data['applied_by'] = $this->User_model->get_by_id($response->applied_by);
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/responses/view', $data);
        $this->load->view('admin/footer');
    }

    public function export() {
        $this->load->library('excel');
        
        $filters = array();
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        if ($this->input->get('date_from')) {
            $filters['date_from'] = $this->input->get('date_from');
        }
        if ($this->input->get('date_to')) {
            $filters['date_to'] = $this->input->get('date_to');
        }

        $responses = $this->Response_model->get_for_export($filters);
        
        // Gerar arquivo Excel
        $this->excel->create_export($responses);
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}