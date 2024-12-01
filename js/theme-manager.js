// Theme und Layout Manager
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const layoutToggle = document.getElementById('layoutToggle');
    const html = document.documentElement;
    const siteLogo = document.getElementById('siteLogo');
    const mainContainer = document.querySelector('.container-box');
    
    // Theme Management
    const savedTheme = localStorage.getItem('kassenbuch_theme') || 'light';
    setTheme(savedTheme);
    
    // Layout Management
    const savedLayout = localStorage.getItem('kassenbuch_layout') || 'box';
    setLayout(savedLayout);
    
    // Event Listeners
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }
    
    if (layoutToggle) {
        layoutToggle.addEventListener('click', () => {
            const newLayout = localStorage.getItem('kassenbuch_layout') === 'full' ? 'box' : 'full';
            setLayout(newLayout);
        });
    }
    
    // Theme Functions
    function setTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem('kassenbuch_theme', theme);
        document.cookie = `kassenbuch_theme=${theme};path=/;max-age=31536000`;
        
        updateThemeIcon(theme);
        updateLogo(theme);
    }
    
    function updateThemeIcon(theme) {
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (theme === 'dark') {
                icon.className = 'bi bi-sun';
                themeToggle.title = 'Zu hellem Theme wechseln';
            } else {
                icon.className = 'bi bi-moon-stars';
                themeToggle.title = 'Zu dunklem Theme wechseln';
            }
        }
    }
    
    function updateLogo(theme) {
        if (siteLogo) {
            const lightSrc = siteLogo.getAttribute('data-light-src');
            const darkSrc = siteLogo.getAttribute('data-dark-src');
            siteLogo.src = theme === 'dark' ? darkSrc : lightSrc;
        }
    }
    
    // Layout Functions
    function setLayout(layout) {
        localStorage.setItem('kassenbuch_layout', layout);
        document.cookie = `kassenbuch_layout=${layout};path=/;max-age=31536000`;
        
        if (mainContainer) {
            mainContainer.className = `container-${layout}`;
        }
        
        updateLayoutIcon(layout);
    }
    
    function updateLayoutIcon(layout) {
        if (layoutToggle) {
            const icon = layoutToggle.querySelector('i');
            if (layout === 'full') {
                icon.className = 'bi bi-arrows-angle-contract';
                layoutToggle.title = 'Zu Box-Layout wechseln';
            } else {
                icon.className = 'bi bi-arrows-fullscreen';
                layoutToggle.title = 'Zu Vollbild-Layout wechseln';
            }
        }
    }
}); 