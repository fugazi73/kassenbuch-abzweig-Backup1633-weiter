document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    
    function updateThemeIcon(theme) {
        icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
    }

    // Initial Icon-Status setzen
    updateThemeIcon(document.documentElement.getAttribute('data-bs-theme'));

    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Theme in Cookie speichern
        document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
    });
});
