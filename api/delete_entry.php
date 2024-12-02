<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

// Prüfe Löschberechtigung
if (!check_permission('delete_entries')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung']);
    exit;
} 