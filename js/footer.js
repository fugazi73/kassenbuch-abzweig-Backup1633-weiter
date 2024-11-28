class FooterManager {
    constructor() {
        this.initializeFooterLinks();
        this.setupDynamicSeparators();
    }

    initializeFooterLinks() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.footer-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }

    setupDynamicSeparators() {
        const footerLinks = document.querySelector('.footer-links');
        if (!footerLinks) return;

        // Entferne alle existierenden Separatoren
        footerLinks.querySelectorAll('.footer-separator').forEach(sep => sep.remove());

        // Füge Separatoren zwischen sichtbaren Links hinzu
        const visibleLinks = Array.from(footerLinks.querySelectorAll('.footer-link'));
        visibleLinks.forEach((link, index) => {
            if (index < visibleLinks.length - 1) {
                const separator = document.createElement('span');
                separator.className = 'footer-separator';
                separator.textContent = '|';
                link.after(separator);
            }
        });
    }
}

// Footer Manager initialisieren
document.addEventListener('DOMContentLoaded', () => {
    window.footerManager = new FooterManager();
}); 