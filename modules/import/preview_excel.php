<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    if (!isset($_FILES['excel_file'])) {
        throw new Exception('Keine Datei gefunden');
    }

    $tmpFile = $_FILES['excel_file']['tmp_name'];
    if (!file_exists($tmpFile) || !is_readable($tmpFile)) {
        throw new Exception('Datei nicht lesbar');
    }

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($tmpFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Hole Header-Zeile
    $header_row = isset($_POST['header_row']) ? (int)$_POST['header_row'] : 1;
    
    // Maximale Anzahl der Vorschau-Zeilen
    $maxRows = min($worksheet->getHighestRow(), $header_row + 10);
    $highestColumn = $worksheet->getHighestColumn();
    
    // Sammle die Daten
    $preview = [];
    $headers = [];
    
    // Hole Überschriften
    for ($col = 'A'; $col <= $highestColumn; $col++) {
        $cell = $worksheet->getCell($col . $header_row);
        $value = $cell->getValue();
        $headers[$col] = empty($value) ? "Spalte $col" : $value;
    }
    
    // Hole Daten für die Vorschau
    for ($row = $header_row + 1; $row <= $maxRows; $row++) {
        $rowData = [];
        $hasData = false;
        
        foreach ($headers as $col => $header) {
            $cell = $worksheet->getCell($col . $row);
            
            if ($cell->isFormula()) {
                $value = $cell->getCalculatedValue();
            } else {
                $value = $cell->getValue();
            }
            
            // Formatiere Zahlen
            if (is_numeric($value)) {
                if (strpos($value, '.') !== false) {
                    $value = number_format($value, 2, ',', '.');
                }
            }
            
            $rowData[$header] = $value;
            if (!empty($value)) {
                $hasData = true;
            }
        }
        
        if ($hasData) {
            $preview[] = $rowData;
        }
    }

    echo json_encode([
        'success' => true,
        'preview' => $preview,
        'headers' => $headers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 