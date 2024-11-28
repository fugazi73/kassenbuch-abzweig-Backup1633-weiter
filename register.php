<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verbindungsprüfung an den Anfang
    if (!$conn) {
        die("Datenbankverbindung fehlgeschlagen");
    }

    // Validierung der Eingaben
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    // Passwort-Komplexität prüfen
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Passwort muss mindestens 8 Zeichen lang sein und Groß-, Kleinbuchstaben sowie Zahlen enthalten.";
    }

    // Prüfen, ob der Benutzername bereits existiert
    $stmt = $conn->prepare("SELECT id FROM benutzer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Benutzername existiert bereits.";
    } else {
        // Benutzer registrieren
        $hashed_password = hash_password($password);
        $stmt = $conn->prepare("INSERT INTO benutzer (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);
        if ($stmt->execute()) {
            $success = "Registrierung erfolgreich! <a href='login.php'>Hier einloggen</a>";
        } else {
            $error = "Fehler bei der Registrierung.";
        }
    }
}

if (!$conn) {
    die("Datenbankverbindung fehlgeschlagen");
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-center">Registrierung</h5>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php elseif (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Benutzername</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Passwort</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
