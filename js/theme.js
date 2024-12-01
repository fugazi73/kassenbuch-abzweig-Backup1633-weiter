// Theme Management Klasse
class ThemeManager {
    constructor() {
        this.themeToggle = document.getElementById('themeToggle');
        this.icon = this.themeToggle?.querySelector('i');
        this.THEMES = {
            LIGHT: 'light',
            DARK: 'dark'
        };
        
        // Sofort das Theme setzen, ohne auf DOMContentLoaded zu warten
        this.init();
    }

    init() {
        // Immer das Theme setzen, auch wenn kein Toggle-Button existiert
        this.setTheme(this.getPreferredTheme());
        
        if (this.themeToggle) {
            // Event Listener
            this.themeToggle.addEventListener('click', () => this.toggleTheme());
        }
        
        // System Theme Change Listener
        this.setupSystemThemeListener();
    }

    // Theme aus Cookie oder System-Präferenz
    getPreferredTheme() {
        // Erst nach dem Cookie suchen
        const cookies = document.cookie.split(';');
        const themeCookie = cookies.find(cookie => cookie.trim().startsWith('theme='));
        
        if (themeCookie) {
            const theme = themeCookie.split('=')[1].trim();
            // Nur gültige Themes zurückgeben
            if (theme === this.THEMES.LIGHT || theme === this.THEMES.DARK) {
                return theme;
            }
        }
        
        // Sonst System-Präferenz nutzen
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
        // Sicherstellen, dass wir ein gültiges Theme haben
        if (theme !== this.THEMES.LIGHT && theme !== this.THEMES.DARK) {
            theme = this.THEMES.LIGHT; // Fallback zu Light-Theme
        }

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
        if (logo && logo instanceof HTMLImageElement) {
            const logoPath = theme === this.THEMES.DARK 
                ? logo.getAttribute('data-dark-src')
                : logo.getAttribute('data-light-src');
            if (logoPath) {
                logo.src = logoPath;
            }
        }
    }

    // Theme Persistenz
    saveTheme(theme) {
        // Sicheres Cookie-Setzen mit allen notwendigen Optionen
        const date = new Date();
        date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000)); // 1 Jahr
        const expires = "expires=" + date.toUTCString();
        document.cookie = `theme=${theme};${expires};path=/;SameSite=Strict`;
    }

    // System Theme Change Listener
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            // Nur ändern wenn kein Theme explizit gesetzt wurde
            if (!document.cookie.includes('theme=')) {
                this.setTheme(e.matches ? this.THEMES.DARK : this.THEMES.LIGHT);
            }
        });
    }
}

// Theme Manager sofort initialisieren
const themeManager = new ThemeManager();
