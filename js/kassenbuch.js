// Kassenbuch JavaScript Funktionen
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Toggle
    const navToggle = document.querySelector('.kb-nav-toggle');
    const nav = document.querySelector('.kb-nav');
    
    if (navToggle && nav) {
        navToggle.addEventListener('click', function() {
            nav.classList.toggle('show');
        });
    }
    
    // Tabellen-Sortierung
    const sortableTables = document.querySelectorAll('.kb-table-sortable');
    
    sortableTables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const sortKey = this.dataset.sort;
                const isAsc = !this.classList.contains('asc');
                
                // Entferne Sortier-Klassen von allen Headers
                headers.forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                
                // Setze neue Sortier-Klasse
                this.classList.add(isAsc ? 'asc' : 'desc');
                
                // Sortiere die Tabelle
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                rows.sort((a, b) => {
                    const aVal = a.querySelector(`[data-${sortKey}]`).dataset[sortKey];
                    const bVal = b.querySelector(`[data-${sortKey}]`).dataset[sortKey];
                    
                    if (sortKey === 'date') {
                        return isAsc ? 
                            new Date(aVal) - new Date(bVal) : 
                            new Date(bVal) - new Date(aVal);
                    }
                    
                    if (sortKey === 'amount') {
                        return isAsc ?
                            parseFloat(aVal) - parseFloat(bVal) :
                            parseFloat(bVal) - parseFloat(aVal);
                    }
                    
                    return isAsc ?
                        aVal.localeCompare(bVal) :
                        bVal.localeCompare(aVal);
                });
                
                // Aktualisiere Tabelle
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    
    // Formular-Validierung
    const forms = document.querySelectorAll('.kb-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    });
    
    // Betrag-Formatierung
    const amountInputs = document.querySelectorAll('.kb-amount-input');
    
    amountInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                const amount = parseFloat(this.value.replace(',', '.'));
                if (!isNaN(amount)) {
                    this.value = amount.toFixed(2).replace('.', ',');
                }
            }
        });
    });
    
    // Datum-Picker Initialisierung
    const dateInputs = document.querySelectorAll('.kb-date-input');
    
    dateInputs.forEach(input => {
        // Verwende natives Datums-Input oder füge hier einen Datepicker deiner Wahl ein
        input.type = 'date';
    });
    
    // Theme Toggle (Hell/Dunkel)
    const themeToggle = document.querySelector('.kb-theme-toggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('kb-theme', newTheme);
        });
    }
    
    // Initialisiere gespeichertes Theme
    const savedTheme = localStorage.getItem('kb-theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    }
}); 