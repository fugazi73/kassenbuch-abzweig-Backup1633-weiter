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

// Zentrale Fehlerbehandlung hinzufügen:
function handle_error($error, $redirect = true) {
    error_log($error);
    $_SESSION['error'] = $error;
    if ($redirect) {
        header('Location: error.php');
        exit;
    }
}

function log_action($message, $level = 'info') {
    $log_file = __DIR__ . '/../logs/' . date('Y-m') . '.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date][$level] $message\n";
    error_log($log_message, 3, $log_file);
}

// ... weitere Funktionen