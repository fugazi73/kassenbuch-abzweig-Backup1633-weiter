<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Prüfe Admin-Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Validiere Eingaben
    if (empty($_POST['username']) || empty($_POST['password'])) {
        throw new Exception('Benutzername und Passwort sind erforderlich');
    }

    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $role = in_array($_POST['role'] ?? 'user', ['admin', 'user', 'chef']) ? $_POST['role'] : 'user';

    // Prüfe ob Benutzer existiert
    $stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Benutzername existiert bereits');
    }

    // Erstelle neuen Benutzer
    $hashed_password = hash_password($password);
    $stmt = $conn->prepare("INSERT INTO benutzer (username, password, role) VALUES (?, ?, ?)");
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}