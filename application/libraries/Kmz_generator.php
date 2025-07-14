<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KMZ_Generator {
    
    public function generate($responses, $questionnaire_title = 'SXData Export') {
        $kml_content = $this->create_kml($responses, $questionnaire_title);
        
        // Criar arquivo temporário
        $temp_dir = sys_get_temp_dir() . '/sxdata_kmz_' . uniqid();
        mkdir($temp_dir);
        
        // Salvar KML
        file_put_contents($temp_dir . '/doc.kml', $kml_content);
        
        // Criar ZIP (KMZ é um ZIP com extensão .kmz)
        $zip = new ZipArchive();
        $kmz_filename = 'sxdata_locations_' . date('Y-m-d_H-i-s') . '.kmz';
        $kmz_path = $temp_dir . '/' . $kmz_filename;
        
        if ($zip->open($kmz_path, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($temp_dir . '/doc.kml', 'doc.kml');
            $zip->close();
            
            // Enviar arquivo
            header('Content-Type: application/vnd.google-earth.kmz');
            header('Content-Disposition: attachment; filename="' . $kmz_filename . '"');
            header('Content-Length: ' . filesize($kmz_path));
            
            readfile($kmz_path);
            
            // Limpar arquivos temporários
            unlink($temp_dir . '/doc.kml');
            unlink($kmz_path);
            rmdir($temp_dir);
            
            exit;
        } else {
            throw new Exception('Erro ao criar arquivo KMZ');
        }
    }
    
    private function create_kml($responses, $title) {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
        $kml .= '<Document>' . "\n";
        $kml .= '<name>' . htmlspecialchars($title) . '</name>' . "\n";
        $kml .= '<description>Localizações coletadas pelo sistema SXData</description>' . "\n";
        
        // Estilo para os marcadores
        $kml .= '<Style id="sxdata_marker">' . "\n";
        $kml .= '<IconStyle>' . "\n";
        $kml .= '<color>ff5dae8f</color>' . "\n";
        $kml .= '<scale>1.0</scale>' . "\n";
        $kml .= '<Icon><href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href></Icon>' . "\n";
        $kml .= '</IconStyle>' . "\n";
        $kml .= '</Style>' . "\n";
        
        // Adicionar marcadores
        foreach ($responses as $response) {
            if ($response->latitude && $response->longitude) {
                $kml .= '<Placemark>' . "\n";
                $kml .= '<name>Resposta #' . $response->id . '</name>' . "\n";
                $kml .= '<description><![CDATA[' . "\n";
                $kml .= '<strong>Questionário:</strong> ' . htmlspecialchars($response->questionnaire_title ?? 'N/A') . '<br/>' . "\n";
                $kml .= '<strong>Aplicador:</strong> ' . htmlspecialchars($response->applied_by_name ?? 'N/A') . '<br/>' . "\n";
                $kml .= '<strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($response->completed_at)) . '<br/>' . "\n";
                
                if ($response->respondent_name) {
                    $kml .= '<strong>Respondente:</strong> ' . htmlspecialchars($response->respondent_name) . '<br/>' . "\n";
                }
                
                if ($response->location_name) {
                    $kml .= '<strong>Local:</strong> ' . htmlspecialchars($response->location_name) . '<br/>' . "\n";
                }
                
                $kml .= '<strong>Coordenadas:</strong> ' . $response->latitude . ', ' . $response->longitude . '<br/>' . "\n";
                $kml .= '<strong>Consentimento:</strong> ' . ($response->consent_given ? 'Sim' : 'Não') . '<br/>' . "\n";
                
                if ($response->photo_path) {
                    $kml .= '<strong>Foto:</strong> Disponível<br/>' . "\n";
                }
                
                $kml .= ']]></description>' . "\n";
                $kml .= '<styleUrl>#sxdata_marker</styleUrl>' . "\n";
                $kml .= '<Point>' . "\n";
                $kml .= '<coordinates>' . $response->longitude . ',' . $response->latitude . ',0</coordinates>' . "\n";
                $kml .= '</Point>' . "\n";
                $kml .= '</Placemark>' . "\n";
            }
        }
        
        $kml .= '</Document>' . "\n";
        $kml .= '</kml>' . "\n";
        
        return $kml;
    }
}