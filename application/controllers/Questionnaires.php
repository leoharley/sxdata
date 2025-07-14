<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaires extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model'); // Adicionar esta linha
        $this->load->library('form_validation');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Questionários - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_all_with_stats();
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/questionnaires/index', $data);
        $this->load->view('admin/footer');
    }

    public function create() {
        if ($this->input->post()) {
            $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
            $this->form_validation->set_rules('description', 'Descrição', 'max_length[1000]');

            if ($this->form_validation->run()) {
                // Processar aplicadores selecionados
                $aplicadores = $this->input->post('aplicadores');
                $aplicadores_json = null;
                
                if ($aplicadores && is_array($aplicadores)) {
                    // Se "todos" foi selecionado, buscar todos os aplicadores
                    if (in_array('all', $aplicadores)) {
                        $all_aplicadores = $this->User_model->get_aplicadores();
                        $aplicadores = array_column($all_aplicadores, 'id');
                    }
                    $aplicadores_json = json_encode(array_map('intval', $aplicadores));
                }

                $questionnaire_data = array(
                    'title' => $this->input->post('title'),
                    'description' => $this->input->post('description'),
                    'created_by' => $this->session->userdata('admin_id'),
                    'requires_consent' => $this->input->post('requires_consent') ? TRUE : FALSE,
                    'requires_location' => $this->input->post('requires_location') ? TRUE : FALSE,
                    'requires_photo' => $this->input->post('requires_photo') ? TRUE : FALSE,
                    'estimated_time' => $this->input->post('estimated_time') ?: NULL,
                    'aplicadores' => $aplicadores_json // Novo campo
                );

                $questionnaire_id = $this->Questionnaire_model->create($questionnaire_data);

                if ($questionnaire_id) {
                    // Salvar perguntas (código existente)
                    $questions = $this->input->post('questions');
                    if ($questions) {
                        foreach ($questions as $index => $question) {
                            $question_data = array(
                                'questionnaire_id' => $questionnaire_id,
                                'question_text' => $question['text'],
                                'question_type' => $question['type'],
                                'is_required' => isset($question['required']) ? TRUE : FALSE,
                                'order_index' => $index + 1,
                                'conditional_logic' => isset($question['conditional_logic']) ? json_encode($question['conditional_logic']) : NULL
                            );

                            $question_id = $this->Question_model->create($question_data);

                            // Salvar opções se for múltipla escolha
                            if (in_array($question['type'], ['radio', 'checkbox']) && isset($question['options'])) {
                                foreach ($question['options'] as $opt_index => $option) {
                                    $this->Question_model->create_option(array(
                                        'question_id' => $question_id,
                                        'option_text' => $option['text'],
                                        'option_value' => $option['value'],
                                        'order_index' => $opt_index + 1
                                    ));
                                }
                            }
                        }
                    }

                    $this->session->set_flashdata('success', 'Questionário criado com sucesso!');
                    redirect('questionnaires');
                } else {
                    $data['error'] = 'Erro ao criar questionário.';
                }
            }
        }

        $data['title'] = 'Criar Questionário - SXData';
        $data['aplicadores'] = $this->User_model->get_aplicadores(); // Carregar aplicadores
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/questionnaires/create', $data);
        $this->load->view('admin/footer');
    }

    public function edit($id) {
        $questionnaire = $this->Questionnaire_model->get_by_id($id);
        if (!$questionnaire) {
            show_404();
        }

        // Debug: Log dos valores que vêm do banco (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Questionnaire data from DB: ' . json_encode($questionnaire));
            log_message('debug', 'requires_consent type: ' . gettype($questionnaire->requires_consent) . ', value: ' . var_export($questionnaire->requires_consent, true));
            log_message('debug', 'requires_location type: ' . gettype($questionnaire->requires_location) . ', value: ' . var_export($questionnaire->requires_location, true));
            log_message('debug', 'requires_photo type: ' . gettype($questionnaire->requires_photo) . ', value: ' . var_export($questionnaire->requires_photo, true));
        }

        // CORREÇÃO: Garantir que os valores sejam tratados como boolean
        $questionnaire->requires_consent = $this->_convert_to_boolean($questionnaire->requires_consent);
        $questionnaire->requires_location = $this->_convert_to_boolean($questionnaire->requires_location);
        $questionnaire->requires_photo = $this->_convert_to_boolean($questionnaire->requires_photo);

        if ($this->input->post()) {
            $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
            $this->form_validation->set_rules('description', 'Descrição', 'max_length[1000]');

            if ($this->form_validation->run()) {
                // Processar aplicadores selecionados
                $aplicadores = $this->input->post('aplicadores');
                $aplicadores_json = null;
                
                if ($aplicadores && is_array($aplicadores)) {
                    // Se "todos" foi selecionado, buscar todos os aplicadores
                    if (in_array('all', $aplicadores)) {
                        $all_aplicadores = $this->User_model->get_aplicadores();
                        $aplicadores = array_column($all_aplicadores, 'id');
                    }
                    $aplicadores_json = json_encode(array_map('intval', $aplicadores));
                }

                $questionnaire_data = array(
                    'title' => $this->input->post('title'),
                    'description' => $this->input->post('description'),
                    'status' => $this->input->post('status'),
                    'requires_consent' => $this->input->post('requires_consent') ? TRUE : FALSE,
                    'requires_location' => $this->input->post('requires_location') ? TRUE : FALSE,
                    'requires_photo' => $this->input->post('requires_photo') ? TRUE : FALSE,
                    'estimated_time' => $this->input->post('estimated_time') ?: NULL,
                    'aplicadores' => $aplicadores_json // Novo campo
                );

                if ($this->Questionnaire_model->update($id, $questionnaire_data)) {
                    $this->session->set_flashdata('success', 'Questionário atualizado com sucesso!');
                    redirect('questionnaires');
                } else {
                    $data['error'] = 'Erro ao atualizar questionário.';
                }
            }
        }

        $data['title'] = 'Editar Questionário - SXData';
        $data['questionnaire'] = $questionnaire;
        $data['questions'] = $this->Question_model->get_by_questionnaire($id);
        $data['aplicadores'] = $this->User_model->get_aplicadores(); // Carregar aplicadores
        
        // Decodificar aplicadores selecionados
        $data['aplicadores_selecionados'] = array();
        if ($questionnaire->aplicadores) {
            $data['aplicadores_selecionados'] = json_decode($questionnaire->aplicadores, true) ?: array();
        }

        // Debug: Log dos dados que serão enviados para a view (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Data being sent to view: ');
            log_message('debug', 'requires_consent: ' . var_export($data['questionnaire']->requires_consent, true));
            log_message('debug', 'requires_location: ' . var_export($data['questionnaire']->requires_location, true));
            log_message('debug', 'requires_photo: ' . var_export($data['questionnaire']->requires_photo, true));
        }
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/questionnaires/edit', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Converte diversos tipos de valor para boolean
     * 
     * @param mixed $value Valor a ser convertido
     * @return bool Valor boolean
     */
    private function _convert_to_boolean($value) {
        // Se já é boolean, retorna como está
        if (is_bool($value)) {
            return $value;
        }
        
        // Se é numeric
        if (is_numeric($value)) {
            return (bool) intval($value);
        }
        
        // Se é string
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on', 't', 'y']);
        }
        
        // Para null ou outros valores, retorna false
        return false;
    }

    // Resto dos métodos permanecem iguais...
    public function duplicate($id) {
        $original = $this->Questionnaire_model->get_by_id($id);
        if (!$original) {
            show_404();
        }

        // Converter valores para boolean antes de duplicar
        $original->requires_consent = $this->_convert_to_boolean($original->requires_consent);
        $original->requires_location = $this->_convert_to_boolean($original->requires_location);
        $original->requires_photo = $this->_convert_to_boolean($original->requires_photo);

        $new_data = array(
            'title' => $original->title . ' (Cópia)',
            'description' => $original->description,
            'created_by' => $this->session->userdata('admin_id'),
            'requires_consent' => $original->requires_consent,
            'requires_location' => $original->requires_location,
            'requires_photo' => $original->requires_photo,
            'estimated_time' => $original->estimated_time,
            'aplicadores' => $original->aplicadores // Copiar aplicadores também
        );

        $new_id = $this->Questionnaire_model->create($new_data);

        if ($new_id) {
            // Copiar perguntas (código existente)
            $questions = $this->Question_model->get_by_questionnaire($id);
            foreach ($questions as $question) {
                $question_data = array(
                    'questionnaire_id' => $new_id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'is_required' => $question->is_required,
                    'order_index' => $question->order_index,
                    'conditional_logic' => $question->conditional_logic
                );

                $new_question_id = $this->Question_model->create($question_data);

                // Copiar opções
                $options = $this->Question_model->get_options($question->id);
                foreach ($options as $option) {
                    $this->Question_model->create_option(array(
                        'question_id' => $new_question_id,
                        'option_text' => $option->option_text,
                        'option_value' => $option->option_value,
                        'order_index' => $option->order_index
                    ));
                }
            }

            $this->session->set_flashdata('success', 'Questionário duplicado com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Erro ao duplicar questionário.');
        }

        redirect('questionnaires');
    }

    public function delete($id) {
        if ($this->Questionnaire_model->delete($id)) {
            $this->session->set_flashdata('success', 'Questionário excluído com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Erro ao excluir questionário.');
        }
        redirect('questionnaires');
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}