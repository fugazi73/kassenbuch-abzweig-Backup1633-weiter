<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['file'])) {
    die(json_encode(['success' => false, 'message' => 'Keine Datei hochgeladen']));
}

try {
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Erste 6 Zeilen für die Vorschau
    $preview = [];
    foreach ($worksheet->getRowIterator(1, 6) as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $preview[] = $rowData;
    }
    
    // Spaltenüberschriften (erste Zeile)
    $columns = array_shift($preview);
    
    echo json_encode([
        'success' => true,
        'preview' => [
            'columns' => $columns,
            'rows' => $preview
        ],
        'columns' => $columns
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 