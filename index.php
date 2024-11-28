<?php
require_once 'config.php';

// Wenn der Benutzer bereits eingeloggt ist, zum Kassenbuch weiterleiten
if (isset($_SESSION['user_id'])) {
    header("Location: kassenbuch.php");
    exit;
}

// Wenn nicht eingeloggt, zur Login-Seite weiterleiten
header("Location: login.php");
exit;
?> 