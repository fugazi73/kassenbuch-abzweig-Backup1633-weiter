document.addEventListener('DOMContentLoaded', function() {
    // Checkbox Funktionalität
    const selectAllCheckbox = document.getElementById('selectAll');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');
    const massDeleteBtn = document.getElementById('massDeleteBtn');

    // "Alle auswählen" Checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('click', function() {
            const isChecked = this.checked;
            entryCheckboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
            updateMassDeleteButton();
        });
    }

    // Mausauswahl-Funktionalität
    let isSelecting = false;
    let selectionStart = null;
    let lastCheckedState = false;

    // Verhindere Standard-Text-Auswahl während der Checkbox-Auswahl
    document.addEventListener('selectstart', function(e) {
        if (isSelecting) {
            e.preventDefault();
        }
    });

    // Mousedown Event für Checkboxen
    entryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('mousedown', function(e) {
            isSelecting = true;
            selectionStart = this;
            lastCheckedState = !this.checked; // Invertiere aktuellen Status
            this.checked = lastCheckedState;
            updateMassDeleteButton();
            e.preventDefault(); // Verhindere Text-Auswahl
        });
    });

    // Mouseover Event für Checkboxen
    entryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('mouseover', function() {
            if (isSelecting) {
                this.checked = lastCheckedState;
                updateMassDeleteButton();
            }
        });
    });

    // Mouseup Event - Ende der Auswahl
    document.addEventListener('mouseup', function() {
        isSelecting = false;
        selectionStart = null;
    });

    // Einzelne Checkboxen
    entryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateMassDeleteButton);
    });

    // Massenlöschung
    if (massDeleteBtn) {
        massDeleteBtn.addEventListener('click', async function() {
            const selectedBoxes = document.querySelectorAll('.entry-checkbox:checked');
            if (selectedBoxes.length === 0) return;

            if (!confirm(`Möchten Sie wirklich ${selectedBoxes.length} Einträge löschen?`)) {
                return;
            }

            const selectedIds = [];
            selectedBoxes.forEach(function(box) {
                selectedIds.push(box.getAttribute('data-entry-id'));
            });

            try {
                const response = await fetch('mass_delete_entries.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: selectedIds })
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error || 'Fehler beim Löschen der Einträge');
                }
            } catch (error) {
                console.error('Fehler:', error);
                alert(error.message);
            }
        });
    }
});

// Update Lösch-Button
function updateMassDeleteButton() {
    const massDeleteBtn = document.getElementById('massDeleteBtn');
    const checkedBoxes = document.querySelectorAll('.entry-checkbox:checked');
    
    if (massDeleteBtn) {
        if (checkedBoxes.length > 0) {
            massDeleteBtn.style.display = 'inline-block';
            const text = checkedBoxes.length === 1 ? '1 Eintrag löschen' : `${checkedBoxes.length} Einträge löschen`;
            massDeleteBtn.innerHTML = `<i class="bi bi-trash"></i> ${text}`;
        } else {
            massDeleteBtn.style.display = 'none';
        }
    }
}

// Speicherfunktion
function saveEntry() {
    const datum = document.getElementById('datum').value;
    const bemerkung = document.getElementById('bemerkung').value;
    const einnahme = document.getElementById('einnahme').value || '0';
    const ausgabe = document.getElementById('ausgabe').value || '0';

    if (!datum || !bemerkung) {
        alert('Bitte füllen Sie alle Pflichtfelder aus.');
        return;
    }

    fetch('save_entry.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            datum: datum,
            bemerkung: bemerkung,
            einnahme: parseFloat(einnahme),
            ausgabe: parseFloat(ausgabe)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler beim Speichern: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern des Eintrags');
    });
}

// Bearbeiten Funktion
async function editEntry(id) {
    if (!id) {
        console.error('Keine ID zum Bearbeiten angegeben');
        return;
    }

    try {
        // Lade die Daten des Eintrags via GET
        const response = await fetch(`get_entry.php?id=${id}`);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server-Antwort:', errorText);
            throw new Error('Fehler beim Laden der Daten');
        }

        const data = await response.json();
        console.log('Geladene Daten:', data); // Debug-Ausgabe

        if (!data.success || !data.entry) {
            throw new Error(data.message || 'Eintrag nicht gefunden');
        }

        const entry = data.entry;

        // Modal und Formular finden
        const editModal = document.getElementById('editModal');
        if (!editModal) {
            throw new Error('Modal nicht gefunden');
        }

        const form = editModal.querySelector('form');
        if (!form) {
            throw new Error('Formular nicht gefunden');
        }

        // Formularfelder füllen
        form.querySelector('input[name="id"]').value = entry.id;
        form.querySelector('input[name="datum"]').value = entry.datum.split(' ')[0];
        form.querySelector('input[name="beleg_nr"]').value = entry.beleg_nr || '';
        form.querySelector('input[name="bemerkung"]').value = entry.bemerkung || '';
        form.querySelector('input[name="einnahme"]').value = entry.einnahme > 0 ? entry.einnahme : '';
        form.querySelector('input[name="ausgabe"]').value = entry.ausgabe > 0 ? entry.ausgabe : '';

        // Modal öffnen
        const modal = new bootstrap.Modal(editModal);
        modal.show();

        // Submit-Handler für das Formular
        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            
            try {
                const response = await fetch('update_entry.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Update Ergebnis:', result); // Debug-Ausgabe

                if (!response.ok) {
                    throw new Error(result.message || 'Fehler beim Speichern');
                }

                if (result.success) {
                    modal.hide();
                    location.reload();
                } else {
                    throw new Error(result.message || 'Fehler beim Speichern');
                }
            } catch (error) {
                console.error('Fehler beim Speichern:', error);
                alert('Fehler beim Speichern: ' + error.message);
            }
        };

    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler: ' + error.message);
    }
}

// Löschen Funktion
async function deleteEntry(id) {
    if (!id) {
        console.error('Keine ID zum Löschen angegeben');
        return;
    }

    if (!confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
        return;
    }

    try {
        // Erstelle FormData für den POST-Request
        const formData = new FormData();
        formData.append('id', id);

        console.log('Sende Lösch-Request für ID:', id); // Debug-Ausgabe

        const response = await fetch('delete_entry.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server-Antwort:', errorText);
            throw new Error('Fehler beim Löschen');
        }

        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Fehler beim Löschen:', error);
        alert('Fehler beim Löschen: ' + error.message);
    }
}

// Formular zurücksetzen
function resetForm() {
    const editModal = document.getElementById('editModal');
    if (editModal) {
        const form = editModal.querySelector('form');
        if (form) {
            form.reset();
            const modal = bootstrap.Modal.getInstance(editModal);
            if (modal) {
                modal.hide();
            }
        }
    }
} 