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
