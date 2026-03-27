<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Ai_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->check_auth();
    }

    public function dashboard() {
        $data['title'] = 'Painel de Análises - SXData';
        $user_id = $this->session->userdata('admin_id');
        $role = $this->session->userdata('admin_role');

        if ($role === 'cliente') {
            // Filtrar apenas análises de projetos aos quais o cliente tem acesso
            $project_ids = $this->_get_client_project_ids($user_id);
            $data['analyses'] = $this->_get_analyses_for_projects($project_ids);
        } else {
            $data['analyses'] = $this->Ai_model->get_statistical_analyses();
        }

        $this->load->view('admin/header', $data);
        $this->load->view('client/dashboard', $data);
        $this->load->view('admin/footer');
    }

    public function view_analysis($id) {
        $data['title'] = 'Análise - SXData';
        $analysis = $this->Ai_model->get_statistical_analysis($id);

        if (!$analysis || !$this->_client_can_view_analysis($analysis)) {
            $this->session->set_flashdata('error', 'Análise não encontrada ou sem permissão.');
            redirect('client/dashboard');
            return;
        }

        $data['analysis'] = $analysis;

        // Carregar perguntas do questionário para resolver q_XXX
        $questions_map = array();
        if (!empty($analysis->questionnaire_id)) {
            $questions = $this->Question_model->get_by_questionnaire($analysis->questionnaire_id);
            foreach ($questions as $q) {
                $questions_map[$q->id] = $q;
            }
        }
        $data['questions_map'] = $questions_map;

        $this->load->view('admin/header', $data);
        $this->load->view('client/analysis_view', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Endpoint AJAX para gerar descrição explicativa de um gráfico
     */
    public function generate_chart_description() {
        header('Content-Type: application/json');

        $chart_title = $this->input->post('chart_title');
        $chart_data = $this->input->post('chart_data');
        $questionnaire_id = $this->input->post('questionnaire_id');

        if (empty($chart_title) || empty($chart_data)) {
            echo json_encode(array('success' => false, 'message' => 'Dados insuficientes.'));
            return;
        }

        // Resolver nomes das perguntas referenciadas no título
        $question_context = '';
        if (preg_match_all('/q_(\d+)/', $chart_title, $matches)) {
            foreach ($matches[1] as $qid) {
                $question = $this->Question_model->get_by_id($qid);
                if ($question) {
                    $question_context .= "q_{$qid} = \"{$question->question_text}\"\n";
                }
            }
        }

        // Montar prompt para gerar descrição
        $this->load->library('ai_service');

        $messages = array(
            array(
                'role' => 'system',
                'content' => 'Você é um analista de dados que explica gráficos de forma clara e acessível para clientes. '
                    . 'Gere uma descrição explicativa curta (2-4 frases) do gráfico, explicando o que ele mostra, '
                    . 'qual a distribuição dos dados e o que pode significar. Responda APENAS com o texto da descrição, sem formatação especial. '
                    . 'Responda em português brasileiro.'
            ),
            array(
                'role' => 'user',
                'content' => "Título do gráfico: {$chart_title}\n\n"
                    . (!empty($question_context) ? "Perguntas referenciadas:\n{$question_context}\n" : '')
                    . "Dados do gráfico (JSON): {$chart_data}\n\n"
                    . "Gere uma descrição explicativa para este gráfico."
            ),
        );

        $result = $this->ai_service->chat_completion('statistical_analysis', $messages, array(
            'resource_type' => 'chart_description',
            'resource_id' => $questionnaire_id,
        ));

        if ($result['success'] && !empty($result['content'])) {
            echo json_encode(array('success' => true, 'description' => $result['content']));
        } else {
            echo json_encode(array('success' => false, 'message' => $result['error'] ?? 'Erro ao gerar descrição.'));
        }
    }

    private function _get_client_project_ids($user_id) {
        $rows = $this->db->select('project_id')
                         ->where('user_id', $user_id)
                         ->get('project_clients')
                         ->result();
        return array_map(function($r) { return (int) $r->project_id; }, $rows);
    }

    private function _get_analyses_for_projects($project_ids) {
        if (empty($project_ids)) return array();

        // Buscar questionnaire_ids dos projetos
        $questionnaire_ids = $this->db->select('id')
                                      ->where_in('project_id', $project_ids)
                                      ->get('questionnaires')
                                      ->result();
        $qids = array_map(function($r) { return (int) $r->id; }, $questionnaire_ids);
        if (empty($qids)) return array();

        // Buscar análises desses questionários
        $this->db->select('ai_statistical_analyses.*, q.title as questionnaire_title');
        $this->db->join('questionnaires q', 'q.id = ai_statistical_analyses.questionnaire_id', 'left');
        $this->db->where_in('ai_statistical_analyses.questionnaire_id', $qids);
        $this->db->order_by('ai_statistical_analyses.created_at', 'DESC');
        return $this->db->get('ai_statistical_analyses')->result_array();
    }

    private function _client_can_view_analysis($analysis) {
        $role = $this->session->userdata('admin_role');
        if ($role !== 'cliente') return true;

        $user_id = $this->session->userdata('admin_id');
        $project_ids = $this->_get_client_project_ids($user_id);
        if (empty($project_ids)) return false;

        $qid = $analysis->questionnaire_id;
        $q = $this->db->select('project_id')->where('id', $qid)->get('questionnaires')->row();
        return $q && in_array((int) $q->project_id, $project_ids);
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}
