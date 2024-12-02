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
    
    // Hole die Spalten
    $highestColumn = $worksheet->getHighestColumn();
    $columns = [];
    
    // Hole die Spaltennamen aus der Header-Zeile
    for ($col = 'A'; $col <= $highestColumn; $col++) {
        $cell = $worksheet->getCell($col . $header_row);
        $value = $cell->getValue();
        
        // Wenn kein Name in der Header-Zeile, verwende Spaltenbezeichnung
        if (empty($value)) {
            $value = 'Spalte ' . $col;
        }
        
        $columns[$col] = $value;
    }

    // Automatische Zuordnung basierend auf Ähnlichkeiten
    $suggested_mapping = [];
    $standard_fields = [
        'datum' => ['datum', 'date', 'tag', 'day'],
        'beleg_nr' => ['beleg', 'belegnr', 'beleg-nr', 'beleg nr', 'nr', 'nummer'],
        'bemerkung' => ['bemerkung', 'text', 'beschreibung', 'buchungstext'],
        'einnahme' => ['einnahme', 'einnahmen', 'eingang', 'haben', 'soll'],
        'ausgabe' => ['ausgabe', 'ausgaben', 'ausgang', 'soll', 'haben']
    ];

    foreach ($columns as $col => $name) {
        $name_lower = strtolower(trim($name));
        foreach ($standard_fields as $field => $keywords) {
            if (in_array($name_lower, $keywords)) {
                $suggested_mapping[$field] = $col;
                break;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'suggested_mapping' => $suggested_mapping
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 