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

        // Verificar se o usuário pode acessar essas estatísticas
        if ($authenticated_user != $user_id && !$this->is_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        try {
            $filters = ['applied_by' => $user_id];
            
            // Estatísticas básicas
            $total_forms = $this->Response_model->count_by_filters($filters);
            $today_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d')
            ]));
            $pending_sync = $this->Response_model->count_by_filters(array_merge($filters, [
                'sync_status' => 'pending'
            ]));
            $photos_captured = $this->Response_model->count_photos($filters);

            // Taxa de sucesso (assumindo que formulários sincronizados são sucessos)
            $synced_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                'sync_status' => 'synced'
            ]));
            $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;

            // Dias ativos (últimos 30 dias)
            $active_days = $this->get_active_days($user_id, 30);

            // Atividade recente
            $recent_activity = $this->get_recent_activity($user_id, 10);

            // Estatísticas por período
            $weekly_stats = $this->get_period_stats($user_id, 7);
            $monthly_stats = $this->get_period_stats($user_id, 30);

            // Questionários mais aplicados
            $top_questionnaires = $this->get_top_questionnaires($user_id, 5);

            $stats = [
                'user_id' => (int)$user_id,
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
            
            // Estatísticas gerais
            $total_responses = $this->Response_model->count_by_filters($filters);
            $unique_respondents = $this->Response_model->count_unique_respondents($filters);
            $total_locations = $this->Response_model->count_locations($filters);
            $total_photos = $this->Response_model->count_photos($filters);
            $consent_rate = $this->Response_model->get_consent_rate($filters);

            // Questionários ativos
            $active_questionnaires = $this->Questionnaire_model->count_active();
            $total_questionnaires = $this->Questionnaire_model->count_all();

            // Top aplicadores
            $top_applicators = $this->Response_model->get_top_applicators($filters, 10);

            // Respostas por dia (últimos 30 dias)
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
     * Obter dias ativos do usuário
     */
    private function get_active_days($user_id, $days = 30) {
        $this->db->select('COUNT(DISTINCT DATE(completed_at)) as active_days');
        $this->db->from('form_responses');
        $this->db->where('applied_by', $user_id);
        $this->db->where('completed_at IS NOT NULL');
        $this->db->where('completed_at >=', date('Y-m-d', strtotime("-{$days} days")));
        
        $result = $this->db->get()->row();
        return $result ? $result->active_days : 0;
    }

    /**
     * Obter atividade recente do usuário
     */
    private function get_recent_activity($user_id, $limit = 10) {
        $this->db->select('
            fr.id,
            fr.completed_at,
            fr.sync_status,
            q.title as questionnaire_title,
            fr.respondent_name
        ');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->where('fr.applied_by', $user_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->order_by('fr.completed_at', 'DESC');
        $this->db->limit($limit);
        
        $activities = $this->db->get()->result();
        
        // Formatar atividades
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
     * Obter estatísticas de um período
     */
    private function get_period_stats($user_id, $days) {
        $filters = [
            'applied_by' => $user_id,
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
     * Obter questionários mais aplicados pelo usuário
     */
    private function get_top_questionnaires($user_id, $limit = 5) {
        $this->db->select('
            q.id,
            q.title,
            COUNT(fr.id) as total_applications,
            MAX(fr.completed_at) as last_application
        ');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->where('fr.applied_by', $user_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('total_applications', 'DESC');
        $this->db->limit($limit);
        
        $results = $this->db->get()->result();
        
        // Formatar resultados
        $questionnaires = [];
        foreach ($results as $result) {
            $questionnaires[] = [
                'id' => (int)$result->id,
                'title' => $result->title,
                'total_applications' => (int)$result->total_applications,
                'last_application' => $result->last_application,
                'last_application_time' => $this->time_elapsed_string($result->last_application)
            ];
        }
        
        return $questionnaires;
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
        $this->db->select('role');
        $this->db->where('id', $user_id);
        $user = $this->db->get('users')->row();
        
        return $user && $user->role === 'admin';
    }

    /**
     * Calcular tempo decorrido em formato legível
     */
    private function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
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
}