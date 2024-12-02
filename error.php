<?php
require_once 'includes/init.php';
$page_title = "Fehler - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';

$error_message = $_GET['message'] ?? 'Ein unbekannter Fehler ist aufgetreten.';
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h1 class="h3 card-title text-danger mb-4">
                <i class="bi bi-exclamation-triangle"></i> Fehler
            </h1>
            
            <p class="lead mb-4">
                <?= htmlspecialchars($error_message) ?>
            </p>
            
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-house"></i> Zur Startseite
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 