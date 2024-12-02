<?php
// Prüfe ob die Session bereits gestartet wurde
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfe ob der Benutzer eingeloggt ist
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Prüfe ob der Benutzer Admin-Rechte hat
function require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /error.php?message=Keine%20Berechtigung');
        exit;
    }
}

// Prüfe ob der Benutzer Chef oder Admin ist
function require_chef_or_admin() {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['chef', 'admin'])) {
        header('Location: /error.php?message=Keine%20Berechtigung');
        exit;
    }
}

// Hole den Benutzernamen des aktuell eingeloggten Benutzers
function get_current_username() {
    return $_SESSION['username'] ?? 'Unbekannt';
}

// Hole die aktuelle Benutzerrolle
function get_current_role() {
    return $_SESSION['user_role'] ?? null;
}

// Prüfe ob der aktuelle Benutzer eine bestimmte Rolle hat
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Prüfe ob der aktuelle Benutzer eine von mehreren Rollen hat
function has_any_role($roles) {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

// Prüfe ob der Benutzer eingeloggt ist und leite ggf. um
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
} 