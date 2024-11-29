<?php
// Bestehende Funktionen...

/**
 * Einstellung in der Datenbank aktualisieren oder erstellen
 */
function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
            setting_value = ?
    ");
    
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

/**
 * Einstellung aus der Datenbank abrufen
 */
function getSetting($conn, $key, $default = null) {
    $stmt = $conn->prepare("
        SELECT setting_value 
        FROM settings 
        WHERE setting_key = ?
    ");
    
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return $default;
}

// ... weitere Funktionen