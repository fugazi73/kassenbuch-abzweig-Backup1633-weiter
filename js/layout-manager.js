document.addEventListener('DOMContentLoaded', function() {
    console.log('Layout Manager geladen');
    
    const layoutToggle = document.querySelector('.layout-toggle');
    const body = document.body;
    const toggleIcon = layoutToggle?.querySelector('i');
    
    if (!layoutToggle || !toggleIcon) {
        console.error('Layout Toggle Elemente nicht gefunden');
        return;
    }
    
    // Gespeichertes Layout laden
    const savedLayout = localStorage.getItem('adminLayout');
    console.log('Gespeichertes Layout:', savedLayout);
    
    if (savedLayout === 'compact') {
        body.classList.add('compact-layout');
        toggleIcon.className = 'bi bi-arrows-angle-expand';
    } else {
        toggleIcon.className = 'bi bi-arrows-angle-contract';
    }
    
    // Layout Toggle Event Listener
    layoutToggle.addEventListener('click', function() {
        console.log('Layout Toggle geklickt');
        body.classList.toggle('compact-layout');
        
        const isCompact = body.classList.contains('compact-layout');
        console.log('Kompaktes Layout:', isCompact);
        
        // Icon aktualisieren
        toggleIcon.className = isCompact ? 'bi bi-arrows-angle-expand' : 'bi bi-arrows-angle-contract';
        
        // Layout speichern
        localStorage.setItem('adminLayout', isCompact ? 'compact' : 'full');
    });
}); 