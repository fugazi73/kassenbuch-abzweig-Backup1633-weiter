document.addEventListener('DOMContentLoaded', function() {
    initializeFormFields();
    initializeTablePosition();
    initializeEventListeners();
});

function initializeFormFields() {
    const einnahmeField = document.getElementById('einnahme');
    const ausgabeField = document.getElementById('ausgabe');
    
    if (!einnahmeField || !ausgabeField) return;

    function toggleFields(event) {
        const sourceField = event.target;
        const targetField = sourceField === einnahmeField ? ausgabeField : einnahmeField;
        
        if (sourceField.value && sourceField.value !== '0' && sourceField.value !== '0,00') {
            targetField.disabled = true;
            targetField.value = '';
        } else {
            targetField.disabled = false;
        }
    }
    
    einnahmeField.addEventListener('input', toggleFields);
    ausgabeField.addEventListener('input', toggleFields);
    
    // Initial state
    if (einnahmeField.value && einnahmeField.value !== '0' && einnahmeField.value !== '0,00') {
        ausgabeField.disabled = true;
    } else if (ausgabeField.value && ausgabeField.value !== '0' && ausgabeField.value !== '0,00') {
        einnahmeField.disabled = true;
    }
}

function initializeTablePosition() {
    restoreTablePosition();
    
    // Save position before form submits
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', saveTablePosition);
    });
    
    // Save position before modal opens
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', saveTablePosition);
    });
}

function saveTablePosition() {
    const scrollPosition = window.scrollY;
    const currentPage = new URLSearchParams(window.location.search).get('page') || '1';
    sessionStorage.setItem('tableScrollPosition', scrollPosition);
    sessionStorage.setItem('tablePage', currentPage);
}

function restoreTablePosition() {
    const savedScroll = sessionStorage.getItem('tableScrollPosition');
    const savedPage = sessionStorage.getItem('tablePage');
    
    if (savedPage) {
        const currentPage = new URLSearchParams(window.location.search).get('page') || '1';
        if (savedPage === currentPage && savedScroll) {
            window.scrollTo(0, parseInt(savedScroll));
        }
    }
}

function initializeEventListeners() {
    // Form submission handler
    const quickEntryForm = document.getElementById('quickEntryForm');
    if (quickEntryForm) {
        quickEntryForm.addEventListener('submit', function(event) {
            event.preventDefault();
            validateAndSubmitForm(this);
        });
    }

    // Edit-Button Event Listener
    document.querySelectorAll('[data-action="edit"]').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            editEntry(id);
        });
    });

    // Delete-Button Event Listener
    document.querySelectorAll('.delete-entry').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
                deleteEntry(id);
            }
        });
    });
}

function validateAndSubmitForm(form) {
    const einnahme = form.einnahme.value.trim();
    const ausgabe = form.ausgabe.value.trim();
    
    // Validierung
    if ((einnahme && ausgabe) || (!einnahme && !ausgabe)) {
        alert('Bitte entweder nur Einnahme oder nur Ausgabe eingeben.');
        return false;
    }
    
    if ((einnahme && parseFloat(einnahme) <= 0) || (ausgabe && parseFloat(ausgabe) <= 0)) {
        alert('Bitte nur positive Zahlen eingeben.');
        return false;
    }
    
    // Form submission
    const formData = new FormData(form);
    fetch('includes/save_entry.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Speichern des Eintrags');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}

function toggleField(sourceField, targetFieldName) {
    const form = sourceField.form;
    const targetField = form.querySelector(`[name="${targetFieldName}"]`);

    if (sourceField.value && sourceField.value !== '0' && sourceField.value !== '0,00') {
        targetField.disabled = true;
        targetField.value = '';
    } else {
        targetField.disabled = false;
    }
}

function fillEditModal(entry) {
    const form = document.getElementById('editEntryForm');
    if (!form) return;
    
    form.querySelector('[name="id"]').value = entry.id;
    form.querySelector('[name="datum"]').value = entry.datum;
    form.querySelector('[name="beleg_nr"]').value = entry.beleg_nr;
    form.querySelector('[name="bemerkung"]').value = entry.bemerkung;
    
    const einnahmeField = form.querySelector('[name="einnahme"]');
    const ausgabeField = form.querySelector('[name="ausgabe"]');
    
    einnahmeField.value = entry.einnahme || '';
    ausgabeField.value = entry.ausgabe || '';
    
    // Event Listener für Einnahme/Ausgabe Toggle
    einnahmeField.addEventListener('input', function() {
        toggleField(this, 'ausgabe');
    });
    ausgabeField.addEventListener('input', function() {
        toggleField(this, 'einnahme');
    });
    
    // Initial state
    if (entry.einnahme > 0) {
        ausgabeField.disabled = true;
    } else if (entry.ausgabe > 0) {
        einnahmeField.disabled = true;
    }
}

// Edit-Funktion
function editEntry(id) {
    fetch(`includes/get_entry.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillEditModal(data.entry);
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            } else {
                alert('Fehler beim Laden des Eintrags');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ein Fehler ist aufgetreten');
        });
}

// Delete-Funktion
function deleteEntry(id) {
    console.log('Lösche Eintrag:', id); // Debug
    const formData = new FormData();
    formData.append('id', id);

    fetch('includes/delete_entry.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response erhalten:', response); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Daten erhalten:', data); // Debug
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Löschen');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}

// Event Listener für das Edit-Formular
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editEntryForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('includes/update_entry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    modal.hide();
                    location.reload();
                } else {
                    alert(data.message || 'Fehler beim Speichern');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
        });
    }
}); 