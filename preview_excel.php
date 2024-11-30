<?php
require_once 'config.php';
require_once 'includes/init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    // Prüfe Admin-Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Prüfe ob Datei hochgeladen wurde
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine Datei oder Fehler beim Upload');
    }

    // Prüfe Dateiformat
    $allowed_types = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    if (!in_array($_FILES['excel_file']['type'], $allowed_types)) {
        throw new Exception('Ungültiges Dateiformat');
    }

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Hole die ersten 6 Zeilen für die Vorschau
    $preview = [];
    $highestRow = min($worksheet->getHighestRow(), 6);
    $highestColumn = $worksheet->getHighestColumn();
    
    // Konvertiere Buchstaben-Spalte in Zahl (A=1, B=2, etc.)
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    // Hole Spaltennamen (erste Zeile)
    $columns = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $cell = $worksheet->getCellByColumnAndRow($col, 1);
        $columns[] = $cell->getValue() ?: 'Spalte ' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1);
    }

    // Hole Datenzeilen
    $rows = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cell = $worksheet->getCellByColumnAndRow($col, $row);
            $value = $cell->getValue();
            
            // Formatiere Datum
            if ($cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC && 
                \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('d.m.Y');
            }
            
            // Formatiere Zahlen
            if ($cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                $value = number_format($value, 2, ',', '.');
            }
            
            $rowData[] = $value;
        }
        $rows[] = $rowData;
    }

    echo json_encode([
        'success' => true,
        'preview' => [
            'columns' => $columns,
            'rows' => $rows
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 