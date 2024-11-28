<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
    exit;
}

// Debug-Logging hinzufügen
error_log("UPDATE USER - Empfangene Daten: " . print_r($_POST, true));

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Fehlende Parameter']);
    exit;
}

$id = (int)$_POST['id'];
$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
$password = $_POST['password'] ?? '';
$role = $_POST['role'];

// Validierung der Rolle
$valid_roles = ['admin', 'user', 'chef'];
if (!in_array($role, $valid_roles)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ungültige Rolle']);
    exit;
}

try {
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, password = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }

    // Debug-Logging vor der Ausführung
    error_log("UPDATE USER - SQL Ausführung mit Rolle: " . $role);

    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Benutzer wurde aktualisiert']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Aktualisieren des Benutzers']);
    }
} catch (Exception $e) {
    error_log("UPDATE USER - Fehler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 