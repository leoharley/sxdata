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

    public function get_location()
    {
        // Verificar se é uma requisição POST
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
        
        $latitude = $this->input->post('latitude');
        $longitude = $this->input->post('longitude');
        
        // Validar entrada
        if (empty($latitude) || empty($longitude) || 
            !is_numeric($latitude) || !is_numeric($longitude)) {
            echo 'N/A';
            return;
        }
        
        // Validar range das coordenadas
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            echo 'N/A';
            return;
        }
        
        try {
            $location_name = $this->get_location_name_api($latitude, $longitude);
            echo $location_name;
        } catch (Exception $e) {
            log_message('error', 'Erro na geocodificação: ' . $e->getMessage());
            echo 'Erro ao carregar';
        }
    }

private function get_location_name_api($latitude, $longitude)
    {
        // Verificar cache primeiro (usando cache do CodeIgniter se disponível)
        $cache_key = 'location_' . round($latitude, 3) . '_' . round($longitude, 3);
        
        if ($this->cache) {
            $cached_result = $this->cache->get($cache_key);
            if ($cached_result !== FALSE) {
                return $cached_result;
            }
        }
        
        try {
            // URL da API Nominatim
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=16&addressdetails=1&accept-language=pt-BR,pt,en";
            
            // Configurar contexto da requisição
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: SXData-App/1.0 (contact@sxdata.com)',
                        'Accept: application/json',
                        'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'
                    ],
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);
            
            if ($response === FALSE) {
                return 'N/A';
            }

            $data = json_decode($response, true);
            
            if (isset($data['display_name'])) {
                $formatted_name = $this->format_location_name_api($data);
                
                // Salvar no cache por 1 hora se o resultado for válido
                if ($this->cache && $formatted_name !== 'N/A') {
                    $this->cache->save($cache_key, $formatted_name, 3600);
                }
                
                return $formatted_name;
            }

        } catch (Exception $e) {
            log_message('error', 'Erro na API de geocodificação: ' . $e->getMessage());
        }

        return 'N/A';
    }

    private function format_location_name_api($data)
    {
        if (!isset($data['address'])) {
            return isset($data['display_name']) ? substr($data['display_name'], 0, 50) . '...' : 'N/A';
        }

        $address = $data['address'];
        $location_parts = [];

        // Priorizar informações mais específicas para o contexto brasileiro
        if (!empty($address['road'])) {
            $location_parts[] = $address['road'];
        }
        
        if (!empty($address['suburb']) || !empty($address['neighbourhood'])) {
            $location_parts[] = $address['suburb'] ?? $address['neighbourhood'];
        }
        
        if (!empty($address['city']) || !empty($address['town']) || !empty($address['village'])) {
            $location_parts[] = $address['city'] ?? $address['town'] ?? $address['village'];
        }
        
        if (!empty($address['state'])) {
            $location_parts[] = $address['state'];
        }

        $result = !empty($location_parts) ? implode(', ', $location_parts) : $data['display_name'];
        
        // Limitar o tamanho da string retornada
        if (strlen($result) > 50) {
            $result = substr($result, 0, 47) . '...';
        }
        
        return $result;
    }

    // Método alternativo usando cURL (mais robusto)
    private function get_location_name_curl($latitude, $longitude)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=16&addressdetails=1&accept-language=pt-BR";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SXData-App/1.0 (contact@sxdata.com)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === FALSE || $http_code !== 200) {
            return 'N/A';
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['display_name'])) {
            return $this->format_location_name_api($data);
        }
        
        return 'N/A';
    }

}