<?php
// Am Anfang des Headers, vor dem DOCTYPE
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?php echo $savedTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kassenbuch' ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="styles/theme.css" rel="stylesheet">
    <link href="styles/main.css" rel="stylesheet">
    <link href="styles/navigation.css" rel="stylesheet">
    <link href="styles/footer.css" rel="stylesheet">
    <link href="styles/kassenbuch.css" rel="stylesheet">

    <!-- Theme Toggle Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        
        // Theme aus Cookie laden oder System-Präferenz nutzen
        const getPreferredTheme = () => {
            const storedTheme = document.cookie.match(/theme=([^;]+)/)?.[1];
            if (storedTheme) {
                return storedTheme;
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };
        
        // Initial Theme setzen
        setTheme(getPreferredTheme());
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    });

    function setTheme(theme) {
        // Bootstrap Theme setzen
        document.documentElement.setAttribute('data-bs-theme', theme);
        
        // Theme-spezifische Klassen aktualisieren
        if (theme === 'dark') {
            document.body.classList.add('bg-dark');
            document.body.classList.add('text-light');
            
            // Navbar/Header anpassen
            document.querySelectorAll('.navbar, header').forEach(el => {
                el.classList.remove('bg-light', 'navbar-light');
                el.classList.add('bg-dark', 'navbar-dark');
            });
            
            // Cards anpassen
            document.querySelectorAll('.card').forEach(card => {
                card.classList.add('bg-dark', 'border-secondary');
                card.classList.add('text-light');
            });
            
            // Dropdowns anpassen
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('dropdown-menu-dark');
            });
            
            // Buttons anpassen
            document.querySelectorAll('.btn-outline-secondary').forEach(btn => {
                btn.classList.add('btn-outline-light');
                btn.classList.remove('btn-outline-secondary');
            });
        } else {
            document.body.classList.remove('bg-dark');
            document.body.classList.remove('text-light');
            
            // Navbar/Header zurücksetzen
            document.querySelectorAll('.navbar, header').forEach(el => {
                el.classList.add('bg-light', 'navbar-light');
                el.classList.remove('bg-dark', 'navbar-dark');
            });
            
            // Cards zurücksetzen
            document.querySelectorAll('.card').forEach(card => {
                card.classList.remove('bg-dark', 'border-secondary');
                card.classList.remove('text-light');
            });
            
            // Dropdowns zurücksetzen
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('dropdown-menu-dark');
            });
            
            // Buttons zurücksetzen
            document.querySelectorAll('.btn-outline-light').forEach(btn => {
                btn.classList.add('btn-outline-secondary');
                btn.classList.remove('btn-outline-light');
            });
        }
        
        // Theme in Cookie speichern
        document.cookie = `theme=${theme};path=/;max-age=31536000`;
        // UI aktualisieren
        updateIcon(theme);
        updateLogo(theme);
    }

    function updateIcon(theme) {
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars';
    }
    
    function updateLogo(theme) {
        const logo = document.getElementById('siteLogo');
        if (logo) {
            const logoPath = theme === 'dark' 
                ? logo.getAttribute('data-dark-src')
                : logo.getAttribute('data-light-src');
            logo.src = logoPath;
        }
    }
    
    // System Dark Mode Änderungen überwachen
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!document.cookie.includes('theme=')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-light border-bottom">
        <div class="container">
            <div class="d-flex align-items-center py-2">
                <!-- Logo und Titel -->
                <a href="index.php" class="d-flex align-items-center text-decoration-none me-4">
                    <img src="<?= $settings['logo_light'] ?? 'images/logo_light.png' ?>" 
                         data-light-src="<?= $settings['logo_light'] ?? 'images/logo_light.png' ?>"
                         data-dark-src="<?= $settings['logo_dark'] ?? 'images/logo_dark.png' ?>"
                         alt="Logo" 
                         class="me-2" 
                         style="height: 35px;" 
                         id="siteLogo">
                    <span class="fs-5 text-dark">Kassenbuch</span>
                </a>
                
                <!-- Navigation -->
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-primary" href="kassenbuch.php">
                        <i class="bi bi-journal-text"></i> Kassenbuch
                    </a>
                    <a class="btn btn-outline-secondary" href="export.php">
                        <i class="bi bi-download"></i> Export
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Administration
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="admin.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="benutzerverwaltung.php">
                                    <i class="bi bi-people"></i> Benutzerverwaltung</a></li>
                                <li><a class="dropdown-item" href="backup.php">
                                    <i class="bi bi-download"></i> Backup & Restore</a></li>
                                <li><a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear"></i> Einstellungen</a></li>
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
                </div>
            </div>
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