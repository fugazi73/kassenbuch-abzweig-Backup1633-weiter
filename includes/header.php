<?php
// Starte Session, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lade die grundlegenden Funktionen
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';

// Lade die Einstellungen
require_once __DIR__ . '/init.php';

// Prüfe Login-Status, aber überspringe für Login-Seite
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if ($current_page !== 'login' && !is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Theme aus Cookie laden
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';

// Bestimme den Base Path für Assets
$current_path = $_SERVER['PHP_SELF'];
$isInSubfolder = strpos($current_path, '/user/') !== false || 
                 strpos($current_path, '/help/') !== false;
$basePath = $isInSubfolder ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?= $savedTheme ?>">
<head>
    <!-- Sofortige Theme-Anwendung um FOUC zu verhindern -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || '<?= $savedTheme ?>';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            // Verhindere Flackern beim Laden
            document.documentElement.style.visibility = 'hidden';
            document.addEventListener('DOMContentLoaded', function() {
                document.documentElement.style.visibility = '';
            });
        })();
    </script>
    <style>
        /* Smooth Transitions für Theme-Wechsel */
        :root {
            transition: background-color 0.15s ease-in-out, 
                        color 0.15s ease-in-out, 
                        border-color 0.15s ease-in-out;
        }
        
        /* Verhindere Flackern von Bootstrap-Komponenten */
        .card, .navbar, .dropdown-menu, .btn, .form-control {
            transition: background-color 0.15s ease-in-out, 
                        border-color 0.15s ease-in-out,
                        color 0.15s ease-in-out;
        }
    </style>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? $site_name ?></title>
    
    <!-- Bootstrap & Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $basePath ?>/styles/footer.css" rel="stylesheet">
    <link href="<?= $basePath ?>/styles/main.css" rel="stylesheet">
    
    <!-- Seiten-spezifische Styles -->
    <?php if ($current_page === 'changelog'): ?>
        <link href="<?= $basePath ?>/styles/changelog.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="<?= $basePath ?>/js/theme.js" defer></script>
    <script src="<?= $basePath ?>/js/main.js" defer></script>
    <script src="<?= $basePath ?>/js/navigation.js" defer></script>
    <script src="<?= $basePath ?>/assets/js/shortcuts.js" defer></script>
    
    <!-- Seiten-spezifische Scripts -->
    <?php if ($current_page === 'kassenbuch'): ?>
        <script src="<?= $basePath ?>/js/kassenbuch/kassenbuch.js" defer></script>
    <?php endif; ?>
    <?php if ($current_page === 'admin'): ?>
        <script src="<?= $basePath ?>/js/admin.js" defer></script>
    <?php endif; ?>
    <style>
    .max-width-container {
        max-width: 1320px;
        margin: 0 auto;
        width: 100%;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .navbar {
        padding: 1.1em 0 !important;
        background-color: var(--bs-tertiary-bg) !important;
        border-bottom: 1px solid var(--bs-border-color) !important;
    }
    .navbar-brand {
        margin-right: 2rem;
        padding: 0;
    }
    .navbar-brand img {
        height: 40px;
        width: auto;
        display: block;
    }
    .navbar .max-width-container {
        display: flex;
        align-items: center;
    }
    .navbar-collapse {
        flex-grow: 1;
    }
    /* Konsistente Farben für Dropdown-Menüs */
    .dropdown-menu {
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }
    /* Konsistente Hover-Effekte */
    .dropdown-item:hover {
        background-color: var(--bs-tertiary-bg);
    }
    /* Aktive Menüpunkte */
    .nav-link.active {
        color: var(--bs-primary) !important;
    }
    /* Konsistente Navbar über alle Seiten */
    .navbar {
        padding: 0.5rem 0;
        background-color: var(--bs-tertiary-bg) !important;
        border-bottom: 1px solid var(--bs-border-color) !important;
    }
    /* Konsistente Footer-Darstellung */
    .footer {
        background-color: var(--bs-tertiary-bg) !important;
        border-top: 1px solid var(--bs-border-color) !important;
    }
    /* Deutlichere Trennung für den neuen Eintrag im Dark Mode */
    [data-bs-theme="dark"] .quick-entry-section {
        background-color: var(--bs-dark);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    /* Konsistente Hintergrundfarben */
    .bg-body-tertiary {
        background-color: var(--bs-tertiary-bg) !important;
    }
    /* Verbesserte Kontraste im Dark Mode */
    [data-bs-theme="dark"] .card {
        border-color: var(--bs-border-color);
    }
    [data-bs-theme="dark"] .dropdown-menu {
        background-color: var(--bs-tertiary-bg);
        border-color: var(--bs-border-color);
    }
    /* Eingabebereich Styles */
    .quick-entry-section .bg-body-tertiary {
        background-color: var(--bs-tertiary-bg) !important;
        border: 1px solid var(--bs-border-color);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    }

    /* Light Theme Anpassungen */
    [data-bs-theme="light"] .quick-entry-section .bg-body-tertiary {
        background-color: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.125);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    /* Dark Theme Anpassungen */
    [data-bs-theme="dark"] .quick-entry-section .bg-body-tertiary {
        background-color: var(--bs-dark) !important;
        border: 1px solid var(--bs-border-color);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
    }

    /* Form Controls im Eingabebereich */
    .quick-entry-section .form-control,
    .quick-entry-section .form-select,
    .quick-entry-section .input-group-text {
        border-color: var(--bs-border-color);
    }

    /* Hover-Effekte für Formularelemente */
    .quick-entry-section .form-control:hover,
    .quick-entry-section .form-select:hover {
        border-color: var(--bs-primary);
    }

    /* Focus-Styles */
    .quick-entry-section .form-control:focus,
    .quick-entry-section .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }

    /* Select2 Anpassungen */
    .quick-entry-section .select2-container--bootstrap-5 .select2-selection {
        border-color: var(--bs-border-color);
    }

    .quick-entry-section .select2-container--bootstrap-5 .select2-selection:hover {
        border-color: var(--bs-primary);
    }

    /* Filter-Bereich Spacing */
    .filter-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }

    /* Überschrift im Filter */
    .filter-section h3 {
        margin-bottom: 1.5rem;
    }

    /* Abstand zwischen den Filter-Elementen */
    .filter-section .row {
        --bs-gutter-y: 1rem;
    }

    /* Filter Labels */
    .filter-section .form-label {
        margin-bottom: 0.5rem;
    }

    /* Filter Buttons Container */
    .filter-section .d-flex.align-items-end {
        margin-top: 0.5rem;
    }

    /* Light Theme Anpassungen */
    [data-bs-theme="light"] .filter-section {
        background-color: #ffffff;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    /* Dark Theme Anpassungen */
    [data-bs-theme="dark"] .filter-section {
        background-color: var(--bs-dark);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
    }
    </style>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Zentrale Alert-Funktion -->
    <script src="js/alerts.js"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="styles/custom.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="max-width-container">
                <a class="navbar-brand d-flex align-items-center" href="<?= $basePath ?>/index.php">
                    <img src="<?= $basePath ?>/<?= $savedTheme === 'dark' ? $logo_dark : $logo_light ?>" 
                         alt="Logo">
                    <span class="ms-2"><?= htmlspecialchars($site_name) ?></span>
                </a>

                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <?php if (check_permission('view_cashbook')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" 
                               href="<?= $basePath ?>/kassenbuch.php">
                                <i class="bi bi-journal-plus"></i> Kassenbuch
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (check_permission('view_admin_menu')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" 
                               data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Administration
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (check_permission('view_dashboard')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/admin.php">
                                        <i class="bi bi-tools"></i> Dashboard
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (check_permission('manage_users')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/admin_users.php">
                                        <i class="bi bi-people"></i> Benutzerverwaltung
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (check_permission('manage_permissions')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/admin_permissions.php">
                                        <i class="bi bi-shield-lock"></i> Berechtigungen
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (check_permission('manage_backup')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/backup.php">
                                        <i class="bi bi-download"></i> Backup & Restore
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (check_permission('manage_settings')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/settings.php">
                                        <i class="bi bi-gear"></i> Einstellungen
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (check_permission('import_excel')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/import.php">
                                        <i class="bi bi-file-earmark-excel"></i> Excel Import
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <?php if (check_permission('export')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>/export.php">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="helpDropdown" 
                               data-bs-toggle="dropdown">
                                <i class="bi bi-question-circle"></i> Hilfe
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/help/changelog.php">
                                        <i class="bi bi-journal-text"></i> Changelog
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/help/manual.php">
                                        <i class="bi bi-book"></i> Handbuch
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/help/about.php">
                                        <i class="bi bi-info-circle"></i> Über
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <button class="nav-link theme-toggle" id="themeToggle">
                                <i class="bi bi-moon-stars"></i>
                            </button>
                        </li>
                        <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person"></i> 
                                <?= htmlspecialchars($_SESSION['username'] ?? 'Benutzer') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/user/password.php">
                                        <i class="bi bi-key"></i> Passwort ändern
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/user/profile.php">
                                        <i class="bi bi-person-gear"></i> Profil bearbeiten
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1">
        <div class="max-width-container py-4">
</body>
</html> 