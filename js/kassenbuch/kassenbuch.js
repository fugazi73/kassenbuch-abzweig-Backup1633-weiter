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
    try {
        const response = await fetch(`get_entry.php?id=${id}`);
        if (!response.ok) throw new Error('Fehler beim Laden der Daten');
        
        const entry = await response.json();
        
        // Formularfelder füllen
        document.getElementById('datum').value = entry.datum;
        document.getElementById('bemerkung').value = entry.bemerkung;
        document.getElementById('einnahme').value = entry.einnahme > 0 ? entry.einnahme : '';
        document.getElementById('ausgabe').value = entry.ausgabe > 0 ? entry.ausgabe : '';
        
        // Button zum Speichern ändern
        const saveBtn = document.querySelector('button[onclick="saveEntry()"]');
        saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Aktualisieren';
        saveBtn.onclick = () => updateEntry(id);
        
        // Abbrechen-Button hinzufügen
        if (!document.getElementById('cancelBtn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancelBtn';
            cancelBtn.className = 'btn btn-secondary ms-2';
            cancelBtn.innerHTML = '<i class="bi bi-x-circle"></i> Abbrechen';
            cancelBtn.onclick = resetForm;
            saveBtn.parentNode.appendChild(cancelBtn);
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Laden der Daten');
    }
}

// Löschen Funktion
async function deleteEntry(id) {
    if (!confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
        return;
    }

    try {
        const response = await fetch('delete_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });

        if (!response.ok) throw new Error('Fehler beim Löschen');
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.message || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Löschen des Eintrags');
    }
}

// Update Funktion
async function updateEntry(id) {
    const datum = document.getElementById('datum').value;
    const bemerkung = document.getElementById('bemerkung').value;
    const einnahme = document.getElementById('einnahme').value || '0';
    const ausgabe = document.getElementById('ausgabe').value || '0';

    if (!datum || !bemerkung) {
        alert('Bitte füllen Sie alle Pflichtfelder aus.');
        return;
    }

    try {
        const response = await fetch('update_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                datum: datum,
                bemerkung: bemerkung,
                einnahme: parseFloat(einnahme),
                ausgabe: parseFloat(ausgabe)
            })
        });

        if (!response.ok) throw new Error('Fehler beim Aktualisieren');
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.message || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Aktualisieren des Eintrags');
    }
}

// Formular zurücksetzen
function resetForm() {
    const form = document.querySelector('.card-body');
    const saveBtn = form.querySelector('button[onclick]');
    const cancelBtn = document.getElementById('cancelBtn');

    // Formularfelder zurücksetzen
    document.getElementById('datum').value = new Date().toISOString().split('T')[0];
    document.getElementById('bemerkung').value = '';
    document.getElementById('einnahme').value = '';
    document.getElementById('ausgabe').value = '';
    document.getElementById('einnahme').disabled = false;
    document.getElementById('ausgabe').disabled = false;

    // Button zurücksetzen
    saveBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Neuer Eintrag';
    saveBtn.onclick = saveEntry;

    // Abbrechen-Button entfernen
    if (cancelBtn) cancelBtn.remove();
} 