<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stats extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('User_model');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($this->input->method() === 'options') {
            exit();
        }
    }

    /**
     * GET /api/stats/user/{user_id}
     * Retorna estatísticas específicas de um usuário aplicador
     */
    public function user($user_id = null) {
    if ($this->input->method() !== 'get') {
        $this->output->set_status_header(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    // Verificar autenticação
    $authenticated_user = $this->verify_auth();
    if (!$authenticated_user) {
        return;
    }

    // Se não foi especificado user_id, usar o do token
    if (!$user_id) {
        $user_id = $authenticated_user;
    }

    // CORREÇÃO: Administradores podem ver dados agregados de todos os aplicadores
    $is_admin_or_supervisor = $this->Response_model->is_supervisor_or_admin($authenticated_user);
    
    // Verificar se o usuário pode acessar essas estatísticas
    if ($authenticated_user != $user_id && !$is_admin_or_supervisor) {
        $this->output->set_status_header(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    try {
        // CORREÇÃO: Para administradores, mostrar dados agregados se não especificar user_id
        if ($is_admin_or_supervisor && !$this->input->get('user_id')) {
            // Dados agregados para administradores/supervisores
            $filters = [];
            
            $total_forms = $this->Response_model->count_by_filters($filters);
            $today_forms = $this->Response_model->count_by_filters([
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d')
            ]);
            $pending_sync = $this->Response_model->count_by_filters([
                'sync_status' => 'pending'
            ]);
            $photos_captured = $this->Response_model->count_photos($filters);
            
            // Taxa de sucesso geral
            $synced_forms = $this->Response_model->count_by_filters([
                'sync_status' => 'synced'
            ]);
            $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;
            
            // Dados agregados de todos os aplicadores
            $total_applicators = $this->Response_model->count_applicators('active');
            $active_days = $this->get_system_active_days(30);
            
            // Atividade recente de todo o sistema
            $recent_activity = $this->format_system_recent_activity(
                $this->Response_model->get_recent(10)
            );
            
            // Estatísticas de período do sistema
            $weekly_stats = $this->get_system_period_stats(7);
            $monthly_stats = $this->get_system_period_stats(30);
            
            // Top questionários do sistema
            $top_questionnaires = $this->format_top_questionnaires(
                $this->Response_model->get_questionnaires_popularity([], 5)
            );

            $stats = [
                'user_id' => (int)$authenticated_user,
                'user_type' => 'admin_aggregated',
                'summary' => [
                    'total_forms' => (int)$total_forms,
                    'today_forms' => (int)$today_forms,
                    'pending_sync' => (int)$pending_sync,
                    'success_rate' => (int)$success_rate,
                    'active_days' => (int)$active_days,
                    'photos_captured' => (int)$photos_captured
                ],
                'recent_activity' => $recent_activity,
                'period_stats' => [
                    'weekly' => $weekly_stats,
                    'monthly' => $monthly_stats
                ],
                'top_questionnaires' => $top_questionnaires,
                'system_info' => [
                    'total_applicators' => (int)$total_applicators,
                    'data_source' => 'aggregated_from_all_applicators'
                ],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        } else {
            // Dados específicos de um usuário (comportamento original)
            $filters = ['applied_by' => $user_id];
            
            $total_forms = $this->Response_model->count_by_filters($filters);
            $today_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d')
            ]));
            $pending_sync = $this->Response_model->count_by_filters(array_merge($filters, [
                'sync_status' => 'pending'
            ]));
            $photos_captured = $this->Response_model->count_photos($filters);

            // Taxa de sucesso
            $synced_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                'sync_status' => 'synced'
            ]));
            $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;

            // Dados usando métodos específicos do model
            $active_days = $this->Response_model->get_active_days($user_id, 30);
            $recent_activity = $this->format_recent_activity(
                $this->Response_model->get_recent_activity($user_id, 10)
            );
            $weekly_stats = $this->Response_model->get_period_stats($user_id, 7);
            $monthly_stats = $this->Response_model->get_period_stats($user_id, 30);
            $top_questionnaires = $this->format_top_questionnaires(
                $this->Response_model->get_top_questionnaires_by_user($user_id, 5)
            );

            $stats = [
                'user_id' => (int)$user_id,
                'user_type' => 'individual',
                'summary' => [
                    'total_forms' => (int)$total_forms,
                    'today_forms' => (int)$today_forms,
                    'pending_sync' => (int)$pending_sync,
                    'success_rate' => (int)$success_rate,
                    'active_days' => (int)$active_days,
                    'photos_captured' => (int)$photos_captured
                ],
                'recent_activity' => $recent_activity,
                'period_stats' => [
                    'weekly' => $weekly_stats,
                    'monthly' => $monthly_stats
                ],
                'top_questionnaires' => $top_questionnaires,
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);

    } catch (Exception $e) {
        $this->output->set_status_header(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get user stats: ' . $e->getMessage()
        ]);
    }
}

    /**
     * GET /api/stats/overview
     * Retorna estatísticas gerais do sistema (apenas para admins)
     */
    public function overview() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é admin
        if (!$this->is_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            return;
        }

        try {
            $filters = [];
            
            // Usar métodos do model para estatísticas gerais
            $total_responses = $this->Response_model->count_by_filters($filters);
            $unique_respondents = $this->Response_model->count_unique_respondents($filters);
            $total_locations = $this->Response_model->count_locations($filters);
            $total_photos = $this->Response_model->count_photos($filters);
            $consent_rate = $this->Response_model->get_consent_rate($filters);

            // Questionários
            $active_questionnaires = $this->Questionnaire_model->count_active();
            $total_questionnaires = $this->Questionnaire_model->count_all();

            // Top aplicadores e dados por dia
            $top_applicators = $this->Response_model->get_top_applicators($filters, 10);
            $responses_by_day = $this->Response_model->get_responses_by_day_filtered($filters, 30);

            $overview = [
                'summary' => [
                    'total_responses' => (int)$total_responses,
                    'unique_respondents' => (int)$unique_respondents,
                    'total_locations' => (int)$total_locations,
                    'total_photos' => (int)$total_photos,
                    'consent_rate' => (float)$consent_rate,
                    'active_questionnaires' => (int)$active_questionnaires,
                    'total_questionnaires' => (int)$total_questionnaires
                ],
                'top_applicators' => $top_applicators,
                'responses_by_day' => $responses_by_day,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            echo json_encode([
                'success' => true,
                'data' => $overview
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get overview stats: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/applicators
     * Retorna estatísticas de todos os aplicadores (apenas para supervisores/admins)
     */
    public function applicators() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é supervisor ou admin
        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Supervisor/Admin access required']);
            return;
        }

        try {
            // Obter filtros da URL
            $filters = [
                'date_from' => $this->input->get('date_from'),
                'date_to' => $this->input->get('date_to'),
                'status' => $this->input->get('status'), // active, inactive, all
            ];

            // Usar métodos do model
            $applicators_stats = $this->format_applicators_stats(
                $this->Response_model->get_applicators_stats($filters)
            );
            $location_stats = $this->Response_model->get_location_stats($filters);
            $summary = $this->Response_model->get_supervisor_summary($filters);

            $response = [
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'applicators' => $applicators_stats,
                    'locations' => $location_stats,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get applicators stats: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/locations
     * Retorna dados de localizações para o mapa (apenas para supervisores/admins)
     */
    public function locations() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é supervisor ou admin
        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Supervisor/Admin access required']);
            return;
        }

        try {
            // Obter filtros da URL
            $filters = [
                'date_from' => $this->input->get('date_from') ?: date('Y-m-d', strtotime('-30 days')),
                'date_to' => $this->input->get('date_to') ?: date('Y-m-d'),
                'applicator_id' => $this->input->get('applicator_id'),
            ];

            // Usar métodos do model
            $map_data = $this->format_map_data(
                $this->Response_model->get_map_locations($filters)
            );
            $coverage_stats = $this->Response_model->get_coverage_stats($filters);

            $response = [
                'success' => true,
                'data' => [
                    'map_data' => $map_data,
                    'coverage' => $coverage_stats,
                    'filters_applied' => $filters,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get location data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Formatar dados de atividade recente
     */
    private function format_recent_activity($activities) {
        $formatted_activities = [];
        
        foreach ($activities as $activity) {
            $time_diff = $this->time_elapsed_string($activity->completed_at);
            
            $formatted_activities[] = [
                'id' => (int)$activity->id,
                'action' => 'Formulário aplicado',
                'description' => $activity->questionnaire_title,
                'time' => $time_diff,
                'sync_status' => $activity->sync_status,
                'respondent_name' => $activity->respondent_name
            ];
        }
        
        return $formatted_activities;
    }

    /**
     * Formatar dados de questionários mais aplicados
     */
    private function format_top_questionnaires($questionnaires) {
        $formatted = [];
        
        foreach ($questionnaires as $questionnaire) {
            $formatted[] = [
                'id' => (int)$questionnaire->id,
                'title' => $questionnaire->title,
                'total_applications' => (int)$questionnaire->total_applications,
                'last_application' => $questionnaire->last_application,
                'last_application_time' => $this->time_elapsed_string($questionnaire->last_application)
            ];
        }
        
        return $formatted;
    }

    /**
     * Formatar dados de aplicadores
     */
    private function format_applicators_stats($applicators) {
        foreach ($applicators as $applicator) {
            $applicator->last_activity_formatted = $applicator->last_activity 
                ? $this->time_elapsed_string($applicator->last_activity) 
                : 'Nunca';
            $applicator->status = $applicator->is_active ? 'active' : 'inactive';
            $applicator->primary_location = $this->Response_model->get_applicator_primary_location($applicator->id);
        }
        
        return $applicators;
    }

    /**
     * Formatar dados do mapa
     */
    private function format_map_data($locations) {
        $map_points = [];
        
        foreach ($locations as $location) {
            $map_points[] = [
                'lat' => (float)$location->latitude,
                'lng' => (float)$location->longitude,
                'applicator_id' => (int)$location->applied_by,
                'applicator_name' => $location->applicator_name,
                'location_name' => $location->location_name,
                'forms_count' => (int)$location->forms_count,
                'last_activity' => $location->last_activity,
                'last_activity_formatted' => $this->time_elapsed_string($location->last_activity)
            ];
        }
        
        return $map_points;
    }

    /**
     * Verificar autenticação e retornar user_id
     */
    private function verify_auth() {
        $headers = $this->input->request_headers();
        
        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Authorization required']);
            return false;
        }

        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') !== 0) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid authorization format']);
            return false;
        }

        $token = substr($auth_header, 7);
        $user_id = $this->verify_token($token);
        
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return false;
        }

        return $user_id;
    }

    /**
     * Verificar token e retornar user_id
     */
    private function verify_token($token) {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
                return false;
            }
            
            if (time() > $payload['expires_at']) {
                return false;
            }
            
            return $payload['user_id'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar se usuário é admin
     */
    private function is_admin($user_id) {
        // Usar User_model se disponível, senão usar Response_model
        if (method_exists($this->User_model, 'get_by_id')) {
            $user = $this->User_model->get_by_id($user_id);
        } else {
            // Fallback usando database diretamente
            $this->db->select('role');
            $this->db->where('id', $user_id);
            $user = $this->db->get('users')->row();
        }
        
        return $user && ($user->role === 'admin' || $user->role === 'administrador');
    }

    /**
     * Calcular tempo decorrido em formato legível
     */
    private function time_elapsed_string($datetime, $full = false) {
        if (!$datetime) {
            return 'Nunca';
        }
        
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'ano',
            'm' => 'mês',
            'w' => 'semana',
            'd' => 'dia',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        );
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' atrás' : 'agora mesmo';
    }


    /**
     * Obter dias ativos do sistema (todos os aplicadores)
     */
    private function get_system_active_days($days = 30) {
        $this->db->select('COUNT(DISTINCT DATE(completed_at)) as active_days');
        $this->db->from('form_responses');
        $this->db->where('completed_at IS NOT NULL');
        $this->db->where('completed_at >=', date('Y-m-d', strtotime("-{$days} days")));
        
        $result = $this->db->get()->row();
        return $result ? $result->active_days : 0;
    }

    /**
     * Obter estatísticas de período do sistema
     */
    private function get_system_period_stats($days) {
        $filters = [
            'date_from' => date('Y-m-d', strtotime("-{$days} days")),
            'date_to' => date('Y-m-d')
        ];
        
        $total = $this->Response_model->count_by_filters($filters);
        $photos = $this->Response_model->count_photos($filters);
        $locations = $this->Response_model->count_locations($filters);
        $avg_per_day = $days > 0 ? round($total / $days, 1) : 0;
        
        return [
            'total_forms' => (int)$total,
            'photos_captured' => (int)$photos,
            'locations_captured' => (int)$locations,
            'avg_per_day' => (float)$avg_per_day,
            'period_days' => (int)$days
        ];
    }

    /**
     * Formatar atividade recente do sistema
     */
    private function format_system_recent_activity($activities) {
        $formatted_activities = [];
        
        foreach ($activities as $activity) {
            $time_diff = $this->time_elapsed_string($activity->completed_at);
            
            $formatted_activities[] = [
                'id' => (int)$activity->id,
                'action' => 'Formulário aplicado',
                'description' => $activity->questionnaire_title,
                'time' => $time_diff,
                'sync_status' => $activity->sync_status,
                'applicator_name' => $activity->applied_by_name,
                'respondent_name' => $activity->respondent_name
            ];
        }
        
        return $formatted_activities;
    }


}