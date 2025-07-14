<?php
// application/libraries/Excel.php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Excel
{

    public function create_export($responses, $format = 'xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar cabeçalhos
        $headers = [
            'A1' => 'ID',
            'B1' => 'Questionário',
            'C1' => 'Aplicador',
            'D1' => 'Respondente',
            'E1' => 'Email',
            'F1' => 'Latitude',
            'G1' => 'Longitude',
            'H1' => 'Localização',
            'I1' => 'Consentimento',
            'J1' => 'Data/Hora Conclusão',
            'K1' => 'Status',
            'L1' => 'Foto'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Estilizar caebeçalho
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '23345F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $statusMap = [
            'synced' => 'Sincronizado',
            'pending' => 'Pendente',
            'error' => 'Erro'
        ];

        // Preencher dados
        $row = 2;
        foreach ($responses as $response) {
            $sheet->setCellValue('A' . $row, $response->id);
            $sheet->setCellValue('B' . $row, $response->questionnaire ?? 'N/A');
            $sheet->setCellValue('C' . $row, $response->aplicador ?? 'N/A');
            $sheet->setCellValue('D' . $row, $response->respondent_name ?? 'N/A');
            $sheet->setCellValue('E' . $row, $response->respondent_email ?? 'N/A');
            $sheet->setCellValue('F' . $row, $response->latitude ?? '');
            $sheet->setCellValue('G' . $row, $response->longitude ?? '');
            $sheet->setCellValue('H' . $row, $response->location_name ?? 'N/A');
            $sheet->setCellValue('I' . $row, $response->consent_given ? 'Sim' : 'Não');
            $sheet->setCellValue('J' . $row, $response->completed_at ? date('d/m/Y H:i:s', strtotime($response->completed_at)) : 'N/A');            
            $sheet->setCellValue('K' . $row, $statusMap[$response->sync_status] ?? 'N/A');
            $sheet->setCellValue('L' . $row, isset($response->photo_path) ? 'Sim' : 'Não');
            $row++;
        }

        // Auto-ajustar colunas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Gerar arquivo
        $filename = 'sxdata_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function create_detailed_export($questionnaire_id, $responses_with_answers)
    {
        $CI =& get_instance();
        $CI->load->model('Questionnaire_model');
        $CI->load->model('Question_model');

        $questionnaire = $CI->Questionnaire_model->get_by_id($questionnaire_id);
        $questions = $CI->Question_model->get_by_questionnaire($questionnaire_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos básicos
        $basic_headers = ['ID', 'Aplicador', 'Respondente', 'Email', 'Data/Hora', 'Localização', 'Latitude', 'Longitude', 'Consentimento'];
        $col = 1;

        foreach ($basic_headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Cabeçalhos das perguntas
        foreach ($questions as $question) {
            $sheet->setCellValueByColumnAndRow($col, 1, 'P' . $question->order_index . ': ' . substr($question->question_text, 0, 50));
            $col++;
        }

        // Estilizar cabeçalho
        $lastCol = $col - 1;
        $range = 'A1:' . chr(64 + $lastCol) . '1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '8fae5d']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Preencher dados
        $row = 2;
        foreach ($responses_with_answers as $response) {
            $col = 1;

            // Dados básicos
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->id);
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->applied_by_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->respondent_name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->respondent_email ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, date('d/m/Y H:i', strtotime($response->completed_at)));
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->location_name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->latitude ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->longitude ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->consent_given ? 'Sim' : 'Não');

            // Respostas das perguntas
            foreach ($questions as $question) {
                $answer = '';
                if (isset($response->answers[$question->id])) {
                    $ans = $response->answers[$question->id];
                    if ($ans->response_text) {
                        $answer = $ans->response_text;
                    } elseif ($ans->response_number !== null) {
                        $answer = $ans->response_number;
                    } elseif ($ans->response_date) {
                        $answer = date('d/m/Y', strtotime($ans->response_date));
                    } elseif ($ans->selected_options) {
                        $options = json_decode($ans->selected_options);
                        $answer = is_array($options) ? implode(', ', $options) : $ans->selected_options;
                    }
                }
                $sheet->setCellValueByColumnAndRow($col++, $row, $answer);
            }

            $row++;
        }

        // Auto-ajustar colunas
        for ($i = 1; $i < $col; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Gerar arquivo
        $filename = 'sxdata_' . strtolower(str_replace(' ', '_', $questionnaire->title)) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}