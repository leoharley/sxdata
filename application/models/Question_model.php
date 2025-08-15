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


    /**
 * Buscar perguntas com lógica condicional
 */
public function get_by_questionnaire_with_logic($questionnaire_id) {
    $this->db->select('q.*, GROUP_CONCAT(
        CONCAT(qo.id, ":", qo.option_text, ":", qo.option_value, ":", qo.order_index) 
        ORDER BY qo.order_index SEPARATOR "|"
    ) as options_data');
    $this->db->from('questions q');
    $this->db->join('question_options qo', 'q.id = qo.question_id', 'left');
    $this->db->where('q.questionnaire_id', $questionnaire_id);
    $this->db->group_by('q.id');
    $this->db->order_by('q.order_index', 'ASC');
    
    $questions = $this->db->get()->result();
    
    // Processar opções
    foreach ($questions as &$question) {
        $question->options = array();
        
        if (!empty($question->options_data)) {
            $options_parts = explode('|', $question->options_data);
            foreach ($options_parts as $option_part) {
                $option_data = explode(':', $option_part);
                if (count($option_data) >= 4) {
                    $question->options[] = (object) array(
                        'id' => $option_data[0],
                        'option_text' => $option_data[1],
                        'option_value' => $option_data[2],
                        'order_index' => $option_data[3]
                    );
                }
            }
        }
        
        // Remover campo temporário
        unset($question->options_data);
        
        // Decodificar lógica condicional se existir
        if (!empty($question->conditional_logic)) {
            $logic = json_decode($question->conditional_logic, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $question->conditional_logic_decoded = $logic;
            } else {
                $question->conditional_logic_decoded = null;
            }
        } else {
            $question->conditional_logic_decoded = null;
        }
    }
    
    return $questions;
}

/**
 * Criar pergunta com lógica condicional
 */
public function create($data) {
    // Validar lógica condicional antes de salvar
    if (isset($data['conditional_logic']) && !empty($data['conditional_logic'])) {
        $this->validate_conditional_logic_json($data['conditional_logic']);
    }
    
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    return $this->db->insert('questions', $data) ? $this->db->insert_id() : FALSE;
}

/**
 * Atualizar pergunta com lógica condicional
 */
public function update($id, $data) {
    // Validar lógica condicional antes de salvar
    if (isset($data['conditional_logic']) && !empty($data['conditional_logic'])) {
        $this->validate_conditional_logic_json($data['conditional_logic']);
    }
    
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    $this->db->where('id', $id);
    return $this->db->update('questions', $data);
}

/**
 * Validar JSON da lógica condicional
 */
private function validate_conditional_logic_json($conditional_logic) {
    if (is_string($conditional_logic)) {
        $decoded = json_decode($conditional_logic, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Lógica condicional contém JSON inválido: ' . json_last_error_msg());
        }
        
        // Validar estrutura básica
        if (!is_array($decoded)) {
            throw new Exception('Lógica condicional deve ser um objeto JSON válido');
        }
        
        // Validar estrutura das regras
        foreach (['visibility', 'required'] as $rule_type) {
            if (isset($decoded[$rule_type])) {
                $this->validate_rule_structure($decoded[$rule_type], $rule_type);
            }
        }
    }
    
    return true;
}

/**
 * Validar estrutura de uma regra
 */
private function validate_rule_structure($rule, $rule_type) {
    if (!is_array($rule)) {
        throw new Exception("Regra de {$rule_type} deve ser um array");
    }
    
    if (!isset($rule['operator']) || !in_array($rule['operator'], ['AND', 'OR'])) {
        throw new Exception("Regra de {$rule_type} deve ter operador válido (AND ou OR)");
    }
    
    if (!isset($rule['conditions']) || !is_array($rule['conditions'])) {
        throw new Exception("Regra de {$rule_type} deve ter array de condições");
    }
    
    foreach ($rule['conditions'] as $index => $condition) {
        $this->validate_condition_structure($condition, $rule_type, $index + 1);
    }
    
    return true;
}

/**
 * Validar estrutura de uma condição
 */
private function validate_condition_structure($condition, $rule_type, $condition_number) {
    if (!is_array($condition)) {
        throw new Exception("Condição {$condition_number} da regra {$rule_type} deve ser um array");
    }
    
    $required_fields = ['question', 'operator'];
    foreach ($required_fields as $field) {
        if (!isset($condition[$field])) {
            throw new Exception("Condição {$condition_number} da regra {$rule_type} deve ter campo '{$field}'");
        }
    }
    
    if (!is_numeric($condition['question'])) {
        throw new Exception("Condição {$condition_number} da regra {$rule_type}: campo 'question' deve ser numérico");
    }
    
    $valid_operators = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
    if (!in_array($condition['operator'], $valid_operators)) {
        throw new Exception("Condição {$condition_number} da regra {$rule_type}: operador inválido");
    }
    
    return true;
}

/**
 * Buscar perguntas que dependem de uma pergunta específica
 */
public function get_dependent_questions($question_id, $questionnaire_id) {
    // Buscar todas as perguntas do questionário
    $this->db->select('*');
    $this->db->where('questionnaire_id', $questionnaire_id);
    $this->db->where('conditional_logic IS NOT NULL');
    $this->db->where('conditional_logic != ""');
    $questions = $this->db->get('questions')->result();
    
    $dependent_questions = array();
    
    // Buscar índice da pergunta alvo
    $this->db->select('order_index');
    $this->db->where('id', $question_id);
    $target_question = $this->db->get('questions')->row();
    
    if (!$target_question) {
        return $dependent_questions;
    }
    
    $target_index = $target_question->order_index - 1; // Converter para índice base 0
    
    foreach ($questions as $question) {
        if (empty($question->conditional_logic)) {
            continue;
        }
        
        $logic = json_decode($question->conditional_logic, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }
        
        // Verificar se esta pergunta depende da pergunta alvo
        $depends_on_target = false;
        
        foreach (['visibility', 'required'] as $rule_type) {
            if (isset($logic[$rule_type]['conditions'])) {
                foreach ($logic[$rule_type]['conditions'] as $condition) {
                    if (isset($condition['question']) && $condition['question'] == $target_index) {
                        $depends_on_target = true;
                        break 2;
                    }
                }
            }
        }
        
        if ($depends_on_target) {
            $dependent_questions[] = $question;
        }
    }
    
    return $dependent_questions;
}

/**
 * Validar integridade da lógica condicional do questionário
 */
public function validate_questionnaire_logic_integrity($questionnaire_id) {
    $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
    $errors = array();
    $warnings = array();
    
    foreach ($questions as $index => $question) {
        if (empty($question->conditional_logic_decoded)) {
            continue;
        }
        
        $logic = $question->conditional_logic_decoded;
        
        foreach (['visibility', 'required'] as $rule_type) {
            if (!isset($logic[$rule_type]['conditions'])) {
                continue;
            }
            
            foreach ($logic[$rule_type]['conditions'] as $condition_index => $condition) {
                $target_question_index = $condition['question'];
                
                // Verificar se a pergunta referenciada existe
                if ($target_question_index >= count($questions)) {
                    $errors[] = "Pergunta " . ($index + 1) . ", condição " . ($condition_index + 1) . ": Referencia pergunta inexistente (índice {$target_question_index})";
                    continue;
                }
                
                // Verificar se não está referenciando pergunta posterior
                if ($target_question_index >= $index) {
                    $errors[] = "Pergunta " . ($index + 1) . ", condição " . ($condition_index + 1) . ": Não pode referenciar pergunta posterior ou a si mesma";
                    continue;
                }
                
                $target_question = $questions[$target_question_index];
                
                // Verificar compatibilidade do operador com o tipo de pergunta
                $operator = $condition['operator'];
                $target_type = $target_question->question_type;
                
                if (in_array($operator, ['greater_than', 'less_than']) && $target_type !== 'number') {
                    $warnings[] = "Pergunta " . ($index + 1) . ", condição " . ($condition_index + 1) . ": Operador numérico usado em pergunta não numérica";
                }
                
                // Verificar se valor existe nas opções (para perguntas de múltipla escolha)
                if (in_array($target_type, ['radio', 'checkbox', 'select']) && 
                    !in_array($operator, ['is_empty', 'is_not_empty']) && 
                    !empty($condition['value'])) {
                    
                    $valid_values = array_column($target_question->options, 'option_text');
                    if (!in_array($condition['value'], $valid_values)) {
                        $warnings[] = "Pergunta " . ($index + 1) . ", condição " . ($condition_index + 1) . ": Valor '{$condition['value']}' não existe nas opções da pergunta referenciada";
                    }
                }
            }
        }
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    );
}

/**
 * Limpar lógica condicional inválida
 */
public function clean_invalid_conditional_logic($questionnaire_id) {
    $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
    $cleaned_count = 0;
    
    foreach ($questions as $question) {
        if (empty($question->conditional_logic)) {
            continue;
        }
        
        $logic = json_decode($question->conditional_logic, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON inválido - remover
            $this->update($question->id, array('conditional_logic' => null));
            $cleaned_count++;
            continue;
        }
        
        $cleaned_logic = $this->clean_logic_structure($logic, $question->order_index - 1, count($questions));
        
        if ($cleaned_logic !== $logic) {
            if (empty($cleaned_logic['visibility']) && empty($cleaned_logic['required'])) {
                // Se não sobrou nenhuma regra válida, remover completamente
                $this->update($question->id, array('conditional_logic' => null));
            } else {
                // Salvar lógica limpa
                $this->update($question->id, array('conditional_logic' => json_encode($cleaned_logic)));
            }
            $cleaned_count++;
        }
    }
    
    return $cleaned_count;
}

/**
 * Limpar estrutura de lógica removendo regras inválidas
 */
private function clean_logic_structure($logic, $current_index, $total_questions) {
    $cleaned = array();
    
    foreach (['visibility', 'required'] as $rule_type) {
        if (!isset($logic[$rule_type]) || !isset($logic[$rule_type]['conditions'])) {
            continue;
        }
        
        $valid_conditions = array();
        
        foreach ($logic[$rule_type]['conditions'] as $condition) {
            if ($this->is_condition_valid($condition, $current_index, $total_questions)) {
                $valid_conditions[] = $condition;
            }
        }
        
        if (!empty($valid_conditions)) {
            $cleaned[$rule_type] = array(
                'operator' => $logic[$rule_type]['operator'] ?? 'AND',
                'conditions' => $valid_conditions
            );
        }
    }
    
    return $cleaned;
}

/**
 * Verificar se uma condição é válida
 */
private function is_condition_valid($condition, $current_index, $total_questions) {
    // Verificar campos obrigatórios
    if (!isset($condition['question']) || !isset($condition['operator'])) {
        return false;
    }
    
    $target_index = $condition['question'];
    
    // Verificar se índice é válido
    if (!is_numeric($target_index) || $target_index < 0 || $target_index >= $total_questions) {
        return false;
    }
    
    // Verificar se não está referenciando pergunta posterior
    if ($target_index >= $current_index) {
        return false;
    }
    
    // Verificar se operador é válido
    $valid_operators = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
    if (!in_array($condition['operator'], $valid_operators)) {
        return false;
    }
    
    return true;
}

/**
 * Migrar lógica condicional para nova estrutura (se necessário)
 */
public function migrate_conditional_logic($questionnaire_id) {
    $questions = $this->get_by_questionnaire($questionnaire_id);
    $migrated_count = 0;
    
    foreach ($questions as $question) {
        if (empty($question->conditional_logic)) {
            continue;
        }
        
        $logic = json_decode($question->conditional_logic, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }
        
        // Verificar se precisa migrar (exemplo: estrutura antiga)
        $needs_migration = false;
        $migrated_logic = $logic;
        
        // Exemplo de migração: converter IDs de pergunta para índices
        if (isset($logic['visibility']['conditions'])) {
            foreach ($logic['visibility']['conditions'] as &$condition) {
                if (isset($condition['question_id']) && !isset($condition['question'])) {
                    // Converter question_id para question (índice)
                    $question_index = $this->get_question_index_by_id($condition['question_id'], $questionnaire_id);
                    if ($question_index !== false) {
                        $condition['question'] = $question_index;
                        unset($condition['question_id']);
                        $needs_migration = true;
                    }
                }
            }
        }
        
        if (isset($logic['required']['conditions'])) {
            foreach ($logic['required']['conditions'] as &$condition) {
                if (isset($condition['question_id']) && !isset($condition['question'])) {
                    $question_index = $this->get_question_index_by_id($condition['question_id'], $questionnaire_id);
                    if ($question_index !== false) {
                        $condition['question'] = $question_index;
                        unset($condition['question_id']);
                        $needs_migration = true;
                    }
                }
            }
        }
        
        if ($needs_migration) {
            $this->update($question->id, array('conditional_logic' => json_encode($migrated_logic)));
            $migrated_count++;
        }
    }
    
    return $migrated_count;
}

/**
 * Obter índice da pergunta pelo ID
 */
private function get_question_index_by_id($question_id, $questionnaire_id) {
    $this->db->select('order_index');
    $this->db->where('id', $question_id);
    $this->db->where('questionnaire_id', $questionnaire_id);
    $question = $this->db->get('questions')->row();
    
    return $question ? ($question->order_index - 1) : false; // Converter para índice base 0
}

/**
 * Clonar pergunta com lógica condicional para outro questionário
 */
public function clone_with_logic($question_id, $new_questionnaire_id, $order_index, $question_mapping = array()) {
    // Buscar pergunta original
    $original = $this->get_by_id($question_id);
    if (!$original) {
        return false;
    }
    
    // Preparar dados da nova pergunta
    $new_question_data = array(
        'questionnaire_id' => $new_questionnaire_id,
        'question_text' => $original->question_text,
        'question_type' => $original->question_type,
        'is_required' => $original->is_required,
        'order_index' => $order_index,
        'conditional_logic' => $this->map_conditional_logic($original->conditional_logic, $question_mapping)
    );
    
    // Criar nova pergunta
    $new_question_id = $this->create($new_question_data);
    
    if ($new_question_id) {
        // Clonar opções se existirem
        $options = $this->get_options($question_id);
        foreach ($options as $option) {
            $this->create_option(array(
                'question_id' => $new_question_id,
                'option_text' => $option->option_text,
                'option_value' => $option->option_value,
                'order_index' => $option->order_index
            ));
        }
    }
    
    return $new_question_id;
}

/**
 * Mapear lógica condicional para novos índices de pergunta
 */
private function map_conditional_logic($conditional_logic, $question_mapping) {
    if (empty($conditional_logic)) {
        return null;
    }
    
    $logic = json_decode($conditional_logic, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    $mapped_logic = $logic;
    
    foreach (['visibility', 'required'] as $rule_type) {
        if (isset($mapped_logic[$rule_type]['conditions'])) {
            foreach ($mapped_logic[$rule_type]['conditions'] as &$condition) {
                if (isset($condition['question']) && isset($question_mapping[$condition['question']])) {
                    $condition['question'] = $question_mapping[$condition['question']];
                } else {
                    // Se não há mapeamento, remover a condição (será limpa depois)
                    $condition = null;
                }
            }
            
            // Remover condições nulas
            $mapped_logic[$rule_type]['conditions'] = array_filter($mapped_logic[$rule_type]['conditions']);
            
            // Se não sobrou nenhuma condição, remover a regra
            if (empty($mapped_logic[$rule_type]['conditions'])) {
                unset($mapped_logic[$rule_type]);
            }
        }
    }
    
    // Se não sobrou nenhuma regra, retornar null
    if (empty($mapped_logic['visibility']) && empty($mapped_logic['required'])) {
        return null;
    }
    
    return json_encode($mapped_logic);
}

/**
 * Estatísticas de uso da lógica condicional
 */
public function get_conditional_logic_stats($questionnaire_id = null) {
    $this->db->select('
        COUNT(*) as total_questions,
        COUNT(CASE WHEN conditional_logic IS NOT NULL AND conditional_logic != "" THEN 1 END) as questions_with_logic,
        COUNT(CASE WHEN conditional_logic LIKE "%visibility%" THEN 1 END) as questions_with_visibility_logic,
        COUNT(CASE WHEN conditional_logic LIKE "%required%" THEN 1 END) as questions_with_required_logic
    ');
    
    if ($questionnaire_id) {
        $this->db->where('questionnaire_id', $questionnaire_id);
    }
    
    $stats = $this->db->get('questions')->row();
    
    // Adicionar estatísticas mais detalhadas
    $detailed_stats = array(
        'total_questions' => (int)$stats->total_questions,
        'questions_with_logic' => (int)$stats->questions_with_logic,
        'questions_with_visibility_logic' => (int)$stats->questions_with_visibility_logic,
        'questions_with_required_logic' => (int)$stats->questions_with_required_logic,
        'logic_usage_percentage' => $stats->total_questions > 0 ? round(($stats->questions_with_logic / $stats->total_questions) * 100, 1) : 0
    );
    
    // Buscar estatísticas de operadores mais usados
    if ($questionnaire_id) {
        $this->db->where('questionnaire_id', $questionnaire_id);
    }
    $this->db->where('conditional_logic IS NOT NULL');
    $this->db->where('conditional_logic != ""');
    $questions_with_logic = $this->db->get('questions')->result();
    
    $operator_usage = array();
    $total_conditions = 0;
    
    foreach ($questions_with_logic as $question) {
        $logic = json_decode($question->conditional_logic, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach (['visibility', 'required'] as $rule_type) {
                if (isset($logic[$rule_type]['conditions'])) {
                    foreach ($logic[$rule_type]['conditions'] as $condition) {
                        if (isset($condition['operator'])) {
                            $operator = $condition['operator'];
                            $operator_usage[$operator] = ($operator_usage[$operator] ?? 0) + 1;
                            $total_conditions++;
                        }
                    }
                }
            }
        }
    }
    
    $detailed_stats['total_conditions'] = $total_conditions;
    $detailed_stats['operator_usage'] = $operator_usage;
    
    return $detailed_stats;
}

/**
 * Buscar perguntas com lógica condicional complexa
 */
public function get_questions_with_complex_logic($questionnaire_id, $min_conditions = 3) {
    $this->db->select('*');
    $this->db->where('questionnaire_id', $questionnaire_id);
    $this->db->where('conditional_logic IS NOT NULL');
    $this->db->where('conditional_logic != ""');
    $questions = $this->db->get('questions')->result();
    
    $complex_questions = array();
    
    foreach ($questions as $question) {
        $logic = json_decode($question->conditional_logic, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }
        
        $total_conditions = 0;
        
        foreach (['visibility', 'required'] as $rule_type) {
            if (isset($logic[$rule_type]['conditions'])) {
                $total_conditions += count($logic[$rule_type]['conditions']);
            }
        }
        
        if ($total_conditions >= $min_conditions) {
            $question->condition_count = $total_conditions;
            $complex_questions[] = $question;
        }
    }
    
    // Ordenar por número de condições (decrescente)
    usort($complex_questions, function($a, $b) {
        return $b->condition_count <=> $a->condition_count;
    });
    
    return $complex_questions;
}

/**
 * Exportar lógica condicional para análise
 */
public function export_conditional_logic_analysis($questionnaire_id) {
    $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
    $analysis = array();
    
    foreach ($questions as $index => $question) {
        if (empty($question->conditional_logic_decoded)) {
            continue;
        }
        
        $logic = $question->conditional_logic_decoded;
        $question_analysis = array(
            'question_id' => $question->id,
            'question_index' => $index,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'rules' => array()
        );
        
        foreach (['visibility', 'required'] as $rule_type) {
            if (isset($logic[$rule_type])) {
                $rule_analysis = array(
                    'type' => $rule_type,
                    'operator' => $logic[$rule_type]['operator'],
                    'conditions_count' => count($logic[$rule_type]['conditions']),
                    'conditions' => array()
                );
                
                foreach ($logic[$rule_type]['conditions'] as $condition) {
                    $rule_analysis['conditions'][] = array(
                        'target_question_index' => $condition['question'],
                        'operator' => $condition['operator'],
                        'value' => $condition['value'] ?? null
                    );
                }
                
                $question_analysis['rules'][] = $rule_analysis;
            }
        }
        
        $analysis[] = $question_analysis;
    }
    
    return $analysis;
}


}