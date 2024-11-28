<?php
require_once 'config.php';
check_login();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $current_password = filter_var($_POST['current_password'], FILTER_SANITIZE_STRING);
    $new_password = filter_var($_POST['new_password'], FILTER_SANITIZE_STRING);

    // Passwort-Komplexität prüfen
    if (strlen($new_password) < 8) {
        $error = "Das neue Passwort muss mindestens 8 Zeichen lang sein.";
        // ... exit oder weitere Verarbeitung ...
    }

    // CSRF-Schutz hinzufügen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ungültige Anfrage";
        // ... exit oder weitere Verarbeitung ...
    }

    // Aktuelles Passwort überprüfen
    $stmt = $conn->prepare("SELECT password FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (verify_password($current_password, $row['password'])) {
            // Neues Passwort setzen
            $hashed_password = hash_password($new_password);
            $update_stmt = $conn->prepare("UPDATE benutzer SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $success = "Passwort erfolgreich geändert.";
            } else {
                $error = "Fehler beim Ändern des Passworts.";
            }
        } else {
            $error = "Aktuelles Passwort ist falsch.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort ändern</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-center">Passwort ändern</h5>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php elseif (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Aktuelles Passwort</label>
                                <input type="password" name="current_password" id="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Neues Passwort</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Passwort ändern</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
