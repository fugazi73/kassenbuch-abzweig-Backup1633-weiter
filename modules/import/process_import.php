<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

try {
    // Debug-Logging
    error_log("POST-Daten: " . print_r($_POST, true));
    error_log("FILES-Daten: " . print_r($_FILES, true));

    // Prüfe ob eine Datei hochgeladen wurde
    if (!isset($_FILES['excel_file'])) {
        throw new Exception('Keine Datei gefunden');
    }

    $tmpFile = $_FILES['excel_file']['tmp_name'];
    if (!file_exists($tmpFile) || !is_readable($tmpFile)) {
        throw new Exception('Datei nicht lesbar');
    }

    // Hole die Spaltenzuordnung
    if (!isset($_POST['mapping'])) {
        error_log("Keine Spaltenzuordnung gefunden in POST");
        throw new Exception('Keine Spaltenzuordnung gefunden');
    }

    // Dekodiere die JSON-Mapping-Daten
    $mapping = json_decode($_POST['mapping'], true);
    if (!$mapping) {
        error_log("Ungültige Mapping-Daten: " . $_POST['mapping']);
        throw new Exception('Ungültige Spaltenzuordnung');
    }

    error_log("Dekodierte Mapping-Daten: " . print_r($mapping, true));

    // Prüfe ob alle erforderlichen Spalten zugeordnet sind
    $required_fields = ['datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe'];
    foreach ($required_fields as $field) {
        if (empty($mapping[$field])) {
            throw new Exception("Pflichtfeld '$field' wurde nicht zugeordnet");
        }
    }

    // Hole die benutzerdefinierten Spalten
    $custom_names = $_POST['custom_names'] ?? [];
    $custom_mappings = $_POST['custom_mappings'] ?? [];

    // Speichere die Konfiguration wenn gewünscht
    if (!empty($_POST['config_name'])) {
        $config = [
            'name' => $_POST['config_name'],
            'mapping' => $mapping,
            'custom_mappings' => array_map(function($name, $column) {
                return ['name' => $name, 'column' => $column];
            }, $custom_names, $custom_mappings)
        ];
        
        $config_key = 'excel_mapping_' . uniqid();
        $config_value = json_encode($config);
        
        $sql = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL-Fehler: " . $conn->error);
        }
        
        $stmt->bind_param('ss', $config_key, $config_value);
        if (!$stmt->execute()) {
            throw new Exception("Fehler beim Speichern der Konfiguration: " . $stmt->error);
        }
    }

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($tmpFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Hole Header-Zeile
    $header_row = isset($_POST['header_row']) ? (int)$_POST['header_row'] : 1;
    
    // Starte Transaktion
    $conn->begin_transaction();
    
    $importCount = 0;
    $errors = [];
    
    // Verarbeite jede Zeile
    $highestRow = $worksheet->getHighestRow();
    for ($row = $header_row + 1; $row <= $highestRow; $row++) {
        $data = [];
        $hasData = false;
        
        // Standard-Spalten
        foreach ($mapping as $db_field => $excel_column) {
            $cell = $worksheet->getCell($excel_column . $row);
            
            if ($cell->isFormula()) {
                $value = $cell->getCalculatedValue();
            } else {
                $value = $cell->getValue();
            }
            
            // Formatiere Werte entsprechend des Feldtyps
            switch ($db_field) {
                case 'datum':
                    if (empty($value)) {
                        $value = date('Y-m-d');
                    } else if (is_numeric($value)) {
                        try {
                            $value = Date::excelToDateTimeObject($value)->format('Y-m-d');
                        } catch (Exception $e) {
                            $errors[] = "Ungültiges Datum in Zeile $row: " . $e->getMessage();
                            continue 2;
                        }
                    } else {
                        $timestamp = strtotime($value);
                        if ($timestamp === false) {
                            $errors[] = "Ungültiges Datum in Zeile $row";
                            continue 2;
                        }
                        $value = date('Y-m-d', $timestamp);
                    }
                    break;
                    
                case 'einnahme':
                case 'ausgabe':
                    if (empty($value)) {
                        $value = 0.00;
                    } else {
                        if (is_string($value)) {
                            $value = str_replace(['.', ',', '€', ' '], ['', '.', '', ''], $value);
                        }
                        $value = floatval($value);
                        if ($value > 0) {
                            $hasData = true;
                        }
                    }
                    break;
                    
                default:
                    $value = trim($value ?? '');
                    if (!empty($value)) {
                        $hasData = true;
                    }
            }
            
            $data[$db_field] = $value;
        }
        
        // Benutzerdefinierte Spalten
        foreach ($custom_names as $index => $name) {
            if (empty($name) || empty($custom_mappings[$index])) continue;
            
            $excel_column = $custom_mappings[$index];
            $db_field = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
            
            $cell = $worksheet->getCell($excel_column . $row);
            $value = $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
            $data[$db_field] = trim($value ?? '');
            
            if (!empty($value)) {
                $hasData = true;
            }
        }
        
        // Überspringe leere Zeilen
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
        
        error_log("SQL: $sql");
        error_log("Daten: " . print_r($data, true));
        
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
        } else {
            $errors[] = "Fehler beim Import von Zeile $row: " . $stmt->error;
        }
    }
    
    // Wenn es Fehler gab, mache alles rückgängig
    if (!empty($errors)) {
        $conn->rollback();
        throw new Exception("Import fehlgeschlagen:\n" . implode("\n", $errors));
    }
    
    // Commit wenn alles erfolgreich
    $conn->commit();
    
    if ($importCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => $importCount . ' Einträge wurden erfolgreich importiert',
            'type' => 'success'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Keine Einträge wurden importiert',
            'type' => 'warning'
        ]);
    }

} catch (Exception $e) {
    error_log("Import-Fehler: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 