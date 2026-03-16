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
            $features[$s->feature_key] = array(
                'name' => $s->feature_name,
                'enabled' => (bool) $s->is_enabled,
                'model' => $s->model,
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
