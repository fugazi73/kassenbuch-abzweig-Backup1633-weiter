<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Prüfe Berechtigung
if (!check_permission('manage_users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

// Stelle sicher, dass es ein POST-Request ist
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    exit;
}

header('Content-Type: application/json');

try {
    // Lese POST-Daten
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Validierungen
    if (empty($username) || empty($password) || empty($role)) {
        throw new Exception('Alle Felder müssen ausgefüllt werden');
    }
    
    if (strlen($username) < 3) {
        throw new Exception('Benutzername muss mindestens 3 Zeichen lang sein');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
    }
    
    $allowed_roles = ['user', 'chef', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        throw new Exception('Ungültige Rolle');
    }
    
    // Prüfe ob Benutzer bereits existiert
    $check_stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception('Benutzername bereits vergeben');
    }
    
    // Hash das Passwort
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Füge neuen Benutzer hinzu
    $stmt = $conn->prepare("INSERT INTO benutzer (username, password, role, active) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Benutzer erfolgreich erstellt'
        ]);
    } else {
        throw new Exception('Fehler beim Erstellen des Benutzers');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 