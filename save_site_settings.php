<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können die Einstellungen ändern.'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $site_name = $_POST['site_name'] ?? '';
        
        if (empty($site_name)) {
            throw new Exception('Seitenname darf nicht leer sein');
        }

        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                               VALUES ('site_name', ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $site_name, $site_name);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Einstellungen wurden gespeichert'
            ]);
        } else {
            throw new Exception('Fehler beim Speichern der Einstellungen');
        }

    } catch (Exception $e) {
        error_log("Fehler beim Speichern der Einstellungen: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 