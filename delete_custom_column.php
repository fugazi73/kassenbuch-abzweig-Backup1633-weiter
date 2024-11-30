<?php
require_once 'config.php';
require_once 'includes/init.php';

header('Content-Type: application/json');

try {
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['columnName'])) {
        throw new Exception('Kein Spaltenname angegeben');
    }

    // Hole aktuelle Spalten
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
    $custom_columns = [];
    if ($row = $result->fetch_assoc()) {
        $custom_columns = json_decode($row['setting_value'], true) ?: [];
    }

    // Finde und entferne die Spalte
    $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $data['columnName']));
    $found = false;
    foreach ($custom_columns as $key => $column) {
        if (strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name'])) === $column_name) {
            unset($custom_columns[$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        throw new Exception('Spalte nicht gefunden');
    }

    // Array neu indexieren
    $custom_columns = array_values($custom_columns);

    // Speichere aktualisierte Konfiguration
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'custom_columns'");
    $json = json_encode($custom_columns);
    $stmt->bind_param('s', $json);
    
    if (!$stmt->execute()) {
        throw new Exception('Fehler beim Speichern der Konfiguration');
    }

    // Entferne Spalte aus der Datenbank
    $sql = "ALTER TABLE kassenbuch_eintraege DROP COLUMN `$column_name`";
    if (!$conn->query($sql)) {
        throw new Exception('Fehler beim Entfernen der Spalte aus der Datenbank');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 