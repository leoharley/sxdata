<?php
// application/models/User_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('users')->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where('users', array('id' => $id))->row();
    }

    public function get_aplicadores() {
        $this->db->where('role', 'aplicador');
        $this->db->where('is_active', TRUE);
        $this->db->order_by('full_name', 'ASC');
        return $this->db->get('users')->result();
    }

    public function authenticate($username, $password) {
        $user = $this->db->get_where('users', array(
            'username' => $username,
            'is_active' => TRUE
        ))->row();

        if ($user && password_verify($password, $user->password_hash)) {
            return $user;
        }
        return FALSE;
    }

    public function get_all_with_stats() {
        $this->db->select('
            u.*,
            COUNT(fr.id) as total_responses,
            MAX(fr.completed_at) as last_response_date
        ');
        $this->db->from('users u');
        $this->db->join('form_responses fr', 'u.id = fr.applied_by', 'left');
        $this->db->group_by('u.id');
        $this->db->order_by('u.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    public function create($data) {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('users', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('users');
    }

    public function count_all() {
        return $this->db->count_all('users');
    }

    public function get_stats() {
        $stats = array();
        
        // Total por role
        $this->db->select('role, COUNT(*) as count');
        $this->db->group_by('role');
        $roles = $this->db->get('users')->result();
        
        foreach ($roles as $role) {
            $stats['by_role'][$role->role] = $role->count;
        }
        
        // Ativos vs Inativos
        $this->db->select('is_active, COUNT(*) as count');
        $this->db->group_by('is_active');
        $active = $this->db->get('users')->result();
        
        foreach ($active as $status) {
            $stats['by_status'][$status->is_active ? 'active' : 'inactive'] = $status->count;
        }
        
        return $stats;
    }
}