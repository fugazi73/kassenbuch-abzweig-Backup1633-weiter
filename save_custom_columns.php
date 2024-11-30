<?php
require_once 'config.php';
require_once 'includes/init.php';

header('Content-Type: application/json');

try {
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Empfange Daten
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['columns'])) {
        throw new Exception('Keine Spaltendaten empfangen');
    }

    // Validiere Spalten
    foreach ($data['columns'] as $column) {
        if (empty($column['name'])) {
            throw new Exception('Spaltenname darf nicht leer sein');
        }
        if (!in_array($column['type'], ['text', 'date', 'decimal', 'integer'])) {
            throw new Exception('Ungültiger Spaltentyp');
        }
    }

    // Speichere in Datenbank
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES ('custom_columns', ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    
    $json = json_encode($data['columns']);
    $stmt->bind_param('ss', $json, $json);
    
    if (!$stmt->execute()) {
        throw new Exception('Fehler beim Speichern der Spalten');
    }

    // Erstelle/Aktualisiere Datenbankstruktur
    foreach ($data['columns'] as $column) {
        $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
        
        // Prüfe ob Spalte existiert
        $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
        
        // SQL-Typ bestimmen
        $sql_type = '';
        switch($column['type']) {
            case 'text':
                $sql_type = 'VARCHAR(255)';
                break;
            case 'date':
                $sql_type = 'DATE';
                break;
            case 'decimal':
                $sql_type = 'DECIMAL(10,2)';
                break;
            case 'integer':
                $sql_type = 'INT';
                break;
            default:
                $sql_type = 'VARCHAR(255)';
        }

        if ($result->num_rows === 0) {
            // Neue Spalte erstellen
            $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN `$column_name` $sql_type";
        } else {
            // Existierende Spalte modifizieren
            $sql = "ALTER TABLE kassenbuch_eintraege MODIFY COLUMN `$column_name` $sql_type";
        }

        if (!$conn->query($sql)) {
            throw new Exception('Fehler beim Bearbeiten der Spalte: ' . $conn->error);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 