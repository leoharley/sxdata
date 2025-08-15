<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Obter perguntas de um questionário com suas opções
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções
     */
    public function get_by_questionnaire($questionnaire_id) {
        $this->db->select('*');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->order_by('order_index', 'ASC');
        $questions = $this->db->get()->result();
        
        // Para cada pergunta, buscar suas opções
        foreach ($questions as $question) {
            $question->options = $this->get_options($question->id);
        }
        
        return $questions;
    }

    /**
     * Obter pergunta por ID com suas opções
     * 
     * @param int $id ID da pergunta
     * @return object|null Pergunta com opções
     */
    public function get_by_id($id) {
        $question = $this->db->get_where('questions', array('id' => $id))->row();
        if ($question) {
            $question->options = $this->get_options($id);
        }
        return $question;
    }

    /**
     * Obter opções de uma pergunta
     * 
     * @param int $question_id ID da pergunta
     * @return array Array de opções
     */
    public function get_options($question_id) {
        $this->db->select('*');
        $this->db->from('question_options');
        $this->db->where('question_id', $question_id);
        $this->db->order_by('order_index', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Criar nova pergunta
     * 
     * @param array $data Dados da pergunta
     * @return int|false ID da pergunta criada ou false
     */
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('questions', $data) ? $this->db->insert_id() : FALSE;
    }

    /**
     * Atualizar pergunta
     * 
     * @param int $question_id ID da pergunta
     * @param array $data Dados para atualização
     * @return bool Sucesso da operação
     */
    public function update($question_id, $data) {
        $this->db->where('id', $question_id);
        return $this->db->update('questions', $data);
    }

    /**
     * Excluir pergunta e suas opções
     * 
     * @param int $question_id ID da pergunta
     * @return bool Sucesso da operação
     */
    public function delete($question_id) {
        // Primeiro excluir as opções
        $this->delete_options($question_id);
        
        // Depois excluir a pergunta
        $this->db->where('id', $question_id);
        return $this->db->delete('questions');
    }

    /**
     * Criar nova opção de pergunta
     * 
     * @param array $data Dados da opção
     * @return int|false ID da opção criada ou false
     */
    public function create_option($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('question_options', $data) ? $this->db->insert_id() : FALSE;
    }

    /**
     * Atualizar opção
     * 
     * @param int $option_id ID da opção
     * @param array $data Dados para atualização
     * @return bool Sucesso da operação
     */
    public function update_option($option_id, $data) {
        $this->db->where('id', $option_id);
        return $this->db->update('question_options', $data);
    }

    /**
     * Excluir opção específica
     * 
     * @param int $option_id ID da opção
     * @return bool Sucesso da operação
     */
    public function delete_option($option_id) {
        $this->db->where('id', $option_id);
        return $this->db->delete('question_options');
    }

    /**
     * Excluir todas as opções de uma pergunta
     * 
     * @param int $question_id ID da pergunta
     * @return bool Sucesso da operação
     */
    public function delete_options($question_id) {
        $this->db->where('question_id', $question_id);
        return $this->db->delete('question_options');
    }

    /**
     * Reordenar perguntas de um questionário
     * 
     * @param int $questionnaire_id ID do questionário
     * @param array $question_orders Array com ID da pergunta e nova ordem
     * @return bool Sucesso da operação
     */
    public function reorder_questions($questionnaire_id, $question_orders) {
        $this->db->trans_start();
        
        foreach ($question_orders as $question_id => $order) {
            $this->db->where('id', $question_id);
            $this->db->where('questionnaire_id', $questionnaire_id);
            $this->db->update('questions', ['order_index' => $order]);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Duplicar pergunta com suas opções
     * 
     * @param int $question_id ID da pergunta original
     * @param int $new_questionnaire_id ID do novo questionário
     * @return int|false ID da nova pergunta ou false
     */
    public function duplicate_question($question_id, $new_questionnaire_id) {
        // Buscar pergunta original
        $original_question = $this->db->get_where('questions', ['id' => $question_id])->row();
        
        if (!$original_question) {
            return false;
        }
        
        // Criar nova pergunta
        $new_question_data = [
            'questionnaire_id' => $new_questionnaire_id,
            'question_text' => $original_question->question_text,
            'question_type' => $original_question->question_type,
            'is_required' => $original_question->is_required,
            'order_index' => $original_question->order_index,
            'conditional_logic' => $original_question->conditional_logic
        ];
        
        $new_question_id = $this->create($new_question_data);
        
        if ($new_question_id) {
            // Duplicar opções se existirem
            $options = $this->get_options($question_id);
            foreach ($options as $option) {
                $this->create_option([
                    'question_id' => $new_question_id,
                    'option_text' => $option->option_text,
                    'option_value' => $option->option_value,
                    'order_index' => $option->order_index
                ]);
            }
        }
        
        return $new_question_id;
    }

    /**
     * Validar estrutura de pergunta
     * 
     * @param array $question_data Dados da pergunta
     * @return array Array com status e mensagens de erro
     */
    public function validate_question($question_data) {
        $errors = [];
        
        // Validar texto da pergunta
        if (empty(trim($question_data['question_text']))) {
            $errors[] = 'O texto da pergunta é obrigatório.';
        }
        
        // Validar tipo
        $allowed_types = ['text', 'textarea', 'number', 'radio', 'checkbox', 'select', 'date', 'time', 'email', 'phone'];
        if (!in_array($question_data['question_type'], $allowed_types)) {
            $errors[] = 'Tipo de pergunta inválido.';
        }
        
        // Validar se perguntas de múltipla escolha têm opções
        if (in_array($question_data['question_type'], ['radio', 'checkbox', 'select'])) {
            if (empty($question_data['options']) || !is_array($question_data['options'])) {
                $errors[] = 'Perguntas de múltipla escolha devem ter pelo menos uma opção.';
            } else {
                $valid_options = 0;
                foreach ($question_data['options'] as $option) {
                    if (!empty(trim($option['option_text']))) {
                        $valid_options++;
                    }
                }
                if ($valid_options === 0) {
                    $errors[] = 'Pelo menos uma opção deve ter texto válido.';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obter estatísticas de perguntas por tipo
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Estatísticas
     */
    public function get_question_stats($questionnaire_id) {
        $this->db->select('question_type, COUNT(*) as count');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->group_by('question_type');
        $stats = $this->db->get()->result();
        
        $formatted_stats = [];
        foreach ($stats as $stat) {
            $formatted_stats[$stat->question_type] = $stat->count;
        }
        
        return $formatted_stats;
    }
}