<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

// Prüfe Berechtigung zum Hinzufügen
if (!check_permission('add_entries')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung']);
    exit;
} 