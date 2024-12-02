document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
    
    // Theme-Icon aktualisieren
    updateThemeIcon(savedTheme);
    
    themeToggle.addEventListener('click', function() {
        // Verhindere mehrfaches Klicken während der Transition
        if (themeToggle.disabled) return;
        themeToggle.disabled = true;
        
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Theme sofort ändern
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Cookie setzen für PHP
        document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
        
        // Icon aktualisieren
        updateThemeIcon(newTheme);
        
        // Button nach Transition wieder aktivieren
        setTimeout(() => {
            themeToggle.disabled = false;
        }, 150);
    });
});

function updateThemeIcon(theme) {
    const themeIcon = document.querySelector('#themeToggle i');
    if (themeIcon) {
        themeIcon.className = theme === 'dark' ? 
            'bi bi-moon-stars' : 
            'bi bi-sun';
    }
}

// Overlay Funktion
function showOverlay(message, type = 'success', duration = 3000) {
    // Entferne existierende Overlays
    const existingOverlay = document.querySelector('.overlay-message');
    if (existingOverlay) {
        existingOverlay.remove();
    }

    // Icon basierend auf Typ
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    // Erstelle Overlay Element
    const overlay = document.createElement('div');
    overlay.className = `overlay-message ${type}`;
    overlay.innerHTML = `
        <div class="message-icon">
            <i class="bi ${icons[type]}"></i>
        </div>
        <div class="message-text">
            ${message}
        </div>
        <button class="btn btn-primary" onclick="this.closest('.overlay-message').remove()">
            OK
        </button>
    `;

    // Füge Overlay zum Body hinzu
    document.body.appendChild(overlay);

    // Optional: Automatisches Schließen nach Verzögerung
    if (duration > 0) {
        setTimeout(() => {
            if (overlay && overlay.parentNode) {
                overlay.remove();
            }
        }, duration);
    }

    // Schließen mit Escape-Taste
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    // Schließen mit Klick außerhalb
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.remove();
        }
    });

    return overlay;
}

// Beispiel für die Verwendung:
// showOverlay('Erfolgreich gespeichert!', 'success');
// showOverlay('Ein Fehler ist aufgetreten!', 'error');
// showOverlay('Bitte beachten Sie...', 'warning');
// showOverlay('Zur Information...', 'info');
