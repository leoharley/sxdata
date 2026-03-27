<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API de IA - Endpoints para o app móvel (preparação futura)
 * e chamadas AJAX do painel administrativo
 */
class Ai extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Ai_model');
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->library('ai_service');
        $this->load->library('ai_prompt_service');

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($this->input->method() === 'options') {
            exit();
        }
    }

    /**
     * GET /api/ai/status
     * Verifica status do módulo de IA
     */
    public function status() {
        $user = $this->verify_auth();
        if (!$user) return;

        $stats = $this->Ai_model->get_dashboard_stats();
        $settings = $this->Ai_model->get_all_settings();

        $features = array();
        foreach ($settings as $s) {
            $features[$s['feature_key']] = array(
                'name'    => $s['feature_name'],
                'enabled' => (bool) $s['is_enabled'],
                'model'   => $s['model'],
            );
        }

        echo json_encode(array(
            'success' => true,
            'configured' => $this->ai_service->is_configured(),
            'features' => $features,
            'stats' => array(
                'executions_today' => $stats['executions_today'],
                'enabled_features' => $stats['enabled_features'],
            ),
        ));
    }

    /**
     * POST /api/ai/transcriptions/sync
     * Recebe batch de transcrições do app
     */
    public function transcriptions_sync() {
        $user = $this->verify_auth();
        if (!$user) return;

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['transcriptions']) || !is_array($data['transcriptions'])) {
            echo json_encode(array('success' => false, 'message' => 'Nenhuma transcrição enviada.'));
            return;
        }

        $received = 0;
        $duplicates = 0;

        foreach ($data['transcriptions'] as $t) {
            if (empty($t['id'])) continue;

            // Verificar duplicata pelo app_id
            $exists = $this->db->where('app_id', $t['id'])->count_all_results('ai_transcriptions');
            if ($exists > 0) {
                $duplicates++;
                continue;
            }

            $insert = array(
                'app_id'                  => $t['id'],
                'questionnaire_id'        => isset($t['questionnaire_id']) ? (int)$t['questionnaire_id'] : null,
                'question_id'             => isset($t['question_id']) ? (int)$t['question_id'] : null,
                'question_text'           => $t['question_text'] ?? null,
                'transcription_text'      => $t['transcribed_text'] ?? '',
                'edited_text'             => $t['edited_text'] ?? null,
                'transcription_edited'    => $t['edited_text'] ?? null,
                'confidence_score'        => isset($t['confidence']) ? (float)$t['confidence'] : null,
                'language'                => $t['language'] ?? 'pt-BR',
                'duration_ms'             => isset($t['duration_ms']) ? (int)$t['duration_ms'] : null,
                'recording_duration_secs' => (int)($t['recording_duration_secs'] ?? 0),
                'audio_duration_seconds'  => isset($t['recording_duration_secs']) ? (float)$t['recording_duration_secs'] : null,
                'applicator_name'         => $t['applicator_name'] ?? null,
                'applicator_id'           => isset($t['applicator_id']) ? (int)$t['applicator_id'] : null,
                'timestamp_app'           => !empty($t['timestamp']) ? date('Y-m-d H:i:s', strtotime($t['timestamp'])) : date('Y-m-d H:i:s'),
                'audio_file_path'         => 'app_audio',
                'status'                  => 'completed',
                'source'                  => 'app',
                'processed_at'            => date('Y-m-d H:i:s'),
            );

            $this->db->insert('ai_transcriptions', $insert);
            $received++;
        }

        echo json_encode(array(
            'success' => true,
            'message' => $received . ' transcrições recebidas com sucesso.',
            'data' => array(
                'received' => $received,
                'duplicates_skipped' => $duplicates,
            ),
        ));
    }

    /**
     * GET /api/ai/followup-tips?questionnaire_id={id}
     * Retorna dicas de follow-up para o app
     */
    public function followup_tips() {
        $user = $this->verify_auth();
        if (!$user) return;

        $questionnaire_id = $this->input->get('questionnaire_id');
        if (empty($questionnaire_id)) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id é obrigatório.'));
            return;
        }

        $question_ids = $this->db->select('id')
                                 ->where('questionnaire_id', $questionnaire_id)
                                 ->get('questions')
                                 ->result();
        $qids = array_map(function($r) { return (int) $r->id; }, $question_ids);

        if (empty($qids)) {
            echo json_encode(array('success' => true, 'data' => array('tips' => array())));
            return;
        }

        $tips = $this->db->select('question_id, tip')
                         ->where_in('question_id', $qids)
                         ->order_by('question_id, id')
                         ->get('question_followup_tips')
                         ->result();

        echo json_encode(array('success' => true, 'data' => array('tips' => $tips)));
    }

    /**
     * POST /api/ai/transcribe
     * Transcreve áudio enviado pelo app
     */
    public function transcribe() {
        $user = $this->verify_auth();
        if (!$user) return;

        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('transcription')) {
            echo json_encode(array('success' => false, 'message' => 'Transcrição não está habilitada'));
            return;
        }

        // Recebe áudio como upload
        $upload_path = FCPATH . 'uploads/audio/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config = array(
            'upload_path' => $upload_path,
            'allowed_types' => 'mp3|wav|m4a|ogg|webm|mp4',
            'max_size' => 25600,
        );

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('audio')) {
            echo json_encode(array('success' => false, 'message' => $this->upload->display_errors('', '')));
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_data['full_path'];

        $form_response_id = $this->input->post('form_response_id');
        $question_id = $this->input->post('question_id');

        $transcription_id = $this->Ai_model->create_transcription(array(
            'form_response_id' => $form_response_id,
            'question_id' => $question_id,
            'audio_file_path' => 'uploads/audio/' . $upload_data['file_name'],
            'status' => 'processing',
        ));

        $result = $this->ai_service->transcribe_audio($file_path, array(
            'resource_type' => 'transcription',
            'resource_id' => $transcription_id,
        ));

        if ($result['success']) {
            $this->Ai_model->update_transcription($transcription_id, array(
                'transcription_text' => $result['text'],
                'audio_duration_seconds' => $result['duration'],
                'language' => $result['language'],
                'status' => 'completed',
                'model_used' => 'whisper-1',
                'processed_at' => date('Y-m-d H:i:s'),
            ));

            echo json_encode(array(
                'success' => true,
                'transcription_id' => $transcription_id,
                'text' => $result['text'],
                'duration' => $result['duration'],
                'language' => $result['language'],
            ));
        } else {
            $this->Ai_model->update_transcription($transcription_id, array(
                'status' => 'error',
                'error_message' => $result['error'],
            ));

            echo json_encode(array('success' => false, 'message' => $result['error']));
        }
    }

    /**
     * POST /api/ai/check-inconsistencies
     * Verifica inconsistências em uma resposta
     */
    public function check_inconsistencies() {
        $user = $this->verify_auth();
        if (!$user) return;

        $form_response_id = $this->get_json_input('form_response_id');

        if (!$form_response_id) {
            echo json_encode(array('success' => false, 'message' => 'form_response_id obrigatório'));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('inconsistency_detection')) {
            echo json_encode(array('success' => false, 'message' => 'Detecção de inconsistências não está habilitada'));
            return;
        }

        $context = $this->ai_prompt_service->prepare_inconsistency_data($form_response_id);
        if (!$context) {
            echo json_encode(array('success' => false, 'message' => 'Resposta não encontrada'));
            return;
        }

        $messages_result = $this->ai_prompt_service->build_messages('inconsistency_detection', $context);
        if (!$messages_result['success']) {
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('inconsistency_detection', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'form_response',
            'resource_id' => $form_response_id,
        ));

        if ($result['success'] && $result['parsed']) {
            echo json_encode(array(
                'success' => true,
                'inconsistencies' => $result['parsed'],
                'tokens_used' => $result['tokens_input'] + $result['tokens_output'],
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => $result['error'] ?? 'Erro no processamento'));
        }
    }

    /**
     * POST /api/ai/suggest-fill
     * Sugere preenchimento inteligente
     */
    public function suggest_fill() {
        $user = $this->verify_auth();
        if (!$user) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $existing_answers = $this->get_json_input('existing_answers') ?: array();

        if (!$questionnaire_id) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id obrigatório'));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('smart_fill')) {
            echo json_encode(array('success' => false, 'message' => 'Preenchimento inteligente não está habilitado'));
            return;
        }

        $context = $this->ai_prompt_service->prepare_smart_fill_data($questionnaire_id, $existing_answers);
        $messages_result = $this->ai_prompt_service->build_messages('smart_fill', $context);

        if (!$messages_result['success']) {
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('smart_fill', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            echo json_encode(array(
                'success' => true,
                'suggestions' => $result['parsed'],
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => $result['error'] ?? 'Erro no processamento'));
        }
    }

    // ============================================================
    // 1. POST /api/ai/standardize
    // Padronização automática de dados
    // ============================================================

    public function standardize() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'standardize')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $responses = $this->get_json_input('responses');

        if (!$questionnaire_id || !$responses || !is_array($responses)) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id e responses são obrigatórios'));
            return;
        }

        $suggestions = array();
        $remaining = array();

        // Fase 1: padronização local (regex) para reduzir custo com IA
        foreach ($responses as $question_id => $value) {
            if (!is_string($value) || trim($value) === '') continue;

            $local_result = $this->standardize_local($value);
            if ($local_result) {
                $suggestions[] = array(
                    'question_id' => (int) $question_id,
                    'original_value' => $value,
                    'suggested_value' => $local_result['value'],
                    'type' => $local_result['type'],
                    'confidence' => $local_result['confidence'],
                );
            } else {
                $remaining[(string) $question_id] = $value;
            }
        }

        // Fase 2: chamar IA para campos que não foram padronizados localmente
        if (!empty($remaining) && $this->Ai_model->is_feature_enabled('data_correction')) {
            $ai_suggestions = $this->standardize_with_ai($questionnaire_id, $remaining);
            $suggestions = array_merge($suggestions, $ai_suggestions);
        }

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'suggestions' => $suggestions,
                'total_fields' => count($responses),
                'fields_with_suggestions' => count($suggestions),
            ),
        ));
    }

    // ============================================================
    // 2. POST /api/ai/smart-suggestions
    // Sugestões inteligentes de preenchimento baseadas em contexto
    // ============================================================

    public function smart_suggestions() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'smart_suggestions')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $question_id = $this->get_json_input('question_id');
        $current_responses = $this->get_json_input('current_responses') ?: array();

        if (!$questionnaire_id || !$question_id) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id e question_id são obrigatórios'));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('smart_fill')) {
            // Fallback silencioso: retorna vazio
            echo json_encode(array('success' => true, 'data' => array('suggestions' => array())));
            return;
        }

        $this->load->model('Question_model');
        $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);
        $target_question = null;

        $context_lines = array();
        foreach ($questions as $q) {
            if ($q->id == $question_id) {
                $target_question = $q;
            }
            if (isset($current_responses[(string) $q->id])) {
                $context_lines[] = "Pergunta: {$q->question_text}\nResposta: {$current_responses[(string) $q->id]}";
            }
        }

        if (!$target_question) {
            echo json_encode(array('success' => true, 'data' => array('suggestions' => array())));
            return;
        }

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um assistente de preenchimento de formulários brasileiros. Com base no contexto das respostas anteriores, sugira um valor para a pergunta pendente. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "Contexto das respostas já preenchidas:\n\n" . implode("\n\n", $context_lines) . "\n\nPergunta pendente (ID {$question_id}): \"{$target_question->question_text}\" (tipo: {$target_question->question_type})\n\nRetorne JSON: [{\"question_id\": {$question_id}, \"suggested_value\": \"...\", \"reason\": \"...\", \"source\": \"context\", \"confidence\": 0.0}]"),
        );

        $result = $this->ai_service->chat_completion_json('smart_fill', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $suggestions = is_array($result['parsed']) ? $result['parsed'] : array($result['parsed']);
            echo json_encode(array('success' => true, 'data' => array('suggestions' => $suggestions)));
        } else {
            // Fallback silencioso
            echo json_encode(array('success' => true, 'data' => array('suggestions' => array())));
        }
    }

    // ============================================================
    // 3. POST /api/ai/reformulate-questions
    // Reformulação de perguntas em diferentes estilos
    // ============================================================

    public function reformulate_questions() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'reformulate_questions')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $question_ids = $this->get_json_input('question_ids');
        $style = $this->get_json_input('style') ?: 'simplified';

        if (!$questionnaire_id || !$question_ids || !is_array($question_ids)) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id e question_ids são obrigatórios'));
            return;
        }

        $valid_styles = array('simplified', 'accessible', 'technical', 'neutral');
        if (!in_array($style, $valid_styles)) {
            $style = 'simplified';
        }

        // Verificar cache
        $cache_key = 'reformulate_' . $questionnaire_id . '_' . md5(implode(',', $question_ids) . $style);
        $cached = $this->Ai_model->get_cache($cache_key);
        if ($cached) {
            echo json_encode(array('success' => true, 'data' => $cached));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('question_reformulation')) {
            echo json_encode(array('success' => true, 'data' => array('questions' => array())));
            return;
        }

        $this->load->model('Question_model');
        $all_questions = $this->Question_model->get_by_questionnaire($questionnaire_id);

        $target_questions = array();
        foreach ($all_questions as $q) {
            if (in_array($q->id, $question_ids)) {
                $target_questions[] = $q;
            }
        }

        if (empty($target_questions)) {
            echo json_encode(array('success' => true, 'data' => array('questions' => array())));
            return;
        }

        $style_descriptions = array(
            'simplified' => 'linguagem simples e cotidiana, fácil de entender para pessoas com baixa escolaridade',
            'accessible' => 'linguagem acessível e inclusiva, evitando termos técnicos',
            'technical' => 'linguagem técnica e precisa para profissionais da área',
            'neutral' => 'linguagem neutra e imparcial, sem viés ou indução de resposta',
        );

        $questions_text = "";
        foreach ($target_questions as $q) {
            $questions_text .= "ID {$q->id}: \"{$q->question_text}\"\n";
        }

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um especialista em design de questionários de pesquisa. Reformule as perguntas mantendo o significado original. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "Reformule as perguntas abaixo no estilo \"{$style}\" ({$style_descriptions[$style]}):\n\n{$questions_text}\nRetorne JSON: [{\"question_id\": N, \"original_text\": \"...\", \"reformulated_text\": \"...\", \"style\": \"{$style}\"}]"),
        );

        $result = $this->ai_service->chat_completion_json('question_reformulation', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $questions_result = is_array($result['parsed']) ? $result['parsed'] : array();
            $data = array('questions' => $questions_result);

            // Cache por 1 hora (mesmas perguntas não mudam)
            $this->Ai_model->set_cache($cache_key, $data, 3600);

            echo json_encode(array('success' => true, 'data' => $data));
        } else {
            echo json_encode(array('success' => true, 'data' => array('questions' => array())));
        }
    }

    // ============================================================
    // 4. POST /api/ai/adaptive-next
    // Roteamento inteligente de próxima pergunta
    // ============================================================

    public function adaptive_next() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'adaptive_next')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $current_question_id = $this->get_json_input('current_question_id');
        $responses = $this->get_json_input('responses') ?: array();

        if (!$questionnaire_id || !$current_question_id) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id e current_question_id são obrigatórios'));
            return;
        }

        // Fallback padrão
        $fallback = array(
            'success' => true,
            'data' => array(
                'next_question_id' => null,
                'reason' => null,
                'is_ai_generated' => false,
                'skip_question_ids' => array(),
                'fallback_rule' => 'sequential',
            ),
        );

        if (!$this->Ai_model->is_feature_enabled('adaptive_routing')) {
            echo json_encode($fallback);
            return;
        }

        // Primeiro: verificar regras manuais no banco
        $rules = $this->Ai_model->get_adaptive_rules($questionnaire_id);
        foreach ($rules as $rule) {
            if ($rule->source_question_id == $current_question_id && $rule->is_active) {
                $logic = json_decode($rule->condition_logic, true);
                if ($logic && $this->evaluate_adaptive_condition($logic, $responses)) {
                    echo json_encode(array(
                        'success' => true,
                        'data' => array(
                            'next_question_id' => (int) $rule->target_question_id,
                            'reason' => 'Regra configurada manualmente',
                            'is_ai_generated' => false,
                            'skip_question_ids' => array(),
                            'fallback_rule' => 'rule_based',
                        ),
                    ));
                    return;
                }
            }
        }

        // Segundo: pedir à IA
        $this->load->model('Question_model');
        $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);

        $questions_list = "";
        $answered_ids = array_keys($responses);
        foreach ($questions as $q) {
            $status = in_array((string) $q->id, $answered_ids) ? '[RESPONDIDA]' : '[PENDENTE]';
            $current = ($q->id == $current_question_id) ? ' [ATUAL]' : '';
            $questions_list .= "ID {$q->id}: \"{$q->question_text}\" (tipo: {$q->question_type}) {$status}{$current}\n";
        }

        $responses_text = "";
        foreach ($responses as $qid => $val) {
            $responses_text .= "Pergunta {$qid}: {$val}\n";
        }

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um especialista em roteamento inteligente de questionários. Determine qual deve ser a próxima pergunta mais relevante e quais podem ser puladas baseado nas respostas anteriores. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "Questionário com as seguintes perguntas:\n{$questions_list}\n\nRespostas até agora:\n{$responses_text}\n\nA pergunta atual é ID {$current_question_id}. Determine a próxima pergunta mais relevante.\n\nRetorne JSON: {\"next_question_id\": N, \"reason\": \"...\", \"skip_question_ids\": []}"),
        );

        $result = $this->ai_service->chat_completion_json('adaptive_routing', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $parsed = $result['parsed'];
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'next_question_id' => isset($parsed['next_question_id']) ? (int) $parsed['next_question_id'] : null,
                    'reason' => isset($parsed['reason']) ? $parsed['reason'] : null,
                    'is_ai_generated' => true,
                    'skip_question_ids' => isset($parsed['skip_question_ids']) ? $parsed['skip_question_ids'] : array(),
                    'fallback_rule' => 'sequential',
                ),
            ));
        } else {
            // Fallback silencioso
            echo json_encode($fallback);
        }
    }

    // ============================================================
    // 5. POST /api/ai/follow-up
    // Sugestão de perguntas complementares
    // ============================================================

    public function follow_up() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'follow_up')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $question_id = $this->get_json_input('question_id');
        $response_value = $this->get_json_input('response_value');
        $all_responses = $this->get_json_input('all_responses') ?: array();

        if (!$questionnaire_id || !$question_id || !$response_value) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id, question_id e response_value são obrigatórios'));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('followup_suggestions')) {
            echo json_encode(array('success' => true, 'data' => array('suggestions' => array())));
            return;
        }

        $this->load->model('Question_model');
        $question = $this->Question_model->get_by_id($question_id);
        $question_text = $question ? $question->question_text : "Pergunta {$question_id}";

        $context = "";
        if (!empty($all_responses)) {
            $context = "\n\nOutras respostas do formulário:\n";
            foreach ($all_responses as $qid => $val) {
                $context .= "Pergunta {$qid}: {$val}\n";
            }
        }

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um pesquisador especialista em coleta de dados no Brasil. Sugira perguntas complementares relevantes quando a resposta do entrevistado indicar necessidade de aprofundamento. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "Pergunta (ID {$question_id}): \"{$question_text}\"\nResposta: \"{$response_value}\"{$context}\n\nSugira perguntas de follow-up relevantes.\n\nRetorne JSON: [{\"question_text\": \"...\", \"question_type\": \"text|select|radio|checkbox\", \"reason\": \"...\", \"after_question_id\": {$question_id}, \"options\": null, \"is_required\": false}]"),
        );

        $result = $this->ai_service->chat_completion_json('followup_suggestions', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $suggestions = is_array($result['parsed']) ? $result['parsed'] : array();
            // Garantir que options seja null ou array (nunca string)
            foreach ($suggestions as &$s) {
                if (isset($s['options']) && !is_array($s['options'])) {
                    $s['options'] = null;
                }
                if (!isset($s['is_required'])) {
                    $s['is_required'] = false;
                }
            }
            unset($s);

            echo json_encode(array('success' => true, 'data' => array('suggestions' => $suggestions)));
        } else {
            echo json_encode(array('success' => true, 'data' => array('suggestions' => array())));
        }
    }

    // ============================================================
    // 6. POST /api/ai/generate-report
    // Geração de relatório descritivo com IA
    // ============================================================

    public function generate_report() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;
        if (!$this->check_rate_limit($user, 'generate_report')) return;

        $questionnaire_id = $this->get_json_input('questionnaire_id');
        $report_type = $this->get_json_input('report_type') ?: 'descriptive';
        $filters = $this->get_json_input('filters') ?: array();

        if (!$questionnaire_id) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id é obrigatório'));
            return;
        }

        $valid_types = array('descriptive', 'statistical', 'executive');
        if (!in_array($report_type, $valid_types)) {
            $report_type = 'descriptive';
        }

        // Verificar cache (5 minutos)
        $cache_key = 'report_' . $questionnaire_id . '_' . $report_type . '_' . md5(json_encode($filters));
        $cached = $this->Ai_model->get_cache($cache_key);
        if ($cached) {
            echo json_encode(array('success' => true, 'data' => $cached));
            return;
        }

        if (!$this->Ai_model->is_feature_enabled('natural_reports')) {
            echo json_encode(array('success' => true, 'data' => array(
                'title' => '',
                'summary' => 'Funcionalidade de relatórios não está habilitada.',
                'key_findings' => array(),
                'charts' => array(),
                'full_report' => '',
            )));
            return;
        }

        // Preparar dados
        $context = $this->ai_prompt_service->prepare_report_data($questionnaire_id, $filters);
        if (!$context || $context['total_responses'] == 0) {
            echo json_encode(array('success' => true, 'data' => array(
                'title' => '',
                'summary' => 'Nenhuma resposta encontrada para gerar relatório.',
                'key_findings' => array(),
                'charts' => array(),
                'full_report' => '',
            )));
            return;
        }

        $type_instructions = array(
            'descriptive' => 'Gere um relatório descritivo completo com análise qualitativa e quantitativa.',
            'statistical' => 'Gere um relatório estatístico focado em números, percentuais, médias e distribuições.',
            'executive' => 'Gere um resumo executivo conciso com os principais achados e recomendações.',
        );

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um analista de dados especialista em pesquisas no Brasil. Gere relatórios profissionais em português brasileiro. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "{$type_instructions[$report_type]}\n\nQuestionário: \"{$context['questionnaire_title']}\"\nPeríodo: {$context['date_range']}\nTotal de respostas: {$context['total_responses']}\n\nDados:\n{$context['data_summary']}\n\nRetorne JSON: {\"title\": \"...\", \"summary\": \"...\", \"key_findings\": [\"...\"], \"charts\": [{\"type\": \"pie|bar|line\", \"title\": \"...\", \"data\": {}}], \"full_report\": \"texto completo do relatório\"}"),
        );

        // Timeout maior para relatórios (60s), usa GPT-4o
        $result = $this->ai_service->chat_completion_json('natural_reports', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
            'timeout' => 60,
        ));

        if ($result['success'] && $result['parsed']) {
            $data = $result['parsed'];
            // Garantir todos os campos esperados pelo app
            $data = array_merge(array(
                'title' => '',
                'summary' => '',
                'key_findings' => array(),
                'charts' => array(),
                'full_report' => '',
            ), $data);

            // Salvar no banco
            $this->Ai_model->create_report(array(
                'title' => $data['title'],
                'questionnaire_id' => $questionnaire_id,
                'report_type' => $report_type,
                'filters_applied' => json_encode($filters),
                'narrative_text' => $data['full_report'],
                'sections' => json_encode($data['key_findings']),
                'charts_data' => json_encode($data['charts']),
                'status' => 'completed',
                'generated_by' => $user,
                'execution_log_id' => isset($result['log_id']) ? $result['log_id'] : null,
            ));

            // Cache por 5 minutos
            $this->Ai_model->set_cache($cache_key, $data, 300);

            echo json_encode(array('success' => true, 'data' => $data));
        } else {
            echo json_encode(array('success' => true, 'data' => array(
                'title' => '',
                'summary' => 'Não foi possível gerar o relatório no momento.',
                'key_findings' => array(),
                'charts' => array(),
                'full_report' => '',
            )));
        }
    }

    // ============================================================
    // 7. POST /api/ai/audit-log
    // Registro de eventos de auditoria
    // ============================================================

    public function audit_log() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;

        $event_type = $this->get_json_input('event_type');
        $timestamp = $this->get_json_input('timestamp');

        if (!$event_type || !$timestamp) {
            echo json_encode(array('success' => false, 'message' => 'event_type e timestamp são obrigatórios'));
            return;
        }

        $valid_events = array(
            'transcription_used', 'transcription_failed',
            'inconsistency_shown', 'inconsistency_corrected', 'inconsistency_ignored', 'inconsistency_deferred',
            'standardization_accepted', 'standardization_rejected',
            'smart_field_applied', 'smart_field_ignored',
            'reformulation_used',
            'adaptive_route_used', 'adaptive_fallback',
            'follow_up_applied', 'follow_up_ignored',
            'report_generated',
        );

        if (!in_array($event_type, $valid_events)) {
            echo json_encode(array('success' => false, 'message' => 'event_type inválido'));
            return;
        }

        $metadata = $this->get_json_input('metadata');

        $id = $this->Ai_model->create_audit_log(array(
            'event_type' => $event_type,
            'question_id' => $this->get_json_input('question_id'),
            'questionnaire_id' => $this->get_json_input('questionnaire_id'),
            'form_id' => $this->get_json_input('form_id'),
            'user_id' => $user,
            'metadata' => is_array($metadata) ? json_encode($metadata) : $metadata,
            'timestamp' => date('Y-m-d H:i:s', strtotime($timestamp)),
        ));

        echo json_encode(array('success' => true, 'data' => array('id' => $id)));
    }

    // ============================================================
    // 8. POST /api/ai/audit-log/batch
    // Envio em lote de eventos de auditoria
    // ============================================================

    public function audit_log_batch() {
        $user = $this->verify_auth();
        if (!$user) return;

        if (!$this->require_post()) return;

        $events = $this->get_json_input('events');

        if (!$events || !is_array($events)) {
            echo json_encode(array('success' => false, 'message' => 'events é obrigatório e deve ser um array'));
            return;
        }

        if (count($events) > 500) {
            echo json_encode(array('success' => false, 'message' => 'Máximo de 500 eventos por lote'));
            return;
        }

        $prepared = array();
        foreach ($events as $event) {
            if (empty($event['event_type']) || empty($event['timestamp'])) {
                continue;
            }

            $metadata = isset($event['metadata']) ? $event['metadata'] : null;

            $prepared[] = array(
                'event_type' => $event['event_type'],
                'question_id' => isset($event['question_id']) ? $event['question_id'] : null,
                'questionnaire_id' => isset($event['questionnaire_id']) ? $event['questionnaire_id'] : null,
                'form_id' => isset($event['form_id']) ? $event['form_id'] : null,
                'user_id' => $user,
                'metadata' => is_array($metadata) ? json_encode($metadata) : $metadata,
                'timestamp' => date('Y-m-d H:i:s', strtotime($event['timestamp'])),
            );
        }

        $inserted = $this->Ai_model->create_audit_logs_batch($prepared);

        echo json_encode(array('success' => true, 'data' => array('inserted' => $inserted)));
    }

    /**
     * GET /api/ai/approved-suggestions?questionnaire_id=X
     * Retorna sugestões aprovadas pelo painel para exibir ao entrevistador no app
     */
    public function approved_suggestions() {
        $user = $this->verify_auth();
        if (!$user) return;

        $questionnaire_id = (int) $this->input->get('questionnaire_id');

        if (!$questionnaire_id) {
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id é obrigatório'));
            return;
        }

        $suggestions = $this->Ai_model->get_field_suggestions(
            array('questionnaire_id' => $questionnaire_id, 'status' => 'approved')
        );

        $result = array();
        foreach ($suggestions as $s) {
            $result[] = array(
                'question_id'     => (int) $s['question_id'],
                'suggested_value' => $s['suggested_value'],
                'confidence'      => $s['confidence_score'] ? (float) $s['confidence_score'] : null,
                'context'         => $s['context_data'] ? json_decode($s['context_data'], true) : null,
            );
        }

        echo json_encode(array(
            'success' => true,
            'data'    => array('suggestions' => $result),
        ));
    }

    // ============================================================
    // HELPERS INTERNOS
    // ============================================================

    /**
     * Padronização local com regex (sem chamar IA)
     */
    private function standardize_local($value) {
        $trimmed = trim($value);

        // Telefone brasileiro: 11 dígitos (com DDD)
        $digits_only = preg_replace('/\D/', '', $trimmed);
        if (preg_match('/^(\d{2})(\d{5})(\d{4})$/', $digits_only, $m)) {
            return array(
                'value' => "({$m[1]}) {$m[2]}-{$m[3]}",
                'type' => 'phone_mask',
                'confidence' => 0.99,
            );
        }
        // Telefone fixo: 10 dígitos
        if (preg_match('/^(\d{2})(\d{4})(\d{4})$/', $digits_only, $m)) {
            return array(
                'value' => "({$m[1]}) {$m[2]}-{$m[3]}",
                'type' => 'phone_mask',
                'confidence' => 0.99,
            );
        }

        // CPF: 11 dígitos
        if (preg_match('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', $digits_only, $m) && strlen($digits_only) === 11) {
            return array(
                'value' => "{$m[1]}.{$m[2]}.{$m[3]}-{$m[4]}",
                'type' => 'cpf_mask',
                'confidence' => 0.99,
            );
        }

        // Capitalização simples: tudo minúsculo e com mais de 2 palavras
        if ($trimmed === mb_strtolower($trimmed, 'UTF-8') && str_word_count($trimmed) >= 2 && strlen($trimmed) > 5) {
            $prepositions = array('da', 'de', 'do', 'das', 'dos', 'e', 'em', 'a', 'o', 'à');
            $words = explode(' ', $trimmed);
            $capitalized = array();
            foreach ($words as $i => $word) {
                if ($i > 0 && in_array(mb_strtolower($word, 'UTF-8'), $prepositions)) {
                    $capitalized[] = mb_strtolower($word, 'UTF-8');
                } else {
                    $capitalized[] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
                }
            }
            $result = implode(' ', $capitalized);
            if ($result !== $trimmed) {
                return array(
                    'value' => $result,
                    'type' => 'capitalization',
                    'confidence' => 0.95,
                );
            }
        }

        return null;
    }

    /**
     * Padronização via OpenAI para campos complexos
     */
    private function standardize_with_ai($questionnaire_id, $remaining) {
        $fields_text = "";
        foreach ($remaining as $qid => $val) {
            $fields_text .= "Campo {$qid}: \"{$val}\"\n";
        }

        $messages = array(
            array('role' => 'system', 'content' => 'Você é um especialista em padronização de dados brasileiros. Corrija capitalização, acentuação, formatação de endereços e outras inconsistências. Responda APENAS em JSON válido.'),
            array('role' => 'user', 'content' => "Padronize os seguintes campos:\n\n{$fields_text}\nRetorne JSON: [{\"question_id\": N, \"original_value\": \"...\", \"suggested_value\": \"...\", \"type\": \"capitalization|address|spelling|cleanup|phone_mask|city_state\", \"confidence\": 0.0}]\n\nSe um campo já estiver correto, não inclua na lista."),
        );

        $result = $this->ai_service->chat_completion_json('data_correction', $messages, array(
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed'] && is_array($result['parsed'])) {
            return $result['parsed'];
        }

        return array();
    }

    /**
     * Avalia condição de roteamento adaptativo
     */
    private function evaluate_adaptive_condition($logic, $responses) {
        if (!isset($logic['question_id']) || !isset($logic['operator']) || !isset($logic['value'])) {
            return false;
        }

        $qid = (string) $logic['question_id'];
        if (!isset($responses[$qid])) {
            return false;
        }

        $actual = $responses[$qid];
        $expected = $logic['value'];

        switch ($logic['operator']) {
            case 'equals':
                return strtolower(trim($actual)) === strtolower(trim($expected));
            case 'contains':
                return stripos($actual, $expected) !== false;
            case 'not_equals':
                return strtolower(trim($actual)) !== strtolower(trim($expected));
            case 'greater_than':
                return (float) $actual > (float) $expected;
            case 'less_than':
                return (float) $actual < (float) $expected;
            default:
                return false;
        }
    }

    /**
     * Valida que o método é POST
     */
    private function require_post() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
            return false;
        }
        return true;
    }

    /**
     * Verifica rate limit (10 chamadas/minuto por usuário)
     */
    private function check_rate_limit($user_id, $endpoint) {
        if (!$this->Ai_model->check_rate_limit($user_id, $endpoint, 10, 60)) {
            $this->output->set_status_header(429);
            echo json_encode(array('success' => false, 'message' => 'Rate limit excedido. Tente novamente em 1 minuto.'));
            return false;
        }
        return true;
    }

    // ============================================================
    // AUTENTICAÇÃO
    // ============================================================

    private function verify_auth() {
        $auth_header = $this->input->get_request_header('Authorization');

        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            // Fallback: verificar sessão admin
            $this->load->library('session');
            if ($this->session->userdata('admin_logged_in')) {
                return $this->session->userdata('admin_id');
            }

            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Authentication required'));
            return false;
        }

        $token = substr($auth_header, 7);
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded || !isset($decoded['user_id']) || !isset($decoded['expires_at'])) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Invalid token'));
            return false;
        }

        if ($decoded['expires_at'] < time()) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Token expired'));
            return false;
        }

        return $decoded['user_id'];
    }

    private function get_json_input($key) {
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json && isset($json[$key])) {
            return $json[$key];
        }
        return $this->input->post($key);
    }
}
