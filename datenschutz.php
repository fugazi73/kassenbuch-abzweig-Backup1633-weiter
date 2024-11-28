<?php
require_once 'config.php';
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'datenschutz_content'");
$stmt->execute();
$content = $stmt->get_result()->fetch_assoc()['setting_value'];
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h1>Datenschutz</h1>
        </div>
        <div class="card-body">
            <?= nl2br(htmlspecialchars($content)) ?>
        </div>
    </div>
</div> 