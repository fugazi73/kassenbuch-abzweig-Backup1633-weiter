<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

// Nur Admins dürfen diese Funktion nutzen
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Prüfe ob IDs übergeben wurden
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ids']) || !is_array($data['ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine IDs angegeben']);
    exit;
}

$ids = array_map('intval', $data['ids']);
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine gültigen IDs']);
    exit;
}

try {
    $conn->begin_transaction();

    // Lösche die ausgewählten Einträge
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "DELETE FROM kassenbuch_eintraege WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => count($ids) . ' Einträge wurden gelöscht']);
    } else {
        throw new Exception('Fehler beim Löschen der Einträge');
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 