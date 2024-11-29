document.addEventListener('DOMContentLoaded', function() {
    const kassenstartForm = document.getElementById('kassenstartForm');
    if (kassenstartForm) {
        kassenstartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('datum', document.getElementById('startdatum').value);
            formData.append('betrag', document.getElementById('startbetrag').value);
            
            fetch('save_startbetrag.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kassenstart wurde gespeichert');
                    location.reload();
                } else {
                    alert('Fehler: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
        });
    }

    const addColumnBtn = document.getElementById('addColumnBtn');
    const dynamicColumns = document.getElementById('dynamicColumns');

    if (addColumnBtn && dynamicColumns) {
        addColumnBtn.addEventListener('click', function() {
            const template = `
                <div class="row g-3 mb-3 custom-column">
                    <div class="col-md-3">
                        <label class="form-label">Spaltenname</label>
                        <input type="text" class="form-control" name="columns[custom][][name]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Typ</label>
                        <select class="form-select" name="columns[custom][][type]" required>
                            <option value="text">Text</option>
                            <option value="date">Datum</option>
                            <option value="decimal">Dezimalzahl</option>
                            <option value="integer">Ganzzahl</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Excel-Spalte</label>
                        <select class="form-select" name="columns[custom][][excel_column]" required>
                            <option value="">Spalte auswählen</option>
                            ${generateExcelColumns()}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger d-block w-100 remove-column">
                            <i class="bi bi-trash"></i> Entfernen
                        </button>
                    </div>
                </div>
            `;
            dynamicColumns.insertAdjacentHTML('beforeend', template);
        });

        dynamicColumns.addEventListener('click', function(e) {
            if (e.target.closest('.remove-column')) {
                e.target.closest('.custom-column').remove();
            }
        });

        const columnConfigForm = document.getElementById('columnConfigForm');
        if (columnConfigForm) {
            columnConfigForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'save_column_config');

                fetch('process_column_config.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Excel-Konfiguration wurde gespeichert');
                        location.reload();
                    } else {
                        alert('Fehler: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                });
            });
        }
    }
});

function generateExcelColumns() {
    return Array.from(Array(26))
        .map((_, i) => {
            const column = String.fromCharCode(65 + i);
            return `<option value="${column}">Spalte ${column}</option>`;
        })
        .join('');
} 