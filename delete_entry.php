<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Prüfe ob ID übergeben wurde
if (!isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine ID angegeben']);
    exit;
}

$id = intval($_POST['id']);

// Lösche den Eintrag
$sql = "DELETE FROM kassenbuch_eintraege WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler']);
} 