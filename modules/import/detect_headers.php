<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    // Prüfe ob eine Datei hochgeladen wurde
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine gültige Datei hochgeladen');
    }

    $tmpFile = $_FILES['excel_file']['tmp_name'];
    if (!file_exists($tmpFile)) {
        throw new Exception('Temporäre Datei nicht gefunden');
    }

    // Prüfe Dateityp
    $allowedTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'application/vnd.ms-excel',
        '' // Für den Fall, dass der MIME-Type nicht erkannt wird
    ];

    $fileType = $_FILES['excel_file']['type'];
    $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes) && !in_array($extension, ['xlsx', 'xls', 'csv'])) {
        throw new Exception('Ungültiges Dateiformat. Erlaubt sind: xlsx, xls, csv');
    }

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($tmpFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Hole die erste Zeile für die Spaltenüberschriften
    $headers = [];
    foreach ($worksheet->getRowIterator(1, 1) as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $headers[] = $cell->getValue();
        }
    }

    // Hole ein paar Beispieldaten
    $preview = [];
    $maxPreviewRows = 5;
    foreach ($worksheet->getRowIterator(2, min($worksheet->getHighestRow(), $maxPreviewRows + 1)) as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $preview[] = $rowData;
    }

    echo json_encode([
        'success' => true,
        'headers' => $headers,
        'preview' => $preview,
        'total_rows' => $worksheet->getHighestRow()
    ]);

} catch (Exception $e) {
    error_log('Import Fehler: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 