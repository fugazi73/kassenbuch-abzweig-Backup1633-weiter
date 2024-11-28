<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Keine ID angegeben']);
    exit;
}

$id = (int)$_POST['id'];

// Verhindere das Löschen des eigenen Accounts
if ($id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Sie können Ihren eigenen Account nicht löschen']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Benutzer wurde gelöscht']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen des Benutzers']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 