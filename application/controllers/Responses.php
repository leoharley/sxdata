<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Responses extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model');
        $this->check_auth();
    }

    /**
     * Página principal de listagem de respostas
     */
    public function index() {
        $data['title'] = 'Respostas - SXData';
        
        // Filtros
        $filters = array();
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        if ($this->input->get('applied_by')) {
            $filters['applied_by'] = $this->input->get('applied_by');
        }
        if ($this->input->get('date_from')) {
            $filters['date_from'] = $this->input->get('date_from');
        }
        if ($this->input->get('date_to')) {
            $filters['date_to'] = $this->input->get('date_to');
        }
        if ($this->input->get('sync_status')) {
            $filters['sync_status'] = $this->input->get('sync_status');
        }

        $data['responses'] = $this->Response_model->get_filtered($filters);
        $data['questionnaires'] = $this->Questionnaire_model->get_all();
        $data['users'] = $this->User_model->get_aplicadores();
        $data['filters'] = $filters;
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/responses/index', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Visualizar resposta individual
     */
    public function view($id) {
        $response = $this->Response_model->get_by_id($id);
        if (!$response) {
            show_404();
        }

        $data['title'] = 'Visualizar Resposta - SXData';
        $data['response'] = $response;
        $data['questionnaire'] = $this->Questionnaire_model->get_by_id($response->questionnaire_id);
        $data['questions'] = $this->Question_model->get_by_questionnaire($response->questionnaire_id);
        $data['answers'] = $this->Response_model->get_answers($id);
        $data['applied_by'] = $this->User_model->get_by_id($response->applied_by);
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/responses/view', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Exportação padrão do sistema (mantido para compatibilidade)
     */
    public function export() {
        $this->load->library('excel');
        
        $filters = array();
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        if ($this->input->get('date_from')) {
            $filters['date_from'] = $this->input->get('date_from');
        }
        if ($this->input->get('date_to')) {
            $filters['date_to'] = $this->input->get('date_to');
        }

        $responses = $this->Response_model->get_for_export($filters);
        
        // Gerar arquivo Excel
        $this->excel->create_export($responses);
    }

    /**
     * NOVO: Exportar dados brutos conforme modelo fornecido (Versão Nativa CORRIGIDA)
     */
    public function export_raw_data() {
        // Verificar autenticação
        if (!$this->session->userdata('admin_logged_in')) {
            show_404();
            return;
        }
        
        $user_id = $this->session->userdata('user_id');
        
        // Verificar se é uma requisição POST
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Método de requisição inválido.');
            redirect('responses');
            return;
        }
        
        // Obter filtros do formulário
        $filters = array();
        $questionnaire_id = $this->input->post('questionnaire_id');
        
        if (empty($questionnaire_id)) {
            $this->session->set_flashdata('error', 'Por favor, selecione um questionário.');
            redirect('responses');
            return;
        }
        
        if ($questionnaire_id && $questionnaire_id !== 'all') {
            $filters['questionnaire_id'] = $questionnaire_id;
            
            // Verificar se o questionário existe
            $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
            if (!$questionnaire) {
                $this->session->set_flashdata('error', 'Questionário não encontrado.');
                redirect('responses');
                return;
            }
        }
        
        if ($this->input->post('date_from')) {
            $filters['date_from'] = $this->input->post('date_from');
        }
        
        if ($this->input->post('date_to')) {
            $filters['date_to'] = $this->input->post('date_to');
        }
        
        if ($this->input->post('applied_by')) {
            $filters['applied_by'] = $this->input->post('applied_by');
        }
        
        // Validar filtros
        $validation_errors = $this->Response_model->validate_export_filters($filters);
        if (!empty($validation_errors)) {
            $this->Response_model->log_export_activity(
                $user_id, 
                'raw_data', 
                $filters, 
                0, 
                'validation_error', 
                implode('; ', $validation_errors)
            );
            
            $this->session->set_flashdata('error', implode('<br>', $validation_errors));
            redirect('responses');
            return;
        }
        
        try {
            // Contar registros antes da exportação
            $record_count = $this->Response_model->count_by_filters($filters);
            
            // Log início da exportação
            $this->Response_model->log_export_activity(
                $user_id, 
                'raw_data', 
                $filters, 
                $record_count, 
                'started'
            );
            
            // Aumentar limites para exportação
            ini_set('memory_limit', '2024M');
            ini_set('max_execution_time', 600);
            
            // IMPORTANTE: Limpar qualquer output anterior e iniciar buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            
            // Gerar arquivo Excel com dados brutos (versão nativa CORRIGIDA)
            $this->_generate_raw_data_excel_native($filters, $questionnaire_id);
            
            // Log sucesso da exportação
            $this->Response_model->log_export_activity(
                $user_id, 
                'raw_data', 
                $filters, 
                $record_count, 
                'success'
            );
            
        } catch (Exception $e) {
            // Limpar buffer em caso de erro
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Log erro da exportação
            $this->Response_model->log_export_activity(
                $user_id, 
                'raw_data', 
                $filters, 
                0, 
                'error', 
                $e->getMessage()
            );
            
            log_message('error', 'Erro na exportação de dados brutos: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Erro ao gerar exportação. Contate o administrador se o problema persistir.');
            redirect('responses');
        }
    }

    /**
     * Método nativo CORRIGIDO para gerar arquivo Excel usando XML
     */
    private function _generate_raw_data_excel_native($filters, $questionnaire_id) {
        // Obter dados das respostas
        $raw_data = $this->Response_model->get_raw_data_for_export($filters);
        
        if (empty($raw_data)) {
            $this->session->set_flashdata('warning', 'Nenhum dado encontrado para exportar com os filtros selecionados.');
            redirect('responses');
            return;
        }
        
        // Log do número de registros
        log_message('info', 'Exportando ' . count($raw_data) . ' registros');
        
        try {
            // Definir nome do arquivo
            $filename = 'dados_brutos_' . date('Y-m-d_H-i-s') . '.xls';
            if ($questionnaire_id && $questionnaire_id !== 'all') {
                $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
                if ($questionnaire) {
                    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $questionnaire->title);
                    $filename = 'dados_brutos_' . $safe_title . '_' . date('Y-m-d_H-i-s') . '.xls';
                }
            }
            
            // Gerar conteúdo Excel XML
            $excel_content = $this->_generate_excel_xml($raw_data);
            
            // Limpar buffer anterior
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configurar headers para download
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Length: ' . strlen($excel_content));
            
            // Enviar conteúdo
            echo $excel_content;
            
            // Log do arquivo gerado
            log_message('info', 'Arquivo Excel gerado: ' . $filename . ' com ' . count($raw_data) . ' registros');
            
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar arquivo Excel: ' . $e->getMessage());
            throw new Exception('Erro ao gerar arquivo Excel: ' . $e->getMessage());
        }
    }
    
    /**
     * Gerar XML compatível com Excel (VERSÃO CORRIGIDA)
     */
    private function _generate_excel_xml($raw_data) {
        $headers = $this->_get_export_headers();
        
        // Início do XML Excel
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        // Estilos
        $xml .= $this->_get_excel_styles();
        
        // Planilha de Resultados
        $xml .= '<Worksheet ss:Name="Resultados">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Definir larguras das colunas
        foreach ($headers as $header) {
            $width = $this->_get_column_width($header);
            $xml .= '<Column ss:Width="' . $width . '"/>' . "\n";
        }
        
        // Headers
        $xml .= '<Row ss:StyleID="HeaderStyle">' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . $this->_escape_xml($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Dados
        foreach ($raw_data as $response_data) {
            $mapped_data = $this->_map_response_to_excel_format($response_data);
            
            $xml .= '<Row>' . "\n";
            foreach ($mapped_data as $value) {
                // Sanitizar e validar valor
                $cell_value = $this->_sanitize_cell_value($value);
                $cell_type = $this->_get_cell_type($cell_value);
                
                $xml .= '<Cell><Data ss:Type="' . $cell_type . '">' . 
                        $this->_escape_xml($cell_value) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        // Planilha de Estatísticas
        $xml .= $this->_generate_statistics_worksheet($raw_data);
        
        $xml .= '</Workbook>';
        
        return $xml;
    }
    
    /**
     * NOVO: Sanitizar valor da célula (resolve problemas de conversão)
     */
    private function _sanitize_cell_value($value) {
        // Tratar null
        if (is_null($value)) {
            return 'N/A';
        }
        
        // Tratar arrays
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }
        
        // Tratar objetos
        if (is_object($value)) {
            return 'Objeto não convertível';
        }
        
        // Tratar booleanos
        if (is_bool($value)) {
            return $value ? 'SIM' : 'NÃO';
        }
        
        // Converter para string
        $str_value = strval($value);
        
        // Limitar tamanho (Excel tem limite de ~32000 caracteres por célula)
        if (strlen($str_value) > 32000) {
            $str_value = substr($str_value, 0, 31997) . '...';
        }
        
        return $str_value;
    }
    
    /**
     * NOVO: Determinar tipo da célula
     */
    private function _get_cell_type($value) {
        // Se é número (mas não string que parece número como CPF)
        if (is_numeric($value) && !preg_match('/^0/', $value) && strlen($value) < 15) {
            return 'Number';
        }
        
        return 'String';
    }
    
    /**
     * NOVO: Escape seguro para XML
     */
    private function _escape_xml($value) {
        // Converter para string primeiro
        $str_value = $this->_sanitize_cell_value($value);
        
        // Escape básico XML
        $escaped = htmlspecialchars($str_value, ENT_XML1 | ENT_COMPAT, 'UTF-8', false);
        
        // Remover caracteres de controle que podem quebrar o XML
        $escaped = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $escaped);
        
        return $escaped;
    }
    
    /**
     * Gerar estilos Excel XML
     */
    private function _get_excel_styles() {
        return '<Styles>
            <Style ss:ID="HeaderStyle">
                <Font ss:Bold="1" ss:Size="12"/>
                <Interior ss:Color="#D9EDF7" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            </Style>
            <Style ss:ID="DataStyle">
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>' . "\n";
    }
    
    /**
     * Determinar largura da coluna baseada no header
     */
    private function _get_column_width($header) {
        $widths = array(
            'TÉCNICO RESPONSÁVEL PELA APLICAÇÃO' => 200,
            'DATA' => 80,
            'NOME' => 150,
            'COMUNIDADE' => 150,
            'CPF' => 120,
            'EMAIL' => 180,
            'IDADE' => 60,
            'SEXO' => 80,
            'LATITUDE' => 100,
            'LONGITUDE' => 100,
            'LOCALIZAÇÃO' => 200,
            'QUESTIONÁRIO' => 180,
            'CONSENTIMENTO DADO' => 120,
            'STATUS SINCRONIZAÇÃO' => 120,
            'DATA INÍCIO' => 120,
            'DATA CONCLUSÃO' => 120,
            'FOTO CAPTURADA' => 100,
            'RESPOSTAS_JSON' => 300,
            'GLOBALRECORDID' => 150
        );
        
        return isset($widths[$header]) ? $widths[$header] : 100;
    }
    
    /**
     * Gerar planilha de estatísticas (VERSÃO CORRIGIDA)
     */
    private function _generate_statistics_worksheet($raw_data) {
        // Calcular estatísticas
        $total_responses = count($raw_data);
        $total_with_consent = 0;
        $total_with_location = 0;
        $total_with_photo = 0;
        $applicators = array();
        
        foreach ($raw_data as $response) {
            if ($response->consent_given) $total_with_consent++;
            if (!empty($response->latitude) && !empty($response->longitude)) $total_with_location++;
            if (!empty($response->photo_path)) $total_with_photo++;
            
            $applicator = $response->applied_by_name ?? 'Não informado';
            $applicators[$applicator] = ($applicators[$applicator] ?? 0) + 1;
        }
        
        $xml = '<Worksheet ss:Name="Estatísticas">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Título
        $xml .= '<Row>' . "\n";
        $xml .= '<Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">Estatísticas da Exportação</Data></Cell>' . "\n";
        $xml .= '<Cell></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Linha em branco
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        
        // Estatísticas gerais
        $stats = array(
            'Total de Respostas' => $total_responses,
            'Com Consentimento' => $total_with_consent,
            'Com Localização' => $total_with_location,
            'Com Foto' => $total_with_photo,
            'Taxa de Consentimento (%)' => $total_responses > 0 ? round(($total_with_consent / $total_responses) * 100, 1) : 0,
            'Taxa de Localização (%)' => $total_responses > 0 ? round(($total_with_location / $total_responses) * 100, 1) : 0,
            'Taxa de Fotos (%)' => $total_responses > 0 ? round(($total_with_photo / $total_responses) * 100, 1) : 0
        );
        
        foreach ($stats as $label => $value) {
            $xml .= '<Row>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->_escape_xml($label) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . $value . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        // Linha em branco
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        
        // Aplicadores
        $xml .= '<Row>' . "\n";
        $xml .= '<Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">Respostas por Aplicador</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">Quantidade</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        foreach ($applicators as $applicator => $count) {
            $xml .= '<Row>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->_escape_xml($applicator) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        return $xml;
    }
    
    /**
     * Definir headers do arquivo Excel baseado no modelo
     */
    private function _get_export_headers() {
        return [
            'TÉCNICO RESPONSÁVEL PELA APLICAÇÃO',
            'DATA',
            'NOME',
            'COMUNIDADE',
            'CPF',
            'EMAIL',
            'IDADE',
            'SEXO',
            'LATITUDE',
            'LONGITUDE',
            'LOCALIZAÇÃO',
            'QUESTIONÁRIO',
            'CONSENTIMENTO DADO',
            'STATUS SINCRONIZAÇÃO',
            'DATA INÍCIO',
            'DATA CONCLUSÃO',
            'FOTO CAPTURADA',
            'RESPOSTAS_JSON',
            'GLOBALRECORDID'
        ];
    }
    
    /**
     * Mapear dados da resposta para formato Excel (VERSÃO CORRIGIDA)
     */
    private function _map_response_to_excel_format($response_data) {
        return [
            $this->_get_safe_value($response_data, 'applied_by_name'),
            $this->_format_date($response_data->completed_at),
            $this->_get_safe_value($response_data, 'respondent_name'),
            $this->_get_safe_value($response_data, 'location_name'),
            $this->_get_safe_value($response_data, 'respondent_cpf'),
            $this->_get_safe_value($response_data, 'respondent_email'),
            $this->_get_safe_value($response_data, 'respondent_age'),
            $this->_get_safe_value($response_data, 'respondent_gender'),
            $this->_get_safe_value($response_data, 'latitude'),
            $this->_get_safe_value($response_data, 'longitude'),
            $this->_get_safe_value($response_data, 'location_name'),
            $this->_get_safe_value($response_data, 'questionnaire_title'),
            $response_data->consent_given ? 'SIM' : 'NÃO',
            strtoupper($this->_get_safe_value($response_data, 'sync_status', 'UNKNOWN')),
            $this->_format_datetime($response_data->started_at),
            $this->_format_datetime($response_data->completed_at),
            !empty($response_data->photo_path) ? 'SIM' : 'NÃO',
            $this->_sanitize_json_for_excel($response_data->answers_json ?? '{}'),
            $response_data->id . '-' . uniqid()
        ];
    }
    
    /**
     * NOVO: Obter valor seguro de propriedade
     */
    private function _get_safe_value($object, $property, $default = 'N/A') {
        if (isset($object->$property) && !is_null($object->$property)) {
            $value = $object->$property;
            
            // Se for array, converter para string
            if (is_array($value)) {
                return implode(', ', $value);
            }
            
            // Se for objeto, tentar converter
            if (is_object($value)) {
                return method_exists($value, '__toString') ? strval($value) : $default;
            }
            
            return strval($value);
        }
        
        return $default;
    }
    
    /**
     * NOVO: Formatar data segura
     */
    private function _format_date($date_string) {
        if (empty($date_string)) {
            return 'N/A';
        }
        
        try {
            return date('d/m/Y', strtotime($date_string));
        } catch (Exception $e) {
            return 'Data inválida';
        }
    }
    
    /**
     * NOVO: Formatar data e hora segura
     */
    private function _format_datetime($datetime_string) {
        if (empty($datetime_string)) {
            return 'N/A';
        }
        
        try {
            return date('d/m/Y H:i', strtotime($datetime_string));
        } catch (Exception $e) {
            return 'Data inválida';
        }
    }
    
    /**
     * Sanitizar JSON para Excel CORRIGIDO (resolve erro de Array to string conversion)
     */
    private function _sanitize_json_for_excel($json_string) {
        // Verificar se é null ou vazio
        if (empty($json_string)) {
            return 'Sem dados';
        }
        
        // Se já é uma string simples, retornar
        if (!is_string($json_string)) {
            // Se é array, converter para string
            if (is_array($json_string)) {
                return 'Array: ' . implode(', ', array_map('strval', $json_string));
            }
            
            // Se é objeto, tentar converter
            if (is_object($json_string)) {
                $json_string = json_encode($json_string);
            } else {
                // Converter qualquer outro tipo para string
                $json_string = strval($json_string);
            }
        }
        
        // Tentar decodificar JSON
        $data = json_decode($json_string, true);
        
        // Se não conseguiu decodificar, retornar string truncada
        if (json_last_error() !== JSON_ERROR_NONE) {
            $clean_string = preg_replace('/[^\x20-\x7E\x{00A0}-\x{FFFF}]/u', '', $json_string);
            return strlen($clean_string) > 100 ? substr($clean_string, 0, 97) . '...' : $clean_string;
        }
        
        // Se decodificou com sucesso e é array
        if (is_array($data)) {
            $simplified = array();
            
            foreach ($data as $item) {
                if (is_array($item)) {
                    // Tratar item como array
                    $question = isset($item['question_text']) ? $item['question_text'] : 'Pergunta';
                    $answer = 'Sem resposta';
                    
                    if (isset($item['response_value'])) {
                        $response_value = $item['response_value'];
                        
                        // Se response_value é array
                        if (is_array($response_value)) {
                            $answer = implode(', ', array_map('strval', $response_value));
                        } else {
                            $answer = strval($response_value);
                        }
                    }
                    
                    $simplified[] = $question . ': ' . $answer;
                } else {
                    // Item não é array, converter para string
                    $simplified[] = strval($item);
                }
            }
            
            $result = implode(' | ', $simplified);
            
            // Limitar tamanho para Excel
            return strlen($result) > 500 ? substr($result, 0, 497) . '...' : $result;
        }
        
        return 'Dados não interpretáveis';
    }

    /**
     * AJAX: Contar respostas baseado nos filtros selecionados
     */
    public function ajax_count_responses() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $filters = array();
        
        if ($this->input->post('questionnaire_id') && $this->input->post('questionnaire_id') !== 'all') {
            $filters['questionnaire_id'] = $this->input->post('questionnaire_id');
        }
        
        if ($this->input->post('date_from')) {
            $filters['date_from'] = $this->input->post('date_from');
        }
        
        if ($this->input->post('date_to')) {
            $filters['date_to'] = $this->input->post('date_to');
        }
        
        if ($this->input->post('applied_by')) {
            $filters['applied_by'] = $this->input->post('applied_by');
        }
        
        $count = $this->Response_model->count_by_filters($filters);
        $statistics = $this->Response_model->get_export_statistics($filters);
        
        $response = array(
            'success' => true,
            'total_responses' => $count,
            'statistics' => $statistics
        );
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    /**
     * AJAX: Preview dos dados que serão exportados
     */
    public function ajax_preview_export() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $filters = array();
        
        if ($this->input->post('questionnaire_id') && $this->input->post('questionnaire_id') !== 'all') {
            $filters['questionnaire_id'] = $this->input->post('questionnaire_id');
        }
        
        if ($this->input->post('date_from')) {
            $filters['date_from'] = $this->input->post('date_from');
        }
        
        if ($this->input->post('date_to')) {
            $filters['date_to'] = $this->input->post('date_to');
        }
        
        if ($this->input->post('applied_by')) {
            $filters['applied_by'] = $this->input->post('applied_by');
        }
        
        try {
            $preview_data = $this->Response_model->get_export_preview($filters, 5);
            $count = $this->Response_model->count_by_filters($filters);
            
            $response = array(
                'success' => true,
                'preview_data' => $preview_data,
                'total_count' => $count,
                'message' => "Prévia de {$count} registros encontrados"
            );
            
        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'message' => 'Erro ao gerar prévia: ' . $e->getMessage()
            );
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    /**
     * AJAX: Validar filtros antes da exportação
     */
    public function ajax_validate_export() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $filters = array();
        
        if ($this->input->post('questionnaire_id') && $this->input->post('questionnaire_id') !== 'all') {
            $filters['questionnaire_id'] = $this->input->post('questionnaire_id');
        }
        
        if ($this->input->post('date_from')) {
            $filters['date_from'] = $this->input->post('date_from');
        }
        
        if ($this->input->post('date_to')) {
            $filters['date_to'] = $this->input->post('date_to');
        }
        
        if ($this->input->post('applied_by')) {
            $filters['applied_by'] = $this->input->post('applied_by');
        }
        
        $validation_errors = $this->Response_model->validate_export_filters($filters);
        
        if (empty($validation_errors)) {
            $count = $this->Response_model->count_by_filters($filters);
            $statistics = $this->Response_model->get_export_statistics($filters);
            
            $response = array(
                'success' => true,
                'message' => "Validação concluída. {$count} registros serão exportados.",
                'count' => $count,
                'statistics' => $statistics
            );
        } else {
            $response = array(
                'success' => false,
                'errors' => $validation_errors,
                'message' => implode(' ', $validation_errors)
            );
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Visualizar histórico de exportações
     */
    public function export_history() {
        $data['title'] = 'Histórico de Exportações - SXData';
        
        $user_id = $this->session->userdata('user_id');
        $is_admin = $this->session->userdata('user_role') === 'administrador';
        
        if ($is_admin) {
            // Administradores podem ver todo o histórico
            $data['export_history'] = $this->Response_model->get_export_usage_statistics(30);
        } else {
            // Usuários normais veem apenas seu histórico
            $data['export_history'] = $this->Response_model->get_user_export_history($user_id, 20);
        }
        
        $data['export_limits'] = $this->Response_model->check_export_limits($user_id);
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/responses/export_history', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Obter localização via coordenadas (geocodificação reversa)
     */
    public function get_location()
    {
        // Verificar se é uma requisição POST
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
        
        $latitude = $this->input->post('latitude');
        $longitude = $this->input->post('longitude');
        
        // Validar entrada
        if (empty($latitude) || empty($longitude) || 
            !is_numeric($latitude) || !is_numeric($longitude)) {
            echo 'N/A';
            return;
        }
        
        // Validar range das coordenadas
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            echo 'N/A';
            return;
        }
        
        try {
            $location_name = $this->get_location_name_api($latitude, $longitude);
            echo $location_name;
        } catch (Exception $e) {
            log_message('error', 'Erro na geocodificação: ' . $e->getMessage());
            echo 'Erro ao carregar';
        }
    }

    /**
     * Método privado para geocodificação usando API Nominatim
     */
    private function get_location_name_api($latitude, $longitude)
    {
        // Verificar cache primeiro (usando cache do CodeIgniter se disponível)
        $cache_key = 'location_' . round($latitude, 3) . '_' . round($longitude, 3);
        
        if ($this->cache) {
            $cached_result = $this->cache->get($cache_key);
            if ($cached_result !== FALSE) {
                return $cached_result;
            }
        }
        
        try {
            // URL da API Nominatim
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=16&addressdetails=1&accept-language=pt-BR,pt,en";
            
            // Configurar contexto da requisição
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: SXData-App/1.0 (contact@sxdata.com)',
                        'Accept: application/json',
                        'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'
                    ],
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);
            
            if ($response === FALSE) {
                return 'N/A';
            }

            $data = json_decode($response, true);
            
            if (isset($data['display_name'])) {
                $formatted_name = $this->format_location_name_api($data);
                
                // Salvar no cache por 1 hora se o resultado for válido
                if ($this->cache && $formatted_name !== 'N/A') {
                    $this->cache->save($cache_key, $formatted_name, 3600);
                }
                
                return $formatted_name;
            }

        } catch (Exception $e) {
            log_message('error', 'Erro na API de geocodificação: ' . $e->getMessage());
        }

        return 'N/A';
    }

    /**
     * Formatar nome da localização retornado pela API
     */
    private function format_location_name_api($data)
    {
        if (!isset($data['address'])) {
            return isset($data['display_name']) ? substr($data['display_name'], 0, 50) . '...' : 'N/A';
        }

        $address = $data['address'];
        $location_parts = [];

        // Priorizar informações mais específicas para o contexto brasileiro
        if (!empty($address['road'])) {
            $location_parts[] = $address['road'];
        }
        
        if (!empty($address['suburb']) || !empty($address['neighbourhood'])) {
            $location_parts[] = $address['suburb'] ?? $address['neighbourhood'];
        }
        
        if (!empty($address['city']) || !empty($address['town']) || !empty($address['village'])) {
            $location_parts[] = $address['city'] ?? $address['town'] ?? $address['village'];
        }
        
        if (!empty($address['state'])) {
            $location_parts[] = $address['state'];
        }

        $result = !empty($location_parts) ? implode(', ', $location_parts) : $data['display_name'];
        
        // Limitar o tamanho da string retornada
        if (strlen($result) > 50) {
            $result = substr($result, 0, 47) . '...';
        }
        
        return $result;
    }

    /**
     * Método alternativo usando cURL (mais robusto)
     */
    private function get_location_name_curl($latitude, $longitude)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=16&addressdetails=1&accept-language=pt-BR";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SXData-App/1.0 (contact@sxdata.com)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === FALSE || $http_code !== 200) {
            return 'N/A';
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['display_name'])) {
            return $this->format_location_name_api($data);
        }
        
        return 'N/A';
    }

    /**
     * Verificar autenticação do usuário
     */
    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }
}