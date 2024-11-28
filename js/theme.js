<<<<<<< HEAD
// Theme Management Klasse
class ThemeManager {
    constructor() {
        this.themeToggle = document.getElementById('themeToggle');
        this.icon = this.themeToggle?.querySelector('i');
        this.THEMES = {
            LIGHT: 'light',
            DARK: 'dark'
        };
        
        this.init();
    }

    init() {
        if (!this.themeToggle) return;
        
        // Initial Theme setzen
        this.setTheme(this.getPreferredTheme());
        
        // Event Listener
        this.themeToggle.addEventListener('click', () => this.toggleTheme());
        this.setupSystemThemeListener();
    }

    // Theme aus Cookie oder System-Präferenz
    getPreferredTheme() {
=======
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    
    // Theme aus Cookie laden oder System-Präferenz nutzen
    const getPreferredTheme = () => {
>>>>>>> 872be59ca604b9eee638b1d18a3feb2fdc091d7f
        const storedTheme = document.cookie.match(/theme=([^;]+)/)?.[1];
        if (storedTheme) {
            return storedTheme;
        }
<<<<<<< HEAD
        return window.matchMedia('(prefers-color-scheme: dark)').matches 
            ? this.THEMES.DARK 
            : this.THEMES.LIGHT;
    }

    // Theme Toggle Handler
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === this.THEMES.DARK 
            ? this.THEMES.LIGHT 
            : this.THEMES.DARK;
        this.setTheme(newTheme);
    }

    // Theme setzen und UI aktualisieren
    setTheme(theme) {
=======
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
>>>>>>> 872be59ca604b9eee638b1d18a3feb2fdc091d7f
        // Bootstrap Theme setzen
        document.documentElement.setAttribute('data-bs-theme', theme);
        
        // Theme-spezifische Klassen aktualisieren
<<<<<<< HEAD
        this.updateBodyClasses(theme);
        this.updateNavigation(theme);
        this.updateCards(theme);
        this.updateDropdowns(theme);
        this.updateButtons(theme);
        
        // Theme speichern
        this.saveTheme(theme);
        
        // UI aktualisieren
        this.updateUI(theme);
    }

    // UI Komponenten Update Methoden
    updateBodyClasses(theme) {
        const isDark = theme === this.THEMES.DARK;
        document.body.classList.toggle('bg-dark', isDark);
        document.body.classList.toggle('text-light', isDark);
    }

    updateNavigation(theme) {
        const isDark = theme === this.THEMES.DARK;
        document.querySelectorAll('.navbar, header').forEach(el => {
            el.classList.toggle('bg-light', !isDark);
            el.classList.toggle('navbar-light', !isDark);
            el.classList.toggle('bg-dark', isDark);
            el.classList.toggle('navbar-dark', isDark);
        });
    }

    updateCards(theme) {
        const isDark = theme === this.THEMES.DARK;
        document.querySelectorAll('.card').forEach(card => {
            card.classList.toggle('bg-dark', isDark);
            card.classList.toggle('border-secondary', isDark);
            card.classList.toggle('text-light', isDark);
        });
    }

    updateDropdowns(theme) {
        const isDark = theme === this.THEMES.DARK;
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.toggle('dropdown-menu-dark', isDark);
        });
    }

    updateButtons(theme) {
        const isDark = theme === this.THEMES.DARK;
        document.querySelectorAll('.btn-outline-secondary, .btn-outline-light').forEach(btn => {
            if (!btn.classList.contains('btn-outline-danger')) {
                btn.classList.toggle('btn-outline-light', isDark);
                btn.classList.toggle('btn-outline-secondary', !isDark);
            }
        });
    }

    updateUI(theme) {
        this.updateIcon(theme);
        this.updateLogo(theme);
    }

    updateIcon(theme) {
        if (this.icon) {
            this.icon.className = theme === this.THEMES.DARK 
                ? 'bi bi-sun-fill' 
                : 'bi bi-moon-stars';
        }
    }

    updateLogo(theme) {
        const logo = document.getElementById('siteLogo');
        if (logo) {
            const logoPath = theme === this.THEMES.DARK 
=======
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
>>>>>>> 872be59ca604b9eee638b1d18a3feb2fdc091d7f
                ? logo.getAttribute('data-dark-src')
                : logo.getAttribute('data-light-src');
            logo.src = logoPath;
        }
    }
<<<<<<< HEAD

    // Theme Persistenz
    saveTheme(theme) {
        document.cookie = `theme=${theme};path=/;max-age=31536000`; // 1 Jahr
    }

    // System Theme Change Listener
    setupSystemThemeListener() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!document.cookie.includes('theme=')) {
                this.setTheme(e.matches ? this.THEMES.DARK : this.THEMES.LIGHT);
            }
        });
    }
}

// Theme Manager initialisieren wenn DOM geladen ist
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
=======
    
    // System Dark Mode Änderungen überwachen
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!document.cookie.includes('theme=')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
>>>>>>> 872be59ca604b9eee638b1d18a3feb2fdc091d7f
}); 