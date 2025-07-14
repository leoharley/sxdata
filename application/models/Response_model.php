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
        $this->db->where('fr.respondent_email !=', '');
        
        $result = $this->db->get()->row();
        return $result ? $result->count : 0;
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
        $this->db->where('fr.photo_path !=', '');
        
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
        
        $this->db->group_by('u.id, u.full_name');
        $this->db->order_by('total_responses', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_questionnaires_popularity($filters = array(), $limit = 10) {
        $this->db->select('q.id, q.title, COUNT(fr.id) as total_responses');
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
        
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('total_responses', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_detailed_analysis($filters = array()) {
        $analysis = array();
        
        // Estatísticas básicas
        $analysis['total_responses'] = $this->count_by_filters($filters);
        $analysis['unique_respondents'] = $this->count_unique_respondents($filters);
        $analysis['responses_with_photos'] = $this->count_photos($filters);
        $analysis['responses_with_location'] = $this->count_locations($filters);
        $analysis['consent_rate'] = $this->get_consent_rate($filters);
        
        // Estatísticas por status de sincronização
        $this->db->select('sync_status, COUNT(*) as count');
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
        
        $this->db->group_by('sync_status');
        $sync_stats = $this->db->get()->result();
        
        $analysis['sync_stats'] = array();
        foreach ($sync_stats as $stat) {
            $analysis['sync_stats'][$stat->sync_status] = $stat->count;
        }
        
        // Top 5 aplicadores
        $analysis['top_applicators'] = $this->get_top_applicators($filters, 5);
        
        // Top 5 questionários
        $analysis['top_questionnaires'] = $this->get_questionnaires_popularity($filters, 5);
        
        // Dados dos últimos 7 dias
        $analysis['daily_responses'] = $this->get_responses_by_day_filtered($filters, 7);
        $analysis['questionnaire_title'] = $this->get_filtered($filters);
        
        // Porcentagens calculadas
        if ($analysis['total_responses'] > 0) {
            $analysis['photo_percentage'] = round(($analysis['responses_with_photos'] / $analysis['total_responses']) * 100, 2);
            $analysis['location_percentage'] = round(($analysis['responses_with_location'] / $analysis['total_responses']) * 100, 2);
        } else {
            $analysis['photo_percentage'] = 0;
            $analysis['location_percentage'] = 0;
        }
       
        return $analysis;
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
}