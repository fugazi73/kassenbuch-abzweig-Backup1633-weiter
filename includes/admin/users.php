<?php
// Benutzer aus der Datenbank abrufen
$users = $conn->query("SELECT id, username, role FROM benutzer ORDER BY username");

function getRoleBadgeClass($role) {
    switch($role) {
        case 'admin':
            return 'text-bg-primary';
        case 'chef':
            return 'text-bg-success';
        default:
            return 'text-bg-secondary';
    }
}

function getRoleDisplayName($role) {
    switch($role) {
        case 'admin':
            return 'Administrator';
        case 'chef':
            return 'Chef';
        default:
            return 'Benutzer';
    }
}
?>

<!-- Header-Bereich -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="card-title mb-1">
                    <i class="bi bi-people"></i> Benutzerverwaltung
                </h4>
                <p class="text-muted mb-0">Verwalten Sie hier alle Benutzerkonten des Systems</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="bi bi-person-plus"></i> Neuer Benutzer
            </button>
        </div>
    </div>
</div>

<!-- Benutzerliste -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th scope="col" class="border-0">Benutzername</th>
                        <th scope="col" class="border-0">Rolle</th>
                        <th scope="col" class="border-0 text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="border-0">
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle text-muted me-2"></i>
                                <?= htmlspecialchars($user['username']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= getRoleBadgeClass($user['role']) ?>">
                                <?= getRoleDisplayName($user['role']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editUser(<?= $user['id'] ?>)" 
                                        title="Benutzer bearbeiten">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteUser(<?= $user['id'] ?>)"
                                        title="Benutzer löschen">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal für neuen Benutzer -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fs-4">
                    <i class="bi bi-person-plus"></i> Neuer Benutzer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createUserForm" onsubmit="return submitCreateUser(event)">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label">Benutzername</label>
                        <input type="text" name="username" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passwort</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rolle</label>
                        <select name="role" class="form-select">
                            <option value="user">Benutzer</option>
                            <option value="admin">Administrator</option>
                            <option value="chef">Chef</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal für Benutzer bearbeiten -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fs-4">
                    <i class="bi bi-pencil"></i> Benutzer bearbeiten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" onsubmit="return submitEditUser(event)">
                <input type="hidden" id="edit_user_id" name="id">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label">Benutzername</label>
                        <input type="text" id="edit_username" name="username" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Neues Passwort (optional)</label>
                        <input type="password" id="edit_password" name="password" class="form-control form-control-lg">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Rolle</label>
                        <select id="edit_role" name="role" class="form-select form-select-lg">
                            <option value="user">Benutzer</option>
                            <option value="admin">Administrator</option>
                            <option value="chef">Chef</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funktion für das Erstellen eines neuen Benutzers
function submitCreateUser(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
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
            throw new Error(data.message || 'Fehler beim Speichern des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten: ' + error.message);
    });

    return false;
}

// Funktion zum Bearbeiten eines Benutzers
function editUser(id) {
    fetch('get_user.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_user_id').value = data.user.id;
                document.getElementById('edit_username').value = data.user.username;
                document.getElementById('edit_role').value = data.user.role;
                document.getElementById('edit_password').value = '';
                
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            } else {
                alert('Fehler beim Laden der Benutzerdaten');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ein Fehler ist aufgetreten');
        });
}

// Funktion zum Speichern der Bearbeitung
function submitEditUser(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

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

// Funktion zum Löschen eines Benutzers
function deleteUser(id) {
    if (confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('delete_user.php', {
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
                location.reload();
            } else {
                throw new Error(data.message || 'Fehler beim Löschen des Benutzers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ein Fehler ist aufgetreten: ' + error.message);
        });
    }
}</script> 