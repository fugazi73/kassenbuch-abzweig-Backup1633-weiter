<?php
header('Content-Type: application/json');

try {
    // JSON-Daten empfangen
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['columnName'])) {
        throw new Exception('Spaltenname fehlt');
    }

    // Hier Ihre Logik zum Löschen der Spalte aus der Datenbank
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 