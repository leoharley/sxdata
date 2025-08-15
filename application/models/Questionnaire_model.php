<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaire_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->select('q.*, u.full_name as created_by_name, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->order_by('q.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_all_with_stats() {
        $questionnaires = $this->get_all();
        
        foreach ($questionnaires as &$questionnaire) {
            // Contar perguntas
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->question_count = $this->db->count_all_results('questions');
            
            // Contar respostas
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->response_count = $this->db->count_all_results('form_responses');
            
            // Última resposta
            $this->db->select('MAX(completed_at) as last_response');
            $this->db->where('questionnaire_id', $questionnaire->id);
            $this->db->where('completed_at IS NOT NULL');
            $last = $this->db->get('form_responses')->row();
            $questionnaire->last_response = $last ? $last->last_response : NULL;
        }
        
        return $questionnaires;
    }

    public function get_by_id($id) {
        // CORREÇÃO: Incluir explicitamente os campos de checkbox e project_id
        $this->db->select('q.id, q.title, q.description, q.status, q.version, 
                          q.requires_consent, q.requires_location, q.requires_photo,
                          q.estimated_time, q.aplicadores, q.created_by, q.created_at, q.updated_at,
                          q.project_id, p.name as project_name,
                          u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.id', $id);
        
        $result = $this->db->get()->row();
        
        // Debug: Log da query executada (remover em produção)
        if (ENVIRONMENT === 'development' && $result) {
            log_message('debug', 'SQL Query: ' . $this->db->last_query());
            log_message('debug', 'Raw result from DB: ' . json_encode($result));
        }
        
        return $result;
    }

    public function get_active() {
        $this->db->select('q.*, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->order_by('q.title', 'ASC');
        return $this->db->get()->result();
    }

    public function get_by_project($project_id) {
        $this->db->select('q.*, u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->where('q.project_id', $project_id);
        $this->db->order_by('q.created_at', 'DESC');
        
        $questionnaires = $this->db->get()->result();
        
        // Adicionar contagem de perguntas e respostas
        foreach ($questionnaires as &$questionnaire) {
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->question_count = $this->db->count_all_results('questions');
            
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->response_count = $this->db->count_all_results('form_responses');
        }
        
        return $questionnaires;
    }

    public function count_by_project($project_id) {
        $this->db->where('project_id', $project_id);
        return $this->db->count_all_results('questionnaires');
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Debug: Log dos dados que serão inseridos (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Creating questionnaire with data: ' . json_encode($data));
        }
        
        return $this->db->insert('questionnaires', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Buscar versão atual antes de atualizar
        $this->db->select('version');
        $this->db->where('id', $id);
        $current = $this->db->get('questionnaires')->row();
        
        if ($current) {
            $data['version'] = $current->version + 1;
        }
        
        // Debug: Log dos dados que serão atualizados (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Updating questionnaire ID ' . $id . ' with data: ' . json_encode($data));
        }
        
        $this->db->where('id', $id);
        $result = $this->db->update('questionnaires', $data);
        
        // Debug: Log da query executada (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Update SQL Query: ' . $this->db->last_query());
            log_message('debug', 'Update result: ' . var_export($result, true));
        }
        
        return $result;
    }

    public function delete($id) {
        // Verificar se tem respostas associadas
        $this->db->where('questionnaire_id', $id);
        $has_responses = $this->db->count_all_results('form_responses') > 0;
        
        if ($has_responses) {
            // Apenas marcar como inativo se tiver respostas
            return $this->update($id, array('status' => 'inactive'));
        } else {
            // Deletar completamente se não tiver respostas
            $this->db->where('id', $id);
            return $this->db->delete('questionnaires');
        }
    }

    public function count_all() {
        return $this->db->count_all('questionnaires');
    }

    public function count_active() {
        $this->db->where('status', 'active');
        return $this->db->count_all_results('questionnaires');
    }

    public function get_usage_stats() {
        $this->db->select('q.title, COUNT(fr.id) as response_count');
        $this->db->from('questionnaires q');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id', 'left');
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('response_count', 'DESC');
        $this->db->limit(10);
        
        return $this->db->get()->result();
    }

    public function get_for_api($user_role = null) {
        $this->db->select('q.*, COUNT(questions.id) as question_count, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id,p.name');
        $this->db->order_by('q.title', 'ASC');
        
        $questionnaires = $this->db->get()->result();
        
        // Adicionar perguntas para cada questionário
        foreach ($questionnaires as &$questionnaire) {
            $questionnaire->questions = $this->get_questions_with_options($questionnaire->id);
        }
        
        return $questionnaires;
    }

    private function get_questions_with_options($questionnaire_id) {
        // Primeiro, buscar as questões
        $this->db->select('*');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->order_by('order_index', 'ASC');
        
        $questions = $this->db->get()->result();
        
        // Para cada questão, buscar suas opções
        foreach ($questions as &$question) {
            $this->db->select('*');
            $this->db->from('question_options');
            $this->db->where('question_id', $question->id);
            $this->db->order_by('order_index', 'ASC');
            
            $question->options = $this->db->get()->result();
        }
        
        return $questions;
    }

    public function can_aplicador_access($questionnaire_id, $aplicador_id) {
        $questionnaire = $this->get_by_id($questionnaire_id);
        
        if (!$questionnaire || $questionnaire->status !== 'active') {
            return FALSE;
        }
        
        // Se não há restrição de aplicadores (NULL), todos podem acessar
        if (empty($questionnaire->aplicadores)) {
            return TRUE;
        }
        
        // Decodificar JSON e verificar se o aplicador está na lista
        $aplicadores_permitidos = json_decode($questionnaire->aplicadores, true);
        
        if (!is_array($aplicadores_permitidos)) {
            return TRUE; // Fallback: se não conseguir decodificar, permite acesso
        }
        
        return in_array($aplicador_id, $aplicadores_permitidos);
    }

    /**
     * Retorna questionários que um aplicador específico pode acessar
     * 
     * @param int $aplicador_id ID do aplicador
     * @return array Lista de questionários disponíveis para o aplicador
     */
    public function get_for_aplicador($aplicador_id) {
        $this->db->select('q.*, COUNT(questions.id) as question_count, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id');
        $this->db->order_by('q.title', 'ASC');
        
        $all_questionnaires = $this->db->get()->result();
        
        // Filtrar questionários que o aplicador pode acessar
        $accessible_questionnaires = array();
        
        foreach ($all_questionnaires as $questionnaire) {
            if ($this->can_aplicador_access($questionnaire->id, $aplicador_id)) {
                // Adicionar perguntas para cada questionário
                $questionnaire->questions = $this->get_questions_with_options($questionnaire->id);
                $accessible_questionnaires[] = $questionnaire;
            }
        }
        
        return $accessible_questionnaires;
    }

    /**
     * Retorna estatísticas de uso por aplicador
     * 
     * @param int $questionnaire_id ID do questionário (opcional)
     * @return array Estatísticas de aplicadores
     */
    public function get_aplicador_stats($questionnaire_id = null) {
        $this->db->select('u.id, u.full_name, u.username, COUNT(fr.id) as total_responses');
        $this->db->from('users u');
        $this->db->join('form_responses fr', 'u.id = fr.applied_by', 'left');
        $this->db->where('u.role', 'aplicador');
        $this->db->where('u.is_active', TRUE);
        
        if ($questionnaire_id) {
            $this->db->where('fr.questionnaire_id', $questionnaire_id);
        }
        
        $this->db->group_by('u.id, u.full_name, u.username');
        $this->db->order_by('total_responses', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Retorna os nomes dos aplicadores permitidos para um questionário
     * 
     * @param object $questionnaire Objeto do questionário
     * @return string Nomes dos aplicadores separados por vírgula
     */
    public function get_aplicadores_names($questionnaire) {
        if (empty($questionnaire->aplicadores)) {
            return 'Todos os aplicadores';
        }
        
        $aplicadores_ids = json_decode($questionnaire->aplicadores, true);
        
        if (!is_array($aplicadores_ids) || empty($aplicadores_ids)) {
            return 'Todos os aplicadores';
        }
        
        $this->db->select('full_name');
        $this->db->where_in('id', $aplicadores_ids);
        $this->db->where('role', 'aplicador');
        $this->db->where('is_active', TRUE);
        $aplicadores = $this->db->get('users')->result();
        
        if (empty($aplicadores)) {
            return 'Nenhum aplicador válido';
        }
        
        $nomes = array_column($aplicadores, 'full_name');
        return implode(', ', $nomes);
    }

    /**
     * Retorna estatísticas de questionários por projeto
     * 
     * @return array Estatísticas agrupadas por projeto
     */
    public function get_stats_by_project() {
        $this->db->select('p.id, p.name, COUNT(q.id) as questionnaire_count, 
                          SUM(CASE WHEN q.status = "active" THEN 1 ELSE 0 END) as active_count');
        $this->db->from('projects p');
        $this->db->join('questionnaires q', 'p.id = q.project_id', 'left');
        $this->db->group_by('p.id, p.name');
        $this->db->order_by('questionnaire_count', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Buscar questionários por termo
     * 
     * @param string $term Termo de busca
     * @param int $project_id ID do projeto (opcional)
     * @return array Lista de questionários encontrados
     */
    public function search($term, $project_id = null) {
        $this->db->select('q.*, u.full_name as created_by_name, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        
        $this->db->group_start();
            $this->db->like('q.title', $term);
            $this->db->or_like('q.description', $term);
        $this->db->group_end();
        
        if ($project_id) {
            $this->db->where('q.project_id', $project_id);
        }
        
        $this->db->order_by('q.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Método para verificar se os campos existem na tabela
     * (método auxiliar para debug - remover em produção)
     */
    public function verify_table_structure() {
        if (ENVIRONMENT === 'development') {
            $query = $this->db->query("DESCRIBE questionnaires");
            $fields = $query->result();
            
            log_message('debug', 'Questionnaires table structure:');
            foreach ($fields as $field) {
                log_message('debug', 'Field: ' . $field->Field . ', Type: ' . $field->Type . ', Null: ' . $field->Null . ', Default: ' . $field->Default);
            }
        }
    }


    /**
     * Obter histórico de aplicações de questionários de um usuário
     * 
     * @param int $user_id ID do usuário aplicador
     * @param array $filters Filtros para a consulta
     * @return array Lista de aplicações
     */
    public function get_application_history($user_id, $filters = []) {
        $this->db->select('
            fr.id,
            fr.questionnaire_id,
            fr.respondent_name,
            fr.respondent_email,
            fr.latitude,
            fr.longitude,
            fr.location_name,
            fr.photo_path,
            fr.consent_given,
            fr.sync_status,
            fr.started_at,
            fr.completed_at,
            fr.created_at,
            q.title as questionnaire_title,
            q.id as questionnaire_code
        ');
        
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->where('fr.applied_by', $user_id);
        
        // Aplicar filtros de período
        if (!empty($filters['period'])) {
            switch ($filters['period']) {
                case 'today':
                    $this->db->where('DATE(fr.completed_at)', date('Y-m-d'));
                    break;
                case 'week':
                    $this->db->where('fr.completed_at >=', date('Y-m-d', strtotime('-7 days')));
                    break;
                case 'month':
                    $this->db->where('fr.completed_at >=', date('Y-m-d', strtotime('-30 days')));
                    break;
            }
        }
        
        // Filtro por status de sincronização
        if (!empty($filters['sync_status'])) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Filtro por questionário específico
        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        // Ordenação e paginação
        $this->db->order_by('fr.completed_at', 'DESC');
        $this->db->order_by('fr.created_at', 'DESC');
        
        if (!empty($filters['limit'])) {
            $this->db->limit($filters['limit'], $filters['offset'] ?? 0);
        }
        
        return $this->db->get()->result();
    }

    /**
     * Obter contadores para os filtros do histórico
     * 
     * @param int $user_id ID do usuário aplicador
     * @return array Contadores por categoria
     */
    public function get_history_counters($user_id) {
        $counters = [];
        
        // Total geral
        $this->db->where('applied_by', $user_id);
        $counters['total'] = $this->db->count_all_results('form_responses');
        
        // Hoje
        $this->db->where('applied_by', $user_id);
        $this->db->where('DATE(completed_at)', date('Y-m-d'));
        $counters['today'] = $this->db->count_all_results('form_responses');
        
        // Última semana
        $this->db->where('applied_by', $user_id);
        $this->db->where('completed_at >=', date('Y-m-d', strtotime('-7 days')));
        $counters['week'] = $this->db->count_all_results('form_responses');
        
        // Por status de sincronização
        $sync_statuses = ['pending', 'synced', 'error'];
        foreach ($sync_statuses as $status) {
            $this->db->where('applied_by', $user_id);
            $this->db->where('sync_status', $status);
            $counters[$status] = $this->db->count_all_results('form_responses');
        }
        
        return $counters;
    }

    /**
     * Obter resumo do histórico de um usuário
     * 
     * @param int $user_id ID do usuário aplicador
     * @return array Dados de resumo
     */
    public function get_history_summary($user_id) {
        // Estatísticas gerais
        $this->db->select('
            COUNT(*) as total_applications,
            COUNT(CASE WHEN sync_status = "synced" THEN 1 END) as synced_count,
            COUNT(CASE WHEN sync_status = "pending" THEN 1 END) as pending_count,
            COUNT(CASE WHEN sync_status = "error" THEN 1 END) as error_count,
            COUNT(CASE WHEN photo_path IS NOT NULL AND photo_path != "" THEN 1 END) as photos_captured,
            COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as locations_captured,
            COUNT(CASE WHEN consent_given = true THEN 1 END) as consents_given,
            COUNT(CASE WHEN DATE(completed_at) = CURDATE() THEN 1 END) as today_applications,
            COUNT(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_applications,
            COUNT(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_applications,
            MIN(completed_at) as first_application,
            MAX(completed_at) as last_application
        ');
        
        $this->db->from('form_responses');
        $this->db->where('applied_by', $user_id);
        $this->db->where('completed_at IS NOT NULL');
        
        $stats = $this->db->get()->row();
        
        if (!$stats) {
            return [
                'total_applications' => 0,
                'success_rate' => 0,
                'photos_captured' => 0,
                'locations_captured' => 0,
                'consents_given' => 0,
                'period_stats' => [
                    'today' => 0,
                    'week' => 0,
                    'month' => 0
                ],
                'sync_stats' => [
                    'synced' => 0,
                    'pending' => 0,
                    'error' => 0
                ],
                'activity_period' => [
                    'first_application' => null,
                    'last_application' => null,
                    'days_active' => 0
                ]
            ];
        }
        
        // Calcular taxa de sucesso
        $success_rate = $stats->total_applications > 0 
            ? round(($stats->synced_count / $stats->total_applications) * 100, 1) 
            : 0;
        
        // Calcular dias ativos
        $days_active = 0;
        if ($stats->first_application && $stats->last_application) {
            $first = new DateTime($stats->first_application);
            $last = new DateTime($stats->last_application);
            $days_active = $first->diff($last)->days + 1;
        }
        
        return [
            'total_applications' => (int)$stats->total_applications,
            'success_rate' => (float)$success_rate,
            'photos_captured' => (int)$stats->photos_captured,
            'locations_captured' => (int)$stats->locations_captured,
            'consents_given' => (int)$stats->consents_given,
            'period_stats' => [
                'today' => (int)$stats->today_applications,
                'week' => (int)$stats->week_applications,
                'month' => (int)$stats->month_applications
            ],
            'sync_stats' => [
                'synced' => (int)$stats->synced_count,
                'pending' => (int)$stats->pending_count,
                'error' => (int)$stats->error_count
            ],
            'activity_period' => [
                'first_application' => $stats->first_application,
                'last_application' => $stats->last_application,
                'days_active' => $days_active
            ]
        ];
    }

    /**
     * Obter aplicações recentes de um usuário
     * 
     * @param int $user_id ID do usuário aplicador
     * @param int $limit Limite de registros
     * @return array Lista das aplicações mais recentes
     */
    public function get_recent_applications($user_id, $limit = 5) {
        $this->db->select('
            fr.id,
            fr.questionnaire_id,
            fr.respondent_name,
            fr.location_name,
            fr.sync_status,
            fr.completed_at,
            fr.created_at,
            q.title as questionnaire_title
        ');
        
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->where('fr.applied_by', $user_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->order_by('fr.completed_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    /**
     * Obter estatísticas de questionários por usuário
     * 
     * @param int $user_id ID do usuário aplicador
     * @return array Estatísticas por questionário
     */
    public function get_user_questionnaire_stats($user_id) {
        $this->db->select('
            q.id,
            q.title,
            COUNT(fr.id) as total_applications,
            COUNT(CASE WHEN fr.sync_status = "synced" THEN 1 END) as synced_applications,
            MAX(fr.completed_at) as last_application,
            MIN(fr.completed_at) as first_application,
            AVG(CASE 
                WHEN fr.started_at IS NOT NULL AND fr.completed_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, fr.started_at, fr.completed_at)
                ELSE NULL 
            END) as avg_duration_minutes
        ');
        
        $this->db->from('questionnaires q');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id AND fr.applied_by = ' . (int)$user_id, 'inner');
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('total_applications', 'DESC');
        $this->db->limit(10); // Top 10 questionários mais aplicados
        
        $results = $this->db->get()->result();
        
        // Formatar os resultados
        $formatted = [];
        foreach ($results as $result) {
            $success_rate = $result->total_applications > 0 
                ? round(($result->synced_applications / $result->total_applications) * 100, 1) 
                : 0;
                
            $formatted[] = [
                'questionnaire_id' => (int)$result->id,
                'questionnaire_title' => $result->title,
                'questionnaire_code' => sprintf('#%03d', $result->id),
                'total_applications' => (int)$result->total_applications,
                'synced_applications' => (int)$result->synced_applications,
                'success_rate' => (float)$success_rate,
                'avg_duration_minutes' => $result->avg_duration_minutes ? round($result->avg_duration_minutes, 1) : null,
                'first_application' => $result->first_application,
                'last_application' => $result->last_application
            ];
        }
        
        return $formatted;
    }

    /**
     * Obter dados para gráfico de aplicações por período
     * 
     * @param int $user_id ID do usuário aplicador
     * @param int $days Número de dias para análise
     * @return array Dados para gráfico
     */
    public function get_applications_chart_data($user_id, $days = 30) {
        $this->db->select('
            DATE(completed_at) as application_date,
            COUNT(*) as total_applications,
            COUNT(CASE WHEN sync_status = "synced" THEN 1 END) as synced_applications,
            COUNT(CASE WHEN photo_path IS NOT NULL AND photo_path != "" THEN 1 END) as photos_captured
        ');
        
        $this->db->from('form_responses');
        $this->db->where('applied_by', $user_id);
        $this->db->where('completed_at IS NOT NULL');
        $this->db->where('completed_at >=', date('Y-m-d', strtotime("-{$days} days")));
        $this->db->group_by('DATE(completed_at)');
        $this->db->order_by('application_date', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Obter questionários disponíveis para um usuário aplicador
     * 
     * @param int $user_id ID do usuário aplicador
     * @return array Lista de questionários disponíveis
     */
    public function get_available_questionnaires_for_user($user_id) {
        $this->db->select('
            q.id,
            q.title,
            q.description,
            q.estimated_time,
            q.requires_consent,
            q.requires_location,
            q.requires_photo,
            p.name as project_name,
            COUNT(fr.id) as user_applications
        ');
        
        $this->db->from('questionnaires q');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id AND fr.applied_by = ' . (int)$user_id, 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id, q.title, q.description, q.estimated_time, q.requires_consent, q.requires_location, q.requires_photo, p.name');
        $this->db->order_by('q.title', 'ASC');
        
        $questionnaires = $this->db->get()->result();
        
        // Filtrar questionários que o usuário pode acessar
        $available = [];
        foreach ($questionnaires as $questionnaire) {
            if ($this->can_aplicador_access($questionnaire->id, $user_id)) {
                $available[] = $questionnaire;
            }
        }
        
        return $available;
    }

    /**
     * Obter detalhes de uma aplicação específica
     * 
     * @param int $response_id ID da resposta
     * @param int $user_id ID do usuário (para verificação de permissão)
     * @return object|null Detalhes da aplicação
     */
    public function get_application_details($response_id, $user_id) {
        $this->db->select('
            fr.*,
            q.title as questionnaire_title,
            q.description as questionnaire_description,
            q.estimated_time,
            p.name as project_name,
            u.full_name as applied_by_name
        ');
        
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->where('fr.id', $response_id);
        $this->db->where('fr.applied_by', $user_id); // Garantir que o usuário só acesse suas próprias aplicações
        
        $application = $this->db->get()->row();
        
        if ($application) {
            // Buscar as respostas das perguntas
            $this->db->select('
                qr.*,
                q.question_text,
                q.question_type
            ');
            $this->db->from('question_responses qr');
            $this->db->join('questions q', 'qr.question_id = q.id', 'left');
            $this->db->where('qr.form_response_id', $response_id);
            $this->db->order_by('q.order_index', 'ASC');
            
            $application->responses = $this->db->get()->result();
        }
        
        return $application;
    }

    public function validate_conditional_logic($questions) {
    $errors = [];
    $warnings = [];
    
    foreach ($questions as $index => $question) {
        if (!empty($question['conditional_logic'])) {
            $logic = json_decode($question['conditional_logic'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Pergunta " . ($index + 1) . ": Lógica condicional inválida (JSON malformado)";
                continue;
            }
            
            // Validar regras de visibilidade
            if (isset($logic['visibility'])) {
                $validation = $this->validate_condition_set($logic['visibility'], $questions, $index);
                if (!$validation['valid']) {
                    $errors = array_merge($errors, $validation['errors']);
                }
                $warnings = array_merge($warnings, $validation['warnings']);
            }
            
            // Validar regras de obrigatoriedade
            if (isset($logic['required'])) {
                $validation = $this->validate_condition_set($logic['required'], $questions, $index);
                if (!$validation['valid']) {
                    $errors = array_merge($errors, $validation['errors']);
                }
                $warnings = array_merge($warnings, $validation['warnings']);
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Validar um conjunto de condições
 * 
 * @param array $condition_set Conjunto de condições
 * @param array $questions Array de todas as perguntas
 * @param int $current_index Índice da pergunta atual
 * @return array Resultado da validação
 */
private function validate_condition_set($condition_set, $questions, $current_index) {
    $errors = [];
    $warnings = [];
    
    if (!isset($condition_set['conditions']) || !is_array($condition_set['conditions'])) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Condições não definidas corretamente";
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    $operator = $condition_set['operator'] ?? 'AND';
    if (!in_array($operator, ['AND', 'OR'])) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Operador lógico inválido ($operator)";
    }
    
    foreach ($condition_set['conditions'] as $condition) {
        $validation = $this->validate_single_condition($condition, $questions, $current_index);
        if (!$validation['valid']) {
            $errors = array_merge($errors, $validation['errors']);
        }
        $warnings = array_merge($warnings, $validation['warnings']);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Validar uma condição individual
 * 
 * @param array $condition Condição a ser validada
 * @param array $questions Array de todas as perguntas
 * @param int $current_index Índice da pergunta atual
 * @return array Resultado da validação
 */
private function validate_single_condition($condition, $questions, $current_index) {
    $errors = [];
    $warnings = [];
    
    // Verificar se a condição tem os campos obrigatórios
    if (!isset($condition['question']) || !isset($condition['operator'])) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Condição incompleta (pergunta ou operador ausente)";
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    $target_question_index = (int)$condition['question'];
    $operator = $condition['operator'];
    $value = $condition['value'] ?? '';
    
    // Verificar se a pergunta referenciada existe
    if (!isset($questions[$target_question_index])) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Referencia pergunta inexistente (índice $target_question_index)";
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    // Verificar se não está referenciando uma pergunta posterior
    if ($target_question_index >= $current_index) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Não pode referenciar pergunta posterior ou a si mesma";
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }
    
    // Verificar se o operador é válido
    $valid_operators = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
    if (!in_array($operator, $valid_operators)) {
        $errors[] = "Pergunta " . ($current_index + 1) . ": Operador inválido ($operator)";
    }
    
    // Validar valor baseado no tipo da pergunta referenciada
    $target_question = $questions[$target_question_index];
    $target_type = $target_question['type'] ?? 'text';
    
    if (in_array($operator, ['greater_than', 'less_than']) && $target_type !== 'number') {
        $warnings[] = "Pergunta " . ($current_index + 1) . ": Operador numérico ($operator) usado em pergunta não numérica";
    }
    
    if (in_array($target_type, ['radio', 'checkbox', 'select']) && !empty($value)) {
        // Verificar se o valor existe nas opções da pergunta referenciada
        $target_options = $target_question['options'] ?? [];
        $valid_values = array_column($target_options, 'text');
        
        if (!in_array($value, $valid_values)) {
            $warnings[] = "Pergunta " . ($current_index + 1) . ": Valor '$value' não existe nas opções da pergunta referenciada";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Executar lógica condicional em um conjunto de respostas
 * 
 * @param array $questions Perguntas do questionário
 * @param array $responses Respostas fornecidas
 * @return array Estado das perguntas (visibilidade e obrigatoriedade)
 */
public function execute_conditional_logic($questions, $responses) {
    $question_states = [];
    
    foreach ($questions as $index => $question) {
        $question_id = "q_$index";
        
        // Estado padrão
        $question_states[$question_id] = [
            'visible' => true,
            'required' => (bool)($question['is_required'] ?? false),
            'original_required' => (bool)($question['is_required'] ?? false)
        ];
        
        if (!empty($question['conditional_logic'])) {
            $logic = json_decode($question['conditional_logic'], true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Avaliar regras de visibilidade
                if (isset($logic['visibility'])) {
                    $visible = $this->evaluate_condition_set($logic['visibility'], $questions, $responses);
                    $question_states[$question_id]['visible'] = $visible;
                }
                
                // Avaliar regras de obrigatoriedade
                if (isset($logic['required'])) {
                    $conditional_required = $this->evaluate_condition_set($logic['required'], $questions, $responses);
                    $question_states[$question_id]['required'] = $question_states[$question_id]['original_required'] || $conditional_required;
                }
            }
        }
    }
    
    return $question_states;
}

/**
 * Avaliar um conjunto de condições
 * 
 * @param array $condition_set Conjunto de condições
 * @param array $questions Perguntas do questionário
 * @param array $responses Respostas fornecidas
 * @return bool Resultado da avaliação
 */
private function evaluate_condition_set($condition_set, $questions, $responses) {
    $operator = $condition_set['operator'] ?? 'AND';
    $conditions = $condition_set['conditions'] ?? [];
    
    if (empty($conditions)) {
        return true;
    }
    
    $results = [];
    foreach ($conditions as $condition) {
        $results[] = $this->evaluate_single_condition_runtime($condition, $questions, $responses);
    }
    
    if ($operator === 'AND') {
        return !in_array(false, $results);
    } else { // OR
        return in_array(true, $results);
    }
}

/**
 * Avaliar uma condição individual em runtime
 * 
 * @param array $condition Condição a ser avaliada
 * @param array $questions Perguntas do questionário
 * @param array $responses Respostas fornecidas
 * @return bool Resultado da avaliação
 */
private function evaluate_single_condition_runtime($condition, $questions, $responses) {
    $target_question_index = (int)$condition['question'];
    $operator = $condition['operator'];
    $expected_value = $condition['value'] ?? '';
    
    // Obter valor da resposta
    $response_key = "q_$target_question_index";
    $actual_value = $responses[$response_key] ?? '';
    
    switch ($operator) {
        case 'equals':
            return $this->compare_values($actual_value, $expected_value, 'equals');
            
        case 'not_equals':
            return !$this->compare_values($actual_value, $expected_value, 'equals');
            
        case 'contains':
            return $this->compare_values($actual_value, $expected_value, 'contains');
            
        case 'not_contains':
            return !$this->compare_values($actual_value, $expected_value, 'contains');
            
        case 'greater_than':
            return $this->compare_values($actual_value, $expected_value, 'greater');
            
        case 'less_than':
            return $this->compare_values($actual_value, $expected_value, 'less');
            
        case 'is_empty':
            return $this->is_value_empty($actual_value);
            
        case 'is_not_empty':
            return !$this->is_value_empty($actual_value);
            
        default:
            return false;
    }
}

/**
 * Comparar valores baseado no tipo de comparação
 * 
 * @param mixed $value1 Primeiro valor
 * @param mixed $value2 Segundo valor
 * @param string $comparison Tipo de comparação
 * @return bool Resultado da comparação
 */
private function compare_values($value1, $value2, $comparison) {
    switch ($comparison) {
        case 'equals':
            if (is_array($value1)) {
                return in_array($value2, $value1);
            }
            return strcasecmp(trim($value1), trim($value2)) === 0;
            
        case 'contains':
            if (is_array($value1)) {
                foreach ($value1 as $v) {
                    if (stripos($v, $value2) !== false) {
                        return true;
                    }
                }
                return false;
            }
            return stripos($value1, $value2) !== false;
            
        case 'greater':
            $num1 = floatval($value1);
            $num2 = floatval($value2);
            return $num1 > $num2;
            
        case 'less':
            $num1 = floatval($value1);
            $num2 = floatval($value2);
            return $num1 < $num2;
            
        default:
            return false;
    }
}

    /**
     * Verificar se valor está vazio
     * 
     * @param mixed $value Valor a ser verificado
     * @return bool True se estiver vazio
     */
    private function is_value_empty($value) {
        if (is_null($value)) {
            return true;
        }
        
        if (is_array($value)) {
            return empty($value);
        }
        
        return trim($value) === '';
    }

    /**
     * Validar respostas considerando lógica condicional
     * 
     * @param array $questions Perguntas do questionário
     * @param array $responses Respostas fornecidas
     * @return array Resultado da validação
     */
    public function validate_responses_with_conditional_logic($questions, $responses) {
        $errors = [];
        $question_states = $this->execute_conditional_logic($questions, $responses);
        
        foreach ($questions as $index => $question) {
            $question_id = "q_$index";
            $state = $question_states[$question_id];
            
            // Só validar perguntas visíveis
            if (!$state['visible']) {
                continue;
            }
            
            $response_value = $responses[$question_id] ?? '';
            
            // Verificar se pergunta obrigatória está preenchida
            if ($state['required'] && $this->is_value_empty($response_value)) {
                $errors[] = [
                    'question_index' => $index,
                    'question_text' => $question['question_text'] ?? "Pergunta " . ($index + 1),
                    'error' => 'Esta pergunta é obrigatória'
                ];
            }
            
            // Validações específicas por tipo de pergunta
            $validation = $this->validate_question_response($question, $response_value);
            if (!$validation['valid']) {
                $errors[] = [
                    'question_index' => $index,
                    'question_text' => $question['question_text'] ?? "Pergunta " . ($index + 1),
                    'error' => $validation['error']
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'question_states' => $question_states
        ];
    }

    /**
     * Validar resposta de uma pergunta específica
     * 
     * @param array $question Dados da pergunta
     * @param mixed $response_value Valor da resposta
     * @return array Resultado da validação
     */
    private function validate_question_response($question, $response_value) {
        $question_type = $question['question_type'] ?? 'text';
        
        switch ($question_type) {
            case 'email':
                if (!empty($response_value) && !filter_var($response_value, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'error' => 'E-mail inválido'];
                }
                break;
                
            case 'number':
                if (!empty($response_value) && !is_numeric($response_value)) {
                    return ['valid' => false, 'error' => 'Deve ser um número válido'];
                }
                break;
                
            case 'radio':
            case 'select':
                if (!empty($response_value)) {
                    $valid_options = array_column($question['options'] ?? [], 'option_value');
                    if (!in_array($response_value, $valid_options)) {
                        return ['valid' => false, 'error' => 'Opção inválida selecionada'];
                    }
                }
                break;
                
            case 'checkbox':
                if (!empty($response_value) && is_array($response_value)) {
                    $valid_options = array_column($question['options'] ?? [], 'option_value');
                    foreach ($response_value as $value) {
                        if (!in_array($value, $valid_options)) {
                            return ['valid' => false, 'error' => 'Opção inválida selecionada'];
                        }
                    }
                }
                break;
        }
        
        return ['valid' => true, 'error' => null];
    }

    /**
     * Processar respostas removendo dados de perguntas ocultas
     * 
     * @param array $questions Perguntas do questionário
     * @param array $responses Respostas fornecidas
     * @return array Respostas processadas
     */
    public function process_responses_with_conditional_logic($questions, $responses) {
        $question_states = $this->execute_conditional_logic($questions, $responses);
        $processed_responses = [];
        
        foreach ($responses as $question_id => $response_value) {
            $state = $question_states[$question_id] ?? ['visible' => true];
            
            // Só incluir respostas de perguntas visíveis
            if ($state['visible']) {
                $processed_responses[$question_id] = $response_value;
            }
        }
        
        return $processed_responses;
    }

}