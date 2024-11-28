document.addEventListener('DOMContentLoaded', function() {
    // Custom Dropdown Funktionalität
    const dropdowns = document.querySelectorAll('.custom-dropdown');
    
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            // Schließe alle anderen Dropdowns
            dropdowns.forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('active');
                }
            });
            // Toggle aktuelles Dropdown
            dropdown.classList.toggle('active');
        });
    });
    
    // Schließen des Dropdowns beim Klick außerhalb
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-dropdown')) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Aktives Dropdown-Item markieren
    const currentPage = window.location.pathname.split('/').pop();
    const dropdownItems = document.querySelectorAll('.dropdown-content a');
    
    dropdownItems.forEach(item => {
        const itemPath = item.getAttribute('href');
        if (itemPath === currentPage) {
            item.classList.add('active');
            // Eltern-Dropdown auch als aktiv markieren
            const parentDropdown = item.closest('.custom-dropdown');
            if (parentDropdown) {
                parentDropdown.querySelector('.dropdown-trigger').classList.add('active');
            }
        }
    });
}); 