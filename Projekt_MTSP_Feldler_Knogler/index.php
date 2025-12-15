<?php
$dbName = "mtsp_übung";
$conn = new mysqli("localhost", "root", "", $dbName);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$suche = isset($_GET["suche"]) ? $_GET["suche"] : "";

$sql = "SELECT buch_nr, isbn, titel, autor, verlag FROM buch";

if ($suche !== "") {
    $suche_esc = $conn->real_escape_string($suche);
    $sql .= " WHERE titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%'";
}

$ergebnis = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothek – Bücher</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <header class="header-bar">
        <h1>Bibliothek-Knogler</h1>
        <a id="button-login" href="login.html">Anmelden</a>
    </header>

    <main>
        <h2>Büchersuche</h2>
        <form method="get">
            <label for="suche">Titel oder Autor</label>
            <input type="text" id="suche" name="suche" value="<?php echo htmlspecialchars($suche); ?>">
            <button type="submit">Suchen</button>
        </form>

        <h2>Alle Bücher</h2>
        <table>
        <tr>
            <th>Nr</th>
            <th>ISBN</th>
            <th>Titel</th>
            <th>Autor</th>
            <th>Verlag</th>
        </tr>
        <?php if ($ergebnis && $ergebnis->num_rows > 0): ?>
            <?php while ($zeile = $ergebnis->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $zeile["buch_nr"]; ?></td>
                    <td><?php echo htmlspecialchars($zeile["isbn"]); ?></td>
                    <td><?php echo htmlspecialchars($zeile["titel"]); ?></td>
                    <td><?php echo htmlspecialchars($zeile["autor"]); ?></td>
                    <td><?php echo htmlspecialchars($zeile["verlag"]); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Keine Bücher gefunden.</td>
            </tr>
        <?php endif; ?>
        </table>
    </main>

</body>
</html>


