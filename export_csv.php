<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$dbname = "kassenbuch";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $von = $_GET['von'];
    $bis = $_GET['bis'];

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="Kassenbuch_' . $von . '_bis_' . $bis . '.csv"');

    $stmt = $conn->prepare("SELECT * FROM kassenbuch_eintraege WHERE datum BETWEEN ? AND ? ORDER BY datum ASC");
    $stmt->bind_param("ss", $von, $bis);
    $stmt->execute();
    $result = $stmt->get_result();

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Datum', 'Beleg', 'Bemerkung', 'Einnahme (€)', 'Ausgabe (€)', 'Saldo (€)']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['datum'],
            $row['beleg'],
            $row['bemerkung'],
            number_format($row['einnahme'], 2, ',', '.'),
            number_format($row['ausgabe'], 2, ',', '.'),
            number_format($row['saldo'], 2, ',', '.')
        ]);
    }
    fclose($output);
    exit;
}
?>
