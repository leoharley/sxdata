<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ai_service - Serviço de integração com OpenAI
 *
 * Gerencia todas as chamadas à API da OpenAI com:
 * - Timeout configurável
 * - Retry com backoff exponencial
 * - Logging automático de execuções
 * - Fallback em caso de falha
 * - Cálculo de custo estimado
 */
class Ai_service {

    private $CI;
    private $api_key;
    private $base_url = 'https://api.openai.com/v1';
    private $default_timeout = 30;
    private $default_retries = 2;

    // Custo por 1K tokens (estimativa - atualizar conforme pricing da OpenAI)
    private $token_costs = array(
        'gpt-4o' => array('input' => 0.0025, 'output' => 0.01),
        'gpt-4o-mini' => array('input' => 0.00015, 'output' => 0.0006),
        'gpt-4-turbo' => array('input' => 0.01, 'output' => 0.03),
        'gpt-3.5-turbo' => array('input' => 0.0005, 'output' => 0.0015),
        'whisper-1' => array('input' => 0.006, 'output' => 0),
    );

    public function __construct() {
        $this->CI =& get_instance();
        $this->api_key = getenv('OPENAI_API_KEY');

        if (!$this->api_key) {
            $this->api_key = isset($_ENV['OPENAI_API_KEY']) ? $_ENV['OPENAI_API_KEY'] : '';
        }

        $this->CI->load->model('Ai_model');
    }

    /**
     * Verifica se a API key está configurada
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Testa a conexão com a OpenAI
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'API Key não configurada. Defina a variável de ambiente OPENAI_API_KEY.',
                'details' => null
            );
        }

        try {
            $response = $this->make_request('GET', '/models', null, 10);

            if ($response['http_code'] === 200) {
                $models = json_decode($response['body'], true);
                $model_count = isset($models['data']) ? count($models['data']) : 0;
                return array(
                    'success' => true,
                    'message' => "Conexão bem-sucedida! {$model_count} modelos disponíveis.",
                    'details' => array(
                        'models_available' => $model_count,
                        'response_time_ms' => $response['duration_ms']
                    )
                );
            }

            $error = json_decode($response['body'], true);
            return array(
                'success' => false,
                'message' => 'Erro na conexão: ' . ($error['error']['message'] ?? 'Erro desconhecido'),
                'details' => $error
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Exceção: ' . $e->getMessage(),
                'details' => null
            );
        }
    }

    /**
     * Executa um chat completion
     */
    public function chat_completion($feature_key, $messages, $options = array()) {
        $setting = $this->CI->Ai_model->get_setting($feature_key);

        if (!$setting || !$setting->is_enabled) {
            return $this->error_response("Funcionalidade '{$feature_key}' não está habilitada.");
        }

        $model = isset($options['model']) ? $options['model'] : $setting->model;
        $temperature = isset($options['temperature']) ? $options['temperature'] : (float) $setting->temperature;
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : (int) $setting->max_tokens;
        $timeout = isset($options['timeout']) ? $options['timeout'] : (int) $setting->timeout_seconds;
        $retries = isset($options['retries']) ? $options['retries'] : (int) $setting->retry_attempts;

        $payload = array(
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        );

        // Log de início
        $log_id = $this->CI->Ai_model->log_execution(array(
            'feature_key' => $feature_key,
            'model_used' => $model,
            'prompt_id' => isset($options['prompt_id']) ? $options['prompt_id'] : null,
            'input_data' => json_encode(array('messages' => $messages, 'options' => $options)),
            'status' => 'processing',
            'executed_by' => $this->CI->session->userdata('admin_id'),
            'resource_type' => isset($options['resource_type']) ? $options['resource_type'] : null,
            'resource_id' => isset($options['resource_id']) ? $options['resource_id'] : null,
        ));

        $start_time = microtime(true);
        $last_error = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            if ($attempt > 0) {
                // Backoff exponencial: 1s, 2s, 4s...
                usleep(pow(2, $attempt - 1) * 1000000);
            }

            try {
                $response = $this->make_request('POST', '/chat/completions', $payload, $timeout);
                $duration_ms = (int) ((microtime(true) - $start_time) * 1000);

                if ($response['http_code'] === 200) {
                    $result = json_decode($response['body'], true);

                    $tokens_input = $result['usage']['prompt_tokens'] ?? 0;
                    $tokens_output = $result['usage']['completion_tokens'] ?? 0;
                    $cost = $this->calculate_cost($model, $tokens_input, $tokens_output);

                    $content = $result['choices'][0]['message']['content'] ?? '';

                    // Atualiza log
                    $this->CI->Ai_model->update_execution_log($log_id, array(
                        'output_data' => json_encode($result),
                        'tokens_input' => $tokens_input,
                        'tokens_output' => $tokens_output,
                        'cost_usd' => $cost,
                        'duration_ms' => $duration_ms,
                        'status' => 'success',
                    ));

                    return array(
                        'success' => true,
                        'content' => $content,
                        'tokens_input' => $tokens_input,
                        'tokens_output' => $tokens_output,
                        'cost_usd' => $cost,
                        'duration_ms' => $duration_ms,
                        'model' => $model,
                        'log_id' => $log_id,
                    );
                }

                $error_body = json_decode($response['body'], true);
                $last_error = $error_body['error']['message'] ?? "HTTP {$response['http_code']}";

                // Não fazer retry para erros 4xx (exceto 429 rate limit)
                if ($response['http_code'] >= 400 && $response['http_code'] < 500 && $response['http_code'] !== 429) {
                    break;
                }

            } catch (Exception $e) {
                $last_error = $e->getMessage();
            }
        }

        $duration_ms = (int) ((microtime(true) - $start_time) * 1000);

        // Log de erro
        $this->CI->Ai_model->update_execution_log($log_id, array(
            'status' => 'error',
            'error_message' => $last_error,
            'duration_ms' => $duration_ms,
        ));

        return $this->error_response($last_error, $log_id);
    }

    /**
     * Transcreve áudio usando Whisper
     */
    public function transcribe_audio($file_path, $options = array()) {
        $setting = $this->CI->Ai_model->get_setting('transcription');

        if (!$setting || !$setting->is_enabled) {
            return $this->error_response("Transcrição de áudio não está habilitada.");
        }

        if (!file_exists($file_path)) {
            return $this->error_response("Arquivo de áudio não encontrado: {$file_path}");
        }

        $language = isset($options['language']) ? $options['language'] : 'pt';
        $model = 'whisper-1';
        $timeout = (int) $setting->timeout_seconds;

        $log_id = $this->CI->Ai_model->log_execution(array(
            'feature_key' => 'transcription',
            'model_used' => $model,
            'input_data' => json_encode(array('file' => basename($file_path), 'language' => $language)),
            'status' => 'processing',
            'executed_by' => $this->CI->session->userdata('admin_id'),
            'resource_type' => isset($options['resource_type']) ? $options['resource_type'] : 'audio',
            'resource_id' => isset($options['resource_id']) ? $options['resource_id'] : null,
        ));

        $start_time = microtime(true);

        try {
            $ch = curl_init();

            $post_data = array(
                'file' => new CURLFile($file_path),
                'model' => $model,
                'language' => $language,
                'response_format' => 'verbose_json',
            );

            curl_setopt_array($ch, array(
                CURLOPT_URL => $this->base_url . '/audio/transcriptions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $this->api_key,
                ),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
            ));

            $body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $duration_ms = (int) ((microtime(true) - $start_time) * 1000);

            if (curl_errno($ch)) {
                throw new Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);

            if ($http_code === 200) {
                $result = json_decode($body, true);
                $duration_audio = $result['duration'] ?? 0;
                $cost = ($duration_audio / 60) * 0.006;

                $this->CI->Ai_model->update_execution_log($log_id, array(
                    'output_data' => json_encode($result),
                    'cost_usd' => $cost,
                    'duration_ms' => $duration_ms,
                    'status' => 'success',
                ));

                return array(
                    'success' => true,
                    'text' => $result['text'] ?? '',
                    'language' => $result['language'] ?? $language,
                    'duration' => $duration_audio,
                    'segments' => $result['segments'] ?? array(),
                    'cost_usd' => $cost,
                    'duration_ms' => $duration_ms,
                    'log_id' => $log_id,
                );
            }

            $error = json_decode($body, true);
            $error_msg = $error['error']['message'] ?? "HTTP {$http_code}";

            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error',
                'error_message' => $error_msg,
                'duration_ms' => $duration_ms,
            ));

            return $this->error_response($error_msg, $log_id);

        } catch (Exception $e) {
            $duration_ms = (int) ((microtime(true) - $start_time) * 1000);
            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'duration_ms' => $duration_ms,
            ));
            return $this->error_response($e->getMessage(), $log_id);
        }
    }

    /**
     * Executa chat completion e tenta parsear JSON da resposta
     */
    public function chat_completion_json($feature_key, $messages, $options = array()) {
        $result = $this->chat_completion($feature_key, $messages, $options);

        if (!$result['success']) {
            return $result;
        }

        $content = $result['content'];

        // Tentar extrair JSON do conteúdo (pode vir com markdown ```json ... ```)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: tentar encontrar [ ou { no início
            $content = trim($content);
            if ($content[0] === '[' || $content[0] === '{') {
                $parsed = json_decode($content, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['parsed'] = null;
                $result['parse_error'] = 'Resposta da IA não é um JSON válido: ' . json_last_error_msg();
                return $result;
            }
        }

        $result['parsed'] = $parsed;
        return $result;
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    /**
     * Faz requisição HTTP para a API da OpenAI
     */
    private function make_request($method, $endpoint, $payload = null, $timeout = 30) {
        $ch = curl_init();

        $headers = array(
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
        );

        $options = array(
            CURLOPT_URL => $this->base_url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        );

        if ($method === 'POST' && $payload) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $options);

        $start = microtime(true);
        $body = curl_exec($ch);
        $duration_ms = (int) ((microtime(true) - $start) * 1000);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL Error: ' . $error);
        }

        curl_close($ch);

        return array(
            'http_code' => $http_code,
            'body' => $body,
            'duration_ms' => $duration_ms,
        );
    }

    /**
     * Calcula custo estimado
     */
    private function calculate_cost($model, $tokens_input, $tokens_output) {
        if (!isset($this->token_costs[$model])) {
            return 0;
        }

        $costs = $this->token_costs[$model];
        return (($tokens_input / 1000) * $costs['input']) + (($tokens_output / 1000) * $costs['output']);
    }

    /**
     * Resposta de erro padronizada
     */
    private function error_response($message, $log_id = null) {
        return array(
            'success' => false,
            'error' => $message,
            'log_id' => $log_id,
        );
    }
}
