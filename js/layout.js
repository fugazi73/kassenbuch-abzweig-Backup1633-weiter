document.addEventListener('DOMContentLoaded', function() {
    initializeLayout();
});

function initializeLayout() {
    // Layout Toggle Button erstellen und einfügen
    const layoutToggle = document.createElement('button');
    layoutToggle.className = 'layout-toggle';
    layoutToggle.setAttribute('title', document.body.classList.contains('compact-layout') ? 
        'Auf volle Breite erweitern' : 'Auf kompakte Breite reduzieren');
    document.body.appendChild(layoutToggle);

    // Click Event Handler
    layoutToggle.addEventListener('click', function() {
        document.body.classList.toggle('compact-layout');
        const isCompact = document.body.classList.contains('compact-layout');
        
        // Cookie setzen
        document.cookie = `layoutMode=${isCompact ? 'compact' : 'full'}; path=/; max-age=31536000`;
        
        // Tooltip aktualisieren
        this.setAttribute('title', isCompact ? 'Auf volle Breite erweitern' : 'Auf kompakte Breite reduzieren');
        
        // Event auslösen für andere Komponenten
        window.dispatchEvent(new CustomEvent('layoutChange', { 
            detail: { isCompact } 
        }));
    });
} 