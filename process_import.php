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
    
    // Hole Mapping aus dem Formular
    $mapping = $_POST['mapping'] ?? [];
    
    // Starte Transaktion
    $conn->begin_transaction();

    // Erste Zeile überspringen wenn Header
    $startRow = isset($_POST['has_header']) && $_POST['has_header'] ? 2 : 1;
    
    $importCount = 0;
    $highestRow = $worksheet->getHighestRow();

    // Lade benutzerdefinierte Spalten
    $custom_columns = [];
    $custom_columns_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
    if ($row = $custom_columns_result->fetch_assoc()) {
        $custom_columns = json_decode($row['setting_value'], true) ?: [];
    }

    for ($rowIndex = $startRow; $rowIndex <= $highestRow; $rowIndex++) {
        $rowData = [];
        foreach ($worksheet->getRowIterator($rowIndex, $rowIndex) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }
        }
        
        // Prüfe ob Zeile leer ist
        if (empty(array_filter($rowData))) {
            continue;
        }

        // Bereite Daten für Import vor
        $data = [
            'datum' => formatDate($rowData[ord($mapping['datum']) - 65]),
            'beleg_nr' => $rowData[ord($mapping['beleg_nr']) - 65],
            'bemerkung' => $rowData[ord($mapping['bemerkung']) - 65],
            'einnahme' => parseAmount($rowData[ord($mapping['einnahme']) - 65]),
            'ausgabe' => parseAmount($rowData[ord($mapping['ausgabe']) - 65])
        ];

        // Füge benutzerdefinierte Spalten zu den Daten hinzu
        foreach ($custom_columns as $column) {
            $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
            if (isset($mapping[$column_name])) {
                $value = $rowData[ord($mapping[$column_name]) - 65];
                
                // Konvertiere Wert basierend auf Spaltentyp
                switch($column['type']) {
                    case 'date':
                        $data[$column_name] = formatDate($value);
                        break;
                    case 'decimal':
                        $data[$column_name] = parseAmount($value);
                        break;
                    case 'integer':
                        $data[$column_name] = intval($value);
                        break;
                    default:
                        $data[$column_name] = $value;
                }
            }
        }

        // Validiere Daten
        if (!$data['datum']) {
            throw new Exception('Ungültiges Datum in Zeile ' . $rowIndex);
        }

        // SQL für INSERT anpassen
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';

        $sql = "INSERT INTO kassenbuch_eintraege (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);

        // Bestimme die Typen für bind_param
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }

        // Dynamisches bind_param
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            throw new Exception('Fehler beim Einfügen der Daten');
        }
        
        $importCount++;
    }
    
    // Commit wenn alles erfolgreich
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$importCount Einträge wurden importiert"
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Hilfsfunktionen
function formatDate($value) {
    if (!$value) return null;
    
    $date = DateTime::createFromFormat('d.m.Y', $value);
    if (!$date) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
    }
    
    return $date ? $date->format('Y-m-d') : null;
}

function parseAmount($value) {
    if (!$value) return 0;
    
    // Entferne Währungssymbole und konvertiere , zu .
    $value = str_replace(['€', '.', ','], ['', '', '.'], $value);
    return floatval($value);
}