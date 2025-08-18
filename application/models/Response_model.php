<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Response_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->select('fr.*, q.title as questionnaire_title, u.full_name as applied_by_name');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->order_by('fr.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_filtered($filters = array()) {
        $this->db->select('fr.*, q.title as questionnaire_title, u.full_name as applied_by_name');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        $this->db->order_by('fr.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        $this->db->select('fr.*, q.title as questionnaire_title, u.full_name as applied_by_name');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->where('fr.id', $id);
        return $this->db->get()->row();
    }

    public function get_answers($form_response_id) {
        $this->db->select('qr.*, q.question_text, q.question_type');
        $this->db->from('question_responses qr');
        $this->db->join('questions q', 'qr.question_id = q.id', 'left');
        $this->db->where('qr.form_response_id', $form_response_id);
        $this->db->order_by('q.order_index', 'ASC');
        return $this->db->get()->result();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('form_responses', $data) ? $this->db->insert_id() : FALSE;
    }

    public function create_answer($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('question_responses', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_sync_status($id, $status) {
        $this->db->where('id', $id);
        return $this->db->update('form_responses', array('sync_status' => $status));
    }

    public function count_all() {
        return $this->db->count_all('form_responses');
    }

    public function count_today() {
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        return $this->db->count_all_results('form_responses');
    }

    public function count_pending_sync() {
        $this->db->where('sync_status', 'pending');
        return $this->db->count_all_results('form_responses');
    }

    public function get_recent($limit = 10) {
        $this->db->select('fr.*, q.title as questionnaire_title, u.full_name as applied_by_name');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->order_by('fr.created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function get_responses_by_day($days = 30) {
        $this->db->select('DATE(created_at) as date, COUNT(*) as count');
        $this->db->where('created_at >=', date('Y-m-d', strtotime("-{$days} days")));
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        return $this->db->get('form_responses')->result();
    }

    public function count_by_filters($filters = array()) {
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        return $this->db->count_all_results();
    }

    public function count_unique_respondents($filters = array()) {
        $this->db->select('COUNT(DISTINCT fr.respondent_email) as count');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar os mesmos filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Garantir que só conte emails não nulos/vazios
        $this->db->where('fr.respondent_email IS NOT NULL');
        $this->db->where("fr.respondent_email != ''");
        
        $result = $this->db->get()->row();
        return $result ? $result->count : 0;
    }

    public function get_with_location($filters = array()) {
        $this->db->select('
            fr.id,
            fr.questionnaire_id,
            fr.respondent_name,
            fr.respondent_email,
            fr.applied_by,
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
            u.full_name as applied_by_name
        ');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Filtrar apenas respostas que possuem localização
        $this->db->where('fr.latitude IS NOT NULL');
        $this->db->where('fr.longitude IS NOT NULL');
        $this->db->where('fr.latitude !=', 0);
        $this->db->where('fr.longitude !=', 0);
        
        // Aplicar filtros adicionais se fornecidos
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Filtrar apenas respostas concluídas
        $this->db->where('fr.completed_at IS NOT NULL');
        
        // Ordenar por data de conclusão (mais recentes primeiro)
        $this->db->order_by('fr.completed_at', 'DESC');
        
        return $this->db->get()->result();
    }

    public function count_photos($filters = array()) {
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar os mesmos filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Contar apenas respostas que têm fotos (photo_path não é nulo/vazio)
        $this->db->where('fr.photo_path IS NOT NULL');
        $this->db->where("fr.photo_path != ''");
        
        return $this->db->count_all_results();
    }

    public function count_locations($filters = array()) {
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar os mesmos filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Contar apenas respostas que têm localização (latitude e longitude não são nulas)
        $this->db->where('fr.latitude IS NOT NULL');
        $this->db->where('fr.longitude IS NOT NULL');
        
        return $this->db->count_all_results();
    }

    public function get_consent_rate($filters = array()) {
        // Primeiro, contar o total de respostas com os filtros
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar os mesmos filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        $total = $this->db->count_all_results();
        
        if ($total == 0) {
            return 0;
        }
        
        // Agora contar quantas deram consentimento
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar os mesmos filtros novamente
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Filtrar apenas os que deram consentimento
        $this->db->where('fr.consent_given', TRUE);
        
        $with_consent = $this->db->count_all_results();
        
        // Calcular a taxa de consentimento em porcentagem
        return round(($with_consent / $total) * 100, 2);
    }

    public function get_responses_by_day_filtered($filters = array(), $days = 30) {
        $this->db->select('DATE(fr.created_at) as date, COUNT(*) as count');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Filtro base de período (últimos X dias)
        $this->db->where('fr.created_at >=', date('Y-m-d', strtotime("-{$days} days")));
        
        // Aplicar filtros adicionais
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        $this->db->group_by('DATE(fr.created_at)');
        $this->db->order_by('date', 'ASC');
        
        return $this->db->get()->result();
    }

    public function get_top_applicators($filters = array(), $limit = 10) {
        $this->db->select('u.id, u.full_name, COUNT(fr.id) as total_responses');
        $this->db->from('form_responses fr');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        
        // Aplicar filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Garantir que há um aplicador válido
        $this->db->where('fr.applied_by IS NOT NULL');
        $this->db->where('u.full_name IS NOT NULL');
        
        // Incluir todas as colunas não agregadas no GROUP BY
        $this->db->group_by('u.id, u.full_name');
        $this->db->order_by('total_responses', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_questionnaires_popularity($filters = array(), $limit = 10) {
        $this->db->select('
            q.id, 
            q.title, 
            COUNT(fr.id) as total_applications,
            MAX(fr.completed_at) as last_application
        ');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar filtros
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        // Garantir que há um questionário válido
        $this->db->where('fr.questionnaire_id IS NOT NULL');
        $this->db->where('q.title IS NOT NULL');
        
        // Incluir todas as colunas não agregadas no GROUP BY
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('total_applications', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_detailed_analysis($filters = array()) {
        // Em vez de retornar um array simples, vamos retornar dados por questionário
        $this->db->select("
            q.id as questionnaire_id,
            q.title as questionnaire_title,
            COUNT(fr.id) as total_responses,
            COUNT(CASE WHEN fr.photo_path IS NOT NULL AND fr.photo_path != '' THEN 1 END) as photos_count,
            COUNT(CASE WHEN fr.latitude IS NOT NULL AND fr.longitude IS NOT NULL THEN 1 END) as locations_count,
            AVG(CASE WHEN fr.completed_at IS NOT NULL AND fr.started_at IS NOT NULL 
                THEN EXTRACT(EPOCH FROM (fr.completed_at - fr.started_at))/60 END) as avg_time,
            COUNT(CASE WHEN fr.completed_at IS NOT NULL THEN 1 END) as completed_responses
        ");
        $this->db->from('questionnaires q');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar filtros
        $this->db->where('q.status', 'active'); // Apenas questionários ativos
        
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('q.id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['applied_by']) && $filters['applied_by']) {
            $this->db->where('fr.applied_by', $filters['applied_by']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['sync_status']) && $filters['sync_status']) {
            $this->db->where('fr.sync_status', $filters['sync_status']);
        }
        
        $this->db->group_by('q.id, q.title');
        $this->db->having('COUNT(fr.id) >', 0); // Apenas questionários com respostas
        $this->db->order_by('COUNT(fr.id)', 'DESC');
        
        $questionnaire_analysis = $this->db->get()->result();
        
        // Calcular métricas adicionais para cada questionário
        foreach ($questionnaire_analysis as $analysis) {
            // Calcular média por dia (assumindo período de 30 dias se não especificado)
            $days_in_period = 30;
            if (isset($filters['date_from']) && isset($filters['date_to'])) {
                $date_from = new DateTime($filters['date_from']);
                $date_to = new DateTime($filters['date_to']);
                $days_in_period = max(1, $date_to->diff($date_from)->days);
            } elseif (isset($filters['period'])) {
                switch ($filters['period']) {
                    case 'last_7_days':
                        $days_in_period = 7;
                        break;
                    case 'last_30_days':
                        $days_in_period = 30;
                        break;
                    case 'last_3_months':
                        $days_in_period = 90;
                        break;
                }
            }
            
            $analysis->avg_per_day = round($analysis->total_responses / $days_in_period, 1);
            
            // Taxa de conclusão (assumindo que nem todas as respostas foram concluídas)
            $analysis->completion_rate = $analysis->total_responses > 0 
                ? round(($analysis->completed_responses / $analysis->total_responses) * 100, 1)
                : 0;
            
            // Formatar tempo médio
            $analysis->avg_time = $analysis->avg_time ? round($analysis->avg_time, 1) : 0;
        }
        
        return $questionnaire_analysis;
    }

    public function get_sync_status_stats() {
        $this->db->select('sync_status, COUNT(*) as count');
        $this->db->group_by('sync_status');
        return $this->db->get('form_responses')->result();
    }

    public function get_for_export($filters = array()) {
        // Implementar lógica de exportação com joins complexos
        $this->db->select('
            fr.id,
            q.title as questionnaire,
            u.full_name as aplicador,
            fr.respondent_name,
            fr.respondent_email,
            fr.latitude,
            fr.longitude,
            fr.location_name,
            fr.consent_given,
            fr.completed_at,
            fr.sync_status
        ');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        
        // Aplicar filtros similares ao get_filtered
        if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
            $this->db->where('fr.questionnaire_id', $filters['questionnaire_id']);
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        $this->db->order_by('fr.completed_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Obter estatísticas de aplicadores para supervisores
     * CORRIGIDO: Usar aspas simples para PostgreSQL
     */
    public function get_applicators_stats($filters = array()) {
        $this->db->select("
            u.id,
            u.full_name,
            u.username,
            u.is_active,
            COUNT(fr.id) as total_forms,
            COUNT(CASE WHEN DATE(fr.completed_at) = CURRENT_DATE THEN 1 END) as today_forms,
            COUNT(CASE WHEN fr.photo_path IS NOT NULL AND fr.photo_path != '' THEN 1 END) as photos_captured,
            COUNT(CASE WHEN fr.latitude IS NOT NULL AND fr.longitude IS NOT NULL THEN 1 END) as locations_captured,
            MAX(fr.completed_at) as last_activity,
            COUNT(DISTINCT DATE(fr.completed_at)) as active_days
        ");
        $this->db->from('users u');
        $this->db->join('form_responses fr', 'u.id = fr.applied_by', 'left');
        $this->db->where('u.role', 'aplicador');
        
        // Aplicar filtros
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'active') {
                $this->db->where('u.is_active', TRUE);
            } elseif ($filters['status'] === 'inactive') {
                $this->db->where('u.is_active', FALSE);
            }
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        // Incluir todas as colunas não agregadas no GROUP BY
        $this->db->group_by('u.id, u.full_name, u.username, u.is_active');
        $this->db->order_by('total_forms', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Obter estatísticas por localização/região
     */
    public function get_location_stats($filters = array()) {
        $this->db->select('
            fr.location_name,
            COUNT(fr.id) as total_forms,
            COUNT(DISTINCT fr.applied_by) as unique_applicators,
            AVG(fr.latitude) as avg_latitude,
            AVG(fr.longitude) as avg_longitude
        ');
        $this->db->from('form_responses fr');
        $this->db->where('fr.location_name IS NOT NULL');
        $this->db->where("fr.location_name != ''");
        
        // Aplicar filtros de data
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        $this->db->group_by('fr.location_name');
        $this->db->order_by('total_forms', 'DESC');
        $this->db->limit(20); // Top 20 localizações
        
        return $this->db->get()->result();
    }

    /**
     * Obter resumo para supervisores
     */
    public function get_supervisor_summary($filters = array()) {
        $summary = array();
        
        // Total de aplicadores
        $this->db->where('role', 'aplicador');
        $summary['total_applicators'] = $this->db->count_all_results('users');
        
        // Aplicadores ativos
        $this->db->where('role', 'aplicador');
        $this->db->where('is_active', TRUE);
        $summary['active_applicators'] = $this->db->count_all_results('users');
        
        // Total de formulários no período
        $this->db->from('form_responses fr');
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        $summary['total_forms'] = $this->db->count_all_results();
        
        // Formulários hoje
        $summary['today_forms'] = $this->count_by_filters(array(
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d')
        ));
        
        // Regiões cobertas
        $this->db->select('COUNT(DISTINCT location_name) as covered_regions');
        $this->db->from('form_responses');
        $this->db->where('location_name IS NOT NULL');
        $this->db->where("location_name != ''");
        $regions_result = $this->db->get()->row();
        $summary['covered_regions'] = $regions_result ? $regions_result->covered_regions : 0;
        
        // Taxa de produtividade
        $summary['productivity_rate'] = $summary['active_applicators'] > 0 
            ? round($summary['total_forms'] / $summary['active_applicators'], 1) 
            : 0;
        
        return $summary;
    }

    /**
     * Obter dados para o mapa de aplicadores
     */
    public function get_map_locations($filters = array()) {
        $this->db->select('
            fr.latitude,
            fr.longitude,
            fr.location_name,
            fr.applied_by,
            u.full_name as applicator_name,
            COUNT(fr.id) as forms_count,
            MAX(fr.completed_at) as last_activity
        ');
        $this->db->from('form_responses fr');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->where('fr.latitude IS NOT NULL');
        $this->db->where('fr.longitude IS NOT NULL');
        $this->db->where('fr.latitude !=', 0);
        $this->db->where('fr.longitude !=', 0);
        
        // Aplicar filtros
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
        }
        
        if (isset($filters['applicator_id']) && $filters['applicator_id']) {
            $this->db->where('fr.applied_by', $filters['applicator_id']);
        }
        
        // Incluir todas as colunas não agregadas no GROUP BY para PostgreSQL
        $this->db->group_by('fr.latitude, fr.longitude, fr.location_name, fr.applied_by, u.full_name');
        $this->db->order_by('forms_count', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Obter estatísticas de cobertura territorial
     * CORRIGIDO: Compatibilidade com PostgreSQL
     */
    public function get_coverage_stats($filters = array()) {
        // Número total de pontos únicos de coleta
        $this->db->select("COUNT(DISTINCT CONCAT(latitude::text, ',', longitude::text)) as unique_points");
        $this->db->from('form_responses');
        $this->db->where('latitude IS NOT NULL');
        $this->db->where('longitude IS NOT NULL');
        $this->db->where('latitude !=', 0);
        $this->db->where('longitude !=', 0);
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('DATE(completed_at) >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('DATE(completed_at) <=', $filters['date_to']);
        }
        
        $unique_points_result = $this->db->get()->row();
        $unique_points = $unique_points_result ? $unique_points_result->unique_points : 0;
        
        // Novas áreas este mês usando EXTRACT para PostgreSQL
        $this->db->select('COUNT(DISTINCT location_name) as new_areas');
        $this->db->from('form_responses');
        $this->db->where('EXTRACT(YEAR FROM completed_at) =', date('Y'));
        $this->db->where('EXTRACT(MONTH FROM completed_at) =', date('n'));
        $this->db->where('location_name IS NOT NULL');
        $this->db->where("location_name != ''");
        $new_areas_result = $this->db->get()->row();
        $new_areas = $new_areas_result ? $new_areas_result->new_areas : 0;
        
        // Zonas de alta densidade usando subconsulta compatível com PostgreSQL
        $high_density_query = "
            SELECT COUNT(*) as high_density_zones
            FROM (
                SELECT location_name, COUNT(*) as forms_count 
                FROM form_responses 
                WHERE location_name IS NOT NULL AND location_name != ''
                GROUP BY location_name 
                HAVING COUNT(*) > 10
            ) as density_zones
        ";
        
        $high_density_result = $this->db->query($high_density_query)->row();
        $high_density = $high_density_result ? $high_density_result->high_density_zones : 0;
        
        return array(
            'unique_collection_points' => (int)$unique_points,
            'coverage_percentage' => min(100, ($unique_points * 2)), // Cálculo simplificado
            'new_areas_this_month' => (int)$new_areas,
            'high_density_zones' => (int)$high_density,
            'total_area_covered' => $unique_points . ' pontos únicos'
        );
    }

    /**
     * Obter localização primária de um aplicador
     */
    public function get_applicator_primary_location($applicator_id) {
        $this->db->select('location_name, COUNT(*) as count');
        $this->db->from('form_responses');
        $this->db->where('applied_by', $applicator_id);
        $this->db->where('location_name IS NOT NULL');
        $this->db->where("location_name != ''");
        $this->db->group_by('location_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(1);
        
        $result = $this->db->get()->row();
        return $result ? $result->location_name : 'Não definida';
    }

    /**
     * Obter dias ativos de um usuário
     */
    public function get_active_days($user_id, $days = 30) {
        $this->db->select('COUNT(DISTINCT DATE(completed_at)) as active_days');
        $this->db->from('form_responses');
        $this->db->where('applied_by', $user_id);
        $this->db->where('completed_at IS NOT NULL');
        $this->db->where('completed_at >=', date('Y-m-d', strtotime("-{$days} days")));
        
        $result = $this->db->get()->row();
        return $result ? $result->active_days : 0;
    }

    /**
     * Obter atividade recente de um usuário
     */
    public function get_recent_activity($user_id, $limit = 10) {
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
        
        return $this->db->get()->result();
    }

    /**
     * Obter estatísticas de um período específico
     */
    public function get_period_stats($user_id, $days) {
        $filters = array(
            'applied_by' => $user_id,
            'date_from' => date('Y-m-d', strtotime("-{$days} days")),
            'date_to' => date('Y-m-d')
        );
        
        $total = $this->count_by_filters($filters);
        $photos = $this->count_photos($filters);
        $locations = $this->count_locations($filters);
        $avg_per_day = $days > 0 ? round($total / $days, 1) : 0;
        
        return array(
            'total_forms' => (int)$total,
            'photos_captured' => (int)$photos,
            'locations_captured' => (int)$locations,
            'avg_per_day' => (float)$avg_per_day,
            'period_days' => (int)$days
        );
    }

    /**
     * Obter questionários mais aplicados por um usuário
     */
    public function get_top_questionnaires_by_user($user_id, $limit = 5) {
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
        
        // Incluir todas as colunas não agregadas no GROUP BY
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('total_applications', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    /**
     * Verificar se usuário é supervisor ou administrador
     */
    public function is_supervisor_or_admin($user_id) {
        $this->db->select('role');
        $this->db->where('id', $user_id);
        $user = $this->db->get('users')->row();
        
        if (!$user) {
            return false;
        }
        
        // Lista completa de roles que têm permissões administrativas
        $admin_roles = array(
            'supervisor', 
            'administrador', 
            'admin', 
            'administrator',
            'gestor',
            'manager'
        );
        
        return in_array(strtolower($user->role), $admin_roles);
    }

    /**
     * Obter informações do usuário
     */
    public function get_user_info($user_id) {
        $this->db->select('id, username, full_name, role, is_active');
        $this->db->where('id', $user_id);
        return $this->db->get('users')->row();
    }

    /**
     * Obter dados agregados para administradores
     * Este método retorna estatísticas de todo o sistema
     */
    public function get_admin_aggregated_stats() {
        // Total de formulários de todos os aplicadores
        $total_forms = $this->count_all();
        
        // Formulários hoje
        $today_forms = $this->count_today();
        
        // Pendentes de sincronização
        $pending_sync = $this->count_pending_sync();
        
        // Fotos capturadas (total)
        $photos_captured = $this->count_photos([]);
        
        // Taxa de sucesso geral
        $synced_forms = $this->count_by_filters(['sync_status' => 'synced']);
        $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;
        
        return [
            'total_forms' => (int)$total_forms,
            'today_forms' => (int)$today_forms,
            'pending_sync' => (int)$pending_sync,
            'photos_captured' => (int)$photos_captured,
            'success_rate' => (int)$success_rate
        ];
    }

    /**
     * Contar total de aplicadores
     */
    public function count_applicators($status = 'all') {
        $this->db->where('role', 'aplicador');
        
        if ($status === 'active') {
            $this->db->where('is_active', TRUE);
        } elseif ($status === 'inactive') {
            $this->db->where('is_active', FALSE);
        }
        
        return $this->db->count_all_results('users');
    }

/**
 * Analisar respostas de texto
 */
private function analyze_text_responses($question_id, $filters, $total_responses) {
    $this->db->select('
        COUNT(*) as total_text_responses,
        COUNT(CASE WHEN response_text IS NOT NULL AND response_text != \'\' THEN 1 END) as filled_responses,
        AVG(LENGTH(response_text)) as avg_length
    ');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $result = $this->db->get()->row();
    
    $filled = $result ? $result->filled_responses : 0;
    $empty = $total_responses - $filled;
    
    return array(
        array(
            'label' => 'Respostas Preenchidas',
            'count' => (int)$filled,
            'percentage' => round(($filled / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Respostas Vazias',
            'count' => (int)$empty,
            'percentage' => round(($empty / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Comprimento Médio',
            'count' => $result ? round($result->avg_length, 0) : 0,
            'percentage' => null,
            'unit' => 'caracteres'
        )
    );
}

public function get_specific_questionnaire_analysis($questionnaire_id, $filters = array()) {
    // Primeiro, obter informações do questionário
    $this->db->select('
        q.id,
        q.title,
        q.description,
        q.created_at,
        q.requires_consent,
        q.requires_location,
        q.requires_photo,
        q.estimated_time,
        u.full_name as created_by_name
    ');
    $this->db->from('questionnaires q');
    $this->db->join('users u', 'q.created_by = u.id', 'left');
    $this->db->where('q.id', $questionnaire_id);
    $questionnaire = $this->db->get()->row();
    
    if (!$questionnaire) {
        return array('error' => 'Questionário não encontrado');
    }
    
    // Obter todas as questões do questionário
    $this->db->select('
        q.id,
        q.question_text,
        q.question_type,
        q.is_required,
        q.order_index
    ');
    $this->db->from('questions q');
    $this->db->where('q.questionnaire_id', $questionnaire_id);
    $this->db->order_by('q.order_index');
    $questions = $this->db->get()->result_array();
    
    // Para cada questão, obter suas opções (se aplicável)
    foreach ($questions as &$question) {
        if (in_array($question['question_type'], ['radio', 'checkbox'])) {
            $this->db->select('id, option_text, option_value, order_index');
            $this->db->from('question_options');
            $this->db->where('question_id', $question['id']);
            $this->db->order_by('order_index');
            $question['options'] = $this->db->get()->result_array();
        } else {
            $question['options'] = array();
        }
        
        // Obter estatísticas da questão
        $question['statistics'] = $this->get_question_statistics($question['id'], $filters);
    }
    
    // Calcular resumo geral do questionário
    $summary = $this->get_questionnaire_summary($questionnaire_id, $filters);
    
    return array(
        'questionnaire' => $questionnaire,
        'questions' => $questions,
        'summary' => $summary
    );
}

/**
 * MÉTODO QUE FALTAVA: Obter resumo estatístico de um questionário
 */
public function get_questionnaire_summary($questionnaire_id, $filters = array()) {
    // Total de respostas do questionário
    $this->db->select('COUNT(*) as total_responses');
    $this->db->from('form_responses fr');
    $this->db->where('fr.questionnaire_id', $questionnaire_id);
    $this->db->where('fr.completed_at IS NOT NULL');
    
    // Aplicar filtros de data
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $total_result = $this->db->get()->row();
    $total_responses = $total_result ? $total_result->total_responses : 0;
    
    // Respondentes únicos
    $this->db->select('COUNT(DISTINCT fr.respondent_email) as unique_respondents');
    $this->db->from('form_responses fr');
    $this->db->where('fr.questionnaire_id', $questionnaire_id);
    $this->db->where('fr.completed_at IS NOT NULL');
    $this->db->where('fr.respondent_email IS NOT NULL');
    $this->db->where("fr.respondent_email != ''");
    
    // Aplicar mesmos filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $unique_result = $this->db->get()->row();
    $unique_respondents = $unique_result ? $unique_result->unique_respondents : 0;
    
    // Tempo médio de conclusão (compatível com PostgreSQL)
    $sql = "SELECT AVG(EXTRACT(EPOCH FROM (completed_at - started_at))/60) as avg_time
            FROM form_responses 
            WHERE questionnaire_id = ? 
            AND completed_at IS NOT NULL 
            AND started_at IS NOT NULL";
    
    $params = array($questionnaire_id);
    
    if (isset($filters['date_from']) && $filters['date_from']) {
        $sql .= " AND DATE(completed_at) >= ?";
        $params[] = $filters['date_from'];
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $sql .= " AND DATE(completed_at) <= ?";
        $params[] = $filters['date_to'];
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $sql .= " AND applied_by = ?";
        $params[] = $filters['applied_by'];
    }
    
    $time_result = $this->db->query($sql, $params)->row();
    $avg_time = $time_result && $time_result->avg_time ? round($time_result->avg_time, 1) : 0;
    
    // Última resposta
    $this->db->select('MAX(completed_at) as last_response');
    $this->db->from('form_responses fr');
    $this->db->where('fr.questionnaire_id', $questionnaire_id);
    $this->db->where('fr.completed_at IS NOT NULL');
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $last_result = $this->db->get()->row();
    $last_response = $last_result && $last_result->last_response ? 
        date('d/m/Y H:i', strtotime($last_result->last_response)) : null;
    
    // Contagem de questões
    $this->db->select('COUNT(*) as total_questions');
    $this->db->from('questions');
    $this->db->where('questionnaire_id', $questionnaire_id);
    $questions_result = $this->db->get()->row();
    $total_questions = $questions_result ? $questions_result->total_questions : 0;
    
    // Taxa de conclusão média (simplificada)
    $avg_completion_rate = 0;
    if ($total_questions > 0 && $total_responses > 0) {
        // Calcular taxa baseada na média de respostas por questão
        $this->db->select('AVG(question_responses_count) as avg_responses');
        $this->db->from('(
            SELECT COUNT(qr.id) as question_responses_count
            FROM questions q
            LEFT JOIN question_responses qr ON q.id = qr.question_id
            LEFT JOIN form_responses fr ON qr.form_response_id = fr.id
            WHERE q.questionnaire_id = ' . $questionnaire_id . '
            ' . (isset($filters['date_from']) && $filters['date_from'] ? 
                'AND DATE(fr.completed_at) >= \'' . $filters['date_from'] . '\'' : '') . '
            ' . (isset($filters['date_to']) && $filters['date_to'] ? 
                'AND DATE(fr.completed_at) <= \'' . $filters['date_to'] . '\'' : '') . '
            ' . (isset($filters['applied_by']) && $filters['applied_by'] ? 
                'AND fr.applied_by = ' . $filters['applied_by'] : '') . '
            GROUP BY q.id
        ) as question_stats', false);
        
        $rate_result = $this->db->get()->row();
        $avg_completion_rate = $rate_result && $rate_result->avg_responses ? 
            round(($rate_result->avg_responses / $total_responses) * 100, 1) : 0;
    }
    
    return array(
        'total_questions' => (int)$total_questions,
        'total_responses' => (int)$total_responses,
        'unique_respondents' => (int)$unique_respondents,
        'avg_time' => (float)$avg_time,
        'last_response' => $last_response,
        'avg_completion_rate' => (float)$avg_completion_rate
    );
}

/**
 * MÉTODO QUE FALTAVA: Obter análise detalhada de respostas por questão
 */
public function get_question_analysis($filters = array()) {
    // Primeiro, obter todas as questões dos questionários filtrados
    $this->db->select('
        q.id as question_id,
        q.question_text,
        q.question_type,
        q.questionnaire_id,
        quest.title as questionnaire_title,
        q.order_index
    ');
    $this->db->from('questions q');
    $this->db->join('questionnaires quest', 'q.questionnaire_id = quest.id', 'inner');
    
    // Se houver filtro por questionário específico
    if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
        $this->db->where('q.questionnaire_id', $filters['questionnaire_id']);
    }
    
    $this->db->where('quest.status', 'active');
    $this->db->order_by('quest.title, q.order_index');
    
    $questions = $this->db->get()->result();
    
    $analysis_data = array();
    
    foreach ($questions as $question) {
        $question_stats = $this->get_question_statistics($question->question_id, $filters);
        
        $analysis_data[] = array(
            'question_id' => $question->question_id,
            'questionnaire_id' => $question->questionnaire_id,
            'questionnaire_title' => $question->questionnaire_title,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'order_index' => $question->order_index,
            'statistics' => $question_stats
        );
    }
    
    return $analysis_data;
}

/**
 * MÉTODO QUE FALTAVA: Obter estatísticas específicas de uma questão
 */
public function get_question_statistics($question_id, $filters = array()) {
    // Obter informações da questão
    $this->db->select('question_type');
    $this->db->where('id', $question_id);
    $question = $this->db->get('questions')->row();
    
    if (!$question) {
        return array();
    }
    
    // Contar total de respostas para esta questão
    $this->db->select('COUNT(qr.id) as total_responses');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    
    // Aplicar filtros de data se fornecidos
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $total_result = $this->db->get()->row();
    $total_responses = $total_result ? $total_result->total_responses : 0;
    
    $statistics = array(
        'total_responses' => (int)$total_responses,
        'response_rate' => 0,
        'data' => array()
    );
    
    if ($total_responses == 0) {
        return $statistics;
    }
    
    // Análise baseada no tipo de questão
    switch ($question->question_type) {
        case 'radio':
            $statistics['data'] = $this->analyze_option_responses($question_id, $filters, $total_responses);
            break;
        case 'checkbox':
            $statistics['data'] = $this->analyze_option_responses($question_id, $filters, $total_responses);
            break;
            
        case 'text':
        case 'textarea':
            $statistics['data'] = $this->analyze_text_responses($question_id, $filters, $total_responses);
            break;
            
        case 'number':
            $statistics['data'] = $this->analyze_number_responses($question_id, $filters, $total_responses);
            break;
            
        case 'date':
        case 'datetime':
            $statistics['data'] = $this->analyze_date_responses($question_id, $filters, $total_responses);
            break;
            
        default:
            $statistics['data'] = $this->analyze_generic_responses($question_id, $filters, $total_responses);
    }
    
    return $statistics;
}

/**
 * MÉTODO CORRIGIDO: Analisar respostas de questões com opções (radio, checkbox)
 */
private function analyze_option_responses($question_id, $filters, $total_responses) {
    // Primeiro, determinar o tipo da questão
    $this->db->select('question_type');
    $this->db->where('id', $question_id);
    $question = $this->db->get('questions')->row();
    
    if (!$question) {
        return array();
    }
    
    // Obter opções da questão
    $this->db->select('id, option_text, option_value, order_index');
    $this->db->where('question_id', $question_id);
    $this->db->order_by('order_index');
    $options = $this->db->get('question_options')->result();
    
    $analysis = array();
    
    foreach ($options as $option) {
        $count = 0;
        
        if ($question->question_type === 'radio') {
            // Para radio, buscar em selected_options (JSON contém o ID da opção)
            $this->db->select('COUNT(*) as count, qr.selected_options::text AS selected_options_text, qr.response_text', FALSE);
            $this->db->from('question_responses qr');
            $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
            $this->db->where('qr.question_id', $question_id);

            // Condição correta para IS NOT NULL
            $this->db->where("qr.selected_options IS NOT NULL", null, false);

            // Filtros - usando where com FALSE para não escapar o cast
            if (!empty($filters['date_from'])) {
                $this->db->where('fr.completed_at::date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('fr.completed_at::date <=', $filters['date_to']);
            }
            if (!empty($filters['applied_by'])) {
                $this->db->where('fr.applied_by', $filters['applied_by']);
            }

            // Group by também precisa do FALSE para não escapar
            $this->db->group_by('qr.response_text', FALSE);
            $this->db->group_by('qr.selected_options::text', FALSE);

            $result = $this->db->get()->result();
            var_dump($this->db->last_query()); exit;

            // Obs: $result é um array, então precisa percorrer ou somar
            $count = !empty($result) ? $result[0]->count : 0;

        } else {
            // Para checkbox, usar método mais seguro
            $count = $this->count_checkbox_option($question_id, $option->option_value, $filters);
        }
        
        $percentage = $total_responses > 0 ? round(($count / $total_responses) * 100, 1) : 0;
        
        $analysis[] = array(
            'option_id' => $option->id,
            'option_text' => $option->option_text,
            'option_value' => $option->option_value,
            'count' => (int)$count,
            'percentage' => (float)$percentage,
            'order_index' => $option->order_index
        );
    }
    
    return $analysis;
}

/**
 * Contar respostas de checkbox para uma opção específica
 */
private function count_checkbox_option($question_id, $option_value, $filters) {
    // Obter todas as respostas para a questão
    $this->db->select('qr.selected_options');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    $this->db->where('qr.selected_options IS NOT NULL');
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $responses = $this->db->get()->result();
    
    $count = 0;
    foreach ($responses as $response) {
        if (!empty($response->selected_options)) {
            // Tentar decodificar JSON
            $selected = json_decode($response->selected_options, true);
            
            // Verificar se a decodificação foi bem-sucedida e se é um array
            if (is_array($selected) && in_array($option_value, $selected)) {
                $count++;
            } elseif (is_string($response->selected_options)) {
                // Fallback: buscar como string se não for JSON válido
                if (strpos($response->selected_options, $option_value) !== false) {
                    $count++;
                }
            }
        }
    }
    
    return $count;
}

/**
 * Analisar respostas numéricas
 */
private function analyze_number_responses($question_id, $filters, $total_responses) {
    $this->db->select('
        COUNT(CASE WHEN response_number IS NOT NULL THEN 1 END) as filled_responses,
        MIN(response_number) as min_value,
        MAX(response_number) as max_value,
        AVG(response_number) as avg_value
    ');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $result = $this->db->get()->row();
    
    $filled = $result ? $result->filled_responses : 0;
    $empty = $total_responses - $filled;
    
    return array(
        array(
            'label' => 'Respostas Preenchidas',
            'count' => (int)$filled,
            'percentage' => round(($filled / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Respostas Vazias',
            'count' => (int)$empty,
            'percentage' => round(($empty / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Valor Mínimo',
            'count' => $result ? $result->min_value : 0,
            'percentage' => null
        ),
        array(
            'label' => 'Valor Máximo',
            'count' => $result ? $result->max_value : 0,
            'percentage' => null
        ),
        array(
            'label' => 'Valor Médio',
            'count' => $result ? round($result->avg_value, 2) : 0,
            'percentage' => null
        )
    );
}

/**
 * Analisar respostas de data
 */
private function analyze_date_responses($question_id, $filters, $total_responses) {
    $this->db->select('
        COUNT(CASE WHEN response_date IS NOT NULL OR response_datetime IS NOT NULL THEN 1 END) as filled_responses,
        MIN(COALESCE(response_date, DATE(response_datetime))) as earliest_date,
        MAX(COALESCE(response_date, DATE(response_datetime))) as latest_date
    ');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $result = $this->db->get()->row();
    
    $filled = $result ? $result->filled_responses : 0;
    $empty = $total_responses - $filled;
    
    return array(
        array(
            'label' => 'Respostas Preenchidas',
            'count' => (int)$filled,
            'percentage' => round(($filled / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Respostas Vazias',
            'count' => (int)$empty,
            'percentage' => round(($empty / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Data Mais Antiga',
            'count' => $result && $result->earliest_date ? $result->earliest_date : 'N/A',
            'percentage' => null,
            'is_date' => true
        ),
        array(
            'label' => 'Data Mais Recente',
            'count' => $result && $result->latest_date ? $result->latest_date : 'N/A',
            'percentage' => null,
            'is_date' => true
        )
    );
}

/**
 * Analisar respostas genéricas (fallback)
 */
private function analyze_generic_responses($question_id, $filters, $total_responses) {
    $this->db->select('
        COUNT(CASE WHEN response_text IS NOT NULL AND response_text != \'\' THEN 1 END) as filled_responses
    ');
    $this->db->from('question_responses qr');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    $this->db->where('qr.question_id', $question_id);
    
    // Aplicar filtros
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $result = $this->db->get()->row();
    
    $filled = $result ? $result->filled_responses : 0;
    $empty = $total_responses - $filled;
    
    return array(
        array(
            'label' => 'Respostas Preenchidas',
            'count' => (int)$filled,
            'percentage' => round(($filled / $total_responses) * 100, 1)
        ),
        array(
            'label' => 'Respostas Vazias',
            'count' => (int)$empty,
            'percentage' => round(($empty / $total_responses) * 100, 1)
        )
    );
}

/**
 * Obter top 5 questões com mais respostas
 */
public function get_top_answered_questions($filters = array(), $limit = 5) {
    $this->db->select('
        q.id,
        q.question_text,
        q.question_type,
        quest.title as questionnaire_title,
        COUNT(qr.id) as total_responses
    ');
    $this->db->from('questions q');
    $this->db->join('questionnaires quest', 'q.questionnaire_id = quest.id', 'inner');
    $this->db->join('question_responses qr', 'q.id = qr.question_id', 'inner');
    $this->db->join('form_responses fr', 'qr.form_response_id = fr.id', 'inner');
    
    // Aplicar filtros
    if (isset($filters['questionnaire_id']) && $filters['questionnaire_id']) {
        $this->db->where('q.questionnaire_id', $filters['questionnaire_id']);
    }
    if (isset($filters['date_from']) && $filters['date_from']) {
        $this->db->where('DATE(fr.completed_at) >=', $filters['date_from']);
    }
    if (isset($filters['date_to']) && $filters['date_to']) {
        $this->db->where('DATE(fr.completed_at) <=', $filters['date_to']);
    }
    if (isset($filters['applied_by']) && $filters['applied_by']) {
        $this->db->where('fr.applied_by', $filters['applied_by']);
    }
    
    $this->db->where('quest.status', 'active');
    $this->db->group_by('q.id, q.question_text, q.question_type, quest.title');
    $this->db->order_by('total_responses', 'DESC');
    $this->db->limit($limit);
    
    return $this->db->get()->result();
}

}