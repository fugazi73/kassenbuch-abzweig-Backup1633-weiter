<?php
// Aktuelle Seite ermitteln
$current_page = basename($_SERVER['PHP_SELF']);

// Funktion zur Überprüfung der Admin-Rolle
function is_admin_role($role) {
    return in_array($role, ['Administrator', 'admin', 'Admin']);
}
?>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <div class="row align-items-center w-100">
            <div class="col-auto">
                <h2 class="mb-0">
                    <i class="bi bi-journal-text"></i> Kassenbuch
                </h2>
            </div>
            <div class="col">
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'kassenbuch.php' ? 'active' : '' ?>" href="kassenbuch.php">
                                <i class="bi bi-journal-text"></i> Kassenbuch
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_role']) && is_admin_role($_SESSION['user_role'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="bi bi-gear"></i> Administration
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'export.php' ? 'active' : '' ?>" href="export.php">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-auto">
                <button id="themeToggle" class="btn btn-outline-secondary">
                    <i class="bi bi-moon"></i> Dunkelmodus
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap Bundle NACH der Navigation -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const themeIcon = themeToggle.querySelector('i');
    const themeText = themeToggle.lastChild;
    
    // Prüfe gespeichertes Theme
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', currentTheme);
    updateThemeButton(currentTheme === 'dark');

    // Theme Wechsel Funktion
    function switchTheme() {
        const isDark = html.getAttribute('data-theme') === 'light';
        const theme = isDark ? 'dark' : 'light';
        
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeButton(isDark);
        
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
    }

    function updateThemeButton(isDark) {
        themeIcon.className = isDark ? 'bi bi-sun' : 'bi bi-moon';
        themeText.textContent = isDark ? ' Hellmodus' : ' Dunkelmodus';
    }

    themeToggle.addEventListener('click', switchTheme);
});
</script> 