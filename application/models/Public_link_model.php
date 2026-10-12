<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Links públicos de questionário (coleta por QR Code).
 *
 * Tabelas em sql/public_links_migration.sql.
 */
class Public_link_model extends CI_Model {

    /** Envios aceitos por endereço de rede, por link, por hora */
    const MAX_SUBMISSIONS_PER_HOUR = 30;

    public function __construct() {
        parent::__construct();
    }

    public function get_by_token($token) {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $this->db->select('l.*, q.title AS questionnaire_title, q.description AS questionnaire_description,
                           q.status AS questionnaire_status, q.requires_consent');
        $this->db->from('questionnaire_public_links l');
        $this->db->join('questionnaires q', 'q.id = l.questionnaire_id', 'inner');
        $this->db->where('l.token', $token);

        return $this->db->get()->row();
    }

    public function get_by_id($id) {
        return $this->db->get_where('questionnaire_public_links', ['id' => (int) $id])->row();
    }

    /**
     * Links de um questionário, com a quantidade de respostas de cada um.
     */
    public function get_for_questionnaire($questionnaire_id) {
        $this->db->select('l.*, u.full_name AS created_by_name,
                           (SELECT COUNT(*) FROM form_responses fr WHERE fr.public_link_id = l.id) AS response_count', FALSE);
        $this->db->from('questionnaire_public_links l');
        $this->db->join('users u', 'u.id = l.created_by', 'left');
        $this->db->where('l.questionnaire_id', (int) $questionnaire_id);
        $this->db->order_by('l.created_at', 'DESC');

        return $this->db->get()->result();
    }

    public function create($questionnaire_id, $label, $expires_at, $user_id) {
        $data = [
            'questionnaire_id' => (int) $questionnaire_id,
            // 128 bits aleatórios: impossível de adivinhar a partir de outro link
            'token'            => bin2hex(random_bytes(16)),
            'label'            => $label !== '' ? $label : null,
            'is_active'        => TRUE,
            'expires_at'       => $expires_at,
            'created_by'       => $user_id ? (int) $user_id : null,
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        return $this->db->insert('questionnaire_public_links', $data) ? $this->db->insert_id() : FALSE;
    }

    public function set_active($id, $active) {
        return $this->db->where('id', (int) $id)->update('questionnaire_public_links', [
            'is_active'      => (bool) $active,
            'deactivated_at' => $active ? null : date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Motivo pelo qual o link não aceita respostas, ou NULL se aceita.
     *
     * O link só funciona com o questionário ativo: pausar o questionário
     * encerra a coleta no app e também pelo QR Code.
     */
    public function unavailable_reason($link) {
        if (!$link) {
            return 'Este link de pesquisa não existe ou foi digitado incorretamente.';
        }
        if (!$this->truthy($link->is_active)) {
            return 'Esta pesquisa foi encerrada e não está mais recebendo respostas.';
        }
        if (!empty($link->expires_at) && strtotime($link->expires_at) < time()) {
            return 'O prazo para responder a esta pesquisa terminou.';
        }
        if ($link->questionnaire_status !== 'active') {
            return 'Esta pesquisa não está recebendo respostas no momento.';
        }

        return null;
    }

    // ------------------------------------------------------------
    // Limite de envios
    // ------------------------------------------------------------

    public function ip_hash($ip) {
        // Com sal: o hash de um IP não pode ser revertido por tabela pronta
        return hash('sha256', 'sxdata-public|' . $ip . '|' . APPPATH);
    }

    public function is_rate_limited($link_id, $ip_hash) {
        $this->db->where('link_id', (int) $link_id);
        $this->db->where('ip_hash', $ip_hash);
        $this->db->where('created_at >', date('Y-m-d H:i:s', time() - 3600));

        return $this->db->count_all_results('public_submission_log') >= self::MAX_SUBMISSIONS_PER_HOUR;
    }

    public function log_submission($link_id, $ip_hash) {
        $this->db->insert('public_submission_log', [
            'link_id'    => (int) $link_id,
            'ip_hash'    => $ip_hash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Limpeza oportunista: o registro só serve para a janela de 1 hora
        if (mt_rand(1, 50) === 1) {
            $this->db->where('created_at <', date('Y-m-d H:i:s', time() - 86400))
                     ->delete('public_submission_log');
        }
    }

    // ------------------------------------------------------------
    // Gravação da resposta
    // ------------------------------------------------------------

    /**
     * Grava uma resposta pública no mesmo formato das coletas do app, para
     * que apareça em respostas, relatórios e painel do cliente sem
     * tratamento especial.
     *
     * @param array $answers lista de linhas para question_responses (sem form_response_id)
     * @return int|string id gravado, ou 'duplicated' se este envio já foi gravado
     */
    public function save_submission($link, $answers, $consent_given, $started_at, $submission_id) {
        $has_client_id = $this->db->field_exists('client_id', 'form_responses');

        if ($has_client_id) {
            // Duplo clique ou reenvio da mesma página: o mesmo envio não grava duas vezes
            $dup = $this->db->select('id')->get_where('form_responses', ['client_id' => $submission_id])->row();
            if ($dup) {
                return 'duplicated';
            }
        }

        $now = date('Y-m-d H:i:s');

        $form = [
            'questionnaire_id' => (int) $link->questionnaire_id,
            'applied_by'       => null,
            'origin'           => 'qrcode',
            'public_link_id'   => (int) $link->id,
            'consent_given'    => (bool) $consent_given,
            'sync_status'      => 'synced',
            'started_at'       => $started_at,
            'completed_at'     => $now,
            'created_at'       => $now,
        ];
        if ($has_client_id) {
            $form['client_id'] = $submission_id;
        }

        $this->db->trans_begin();

        if (!$this->db->insert('form_responses', $form)) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $form_response_id = (int) $this->db->insert_id();

        foreach ($answers as $answer) {
            $answer['form_response_id'] = $form_response_id;
            $answer['created_at'] = $now;
            if (!$this->db->insert('question_responses', $answer)) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();

        return $form_response_id;
    }

    private function truthy($value) {
        return $value === TRUE || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    }
}
