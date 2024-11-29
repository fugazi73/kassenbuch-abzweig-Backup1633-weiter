<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

try {
    // Aktive Benutzer (in den letzten 24 Stunden)
    $active_users = $conn->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM kassenbuch_eintraege 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ")->fetch_assoc()['count'];

    // Letzte Aktivität
    $last_activity = $conn->query("
        SELECT MAX(created_at) as last_activity 
        FROM kassenbuch_eintraege
    ")->fetch_assoc()['last_activity'];

    echo json_encode([
        'success' => true,
        'active_users' => $active_users,
        'last_activity' => $last_activity ? date('d.m.Y H:i', strtotime($last_activity)) : 'Keine Aktivität'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 