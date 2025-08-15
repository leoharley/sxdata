<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaires extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model');
        $this->load->model('Project_model'); // NOVO: Carregar model de projetos
        $this->load->library('form_validation');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Questionários - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_all_with_stats();
        $data['projects'] = $this->Project_model->get_for_select(); // NOVO: Carregar projetos para filtro
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/questionnaires/index', $data);
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

    // NOVO: Método para filtrar questionários por projeto
    public function by_project($project_id) {
        $project = $this->Project_model->get_by_id($project_id);
        if (!$project) {
            show_404();
        }

        $data['title'] = 'Questionários - ' . $project->name . ' - SXData';
        $data['questionnaires'] = $this->Questionnaire_model->get_by_project($project_id);
        $data['project'] = $project;
        $data['projects'] = $this->Project_model->get_for_select();
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/questionnaires/by_project', $data);
        $this->load->view('admin/footer');
    }

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
            'aplicadores' => $original->aplicadores,
            'project_id' => $original->project_id // NOVO: Copiar projeto também
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

    // NOVO: Método para API que inclui informações do projeto
    public function get_api_data() {
        $user_role = $this->input->get('role');
        $questionnaires = $this->Questionnaire_model->get_for_api($user_role);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $questionnaires
        ]);
    }

    // NOVO: Método para busca AJAX
    public function search() {
        $term = $this->input->get('term');
        $project_id = $this->input->get('project_id');
        
        if (!$term) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Termo de busca é obrigatório']);
            return;
        }
        
        $results = $this->Questionnaire_model->search($term, $project_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }

/**
 * Validar e processar lógica condicional
 */
private function process_conditional_logic($questions) {
    $processed_questions = [];
    
    foreach ($questions as $index => $question) {
        $processed_question = $question;
        
        // Processar lógica condicional se existir
        if (isset($question['conditional_logic']) && !empty($question['conditional_logic'])) {
            $logic = json_decode($question['conditional_logic'], true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Validar a lógica
                $validation = $this->validate_conditional_logic_structure($logic, $index, $questions);
                
                if (!$validation['valid']) {
                    // Se há erros, remover a lógica inválida
                    $processed_question['conditional_logic'] = null;
                    log_message('warning', 'Lógica condicional inválida removida da pergunta ' . ($index + 1) . ': ' . implode(', ', $validation['errors']));
                } else {
                    $processed_question['conditional_logic'] = json_encode($logic);
                }
            } else {
                $processed_question['conditional_logic'] = null;
                log_message('warning', 'JSON inválido na lógica condicional da pergunta ' . ($index + 1));
            }
        } else {
            $processed_question['conditional_logic'] = null;
        }
        
        $processed_questions[] = $processed_question;
    }
    
    return $processed_questions;
}

/**
 * Validar estrutura da lógica condicional
 */
private function validate_conditional_logic_structure($logic, $current_index, $all_questions) {
    $errors = [];
    $warnings = [];
    
    // Validar regras de visibilidade
    if (isset($logic['visibility'])) {
        $validation = $this->validate_rule_structure($logic['visibility'], 'visibility', $current_index, $all_questions);
        $errors = array_merge($errors, $validation['errors']);
        $warnings = array_merge($warnings, $validation['warnings']);
    }
    
    // Validar regras de obrigatoriedade
    if (isset($logic['required'])) {
        $validation = $this->validate_rule_structure($logic['required'], 'required', $current_index, $all_questions);
        $errors = array_merge($errors, $validation['errors']);
        $warnings = array_merge($warnings, $validation['warnings']);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Validar estrutura de uma regra específica
 */
private function validate_rule_structure($rule, $rule_type, $current_index, $all_questions) {
    $errors = [];
    $warnings = [];
    
    // Verificar se tem operador válido
    if (!isset($rule['operator']) || !in_array($rule['operator'], ['AND', 'OR'])) {
        $errors[] = "Operador lógico inválido para regra de {$rule_type}";
    }
    
    // Verificar se tem condições
    if (!isset($rule['conditions']) || !is_array($rule['conditions']) || empty($rule['conditions'])) {
        $errors[] = "Nenhuma condição definida para regra de {$rule_type}";
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    // Validar cada condição
    foreach ($rule['conditions'] as $condition_index => $condition) {
        $condition_validation = $this->validate_condition_structure($condition, $current_index, $all_questions, $condition_index + 1);
        $errors = array_merge($errors, $condition_validation['errors']);
        $warnings = array_merge($warnings, $condition_validation['warnings']);
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

/**
 * Validar estrutura de uma condição
 */
private function validate_condition_structure($condition, $current_index, $all_questions, $condition_number) {
    $errors = [];
    $warnings = [];
    
    // Verificar campos obrigatórios
    if (!isset($condition['question']) || !is_numeric($condition['question'])) {
        $errors[] = "Condição {$condition_number}: Pergunta de referência inválida";
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    if (!isset($condition['operator']) || empty($condition['operator'])) {
        $errors[] = "Condição {$condition_number}: Operador não definido";
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    $target_question_index = intval($condition['question']);
    $operator = $condition['operator'];
    $value = isset($condition['value']) ? $condition['value'] : '';
    
    // Verificar se a pergunta referenciada existe e é anterior
    if ($target_question_index >= $current_index) {
        $errors[] = "Condição {$condition_number}: Não pode referenciar pergunta posterior ou a si mesma";
    }
    
    if (!isset($all_questions[$target_question_index])) {
        $errors[] = "Condição {$condition_number}: Pergunta referenciada não existe";
    }
    
    // Verificar se operador é válido
    $valid_operators = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
    if (!in_array($operator, $valid_operators)) {
        $errors[] = "Condição {$condition_number}: Operador '{$operator}' inválido";
    }
    
    // Verificar valor baseado no operador
    if (!in_array($operator, ['is_empty', 'is_not_empty']) && empty($value)) {
        $warnings[] = "Condição {$condition_number}: Valor não definido para operador '{$operator}'";
    }
    
    // Verificar compatibilidade entre operador e tipo de pergunta
    if (isset($all_questions[$target_question_index])) {
        $target_question = $all_questions[$target_question_index];
        $target_type = isset($target_question['type']) ? $target_question['type'] : 'text';
        
        if (in_array($operator, ['greater_than', 'less_than']) && $target_type !== 'number') {
            $warnings[] = "Condição {$condition_number}: Operador numérico usado em pergunta não numérica";
        }
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

/**
 * Método modificado para create - adicionar processamento de lógica condicional
 */
public function create() {
    // Verificar se veio um project_id via GET
    $preselected_project_id = $this->input->get('project_id');
    
    if ($this->input->post()) {
        $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
        $this->form_validation->set_rules('description', 'Descrição', 'max_length[1000]');

        if ($this->form_validation->run()) {
            // Processar aplicadores selecionados
            $aplicadores = $this->input->post('aplicadores');
            $aplicadores_json = null;
            
            if ($aplicadores && is_array($aplicadores)) {
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
                'aplicadores' => $aplicadores_json,
                'project_id' => $this->input->post('project_id') ?: NULL
            );

            $questionnaire_id = $this->Questionnaire_model->create($questionnaire_data);

            if ($questionnaire_id) {
                // Processar perguntas com lógica condicional
                $questions = $this->input->post('questions');
                if ($questions) {
                    $processed_questions = $this->process_conditional_logic($questions);
                    
                    foreach ($processed_questions as $index => $question) {
                        $question_data = array(
                            'questionnaire_id' => $questionnaire_id,
                            'question_text' => $question['text'],
                            'question_type' => $question['type'],
                            'is_required' => isset($question['required']) ? TRUE : FALSE,
                            'order_index' => $index + 1,
                            'conditional_logic' => $question['conditional_logic']
                        );

                        $question_id = $this->Question_model->create($question_data);

                        // Salvar opções se for múltipla escolha
                        if (in_array($question['type'], ['radio', 'checkbox', 'select']) && isset($question['options'])) {
                            foreach ($question['options'] as $opt_index => $option) {
                                if (!empty(trim($option['text']))) {
                                    $this->Question_model->create_option(array(
                                        'question_id' => $question_id,
                                        'option_text' => trim($option['text']),
                                        'option_value' => !empty($option['value']) ? $option['value'] : strtolower(str_replace(' ', '_', trim($option['text']))),
                                        'order_index' => $opt_index + 1
                                    ));
                                }
                            }
                        }
                    }
                }

                $this->session->set_flashdata('success', 'Questionário criado com sucesso!');
                
                if ($this->input->post('project_id')) {
                    redirect('projects/view/' . $this->input->post('project_id'));
                } else {
                    redirect('questionnaires');
                }
            } else {
                $data['error'] = 'Erro ao criar questionário.';
            }
        }
    }

    $data['title'] = 'Criar Questionário - SXData';
    $data['aplicadores'] = $this->User_model->get_aplicadores();
    $data['projects'] = $this->Project_model->get_for_select();
    $data['preselected_project_id'] = $preselected_project_id;
    
    $this->load->view('admin/header', $data);
    $this->load->view('admin/questionnaires/create', $data);
    $this->load->view('admin/footer');
}

/**
 * Método modificado para edit - adicionar processamento de lógica condicional
 */
public function edit($id) {
    $questionnaire = $this->Questionnaire_model->get_by_id($id);
    if (!$questionnaire) {
        show_404();
    }

    // Garantir que os valores sejam tratados como boolean
    $questionnaire->requires_consent = $this->_convert_to_boolean($questionnaire->requires_consent);
    $questionnaire->requires_location = $this->_convert_to_boolean($questionnaire->requires_location);
    $questionnaire->requires_photo = $this->_convert_to_boolean($questionnaire->requires_photo);

    if ($this->input->post()) {
        $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
        $this->form_validation->set_rules('description', 'Descrição', 'max_length[1000]');

        if ($this->form_validation->run()) {
            // ... resto do código de processamento do POST ...
        }
    }

    $data['title'] = 'Editar Questionário - SXData';
    $data['questionnaire'] = $questionnaire;
    
    // CORREÇÃO: Usar método compatível com diferentes bancos de dados
    try {
        $data['questions'] = $this->Question_model->get_by_questionnaire_with_logic($id);
    } catch (Exception $e) {
        log_message('error', 'Erro ao buscar perguntas com lógica: ' . $e->getMessage());
        // Fallback para método básico
        $data['questions'] = $this->Question_model->get_by_questionnaire($id);
        
        // Adicionar opções manualmente para cada pergunta
        foreach ($data['questions'] as &$question) {
            $question->options = $this->Question_model->get_options($question->id);
            $question->conditional_logic_decoded = null;
            
            if (!empty($question->conditional_logic)) {
                $logic = json_decode($question->conditional_logic, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $question->conditional_logic_decoded = $logic;
                }
            }
        }
    }
    
    $data['aplicadores'] = $this->User_model->get_aplicadores();
    $data['projects'] = $this->Project_model->get_for_select();
    
    // Decodificar aplicadores selecionados
    $data['aplicadores_selecionados'] = array();
    if ($questionnaire->aplicadores) {
        $data['aplicadores_selecionados'] = json_decode($questionnaire->aplicadores, true) ?: array();
    }
    
    $this->load->view('admin/header', $data);
    $this->load->view('admin/questionnaires/edit', $data);
    $this->load->view('admin/footer');
}

/**
 * Helper method para exibir resumo da lógica condicional
 * Adicionar este método ao controller
 */
public function display_conditional_logic_summary($conditional_logic_json) {
    if (empty($conditional_logic_json)) {
        return '';
    }
    
    $logic = json_decode($conditional_logic_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<span class="text-danger">Lógica inválida</span>';
    }
    
    $summary = array();
    
    if (isset($logic['visibility']) && !empty($logic['visibility']['conditions'])) {
        $visibility_text = $this->format_conditions_summary($logic['visibility']);
        $summary[] = '<strong>Visibilidade:</strong> ' . $visibility_text;
    }
    
    if (isset($logic['required']) && !empty($logic['required']['conditions'])) {
        $required_text = $this->format_conditions_summary($logic['required']);
        $summary[] = '<strong>Obrigatória:</strong> ' . $required_text;
    }
    
    return implode('<br>', $summary);
}

/**
 * Helper method para formatar resumo das condições
 */
private function format_conditions_summary($rule) {
    if (empty($rule['conditions'])) {
        return '';
    }
    
    $operator = isset($rule['operator']) ? $rule['operator'] : 'AND';
    $operator_text = $operator === 'AND' ? ' E ' : ' OU ';
    
    $condition_texts = array();
    
    foreach ($rule['conditions'] as $condition) {
        $question_ref = 'Pergunta ' . ($condition['question'] + 1);
        $operator_map = array(
            'equals' => 'igual a',
            'not_equals' => 'diferente de',
            'contains' => 'contém',
            'not_contains' => 'não contém',
            'greater_than' => 'maior que',
            'less_than' => 'menor que',
            'is_empty' => 'está vazio',
            'is_not_empty' => 'não está vazio'
        );
        
        $operator_text_condition = isset($operator_map[$condition['operator']]) ? 
                                   $operator_map[$condition['operator']] : 
                                   $condition['operator'];
        
        $condition_text = $question_ref . ' ' . $operator_text_condition;
        
        if (!in_array($condition['operator'], ['is_empty', 'is_not_empty']) && !empty($condition['value'])) {
            $condition_text .= ' "' . $condition['value'] . '"';
        }
        
        $condition_texts[] = $condition_text;
    }
    
    if (count($condition_texts) === 0) return '';
    
    return implode($operator_text, $condition_texts);
}

/**
 * Método para testar lógica condicional
 */
public function test_conditional_logic($questionnaire_id) {
    $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
    if (!$questionnaire) {
        show_404();
    }
    
    $questions = $this->Question_model->get_by_questionnaire_with_logic($questionnaire_id);
    
    // Se é POST, processar respostas de teste
    if ($this->input->post()) {
        $test_responses = $this->input->post('responses', array());
        
        try {
            $question_states = $this->Questionnaire_model->execute_conditional_logic($questions, $test_responses);
            $validation = $this->Questionnaire_model->validate_responses_with_conditional_logic($questions, $test_responses);
            
            $data['test_results'] = array(
                'responses' => $test_responses,
                'states' => $question_states,
                'validation' => $validation
            );
        } catch (Exception $e) {
            $data['test_error'] = 'Erro ao executar lógica condicional: ' . $e->getMessage();
        }
    }
    
    $data['title'] = 'Testar Lógica Condicional - ' . $questionnaire->title;
    $data['questionnaire'] = $questionnaire;
    $data['questions'] = $questions;
    
    $this->load->view('admin/header', $data);
    $this->load->view('admin/questionnaires/test_logic', $data);
    $this->load->view('admin/footer');
}

/**
 * Método para exportar questionário com lógica condicional
 */
public function export_with_logic($questionnaire_id) {
    $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
    if (!$questionnaire) {
        show_404();
    }
    
    $questions = $this->Question_model->get_by_questionnaire_with_logic($questionnaire_id);
    
    $export_data = array(
        'questionnaire' => $questionnaire,
        'questions' => array()
    );
    
    foreach ($questions as $question) {
        $question_data = array(
            'id' => $question->id,
            'text' => $question->question_text,
            'type' => $question->question_type,
            'required' => (bool)$question->is_required,
            'order' => $question->order_index,
            'conditional_logic' => $question->conditional_logic ? json_decode($question->conditional_logic, true) : null,
            'options' => $question->options ?: array()
        );
        
        $export_data['questions'][] = $question_data;
    }
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="questionnaire_' . $questionnaire_id . '_with_logic.json"');
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Método para importar questionário com lógica condicional
 */
public function import_with_logic() {
    if ($this->input->post() && isset($_FILES['import_file'])) {
        $file = $_FILES['import_file'];
        
        if ($file['type'] !== 'application/json') {
            $this->session->set_flashdata('error', 'Arquivo deve ser do tipo JSON.');
            redirect('questionnaires');
        }
        
        $content = file_get_contents($file['tmp_name']);
        $import_data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->session->set_flashdata('error', 'Arquivo JSON inválido.');
            redirect('questionnaires');
        }
        
        // Validar estrutura do arquivo
        if (!isset($import_data['questionnaire']) || !isset($import_data['questions'])) {
            $this->session->set_flashdata('error', 'Estrutura do arquivo inválida.');
            redirect('questionnaires');
        }
        
        try {
            $this->db->trans_start();
            
            // Criar questionário
            $questionnaire_data = array(
                'title' => $import_data['questionnaire']->title . ' (Importado)',
                'description' => $import_data['questionnaire']->description,
                'created_by' => $this->session->userdata('admin_id'),
                'requires_consent' => $import_data['questionnaire']->requires_consent ?: FALSE,
                'requires_location' => $import_data['questionnaire']->requires_location ?: FALSE,
                'requires_photo' => $import_data['questionnaire']->requires_photo ?: FALSE,
                'estimated_time' => $import_data['questionnaire']->estimated_time,
                'project_id' => $this->input->post('target_project_id') ?: NULL
            );
            
            $new_questionnaire_id = $this->Questionnaire_model->create($questionnaire_data);
            
            if ($new_questionnaire_id) {
                // Criar perguntas
                foreach ($import_data['questions'] as $question_data) {
                    $new_question_data = array(
                        'questionnaire_id' => $new_questionnaire_id,
                        'question_text' => $question_data['text'],
                        'question_type' => $question_data['type'],
                        'is_required' => $question_data['required'] ?: FALSE,
                        'order_index' => $question_data['order'],
                        'conditional_logic' => $question_data['conditional_logic'] ? json_encode($question_data['conditional_logic']) : NULL
                    );
                    
                    $new_question_id = $this->Question_model->create($new_question_data);
                    
                    // Criar opções se existirem
                    if (!empty($question_data['options'])) {
                        foreach ($question_data['options'] as $option_index => $option) {
                            $this->Question_model->create_option(array(
                                'question_id' => $new_question_id,
                                'option_text' => $option['option_text'],
                                'option_value' => $option['option_value'],
                                'order_index' => $option_index + 1
                            ));
                        }
                    }
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    $this->session->set_flashdata('error', 'Erro ao importar questionário.');
                } else {
                    $this->session->set_flashdata('success', 'Questionário importado com sucesso!');
                    redirect('questionnaires/edit/' . $new_questionnaire_id);
                }
            }
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Erro ao importar: ' . $e->getMessage());
        }
    }
    
    redirect('questionnaires');
}

}