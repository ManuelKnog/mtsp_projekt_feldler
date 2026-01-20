<?php
session_start();

// Datenbankverbindung
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Suchparameter aus URL
$suche = trim($_GET["suche"] ?? "");
$kategorie = $_GET["kategorie"] ?? "";
$verlag = $_GET["verlag"] ?? "";

// SQL-Query mit dynamischen Filtern (WHERE 1=1 ermöglicht einfaches Anhängen von AND-Bedingungen)
$sql = "SELECT buch_nr, isbn, titel, autor, verlag, beschreibung, kategorie FROM buch WHERE 1=1";
if ($suche !== "") {
    $suche_esc = $conn->real_escape_string($suche);
    $sql .= " AND (titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%' OR beschreibung LIKE '%$suche_esc%')";
}
if ($kategorie !== "") {
    $kategorie_esc = $conn->real_escape_string($kategorie);
    $sql .= " AND kategorie = '$kategorie_esc'";
}
if ($verlag !== "") {
    $verlag_esc = $conn->real_escape_string($verlag);
    $sql .= " AND verlag = '$verlag_esc'";
}
$sql .= " ORDER BY buch_nr ASC";
$ergebnis = $conn->query($sql);

// Kategorien und Verlage für Dropdown-Menüs
$kategorien_result = $conn->query("SELECT DISTINCT kategorie FROM buch WHERE kategorie IS NOT NULL AND kategorie != '' ORDER BY kategorie");
$verlage_result = $conn->query("SELECT DISTINCT verlag FROM buch WHERE verlag IS NOT NULL AND verlag != '' ORDER BY verlag");
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
    <nav class="header-bar">
        <h1><a href="index.php" style="text-decoration: none; color: inherit;">Bibliothek-Knogler</a></h1>
        <div class="header-actions">
            <?php if (isset($_SESSION['bibliothekar_angemeldet']) && $_SESSION['bibliothekar_angemeldet'] === true): ?>
                <a href="verwaltung.php">Verwaltung</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Anmelden</a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <h2>Büchersuche</h2>
        
        <section>
            <form method="get">
                <label for="suche">Volltextsuche (Titel, Autor, Beschreibung)</label>
                <input type="text" id="suche" name="suche" value="<?php echo htmlspecialchars($suche); ?>" placeholder="Suchbegriff eingeben">
                <label for="kategorie">Kategorie</label>
                <select id="kategorie" name="kategorie">
                    <option value="">Alle Kategorien</option>
                    <?php if ($kategorien_result && $kategorien_result->num_rows > 0): ?>
                        <?php while ($kat = $kategorien_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($kat["kategorie"]); ?>" <?php echo $kategorie === $kat["kategorie"] ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($kat["kategorie"]); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <label for="verlag">Verlag</label>
                <select id="verlag" name="verlag">
                    <option value="">Alle Verlage</option>
                    <?php if ($verlage_result && $verlage_result->num_rows > 0): ?>
                        <?php while ($ver = $verlage_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($ver["verlag"]); ?>" <?php echo $verlag === $ver["verlag"] ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($ver["verlag"]); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <button type="submit">Suchen</button>
            </form>
        </section>

        <h2>Alle Bücher</h2>
        <section>
            <table>
                <thead>
                    <tr>
                        <th>Nr</th>
                        <th>ISBN</th>
                        <th>Titel</th>
                        <th>Autor</th>
                        <th>Verlag</th>
                        <th>Kategorie</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ergebnis && $ergebnis->num_rows > 0): ?>
                        <?php while ($zeile = $ergebnis->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $zeile["buch_nr"]; ?></td>
                                <td><?php echo htmlspecialchars($zeile["isbn"]); ?></td>
                                <td><strong><?php echo htmlspecialchars($zeile["titel"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($zeile["autor"]); ?></td>
                                <td><?php echo htmlspecialchars($zeile["verlag"]); ?></td>
                                <td><?php echo htmlspecialchars($zeile["kategorie"] ?? "-"); ?></td>
                                <td><?php echo htmlspecialchars(substr($zeile["beschreibung"] ?? "", 0, 100)) . (strlen($zeile["beschreibung"] ?? "") > 100 ? "..." : ""); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Keine Bücher gefunden.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>

