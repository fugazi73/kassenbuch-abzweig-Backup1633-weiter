<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Keine ID angegeben');
    }

    $id = intval($_POST['id']);
    
    // Überprüfen Sie die Berechtigung
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'chef'])) {
        throw new Exception('Keine Berechtigung zum Löschen');
    }

    $stmt = $conn->prepare("DELETE FROM kassenbuch_eintraege WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Fehler beim Löschen des Eintrags');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
