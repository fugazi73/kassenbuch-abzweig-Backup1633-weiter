<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    if (!isset($_POST['id'])) {
        throw new Exception('Benutzer ID fehlt');
    }

    $id = (int)$_POST['id'];
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $role = in_array($_POST['role'] ?? 'user', ['admin', 'user', 'chef']) ? $_POST['role'] : 'user';
    
    // Optional: Passwort ändern
    if (!empty($_POST['password'])) {
        $password = hash_password($_POST['password']);
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, password = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Fehler beim Aktualisieren');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 