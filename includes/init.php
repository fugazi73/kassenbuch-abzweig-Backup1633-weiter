<?php
// Korrigiere den Pfad zur config.php
$root_dir = dirname(__DIR__); // Gehe ein Verzeichnis nach oben
require_once($root_dir . '/config.php');

// Lade Einstellungen aus der Datenbank
$settings = [];
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Globale Variablen für Templates
$site_name = $settings['site_name'] ?? '';
$logo_light = $settings['logo_light'] ?? 'images/logo_light.png';
$logo_dark = $settings['logo_dark'] ?? 'images/logo_dark.png'; 