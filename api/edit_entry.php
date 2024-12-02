<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

// Prüfe Bearbeitungsberechtigung
if (!check_permission('edit_entries')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung']);
    exit;
} 