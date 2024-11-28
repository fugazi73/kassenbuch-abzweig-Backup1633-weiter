class FilterManager {
    constructor() {
        this.initializeSelect2();
        this.initializeEventListeners();
    }

    initializeSelect2() {
        // Warte bis jQuery verfügbar ist
        if (typeof jQuery !== 'undefined') {
            // Select2 für Beschreibungen
            $('#description_filter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Beschreibung wählen',
                allowClear: true,
                width: '100%'
            });

            // Select2 für Typ
            $('#type_filter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Typ wählen',
                allowClear: true,
                width: '100%'
            });
        } else {
            console.error('jQuery ist nicht verfügbar');
        }
    }

    initializeEventListeners() {
        // Date Filter
        $('#date_from, #date_to').on('change', () => this.applyFilters());
        
        // Description Filter
        $('#description_filter').on('change', () => this.applyFilters());
        
        // Type Filter
        $('#type_filter').on('change', () => this.applyFilters());
        
        // Reset Button
        $('#reset_filters').on('click', () => this.resetFilters());
    }

    applyFilters() {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        const description = $('#description_filter').val();
        const type = $('#type_filter').val();

        // Alle Zeilen durchgehen
        $('table tbody tr').each(function() {
            let show = true;
            const row = $(this);

            // Datumsfilter
            if (dateFrom || dateTo) {
                const date = new Date(row.find('td:first').text().split('.').reverse().join('-'));
                if (dateFrom && new Date(dateFrom) > date) show = false;
                if (dateTo && new Date(dateTo) < date) show = false;
            }

            // Beschreibungsfilter
            if (description && row.find('td:eq(1)').text() !== description) {
                show = false;
            }

            // Typfilter
            if (type && row.find('td:eq(3)').text() !== type) {
                show = false;
            }

            row.toggle(show);
        });

        this.updateSummary();
    }

    resetFilters() {
        // Reset all filters
        $('#date_from, #date_to').val('');
        $('#description_filter, #type_filter').val(null).trigger('change');
        $('table tbody tr').show();
        this.updateSummary();
    }

    updateSummary() {
        let einnahmen = 0;
        let ausgaben = 0;

        $('table tbody tr:visible').each(function() {
            const amount = parseFloat($(this).find('td:eq(2)').text().replace('€', '').replace('.', '').replace(',', '.'));
            const type = $(this).find('td:eq(3)').text();

            if (type === 'Einnahme') einnahmen += amount;
            else if (type === 'Ausgabe') ausgaben += amount;
        });

        $('#summe_einnahmen').text(einnahmen.toFixed(2).replace('.', ',') + ' €');
        $('#summe_ausgaben').text(ausgaben.toFixed(2).replace('.', ',') + ' €');
        $('#summe_gesamt').text((einnahmen - ausgaben).toFixed(2).replace('.', ',') + ' €');
    }
}

// Initialisierung
$(document).ready(function() {
    window.filterManager = new FilterManager();
}); 