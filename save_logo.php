<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können Logos ändern.'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->begin_transaction();
        $success = false;

        // Upload-Verzeichnis erstellen falls nicht vorhanden
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Helles Logo verarbeiten
        if (isset($_FILES['logo_light']) && $_FILES['logo_light']['error'] === UPLOAD_ERR_OK) {
            $fileName = 'logo_light.' . pathinfo($_FILES['logo_light']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo_light']['tmp_name'], $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                      VALUES ('logo_light', ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                $relativePath = $uploadPath;
                $stmt->bind_param("ss", $relativePath, $relativePath);
                $stmt->execute();
                $success = true;
            }
        }

        // Dunkles Logo verarbeiten
        if (isset($_FILES['logo_dark']) && $_FILES['logo_dark']['error'] === UPLOAD_ERR_OK) {
            $fileName = 'logo_dark.' . pathinfo($_FILES['logo_dark']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo_dark']['tmp_name'], $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                      VALUES ('logo_dark', ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                $relativePath = $uploadPath;
                $stmt->bind_param("ss", $relativePath, $relativePath);
                $stmt->execute();
                $success = true;
            }
        }

        $conn->commit();
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Logos wurden erfolgreich gespeichert' : 'Keine Änderungen vorgenommen'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Fehler beim Logo-Upload: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern der Logos: ' . $e->getMessage()
        ]);
    }
} 