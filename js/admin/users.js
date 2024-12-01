// Benutzerverwaltung JavaScript

// Beim Laden der Seite
document.addEventListener('DOMContentLoaded', function() {
    // Benutzer laden
    loadUsers();
    
    // Event Listener für Formulare
    setupFormValidation();
});

// Benutzer laden
async function loadUsers() {
    try {
        const response = await fetch('api/users/list.php');
        const data = await response.json();
        
        if (data.success) {
            renderUsers(data.users);
        } else {
            showAlert('error', 'Fehler beim Laden der Benutzer: ' + data.message);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Benutzer:', error);
        showAlert('error', 'Fehler beim Laden der Benutzer');
    }
}

// Benutzer in Tabelle rendern
function renderUsers(users) {
    const tbody = document.getElementById('userTableBody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                        ${user.username.charAt(0).toUpperCase()}
                    </div>
                    <div class="ms-3">
                        <div class="fw-bold">${escapeHtml(user.username)}</div>
                        <small class="text-muted">${escapeHtml(user.name || '')}</small>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <span class="badge bg-${getRoleBadgeClass(user.role)}">
                    ${getRoleDisplayName(user.role)}
                </span>
            </td>
            <td>
                <span class="badge bg-${user.active ? 'success' : 'danger'}">
                    ${user.active ? 'Aktiv' : 'Inaktiv'}
                </span>
            </td>
            <td>
                <small class="text-muted">
                    ${formatDate(user.last_login)}
                </small>
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${user.id}, '${escapeHtml(user.username)}')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Benutzer speichern
async function saveUser() {
    const form = document.getElementById('addUserForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/users/create.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Benutzer wurde erfolgreich angelegt');
            $('#addUserModal').modal('hide');
            form.reset();
            loadUsers();
        } else {
            showAlert('error', 'Fehler beim Anlegen des Benutzers: ' + data.message);
        }
    } catch (error) {
        console.error('Fehler beim Speichern:', error);
        showAlert('error', 'Fehler beim Speichern des Benutzers');
    }
}

// Benutzer bearbeiten Modal öffnen
async function editUser(userId) {
    try {
        const response = await fetch(`api/users/get.php?id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const form = document.getElementById('editUserForm');
            form.elements['user_id'].value = data.user.id;
            form.elements['username'].value = data.user.username;
            form.elements['email'].value = data.user.email;
            form.elements['role'].value = data.user.role;
            form.elements['active'].checked = data.user.active;
            
            $('#editUserModal').modal('show');
        } else {
            showAlert('error', 'Fehler beim Laden des Benutzers: ' + data.message);
        }
    } catch (error) {
        console.error('Fehler beim Laden des Benutzers:', error);
        showAlert('error', 'Fehler beim Laden des Benutzers');
    }
}

// Benutzer aktualisieren
async function updateUser() {
    const form = document.getElementById('editUserForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/users/update.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Benutzer wurde erfolgreich aktualisiert');
            $('#editUserModal').modal('hide');
            loadUsers();
        } else {
            showAlert('error', 'Fehler beim Aktualisieren des Benutzers: ' + data.message);
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
        showAlert('error', 'Fehler beim Aktualisieren des Benutzers');
    }
}

// Löschen bestätigen
function confirmDelete(userId, username) {
    document.getElementById('deleteUserName').textContent = username;
    const deleteButton = document.querySelector('#deleteUserModal .btn-danger');
    deleteButton.onclick = () => deleteUser(userId);
    $('#deleteUserModal').modal('show');
}

// Benutzer löschen
async function deleteUser(userId) {
    try {
        const response = await fetch('api/users/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: userId })
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Benutzer wurde erfolgreich gelöscht');
            $('#deleteUserModal').modal('hide');
            loadUsers();
        } else {
            showAlert('error', 'Fehler beim Löschen des Benutzers: ' + data.message);
        }
    } catch (error) {
        console.error('Fehler beim Löschen:', error);
        showAlert('error', 'Fehler beim Löschen des Benutzers');
    }
}

// Passwort Sichtbarkeit umschalten
function togglePassword(button) {
    const input = button.previousElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Formularvalidierung einrichten
function setupFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Hilfsfunktionen
function getRoleBadgeClass(role) {
    switch (role) {
        case 'admin': return 'danger';
        case 'chef': return 'warning';
        default: return 'info';
    }
}

function getRoleDisplayName(role) {
    switch (role) {
        case 'admin': return 'Administrator';
        case 'chef': return 'Chef';
        default: return 'Benutzer';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Nie';
    const date = new Date(dateString);
    return date.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Alert-System
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
} 