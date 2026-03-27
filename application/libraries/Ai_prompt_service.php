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
            $system_content = $this->interpolate($prompt->system_prompt, $variables);

            // Injetar diretrizes ativas para esta feature
            $directives_text = $this->build_directives_block($feature_key);
            if ($directives_text) {
                $system_content .= "\n\n" . $directives_text;
            }

            $messages[] = array(
                'role' => 'system',
                'content' => $system_content,
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
     *
     * Campos de texto livre (text/textarea/number/date): enviados como corrigíveis.
     * Campos de opção (radio/checkbox/select): enviados com o texto de exibição (option_text)
     * marcados como NÃO corrigíveis — o valor armazenado afeta regras de condicionalidade.
     */
    public function prepare_correction_data($form_response_id) {
        $this->CI->load->model('Response_model');
        $this->CI->load->model('Questionnaire_model');
        $this->CI->load->model('Question_model');

        $response = $this->CI->Response_model->get_by_id($form_response_id);
        if (!$response) {
            return null;
        }

        $answers  = $this->CI->Response_model->get_answers($form_response_id);
        $questionnaire = $this->CI->Questionnaire_model->get_by_id($response->questionnaire_id);

        $option_types = array('radio', 'checkbox', 'select');
        $field_data   = array();

        foreach ($answers as $answer) {
            $type = $answer->question_type ?? 'text';

            if (in_array($type, $option_types)) {
                // Busca o texto de exibição das opções selecionadas
                $selected_raw = json_decode($answer->selected_options ?? '[]', true);
                if (!is_array($selected_raw)) {
                    $selected_raw = array();
                }

                $display_texts = array();
                if (!empty($selected_raw)) {
                    $options = $this->CI->Question_model->get_options($answer->question_id);
                    foreach ($options as $opt) {
                        foreach ($selected_raw as $sel) {
                            if ((string)$sel === (string)$opt->option_text
                                || (string)$sel === (string)$opt->option_value
                                || (string)$sel === (string)$opt->id) {
                                $display_texts[] = $opt->option_text;
                                break;
                            }
                        }
                    }
                    // Fallback: usa os valores brutos se não encontrou nenhum label
                    if (empty($display_texts)) {
                        $display_texts = array_map('strval', $selected_raw);
                    }
                }

                $field_data[] = array(
                    'question_id'   => $answer->question_id,
                    'question'      => $answer->question_text ?? '',
                    'type'          => $type,
                    'display_value' => implode(', ', $display_texts),
                    'correctable'   => false,
                );
            } else {
                // Campo de texto livre — corrigível
                $value = $this->extract_answer_value($answer);
                if ($value === '' || $value === null) {
                    continue; // ignora vazios
                }

                $field_data[] = array(
                    'question_id'   => $answer->question_id,
                    'question'      => $answer->question_text ?? '',
                    'type'          => $type,
                    'display_value' => (string) $value,
                    'correctable'   => true,
                );
            }
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
     * Prepara dados para geração de regras adaptativas
     */
    public function prepare_adaptive_data($questionnaire_id) {
        $this->CI->load->model('Questionnaire_model');
        $this->CI->load->model('Question_model');

        $questionnaire = $this->CI->Questionnaire_model->get_by_id($questionnaire_id);
        $questions     = $this->CI->Question_model->get_by_questionnaire($questionnaire_id);

        $questions_data = array();
        foreach ($questions as $q) {
            $entry = array(
                'id'          => $q->id,
                'text'        => $q->question_text,
                'type'        => $q->question_type,
                'order_index' => $q->order_index ?? 0,
            );
            if (!empty($q->options)) {
                $entry['options'] = array_map(function($o) {
                    return array('text' => $o->option_text, 'value' => $o->option_value ?? $o->option_text);
                }, $q->options);
            }
            $questions_data[] = $entry;
        }

        return array(
            'questionnaire_title' => $questionnaire ? $questionnaire->title : 'Questionário',
            'questionnaire_description' => $questionnaire ? ($questionnaire->description ?? '') : '',
            'questions_data' => json_encode($questions_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'questionnaire_id' => $questionnaire_id,
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
     * Monta bloco de diretrizes do admin para injetar no system prompt
     */
    private function build_directives_block($feature_key) {
        $directives = $this->CI->Ai_model->get_active_directives_for_feature($feature_key);
        if (empty($directives)) {
            return '';
        }

        $type_headers = array(
            'instruction' => 'INSTRUÇÕES DO ADMINISTRADOR',
            'restriction' => 'RESTRIÇÕES E LIMITES (NUNCA VIOLAR)',
            'persona' => 'COMPORTAMENTO E PERSONA',
            'format' => 'FORMATO DE RESPOSTA',
            'context' => 'CONTEXTO ADICIONAL',
        );

        $grouped = array();
        foreach ($directives as $d) {
            $type = $d['directive_type'] ?? 'instruction';
            $grouped[$type][] = $d['content'];
        }

        $blocks = array();
        // Prioridade de tipo: restrições primeiro
        $type_order = array('restriction', 'instruction', 'persona', 'format', 'context');
        foreach ($type_order as $type) {
            if (!isset($grouped[$type])) continue;
            $header = $type_headers[$type] ?? strtoupper($type);
            $items = array();
            foreach ($grouped[$type] as $i => $content) {
                $items[] = ($i + 1) . '. ' . $content;
            }
            $blocks[] = "=== {$header} ===\n" . implode("\n", $items);
        }

        return "--- DIRETRIZES DO ADMINISTRADOR ---\nAs diretrizes abaixo foram definidas pelo administrador do sistema e devem ser seguidas rigorosamente.\n\n" . implode("\n\n", $blocks);
    }

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
