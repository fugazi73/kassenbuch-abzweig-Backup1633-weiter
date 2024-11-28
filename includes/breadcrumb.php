<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="kassenbuch.php">Start</a></li>
        <?php if (isset($breadcrumb_title)): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($breadcrumb_title) ?></li>
        <?php endif; ?>
    </ol>
</nav> 