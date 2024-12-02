<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

// Berechtigungsprüfung
if (!check_permission('delete_entries')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung']);
    exit;
}

try {
    // Lese Filter aus dem Request
    $data = json_decode(file_get_contents('php://input'), true);
    $filters = $data['filters'] ?? [];
    
    // Baue WHERE-Bedingung basierend auf den Filtern
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($filters)) {
        if (!empty($filters['monat'])) {
            $where_conditions[] = "DATE_FORMAT(datum, '%Y-%m') = ?";
            $params[] = $filters['monat'];
            $types .= 's';
        }
        
        if (!empty($filters['bemerkung'])) {
            $where_conditions[] = "bemerkung LIKE ?";
            $params[] = '%' . $filters['bemerkung'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['von_datum'])) {
            $where_conditions[] = "datum >= ?";
            $params[] = date('Y-m-d', strtotime(str_replace('.', '-', $filters['von_datum'])));
            $types .= 's';
        }
        
        if (!empty($filters['bis_datum'])) {
            $where_conditions[] = "datum <= ?";
            $params[] = date('Y-m-d', strtotime(str_replace('.', '-', $filters['bis_datum'])));
            $types .= 's';
        }
        
        if (!empty($filters['typ'])) {
            if ($filters['typ'] === 'einnahme') {
                $where_conditions[] = "einnahme > 0";
            } elseif ($filters['typ'] === 'ausgabe') {
                $where_conditions[] = "ausgabe > 0";
            }
        }
    }
    
    // Erstelle SQL-Query
    $sql = "SELECT id FROM kassenbuch_eintraege";
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $sql .= " ORDER BY datum DESC, id DESC";
    
    // Bereite Statement vor
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Führe Query aus
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Sammle alle IDs
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
    
    // Sende Ergebnis zurück
    echo json_encode([
        'success' => true,
        'ids' => $ids,
        'count' => count($ids)
    ]);
    
} catch (Exception $e) {
    error_log('Fehler beim Laden der Eintrags-IDs: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Einträge: ' . $e->getMessage()
    ]);
} 