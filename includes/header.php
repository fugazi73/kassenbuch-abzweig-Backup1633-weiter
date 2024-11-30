<?php
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';

// Bestimme den Base Path für Assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/help/') !== false;
$basePath = $isInSubfolder ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?php echo $savedTheme ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kassenbuch' ?></title>
    
    <!-- Bootstrap & Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= $basePath ?>/styles/theme.css" rel="stylesheet">
    <link href="<?= $basePath ?>/styles/header.css" rel="stylesheet">
    <link href="<?= $basePath ?>/styles/footer.css" rel="stylesheet">
    <link href="<?= $basePath ?>/styles/main.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="<?= $basePath ?>/js/main.js" defer></script>
    <script src="<?= $basePath ?>/js/theme.js" defer></script>
    <script src="<?= $basePath ?>/js/navigation.js" defer></script>
    
    <?php if (basename($_SERVER['PHP_SELF']) === 'kassenbuch.php'): ?>
        <script src="<?= $basePath ?>/js/kassenbuch.js" defer></script>
    <?php endif; ?>
    
    <?php if (basename($_SERVER['PHP_SELF']) === 'admin.php'): ?>
        <script src="<?= $basePath ?>/js/admin.js" defer></script>
    <?php endif; ?>
    
    <?php if (basename($_SERVER['PHP_SELF']) === 'changelog.php'): ?>
        <link href="<?= $basePath ?>/styles/changelog.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
            <div class="container py-2">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $basePath ?>/index.php">
                    <img src="<?= $basePath ?>/<?= $logo_light ?>" alt="Logo" height="32" class="theme-light">
                    <img src="<?= $basePath ?>/<?= $logo_dark ?>" alt="Logo" height="32" class="theme-dark">
                    <span><?= htmlspecialchars($site_name) ?></span>
                </a>

                <!-- Toggle Button für mobile Ansicht -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation -->
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" href="<?= $basePath ?>/kassenbuch.php">
                                <i class="bi bi-journal-plus"></i> Kassenbuch
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-gear"></i> Administration
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item" href="<?= $basePath ?>/admin.php">
                                        <i class="bi bi-tools"></i> Administration</a></li>
                                    <li><a class="dropdown-item" href="<?= $basePath ?>/admin_users.php">
                                        <i class="bi bi-people"></i> Benutzerverwaltung</a></li>
                                    <li><a class="dropdown-item" href="<?= $basePath ?>/backup.php">
                                        <i class="bi bi-download"></i> Backup & Restore</a></li>
                                    <li><a class="dropdown-item" href="<?= $basePath ?>/import_excel.php">
                                        <i class="bi bi-file-excel"></i> Excel-Import</a></li>
                                    <li><a class="dropdown-item" href="<?= $basePath ?>/settings.php">
                                        <i class="bi bi-gear"></i> Einstellungen</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>/export.php">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="helpDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-question-circle"></i> Hilfe
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="helpDropdown">
                                <li><a class="dropdown-item" href="<?= $basePath ?>/help/changelog.php">
                                    <i class="bi bi-journal-text"></i> Changelog</a></li>
                                <li><a class="dropdown-item" href="<?= $basePath ?>/help/manual.php">
                                    <i class="bi bi-book"></i> Handbuch</a></li>
                                <li><a class="dropdown-item" href="<?= $basePath ?>/help/about.php">
                                    <i class="bi bi-info-circle"></i> Über</a></li>
                            </ul>
                        </li>
                    </ul>

                    <!-- Rechte Navigation -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <button class="nav-link theme-toggle" id="themeToggle">
                                <i class="bi bi-moon-stars"></i>
                            </button>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link user-info">
                                <i class="bi bi-person"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Benutzer') ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</body>
</html> 