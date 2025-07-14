<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->library('excel');
        $this->load->library('kmz_generator');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Relatórios - SXData';
        
        // Processar filtros
        $filters = $this->get_filters();
        $data['filters'] = $filters;
        
        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_all_with_stats();

        // Carregar dados
        $data['questionnaires'] = $this->Questionnaire_model->get_all();
        $data['period_stats'] = $this->calculate_period_stats($filters);
        $data['charts_data'] = $this->get_charts_data($filters);
        $data['detailed_analysis'] = $this->get_detailed_analysis($filters);
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/reports/index', $data);
        $this->load->view('admin/footer');
    }

    public function export_all() {
        $filters = $this->get_filters();
        $responses = $this->Response_model->get_for_export($filters);
        
        $this->excel->create_export($responses);
    }

    public function generate_kmz() {
        $filters = $this->get_filters();
        $responses = $this->Response_model->get_with_location($filters);
        
        if (empty($responses)) {
            $this->session->set_flashdata('error', 'Nenhuma localização encontrada para gerar o arquivo KMZ.');
            redirect('reports');
            return;
        }
        
        $title = 'Localizações SXData';
        if (isset($filters['questionnaire_id'])) {
            $questionnaire = $this->Questionnaire_model->get_by_id($filters['questionnaire_id']);
            $title = 'Localizações - ' . $questionnaire->title;
        }
        
        $this->kmz_generator->generate($responses, $title);
    }

    private function get_filters() {
        $filters = array();
        
        $period = $this->input->get('period') ?: 'last_30_days';
        $filters['period'] = $period;
        
        switch ($period) {
            case 'last_7_days':
                $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'last_30_days':
                $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'last_3_months':
                $filters['date_from'] = date('Y-m-d', strtotime('-3 months'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'custom':
                $filters['date_from'] = $this->input->get('date_from');
                $filters['date_to'] = $this->input->get('date_to');
                break;
        }
        
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        
        return $filters;
    }

    private function calculate_period_stats($filters) {
        // Implementar cálculo de estatísticas do período
        return array(
            'total_responses' => $this->Response_model->count_by_filters($filters),
            'unique_respondents' => $this->Response_model->count_unique_respondents($filters),
            'photos_captured' => $this->Response_model->count_photos($filters),
            'locations_captured' => $this->Response_model->count_locations($filters),
            'consent_rate' => $this->Response_model->get_consent_rate($filters)
        );
    }

    private function get_charts_data($filters) {
        return array(
            'responses_by_day' => $this->Response_model->get_responses_by_day_filtered($filters),
            'top_applicators' => $this->Response_model->get_top_applicators($filters),
            'questionnaires_popularity' => $this->Response_model->get_questionnaires_popularity($filters)
        );
    }

    private function get_detailed_analysis($filters) {
        return $this->Response_model->get_detailed_analysis($filters);
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}