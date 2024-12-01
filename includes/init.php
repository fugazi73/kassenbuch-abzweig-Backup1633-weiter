<?php
// Datenbank-Verbindung und grundlegende Einstellungen
require_once __DIR__ . '/../config.php';

// Lade die Einstellungen aus der Datenbank
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Logo-Pfade
$logo_light = $settings['logo_light'] ?? 'images/logo_light.png';
$logo_dark = $settings['logo_dark'] ?? 'images/logo_dark.png';

// Seitenname
$site_name = $settings['site_name'] ?? 'Kassenbuch';

// Basis-URL für Assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/help/') !== false;
$basePath = $isInSubfolder ? '..' : '.';

// Theme aus Cookie laden
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';