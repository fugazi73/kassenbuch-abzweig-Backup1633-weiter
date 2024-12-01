console.log('backup.js wird geladen...');

// Backup-Verwaltung JavaScript
let currentBackupFile = null;

// Bootstrap-Hilfsfunktionen
function getBootstrap() {
    return window.bootstrap;
}

function getModal(element) {
    const bs = getBootstrap();
    return bs ? new bs.Modal(element) : null;
}

function getModalInstance(element) {
    const bs = getBootstrap();
    return bs ? bs.Modal.getInstance(element) : null;
}

function getAlert(element) {
    const bs = getBootstrap();
    return bs ? new bs.Alert(element) : null;
}

// Backup erstellen
function createBackup(type = 'full') {
    if (!confirm(`Möchten Sie wirklich ein ${getBackupTypeName(type)} erstellen?`)) {
        return;
    }
    
    const overlay = showBackupOverlay('Backup wird erstellt...');
    
    const formData = new FormData();
    formData.append('create_backup', '1');
    formData.append('backup_type', type);
    
    fetch('includes/admin/backup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            throw new Error('Netzwerkfehler');
        }
    })
    .catch(error => {
        console.error('Backup Error:', error);
        showAlert('error', 'Fehler beim Erstellen des Backups');
    })
    .then(() => {
        removeBackupOverlay(overlay);
    });
}

// Backup wiederherstellen bestätigen
function confirmRestore(filename) {
    if (!getBootstrap()) {
        console.error('Bootstrap ist nicht verfügbar');
        return;
    }
    
    currentBackupFile = filename;
    const modalElement = document.getElementById('restoreModal');
    if (!modalElement) {
        console.error('Modal-Element nicht gefunden');
        return;
    }
    
    const fileNameElement = document.getElementById('restoreFileName');
    if (fileNameElement) {
        fileNameElement.textContent = filename;
    }
    
    const modal = getModal(modalElement);
    if (modal) {
        modal.show();
    }
}

// Backup wiederherstellen
function restoreBackup() {
    if (!currentBackupFile) return;
    
    const modalElement = document.getElementById('restoreModal');
    if (!modalElement) return;
    
    const modal = getModalInstance(modalElement);
    if (modal) {
        modal.hide();
    }
    
    const overlay = showBackupOverlay('Backup wird wiederhergestellt...');
    
    const formData = new FormData();
    formData.append('restore_backup', '1');
    formData.append('backup_id', currentBackupFile);
    
    fetch('includes/admin/backup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            throw new Error('Netzwerkfehler');
        }
    })
    .catch(error => {
        console.error('Restore Error:', error);
        showAlert('error', 'Fehler beim Wiederherstellen des Backups');
    })
    .then(() => {
        removeBackupOverlay(overlay);
    });
}

// Backup löschen bestätigen
function confirmDelete(filename) {
    if (confirm(`Möchten Sie das Backup "${filename}" wirklich löschen?`)) {
        deleteBackup(filename);
    }
}

// Backup löschen
function deleteBackup(filename) {
    const overlay = showBackupOverlay('Backup wird gelöscht...');
    
    const formData = new FormData();
    formData.append('delete_backup', '1');
    formData.append('backup_id', filename);
    
    fetch('includes/admin/backup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            throw new Error('Netzwerkfehler');
        }
    })
    .catch(error => {
        console.error('Delete Error:', error);
        showAlert('error', 'Fehler beim Löschen des Backups');
    })
    .then(() => {
        removeBackupOverlay(overlay);
    });
}

// Backup herunterladen
function downloadBackup(filename) {
    window.location.href = `includes/admin/download_backup.php?file=${encodeURIComponent(filename)}`;
}

// Hilfsfunktionen
function getBackupTypeName(type) {
    switch(type) {
        case 'full': return 'vollständiges Backup';
        case 'db': return 'Datenbank-Backup';
        case 'files': return 'Dateien-Backup';
        default: return 'Backup';
    }
}

// Overlay für Backup-Prozess anzeigen
function showBackupOverlay(message) {
    const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    
    const overlay = document.createElement('div');
    overlay.id = 'backupOverlay';
    overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
    overlay.style.backgroundColor = isDarkTheme ? 'rgba(0, 0, 0, 0.75)' : 'rgba(0, 0, 0, 0.5)';
    overlay.style.zIndex = '9999';
    
    const content = document.createElement('div');
    content.className = `${isDarkTheme ? 'bg-dark' : 'bg-white'} p-4 rounded-3 shadow-lg text-center`;
    content.style.maxWidth = '400px';
    
    const spinnerColor = isDarkTheme ? 'text-light' : 'text-primary';
    const textColor = isDarkTheme ? 'text-light' : 'text-dark';
    const mutedTextColor = isDarkTheme ? 'text-light opacity-75' : 'text-muted';
    
    content.innerHTML = `
        <div class="mb-4">
            <div class="spinner-border ${spinnerColor}" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Wird ausgeführt...</span>
            </div>
        </div>
        <h5 class="mb-3 ${textColor}">${message}</h5>
        <div class="progress" style="height: 10px; background-color: ${isDarkTheme ? 'rgba(255,255,255,0.1)' : ''}">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: 100%"></div>
        </div>
        <p class="${mutedTextColor} mt-3 mb-0">Bitte haben Sie einen Moment Geduld...</p>
    `;
    
    overlay.appendChild(content);
    document.body.appendChild(overlay);
    
    return overlay;
}

// Overlay entfernen
function removeBackupOverlay(overlay) {
    if (overlay && overlay.parentNode) {
        overlay.remove();
    }
}

// Alert anzeigen
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    
    const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    if (isDarkTheme) {
        alertDiv.style.backgroundColor = type === 'success' ? '#1a472a' : '#471a1a';
        alertDiv.style.color = '#ffffff';
        alertDiv.style.borderColor = type === 'success' ? '#2e8b57' : '#8b2e2e';
    }
    
    alertDiv.style.zIndex = '9999';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = isDarkTheme ? '0 0.5rem 1rem rgba(0, 0, 0, 0.5)' : '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ${isDarkTheme ? 'btn-close-white' : ''}" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    const bsAlert = getAlert(alertDiv);
    if (bsAlert) {
        setTimeout(() => {
            bsAlert.close();
            alertDiv.addEventListener('closed.bs.alert', () => {
                alertDiv.remove();
            });
        }, 5000);
    } else {
        setTimeout(() => alertDiv.remove(), 5000);
    }
} 