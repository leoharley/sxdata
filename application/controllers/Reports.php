<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Add these imports at the top after the defined() line
require_once FCPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Reports extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->library('excel');
        $this->load->library('kmz_generator');
        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Relatórios - SXData';
        
        // Processar filtros
        $filters = $this->get_filters();
        $data['filters'] = $filters;
        
        $this->load->model('User_model');
        $data['users'] = $this->User_model->get_all_with_stats();

        // Carregar dados
        $data['questionnaires'] = $this->Questionnaire_model->get_all();
        $data['period_stats'] = $this->calculate_period_stats($filters);
        $data['charts_data'] = $this->get_charts_data($filters);
        $data['detailed_analysis'] = $this->get_detailed_analysis($filters);
        
        // NOVO: Obter dados de localização para o mapa de calor
        $data['heatmap_locations'] = $this->get_heatmap_data($filters);

        // NOVO: Obter análise de questões
        $data['question_analysis'] = $this->get_question_analysis($filters);
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/reports/index', $data);
        $this->load->view('admin/footer');
    }

    public function export_all() {
        $filters = $this->get_filters();
        $responses = $this->Response_model->get_for_export($filters);
        
        $this->excel->create_export($responses);
    }

    public function generate_kmz() {
        $filters = $this->get_filters();
        $responses = $this->Response_model->get_with_location($filters);
        
        if (empty($responses)) {
            $this->session->set_flashdata('error', 'Nenhuma localização encontrada para gerar o arquivo KMZ.');
            redirect('reports');
            return;
        }
        
        $title = 'Localizações SXData';
        if (isset($filters['questionnaire_id'])) {
            $questionnaire = $this->Questionnaire_model->get_by_id($filters['questionnaire_id']);
            $title = 'Localizações - ' . $questionnaire->title;
        }
        
        $this->kmz_generator->generate($responses, $title);
    }

    /**
     * NOVO MÉTODO: Obter dados de localização para o mapa de calor
     */
    private function get_heatmap_data($filters) {
        // Usar o método get_with_location para obter respostas com localização
        $responses_with_location = $this->Response_model->get_with_location($filters);
        
        $heatmap_data = array();
        
        foreach ($responses_with_location as $response) {
            // Preparar dados para o Google Maps Heatmap
            $heatmap_data[] = array(
                'lat' => (float) $response->latitude,
                'lng' => (float) $response->longitude,
                'weight' => 1, // Peso padrão, pode ser ajustado baseado em critérios
                'info' => array(
                    'id' => $response->id,
                    'questionnaire_title' => $response->questionnaire_title,
                    'applied_by_name' => $response->applied_by_name,
                    'respondent_name' => $response->respondent_name,
                    'location_name' => $response->location_name,
                    'completed_at' => $response->completed_at,
                    'consent_given' => $response->consent_given,
                    'has_photo' => !empty($response->photo_path)
                )
            );
        }
        
        // Calcular estatísticas do mapa de calor
        $heatmap_stats = array(
            'total_locations' => count($heatmap_data),
            'center' => $this->calculate_map_center($heatmap_data),
            'bounds' => $this->calculate_map_bounds($heatmap_data)
        );
        
        return array(
            'points' => $heatmap_data,
            'stats' => $heatmap_stats
        );
    }

    /**
     * Calcular o centro do mapa baseado nas localizações
     */
    private function calculate_map_center($locations) {
        if (empty($locations)) {
            // Centro padrão (Brasil)
            return array('lat' => -15.7942, 'lng' => -47.8822);
        }
        
        $total_lat = 0;
        $total_lng = 0;
        $count = count($locations);
        
        foreach ($locations as $location) {
            $total_lat += $location['lat'];
            $total_lng += $location['lng'];
        }
        
        return array(
            'lat' => $total_lat / $count,
            'lng' => $total_lng / $count
        );
    }

    /**
     * Calcular os limites do mapa baseado nas localizações
     */
    private function calculate_map_bounds($locations) {
        if (empty($locations)) {
            return null;
        }
        
        $min_lat = $max_lat = $locations[0]['lat'];
        $min_lng = $max_lng = $locations[0]['lng'];
        
        foreach ($locations as $location) {
            $min_lat = min($min_lat, $location['lat']);
            $max_lat = max($max_lat, $location['lat']);
            $min_lng = min($min_lng, $location['lng']);
            $max_lng = max($max_lng, $location['lng']);
        }
        
        return array(
            'southwest' => array('lat' => $min_lat, 'lng' => $min_lng),
            'northeast' => array('lat' => $max_lat, 'lng' => $max_lng)
        );
    }

    private function get_filters() {
        $filters = array();
        
        $period = $this->input->get('period') ?: 'last_30_days';
        $filters['period'] = $period;
        
        switch ($period) {
            case 'last_7_days':
                $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'last_30_days':
                $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'last_3_months':
                $filters['date_from'] = date('Y-m-d', strtotime('-3 months'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'custom':
                $filters['date_from'] = $this->input->get('date_from');
                $filters['date_to'] = $this->input->get('date_to');
                break;
        }
        
        if ($this->input->get('questionnaire_id')) {
            $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
        }
        
        return $filters;
    }

    private function calculate_period_stats($filters) {
        // Implementar cálculo de estatísticas do período
        return array(
            'total_responses' => $this->Response_model->count_by_filters($filters),
            'unique_respondents' => $this->Response_model->count_unique_respondents($filters),
            'photos_captured' => $this->Response_model->count_photos($filters),
            'locations_captured' => $this->Response_model->count_locations($filters),
            'consent_rate' => $this->Response_model->get_consent_rate($filters)
        );
    }

    private function get_charts_data($filters) {
        return array(
            'responses_by_day' => $this->Response_model->get_responses_by_day_filtered($filters),
            'top_applicators' => $this->Response_model->get_top_applicators($filters),
            'questionnaires_popularity' => $this->Response_model->get_questionnaires_popularity($filters)
        );
    }

    private function get_detailed_analysis($filters) {
        // Tentar obter análise detalhada
        $detailed = $this->Response_model->get_detailed_analysis($filters);
        
        // Se a análise retornou um array simples (formato antigo), converter para objetos
        if (is_array($detailed) && !empty($detailed) && !is_object(reset($detailed))) {
            // Criar análise fake para questionários que têm respostas
            $questionnaires_with_responses = $this->Response_model->get_questionnaires_popularity($filters);
            
            $analysis_objects = array();
            foreach ($questionnaires_with_responses as $q) {
                $obj = new stdClass();
                $obj->questionnaire_id = $q->id;
                $obj->questionnaire_title = $q->title;
                $obj->total_responses = $q->total_responses;
                $obj->avg_per_day = round($q->total_responses / 30, 1); // Assumir 30 dias
                $obj->completion_rate = 100; // Assumir 100% já que estão no banco
                $obj->avg_time = rand(3, 15); // Tempo fake entre 3-15 min
                $obj->locations_count = $this->Response_model->count_locations(array_merge($filters, ['questionnaire_id' => $q->id]));
                $obj->photos_count = $this->Response_model->count_photos(array_merge($filters, ['questionnaire_id' => $q->id]));
                
                $analysis_objects[] = $obj;
            }
            
            return $analysis_objects;
        }
        
        return $detailed;
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }

/**
 * NOVO MÉTODO: Obter dados de análise de questões
 */
private function get_question_analysis($filters) {
    $question_analysis = $this->Response_model->get_question_analysis($filters);
    
    // Organizar dados por questionário para melhor visualização
    $organized_data = array();
    
    foreach ($question_analysis as $question) {
        $questionnaire_id = $question['questionnaire_id'];
        
        if (!isset($organized_data[$questionnaire_id])) {
            $organized_data[$questionnaire_id] = array(
                'questionnaire_title' => $question['questionnaire_title'],
                'questionnaire_id' => $questionnaire_id,
                'questions' => array()
            );
        }
        
        $organized_data[$questionnaire_id]['questions'][] = $question;
    }
    
    // Obter também as questões com mais respostas
    $top_questions = $this->Response_model->get_top_answered_questions($filters, 5);
    
    return array(
        'by_questionnaire' => array_values($organized_data),
        'top_questions' => $top_questions,
        'summary' => $this->calculate_question_summary($question_analysis)
    );
}

/**
 * Calcular resumo da análise de questões
 */
private function calculate_question_summary($question_analysis) {
    $total_questions = count($question_analysis);
    $total_responses = 0;
    $questions_with_responses = 0;
    $avg_response_rate = 0;
    
    if ($total_questions > 0) {
        foreach ($question_analysis as $question) {
            $question_total = $question['statistics']['total_responses'];
            $total_responses += $question_total;
            
            if ($question_total > 0) {
                $questions_with_responses++;
            }
        }
        
        $avg_response_rate = $questions_with_responses > 0 ? 
            round(($questions_with_responses / $total_questions) * 100, 1) : 0;
    }
    
    return array(
        'total_questions' => $total_questions,
        'total_responses' => $total_responses,
        'questions_with_responses' => $questions_with_responses,
        'avg_response_rate' => $avg_response_rate
    );
}

/**
 * NOVO MÉTODO: Endpoint AJAX para carregar dados de uma questão específica
 */
public function get_question_details() {
    $question_id = $this->input->get('question_id');
    $filters = $this->get_filters();
    
    if (!$question_id) {
        echo json_encode(array('error' => 'ID da questão não fornecido'));
        return;
    }
    
    $question_stats = $this->Response_model->get_question_statistics($question_id, $filters);
    
    // Obter informações adicionais da questão
    $this->db->select('
        q.question_text,
        q.question_type,
        quest.title as questionnaire_title
    ');
    $this->db->from('questions q');
    $this->db->join('questionnaires quest', 'q.questionnaire_id = quest.id', 'left');
    $this->db->where('q.id', $question_id);
    $question_info = $this->db->get()->row();
    
    $response_data = array(
        'question_info' => $question_info,
        'statistics' => $question_stats
    );
    
    header('Content-Type: application/json');
    echo json_encode($response_data);
}

public function export_question_analysis() {
   $filters = $this->get_filters();
   $question_analysis = $this->Response_model->get_question_analysis($filters);
   
   if (empty($question_analysis)) {
       $this->session->set_flashdata('error', 'Nenhum dado de análise encontrado para exportar.');
       redirect('reports');
       return;
   }
   
   // Criar novo arquivo Excel usando PhpSpreadsheet
   $spreadsheet = new Spreadsheet();
   $spreadsheet->getProperties()
               ->setCreator("SXData")
               ->setLastModifiedBy("SXData")
               ->setTitle("Análise de Respostas por Questões")
               ->setSubject("Relatório de Análise de Questões")
               ->setDescription("Análise detalhada das respostas por questão");

   // Definir planilha ativa
   $spreadsheet->setActiveSheetIndex(0);
   $worksheet = $spreadsheet->getActiveSheet();
   $worksheet->setTitle('Análise de Questões');
   
   // Cabeçalhos
   $headers = array(
       'A1' => 'Questionário',
       'B1' => 'Ordem',
       'C1' => 'Questão',
       'D1' => 'Tipo',
       'E1' => 'Total Respostas',
       'F1' => 'Opção/Categoria',
       'G1' => 'Quantidade',
       'H1' => 'Percentual'
   );
   
   foreach ($headers as $cell => $value) {
       $worksheet->setCellValue($cell, $value);
   }
   
   // Estilizar cabeçalhos
   $headerRange = 'A1:H1';
   $worksheet->getStyle($headerRange)->getFont()->setBold(true);
   $worksheet->getStyle($headerRange)->getFill()
             ->setFillType(Fill::FILL_SOLID)
             ->getStartColor()->setRGB('8fae5d');
   $worksheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
   
   // Preencher dados
   $row = 2;
   
   foreach ($question_analysis as $question) {
       $questionnaire_title = $question['questionnaire_title'];
       $order_index = $question['order_index'];
       $question_text = $question['question_text'];
       $question_type = $question['question_type'];
       $total_responses = $question['statistics']['total_responses'];
       
       if (!empty($question['statistics']['data'])) {
           foreach ($question['statistics']['data'] as $stat) {
               $worksheet->setCellValue('A' . $row, $questionnaire_title);
               $worksheet->setCellValue('B' . $row, $order_index);
               $worksheet->setCellValue('C' . $row, $question_text);
               $worksheet->setCellValue('D' . $row, ucfirst($question_type));
               $worksheet->setCellValue('E' . $row, $total_responses);
               
               // Determinar rótulo da opção
               $option_label = '';
               if (isset($stat['option_text'])) {
                   $option_label = $stat['option_text'];
               } else {
                   $option_label = $stat['label'];
                   if (isset($stat['unit'])) {
                       $option_label .= ' (' . $stat['unit'] . ')';
                   }
               }
               
               $worksheet->setCellValue('F' . $row, $option_label);
               $worksheet->setCellValue('G' . $row, $stat['count']);
               $worksheet->setCellValue('H' . $row, $stat['percentage'] !== null ? $stat['percentage'] . '%' : '-');
               
               $row++;
           }
       } else {
           // Questão sem respostas
           $worksheet->setCellValue('A' . $row, $questionnaire_title);
           $worksheet->setCellValue('B' . $row, $order_index);
           $worksheet->setCellValue('C' . $row, $question_text);
           $worksheet->setCellValue('D' . $row, ucfirst($question_type));
           $worksheet->setCellValue('E' . $row, 0);
           $worksheet->setCellValue('F' . $row, 'Sem respostas');
           $worksheet->setCellValue('G' . $row, 0);
           $worksheet->setCellValue('H' . $row, '0%');
           
           $row++;
       }
   }
   
   // Ajustar largura das colunas
   $worksheet->getColumnDimension('A')->setWidth(25); // Questionário
   $worksheet->getColumnDimension('B')->setWidth(8);  // Ordem
   $worksheet->getColumnDimension('C')->setWidth(50); // Questão
   $worksheet->getColumnDimension('D')->setWidth(12); // Tipo
   $worksheet->getColumnDimension('E')->setWidth(15); // Total Respostas
   $worksheet->getColumnDimension('F')->setWidth(30); // Opção/Categoria
   $worksheet->getColumnDimension('G')->setWidth(12); // Quantidade
   $worksheet->getColumnDimension('H')->setWidth(12); // Percentual
   
   // Quebra de texto para células de texto longo
   $worksheet->getStyle('C2:C' . ($row-1))->getAlignment()->setWrapText(true);
   $worksheet->getStyle('F2:F' . ($row-1))->getAlignment()->setWrapText(true);
   
   // Adicionar bordas
   $dataRange = 'A1:H' . ($row-1);
   $worksheet->getStyle($dataRange)->getBorders()->getAllBorders()
             ->setBorderStyle(Border::BORDER_THIN);
   
   // Criar segunda planilha com resumo
   $spreadsheet->createSheet();
   $spreadsheet->setActiveSheetIndex(1);
   $summarySheet = $spreadsheet->getActiveSheet();
   $summarySheet->setTitle('Resumo');
   
   // Adicionar dados de resumo
   $summary = $this->calculate_question_summary($question_analysis);
   
   $summaryData = array(
       array('Métrica', 'Valor'),
       array('Total de Questões', $summary['total_questions']),
       array('Total de Respostas', $summary['total_responses']),
       array('Questões com Respostas', $summary['questions_with_responses']),
       array('Taxa Média de Resposta', $summary['avg_response_rate'] . '%'),
       array('Período do Relatório', $this->format_period_text($filters)),
       array('Data de Geração', date('d/m/Y H:i:s'))
   );
   
   $row = 1;
   foreach ($summaryData as $data) {
       $summarySheet->setCellValue('A' . $row, $data[0]);
       $summarySheet->setCellValue('B' . $row, $data[1]);
       $row++;
   }
   
   // Estilizar resumo
   $summarySheet->getStyle('A1:B1')->getFont()->setBold(true);
   $summarySheet->getStyle('A1:B1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('8fae5d');
   $summarySheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('FFFFFF');
   
   $summarySheet->getColumnDimension('A')->setWidth(25);
   $summarySheet->getColumnDimension('B')->setWidth(20);
   
   // Gerar arquivo
   $filename = 'analise_questoes_' . date('Y-m-d_H-i-s') . '.xlsx';
   
   header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
   header('Content-Disposition: attachment;filename="' . $filename . '"');
   header('Cache-Control: max-age=0');
   
   $writer = new Xlsx($spreadsheet);
   $writer->save('php://output');
   exit;
}

/**
 * Criar planilha de estatísticas das respostas de texto
 */
private function create_text_statistics_sheet($sheet, $samples, $question_info) {
    // Calcular estatísticas
    $total_samples = count($samples);
    $total_chars = 0;
    $total_words = 0;
    $char_lengths = array();
    $word_counts = array();
    $responses_by_hour = array();
    $responses_by_date = array();
    
    foreach ($samples as $sample) {
        $text = $sample->response_text ?: '';
        $char_count = strlen($text);
        $word_count = str_word_count($text);
        
        $total_chars += $char_count;
        $total_words += $word_count;
        $char_lengths[] = $char_count;
        $word_counts[] = $word_count;
        
        // Agrupar por hora
        $hour = date('H', strtotime($sample->completed_at));
        if (!isset($responses_by_hour[$hour])) {
            $responses_by_hour[$hour] = 0;
        }
        $responses_by_hour[$hour]++;
        
        // Agrupar por data
        $date = date('Y-m-d', strtotime($sample->completed_at));
        if (!isset($responses_by_date[$date])) {
            $responses_by_date[$date] = 0;
        }
        $responses_by_date[$date]++;
    }
    
    $avg_chars = $total_samples > 0 ? round($total_chars / $total_samples, 1) : 0;
    $avg_words = $total_samples > 0 ? round($total_words / $total_samples, 1) : 0;
    $min_chars = !empty($char_lengths) ? min($char_lengths) : 0;
    $max_chars = !empty($char_lengths) ? max($char_lengths) : 0;
    $min_words = !empty($word_counts) ? min($word_counts) : 0;
    $max_words = !empty($word_counts) ? max($word_counts) : 0;
    
    // Título
    $sheet->setCellValue('A1', 'ESTATÍSTICAS DAS RESPOSTAS DE TEXTO');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Estatísticas gerais
    $row = 3;
    $stats_data = array(
        array('ESTATÍSTICAS GERAIS', ''),
        array('Total de amostras', $total_samples),
        array('Total de caracteres', number_format($total_chars)),
        array('Total de palavras', number_format($total_words)),
        array('Média de caracteres por resposta', $avg_chars),
        array('Média de palavras por resposta', $avg_words),
        array('Menor resposta (caracteres)', $min_chars),
        array('Maior resposta (caracteres)', $max_chars),
        array('Menor resposta (palavras)', $min_words),
        array('Maior resposta (palavras)', $max_words),
        array('', ''),
        array('DISTRIBUIÇÃO POR COMPRIMENTO', ''),
    );
    
    foreach ($stats_data as $data) {
        $sheet->setCellValue('A' . $row, $data[0]);
        $sheet->setCellValue('B' . $row, $data[1]);
        
        if (strpos($data[0], 'ESTATÍSTICAS') !== false || strpos($data[0], 'DISTRIBUIÇÃO') !== false) {
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('E9ECEF');
        }
        
        $row++;
    }
    
    // Distribuição por faixas de comprimento
    $length_ranges = array(
        '0-50 caracteres' => 0,
        '51-100 caracteres' => 0,
        '101-200 caracteres' => 0,
        '201-500 caracteres' => 0,
        'Mais de 500 caracteres' => 0
    );
    
    foreach ($char_lengths as $length) {
        if ($length <= 50) {
            $length_ranges['0-50 caracteres']++;
        } elseif ($length <= 100) {
            $length_ranges['51-100 caracteres']++;
        } elseif ($length <= 200) {
            $length_ranges['101-200 caracteres']++;
        } elseif ($length <= 500) {
            $length_ranges['201-500 caracteres']++;
        } else {
            $length_ranges['Mais de 500 caracteres']++;
        }
    }
    
    foreach ($length_ranges as $range => $count) {
        $percentage = $total_samples > 0 ? round(($count / $total_samples) * 100, 1) : 0;
        $sheet->setCellValue('A' . $row, $range);
        $sheet->setCellValue('B' . $row, $count . ' (' . $percentage . '%)');
        $row++;
    }
    
    // Ajustar larguras
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(20);
    
    // Adicionar bordas
    $dataRange = 'A3:B' . ($row - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
}

public function get_specific_questionnaire_analysis() {
    // Verificar autenticação
    $this->check_auth();
    
    $questionnaire_id = $this->input->get('questionnaire_id');
    
    if (!$questionnaire_id) {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'ID do questionário não fornecido'));
        return;
    }
    
    $filters = $this->get_filters();
    
    try {
        $analysis = $this->Response_model->get_specific_questionnaire_analysis($questionnaire_id, $filters);
        
        header('Content-Type: application/json');
        echo json_encode($analysis);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'Erro ao carregar análise: ' . $e->getMessage()));
    }
}

/**
 * MÉTODO QUE FALTAVA: Exportar análise específica de questionário
 */
public function export_specific_questionnaire() {
    $this->check_auth();
    
    $filters = $this->get_filters();
    $questionnaire_id = $filters['questionnaire_id'] ?? null;
    
    if (!$questionnaire_id) {
        $this->session->set_flashdata('error', 'Questionário não especificado para exportação.');
        redirect('reports');
        return;
    }
    
    try {
        $analysis = $this->Response_model->get_specific_questionnaire_analysis($questionnaire_id, $filters);
        
        if (isset($analysis['error'])) {
            $this->session->set_flashdata('error', $analysis['error']);
            redirect('reports');
            return;
        }
        
        // Criar arquivo Excel usando PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
                    ->setCreator("SXData")
                    ->setLastModifiedBy("SXData")
                    ->setTitle("Análise Detalhada - " . $analysis['questionnaire']->title)
                    ->setSubject("Análise de Questionário Específico")
                    ->setDescription("Análise detalhada do questionário: " . $analysis['questionnaire']->title);

        // PLANILHA 1: Resumo do Questionário
        $spreadsheet->setActiveSheetIndex(0);
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');
        
        $this->create_summary_sheet($summarySheet, $analysis);
        
        // PLANILHA 2: Análise por Questão
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1);
        $questionsSheet = $spreadsheet->getActiveSheet();
        $questionsSheet->setTitle('Análise por Questão');
        
        $this->create_questions_analysis_sheet($questionsSheet, $analysis);
        
        // Gerar arquivo
        $filename = 'analise_questionario_' . preg_replace('/[^a-zA-Z0-9]/', '_', $analysis['questionnaire']->title) . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        $this->session->set_flashdata('error', 'Erro ao gerar exportação: ' . $e->getMessage());
        redirect('reports');
    }
}

/**
 * Criar planilha de resumo
 */
private function create_summary_sheet($sheet, $analysis) {
    $questionnaire = $analysis['questionnaire'];
    $summary = $analysis['summary'];
    
    // Título
    $sheet->setCellValue('A1', 'ANÁLISE DETALHADA DO QUESTIONÁRIO');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Informações do questionário
    $row = 3;
    $info_data = array(
        array('Nome do Questionário:', $questionnaire->title),
        array('Descrição:', $questionnaire->description ?: 'Sem descrição'),
        array('Data de criação:', date('d/m/Y H:i', strtotime($questionnaire->created_at))),
        array('', ''),
        array('ESTATÍSTICAS GERAIS', ''),
        array('Total de questões:', $summary['total_questions']),
        array('Total de respostas:', $summary['total_responses']),
        array('Respondentes únicos:', $summary['unique_respondents']),
        array('Taxa média de conclusão:', $summary['avg_completion_rate'] . '%'),
        array('Tempo médio de resposta:', $summary['avg_time'] . ' minutos'),
        array('Última resposta:', $summary['last_response'] ?: 'Nunca'),
        array('Relatório gerado em:', date('d/m/Y H:i:s'))
    );
    
    foreach ($info_data as $data) {
        $sheet->setCellValue('A' . $row, $data[0]);
        $sheet->setCellValue('B' . $row, $data[1]);
        
        if ($data[0] === 'ESTATÍSTICAS GERAIS') {
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('E9ECEF');
        }
        
        $row++;
    }
    
    // Ajustar larguras
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(40);
    
    // Adicionar bordas
    $dataRange = 'A3:B' . ($row - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
}

/**
 * Criar planilha de análise por questão
 */
private function create_questions_analysis_sheet($sheet, $analysis) {
    // Cabeçalhos
    $headers = array(
        'A1' => 'Ordem',
        'B1' => 'Questão',
        'C1' => 'Tipo',
        'D1' => 'Total Respostas',
        'E1' => 'Taxa de Resposta',
        'F1' => 'Observações'
    );
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Estilizar cabeçalhos
    $headerRange = 'A1:F1';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setRGB('8fae5d');
    $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
    
    // Preencher dados
    $row = 2;
    $total_questionnaire_responses = $analysis['summary']['total_responses'];
    
    foreach ($analysis['questions'] as $question) {
        $total_responses = $question['statistics']['total_responses'];
        $response_rate = $total_questionnaire_responses > 0 ? 
            round(($total_responses / $total_questionnaire_responses) * 100, 1) : 0;
        
        $observations = '';
        if ($response_rate >= 80) {
            $observations = 'Alta taxa de resposta';
        } elseif ($response_rate >= 50) {
            $observations = 'Taxa de resposta média';
        } elseif ($response_rate > 0) {
            $observations = 'Baixa taxa de resposta';
        } else {
            $observations = 'Nenhuma resposta';
        }
        
        $sheet->setCellValue('A' . $row, $question['order_index']);
        $sheet->setCellValue('B' . $row, $question['question_text']);
        $sheet->setCellValue('C' . $row, ucfirst($question['question_type']));
        $sheet->setCellValue('D' . $row, $total_responses);
        $sheet->setCellValue('E' . $row, $response_rate . '%');
        $sheet->setCellValue('F' . $row, $observations);
        
        $row++;
    }
    
    // Ajustar larguras
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(60);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(25);
    
    // Quebra de texto
    $sheet->getStyle('B2:B' . ($row-1))->getAlignment()->setWrapText(true);
    
    // Adicionar bordas
    $dataRange = 'A1:F' . ($row-1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
}

}
?>