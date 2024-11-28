<?php
function is_admin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'chef');
}

function is_chef() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'chef';
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Neue Datei für gemeinsame Funktionen
function generateBelegNr($datum, $conn) {
    $year_month = date('y-m', strtotime($datum)); // Gibt z.B. "23-12" für Dezember 2023
    
    // Hole die höchste Nummer für diesen Monat
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(beleg_nr, '-', -1) AS UNSIGNED)) as last_nr 
                           FROM kassenbuch_eintraege 
                           WHERE beleg_nr LIKE ?");
    $pattern = $year_month . "-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $next_nr = ($row['last_nr'] ?? 0) + 1;
    
    return $year_month . '-' . str_pad($next_nr, 3, '0', STR_PAD_LEFT);
}

function get_pagination_url($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

function hash_password($password) {
    if (empty($password)) {
        throw new Exception('Passwort darf nicht leer sein');
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

// Neue Funktion für Chef-spezifische Berechtigungen
function check_chef_permission() {
    if (!is_chef()) {
        header('Location: error.php?message=Keine Berechtigung');
        exit;
    }
}

function is_chef_or_admin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'chef' || $_SESSION['user_role'] === 'admin');
}