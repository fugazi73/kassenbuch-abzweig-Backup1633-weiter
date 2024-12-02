<?php
require_once '../includes/init.php';
$page_title = "Über meincode.eu - " . htmlspecialchars($site_name ?? '');
include '../includes/header.php';
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-info-circle text-primary"></i> Entwickelt von meincode.eu
            </h1>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="mb-4">
                        <p class="lead">
                            Professionelle Webentwicklung und maßgeschneiderte Softwarelösungen für Ihr Unternehmen.
                        </p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h5 fw-bold mb-3">
                            <i class="bi bi-code-square text-primary me-2"></i>Expertise
                        </h2>
                        <p>
                            Als erfahrener Entwickler biete ich individuelle Lösungen für die digitalen Herausforderungen 
                            Ihres Unternehmens. Von der Konzeption bis zur Umsetzung steht die Qualität und 
                            Benutzerfreundlichkeit im Fokus.
                        </p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h5 fw-bold mb-3">
                            <i class="bi bi-tools text-primary me-2"></i>Dienstleistungen
                        </h2>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check2-circle text-success me-2"></i>
                                    <span>Webentwicklung</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check2-circle text-success me-2"></i>
                                    <span>Datenbanklösungen</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check2-circle text-success me-2"></i>
                                    <span>WordPress Entwicklung</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check2-circle text-success me-2"></i>
                                    <span>Softwareoptimierung</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="h5 fw-bold mb-3">
                            <i class="bi bi-envelope text-primary me-2"></i>Kontakt
                        </h2>
                        <div class="mb-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="vstack gap-3">
                                        <div>
                                            <div class="text-muted small">Adresse</div>
                                            <div>Patrick Bednarz</div>
                                            <div>Brauerstraße 36</div>
                                            <div>46236 Bottrop</div>
                                        </div>
                                        <div>
                                            <div class="text-muted small">E-Mail</div>
                                            <a href="mailto:info@meincode.eu" class="text-decoration-none">
                                                info@meincode.eu
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="vstack gap-3">
                                        <div>
                                            <div class="text-muted small">Telefon</div>
                                            <div>+49 (2041) 693565</div>
                                            <div>+49 (2041) 4065920</div>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Web</div>
                                            <a href="https://meincode.eu" target="_blank" class="text-decoration-none">
                                                meincode.eu
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="https://meincode.eu" target="_blank" class="btn btn-primary">
                            <i class="bi bi-globe me-2"></i>Website besuchen
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body">
                            <h3 class="h5 fw-bold mb-3">
                                <i class="bi bi-stack text-primary me-2"></i>Technologien
                            </h3>
                            <div class="list-group list-group-flush mb-4">
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-filetype-php text-primary me-2"></i>
                                        <div>PHP</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-database text-primary me-2"></i>
                                        <div>MySQL</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-filetype-js text-primary me-2"></i>
                                        <div>JavaScript</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-filetype-html text-primary me-2"></i>
                                        <div>HTML5</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-filetype-css text-primary me-2"></i>
                                        <div>CSS3</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-bootstrap text-primary me-2"></i>
                                        <div>Bootstrap</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-git text-primary me-2"></i>
                                        <div>Git</div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-wordpress text-primary me-2"></i>
                                        <div>WordPress</div>
                                    </div>
                                </div>
                            </div>

                            <h3 class="h5 fw-bold mb-3">
                                <i class="bi bi-award text-primary me-2"></i>Zertifizierungen
                            </h3>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-patch-check text-success me-2"></i>
                                        <div>
                                            <div class="fw-medium">PHP Certified Developer</div>
                                            <small class="text-muted">Zend Certification</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group-item bg-transparent px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-patch-check text-success me-2"></i>
                                        <div>
                                            <div class="fw-medium">MySQL Database Administrator</div>
                                            <small class="text-muted">Oracle Certification</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[data-bs-theme="dark"] .card.bg-light {
    background-color: var(--bs-dark) !important;
}

.list-group-item.bg-transparent {
    border-color: var(--bs-border-color);
}
</style>

<?php include '../includes/footer.php'; ?> 