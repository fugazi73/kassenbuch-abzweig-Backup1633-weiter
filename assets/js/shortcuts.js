document.addEventListener('keydown', function(e) {
    // Ignoriere Tastaturkürzel wenn in Eingabefeldern
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }

    // Strg + F: Filter öffnen
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        const filterButton = document.querySelector('[data-bs-target="#filterModal"]');
        if (filterButton) {
            filterButton.click();
        }
    }

    // Strg + E: Export öffnen
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        const exportButton = document.querySelector('[data-bs-target="#exportModal"]');
        if (exportButton) {
            exportButton.click();
        }
    }

    // Esc: Modals schließen
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
}); 