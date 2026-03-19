<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Ai_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Response_model');
        $this->load->library('ai_service');
        $this->load->library('ai_prompt_service');
        $this->check_auth();
    }

    // ============================================================
    // DASHBOARD DE IA
    // ============================================================

    public function index() {
        $data['title'] = 'Inteligência Artificial - SXData';
        $data['stats'] = $this->Ai_model->get_dashboard_stats();
        $data['execution_stats'] = $this->Ai_model->get_execution_stats();
        $data['settings'] = $this->Ai_model->get_settings_by_category();
        $data['is_configured'] = $this->ai_service->is_configured();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/dashboard', $data);
        $this->load->view('admin/footer');
    }

    // ============================================================
    // CONFIGURAÇÕES
    // ============================================================

    public function settings() {
        $data['title'] = 'Configurações de IA - SXData';
        $data['settings'] = $this->Ai_model->get_settings_by_category();
        $data['prompts'] = $this->Ai_model->get_prompts();
        $data['is_configured'] = $this->ai_service->is_configured();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/settings', $data);
        $this->load->view('admin/footer');
    }

    public function update_setting() {
        $id = $this->input->post('id');
        $data = array(
            'is_enabled' => $this->input->post('is_enabled') ? TRUE : FALSE,
            'model' => $this->input->post('model'),
            'temperature' => (float) $this->input->post('temperature'),
            'max_tokens' => (int) $this->input->post('max_tokens'),
            'timeout_seconds' => (int) $this->input->post('timeout_seconds'),
            'retry_attempts' => (int) $this->input->post('retry_attempts'),
        );

        $this->Ai_model->update_setting($id, $data);
        $this->session->set_flashdata('success', 'Configuração atualizada com sucesso!');
        redirect('ai/settings');
    }

    public function toggle_feature() {
        $feature_key = $this->input->post('feature_key');
        $enabled = $this->input->post('enabled') === 'true';

        $this->Ai_model->toggle_feature($feature_key, $enabled);

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }

    public function test_connection() {
        $result = $this->ai_service->test_connection();

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ============================================================
    // PROMPTS
    // ============================================================

    public function prompts() {
        $data['title'] = 'Prompts de IA - SXData';
        $data['prompts'] = $this->Ai_model->get_prompts();
        $data['settings'] = $this->Ai_model->get_all_settings();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/prompts', $data);
        $this->load->view('admin/footer');
    }

    public function save_prompt() {
        $id = $this->input->post('id');
        $data = array(
            'feature_key' => $this->input->post('feature_key'),
            'prompt_name' => $this->input->post('prompt_name'),
            'system_prompt' => $this->input->post('system_prompt'),
            'user_prompt_template' => $this->input->post('user_prompt_template'),
            'is_active' => $this->input->post('is_active') ? TRUE : FALSE,
        );

        if ($id) {
            $this->Ai_model->update_prompt($id, $data);
            $this->session->set_flashdata('success', 'Prompt atualizado!');
        } else {
            $data['version'] = 1;
            $data['created_by'] = $this->session->userdata('admin_id');
            $this->Ai_model->create_prompt($data);
            $this->session->set_flashdata('success', 'Prompt criado!');
        }

        redirect('ai/prompts');
    }

    // ============================================================
    // LOGS
    // ============================================================

    public function logs() {
        $data['title'] = 'Logs de IA - SXData';

        $filters = array(
            'feature_key' => $this->input->get('feature_key'),
            'status' => $this->input->get('status'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
        );

        $page = max(1, (int) $this->input->get('page'));
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $data['logs'] = $this->Ai_model->get_execution_logs($filters, $per_page, $offset);
        $data['total'] = $this->Ai_model->count_execution_logs($filters);
        $data['filters'] = $filters;
        $data['page'] = $page;
        $data['per_page'] = $per_page;
        $data['settings'] = $this->Ai_model->get_all_settings();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/logs', $data);
        $this->load->view('admin/footer');
    }

    // ============================================================
    // TRANSCRIÇÃO DE ÁUDIO
    // ============================================================

    public function transcriptions() {
        $data['title'] = 'Transcrições de Áudio - SXData';
        $data['transcriptions'] = $this->Ai_model->get_transcriptions();
        $data['status_counts'] = $this->Ai_model->count_transcriptions_by_status();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('transcription');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/transcriptions', $data);
        $this->load->view('admin/footer');
    }

    public function upload_audio() {
        $config = array(
            'upload_path' => FCPATH . 'uploads/audio/',
            'allowed_types' => 'mp3|wav|m4a|ogg|webm|mp4',
            'max_size' => 25600, // 25MB (limite Whisper)
        );

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('audio_file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            redirect('ai/transcriptions');
            return;
        }

        $upload_data = $this->upload->data();
        $form_response_id = $this->input->post('form_response_id') ?: null;
        $question_id = $this->input->post('question_id') ?: null;

        $transcription_id = $this->Ai_model->create_transcription(array(
            'form_response_id' => $form_response_id,
            'question_id' => $question_id,
            'audio_file_path' => 'uploads/audio/' . $upload_data['file_name'],
            'status' => 'pending',
        ));

        // Processar imediatamente se a feature estiver ativa
        if ($this->Ai_model->is_feature_enabled('transcription')) {
            $this->process_transcription($transcription_id);
        }

        $this->session->set_flashdata('success', 'Áudio enviado com sucesso!');
        redirect('ai/transcriptions');
    }

    public function process_transcription($id = null) {
        if (!$id) {
            $id = $this->input->post('id');
        }

        $transcription = $this->Ai_model->get_transcription($id);
        if (!$transcription) {
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'error' => 'Transcrição não encontrada'));
                return;
            }
            $this->session->set_flashdata('error', 'Transcrição não encontrada.');
            redirect('ai/transcriptions');
            return;
        }

        $file_path = FCPATH . $transcription->audio_file_path;
        $this->Ai_model->update_transcription($id, array('status' => 'processing'));

        $result = $this->ai_service->transcribe_audio($file_path, array(
            'resource_type' => 'transcription',
            'resource_id' => $id,
        ));

        if ($result['success']) {
            $this->Ai_model->update_transcription($id, array(
                'transcription_text' => $result['text'],
                'audio_duration_seconds' => $result['duration'],
                'language' => $result['language'],
                'status' => 'completed',
                'model_used' => 'whisper-1',
                'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => $this->session->userdata('admin_id'),
            ));
        } else {
            $this->Ai_model->update_transcription($id, array(
                'status' => 'error',
                'error_message' => $result['error'],
            ));
        }

        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Transcrição concluída!');
        } else {
            $this->session->set_flashdata('error', 'Erro na transcrição: ' . $result['error']);
        }
        redirect('ai/transcriptions');
    }

    public function save_transcription_edit() {
        $id = $this->input->post('id');
        $edited_text = $this->input->post('transcription_edited');

        $this->Ai_model->update_transcription($id, array(
            'transcription_edited' => $edited_text,
        ));

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }

    // ============================================================
    // DETECÇÃO DE INCONSISTÊNCIAS
    // ============================================================

    public function inconsistencies() {
        $data['title'] = 'Detecção de Inconsistências - SXData';

        $filters = array(
            'severity' => $this->input->get('severity'),
            'resolution_status' => $this->input->get('status'),
            'questionnaire_id' => $this->input->get('questionnaire_id'),
        );

        $data['inconsistencies'] = $this->Ai_model->get_inconsistencies($filters);
        $data['severity_counts'] = $this->Ai_model->count_inconsistencies_by_severity();
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['filters'] = $filters;
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('inconsistency_detection');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/inconsistencies', $data);
        $this->load->view('admin/footer');
    }

    public function analyze_inconsistencies() {
        header('Content-Type: application/json');

        try {
            $form_response_id = $this->input->post('form_response_id');

            if (!$form_response_id) {
                echo json_encode(array('success' => false, 'message' => 'Informe o ID da resposta.'));
                return;
            }

            // Verificar se a feature está habilitada
            if (!$this->Ai_model->is_feature_enabled('inconsistency_detection')) {
                echo json_encode(array('success' => false, 'message' => 'A funcionalidade "Detecção de Inconsistências" está desabilitada. Ative-a em Configurações de IA.'));
                return;
            }

            // Verificar se a API key está configurada
            if (!$this->ai_service->is_configured()) {
                echo json_encode(array('success' => false, 'message' => 'A chave da API OpenAI não está configurada. Verifique o arquivo .env'));
                return;
            }

            $context = $this->ai_prompt_service->prepare_inconsistency_data($form_response_id);
            if (!$context) {
                echo json_encode(array('success' => false, 'message' => 'Resposta #' . $form_response_id . ' não encontrada no banco de dados.'));
                return;
            }

            $messages_result = $this->ai_prompt_service->build_messages('inconsistency_detection', $context);
            if (!$messages_result['success']) {
                $error_msg = isset($messages_result['error']) ? $messages_result['error'] : 'Erro ao montar prompt.';
                echo json_encode(array('success' => false, 'message' => $error_msg));
                return;
            }

            $result = $this->ai_service->chat_completion_json('inconsistency_detection', $messages_result['messages'], array(
                'prompt_id' => $messages_result['prompt_id'],
                'resource_type' => 'form_response',
                'resource_id' => $form_response_id,
            ));

            if ($result['success'] && isset($result['parsed']) && $result['parsed']) {
                $items = is_array($result['parsed']) && isset($result['parsed'][0]) ? $result['parsed'] : array($result['parsed']);
                $count = 0;

                foreach ($items as $item) {
                    if (!is_array($item) || empty($item['description'])) continue;

                    $this->Ai_model->create_inconsistency(array(
                        'form_response_id' => $form_response_id,
                        'questionnaire_id' => $context['questionnaire_id'],
                        'inconsistency_type' => isset($item['inconsistency_type']) ? $item['inconsistency_type'] : 'logic',
                        'severity' => isset($item['severity']) ? $item['severity'] : 'medium',
                        'consistency_score' => isset($item['consistency_score']) ? $item['consistency_score'] : null,
                        'description' => isset($item['description']) ? $item['description'] : '',
                        'ai_justification' => isset($item['description']) ? $item['description'] : '',
                        'affected_questions' => json_encode(isset($item['affected_questions']) ? $item['affected_questions'] : array()),
                        'suggested_action' => isset($item['suggested_action']) ? $item['suggested_action'] : '',
                        'execution_log_id' => isset($result['log_id']) ? $result['log_id'] : null,
                    ));
                    $count++;
                }

                echo json_encode(array(
                    'success' => true,
                    'message' => 'Análise concluída com sucesso.',
                    'count' => $count,
                ));
            } else {
                $error_msg = isset($result['error']) ? $result['error'] : 'Erro ao processar análise com IA.';
                echo json_encode(array('success' => false, 'message' => $error_msg));
            }

        } catch (Exception $e) {
            log_message('error', 'AI analyze_inconsistencies error: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => 'Erro interno: ' . $e->getMessage()));
        }
    }

    public function resolve_inconsistency() {
        $id = $this->input->post('id');
        $action = $this->input->post('action'); // confirmed, dismissed, resolved

        $this->Ai_model->resolve_inconsistency($id, array(
            'resolution_status' => $action,
            'resolved_by' => $this->session->userdata('admin_id'),
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolution_notes' => $this->input->post('notes'),
        ));

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }

    // ============================================================
    // CORREÇÃO E PADRONIZAÇÃO
    // ============================================================

    public function corrections() {
        $data['title'] = 'Correção e Padronização - SXData';

        $filters = array(
            'status' => $this->input->get('status'),
            'correction_type' => $this->input->get('type'),
        );

        $data['corrections'] = $this->Ai_model->get_corrections($filters);
        $data['filters'] = $filters;
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('data_correction');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/corrections', $data);
        $this->load->view('admin/footer');
    }

    public function analyze_corrections() {
        $form_response_id = $this->input->post('form_response_id');

        $context = $this->ai_prompt_service->prepare_correction_data($form_response_id);
        if (!$context) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => 'Resposta não encontrada'));
            return;
        }

        $messages_result = $this->ai_prompt_service->build_messages('data_correction', $context);
        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('data_correction', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'form_response',
            'resource_id' => $form_response_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $items = is_array($result['parsed']) && isset($result['parsed'][0]) ? $result['parsed'] : array($result['parsed']);

            foreach ($items as $item) {
                $this->Ai_model->create_correction(array(
                    'form_response_id' => $form_response_id,
                    'question_id' => $item['question_id'] ?? null,
                    'original_value' => $item['original_value'] ?? '',
                    'suggested_value' => $item['suggested_value'] ?? '',
                    'correction_type' => $item['correction_type'] ?? 'padronizacao',
                    'confidence_score' => $item['confidence'] ?? null,
                    'execution_log_id' => $result['log_id'],
                ));
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function review_correction() {
        $id = $this->input->post('id');
        $action = $this->input->post('action'); // accepted, rejected, edited

        $data = array(
            'status' => $action,
            'reviewed_by' => $this->session->userdata('admin_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        );

        if ($action === 'accepted' || $action === 'edited') {
            $data['applied_value'] = $this->input->post('applied_value');
        }

        $this->Ai_model->update_correction($id, $data);

        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
    }

    // ============================================================
    // PREENCHIMENTO INTELIGENTE
    // ============================================================

    public function smart_fill() {
        $data['title'] = 'Preenchimento Inteligente - SXData';
        $data['suggestions'] = $this->Ai_model->get_field_suggestions();
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('smart_fill');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/smart_fill', $data);
        $this->load->view('admin/footer');
    }

    public function generate_suggestions() {
        $questionnaire_id = $this->input->post('questionnaire_id');
        $existing_answers = $this->input->post('existing_answers') ?: array();

        $context = $this->ai_prompt_service->prepare_smart_fill_data($questionnaire_id, $existing_answers);
        $messages_result = $this->ai_prompt_service->build_messages('smart_fill', $context);

        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('smart_fill', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $items = is_array($result['parsed']) && isset($result['parsed'][0]) ? $result['parsed'] : array($result['parsed']);

            foreach ($items as $item) {
                $this->Ai_model->create_field_suggestion(array(
                    'questionnaire_id' => $questionnaire_id,
                    'question_id' => $item['question_id'] ?? 0,
                    'suggested_value' => $item['suggested_value'] ?? '',
                    'confidence_score' => $item['confidence'] ?? null,
                    'context_data' => json_encode(array('rationale' => $item['rationale'] ?? '')),
                    'execution_log_id' => $result['log_id'],
                ));
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function approve_suggestion() {
        header('Content-Type: application/json');
        $suggestion_id = (int) $this->input->post('suggestion_id');

        if (!$suggestion_id) {
            echo json_encode(array('success' => false, 'message' => 'ID inválido.'));
            return;
        }

        $result = $this->Ai_model->update_field_suggestion($suggestion_id, array(
            'status'      => 'approved',
            'reviewed_by' => $this->session->userdata('admin_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ));

        echo json_encode(array('success' => (bool) $result));
    }

    public function reject_suggestion() {
        header('Content-Type: application/json');
        $suggestion_id = (int) $this->input->post('suggestion_id');

        if (!$suggestion_id) {
            echo json_encode(array('success' => false, 'message' => 'ID inválido.'));
            return;
        }

        $result = $this->Ai_model->update_field_suggestion($suggestion_id, array(
            'status'      => 'rejected',
            'reviewed_by' => $this->session->userdata('admin_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ));

        echo json_encode(array('success' => (bool) $result));
    }

    // ============================================================
    // REFORMULAÇÃO DE PERGUNTAS
    // ============================================================

    public function reformulations() {
        $data['title'] = 'Reformulação de Perguntas - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled']     = $this->Ai_model->is_feature_enabled('question_reformulation');

        $questionnaire_id = (int) $this->input->get('questionnaire_id');
        $data['selected_questionnaire_id'] = $questionnaire_id;
        $data['reformulations'] = $questionnaire_id
            ? $this->Ai_model->get_reformulations_by_questionnaire($questionnaire_id)
            : array();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/reformulations', $data);
        $this->load->view('admin/footer');
    }

    public function generate_reformulation() {
        $question_id = $this->input->post('question_id');
        $objective = $this->input->post('objective') ?: 'clareza';

        $context = $this->ai_prompt_service->prepare_reformulation_data($question_id, $objective);
        if (!$context) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => 'Pergunta não encontrada'));
            return;
        }

        $messages_result = $this->ai_prompt_service->build_messages('question_reformulation', $context);
        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('question_reformulation', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'question',
            'resource_id' => $question_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $parsed = $result['parsed'];
            $this->Ai_model->create_reformulation(array(
                'question_id' => $question_id,
                'original_text' => $context['original_question'],
                'reformulated_text' => $parsed['reformulated_text'] ?? '',
                'objective' => $objective,
                'execution_log_id' => $result['log_id'],
            ));
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function approve_reformulation() {
        header('Content-Type: application/json');

        $reformulation_id = (int) $this->input->post('reformulation_id');
        $action           = $this->input->post('action'); // 'approved' ou 'rejected'

        if (!$reformulation_id || !in_array($action, array('approved', 'rejected'))) {
            echo json_encode(array('success' => false, 'message' => 'Parâmetros inválidos.'));
            return;
        }

        $reformulation = $this->Ai_model->get_reformulation($reformulation_id);
        if (!$reformulation) {
            echo json_encode(array('success' => false, 'message' => 'Reformulação não encontrada.'));
            return;
        }

        $this->Ai_model->update_reformulation($reformulation_id, array(
            'status'      => $action,
            'approved_by' => $this->session->userdata('admin_id'),
            'approved_at' => date('Y-m-d H:i:s'),
        ));

        // Ao aprovar: atualiza o texto da pergunta original
        if ($action === 'approved') {
            $this->load->model('Question_model');
            $this->Question_model->update($reformulation->question_id, array(
                'question_text' => $reformulation->reformulated_text,
            ));
        }

        echo json_encode(array('success' => true, 'action' => $action));
    }

    public function generate_reformulations_batch() {
        header('Content-Type: application/json');

        $questionnaire_id = (int) $this->input->post('questionnaire_id');
        $objective        = $this->input->post('objective') ?: 'clareza';

        if (!$questionnaire_id) {
            echo json_encode(array('success' => false, 'message' => 'Selecione um questionário.'));
            return;
        }

        $this->load->model('Question_model');
        $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);

        if (empty($questions)) {
            echo json_encode(array('success' => false, 'message' => 'Nenhuma pergunta encontrada.'));
            return;
        }

        $generated = 0;
        $errors    = 0;

        foreach ($questions as $question) {
            $q_id    = is_array($question) ? $question['id'] : $question->id;
            $context = $this->ai_prompt_service->prepare_reformulation_data($q_id, $objective);
            if (!$context) { $errors++; continue; }

            $messages_result = $this->ai_prompt_service->build_messages('question_reformulation', $context);
            if (!$messages_result['success']) { $errors++; continue; }

            $result = $this->ai_service->chat_completion_json('question_reformulation', $messages_result['messages'], array(
                'prompt_id'     => $messages_result['prompt_id'],
                'resource_type' => 'question',
                'resource_id'   => $q_id,
            ));

            if ($result['success'] && !empty($result['parsed']['reformulated_text'])) {
                $this->Ai_model->create_reformulation(array(
                    'question_id'       => $q_id,
                    'original_text'     => $context['original_question'],
                    'reformulated_text' => $result['parsed']['reformulated_text'],
                    'objective'         => $objective,
                    'execution_log_id'  => $result['log_id'],
                ));
                $generated++;
            } else {
                $errors++;
            }
        }

        echo json_encode(array(
            'success'   => $generated > 0,
            'generated' => $generated,
            'errors'    => $errors,
            'message'   => "{$generated} reformulação(ões) gerada(s)." . ($errors > 0 ? " {$errors} falha(s)." : ''),
        ));
    }

    // ============================================================
    // QUESTIONÁRIO ADAPTATIVO
    // ============================================================

    public function adaptive() {
        $data['title'] = 'Questionário Adaptativo - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('adaptive_routing');

        $questionnaire_id = (int) $this->input->get('questionnaire_id');
        $data['selected_questionnaire_id'] = $questionnaire_id;

        if ($questionnaire_id) {
            $data['rules']     = $this->Ai_model->get_adaptive_rules($questionnaire_id);
            $this->load->model('Question_model');
            $data['questions'] = $this->Question_model->get_by_questionnaire($questionnaire_id);
        } else {
            $data['rules']     = array();
            $data['questions'] = array();
        }

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/adaptive', $data);
        $this->load->view('admin/footer');
    }

    // ============================================================
    // SUGESTÕES DE FOLLOW-UP
    // ============================================================

    public function followup() {
        $data['title'] = 'Sugestões de Follow-up - SXData';
        $data['suggestions'] = $this->Ai_model->get_followup_suggestions();
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('followup_suggestions');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/followup', $data);
        $this->load->view('admin/footer');
    }

    public function generate_followup() {
        $questionnaire_id = $this->input->post('questionnaire_id');

        $context = $this->ai_prompt_service->prepare_followup_data($questionnaire_id);
        $messages_result = $this->ai_prompt_service->build_messages('followup_suggestions', $context);

        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('followup_suggestions', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && $result['parsed']) {
            $items = is_array($result['parsed']) && isset($result['parsed'][0]) ? $result['parsed'] : array($result['parsed']);

            foreach ($items as $item) {
                $this->Ai_model->create_followup_suggestion(array(
                    'questionnaire_id' => $questionnaire_id,
                    'suggested_question_text' => $item['question_text'] ?? '',
                    'question_type' => $item['question_type'] ?? 'text',
                    'suggested_options' => json_encode($item['options'] ?? array()),
                    'rationale' => $item['rationale'] ?? '',
                    'execution_log_id' => $result['log_id'],
                ));
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ============================================================
    // ANÁLISE ESTATÍSTICA
    // ============================================================

    public function analysis() {
        $data['title'] = 'Análise Estatística com IA - SXData';
        $data['analyses'] = $this->Ai_model->get_statistical_analyses();
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('statistical_analysis');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/analysis', $data);
        $this->load->view('admin/footer');
    }

    public function generate_analysis() {
        // Buffer para capturar eventuais notices PHP antes de enviar JSON limpo
        ob_start();

        $questionnaire_id = $this->input->post('questionnaire_id');
        $filters = array(
            'date_from' => $this->input->post('date_from'),
            'date_to'   => $this->input->post('date_to'),
        );

        $context         = $this->ai_prompt_service->prepare_statistical_data($questionnaire_id, $filters);
        $messages_result = $this->ai_prompt_service->build_messages('statistical_analysis', $context);

        if (!$messages_result['success']) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion_json('statistical_analysis', $messages_result['messages'], array(
            'prompt_id'     => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id'   => $questionnaire_id,
        ));

        $analysis_id = null;

        if ($result['success'] && $result['parsed']) {
            $parsed = $result['parsed'];

            $summary = $parsed['summary'] ?? '';
            if (is_array($summary)) {
                $summary = json_encode($summary, JSON_UNESCAPED_UNICODE);
            }

            $analysis_id = $this->Ai_model->create_statistical_analysis(array(
                'questionnaire_id'  => $questionnaire_id,
                'analysis_type'     => 'general',
                'filters_applied'   => json_encode($filters),
                'summary_text'      => (string) $summary,
                'patterns'          => json_encode(is_array($parsed['patterns']          ?? null) ? $parsed['patterns']          : array()),
                'outliers'          => json_encode(is_array($parsed['outliers']          ?? null) ? $parsed['outliers']          : array()),
                'trends'            => json_encode(is_array($parsed['trends']            ?? null) ? $parsed['trends']            : array()),
                'insights'          => json_encode(is_array($parsed['insights']          ?? null) ? $parsed['insights']          : array()),
                'chart_suggestions' => json_encode(is_array($parsed['chart_suggestions'] ?? null) ? $parsed['chart_suggestions'] : array()),
                'generated_by'      => $this->session->userdata('admin_id'),
                'execution_log_id'  => $result['log_id'],
            ));

            $result['analysis_id'] = $analysis_id;
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function delete_analysis() {
        $id = $this->input->post('id');
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }
        $this->db->delete('ai_statistical_analyses', ['id' => $id]);
        echo json_encode(['success' => true]);
    }

    public function view_analysis($id) {
        $data['title'] = 'Análise Estatística - SXData';
        $data['analysis'] = $this->Ai_model->get_statistical_analysis($id);

        if (!$data['analysis']) {
            $this->session->set_flashdata('error', 'Análise não encontrada.');
            redirect('ai/analysis');
            return;
        }

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/analysis_view', $data);
        $this->load->view('admin/footer');
    }

    // ============================================================
    // GRÁFICOS INTELIGENTES
    // ============================================================

    public function charts() {
        $data['title'] = 'Gráficos Inteligentes - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('smart_charts');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/charts', $data);
        $this->load->view('admin/footer');
    }

    public function generate_charts() {
        $questionnaire_id = $this->input->post('questionnaire_id');
        $filters = array(
            'date_from' => $this->input->post('date_from'),
            'date_to' => $this->input->post('date_to'),
        );

        $context = $this->ai_prompt_service->prepare_statistical_data($questionnaire_id, $filters);
        $messages_result = $this->ai_prompt_service->build_messages('smart_charts', $context);

        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        ob_start();
        $result = $this->ai_service->chat_completion_json('smart_charts', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));
        ob_end_clean();

        if (!$result['success']) {
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }

        $charts = $result['parsed'] ?? [];
        // A IA pode retornar {charts: [...]} ou diretamente [...]
        if (isset($charts['charts']) && is_array($charts['charts'])) {
            $charts = $charts['charts'];
        }
        if (!is_array($charts)) {
            $charts = [];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'charts' => $charts]);
    }

    // ============================================================
    // RELATÓRIOS EM LINGUAGEM NATURAL
    // ============================================================

    public function reports() {
        $data['title'] = 'Relatórios com IA - SXData';
        $data['reports'] = $this->Ai_model->get_reports();
        $data['questionnaires'] = $this->Questionnaire_model->get_active();
        $data['is_enabled'] = $this->Ai_model->is_feature_enabled('natural_reports');

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/reports', $data);
        $this->load->view('admin/footer');
    }

    public function generate_report() {
        $questionnaire_id = $this->input->post('questionnaire_id');
        $report_type = $this->input->post('report_type') ?: 'general';
        $filters = array(
            'date_from' => $this->input->post('date_from'),
            'date_to' => $this->input->post('date_to'),
        );

        $context = $this->ai_prompt_service->prepare_report_data($questionnaire_id, $filters);
        $messages_result = $this->ai_prompt_service->build_messages('natural_reports', $context);

        if (!$messages_result['success']) {
            header('Content-Type: application/json');
            echo json_encode($messages_result);
            return;
        }

        $result = $this->ai_service->chat_completion($messages_result['messages'][0]['content'] === '' ? 'natural_reports' : 'natural_reports', $messages_result['messages'], array(
            'prompt_id' => $messages_result['prompt_id'],
            'resource_type' => 'questionnaire',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success']) {
            $report_id = $this->Ai_model->create_report(array(
                'title' => 'Relatório - ' . ($context['questionnaire_title'] ?? 'Geral') . ' - ' . date('d/m/Y'),
                'questionnaire_id' => $questionnaire_id,
                'report_type' => $report_type,
                'filters_applied' => json_encode($filters),
                'narrative_text' => $result['content'],
                'generated_by' => $this->session->userdata('admin_id'),
                'execution_log_id' => $result['log_id'],
                'status' => 'completed',
            ));

            $result['report_id'] = $report_id;
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function view_report($id) {
        $data['title'] = 'Relatório - SXData';
        $data['report'] = $this->Ai_model->get_report($id);

        if (!$data['report']) {
            $this->session->set_flashdata('error', 'Relatório não encontrado.');
            redirect('ai/reports');
            return;
        }

        $this->load->view('admin/header', $data);
        $this->load->view('admin/ai/report_view', $data);
        $this->load->view('admin/footer');
    }

    // ============================================================
    // PROCESSAMENTO EM LOTE
    // ============================================================

    public function batch_analyze() {
        header('Content-Type: application/json');

        $type = $this->input->post('type'); // inconsistencies, corrections
        $questionnaire_id = $this->input->post('questionnaire_id');

        if (!$questionnaire_id) {
            echo json_encode(array('success' => false, 'message' => 'Selecione um questionário.'));
            return;
        }

        $feature = $type === 'inconsistencies' ? 'inconsistency_detection' : 'data_correction';

        if (!$this->Ai_model->is_feature_enabled($feature)) {
            $name = $type === 'inconsistencies' ? 'Detecção de Inconsistências' : 'Correção e Padronização';
            echo json_encode(array('success' => false, 'message' => "A funcionalidade \"{$name}\" está desabilitada. Ative-a em Configurações de IA."));
            return;
        }

        $filters = array('questionnaire_id' => $questionnaire_id);
        $responses = $this->Response_model->get_filtered($filters);

        if (empty($responses)) {
            echo json_encode(array('success' => false, 'message' => 'Nenhuma resposta encontrada para este questionário.'));
            return;
        }

        $results = array('processed' => 0, 'errors' => 0, 'total' => count($responses), 'count' => 0);

        foreach ($responses as $response) {
            if ($type === 'inconsistencies') {
                $context = $this->ai_prompt_service->prepare_inconsistency_data($response->id);
            } else {
                $context = $this->ai_prompt_service->prepare_correction_data($response->id);
            }

            if (!$context) {
                $results['errors']++;
                continue;
            }

            $messages_result = $this->ai_prompt_service->build_messages($feature, $context);

            if (!$messages_result['success']) {
                $results['errors']++;
                continue;
            }

            $result = $this->ai_service->chat_completion_json($feature, $messages_result['messages'], array(
                'prompt_id' => $messages_result['prompt_id'],
                'resource_type' => 'form_response',
                'resource_id' => $response->id,
            ));

            if ($result['success'] && $result['parsed']) {
                $items = is_array($result['parsed']) && isset($result['parsed'][0]) ? $result['parsed'] : array($result['parsed']);

                foreach ($items as $item) {
                    if (empty($item['description'])) continue;

                    if ($type === 'inconsistencies') {
                        $this->Ai_model->create_inconsistency(array(
                            'form_response_id' => $response->id,
                            'questionnaire_id' => $questionnaire_id,
                            'inconsistency_type' => isset($item['inconsistency_type']) ? $item['inconsistency_type'] : 'logic',
                            'severity' => isset($item['severity']) ? $item['severity'] : 'medium',
                            'consistency_score' => isset($item['consistency_score']) ? $item['consistency_score'] : null,
                            'description' => isset($item['description']) ? $item['description'] : '',
                            'ai_justification' => isset($item['description']) ? $item['description'] : '',
                            'affected_questions' => json_encode(isset($item['affected_questions']) ? $item['affected_questions'] : array()),
                            'suggested_action' => isset($item['suggested_action']) ? $item['suggested_action'] : '',
                            'execution_log_id' => $result['log_id'],
                        ));
                        $results['count']++;
                    } else {
                        $this->Ai_model->create_correction(array(
                            'form_response_id' => $response->id,
                            'question_id' => isset($item['question_id']) ? $item['question_id'] : null,
                            'original_value' => isset($item['original_value']) ? $item['original_value'] : '',
                            'suggested_value' => isset($item['suggested_value']) ? $item['suggested_value'] : '',
                            'correction_type' => isset($item['correction_type']) ? $item['correction_type'] : 'general',
                            'confidence_score' => isset($item['confidence']) ? $item['confidence'] : null,
                            'execution_log_id' => $result['log_id'],
                        ));
                        $results['count']++;
                    }
                }

                $results['processed']++;
            } else {
                $results['errors']++;
            }

            // Pausa entre chamadas para evitar rate limiting da OpenAI
            usleep(500000); // 0.5s
        }

        echo json_encode(array(
            'success' => true,
            'message' => "Análise em lote concluída. {$results['processed']}/{$results['total']} respostas processadas.",
            'count' => $results['count'],
            'results' => $results,
        ));
    }

    // ============================================================
    // DIAGNÓSTICO (remover após debug)
    // ============================================================

    public function debug_analyze() {
        header('Content-Type: application/json');
        $steps = array();

        try {
            $steps[] = 'Step 1: Controller OK';

            $form_response_id = $this->input->post('form_response_id');
            $steps[] = 'Step 2: form_response_id = ' . var_export($form_response_id, true);

            $enabled = $this->Ai_model->is_feature_enabled('inconsistency_detection');
            $steps[] = 'Step 3: feature_enabled = ' . var_export($enabled, true);

            $configured = $this->ai_service->is_configured();
            $steps[] = 'Step 4: api_configured = ' . var_export($configured, true);

            if ($form_response_id) {
                $context = $this->ai_prompt_service->prepare_inconsistency_data($form_response_id);
                $steps[] = 'Step 5: context = ' . ($context ? 'OK (questionnaire: ' . $context['questionnaire_id'] . ')' : 'NULL - resposta não encontrada');

                if ($context) {
                    $messages_result = $this->ai_prompt_service->build_messages('inconsistency_detection', $context);
                    $steps[] = 'Step 6: build_messages = ' . ($messages_result['success'] ? 'OK' : 'FAIL: ' . (isset($messages_result['error']) ? $messages_result['error'] : 'unknown'));

                    if ($messages_result['success'] && $enabled && $configured) {
                        $test_ai = $this->input->post('test_ai');
                        if ($test_ai) {
                            $result = $this->ai_service->chat_completion_json('inconsistency_detection', $messages_result['messages'], array(
                                'prompt_id' => $messages_result['prompt_id'],
                                'resource_type' => 'form_response',
                                'resource_id' => $form_response_id,
                            ));
                            $steps[] = 'Step 7: OpenAI result success=' . var_export($result['success'], true);
                            if (!$result['success']) {
                                $steps[] = 'Step 7 ERROR: ' . (isset($result['error']) ? $result['error'] : 'unknown');
                            } else {
                                $steps[] = 'Step 7 parsed=' . var_export(isset($result['parsed']) && $result['parsed'] !== null, true);
                                $steps[] = 'Step 7 tokens=' . (isset($result['tokens_input']) ? $result['tokens_input'] : 0) . '+' . (isset($result['tokens_output']) ? $result['tokens_output'] : 0);
                            }
                        } else {
                            $steps[] = 'Step 7: Pronto para OpenAI. Envie test_ai=1 para testar a chamada real.';
                        }
                    }
                }
            }

            echo json_encode(array('success' => true, 'steps' => $steps));

        } catch (Exception $e) {
            $steps[] = 'EXCEPTION: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
            echo json_encode(array('success' => false, 'steps' => $steps));
        }
    }

    // ============================================================
    // AUTH
    // ============================================================

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}
