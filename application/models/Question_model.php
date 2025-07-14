<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_by_questionnaire($questionnaire_id) {
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->order_by('order_index', 'ASC');
        $questions = $this->db->get('questions')->result();
        
        // Adicionar opções para cada pergunta
        foreach ($questions as &$question) {
            $question->options = $this->get_options($question->id);
        }
        
        return $questions;
    }

    public function get_by_id($id) {
        $question = $this->db->get_where('questions', array('id' => $id))->row();
        if ($question) {
            $question->options = $this->get_options($id);
        }
        return $question;
    }

    public function get_options($question_id) {
        $this->db->where('question_id', $question_id);
        $this->db->order_by('order_index', 'ASC');
        return $this->db->get('question_options')->result();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('questions', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('questions', $data);
    }

    public function delete($id) {
        // Deletar opções primeiro
        $this->db->where('question_id', $id);
        $this->db->delete('question_options');
        
        // Deletar pergunta
        $this->db->where('id', $id);
        return $this->db->delete('questions');
    }

    public function create_option($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('question_options', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_option($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('question_options', $data);
    }

    public function delete_option($id) {
        $this->db->where('id', $id);
        return $this->db->delete('question_options');
    }

    public function reorder_questions($questionnaire_id, $order_data) {
        foreach ($order_data as $index => $question_id) {
            $this->db->where('id', $question_id);
            $this->db->update('questions', array('order_index' => $index + 1));
        }
        return TRUE;
    }
}