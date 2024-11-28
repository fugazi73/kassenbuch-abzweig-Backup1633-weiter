<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

if (!file_exists('vendor/autoload.php')) {
    die('Bitte führen Sie zuerst "composer install" aus');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
    $valid_roles = ['admin', 'user', 'chef'];
    $role = in_array($_POST['role'] ?? 'user', $valid_roles) ? $_POST['role'] : 'user';

    // Debug-Logging
    error_log("Rolle beim Erstellen: " . $role);

    // Prüfen der Mindestlängen
    if (strlen($username) < 3 || strlen($password) < 8) {
        echo json_encode([
            'success' => false,
            'message' => 'Benutzername oder Passwort zu kurz'
        ]);
        exit;
    }

    // Passwort hashen
    $hashed_password = hash_password($password);

    // Benutzer hinzufügen
    $stmt = $conn->prepare("INSERT INTO benutzer (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);

    header('Content-Type: application/json');
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Benutzer wurde erfolgreich erstellt"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Fehler beim Erstellen des Benutzers"
        ]);
    }
    exit;
}
?>
