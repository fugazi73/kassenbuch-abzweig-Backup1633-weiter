<?php
session_start();
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

// Startbetrag-Informationen abrufen
$startbetrag_query = $conn->query("SELECT datum, einnahme as betrag FROM kassenbuch_eintraege WHERE bemerkung = 'Startbetrag' ORDER BY datum DESC LIMIT 1");
$startbetrag_info = $startbetrag_query->fetch_assoc();
$startbetrag = $startbetrag_info['betrag'] ?? 0;
$startbetrag_datum = $startbetrag_info['datum'] ?? date('Y-m-d');

// Benutzer aus der Datenbank abrufen
$users = $conn->query("SELECT id, username, role FROM benutzer ORDER BY username");

$page_title = 'Administration | Kassenbuch';
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Benutzerverwaltung -->
    <div class="card mb-5" id="benutzerverwaltung">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people"></i> Benutzerverwaltung</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="bi bi-person-plus"></i> Neuer Benutzer
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Benutzername</th>
                            <th>Rolle</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-muted me-2"></i>
                                    <?= htmlspecialchars($user['username']) ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $roleBadgeClass = match($user['role']) {
                                    'admin' => 'bg-primary',
                                    'chef' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                                $roleDisplayName = match($user['role']) {
                                    'admin' => 'Administrator',
                                    'chef' => 'Chef',
                                    default => 'Benutzer'
                                };
                                ?>
                                <span class="badge <?= $roleBadgeClass ?>">
                                    <?= $roleDisplayName ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editUser(<?= $user['id'] ?>)" 
                                            title="Benutzer bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteUser(<?= $user['id'] ?>)"
                                            title="Benutzer löschen">
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

    <!-- Kassenstand -->
    <div class="card mb-5" id="kassenstand">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cash-register"></i> Kassenstand</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#startbetragModal">
                <i class="bi bi-cash me-2"></i>
                Startbetrag ändern
            </button>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Der aktuelle Startbetrag wurde am <?= date('d.m.Y', strtotime($startbetrag_datum)) ?> 
                auf <?= number_format($startbetrag, 2, ',', '.') ?> € festgelegt.
            </div>
        </div>
    </div>
</div>

<!-- Abstand am Ende der Seite -->
<div class="mb-5"></div>

<!-- Modals für Benutzer und Startbetrag -->
<?php 
require_once 'includes/user_modals.php';
require_once 'includes/startbetrag_modal.php';
require_once 'includes/footer.php'; 
?>

<script>
async function saveNewUser(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const formData = new FormData(form);

        const response = await fetch('save_user.php', {
            method: 'POST',
            body: formData
        });

        // Prüfe auf Server-Fehler
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            form.reset();
            location.reload(); // oder loadUsers();
        } else {
            throw new Error(data.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Speichern des Benutzers: ' + error.message);
    }
}

// Event-Listener für das Formular
document.getElementById('newUserForm').addEventListener('submit', saveNewUser);
</script> 

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.nav-link {
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.btn-primary {
    padding: 0.375rem 1rem;
}

.input-group {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style> 

<!-- Bootstrap Bundle hinzufügen -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
error_log("Current page: " . basename($_SERVER['PHP_SELF']) . ", User Role: " . ($_SESSION['user_role'] ?? 'Not set'));
?> 

<script>
function deleteUser(userId) {
    if (!confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', userId);

    fetch('delete_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Seite neu laden nach erfolgreichem Löschen
        } else {
            alert(data.message || 'Fehler beim Löschen des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}
</script> 

<script>
function loadStats() {
    fetch('get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stats_eintraege_heute').textContent = data.eintraege_heute;
                document.getElementById('stats_umsatz_heute').textContent = data.umsatz_heute + ' €';
                document.getElementById('stats_eintraege_monat').textContent = data.eintraege_monat;
                document.getElementById('stats_umsatz_monat').textContent = data.umsatz_monat + ' €';
                document.getElementById('current_saldo').textContent = data.current_saldo + ' €';
                document.getElementById('current_kassenstand').textContent = data.current_kassenstand + ' €';
            }
        })
        .catch(error => console.error('Error:', error));
}
</script> 
