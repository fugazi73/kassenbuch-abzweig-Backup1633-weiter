<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    if (!isset($_POST['id'])) {
        throw new Exception('Keine ID angegeben');
    }

    $id = (int)$_POST['id'];

    // Verhindere das Löschen des eigenen Accounts
    if ($id === $_SESSION['user_id']) {
        throw new Exception('Sie können Ihren eigenen Account nicht löschen');
    }

    $stmt = $conn->prepare("DELETE FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Benutzer wurde gelöscht'
        ]);
    } else {
        throw new Exception('Fehler beim Löschen des Benutzers');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 