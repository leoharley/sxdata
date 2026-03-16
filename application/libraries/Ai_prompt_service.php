<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ai_prompt_service - Gerenciamento de prompts para IA
 *
 * Responsável por:
 * - Carregar prompts versionados do banco
 * - Interpolar variáveis nos templates
 * - Montar mensagens para chat completion
 * - Preparar dados de contexto para cada feature
 */
class Ai_prompt_service {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Ai_model');
    }

    /**
     * Monta mensagens de chat com base no prompt ativo e variáveis
     */
    public function build_messages($feature_key, $variables = array()) {
        $prompt = $this->CI->Ai_model->get_active_prompt($feature_key);

        if (!$prompt) {
            return array(
                'success' => false,
                'error' => "Nenhum prompt ativo encontrado para '{$feature_key}'",
            );
        }

        $messages = array();

        if (!empty($prompt->system_prompt)) {
            $messages[] = array(
                'role' => 'system',
                'content' => $this->interpolate($prompt->system_prompt, $variables),
            );
        }

        $messages[] = array(
            'role' => 'user',
            'content' => $this->interpolate($prompt->user_prompt_template, $variables),
        );

        return array(
            'success' => true,
            'messages' => $messages,
            'prompt_id' => $prompt->id,
        );
    }

    /**
     * Prepara dados de contexto para detecção de inconsistências
     */
    public function prepare_inconsistency_data($form_response_id) {
        $this->CI->load->model('Response_model');
        $this->CI->load->model('Questionnaire_model');

        $response = $this->CI->Response_model->get_by_id($form_response_id);
        if (!$response) {
            return null;
        }

        $answers = $this->CI->Response_model->get_answers($form_response_id);
        $questionnaire = $this->CI->Questionnaire_model->get_by_id($response->questionnaire_id);

        $responses_data = array();
        foreach ($answers as $answer) {
            $responses_data[] = array(
                'question_id' => $answer->question_id,
                'question' => $answer->question_text ?? 'Pergunta ' . $answer->question_id,
                'type' => $answer->question_type ?? 'text',
                'answer' => $this->extract_answer_value($answer),
            );
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'responses_data' => json_encode($responses_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'form_response_id' => $form_response_id,
            'questionnaire_id' => $response->questionnaire_id,
        );
    }

    /**
     * Prepara dados para correção e padronização
     */
    public function prepare_correction_data($form_response_id) {
        $this->CI->load->model('Response_model');
        $this->CI->load->model('Questionnaire_model');

        $response = $this->CI->Response_model->get_by_id($form_response_id);
        if (!$response) {
            return null;
        }

        $answers = $this->CI->Response_model->get_answers($form_response_id);
        $questionnaire = $this->CI->Questionnaire_model->get_by_id($response->questionnaire_id);

        $field_data = array();
        foreach ($answers as $answer) {
            $field_data[] = array(
                'question_id' => $answer->question_id,
                'question' => $answer->question_text ?? '',
                'type' => $answer->question_type ?? 'text',
                'value' => $this->extract_answer_value($answer),
            );
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'field_data' => json_encode($field_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'form_response_id' => $form_response_id,
            'questionnaire_id' => $response->questionnaire_id,
        );
    }

    /**
     * Prepara dados para preenchimento inteligente
     */
    public function prepare_smart_fill_data($questionnaire_id, $existing_answers = array(), $pending_question_ids = array()) {
        $this->CI->load->model('Questionnaire_model');
        $this->CI->load->model('Question_model');

        $questionnaire = $this->CI->Questionnaire_model->get_by_id($questionnaire_id);
        $questions = $this->CI->Question_model->get_by_questionnaire($questionnaire_id);

        $existing = array();
        $pending = array();

        foreach ($questions as $q) {
            if (isset($existing_answers[$q->id])) {
                $existing[] = array(
                    'question_id' => $q->id,
                    'question' => $q->question_text,
                    'answer' => $existing_answers[$q->id],
                );
            } elseif (empty($pending_question_ids) || in_array($q->id, $pending_question_ids)) {
                $pending[] = array(
                    'question_id' => $q->id,
                    'question' => $q->question_text,
                    'type' => $q->question_type,
                );
            }
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'existing_answers' => json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'pending_fields' => json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'questionnaire_id' => $questionnaire_id,
        );
    }

    /**
     * Prepara dados para reformulação de perguntas
     */
    public function prepare_reformulation_data($question_id, $objective = 'clareza') {
        $this->CI->load->model('Question_model');
        $this->CI->load->model('Questionnaire_model');

        $question = $this->CI->Question_model->get_by_id($question_id);
        if (!$question) {
            return null;
        }

        $questionnaire = $this->CI->Questionnaire_model->get_by_id($question->questionnaire_id);

        return array(
            'original_question' => $question->question_text,
            'objective' => $objective,
            'context' => $questionnaire ? $questionnaire->title . ' - ' . ($questionnaire->description ?? '') : '',
            'question_id' => $question_id,
            'questionnaire_id' => $question->questionnaire_id,
        );
    }

    /**
     * Prepara dados para sugestões de follow-up
     */
    public function prepare_followup_data($questionnaire_id) {
        $this->CI->load->model('Questionnaire_model');
        $this->CI->load->model('Response_model');
        $this->CI->load->model('Question_model');

        $questionnaire = $this->CI->Questionnaire_model->get_by_id($questionnaire_id);
        $questions = $this->CI->Question_model->get_by_questionnaire($questionnaire_id);
        $response_count = $this->CI->Response_model->count_by_filters(array('questionnaire_id' => $questionnaire_id));

        $questions_summary = array();
        foreach ($questions as $q) {
            $questions_summary[] = array(
                'id' => $q->id,
                'text' => $q->question_text,
                'type' => $q->question_type,
            );
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'responses_summary' => json_encode(array(
                'total_responses' => $response_count,
                'questions' => $questions_summary,
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'questionnaire_id' => $questionnaire_id,
        );
    }

    /**
     * Prepara dados para análise estatística
     */
    public function prepare_statistical_data($questionnaire_id, $filters = array()) {
        $this->CI->load->model('Questionnaire_model');
        $this->CI->load->model('Response_model');

        $questionnaire = $this->CI->Questionnaire_model->get_by_id($questionnaire_id);
        $filters['questionnaire_id'] = $questionnaire_id;

        $total_responses = $this->CI->Response_model->count_by_filters($filters);

        // Obter dados agregados
        $responses = $this->CI->Response_model->get_filtered($filters);

        $date_from = isset($filters['date_from']) ? $filters['date_from'] : 'início';
        $date_to = isset($filters['date_to']) ? $filters['date_to'] : 'atual';

        // Montar resumo dos dados
        $data_summary = array(
            'total' => $total_responses,
            'responses_sample' => array(),
        );

        $count = 0;
        foreach ($responses as $r) {
            if ($count >= 50) break;
            $answers = $this->CI->Response_model->get_answers($r->id);
            $row = array('response_id' => $r->id);
            foreach ($answers as $a) {
                $row['q_' . $a->question_id] = $this->extract_answer_value($a);
            }
            $data_summary['responses_sample'][] = $row;
            $count++;
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'total_responses' => $total_responses,
            'date_range' => "{$date_from} a {$date_to}",
            'data_summary' => json_encode($data_summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'questionnaire_id' => $questionnaire_id,
            'filters' => $filters,
        );
    }

    /**
     * Prepara dados para relatórios em linguagem natural
     */
    public function prepare_report_data($questionnaire_id, $filters = array()) {
        return $this->prepare_statistical_data($questionnaire_id, $filters);
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Interpola variáveis no template de prompt
     * Variáveis no formato {{variable_name}}
     */
    private function interpolate($template, $variables) {
        foreach ($variables as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Extrai valor de resposta de um question_response
     */
    private function extract_answer_value($answer) {
        if (!empty($answer->response_text)) {
            return $answer->response_text;
        }
        if (isset($answer->response_number) && $answer->response_number !== null) {
            return $answer->response_number;
        }
        if (!empty($answer->response_date)) {
            return $answer->response_date;
        }
        if (!empty($answer->response_datetime)) {
            return $answer->response_datetime;
        }
        if (!empty($answer->selected_options)) {
            $options = json_decode($answer->selected_options, true);
            return is_array($options) ? implode(', ', $options) : $answer->selected_options;
        }
        return '';
    }
}
