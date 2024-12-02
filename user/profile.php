<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

$page_title = "Profil bearbeiten - " . htmlspecialchars($site_name ?? '');
include '../includes/header.php';

$success = $error = '';

// Benutzerdaten laden
try {
    $stmt = $conn->prepare("SELECT username FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        throw new Exception("Benutzerdaten konnten nicht geladen werden.");
    }
    $user['email'] = '';
} catch (Exception $e) {
    $error = "Fehler beim Laden der Benutzerdaten: " . $e->getMessage();
    $user = ['username' => $_SESSION['username'] ?? '', 'email' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Bitte geben Sie eine E-Mail-Adresse ein.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Bitte geben Sie eine gültige E-Mail-Adresse ein.";
    } else {
        try {
            $update_stmt = $conn->prepare("UPDATE benutzer SET email = ? WHERE id = ?");
            $update_stmt->bind_param("si", $email, $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $success = "Ihre Profildaten wurden erfolgreich aktualisiert.";
                $user['email'] = $email;
            } else {
                throw new Exception("Fehler beim Aktualisieren der Daten.");
            }
        } catch (Exception $e) {
            $error = "Fehler beim Aktualisieren der Profildaten: " . $e->getMessage();
        }
    }
}
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-person-gear text-primary"></i> Profil bearbeiten
            </h1>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">Benutzername</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" 
                           disabled readonly>
                    <div class="form-text">Der Benutzername kann nicht geändert werden.</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Speichern
                    </button>
                    <a href="../kassenbuch.php" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 