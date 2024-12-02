document.addEventListener('DOMContentLoaded', function() {
    // Checkbox Funktionalität
    const selectAllCheckbox = document.getElementById('selectAll');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');
    const massDeleteBtn = document.getElementById('massDeleteBtn');
    const deleteCountSpan = massDeleteBtn ? massDeleteBtn.querySelector('.delete-count') : null;

    // "Alle auswählen" Checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('click', function() {
            const isChecked = this.checked;
            entryCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateMassDeleteButton();
        });
    }

    // Einzelne Checkboxen
    entryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateMassDeleteButton();
            // Aktualisiere "Alle auswählen" Checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(entryCheckboxes).every(cb => cb.checked);
            }
        });
    });

    // Event-Handler für die Dropdown-Optionen
    document.getElementById('selectCurrentPage')?.addEventListener('click', function(e) {
        e.preventDefault();
        entryCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        updateMassDeleteButton();
    });

    document.getElementById('selectAllPages')?.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            // Hole alle verfügbaren IDs vom Server
            const response = await fetch('get_all_entry_ids.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filters: getActiveFilters()
                })
            });

            if (!response.ok) {
                throw new Error('Fehler beim Laden der Einträge');
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Fehler beim Laden der Einträge');
            }

            // Markiere alle sichtbaren Checkboxen
            entryCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = true;
            }

            // Speichere die komplette ID-Liste in einem versteckten Input
            let hiddenInput = document.getElementById('allSelectedIds');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'allSelectedIds';
                document.querySelector('.table-responsive')?.appendChild(hiddenInput);
            }
            hiddenInput.value = JSON.stringify(data.ids);

            // Aktualisiere den Löschbutton mit der Gesamtanzahl
            updateMassDeleteButton(data.ids.length);
            
            showOverlay(`${data.ids.length} Einträge wurden markiert`, 'success', 2000);
        } catch (error) {
            console.error('Fehler:', error);
            showOverlay(error.message, 'error');
        }
    });

    document.getElementById('deselectAll')?.addEventListener('click', function(e) {
        e.preventDefault();
        entryCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        // Entferne versteckte IDs
        const hiddenInput = document.getElementById('allSelectedIds');
        if (hiddenInput) {
            hiddenInput.remove();
        }
        updateMassDeleteButton();
    });

    // Massenlöschung
    if (massDeleteBtn) {
        massDeleteBtn.addEventListener('click', async function() {
            const selectedBoxes = document.querySelectorAll('.entry-checkbox:checked');
            const allSelectedIds = document.getElementById('allSelectedIds');
            let idsToDelete = [];

            // Sammle alle zu löschenden IDs
            if (allSelectedIds && allSelectedIds.value) {
                try {
                    idsToDelete = JSON.parse(allSelectedIds.value);
                } catch (e) {
                    console.error('Fehler beim Parsen der IDs:', e);
                    idsToDelete = Array.from(selectedBoxes).map(box => parseInt(box.value));
                }
            } else {
                idsToDelete = Array.from(selectedBoxes).map(box => parseInt(box.value));
            }

            if (idsToDelete.length === 0) return;

            const overlay = document.createElement('div');
            overlay.className = 'overlay-message warning';
            overlay.innerHTML = `
                <div class="message-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="message-text">
                    Möchten Sie wirklich ${idsToDelete.length} ${idsToDelete.length === 1 ? 'Eintrag' : 'Einträge'} löschen?
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" id="cancelDelete">
                        <i class="bi bi-x-lg"></i> Abbrechen
                    </button>
                    <button class="btn btn-danger" id="confirmDelete">
                        <i class="bi bi-trash"></i> Löschen
                    </button>
                </div>
            `;

            document.body.appendChild(overlay);

            const confirmBtn = overlay.querySelector('#confirmDelete');
            const cancelBtn = overlay.querySelector('#cancelDelete');
            
            const cleanup = () => {
                overlay.remove();
            };

            confirmBtn.addEventListener('click', async () => {
                cleanup();
                try {
                    const response = await fetch('mass_delete_entries.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ ids: idsToDelete })
                    });

                    const data = await response.json();
                    if (data.success) {
                        showOverlay(`${idsToDelete.length} ${idsToDelete.length === 1 ? 'Eintrag wurde' : 'Einträge wurden'} erfolgreich gelöscht`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.error || 'Fehler beim Löschen der Einträge');
                    }
                } catch (error) {
                    console.error('Fehler:', error);
                    showOverlay(error.message, 'error');
                }
            });

            cancelBtn.addEventListener('click', cleanup);

            // Schließen mit Escape-Taste
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    cleanup();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);

            // Schließen mit Klick außerhalb
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    cleanup();
                }
            });
        });
    }
});

// Update Lösch-Button
function updateMassDeleteButton(totalCount = null) {
    const massDeleteBtn = document.getElementById('massDeleteBtn');
    const deleteCountSpan = massDeleteBtn?.querySelector('.delete-count');
    
    if (!massDeleteBtn) return;

    // Wenn eine Gesamtanzahl übergeben wurde, nutze diese
    if (totalCount !== null) {
        massDeleteBtn.style.display = 'inline-flex';
        if (deleteCountSpan) {
            deleteCountSpan.textContent = `${totalCount} ${totalCount === 1 ? 'Eintrag' : 'Einträge'} löschen`;
        }
        return;
    }

    // Sonst zähle die markierten Checkboxen
    const checkedBoxes = document.querySelectorAll('.entry-checkbox:checked');
    const allSelectedIds = document.getElementById('allSelectedIds');
    let count = checkedBoxes.length;

    // Wenn es versteckte IDs gibt, nutze deren Anzahl
    if (allSelectedIds && allSelectedIds.value) {
        try {
            const ids = JSON.parse(allSelectedIds.value);
            count = ids.length;
        } catch (e) {
            console.error('Fehler beim Parsen der IDs:', e);
        }
    }

    if (count > 0) {
        massDeleteBtn.style.display = 'inline-flex';
        if (deleteCountSpan) {
            deleteCountSpan.textContent = `${count} ${count === 1 ? 'Eintrag' : 'Einträge'} löschen`;
        }
    } else {
        massDeleteBtn.style.display = 'none';
    }
}

// Hilfsfunktion zum Sammeln der aktiven Filter
function getActiveFilters() {
    const filters = {};
    const urlParams = new URLSearchParams(window.location.search);
    
    ['monat', 'bemerkung', 'von_datum', 'bis_datum', 'typ'].forEach(param => {
        const value = urlParams.get(param);
        if (value) {
            filters[param] = value;
        }
    });
    
    return filters;
}