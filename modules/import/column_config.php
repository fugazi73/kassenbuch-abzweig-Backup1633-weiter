<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';

// Standardspalten des Kassenbuchs
$default_columns = [
    'beleg_nr' => 'Beleg #',
    'datum' => 'Datum',
    'bemerkung' => 'Bemerkung',
    'einnahme' => 'Einnahme',
    'ausgabe' => 'Ausgabe'
];

// Hole benutzerdefinierte Spalten
function getCustomColumns() {
    global $conn;
    $custom_columns = [];
    
    // Hole alle Spalten aus der Tabelle
    $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege");
    $all_columns = [];
    while ($row = $result->fetch_assoc()) {
        $all_columns[] = $row['Field'];
    }
    
    // Standard-Spalten definieren
    $standard_columns = ['id', 'datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe', 'saldo', 'kassenstand', 'user_id', 'created_at'];
    
    // Benutzerdefinierte Spalten sind alle, die nicht Standard sind
    $custom_columns = array_diff($all_columns, $standard_columns);
    
    // Hole die Spaltennamen aus den Einstellungen
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
    if ($row = $result->fetch_assoc()) {
        $saved_columns = json_decode($row['setting_value'], true) ?: [];
        
        // Erstelle ein Array mit den benutzerdefinierten Spalten und ihren Namen
        $named_columns = [];
        foreach ($custom_columns as $column) {
            $name = $column;
            foreach ($saved_columns as $saved) {
                if (strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $saved['name'])) === $column) {
                    $name = $saved['name'];
                    break;
                }
            }
            $named_columns[$column] = $name;
        }
        return $named_columns;
    }
    
    return array_combine($custom_columns, $custom_columns);
}

// Hole alle verfügbaren Spalten aus Excel
function getExcelColumns($file_path, $header_row) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestColumn = $worksheet->getHighestColumn();
        
        $columns = [];
        // Hole die Spaltennamen aus der angegebenen Header-Zeile
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cell = $worksheet->getCell($col . $header_row);
            $value = $cell->getValue();
            
            // Wenn kein Name in der Header-Zeile, verwende Spaltenbezeichnung
            if (empty($value)) {
                $value = 'Spalte ' . $col;
            }
            
            $columns[$col] = $value;
        }
        
        return $columns;
    } catch (Exception $e) {
        error_log("Fehler beim Lesen der Excel-Spalten: " . $e->getMessage());
        return [];
    }
}

// Erstelle system_config Tabelle falls nicht vorhanden
function createSystemConfigTable() {
    global $conn;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS system_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(255) NOT NULL UNIQUE,
            config_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        if (!$conn->query($sql)) {
            throw new Exception("Fehler beim Erstellen der Tabelle: " . $conn->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Fehler beim Erstellen der system_config Tabelle: " . $e->getMessage());
        return false;
    }
}

// Lade gespeicherte Spaltenkonfiguration
function loadColumnMapping() {
    global $conn;
    
    try {
        // Stelle sicher, dass die Tabelle existiert
        if (!createSystemConfigTable()) {
            throw new Exception("Konnte system_config Tabelle nicht erstellen");
        }
        
        $sql = "SELECT config_value FROM system_config WHERE config_key = 'excel_column_mapping'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return json_decode($row['config_value'], true);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Fehler beim Laden der Spaltenkonfiguration: " . $e->getMessage());
        return null;
    }
}

// Speichere Spaltenkonfiguration
function saveColumnMapping($mapping) {
    global $conn;
    
    try {
        // Stelle sicher, dass die Tabelle existiert
        if (!createSystemConfigTable()) {
            throw new Exception("Konnte system_config Tabelle nicht erstellen");
        }
        
        $mapping_json = json_encode($mapping);
        
        $sql = "INSERT INTO system_config (config_key, config_value) 
                VALUES ('excel_column_mapping', ?) 
                ON DUPLICATE KEY UPDATE config_value = ?";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Fehler beim Vorbereiten des Statements: " . $conn->error);
        }
        
        $stmt->bind_param('ss', $mapping_json, $mapping_json);
        if (!$stmt->execute()) {
            throw new Exception("Fehler beim Speichern der Konfiguration: " . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Fehler beim Speichern der Spaltenkonfiguration: " . $e->getMessage());
        return false;
    }
}

// Hilfsfunktion für die Datumsformatierung
function formatDate($value) {
    if (empty($value)) {
        return date('Y-m-d'); // Aktuelles Datum als Fallback
    }
    
    // Wenn es bereits ein DateTime Objekt ist
    if ($value instanceof DateTime) {
        return $value->format('Y-m-d');
    }
    
    // Wenn es ein Excel-Datum ist (numerischer Wert)
    if (is_numeric($value)) {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (Exception $e) {
            error_log("Fehler bei Excel-Datumskonvertierung: " . $e->getMessage());
        }
    }
    
    // Versuche verschiedene Datumsformate
    $formats = [
        'd.m.Y',    // 15.01.2024
        'n/j/Y',    // 1/10/2024
        'Y-m-d',    // 2024-01-15
        'd.m.y',    // 15.01.24
        'j.n.Y',    // 1.1.2024
        'Y/m/d',    // 2024/01/15
        'Y/n/j',    // 2024/1/1
        'j.n.y'     // 1.1.24
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date && $date->format($format) == $value) {
            return $date->format('Y-m-d');
        }
    }
    
    // Versuche strtotime als letzte Option
    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    // Wenn alles fehlschlägt, gib das aktuelle Datum zurück
    return date('Y-m-d');
}

// Funktion für Excel-Vorschau
function getExcelPreview($file_path, $max_rows = 10) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        $preview = [];
        // Zeige die ersten max_rows Zeilen
        for ($row = 1; $row <= $max_rows; $row++) {
            $rowData = [];
            $hasData = false;
            
            // Hole Daten für jede Spalte
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cell = $worksheet->getCell($columnLetter . $row);
                
                if ($cell->isFormula()) {
                    $value = $cell->getCalculatedValue();
                } else {
                    $value = $cell->getValue();
                }
                
                $rowData[$columnLetter] = $value;
                if ($value !== null && trim($value) !== '') {
                    $hasData = true;
                }
            }
            
            if ($hasData) {
                $preview[$row] = $rowData;
            }
        }
        
        return $preview;
    } catch (Exception $e) {
        error_log("Fehler beim Lesen der Excel-Vorschau: " . $e->getMessage());
        return [];
    }
}
  