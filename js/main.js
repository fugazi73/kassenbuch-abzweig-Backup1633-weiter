document.addEventListener('DOMContentLoaded', function() {
    // Initialisiere alle Dropdowns
    var dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    dropdownElementList.forEach(function(dropdownToggle) {
        new bootstrap.Dropdown(dropdownToggle);
    });
    
    // Weitere bestehende Funktionalitäten bleiben erhalten
    if (typeof initKassenbuch === 'function') {
        initKassenbuch();
    }
    
    if (typeof initSettings === 'function') {
        initSettings();
    }
    
    if (typeof initAdmin === 'function') {
        initAdmin();
    }
}); 