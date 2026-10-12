<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Upload dos audios gravados durante a entrevista.
 *
 * O app envia o arquivo aqui e depois manda o nome no campo audio_path do
 * forms/submit. Sem esta rota o envio dava 404 e os audios ficavam presos no
 * aparelho.
 */
class Audios extends CI_Controller {

    // m4a/aac: Android e iPhone. webm/mp4: versao web (Chrome e Safari).
    private $allowed_extensions = array('m4a', 'mp3', 'aac', 'wav', 'ogg', 'webm', 'mp4');
    private $max_size_bytes = 104857600; // 100 MB

    public function __construct() {
        parent::__construct();
        $this->load->library('api_token');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($this->input->method() === 'options') {
            exit();
        }
    }

    public function upload() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!$this->api_token->user_id_from_request()) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        if (!isset($_FILES['audio']) || !is_array($_FILES['audio'])) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'No audio file provided']);
            return;
        }

        $file = $_FILES['audio'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $this->php_upload_error_message($file['error']);
            log_message('error', "Audios::upload - erro no upload: {$msg} (code {$file['error']})");
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => $msg]);
            return;
        }

        if ($file['size'] > $this->max_size_bytes) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Arquivo excede o limite de 100MB']);
            return;
        }

        // Validado pela extensao e nao pelo MIME: o MIME informado pelo
        // Android varia (audio/mp4, audio/aac, audio/x-m4a) e a biblioteca de
        // upload do CodeIgniter recusaria arquivos validos.
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions, true)) {
            $this->output->set_status_header(400);
            echo json_encode([
                'success' => false,
                'message' => "Extensao '.{$ext}' nao permitida. Aceitos: " . implode(', ', $this->allowed_extensions)
            ]);
            return;
        }

        // Mantem o nome gerado pelo app (e o vinculo com o formulario), sem
        // caminhos nem caracteres estranhos
        $safe_name = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($file['name']));
        if ($safe_name === '' || $safe_name[0] === '.') {
            $safe_name = 'audio_' . time() . '.' . $ext;
        }

        $upload_dir = FCPATH . 'uploads/audios/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            log_message('error', "Audios::upload - falha ao criar {$upload_dir}");
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Falha ao preparar diretorio de upload']);
            return;
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $safe_name)) {
            log_message('error', "Audios::upload - move_uploaded_file falhou para {$safe_name}");
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar arquivo no servidor']);
            return;
        }

        echo json_encode([
            'success'   => true,
            'filename'  => $safe_name,
            'file_size' => $file['size'],
        ]);
    }

    private function php_upload_error_message($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:   return 'Arquivo excede upload_max_filesize do php.ini';
            case UPLOAD_ERR_FORM_SIZE:  return 'Arquivo excede MAX_FILE_SIZE do formulario';
            case UPLOAD_ERR_PARTIAL:    return 'Upload parcial (interrompido)';
            case UPLOAD_ERR_NO_FILE:    return 'Nenhum arquivo enviado';
            case UPLOAD_ERR_NO_TMP_DIR: return 'Diretorio temporario do PHP nao configurado';
            case UPLOAD_ERR_CANT_WRITE: return 'Falha ao escrever no disco';
            case UPLOAD_ERR_EXTENSION:  return 'Upload bloqueado por uma extensao do PHP';
            default:                    return 'Erro de upload desconhecido';
        }
    }
}
