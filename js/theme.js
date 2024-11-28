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
        const storedTheme = document.cookie.match(/theme=([^;]+)/)?.[1];
        if (storedTheme) {
            return storedTheme;
        }
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
        // Bootstrap Theme setzen
        document.documentElement.setAttribute('data-bs-theme', theme);
        
        // Theme-spezifische Klassen aktualisieren
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
                ? logo.getAttribute('data-dark-src')
                : logo.getAttribute('data-light-src');
            logo.src = logoPath;
        }
    }

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
}); 