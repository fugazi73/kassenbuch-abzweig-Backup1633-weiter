<?php
require_once 'includes/init.php';
require_once 'functions.php';

// Prüfe Berechtigung
if (!is_admin()) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

$page_title = "Benutzerverwaltung - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-people-fill text-primary"></i> Benutzerverwaltung
            </h1>

            <!-- Benutzer hinzufügen -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-person-plus text-success"></i> Neuen Benutzer anlegen
                </h3>
                <form id="addUserForm" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="username" class="form-label small">Benutzername</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-4">
                            <label for="password" class="form-label small">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="role" class="form-label small">Rolle</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Bitte wählen...</option>
                                <option value="user">Benutzer</option>
                                <option value="chef">Chef</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Benutzer anlegen
                        </button>
                    </div>
                </form>
            </div>

            <!-- Benutzerliste -->
            <div class="admin-section">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-list-ul text-info"></i> Benutzerliste
                </h3>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Benutzername</th>
                                <th>Rolle</th>
                                <th>Letzter Login</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT *, COALESCE(active, 1) as active FROM benutzer ORDER BY username");
                            while ($user = $result->fetch_assoc()):
                                // Rolle in lesbaren Text umwandeln
                                $role_text = '';
                                switch($user['role']) {
                                    case 'admin':
                                        $role_text = 'Administrator';
                                        $role_badge = 'danger';
                                        break;
                                    case 'chef':
                                        $role_text = 'Chef';
                                        $role_badge = 'success';
                                        break;
                                    default:
                                        $role_text = 'Benutzer';
                                        $role_badge = 'primary';
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><span class="badge bg-<?= $role_badge ?>"><?= $role_text ?></span></td>
                                <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie' ?></td>
                                <td>
                                    <?php if (isset($user['active']) && $user['active']): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editUser(<?= $user['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteUser(<?= $user['id'] ?>)">
                                            <i class="bi bi-trash"></i>
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
    </div>
</div>

<!-- Benutzer bearbeiten Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Benutzer bearbeiten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Neues Passwort (optional)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rolle</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="user">Benutzer</option>
                            <option value="chef">Chef</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                            <label class="form-check-label" for="edit_active">Aktiv</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Speichern</button>
            </div>
        </div>
    </div>
</div>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
}

.admin-section h3 {
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
}

.table th {
    font-size: 0.85rem;
    font-weight: 500;
}

.table td {
    font-size: 0.9rem;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

.form-label.small {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.modal-title {
    font-size: 1rem;
}

[data-bs-theme="dark"] .table-light {
    background-color: rgba(255, 255, 255, 0.05);
}

[data-bs-theme="dark"] .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>

<script>
// JavaScript-Code für die Benutzerverwaltung
document.addEventListener('DOMContentLoaded', function() {
    // Formular-Validierung aktivieren
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Benutzer hinzufügen
    document.getElementById('addUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!this.checkValidity()) return;

        const formData = {
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            role: document.getElementById('role').value
        };

        try {
            const response = await fetch('add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Fehler beim Anlegen des Benutzers');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Anlegen des Benutzers');
        }
    });
});

// Benutzer bearbeiten
async function editUser(id) {
    try {
        const response = await fetch(`get_user.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('edit_user_id').value = data.user.id;
            document.getElementById('edit_username').value = data.user.username;
            document.getElementById('edit_role').value = data.user.role;
            document.getElementById('edit_active').checked = data.user.active == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Laden der Benutzerdaten');
    }
}

// Benutzer speichern
async function saveUser() {
    const formData = {
        id: document.getElementById('edit_user_id').value,
        username: document.getElementById('edit_username').value,
        password: document.getElementById('edit_password').value,
        role: document.getElementById('edit_role').value,
        active: document.getElementById('edit_active').checked ? 1 : 0
    };

    try {
        const response = await fetch('update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Fehler beim Speichern der Änderungen');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern der Änderungen');
    }
}

// Benutzer löschen
async function deleteUser(id) {
    if (!confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        return;
    }

    try {
        const response = await fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Fehler beim Löschen des Benutzers');
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Löschen des Benutzers');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
