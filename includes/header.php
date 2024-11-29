<?php
// Am Anfang des Headers, vor dem DOCTYPE
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?php echo $savedTheme ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kassenbuch' ?></title>
    
    <!-- Bootstrap & Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<<<<<<< HEAD
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="styles/theme.css" rel="stylesheet">
    <link href="styles/header.css" rel="stylesheet">
    <link href="styles/footer.css" rel="stylesheet">
=======
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles/main.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/main.js" defer></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'kassenbuch.php'): ?>
        <script src="js/kassenbuch.js" defer></script>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'admin.php'): ?>
        <script src="js/admin.js" defer></script>
    <?php endif; ?>
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
</head>
<body>
    <header class="main-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <!-- Logo und Firmenname -->
                <a class="navbar-brand" href="index.php">
                    <img src="images/ME.png" alt="ME Logo">
                    ME
                </a>

                <!-- Toggle Button für mobile Ansicht -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation -->
<<<<<<< HEAD
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" href="kassenbuch.php">
                                <i class="bi bi-journal-plus"></i> Kassenbuch
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'administration' ? 'active' : '' ?>" href="administration.php">
=======
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-primary" href="kassenbuch.php">
                        <i class="bi bi-journal-text"></i> Kassenbuch
                    </a>
                    <a class="btn btn-outline-secondary" href="export.php">
                        <i class="bi bi-download"></i> Export
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                    id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
                                <i class="bi bi-gear"></i> Administration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="export.php">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                    </ul>

                    <!-- Rechte Navigation -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <button class="nav-link theme-toggle" id="themeToggle">
                                <i class="bi bi-sun-fill"></i>
                            </button>
<<<<<<< HEAD
                        </li>
                        <li class="nav-item">
                            <span class="nav-link user-info">
                                <i class="bi bi-person"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Benutzer') ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
=======
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li>
                                    <a class="dropdown-item" href="admin_users.php">
                                        <i class="bi bi-people"></i> Benutzerverwaltung
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="backup.php">
                                        <i class="bi bi-download"></i> Backup & Restore
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="settings.php">
                                        <i class="bi bi-gear"></i> Einstellungen
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Rechte Seite -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <button id="themeToggle" class="btn btn-outline-secondary" onclick="toggleTheme()">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <span class="text-muted"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
                </div>
            </div>
        </nav>
    </header>
<<<<<<< HEAD

    <main class="container my-4">
        <!-- JavaScript Dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/theme.js" defer></script>
        <script src="js/navigation.js" defer></script>
=======
    <main class="flex-grow-1">
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
    </main>
</body>
</html> 