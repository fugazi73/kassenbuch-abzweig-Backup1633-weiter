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
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['role'])) {
        throw new Exception('Alle Felder müssen ausgefüllt werden');
    }

    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $role = in_array($_POST['role'], ['admin', 'user', 'chef']) ? $_POST['role'] : 'user';

    // Validiere Länge
    if (strlen($username) < 3) {
        throw new Exception('Benutzername muss mindestens 3 Zeichen lang sein');
    }
    if (strlen($password) < 8) {
        throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
    }

    // Prüfe ob Benutzer existiert
    $stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Benutzername existiert bereits');
    }

    // Erstelle neuen Benutzer
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO benutzer (username, password, role, active) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Benutzer wurde erfolgreich erstellt'
        ]);
    } else {
        throw new Exception('Fehler beim Erstellen des Benutzers');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 