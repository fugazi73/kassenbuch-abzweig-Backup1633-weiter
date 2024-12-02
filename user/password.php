<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

$page_title = "Passwort ändern - " . htmlspecialchars($site_name ?? '');
include '../includes/header.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Bitte füllen Sie alle Felder aus.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Die neuen Passwörter stimmen nicht überein.";
    } elseif (strlen($new_password) < 8) {
        $error = "Das neue Passwort muss mindestens 8 Zeichen lang sein.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT password FROM benutzer WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                throw new Exception("Benutzer nicht gefunden.");
            }

            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE benutzer SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    $success = "Ihr Passwort wurde erfolgreich geändert.";
                } else {
                    throw new Exception("Fehler beim Ändern des Passworts.");
                }
            } else {
                $error = "Das aktuelle Passwort ist nicht korrekt.";
            }
        } catch (Exception $e) {
            $error = "Fehler: " . $e->getMessage();
        }
    }
}
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-key text-primary"></i> Passwort ändern
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
                    <label for="current_password" class="form-label">Aktuelles Passwort</label>
                    <input type="password" class="form-control" id="current_password" 
                           name="current_password" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">Neues Passwort</label>
                    <input type="password" class="form-control" id="new_password" 
                           name="new_password" required minlength="8">
                    <div class="form-text">Mindestens 8 Zeichen</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Neues Passwort bestätigen</label>
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Passwort ändern
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