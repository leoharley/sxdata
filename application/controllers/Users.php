<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Usuários - SXData';
        $data['users'] = $this->User_model->get_all_with_stats();
        $data['stats'] = $this->User_model->get_stats();
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/users/index', $data);
        $this->load->view('admin/footer');
    }

    public function create() {
        $this->form_validation->set_rules('full_name', 'Nome Completo', 'required|max_length[100]');
        $this->form_validation->set_rules('username', 'Usuário', 'required|max_length[50]|is_unique[users.username]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
        $this->form_validation->set_rules('role', 'Função', 'required|in_list[aplicador,supervisor,administrador]');
        $this->form_validation->set_rules('password', 'Senha', 'required|min_length[6]');
        $this->form_validation->set_rules('password_confirm', 'Confirmação de Senha', 'required|matches[password]');

        if ($this->form_validation->run()) {
            $user_data = array(
                'full_name' => $this->input->post('full_name'),
                'username' => $this->input->post('username'),
                'email' => $this->input->post('email'),
                'role' => $this->input->post('role'),
                'password' => $this->input->post('password'),
                'is_active' => TRUE
            );

            if ($this->User_model->create($user_data)) {
                $this->session->set_flashdata('success', 'Usuário criado com sucesso!');
            } else {
                $this->session->set_flashdata('error', 'Erro ao criar usuário.');
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
        }

        redirect('users');
    }

    public function toggle_status($id, $status) {
        $status = $status === 'true' ? TRUE : FALSE;
        
        if ($this->User_model->update($id, array('is_active' => $status))) {
            $action = $status ? 'ativado' : 'desativado';
            $this->session->set_flashdata('success', "Usuário {$action} com sucesso!");
        } else {
            $this->session->set_flashdata('error', 'Erro ao alterar status do usuário.');
        }

        redirect('users');
    }

    public function reset_password($id) {
        $new_password = $this->generate_password();
        
        if ($this->User_model->update($id, array('password' => $new_password))) {
            $user = $this->User_model->get_by_id($id);
            
            // Aqui você pode implementar o envio por email
            $this->session->set_flashdata('success', "Senha redefinida! Nova senha: {$new_password}");
        } else {
            $this->session->set_flashdata('error', 'Erro ao redefinir senha.');
        }

        redirect('users');
    }

    private function generate_password($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
        
        // Verificar se é administrador ou supervisor
        $role = $this->session->userdata('admin_role');
        if (!in_array($role, ['administrador', 'supervisor'])) {
            show_error('Acesso negado', 403);
        }
    }
}