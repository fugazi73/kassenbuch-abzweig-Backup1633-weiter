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
    
    // Maximale Anzahl der zu prüfenden Zeilen
    $maxRows = min($worksheet->getHighestRow(), 10);
    
    // Sammle die ersten Zeilen
    $rows = [];
    for ($row = 1; $row <= $maxRows; $row++) {
        $rowData = [];
        $isEmpty = true;
        
        // Prüfe jede Zelle in der Zeile
        foreach ($worksheet->getRowIterator($row)->current()->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if (!empty($value)) {
                $isEmpty = false;
            }
            $rowData[] = $value;
        }
        
        // Speichere nicht-leere Zeilen
        if (!$isEmpty) {
            $rows[] = $rowData;
        }
    }

    // Analysiere die Zeilen
    $headerRows = 1; // Mindestens eine Überschriftszeile
    
    // Suche nach typischen Überschriftsmerkmalen
    for ($i = 0; $i < count($rows) - 1; $i++) {
        $currentRow = $rows[$i];
        $nextRow = $rows[$i + 1];
        
        // Prüfe auf typische Überschriftsmerkmale
        $isHeader = false;
        
        // 1. Prüfe ob die aktuelle Zeile Text und die nächste Zahlen enthält
        $currentHasText = false;
        $nextHasNumbers = false;
        
        foreach ($currentRow as $cell) {
            if (!is_numeric($cell) && !empty($cell)) {
                $currentHasText = true;
                break;
            }
        }
        
        foreach ($nextRow as $cell) {
            if (is_numeric($cell) && !empty($cell)) {
                $nextHasNumbers = true;
                break;
            }
        }
        
        // 2. Prüfe auf typische Überschriftswörter
        $headerKeywords = ['kassenbuch', 'von', 'bis', 'beleg', 'datum', 'einnahme', 'ausgabe', 'saldo'];
        foreach ($currentRow as $cell) {
            $cellValue = strtolower(trim($cell));
            if (in_array($cellValue, $headerKeywords)) {
                $isHeader = true;
                break;
            }
        }
        
        if ($isHeader || ($currentHasText && $nextHasNumbers)) {
            $headerRows = $i + 1;
        }
    }

    echo json_encode([
        'success' => true,
        'header_rows' => $headerRows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 