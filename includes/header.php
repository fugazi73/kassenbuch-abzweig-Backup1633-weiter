<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kassenbuch' ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
    /* Light Theme Grundeinstellungen */
    :root {
        --background-color: #f8f9fa;
        --nav-background: #ffffff;
        --card-background: #ffffff;
        --text-color: #333333;
        --border-color: #dee2e6;
        --table-hover: #f5f5f5;
        --input-background: #ffffff;
        --input-border: #ced4da;
        --input-text: #333333;
        --btn-background: #ffffff;
        --btn-border: #ced4da;
        --btn-text: #333333;
    }

    /* Grundlegende Light Theme Container Styles */
    .table-container {
        background-color: var(--card-background);
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    /* Dann die Dark Theme Überschreibungen wie gehabt */
    [data-theme="dark"] .table-container {
        background-color: var(--card-background);
        border: none !important;
        box-shadow: 0 0 0 1px var(--border-color);
        border-radius: 4px;
        overflow: hidden;
    }

    :root[data-theme="dark"] {
        --background-color: #1e1e1e;
        --nav-background: #252525;
        --card-background: #2d2d2d;
        --text-color: #e4e6eb;
        --border-color: #404040;
        --table-hover: #353535;
        --input-background: #3d3d3d;
        --input-border: #505050;
        --input-text: #e4e6eb;
        --btn-background: #3d3d3d;
        --btn-border: #505050;
        --btn-text: #e4e6eb;
    }

    /* Grundlegende Dark Theme Styles */
    [data-theme="dark"] body {
        background-color: var(--background-color);
        color: var(--text-color);
    }

    [data-theme="dark"] .navbar {
        background-color: var(--nav-background);
        border-bottom: 1px solid var(--border-color);
    }

    [data-theme="dark"] .card {
        background-color: var(--card-background);
        border: none !important;
        box-shadow: 0 0 0 1px var(--border-color);
    }

    [data-theme="dark"] .table {
        color: var(--text-color);
        background-color: var(--card-background);
        margin-bottom: 0;
    }

    [data-theme="dark"] .table thead th {
        background-color: #1a1a1a;
        color: var(--text-color);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .table tbody tr {
        background-color: var(--card-background);
    }

    [data-theme="dark"] .table tbody tr:nth-child(odd) {
        background-color: #262626;
    }

    [data-theme="dark"] .table tbody tr:hover {
        background-color: #363636;
    }

    [data-theme="dark"] .table td {
        color: var(--text-color);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: var(--input-background);
        border-color: var(--input-border);
        color: var(--input-text);
    }

    [data-theme="dark"] .btn-outline-secondary {
        background-color: var(--btn-background);
        border-color: var(--btn-border);
        color: var(--btn-text);
    }

    [data-theme="dark"] .btn-outline-secondary:hover {
        background-color: var(--table-hover);
    }

    /* Zusätzliche Anpassungen für spezifische Elemente */
    [data-theme="dark"] .card-title,
    [data-theme="dark"] .card-subtitle {
        color: var(--text-color);
    }

    [data-theme="dark"] .text-muted {
        color: #8f9296 !important;
    }

    [data-theme="dark"] .modal-content {
        background-color: var(--card-background);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .modal-header,
    [data-theme="dark"] .modal-footer {
        border-color: var(--border-color);
    }

    /* Anpassungen für Datepicker und andere Input-Elemente */
    [data-theme="dark"] input[type="date"] {
        color-scheme: dark;
    }

    [data-theme="dark"] ::placeholder {
        color: #8f9296 !important;
    }

    /* Scrollbar Anpassungen für Webkit Browser */
    [data-theme="dark"] ::-webkit-scrollbar {
        width: 12px;
    }

    [data-theme="dark"] ::-webkit-scrollbar-track {
        background: var(--background-color);
    }

    [data-theme="dark"] ::-webkit-scrollbar-thumb {
        background-color: var(--border-color);
        border-radius: 6px;
        border: 3px solid var(--background-color);
    }

    /* Sicherstellen, dass Links und Buttons gut lesbar sind */
    [data-theme="dark"] a, 
    [data-theme="dark"] .btn {
        color: var(--primary-color);
    }

    [data-theme="dark"] .btn:hover {
        background-color: var(--primary-color);
        color: #fff;
    }

    /* Tabellen-Container im dunklen Theme */
    [data-theme="dark"] .table-container {
        background-color: var(--card-background);
        border: none !important;
        box-shadow: 0 0 0 1px var(--border-color);
        border-radius: 4px;
        overflow: hidden;
    }

    [data-theme="dark"] .table-container .table {
        margin-bottom: 0;
    }

    [data-theme="dark"] .table-container .table thead th {
        background-color: #1a1a1a;
        border-bottom: 1px solid var(--border-color);
    }

    [data-theme="dark"] .table-container .table tbody tr {
        background-color: var(--card-background);
    }

    [data-theme="dark"] .table-container .table tbody tr:nth-child(odd) {
        background-color: #262626;
    }

    [data-theme="dark"] .table-container .table tbody tr:hover {
        background-color: #363636;
    }

    [data-theme="dark"] .table-container .table td,
    [data-theme="dark"] .table-container .table th {
        border-color: var(--border-color);
    }

    /* Pagination innerhalb des Containers */
    [data-theme="dark"] .table-container .pagination-container {
        background-color: var(--card-background);
        border-top: 1px solid var(--border-color);
        padding: 1rem;
    }

    /* Light Theme Pagination */
    .pagination {
        margin: 0;
    }

    .pagination .page-item .page-link {
        background-color: var(--card-background);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #ffffff;
    }

    .pagination .page-item.disabled .page-link {
        background-color: var(--background-color);
        border-color: var(--border-color);
        color: #6c757d;
    }

    /* Dark Theme Pagination */
    [data-theme="dark"] .pagination .page-item .page-link {
        background-color: var(--card-background);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    [data-theme="dark"] .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #ffffff;
    }

    [data-theme="dark"] .pagination .page-item.disabled .page-link {
        background-color: var(--background-color);
        border-color: var(--border-color);
        color: #6c757d;
    }

    [data-theme="dark"] .pagination .page-link:hover {
        background-color: var(--table-hover);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    /* Pagination Container */
    .pagination-container {
        padding: 1rem;
        border-top: 1px solid var(--border-color);
        background-color: var(--card-background);
    }

    [data-theme="dark"] .pagination-container {
        background-color: var(--card-background);
        border-top: 1px solid var(--border-color);
    }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<!-- Bootstrap Bundle NACH der Navigation -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 