<?php
// Am Anfang des Headers, vor dem DOCTYPE
$savedTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?php echo $savedTheme ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kassenbuch' ?></title>
    
    <!-- Bootstrap & Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles/main.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/main.js" defer></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'kassenbuch.php'): ?>
        <script src="js/kassenbuch.js" defer></script>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'admin.php'): ?>
        <script src="js/admin.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <?php require_once 'navbar.php'; ?>
</body>
</html> 