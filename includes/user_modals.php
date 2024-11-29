<!-- Modal für neuen Benutzer -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neuer Benutzer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newUserForm" onsubmit="return saveNewUser(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Benutzername</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passwort</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rolle</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="user">Benutzer</option>
                            <option value="admin">Administrator</option>
                            <option value="chef">Chef</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal für Benutzer bearbeiten -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Benutzer bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" onsubmit="return submitEditUser(event)">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Benutzername</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Neues Passwort (optional)</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-muted">Leer lassen, um Passwort nicht zu ändern</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rolle</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="user">Benutzer</option>
                            <option value="admin">Administrator</option>
                            <option value="chef">Chef</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(id) {
    fetch('get_user.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_user_id').value = data.user.id;
                document.getElementById('edit_username').value = data.user.username;
                document.getElementById('edit_role').value = data.user.role;
                
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            } else {
                alert(data.message || 'Fehler beim Laden des Benutzers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ein Fehler ist aufgetreten');
        });
}

function validateForm(einnahme, ausgabe) {
    // Konvertiere Strings zu Zahlen und handle Komma/Punkt
    einnahme = parseFloat(einnahme.replace(',', '.')) || 0;
    ausgabe = parseFloat(ausgabe.replace(',', '.')) || 0;
    
    // Prüfe ob beide Felder leer oder 0 sind
    if (einnahme === 0 && ausgabe === 0) {
        return 'Bitte geben Sie entweder eine Einnahme oder eine Ausgabe ein.';
    }
    
    // Prüfe ob beide Felder gefüllt sind
    if (einnahme > 0 && ausgabe > 0) {
        return 'Bitte geben Sie entweder nur eine Einnahme oder nur eine Ausgabe ein.';
    }
    
    // Prüfe auf negative Zahlen
    if (einnahme < 0 || ausgabe < 0) {
        return 'Bitte geben Sie nur positive Zahlen ein.';
    }
    
    return null; // Keine Fehler
}

function saveNewUser(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    // Validierung
    const einnahme = formData.get('einnahme');
    const ausgabe = formData.get('ausgabe');
    const error = validateForm(einnahme, ausgabe);
    
    if (error) {
        alert(error);
        return false;
    }

    fetch('save_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Netzwerk-Antwort war nicht ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('newUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Speichern des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten: ' + error.message);
    });

    return false;
}

function submitEditUser(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Aktualisieren des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });

    return false;
}

function deleteUser(id) {
    if (confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        fetch('delete_user.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Fehler beim Löschen des Benutzers');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
    }
}
</script> 