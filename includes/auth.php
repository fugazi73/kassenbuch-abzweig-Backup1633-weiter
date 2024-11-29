<?php
// Nur Session-Check und Umleitung

// Prüfe ob der Benutzer eingeloggt ist und leite ggf. um
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
} 