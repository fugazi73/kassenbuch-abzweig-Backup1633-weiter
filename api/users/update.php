<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Prüfe Berechtigung
    if (!check_permission('manage_users')) {
        throw new Exception('Keine Berechtigung');
    }

    // Validiere Eingaben
    if (empty($_POST['id']) || empty($_POST['username']) || empty($_POST['role'])) {
        throw new Exception('Pflichtfelder fehlen');
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'], ['admin', 'user', 'chef']) ? $_POST['role'] : 'user';
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

    // Validiere Länge
    if (strlen($username) < 3) {
        throw new Exception('Benutzername muss mindestens 3 Zeichen lang sein');
    }
    if (!empty($password) && strlen($password) < 8) {
        throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
    }

    // Prüfe ob Benutzer existiert (außer bei sich selbst)
    $stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Benutzername existiert bereits');
    }

    // Update Benutzer
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, password = ?, role = ?, active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $username, $hashed_password, $role, $active, $id);
    } else {
        $stmt = $conn->prepare("UPDATE benutzer SET username = ?, role = ?, active = ? WHERE id = ?");
        $stmt->bind_param("ssii", $username, $role, $active, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Benutzer wurde erfolgreich aktualisiert'
        ]);
    } else {
        throw new Exception('Fehler beim Aktualisieren des Benutzers');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 