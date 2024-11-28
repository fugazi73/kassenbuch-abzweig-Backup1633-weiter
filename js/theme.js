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
        document.cookie = `theme=${theme};path=/;max-age=31536000`; // 1 Jahr
        
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
}); 