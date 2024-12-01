<?php
// Starte Session, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfe ob die Grundfunktionen bereits geladen wurden
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/../functions.php';
}

// Prüfe ob die Datenbank-Verbindung bereits besteht
if (!isset($conn)) {
    require_once __DIR__ . '/../config.php';
}

// Lade die Einstellungen aus der Datenbank
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Logo-Pfade
$logo_light = isset($settings['logo_light']) ? $settings['logo_light'] : 'images/logo_light.png';
$logo_dark = isset($settings['logo_dark']) ? $settings['logo_dark'] : 'images/logo_dark.png';

// Seitenname
$site_name = $settings['site_name'] ?? 'Kassenbuch';

// Basis-URL für Assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/help/') !== false;
$basePath = $isInSubfolder ? '..' : '.';

// Theme aus Cookie laden
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';

// Aktuelle Seite ermitteln
$current_page = basename($_SERVER['PHP_SELF'], '.php');