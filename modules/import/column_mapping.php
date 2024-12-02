<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    // Prüfe ob eine Konfigurations-ID übergeben wurde
    if (!isset($_GET['config_id'])) {
        throw new Exception('Keine Konfigurations-ID angegeben');
    }

    $config_id = $_GET['config_id'];
    
    // Hole die Konfiguration aus der Datenbank
    $sql = "SELECT config_value FROM system_config WHERE config_key = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL-Fehler: " . $conn->error);
    }
    
    $config_key = "excel_mapping_" . $config_id;
    $stmt->bind_param('s', $config_key);
    
    if (!$stmt->execute()) {
        throw new Exception("Fehler beim Ausführen der Abfrage: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Keine Konfiguration mit dieser ID gefunden");
    }
    
    $row = $result->fetch_assoc();
    $config = json_decode($row['config_value'], true);
    
    if (!$config) {
        throw new Exception("Ungültige Konfiguration");
    }
    
    echo json_encode([
        'success' => true,
        'mapping' => $config['mapping'] ?? [],
        'custom_mappings' => $config['custom_mappings'] ?? []
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 