<?php
// Authentifizierungsfunktionen
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && 
           in_array($_SESSION['user_role'], ['admin', 'chef']);
}

function is_chef() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'chef';
}

function is_chef_or_admin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'chef' || $_SESSION['user_role'] === 'admin');
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function check_chef_permission() {
    if (!is_chef()) {
        header('Location: error.php?message=Keine Berechtigung');
        exit;
    }
}

// Passwort-Funktionen
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Fehlerbehandlung und Logging
function handle_error($error, $redirect = true) {
    error_log($error);
    $_SESSION['error'] = $error;
    if ($redirect) {
        header('Location: error.php');
        exit;
    }
}

function handle_forbidden() {
    http_response_code(403);
    header('Location: forbidden.php');
    exit;
}

function log_action($message, $level = 'info') {
    $log_file = __DIR__ . '/logs/' . date('Y-m') . '.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date][$level] $message\n";
    error_log($log_message, 3, $log_file);
}

// Datenbankfunktionen
function generateBelegNr($datum, $conn) {
    $year_month = date('y-m', strtotime($datum));
    
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

function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
            setting_value = ?
    ");
    
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

function getSetting($conn, $key, $default = null) {
    $stmt = $conn->prepare("
        SELECT setting_value 
        FROM settings 
        WHERE setting_key = ?
    ");
    
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return $default;
}

// Hilfsfunktionen
function get_pagination_url($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}