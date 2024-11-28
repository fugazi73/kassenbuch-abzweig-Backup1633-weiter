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
    
    <!-- jQuery first, then Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 CSS und JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Bootstrap & Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="styles/theme.css" rel="stylesheet">
    <link href="styles/header.css" rel="stylesheet">
    <link href="styles/footer.css" rel="stylesheet">
    
    <?php
    // Seiten-spezifische Styles
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if (file_exists("styles/{$current_page}.css")) {
        echo "<link href='styles/{$current_page}.css' rel='stylesheet'>";
    }
    ?>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <!-- Logo und Firmenname -->
                <a class="navbar-brand" href="index.php">
                    <img src="images/ME.png" alt="ME Logo">
                    ME
                </a>

                <!-- Hauptnavigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" href="kassenbuch.php">
                            <i class="bi bi-journal-plus"></i> Kassenbuch
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Export Button -->
                        <li class="nav-item">
                            <a href="export.php" class="export-btn">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Rechte Navigation -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Administrator', 'admin', 'Admin', 'Chef'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $current_page === 'admin' ? 'active' : '' ?>" href="admin.php">
                                        <i class="bi bi-gear"></i> Administration
                                    </a>
                                </li>
                            <?php endif; ?>
                            <!-- Theme Toggler -->
                            <li class="nav-item">
                                <button class="nav-link" id="themeToggle">
                                    <i class="bi bi-sun-fill"></i>
                                </button>
                            </li>
                            <!-- Logout -->
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
    <main class="flex-grow-1">
        <!-- JavaScript Dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/theme.js" defer></script>
        <script src="js/navigation.js" defer></script>
    </main>
</body>
</html> 