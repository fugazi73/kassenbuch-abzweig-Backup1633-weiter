<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Prüfe ob Admin-Berechtigung vorhanden
if (!check_permission('manage_permissions')) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

// Speichern von Änderungen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    // Lösche alte Berechtigungen
    $conn->query("TRUNCATE TABLE role_permissions");
    
    // Speichere neue Berechtigungen
    $stmt = $conn->prepare("INSERT INTO role_permissions (role, permission, allowed) VALUES (?, ?, ?)");
    
    foreach ($_POST['permissions'] as $role => $permissions) {
        foreach ($permissions as $permission => $allowed) {
            $stmt->bind_param("ssi", $role, $permission, $allowed);
            $stmt->execute();
        }
    }
    
    // Speichere Erfolgsmeldung in Session
    $_SESSION['success_message'] = "Berechtigungen wurden gespeichert.";
    
    // Leite um mit JavaScript
    echo "<script>window.location.href = 'admin_permissions.php';</script>";
    exit;
}

$page_title = "Berechtigungsverwaltung - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';

// Hole aktuelle Berechtigungen
$current_permissions = [];
$result = $conn->query("SELECT role, permission, allowed FROM role_permissions");
while ($row = $result->fetch_assoc()) {
    $current_permissions[$row['role']][$row['permission']] = (bool)$row['allowed'];
}
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-shield-lock text-primary"></i> Berechtigungsverwaltung
            </h1>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>

            <form method="post">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Berechtigung</th>
                                <?php foreach (get_all_roles() as $role => $role_name): ?>
                                    <th class="text-center"><?= htmlspecialchars($role_name) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (get_all_permissions() as $permission => $description): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($description) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($permission) ?></small>
                                </td>
                                <?php foreach (get_all_roles() as $role => $role_name): ?>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" 
                                               name="permissions[<?= $role ?>][<?= $permission ?>]" 
                                               value="1"
                                               <?= ($current_permissions[$role][$permission] ?? false) ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="save_permissions" class="btn btn-primary">
                        <i class="bi bi-save"></i> Berechtigungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 