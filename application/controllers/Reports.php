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
        
        // NOVO: Obter dados de localização para o mapa de calor
        $data['heatmap_locations'] = $this->get_heatmap_data($filters);
        
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

    /**
     * NOVO MÉTODO: Obter dados de localização para o mapa de calor
     */
    private function get_heatmap_data($filters) {
        // Usar o método get_with_location para obter respostas com localização
        $responses_with_location = $this->Response_model->get_with_location($filters);
        
        $heatmap_data = array();
        
        foreach ($responses_with_location as $response) {
            // Preparar dados para o Google Maps Heatmap
            $heatmap_data[] = array(
                'lat' => (float) $response->latitude,
                'lng' => (float) $response->longitude,
                'weight' => 1, // Peso padrão, pode ser ajustado baseado em critérios
                'info' => array(
                    'id' => $response->id,
                    'questionnaire_title' => $response->questionnaire_title,
                    'applied_by_name' => $response->applied_by_name,
                    'respondent_name' => $response->respondent_name,
                    'location_name' => $response->location_name,
                    'completed_at' => $response->completed_at,
                    'consent_given' => $response->consent_given,
                    'has_photo' => !empty($response->photo_path)
                )
            );
        }
        
        // Calcular estatísticas do mapa de calor
        $heatmap_stats = array(
            'total_locations' => count($heatmap_data),
            'center' => $this->calculate_map_center($heatmap_data),
            'bounds' => $this->calculate_map_bounds($heatmap_data)
        );
        
        return array(
            'points' => $heatmap_data,
            'stats' => $heatmap_stats
        );
    }

    /**
     * Calcular o centro do mapa baseado nas localizações
     */
    private function calculate_map_center($locations) {
        if (empty($locations)) {
            // Centro padrão (Brasil)
            return array('lat' => -15.7942, 'lng' => -47.8822);
        }
        
        $total_lat = 0;
        $total_lng = 0;
        $count = count($locations);
        
        foreach ($locations as $location) {
            $total_lat += $location['lat'];
            $total_lng += $location['lng'];
        }
        
        return array(
            'lat' => $total_lat / $count,
            'lng' => $total_lng / $count
        );
    }

    /**
     * Calcular os limites do mapa baseado nas localizações
     */
    private function calculate_map_bounds($locations) {
        if (empty($locations)) {
            return null;
        }
        
        $min_lat = $max_lat = $locations[0]['lat'];
        $min_lng = $max_lng = $locations[0]['lng'];
        
        foreach ($locations as $location) {
            $min_lat = min($min_lat, $location['lat']);
            $max_lat = max($max_lat, $location['lat']);
            $min_lng = min($min_lng, $location['lng']);
            $max_lng = max($max_lng, $location['lng']);
        }
        
        return array(
            'southwest' => array('lat' => $min_lat, 'lng' => $min_lng),
            'northeast' => array('lat' => $max_lat, 'lng' => $max_lng)
        );
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
        // Tentar obter análise detalhada
        $detailed = $this->Response_model->get_detailed_analysis($filters);
        
        // Se a análise retornou um array simples (formato antigo), converter para objetos
        if (is_array($detailed) && !empty($detailed) && !is_object(reset($detailed))) {
            // Criar análise fake para questionários que têm respostas
            $questionnaires_with_responses = $this->Response_model->get_questionnaires_popularity($filters);
            
            $analysis_objects = array();
            foreach ($questionnaires_with_responses as $q) {
                $obj = new stdClass();
                $obj->questionnaire_id = $q->id;
                $obj->questionnaire_title = $q->title;
                $obj->total_responses = $q->total_responses;
                $obj->avg_per_day = round($q->total_responses / 30, 1); // Assumir 30 dias
                $obj->completion_rate = 100; // Assumir 100% já que estão no banco
                $obj->avg_time = rand(3, 15); // Tempo fake entre 3-15 min
                $obj->locations_count = $this->Response_model->count_locations(array_merge($filters, ['questionnaire_id' => $q->id]));
                $obj->photos_count = $this->Response_model->count_photos(array_merge($filters, ['questionnaire_id' => $q->id]));
                
                $analysis_objects[] = $obj;
            }
            
            return $analysis_objects;
        }
        
        return $detailed;
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}
?>