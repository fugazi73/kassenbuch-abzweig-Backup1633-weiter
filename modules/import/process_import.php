<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/column_config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

header('Content-Type: application/json');

try {
    // Prüfe Admin-Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Prüfe ob Excel-Datei in der Session vorhanden ist
    if (!isset($_SESSION['excel_file']) || !file_exists($_SESSION['excel_file'])) {
        throw new Exception('Keine Datei gefunden');
    }

    // Entferne UNIQUE-Index von beleg_nr falls vorhanden
    $conn->query("ALTER TABLE kassenbuch_eintraege DROP INDEX IF EXISTS beleg_nr");
    
    // Hole die Überschriftszeile
    $header_row = isset($_SESSION['excel_header_row']) ? (int)$_SESSION['excel_header_row'] : 6;

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($_SESSION['excel_file']);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    // Starte Transaktion
    $conn->begin_transaction();

    $importCount = 0;
    
    // Lade Spaltenkonfiguration
    $column_mapping = loadColumnMapping();
    if (!$column_mapping) {
        throw new Exception('Keine Spaltenkonfiguration gefunden. Bitte zuerst Spalten zuordnen.');
    }
    
    function isEmptyOrSummaryRow($row, $columnMapping) {
        // Prüfe auf Schlüsselwörter in der Beschreibung
        $description = trim($row[$columnMapping['beschreibung']] ?? '');
        if (in_array(strtolower($description), ['saldo', 'summen', 'summe'])) {
            return true;
        }

        // Zähle die Anzahl der Nullwerte
        $zeroCount = 0;
        $totalFields = 0;
        
        foreach ($row as $value) {
            if (empty($value) || $value == '0' || $value == '0,00' || $value == '0,00 €' || $value == '0.00' || $value == '0.00 €') {
                $zeroCount++;
            }
            $totalFields++;
        }

        // Wenn mehr als 80% der Felder Null sind, betrachte es als leere Zeile
        return ($zeroCount / $totalFields) > 0.8;
    }

    function processExcelFile($filePath, $headerRows = 6) {
        // ... existing code ...
        
        foreach ($worksheet->getRowIterator($headerRows + 1) as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }
            
            // Überspringe leere oder Summenzeilen
            if (isEmptyOrSummaryRow($rowData, $columnMapping)) {
                continue;
            }
            
            // Verarbeite nur Zeilen mit gültigen Daten
            $datum = formatDate($rowData[$columnMapping['datum']]);
            if (!$datum) {
                continue;
            }

            // ... rest of existing code ...
        }
        // ... existing code ...
    }
    
    // Verarbeite jede Zeile nach den Überschriften
    for ($row = $header_row + 1; $row <= $highestRow; $row++) {
        $data = [];
        $hasData = false;
        
        // Lese Zeilendaten entsprechend der Spaltenkonfiguration
        foreach ($column_mapping as $db_field => $excel_column) {
            $cell = $worksheet->getCell($excel_column . $row);
            
            if ($cell->isFormula()) {
                $value = $cell->getCalculatedValue();
            } else {
                $value = $cell->getValue();
            }
            
            // Formatiere Werte entsprechend des Feldtyps
            switch ($db_field) {
                case 'datum':
                    $value = formatDate($value);
                    break;
                    
                case 'einnahme':
                case 'ausgabe':
                    if (!empty($value)) {
                        if (is_numeric($value)) {
                            $value = floatval($value);
                        } else {
                            $value = str_replace(['.', ',', '€', ' '], ['', '.', '', ''], $value);
                            $value = floatval($value);
                        }
                    } else {
                        $value = 0.00;
                    }
                    break;
                    
                default:
                    $value = trim($value ?? '');
            }
            
            $data[$db_field] = $value;
            
            // Prüfe ob die Zeile echte Daten enthält
            if ($db_field === 'einnahme' && $value > 0) $hasData = true;
            if ($db_field === 'ausgabe' && $value > 0) $hasData = true;
            if ($db_field === 'bemerkung' && !empty($value)) $hasData = true;
        }

        // Überspringe Zeile wenn keine echten Daten vorhanden sind
        if (!$hasData) {
            continue;
        }

        // Berechne Saldo
        $data['saldo'] = ($data['einnahme'] ?? 0) - ($data['ausgabe'] ?? 0);
        
        // Füge Benutzer-ID hinzu
        $data['user_id'] = $_SESSION['user_id'];

        // Bereite SQL vor
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        
        $sql = "INSERT INTO kassenbuch_eintraege (" . implode(',', $columns) . ") 
                VALUES ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL-Fehler: " . $conn->error);
        }

        // Bestimme die Typen für bind_param
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }

        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $importCount++;
        }
    }
    
    // Commit wenn alles erfolgreich
    $conn->commit();
    
    // Lösche temporäre Datei und Session-Variablen
    if (isset($_SESSION['excel_file']) && file_exists($_SESSION['excel_file'])) {
        unlink($_SESSION['excel_file']);
    }
    unset($_SESSION['excel_file']);
    unset($_SESSION['original_filename']);
    
    echo json_encode([
        'success' => true,
        'message' => "$importCount Einträge wurden erfolgreich importiert"
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Import-Fehler: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 