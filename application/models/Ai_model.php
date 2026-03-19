<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ============================================================
    // CONFIGURAÇÕES DE IA
    // ============================================================

    public function get_all_settings() {
        return $this->db->order_by('category', 'ASC')
                        ->order_by('feature_name', 'ASC')
                        ->get('ai_settings')
                        ->result_array();
    }

    public function get_settings_by_category() {
        $settings = $this->get_all_settings();
        $grouped = array();
        foreach ($settings as $setting) {
            $grouped[$setting['category']][] = $setting;
        }
        return $grouped;
    }

    public function get_setting($feature_key) {
        return $this->db->get_where('ai_settings', array('feature_key' => $feature_key))->row();
    }

    public function update_setting($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_settings', $data);
    }

    public function toggle_feature($feature_key, $enabled) {
        $this->db->where('feature_key', $feature_key);
        return $this->db->update('ai_settings', array(
            'is_enabled' => $enabled,
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }

    public function is_feature_enabled($feature_key) {
        $setting = $this->get_setting($feature_key);
        return $setting && $setting->is_enabled;
    }

    // ============================================================
    // PROMPTS
    // ============================================================

    public function get_prompts($feature_key = null) {
        if ($feature_key) {
            $this->db->where('feature_key', $feature_key);
        }
        return $this->db->order_by('feature_key', 'ASC')
                        ->order_by('version', 'DESC')
                        ->get('ai_prompts')
                        ->result_array();
    }

    public function get_active_prompt($feature_key) {
        return $this->db->where('feature_key', $feature_key)
                        ->where('is_active', TRUE)
                        ->order_by('version', 'DESC')
                        ->get('ai_prompts')
                        ->row();
    }

    public function get_prompt($id) {
        return $this->db->get_where('ai_prompts', array('id' => $id))->row();
    }

    public function create_prompt($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_prompts', $data);
        return $this->db->insert_id();
    }

    public function update_prompt($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_prompts', $data);
    }

    // ============================================================
    // LOGS DE EXECUÇÃO
    // ============================================================

    public function log_execution($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_execution_logs', $data);
        return $this->db->insert_id();
    }

    public function update_execution_log($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('ai_execution_logs', $data);
    }

    public function get_execution_logs($filters = array(), $limit = 50, $offset = 0) {
        if (!empty($filters['feature_key'])) {
            $this->db->where('feature_key', $filters['feature_key']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        return $this->db->order_by('created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_execution_logs')
                        ->result_array();
    }

    public function count_execution_logs($filters = array()) {
        if (!empty($filters['feature_key'])) {
            $this->db->where('feature_key', $filters['feature_key']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        return $this->db->count_all_results('ai_execution_logs');
    }

    public function get_execution_stats() {
        $stats = array();

        // Total de execuções
        $stats['total'] = $this->db->count_all('ai_execution_logs');

        // Por status
        $query = $this->db->select('status, COUNT(*) as count')
                          ->group_by('status')
                          ->get('ai_execution_logs');
        $stats['by_status'] = array();
        foreach ($query->result() as $row) {
            $stats['by_status'][$row->status] = (int) $row->count;
        }

        // Custo total
        $query = $this->db->select('SUM(cost_usd) as total_cost, SUM(tokens_input) as total_input, SUM(tokens_output) as total_output')
                          ->get('ai_execution_logs');
        $row = $query->row();
        $stats['total_cost'] = $row ? (float) $row->total_cost : 0;
        $stats['total_tokens_input'] = $row ? (int) $row->total_input : 0;
        $stats['total_tokens_output'] = $row ? (int) $row->total_output : 0;

        // Últimas 24h
        $stats['last_24h'] = $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                                       ->count_all_results('ai_execution_logs');

        // Por feature (últimos 30 dias)
        $query = $this->db->select('feature_key, COUNT(*) as count, SUM(cost_usd) as cost')
                          ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                          ->group_by('feature_key')
                          ->get('ai_execution_logs');
        $stats['by_feature'] = $query->result_array();

        // Erros recentes
        $stats['recent_errors'] = $this->db->where('status', 'error')
                                           ->order_by('created_at', 'DESC')
                                           ->limit(10)
                                           ->get('ai_execution_logs')
                                           ->result_array();

        return $stats;
    }

    // ============================================================
    // TRANSCRIÇÕES
    // ============================================================

    public function get_transcriptions($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_transcriptions.*, u.full_name as processed_by_name');
        $this->db->join('users u', 'u.id = ai_transcriptions.processed_by', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('ai_transcriptions.status', $filters['status']);
        }
        if (!empty($filters['form_response_id'])) {
            $this->db->where('ai_transcriptions.form_response_id', $filters['form_response_id']);
        }

        return $this->db->order_by('ai_transcriptions.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_transcriptions')
                        ->result_array();
    }

    public function get_transcription($id) {
        return $this->db->get_where('ai_transcriptions', array('id' => $id))->row();
    }

    public function create_transcription($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_transcriptions', $data);
        return $this->db->insert_id();
    }

    public function update_transcription($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_transcriptions', $data);
    }

    public function count_transcriptions_by_status() {
        return $this->db->select('status, COUNT(*) as count')
                        ->group_by('status')
                        ->get('ai_transcriptions')
                        ->result();
    }

    // ============================================================
    // INCONSISTÊNCIAS
    // ============================================================

    public function get_inconsistencies($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_inconsistencies.*, q.title as questionnaire_title, u.full_name as resolved_by_name');
        $this->db->join('questionnaires q', 'q.id = ai_inconsistencies.questionnaire_id', 'left');
        $this->db->join('users u', 'u.id = ai_inconsistencies.resolved_by', 'left');

        if (!empty($filters['severity'])) {
            $this->db->where('ai_inconsistencies.severity', $filters['severity']);
        }
        if (!empty($filters['resolution_status'])) {
            $this->db->where('ai_inconsistencies.resolution_status', $filters['resolution_status']);
        }
        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('ai_inconsistencies.questionnaire_id', $filters['questionnaire_id']);
        }

        return $this->db->order_by('ai_inconsistencies.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_inconsistencies')
                        ->result_array();
    }

    public function get_inconsistency($id) {
        $this->db->select('ai_inconsistencies.*, q.title as questionnaire_title');
        $this->db->join('questionnaires q', 'q.id = ai_inconsistencies.questionnaire_id', 'left');
        return $this->db->get_where('ai_inconsistencies', array('ai_inconsistencies.id' => $id))->row();
    }

    public function create_inconsistency($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_inconsistencies', $data);
        return $this->db->insert_id();
    }

    public function resolve_inconsistency($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_inconsistencies', $data);
    }

    public function count_inconsistencies_by_severity() {
        return $this->db->select('severity, resolution_status, COUNT(*) as count')
                        ->group_by('severity, resolution_status')
                        ->get('ai_inconsistencies')
                        ->result();
    }

    // ============================================================
    // CORREÇÕES
    // ============================================================

    public function get_corrections($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_corrections.*, q.question_text');
        $this->db->join('questions q', 'q.id = ai_corrections.question_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('ai_corrections.status', $filters['status']);
        }
        if (!empty($filters['form_response_id'])) {
            $this->db->where('ai_corrections.form_response_id', $filters['form_response_id']);
        }
        if (!empty($filters['correction_type'])) {
            $this->db->where('ai_corrections.correction_type', $filters['correction_type']);
        }

        return $this->db->order_by('ai_corrections.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_corrections')
                        ->result_array();
    }

    public function update_correction($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_corrections', $data);
    }

    public function create_correction($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_corrections', $data);
        return $this->db->insert_id();
    }

    // ============================================================
    // SUGESTÕES DE PREENCHIMENTO
    // ============================================================

    public function get_field_suggestions($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_field_suggestions.*, q.question_text');
        $this->db->join('questions q', 'q.id = ai_field_suggestions.question_id', 'left');

        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('ai_field_suggestions.questionnaire_id', $filters['questionnaire_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ai_field_suggestions.status', $filters['status']);
        }

        return $this->db->order_by('ai_field_suggestions.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_field_suggestions')
                        ->result_array();
    }

    public function create_field_suggestion($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_field_suggestions', $data);
        return $this->db->insert_id();
    }

    public function update_field_suggestion($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('ai_field_suggestions', $data);
    }

    // ============================================================
    // REFORMULAÇÕES
    // ============================================================

    public function get_reformulations($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_reformulations.*, q.question_text as current_text');
        $this->db->join('questions q', 'q.id = ai_reformulations.question_id', 'left');

        if (!empty($filters['question_id'])) {
            $this->db->where('ai_reformulations.question_id', $filters['question_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ai_reformulations.status', $filters['status']);
        }

        return $this->db->order_by('ai_reformulations.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_reformulations')
                        ->result_array();
    }

    public function create_reformulation($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_reformulations', $data);
        return $this->db->insert_id();
    }

    public function update_reformulation($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('ai_reformulations', $data);
    }

    // ============================================================
    // REGRAS ADAPTATIVAS
    // ============================================================

    public function get_adaptive_rules($questionnaire_id = null) {
        if ($questionnaire_id) {
            $this->db->where('questionnaire_id', $questionnaire_id);
        }
        return $this->db->order_by('priority', 'ASC')
                        ->get('ai_adaptive_rules')
                        ->result_array();
    }

    public function create_adaptive_rule($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_adaptive_rules', $data);
        return $this->db->insert_id();
    }

    public function update_adaptive_rule($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_adaptive_rules', $data);
    }

    // ============================================================
    // FOLLOW-UP
    // ============================================================

    public function get_followup_suggestions($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('ai_followup_suggestions.*, q.title as questionnaire_title');
        $this->db->join('questionnaires q', 'q.id = ai_followup_suggestions.questionnaire_id', 'left');

        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('ai_followup_suggestions.questionnaire_id', $filters['questionnaire_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ai_followup_suggestions.status', $filters['status']);
        }

        return $this->db->order_by('ai_followup_suggestions.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_followup_suggestions')
                        ->result_array();
    }

    public function create_followup_suggestion($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_followup_suggestions', $data);
        return $this->db->insert_id();
    }

    public function update_followup_suggestion($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('ai_followup_suggestions', $data);
    }

    // ============================================================
    // ANÁLISES ESTATÍSTICAS
    // ============================================================

    public function get_statistical_analyses($filters = array(), $limit = 20, $offset = 0) {
        $this->db->select('ai_statistical_analyses.*, q.title as questionnaire_title, u.full_name as generated_by_name');
        $this->db->join('questionnaires q', 'q.id = ai_statistical_analyses.questionnaire_id', 'left');
        $this->db->join('users u', 'u.id = ai_statistical_analyses.generated_by', 'left');

        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('ai_statistical_analyses.questionnaire_id', $filters['questionnaire_id']);
        }
        if (!empty($filters['analysis_type'])) {
            $this->db->where('ai_statistical_analyses.analysis_type', $filters['analysis_type']);
        }

        return $this->db->order_by('ai_statistical_analyses.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_statistical_analyses')
                        ->result_array();
    }

    public function get_statistical_analysis($id) {
        $this->db->select('ai_statistical_analyses.*, q.title as questionnaire_title');
        $this->db->join('questionnaires q', 'q.id = ai_statistical_analyses.questionnaire_id', 'left');
        return $this->db->get_where('ai_statistical_analyses', array('ai_statistical_analyses.id' => $id))->row();
    }

    public function create_statistical_analysis($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_statistical_analyses', $data);
        return $this->db->insert_id();
    }

    // ============================================================
    // RELATÓRIOS
    // ============================================================

    public function get_reports($filters = array(), $limit = 20, $offset = 0) {
        $this->db->select('ai_reports.*, q.title as questionnaire_title, u.full_name as generated_by_name');
        $this->db->join('questionnaires q', 'q.id = ai_reports.questionnaire_id', 'left');
        $this->db->join('users u', 'u.id = ai_reports.generated_by', 'left');

        if (!empty($filters['questionnaire_id'])) {
            $this->db->where('ai_reports.questionnaire_id', $filters['questionnaire_id']);
        }
        if (!empty($filters['report_type'])) {
            $this->db->where('ai_reports.report_type', $filters['report_type']);
        }

        return $this->db->order_by('ai_reports.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get('ai_reports')
                        ->result_array();
    }

    public function get_report($id) {
        $this->db->select('ai_reports.*, q.title as questionnaire_title');
        $this->db->join('questionnaires q', 'q.id = ai_reports.questionnaire_id', 'left');
        return $this->db->get_where('ai_reports', array('ai_reports.id' => $id))->row();
    }

    public function create_report($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_reports', $data);
        return $this->db->insert_id();
    }

    public function update_report($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_reports', $data);
    }

    // ============================================================
    // AUDITORIA (app móvel)
    // ============================================================

    public function create_audit_log($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ai_audit_logs', $data);
        return $this->db->insert_id();
    }

    public function create_audit_logs_batch($events) {
        $inserted = 0;
        foreach ($events as $event) {
            $event['created_at'] = date('Y-m-d H:i:s');
            if ($this->db->insert('ai_audit_logs', $event)) {
                $inserted++;
            }
        }
        return $inserted;
    }

    // ============================================================
    // CACHE DE IA
    // ============================================================

    public function get_cache($cache_key) {
        $row = $this->db->where('cache_key', $cache_key)
                        ->where('expires_at >', date('Y-m-d H:i:s'))
                        ->get('ai_cache')
                        ->row();
        if ($row) {
            return json_decode($row->cache_value, true);
        }
        return null;
    }

    public function set_cache($cache_key, $value, $ttl_seconds = 300) {
        // Remove cache antigo com mesma key
        $this->db->where('cache_key', $cache_key)->delete('ai_cache');

        return $this->db->insert('ai_cache', array(
            'cache_key' => $cache_key,
            'cache_value' => json_encode($value),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl_seconds),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function clear_expired_cache() {
        return $this->db->where('expires_at <', date('Y-m-d H:i:s'))
                        ->delete('ai_cache');
    }

    // ============================================================
    // RATE LIMITING
    // ============================================================

    public function check_rate_limit($user_id, $endpoint, $max_requests = 10, $window_seconds = 60) {
        $window_start = date('Y-m-d H:i:s', time() - $window_seconds);

        $query = $this->db->select('COUNT(*) as cnt')
                          ->where('user_id', $user_id)
                          ->where('endpoint', $endpoint)
                          ->where('window_start >=', $window_start)
                          ->get('ai_rate_limits');

        $row = $query->row();
        $count = $row ? (int) $row->cnt : 0;

        if ($count >= $max_requests) {
            return false; // limite atingido
        }

        // Registrar requisição
        $this->db->insert('ai_rate_limits', array(
            'user_id' => $user_id,
            'endpoint' => $endpoint,
            'window_start' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return true;
    }

    public function cleanup_rate_limits() {
        // Limpar registros antigos (mais de 5 minutos)
        return $this->db->where('window_start <', date('Y-m-d H:i:s', time() - 300))
                        ->delete('ai_rate_limits');
    }

    // ============================================================
    // DASHBOARD DE IA - ESTATÍSTICAS GERAIS
    // ============================================================

    public function get_dashboard_stats() {
        $stats = array();

        $stats['transcriptions_pending'] = $this->db->where('status', 'pending')->count_all_results('ai_transcriptions');
        $stats['transcriptions_total'] = $this->db->count_all('ai_transcriptions');
        $stats['inconsistencies_pending'] = $this->db->where('resolution_status', 'pending')->count_all_results('ai_inconsistencies');
        $stats['inconsistencies_total'] = $this->db->count_all('ai_inconsistencies');
        $stats['corrections_pending'] = $this->db->where('status', 'pending')->count_all_results('ai_corrections');
        $stats['corrections_total'] = $this->db->count_all('ai_corrections');
        $stats['reports_total'] = $this->db->count_all('ai_reports');
        $stats['analyses_total'] = $this->db->count_all('ai_statistical_analyses');

        $stats['enabled_features'] = $this->db->where('is_enabled', TRUE)->count_all_results('ai_settings');
        $stats['total_features'] = $this->db->count_all('ai_settings');

        // Execuções hoje
        $stats['executions_today'] = $this->db->where('created_at >=', date('Y-m-d'))
                                               ->count_all_results('ai_execution_logs');

        // Custo do mês
        $query = $this->db->select('SUM(cost_usd) as cost')
                          ->where('created_at >=', date('Y-m-01'))
                          ->get('ai_execution_logs');
        $row = $query->row();
        $stats['cost_this_month'] = $row ? (float) $row->cost : 0;

        return $stats;
    }
}
