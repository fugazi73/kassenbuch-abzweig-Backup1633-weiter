<?php
// Temporär für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
check_login();

// Logging für Debugging
error_log("Save User aufgerufen");

$valid_roles = ['admin', 'user', 'chef'];

try {
    // Prüfe Admin-Rechte
    if (!is_admin()) {
        throw new Exception('Zugriff verweigert');
    }

    // Prüfe Request-Methode
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Ungültige Request-Methode');
    }

    // Validiere Eingaben
    if (empty($_POST['username']) || empty($_POST['password'])) {
        throw new Exception('Benutzername und Passwort sind erforderlich');
    }

    $username = htmlspecialchars(strip_tags($_POST['username']));
    $password = $_POST['password'];
    $role = in_array($_POST['role'] ?? 'user', $valid_roles) ? $_POST['role'] : 'user';

    // Debug-Logging hinzufügen
    error_log("Empfangene Rolle: " . $_POST['role']);
    error_log("Gespeicherte Rolle: " . $role);

    // Prüfe Datenbankverbindung
    if (!$conn) {
        throw new Exception('Keine Datenbankverbindung');
    }

    // Prüfe ob Benutzer existiert
    $stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ?");
    if (!$stmt) {
        throw new Exception('Datenbankfehler: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception('Benutzername existiert bereits');
    }

    // Erstelle neuen Benutzer
    $hashed_password = hash_password($password);
    $insert_stmt = $conn->prepare("INSERT INTO benutzer (username, password, role) VALUES (?, ?, ?)");
    if (!$insert_stmt) {
        throw new Exception('Datenbankfehler: ' . $conn->error);
    }

    $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if (!$insert_stmt->execute()) {
        throw new Exception('Fehler beim Speichern: ' . $insert_stmt->error);
    }

    // Erfolgreiche Antwort
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Benutzer wurde erfolgreich erstellt'
    ]);

} catch (Exception $e) {
    error_log("Fehler in save_user.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}