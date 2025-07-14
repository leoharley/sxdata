<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaire_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->select('q.*, u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
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
        // CORREÇÃO: Incluir explicitamente os campos de checkbox
        $this->db->select('q.id, q.title, q.description, q.status, q.version, 
                          q.requires_consent, q.requires_location, q.requires_photo,
                          q.estimated_time, q.aplicadores, q.created_by, q.created_at, q.updated_at,
                          u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
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
        $this->db->where('status', 'active');
        $this->db->order_by('title', 'ASC');
        return $this->db->get('questionnaires')->result();
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
        $this->db->select('q.*, COUNT(questions.id) as question_count');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id');
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
        $this->db->select('q.*, COUNT(questions.id) as question_count');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
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
}