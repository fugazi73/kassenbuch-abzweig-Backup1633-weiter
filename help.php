<?php
require_once 'config.php';
$page_title = 'Hilfe & Dokumentation';
require_once 'includes/header.php';

// Hole Changelog-Daten
$stmt = $conn->prepare("SELECT * FROM changelog ORDER BY date DESC");
$stmt->execute();
$changelog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid help-container mt-4">
    <div class="row">
        <!-- Linke Sidebar mit Navigation -->
        <div class="col-md-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <nav class="nav flex-column help-nav">
                        <a class="nav-link active" href="#grundlagen">Grundlagen</a>
                        <a class="nav-link" href="#kassenbuch">Kassenbuch</a>
                        <a class="nav-link" href="#berichte">Berichte & Auswertungen</a>
                        <?php if (is_chef() || is_admin()): ?>
                            <a class="nav-link" href="#verwaltung">Verwaltung</a>
                            <a class="nav-link" href="#einstellungen">Systemeinstellungen</a>
                        <?php endif; ?>
                        <a class="nav-link" href="#changelog">Changelog</a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Hauptinhalt -->
        <div class="col-md-9">
            <!-- Grundlagen -->
            <section id="grundlagen" class="mb-5">
                <h2>Grundlagen</h2>
                <div class="card">
                    <div class="card-body">
                        <h3>Erste Schritte</h3>
                        <p>Willkommen im Kassenbuch-System! Hier finden Sie alle wichtigen Informationen...</p>
                        
                        <h3>Navigation</h3>
                        <div class="help-image-container">
                            <img src="images/help/navigation.png" alt="Navigation" class="img-fluid">
                            <p class="help-caption">Die Hauptnavigation des Systems</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Changelog -->
            <section id="changelog" class="mb-5">
                <h2>Changelog</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($changelog as $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <?= date('d.m.Y', strtotime($entry['date'])) ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h4><?= htmlspecialchars($entry['version']) ?></h4>
                                        <div class="changelog-badges">
                                            <?php foreach (json_decode($entry['categories']) as $category): ?>
                                                <span class="badge bg-<?= get_category_color($category) ?>">
                                                    <?= htmlspecialchars($category) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="changelog-details mt-2">
                                            <?= nl2br(htmlspecialchars($entry['description'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
/* Timeline Styling */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding: 20px 0;
    border-left: 2px solid var(--border-color);
    margin-left: 20px;
}

.timeline-date {
    position: absolute;
    left: -150px;
    width: 120px;
    text-align: right;
    color: var(--text-color);
    opacity: 0.8;
}

.timeline-content {
    margin-left: 20px;
    padding: 15px;
    background: var(--card-bg);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.changelog-badges .badge {
    margin-right: 5px;
}

/* Help Navigation */
.help-nav .nav-link {
    color: var(--text-color);
    padding: 10px 15px;
    border-radius: 4px;
}

.help-nav .nav-link:hover {
    background-color: var(--nav-hover);
}

.help-nav .nav-link.active {
    background-color: var(--nav-active);
    color: var(--nav-active-color);
}

/* Image Containers */
.help-image-container {
    margin: 20px 0;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.help-caption {
    padding: 10px;
    background: var(--card-bg);
    margin: 0;
    border-top: 1px solid var(--border-color);
    font-size: 0.9rem;
    color: var(--text-color);
    opacity: 0.8;
}
</style> 