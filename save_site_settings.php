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
        // Empfange JSON-Daten
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            throw new Exception('Ungültige Daten empfangen');
        }

        $site_name = $data['site_name'] ?? '';
        $company_name = $data['company_name'] ?? '';
        $company_street = $data['company_street'] ?? '';
        $company_zip = $data['company_zip'] ?? '';
        $company_city = $data['company_city'] ?? '';
        
        if (empty($site_name)) {
            throw new Exception('Seitenname darf nicht leer sein');
        }

        // Beginne Transaktion
        $conn->begin_transaction();

        // Speichere alle Einstellungen
        $settings = [
            'site_name' => $site_name,
            'company_name' => $company_name,
            'company_street' => $company_street,
            'company_zip' => $company_zip,
            'company_city' => $company_city
        ];

        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");

        foreach ($settings as $key => $value) {
            $stmt->bind_param("sss", $key, $value, $value);
            if (!$stmt->execute()) {
                throw new Exception('Fehler beim Speichern der Einstellung: ' . $key);
            }
        }

        // Commit Transaktion
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Einstellungen wurden gespeichert'
        ]);

    } catch (Exception $e) {
        // Rollback bei Fehler
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        error_log("Fehler beim Speichern der Einstellungen: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 