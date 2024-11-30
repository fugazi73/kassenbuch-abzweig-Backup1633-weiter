<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Prüfe Berechtigungen
if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Benutzerverwaltung | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row my-4">
        <!-- Benutzer aus der Datenbank abrufen -->
        <?php $users = $conn->query("SELECT id, username, role FROM benutzer ORDER BY username"); ?>

        <!-- CSS für Benutzerverwaltung -->
        <link href="styles/benutzerverwaltung.css" rel="stylesheet">

        <!-- Benutzerverwaltung -->
        <div class="card mb-5" id="benutzerverwaltung">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people"></i> Benutzerverwaltung</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
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
                                    $roleBadgeClass = '';
                                    switch($user['role']) {
                                        case 'admin':
                                            $roleBadgeClass = 'bg-primary';
                                            break;
                                        case 'chef':
                                            $roleBadgeClass = 'bg-success'; 
                                            break;
                                        default:
                                            $roleBadgeClass = 'bg-secondary';
                                    }
                                    $roleDisplayName = '';
                                    switch($user['role']) {
                                        case 'admin':
                                            $roleDisplayName = 'Administrator';
                                            break;
                                        case 'chef':
                                            $roleDisplayName = 'Chef';
                                            break;
                                        default:
                                            $roleDisplayName = 'Benutzer';
                                    }
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
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteUser(<?= $user['id'] ?>)" 
                                                title="Benutzer löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- Bootstrap Bundle mit Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?> 