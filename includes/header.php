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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="styles/theme.css" rel="stylesheet">
    <link href="styles/header.css" rel="stylesheet">
    <link href="styles/footer.css" rel="stylesheet">
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
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch' ? 'active' : '' ?>" href="kassenbuch.php">
                                <i class="bi bi-journal-plus"></i> Kassenbuch
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'administration' ? 'active' : '' ?>" href="administration.php">
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
                </div>
            </div>
        </nav>
    </header>

    <main class="container my-4">
        <!-- JavaScript Dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/theme.js" defer></script>
        <script src="js/navigation.js" defer></script>
    </main>
</body>
</html> 