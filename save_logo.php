<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!is_admin()) {
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können Logos ändern.'
    ]));
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $uploadDir = 'images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $success = false;
        $messages = [];

        // Helles Logo verarbeiten
        if (isset($_FILES['logo_light']) && $_FILES['logo_light']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['logo_light']['name']);
            $extension = strtolower($fileInfo['extension']);
            
            // Prüfe erlaubte Dateitypen
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception('Nur JPG, PNG und GIF Dateien sind erlaubt.');
            }

            $fileName = 'logo_light.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo_light']['tmp_name'], $uploadPath)) {
                // Speichere den Pfad in der Datenbank
                $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('logo_light', ?)");
                $relativePath = $uploadPath;
                $stmt->bind_param("s", $relativePath);
                $stmt->execute();
                $success = true;
                $messages[] = 'Helles Logo wurde gespeichert.';
            }
        }

        // Dunkles Logo verarbeiten
        if (isset($_FILES['logo_dark']) && $_FILES['logo_dark']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['logo_dark']['name']);
            $extension = strtolower($fileInfo['extension']);
            
            // Prüfe erlaubte Dateitypen
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception('Nur JPG, PNG und GIF Dateien sind erlaubt.');
            }

            $fileName = 'logo_dark.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo_dark']['tmp_name'], $uploadPath)) {
                // Speichere den Pfad in der Datenbank
                $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('logo_dark', ?)");
                $relativePath = $uploadPath;
                $stmt->bind_param("s", $relativePath);
                $stmt->execute();
                $success = true;
                $messages[] = 'Dunkles Logo wurde gespeichert.';
            }
        }

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => implode(' ', $messages)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Keine Änderungen vorgenommen.'
            ]);
        }

    } catch (Exception $e) {
        error_log("Fehler beim Logo-Upload: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern der Logos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Anfrage.'
    ]);
} 