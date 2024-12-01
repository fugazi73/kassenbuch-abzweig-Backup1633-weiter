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
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/help/') !== false;
$basePath = $isInSubfolder ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?= $savedTheme ?>">
<head>
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
        padding: 0.5rem 0;
        background-color: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
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
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="max-width-container">
                <a class="navbar-brand" href="<?= $basePath ?>/index.php">
                    <img src="<?= $basePath ?>/<?= $savedTheme === 'dark' ? $logo_dark : $logo_light ?>" 
                         alt="Logo">
                </a>

                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" 
                               href="<?= $basePath ?>/kassenbuch.php">
                                <i class="bi bi-journal-plus"></i> Kassenbuch
                            </a>
                        </li>
                        
                        <?php if (is_chef() || is_admin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" 
                               data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Administration
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/admin.php">
                                        <i class="bi bi-tools"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/admin_users.php">
                                        <i class="bi bi-people"></i> Benutzerverwaltung
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/backup.php">
                                        <i class="bi bi-download"></i> Backup & Restore
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/settings.php">
                                        <i class="bi bi-gear"></i> Einstellungen
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/import_excel.php">
                                        <i class="bi bi-file-earmark-excel"></i> Excel Import
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $basePath ?>/export_history.php">
                                        <i class="bi bi-clock-history"></i> Export Historie
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>/export.php">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                        
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