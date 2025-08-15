defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaire_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->select('q.*, u.full_name as created_by_name, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->order_by('q.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Buscar questionários por termo
     * 
     * @param string $term Termo de busca
     * @param int $project_id ID do projeto (opcional)
     * @return array Lista de questionários encontrados
     */
    public function search($term, $project_id = null) {
        $this->db->select('q.*, u.full_name as created_by_name, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        
        $this->db->group_start();
            $this->db->like('q.title', $term);
            $this->db->or_like('q.description', $term);
        $this->db->group_end();
        
        if ($project_id) {
            $this->db->where('q.project_id', $project_id);
        }
        
        $this->db->order_by('q.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Método para verificar se os campos existem na tabela
     * (método auxiliar para debug - remover em produção)
     */
    public function verify_table_structure() {
        if (ENVIRONMENT === 'development') {
            $query = $this->db->query("DESCRIBE questionnaires");
            $fields = $query->result();
            
            log_message('debug', 'Questionnaires table structure:');
            foreach ($fields as $field) {
                log_message('debug', 'Field: ' . $field->Field . ', Type: ' . $field->Type . ', Null: ' . $field->Null . ', Default: ' . $field->Default);
            }
        }
    }

    /**
     * Executar lógica condicional para um conjunto de respostas
     * 
     * @param array $questions Array de perguntas com lógica condicional
     * @param array $responses Respostas fornecidas
     * @return array Estados das perguntas (visível, obrigatória, etc.)
     */
    public function execute_conditional_logic($questions, $responses = []) {
        $question_states = [];
        
        // Inicializar estados padrão
        foreach ($questions as $index => $question) {
            $question_states["q_$index"] = [
                'visible' => true,
                'required' => (bool)$question->is_required,
                'original_required' => (bool)$question->is_required
            ];
        }
        
        // Processar lógica condicional
        foreach ($questions as $index => $question) {
            if (!empty($question->conditional_logic_decoded)) {
                $logic = $question->conditional_logic_decoded;
                
                // Processar regras de visibilidade
                if (isset($logic['visibility'])) {
                    $visibility_result = $this->evaluate_rule($logic['visibility'], $responses, $questions);
                    $question_states["q_$index"]['visible'] = $visibility_result;
                }
                
                // Processar regras de obrigatoriedade
                if (isset($logic['required'])) {
                    $required_result = $this->evaluate_rule($logic['required'], $responses, $questions);
                    if ($required_result) {
                        $question_states["q_$index"]['required'] = true;
                    }
                }
                
                // Se pergunta não está visível, não deve ser obrigatória
                if (!$question_states["q_$index"]['visible']) {
                    $question_states["q_$index"]['required'] = false;
                }
            }
        }
        
        return $question_states;
    }

    /**
     * Avaliar uma regra de lógica condicional
     * 
     * @param array $rule Regra a ser avaliada
     * @param array $responses Respostas fornecidas
     * @param array $questions Array de perguntas
     * @return bool Resultado da avaliação
     */
    private function evaluate_rule($rule, $responses, $questions) {
        if (!isset($rule['conditions']) || empty($rule['conditions'])) {
            return true;
        }
        
        $operator = isset($rule['operator']) ? $rule['operator'] : 'AND';
        $results = [];
        
        foreach ($rule['conditions'] as $condition) {
            if (!isset($condition['question'], $condition['operator'])) {
                continue;
            }
            
            $target_question_index = intval($condition['question']);
            $condition_operator = $condition['operator'];
            $condition_value = isset($condition['value']) ? $condition['value'] : '';
            
            // Obter resposta da pergunta alvo
            $response_value = isset($responses["q_$target_question_index"]) ? 
                            $responses["q_$target_question_index"] : '';
            
            // Avaliar condição
            $condition_result = $this->evaluate_condition(
                $response_value, 
                $condition_operator, 
                $condition_value,
                isset($questions[$target_question_index]) ? $questions[$target_question_index] : null
            );
            
            $results[] = $condition_result;
        }
        
        // Aplicar operador lógico
        if ($operator === 'OR') {
            return in_array(true, $results);
        } else { // AND
            return !in_array(false, $results);
        }
    }

    /**
     * Avaliar uma condição específica
     * 
     * @param mixed $response_value Valor da resposta
     * @param string $operator Operador da condição
     * @param mixed $condition_value Valor da condição
     * @param object $target_question Pergunta alvo (opcional)
     * @return bool Resultado da avaliação
     */
    private function evaluate_condition($response_value, $operator, $condition_value, $target_question = null) {
        // Normalizar valores
        $response_str = is_array($response_value) ? implode(',', $response_value) : strval($response_value);
        $condition_str = strval($condition_value);
        
        switch ($operator) {
            case 'equals':
                return $response_str === $condition_str;
                
            case 'not_equals':
                return $response_str !== $condition_str;
                
            case 'contains':
                if (is_array($response_value)) {
                    return in_array($condition_str, $response_value);
                }
                return strpos($response_str, $condition_str) !== false;
                
            case 'not_contains':
                if (is_array($response_value)) {
                    return !in_array($condition_str, $response_value);
                }
                return strpos($response_str, $condition_str) === false;
                
            case 'greater_than':
                return is_numeric($response_str) && is_numeric($condition_str) && 
                       floatval($response_str) > floatval($condition_str);
                
            case 'less_than':
                return is_numeric($response_str) && is_numeric($condition_str) && 
                       floatval($response_str) < floatval($condition_str);
                
            case 'is_empty':
                return empty($response_str) || trim($response_str) === '';
                
            case 'is_not_empty':
                return !empty($response_str) && trim($response_str) !== '';
                
            default:
                return false;
        }
    }

    /**
     * Validar respostas com lógica condicional
     * 
     * @param array $questions Array de perguntas
     * @param array $responses Respostas fornecidas
     * @return array Resultado da validação
     */
    public function validate_responses_with_conditional_logic($questions, $responses) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // Executar lógica condicional para obter estados das perguntas
            $question_states = $this->execute_conditional_logic($questions, $responses);
            
            // Validar cada pergunta
            foreach ($questions as $index => $question) {
                $state = $question_states["q_$index"];
                $response = isset($responses["q_$index"]) ? $responses["q_$index"] : null;
                
                // Se pergunta não está visível, pular validação
                if (!$state['visible']) {
                    continue;
                }
                
                // Validar obrigatoriedade
                if ($state['required']) {
                    if (empty($response) && $response !== '0') {
                        $validation['errors'][] = "Pergunta " . ($index + 1) . " é obrigatória.";
                        $validation['valid'] = false;
                    }
                }
                
                // Validar tipo de resposta
                if (!empty($response)) {
                    $type_validation = $this->validate_response_type($question, $response);
                    if (!$type_validation['valid']) {
                        $validation['errors'] = array_merge($validation['errors'], $type_validation['errors']);
                        $validation['valid'] = false;
                    }
                }
            }
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Erro na validação: ' . $e->getMessage();
            log_message('error', 'Erro na validação com lógica condicional: ' . $e->getMessage());
        }
        
        return $validation;
    }

    /**
     * Validar tipo de resposta para uma pergunta
     * 
     * @param object $question Pergunta
     * @param mixed $response Resposta
     * @return array Resultado da validação
     */
    private function validate_response_type($question, $response) {
        $validation = [
            'valid' => true,
            'errors' => []
        ];
        
        switch ($question->question_type) {
            case 'number':
                if (!is_numeric($response)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Pergunta " . $question->order_index . " deve ser um número.";
                }
                break;
                
            case 'email':
                if (!filter_var($response, FILTER_VALIDATE_EMAIL)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Pergunta " . $question->order_index . " deve ser um email válido.";
                }
                break;
                
            case 'date':
                if (!$this->validate_date_format($response)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Pergunta " . $question->order_index . " deve ser uma data válida.";
                }
                break;
                
            case 'radio':
            case 'select':
                // Validar se valor está nas opções disponíveis
                if (!empty($question->options)) {
                    $valid_values = array_column($question->options, 'option_text');
                    if (!in_array($response, $valid_values)) {
                        $validation['valid'] = false;
                        $validation['errors'][] = "Pergunta " . $question->order_index . " tem valor inválido.";
                    }
                }
                break;
                
            case 'checkbox':
                // Validar se é array e todos os valores estão nas opções
                if (!is_array($response)) {
                    $response = [$response];
                }
                
                if (!empty($question->options)) {
                    $valid_values = array_column($question->options, 'option_text');
                    foreach ($response as $value) {
                        if (!in_array($value, $valid_values)) {
                            $validation['valid'] = false;
                            $validation['errors'][] = "Pergunta " . $question->order_index . " tem valor inválido: $value";
                        }
                    }
                }
                break;
        }
        
        return $validation;
    }

    /**
     * Validar formato de data
     * 
     * @param string $date Data a ser validada
     * @return bool Válida ou não
     */
    private function validate_date_format($date) {
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'];
        
        foreach ($formats as $format) {
            $d = DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Processar respostas com lógica condicional para salvar no banco
     * 
     * @param array $questions Array de perguntas
     * @param array $raw_responses Respostas brutas do formulário
     * @return array Respostas processadas e validadas
     */
    public function process_responses_with_logic($questions, $raw_responses) {
        $processed = [
            'responses' => [],
            'metadata' => [
                'total_questions' => count($questions),
                'answered_questions' => 0,
                'visible_questions' => 0,
                'required_questions' => 0,
                'logic_applied' => false
            ],
            'validation' => ['valid' => true, 'errors' => []]
        ];
        
        try {
            // Executar lógica condicional
            $question_states = $this->execute_conditional_logic($questions, $raw_responses);
            $processed['metadata']['logic_applied'] = true;
            
            // Processar cada pergunta
            foreach ($questions as $index => $question) {
                $state = $question_states["q_$index"];
                $response_key = "q_$index";
                $raw_response = isset($raw_responses[$response_key]) ? $raw_responses[$response_key] : null;
                
                // Contabilizar pergunta visível
                if ($state['visible']) {
                    $processed['metadata']['visible_questions']++;
                    
                    if ($state['required']) {
                        $processed['metadata']['required_questions']++;
                    }
                    
                    // Processar resposta se pergunta está visível
                    if (!empty($raw_response) || $raw_response === '0') {
                        $processed['responses'][$response_key] = [
                            'question_id' => $question->id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'response_value' => $this->normalize_response_value($raw_response, $question),
                            'was_visible' => true,
                            'was_required' => $state['required'],
                            'order_index' => $question->order_index
                        ];
                        $processed['metadata']['answered_questions']++;
                    } else if ($state['required']) {
                        // Pergunta obrigatória não respondida
                        $processed['validation']['valid'] = false;
                        $processed['validation']['errors'][] = "Pergunta " . ($index + 1) . " é obrigatória.";
                    }
                } else {
                    // Pergunta não visível - registrar como não aplicável
                    $processed['responses'][$response_key] = [
                        'question_id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'response_value' => null,
                        'was_visible' => false,
                        'was_required' => false,
                        'order_index' => $question->order_index,
                        'not_applicable' => true
                    ];
                }
            }
            
        } catch (Exception $e) {
            $processed['validation']['valid'] = false;
            $processed['validation']['errors'][] = 'Erro no processamento: ' . $e->getMessage();
            log_message('error', 'Erro no processamento de respostas com lógica: ' . $e->getMessage());
        }
        
        return $processed;
    }

    /**
     * Normalizar valor de resposta baseado no tipo de pergunta
     * 
     * @param mixed $raw_value Valor bruto
     * @param object $question Pergunta
     * @return mixed Valor normalizado
     */
    private function normalize_response_value($raw_value, $question) {
        switch ($question->question_type) {
            case 'checkbox':
                return is_array($raw_value) ? $raw_value : [$raw_value];
                
            case 'number':
                return is_numeric($raw_value) ? floatval($raw_value) : $raw_value;
                
            case 'radio':
            case 'select':
            case 'text':
            case 'textarea':
            case 'email':
            case 'date':
            default:
                return is_string($raw_value) ? trim($raw_value) : $raw_value;
        }
    }

    /**
     * Gerar relatório de uso da lógica condicional
     * 
     * @param int $questionnaire_id ID do questionário (opcional)
     * @return array Relatório detalhado
     */
    public function generate_conditional_logic_report($questionnaire_id = null) {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'scope' => $questionnaire_id ? 'single_questionnaire' : 'all_questionnaires',
            'questionnaire_id' => $questionnaire_id,
            'summary' => [],
            'questionnaires' => [],
            'total_logic_usage' => 0,
            'complexity_analysis' => []
        ];
        
        try {
            // Query base
            $this->db->select('q.id, q.title, q.status, COUNT(questions.id) as total_questions');
            $this->db->from('questionnaires q');
            $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
            
            if ($questionnaire_id) {
                $this->db->where('q.id', $questionnaire_id);
            }
            
            $this->db->group_by('q.id, q.title, q.status');
            $questionnaires = $this->db->get()->result();
            
            foreach ($questionnaires as $questionnaire) {
                // Análise detalhada por questionário
                $questionnaire_analysis = [
                    'id' => $questionnaire->id,
                    'title' => $questionnaire->title,
                    'status' => $questionnaire->status,
                    'total_questions' => $questionnaire->total_questions,
                    'questions_with_logic' => 0,
                    'logic_types' => ['visibility' => 0, 'required' => 0, 'both' => 0],
                    'complexity_score' => 0,
                    'dependencies' => []
                ];
                
                // Obter estatísticas de lógica condicional
                if (method_exists($this->load->model('Question_model'), 'get_conditional_logic_stats')) {
                    $this->load->model('Question_model');
                    $logic_stats = $this->Question_model->get_conditional_logic_stats($questionnaire->id);
                    
                    $questionnaire_analysis['questions_with_logic'] = $logic_stats['questions_with_logic'];
                    $questionnaire_analysis['logic_types']['visibility'] = $logic_stats['visibility_rules'];
                    $questionnaire_analysis['logic_types']['required'] = $logic_stats['required_rules'];
                    $questionnaire_analysis['complexity_score'] = $logic_stats['complex_logic'];
                    
                    $report['total_logic_usage'] += $logic_stats['questions_with_logic'];
                }
                
                $report['questionnaires'][] = $questionnaire_analysis;
            }
            
            // Gerar resumo
            $report['summary'] = [
                'total_questionnaires' => count($questionnaires),
                'questionnaires_with_logic' => count(array_filter($report['questionnaires'], function($q) {
                    return $q['questions_with_logic'] > 0;
                })),
                'total_questions' => array_sum(array_column($report['questionnaires'], 'total_questions')),
                'total_logic_questions' => $report['total_logic_usage'],
                'average_complexity' => count($report['questionnaires']) > 0 ? 
                    array_sum(array_column($report['questionnaires'], 'complexity_score')) / count($report['questionnaires']) : 0
            ];
            
        } catch (Exception $e) {
            $report['error'] = 'Erro na geração do relatório: ' . $e->getMessage();
            log_message('error', 'Erro no relatório de lógica condicional: ' . $e->getMessage());
        }
        
        return $report;
    }

    /**
     * Otimizar performance de questionários com lógica condicional
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Resultado da otimização
     */
    public function optimize_conditional_logic_performance($questionnaire_id) {
        $optimization = [
            'questionnaire_id' => $questionnaire_id,
            'optimizations_applied' => [],
            'performance_impact' => [],
            'recommendations' => []
        ];
        
        try {
            // Carregar perguntas com lógica
            $this->load->model('Question_model');
            
            if (method_exists($this->Question_model, 'get_by_questionnaire_with_logic')) {
                $questions = $this->Question_model->get_by_questionnaire_with_logic($questionnaire_id);
                
                // Analisar complexidade
                $complex_questions = 0;
                $total_conditions = 0;
                $circular_dependencies = [];
                
                foreach ($questions as $index => $question) {
                    if (!empty($question->conditional_logic_decoded)) {
                        $logic = $question->conditional_logic_decoded;
                        $question_conditions = 0;
                        
                        if (isset($logic['visibility']['conditions'])) {
                            $question_conditions += count($logic['visibility']['conditions']);
                        }
                        if (isset($logic['required']['conditions'])) {
                            $question_conditions += count($logic['required']['conditions']);
                        }
                        
                        $total_conditions += $question_conditions;
                        
                        if ($question_conditions > 5) {
                            $complex_questions++;
                            $optimization['recommendations'][] = 
                                "Pergunta " . ($index + 1) . " tem muitas condições ($question_conditions). Considere simplificar.";
                        }
                    }
                }
                
                // Gerar recomendações
                if ($complex_questions > 0) {
                    $optimization['recommendations'][] = 
                        "Encontradas $complex_questions perguntas com lógica complexa. Isso pode impactar a performance.";
                }
                
                if ($total_conditions > 20) {
                    $optimization['recommendations'][] = 
                        "Total de $total_conditions condições no questionário. Considere revisar para otimizar performance.";
                }
                
                $optimization['performance_impact'] = [
                    'total_questions' => count($questions),
                    'questions_with_logic' => count(array_filter($questions, function($q) {
                        return !empty($q->conditional_logic_decoded);
                    })),
                    'total_conditions' => $total_conditions,
                    'complex_questions' => $complex_questions,
                    'estimated_load' => $this->calculate_logic_load($total_conditions, count($questions))
                ];
                
            } else {
                $optimization['error'] = 'Método de carregamento de lógica condicional não disponível.';
            }
            
        } catch (Exception $e) {
            $optimization['error'] = 'Erro na otimização: ' . $e->getMessage();
            log_message('error', 'Erro na otimização de lógica condicional: ' . $e->getMessage());
        }
        
        return $optimization;
    }

    /**
     * Calcular carga estimada da lógica condicional
     * 
     * @param int $total_conditions Total de condições
     * @param int $total_questions Total de perguntas
     * @return string Classificação da carga
     */
    private function calculate_logic_load($total_conditions, $total_questions) {
        if ($total_questions == 0) return 'unknown';
        
        $ratio = $total_conditions / $total_questions;
        
        if ($ratio < 0.5) return 'low';
        if ($ratio < 1.5) return 'medium';
        if ($ratio < 3) return 'high';
        return 'very_high';
    } $this->db->get()->result();
    }

    public function get_all_with_stats() {
        $questionnaires = $this->get_all();
        
        foreach ($questionnaires as &$questionnaire) {
            // Contar perguntas
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->question_count = $this->db->count_all_results('questions');
            
            // Contar respostas
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->response_count = $this->db->count_all_results('form_responses');
            
            // Última resposta
            $this->db->select('MAX(completed_at) as last_response');
            $this->db->where('questionnaire_id', $questionnaire->id);
            $this->db->where('completed_at IS NOT NULL');
            $last = $this->db->get('form_responses')->row();
            $questionnaire->last_response = $last ? $last->last_response : NULL;
        }
        
        return $questionnaires;
    }

    public function get_by_id($id) {
        // CORREÇÃO: Incluir explicitamente os campos de checkbox e project_id
        $this->db->select('q.id, q.title, q.description, q.status, q.version, 
                          q.requires_consent, q.requires_location, q.requires_photo,
                          q.estimated_time, q.aplicadores, q.created_by, q.created_at, q.updated_at,
                          q.project_id, p.name as project_name,
                          u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.id', $id);
        
        $result = $this->db->get()->row();
        
        // Debug: Log da query executada (remover em produção)
        if (ENVIRONMENT === 'development' && $result) {
            log_message('debug', 'SQL Query: ' . $this->db->last_query());
            log_message('debug', 'Raw result from DB: ' . json_encode($result));
        }
        
        return $result;
    }

    public function get_active() {
        $this->db->select('q.*, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->order_by('q.title', 'ASC');
        return $this->db->get()->result();
    }

    public function get_by_project($project_id) {
        $this->db->select('q.*, u.full_name as created_by_name');
        $this->db->from('questionnaires q');
        $this->db->join('users u', 'q.created_by = u.id', 'left');
        $this->db->where('q.project_id', $project_id);
        $this->db->order_by('q.created_at', 'DESC');
        
        $questionnaires = $this->db->get()->result();
        
        // Adicionar contagem de perguntas e respostas
        foreach ($questionnaires as &$questionnaire) {
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->question_count = $this->db->count_all_results('questions');
            
            $this->db->where('questionnaire_id', $questionnaire->id);
            $questionnaire->response_count = $this->db->count_all_results('form_responses');
        }
        
        return $questionnaires;
    }

    public function count_by_project($project_id) {
        $this->db->where('project_id', $project_id);
        return $this->db->count_all_results('questionnaires');
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Debug: Log dos dados que serão inseridos (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Creating questionnaire with data: ' . json_encode($data));
        }
        
        return $this->db->insert('questionnaires', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Buscar versão atual antes de atualizar
        $this->db->select('version');
        $this->db->where('id', $id);
        $current = $this->db->get('questionnaires')->row();
        
        if ($current) {
            $data['version'] = $current->version + 1;
        }
        
        // Debug: Log dos dados que serão atualizados (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Updating questionnaire ID ' . $id . ' with data: ' . json_encode($data));
        }
        
        $this->db->where('id', $id);
        $result = $this->db->update('questionnaires', $data);
        
        // Debug: Log da query executada (remover em produção)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Update SQL Query: ' . $this->db->last_query());
            log_message('debug', 'Update result: ' . var_export($result, true));
        }
        
        return $result;
    }

    public function delete($id) {
        // Verificar se tem respostas associadas
        $this->db->where('questionnaire_id', $id);
        $has_responses = $this->db->count_all_results('form_responses') > 0;
        
        if ($has_responses) {
            // Apenas marcar como inativo se tiver respostas
            return $this->update($id, array('status' => 'inactive'));
        } else {
            // Deletar completamente se não tiver respostas
            $this->db->where('id', $id);
            return $this->db->delete('questionnaires');
        }
    }

    public function count_all() {
        return $this->db->count_all('questionnaires');
    }

    public function count_active() {
        $this->db->where('status', 'active');
        return $this->db->count_all_results('questionnaires');
    }

    public function get_usage_stats() {
        $this->db->select('q.title, COUNT(fr.id) as response_count');
        $this->db->from('questionnaires q');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id', 'left');
        $this->db->group_by('q.id, q.title');
        $this->db->order_by('response_count', 'DESC');
        $this->db->limit(10);
        
        return $this->db->get()->result();
    }

    public function get_for_api($user_role = null) {
        $this->db->select('q.*, COUNT(questions.id) as question_count, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id,p.name');
        $this->db->order_by('q.title', 'ASC');
        
        $questionnaires = $this->db->get()->result();
        
        // Adicionar perguntas para cada questionário
        foreach ($questionnaires as &$questionnaire) {
            $questionnaire->questions = $this->get_questions_with_options($questionnaire->id);
        }
        
        return $questionnaires;
    }

    private function get_questions_with_options($questionnaire_id) {
        // Primeiro, buscar as questões
        $this->db->select('*');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->order_by('order_index', 'ASC');
        
        $questions = $this->db->get()->result();
        
        // Para cada questão, buscar suas opções
        foreach ($questions as &$question) {
            $this->db->select('*');
            $this->db->from('question_options');
            $this->db->where('question_id', $question->id);
            $this->db->order_by('order_index', 'ASC');
            
            $question->options = $this->db->get()->result();
        }
        
        return $questions;
    }

    public function can_aplicador_access($questionnaire_id, $aplicador_id) {
        $questionnaire = $this->get_by_id($questionnaire_id);
        
        if (!$questionnaire || $questionnaire->status !== 'active') {
            return FALSE;
        }
        
        // Se não há restrição de aplicadores (NULL), todos podem acessar
        if (empty($questionnaire->aplicadores)) {
            return TRUE;
        }
        
        // Decodificar JSON e verificar se o aplicador está na lista
        $aplicadores_permitidos = json_decode($questionnaire->aplicadores, true);
        
        if (!is_array($aplicadores_permitidos)) {
            return TRUE; // Fallback: se não conseguir decodificar, permite acesso
        }
        
        return in_array($aplicador_id, $aplicadores_permitidos);
    }

    /**
     * Retorna questionários que um aplicador específico pode acessar
     * 
     * @param int $aplicador_id ID do aplicador
     * @return array Lista de questionários disponíveis para o aplicador
     */
    public function get_for_aplicador($aplicador_id) {
        $this->db->select('q.*, COUNT(questions.id) as question_count, p.name as project_name');
        $this->db->from('questionnaires q');
        $this->db->join('questions', 'q.id = questions.questionnaire_id', 'left');
        $this->db->join('projects p', 'q.project_id = p.id', 'left');
        $this->db->where('q.status', 'active');
        $this->db->group_by('q.id');
        $this->db->order_by('q.title', 'ASC');
        
        $all_questionnaires = $this->db->get()->result();
        
        // Filtrar questionários que o aplicador pode acessar
        $accessible_questionnaires = array();
        
        foreach ($all_questionnaires as $questionnaire) {
            if ($this->can_aplicador_access($questionnaire->id, $aplicador_id)) {
                // Adicionar perguntas para cada questionário
                $questionnaire->questions = $this->get_questions_with_options($questionnaire->id);
                $accessible_questionnaires[] = $questionnaire;
            }
        }
        
        return $accessible_questionnaires;
    }

    /**
     * Retorna estatísticas de uso por aplicador
     * 
     * @param int $questionnaire_id ID do questionário (opcional)
     * @return array Estatísticas de aplicadores
     */
    public function get_aplicador_stats($questionnaire_id = null) {
        $this->db->select('u.id, u.full_name, u.username, COUNT(fr.id) as total_responses');
        $this->db->from('users u');
        $this->db->join('form_responses fr', 'u.id = fr.applied_by', 'left');
        $this->db->where('u.role', 'aplicador');
        $this->db->where('u.is_active', TRUE);
        
        if ($questionnaire_id) {
            $this->db->where('fr.questionnaire_id', $questionnaire_id);
        }
        
        $this->db->group_by('u.id, u.full_name, u.username');
        $this->db->order_by('total_responses', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Retorna os nomes dos aplicadores permitidos para um questionário
     * 
     * @param object $questionnaire Objeto do questionário
     * @return string Nomes dos aplicadores separados por vírgula
     */
    public function get_aplicadores_names($questionnaire) {
        if (empty($questionnaire->aplicadores)) {
            return 'Todos os aplicadores';
        }
        
        $aplicadores_ids = json_decode($questionnaire->aplicadores, true);
        
        if (!is_array($aplicadores_ids) || empty($aplicadores_ids)) {
            return 'Todos os aplicadores';
        }
        
        $this->db->select('full_name');
        $this->db->where_in('id', $aplicadores_ids);
        $this->db->where('role', 'aplicador');
        $this->db->where('is_active', TRUE);
        $aplicadores = $this->db->get('users')->result();
        
        if (empty($aplicadores)) {
            return 'Nenhum aplicador válido';
        }
        
        $nomes = array_column($aplicadores, 'full_name');
        return implode(', ', $nomes);
    }

    /**
     * Retorna estatísticas de questionários por projeto
     * 
     * @return array Estatísticas agrupadas por projeto
     */
    public function get_stats_by_project() {
        $this->db->select('p.id, p.name, COUNT(q.id) as questionnaire_count, 
                          SUM(CASE WHEN q.status = "active" THEN 1 ELSE 0 END) as active_count');
        $this->db->from('projects p');
        $this->db->join('questionnaires q', 'p.id = q.project_id', 'left');
        $this->db->group_by('p.id, p.name');
        $this->db->order_by('questionnaire_count', 'DESC');
        
        return
        