<?php
// application/controllers/api/Auth.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('api_token');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($this->input->method() === 'options') {
            exit();
        }
    }

    public function login() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $json = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($json['username']) || !isset($json['password'])) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }

        $username = $json['username'];
        $password = $json['password'];

        $user = $this->User_model->authenticate($username, $password);

        if ($user) {
            $token = $this->api_token->issue($user->id);
            
            // Log da atividade
            $this->log_activity($user->id, 'login', 'auth', null, [
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent()
            ]);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'is_active' => $user->is_active
                ]
            ]);
        } else {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }

    public function verify() {
        // Valida assinatura, prazo e se o usuario continua ativo
        $user_id = $this->api_token->user_id_from_request();

        if ($user_id) {
            $user = $this->User_model->get_by_id($user_id);

            if ($user) {
                echo json_encode([
                    'success' => true,
                    // Renovacao deslizante: token novo com prazo cheio. O app
                    // grava por cima do atual, entao quem abre o app ao menos
                    // uma vez a cada 28 dias nunca tem a sessao vencida.
                    'token' => $this->api_token->issue($user->id),
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'full_name' => $user->full_name,
                        'role' => $user->role,
                        'is_active' => $user->is_active
                    ]
                ]);
            } else {
                $this->output->set_status_header(401);
                echo json_encode(['success' => false, 'message' => 'User not found or inactive']);
            }
        } else {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
        }
    }

    private function log_activity($user_id, $action, $resource_type, $resource_id, $details) {
        $this->db->insert('activity_logs', [
            'user_id' => $user_id,
            'action' => $action,
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'details' => json_encode($details),
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}