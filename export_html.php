<?php
require_once 'config.php';
check_login();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $von = $_GET['von'];
    $bis = $_GET['bis'];

    $stmt = $conn->prepare("SELECT * FROM kassenbuch_eintraege WHERE datum BETWEEN ? AND ? ORDER BY datum ASC");
    $stmt->bind_param("ss", $von, $bis);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kassenbuch Bericht</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .no-print {
            display: none;
        }
        @media print {
            .no-print {
                display: none;
            }
            table {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body class="container">
    <div class="my-4">
        <h1 class="text-center">Kassenbuch Bericht</h1>
        <p class="text-center">Zeitraum: <?= htmlspecialchars($von) ?> bis <?= htmlspecialchars($bis) ?></p>

        <!-- Button zum Drucken -->
        <div class="text-center mb-3">
            <button class="btn btn-primary no-print" onclick="window.print()">Drucken</button>
        </div>

        <!-- Tabelle für den Bericht -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Beleg #</th>
                    <th>Bemerkung</th>
                    <th>Einnahme (€)</th>
                    <th>Ausgabe (€)</th>
                    <th>Saldo (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['datum'] ?></td>
                        <td><?= $row['beleg'] ?></td>
                        <td><?= htmlspecialchars($row['bemerkung']) ?></td>
                        <td><?= number_format($row['einnahme'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['ausgabe'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['saldo'], 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
