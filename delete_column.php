<?php
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Prüfe Admin-Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // JSON-Daten empfangen
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['columnName'])) {
        throw new Exception('Spaltenname fehlt');
    }

    error_log('Empfangene Daten: ' . print_r($data, true));

    // Hole aktuelle Spaltenkonfiguration
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'excel_columns'";
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Fehler beim Laden der Spaltenkonfiguration: ' . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    if (!$row) {
        // Wenn keine Konfiguration existiert, erstelle eine leere
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('excel_columns', '[]')";
        if (!$conn->query($sql)) {
            throw new Exception('Fehler beim Erstellen der Spaltenkonfiguration: ' . $conn->error);
        }
        $config = [];
    } else {
        $config = json_decode($row['setting_value'], true);
        if (!$config) {
            $config = [];
        }
        error_log('Geladene Konfiguration: ' . print_r($row['setting_value'], true));
    }

    error_log('Config Typ: ' . gettype($config));
    error_log('Config Inhalt RAW: ' . print_r($config, true));

    // Entferne die Spalte aus der Konfiguration
    $columnName = $data['columnName'];
    $found = false;

    error_log('Zu löschende Spalte: ' . $columnName);
    error_log('Aktuelle Konfiguration: ' . print_r($config, true));

    foreach ($config as $key => $value) {
        error_log('Prüfe Key: ' . $key . ', Value: ' . print_r($value, true));

        // Normalisieren Sie den Spaltennamen für den Vergleich
        $normalizedColumnName = trim(strtolower($columnName));

        if (is_array($value)) {
            // Prüfen Sie, ob das 'name'-Feld existiert
            if (isset($value['name'])) {
                $normalizedValueName = trim(strtolower($value['name']));
                if ($normalizedValueName === $normalizedColumnName) {
                    unset($config[$key]);
                    $found = true;
                    error_log('Array-Match gefunden bei Key: ' . $key);
                    break;
                }
            }
        } elseif (is_string($value)) {
            $normalizedValue = trim(strtolower($value));
            if ($normalizedValue === $normalizedColumnName) {
                unset($config[$key]);
                $found = true;
                error_log('String-Match gefunden bei Key: ' . $key);
                break;
            }
        } else {
            error_log('Unerwarteter Wertyp bei Key: ' . $key);
        }
    }

    if ($found) {
        // Entfernen Sie diese Zeile
        // $config = array_values($config);
        
        // Speichere aktualisierte Konfiguration
        $updatedConfig = json_encode($config);
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'excel_columns'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $updatedConfig);
        
        if (!$stmt->execute()) {
            throw new Exception('Fehler beim Speichern der Konfiguration: ' . $stmt->error);
        }
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Spalte nicht gefunden: ' . $columnName);
    }

} catch (Exception $e) {
    error_log('Delete Column Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 