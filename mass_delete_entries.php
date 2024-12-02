<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

// Berechtigungsprüfung
if (!check_permission('delete_entries')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung zum Löschen von Einträgen']);
    exit;
}

// Lese und validiere die Eingabedaten
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine Einträge zum Löschen ausgewählt']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Konvertiere IDs in Integer und bereite SQL vor
    $ids = array_map('intval', $data['ids']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "DELETE FROM kassenbuch_eintraege WHERE id IN ($placeholders)";
    
    // Bereite Statement vor und führe es aus
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    
    if (!$stmt->execute()) {
        throw new Exception('Fehler beim Löschen der Einträge: ' . $stmt->error);
    }
    
    $deleted_count = $stmt->affected_rows;
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $deleted_count . ' Einträge wurden erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Fehler bei Massenlöschung: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 