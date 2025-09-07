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
     * NOVO: Exportar dados brutos conforme modelo fornecido
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
        /*    $record_count = $this->Response_model->count_by_filters($filters);
            
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
            
            // Gerar arquivo Excel com dados brutos
            $this->_generate_raw_data_excel($filters, $questionnaire_id);
            
            // Log sucesso da exportação
            $this->Response_model->log_export_activity(
                $user_id, 
                'raw_data', 
                $filters, 
                $record_count, 
                'success'
            ); */
            
        } catch (Exception $e) {
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
     * Método privado para gerar arquivo Excel com dados brutos
     */
    private function _generate_raw_data_excel($filters, $questionnaire_id) {
        // Verificar se PhpSpreadsheet está disponível
        $autoload_path = APPPATH . 'third_party/PhpSpreadsheet/vendor/autoload.php';
        if (!file_exists($autoload_path)) {
            throw new Exception('Biblioteca PhpSpreadsheet não encontrada. Instale via Composer ou faça download manual.');
        }
        
        require_once $autoload_path;
        
        // Obter dados das respostas com todas as informações necessárias
        $raw_data = $this->Response_model->get_raw_data_for_export($filters);
        
        if (empty($raw_data)) {
            $this->session->set_flashdata('warning', 'Nenhum dado encontrado para exportar com os filtros selecionados.');
            redirect('responses');
            return;
        }
        
        // Log do número de registros
        log_message('info', 'Exportando ' . count($raw_data) . ' registros');
        
        try {
            // Criar arquivo Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Resultados');
            
            // Definir headers baseados no modelo fornecido
            $headers = $this->_get_export_headers();
            
            // Configurar headers
            $col = 1;
            foreach ($headers as $header) {
                $worksheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }
            
            // Aplicar estilo aos headers
            $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
            $worksheet->getStyle($headerRange)->getFont()->setBold(true);
            $worksheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9EDF7');
            
            // Adicionar bordas aos headers
            $worksheet->getStyle($headerRange)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Preencher dados
            $row = 2;
            foreach ($raw_data as $response_data) {
                $col = 1;
                
                // Mapear dados conforme estrutura do modelo
                $mapped_data = $this->_map_response_to_excel_format($response_data);
                
                foreach ($mapped_data as $value) {
                    // Tratar valores especiais
                    if (is_null($value)) {
                        $value = 'N/A';
                    }
                    
                    $worksheet->setCellValueByColumnAndRow($col, $row, $value);
                    $col++;
                }
                
                $row++;
                
                // Liberar memória a cada 100 linhas para arquivos grandes
                if ($row % 100 == 0) {
                    gc_collect_cycles();
                }
            }
            
            // Auto-ajustar largura das colunas (limitado para evitar colunas muito largas)
            foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers))) as $columnID) {
                $worksheet->getColumnDimension($columnID)->setAutoSize(true);
                // Limitar largura máxima
                if ($worksheet->getColumnDimension($columnID)->getWidth() > 50) {
                    $worksheet->getColumnDimension($columnID)->setWidth(50);
                }
            }
            
            // Criar segunda planilha para gráficos (como no modelo)
            $graphSheet = $spreadsheet->createSheet();
            $graphSheet->setTitle('Gráficos');
            
            // Adicionar alguns dados básicos de estatísticas na planilha de gráficos
            $this->_add_statistics_to_graph_sheet($graphSheet, $raw_data);
            
            // Definir nome do arquivo
            $filename = 'dados_brutos_' . date('Y-m-d_H-i-s') . '.xlsx';
            if ($questionnaire_id && $questionnaire_id !== 'all') {
                $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
                if ($questionnaire) {
                    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $questionnaire->title);
                    $filename = 'dados_brutos_' . $safe_title . '_' . date('Y-m-d_H-i-s') . '.xlsx';
                }
            }
            
            // Log do arquivo gerado
            log_message('info', 'Arquivo Excel gerado: ' . $filename . ' com ' . count($raw_data) . ' registros');
            
            // Configurar headers para download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Criar writer e fazer download
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            
            // Limpar memória
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
            
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar arquivo Excel: ' . $e->getMessage());
            throw new Exception('Erro ao gerar arquivo Excel: ' . $e->getMessage());
        }
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
     * Mapear dados da resposta para formato Excel
     */
    private function _map_response_to_excel_format($response_data) {
        return [
            $response_data->applied_by_name ?? 'N/A',
            $response_data->completed_at ? date('d/m/Y', strtotime($response_data->completed_at)) : 'N/A',
            $response_data->respondent_name ?? 'N/A',
            $response_data->location_name ?? 'N/A',
            $response_data->respondent_cpf ?? 'N/A',
            $response_data->respondent_email ?? 'N/A',
            $response_data->respondent_age ?? 'N/A',
            $response_data->respondent_gender ?? 'N/A',
            $response_data->latitude ?? 'N/A',
            $response_data->longitude ?? 'N/A',
            $response_data->location_name ?? 'N/A',
            $response_data->questionnaire_title ?? 'N/A',
            $response_data->consent_given ? 'SIM' : 'NÃO',
            strtoupper($response_data->sync_status ?? 'UNKNOWN'),
            $response_data->started_at ? date('d/m/Y H:i', strtotime($response_data->started_at)) : 'N/A',
            $response_data->completed_at ? date('d/m/Y H:i', strtotime($response_data->completed_at)) : 'N/A',
            !empty($response_data->photo_path) ? 'SIM' : 'NÃO',
            $response_data->answers_json ?? '{}',
            $response_data->id . '-' . uniqid()
        ];
    }
    
    /**
     * Adicionar estatísticas básicas na planilha de gráficos
     */
    private function _add_statistics_to_graph_sheet($sheet, $raw_data) {
        $sheet->setCellValue('A1', 'Estatísticas Básicas');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Contar totais
        $total_responses = count($raw_data);
        $total_with_consent = 0;
        $total_with_location = 0;
        $total_with_photo = 0;
        $applicators = [];
        
        foreach ($raw_data as $response) {
            if ($response->consent_given) $total_with_consent++;
            if (!empty($response->latitude) && !empty($response->longitude)) $total_with_location++;
            if (!empty($response->photo_path)) $total_with_photo++;
            
            $applicator = $response->applied_by_name ?? 'Não informado';
            $applicators[$applicator] = ($applicators[$applicator] ?? 0) + 1;
        }
        
        // Adicionar estatísticas
        $row = 3;
        $sheet->setCellValue('A' . $row, 'Total de Respostas:');
        $sheet->setCellValue('B' . $row, $total_responses);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Com Consentimento:');
        $sheet->setCellValue('B' . $row, $total_with_consent);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Com Localização:');
        $sheet->setCellValue('B' . $row, $total_with_location);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Com Foto:');
        $sheet->setCellValue('B' . $row, $total_with_photo);
        $row += 2;
        
        // Aplicadores
        $sheet->setCellValue('A' . $row, 'Respostas por Aplicador:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($applicators as $applicator => $count) {
            $sheet->setCellValue('A' . $row, $applicator);
            $sheet->setCellValue('B' . $row, $count);
            $row++;
        }
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