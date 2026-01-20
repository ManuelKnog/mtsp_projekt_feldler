<?php
session_start();

// Zugriff nur für angemeldete Bibliothekare
if (!isset($_SESSION['bibliothekar_angemeldet']) || $_SESSION['bibliothekar_angemeldet'] !== true) {
    header("Location: login.php");
    exit;
}

// Datenbankverbindung
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$meldung = "";

// Formularverarbeitung für CRUD-Operationen
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_action"])) {
    $action = $_POST["submit_action"];
    
    // Neues Buch hinzufügen
    if ($action === "add") {
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");
        $beschreibung = trim($_POST["beschreibung"] ?? "");
        $anschaffungspreis = floatval($_POST["anschaffungspreis"] ?? 0);
        $kategorie = trim($_POST["kategorie"] ?? "");
        
        // Prepared Statement für INSERT (Schutz vor SQL-Injection)
        $stmt = $conn->prepare("INSERT INTO buch (isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", $isbn, $titel, $autor, $verlag, $beschreibung, $anschaffungspreis, $kategorie);
        $stmt->execute();
        $meldung = "Buch wurde hinzugefügt.";
        $stmt->close();
    } 
    // Buch aktualisieren
    else if ($action === "update") {
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");
        $beschreibung = trim($_POST["beschreibung"] ?? "");
        $anschaffungspreis = floatval($_POST["anschaffungspreis"] ?? 0);
        $kategorie = trim($_POST["kategorie"] ?? "");
        
        // Prepared Statement für UPDATE
        $stmt = $conn->prepare("UPDATE buch SET isbn = ?, titel = ?, autor = ?, verlag = ?, beschreibung = ?, anschaffungspreis = ?, kategorie = ? WHERE buch_nr = ?");
        $stmt->bind_param("sssssdsi", $isbn, $titel, $autor, $verlag, $beschreibung, $anschaffungspreis, $kategorie, $buch_nr);
        $stmt->execute();
        $meldung = "Buch wurde aktualisiert.";
        $stmt->close();
    }
    // Buch löschen
    else if ($action === "delete") {
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM buch WHERE buch_nr = ?");
        $stmt->bind_param("i", $buch_nr);
        $stmt->execute();
        $meldung = "Buch wurde gelöscht.";
        $stmt->close();
    }
}

// Suchfunktion
$suche = trim($_GET["suche"] ?? "");

// SQL-Query für Bücherliste
$sql = "SELECT buch_nr, isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie FROM buch";
if ($suche !== "") {
    // Escaping für Sicherheit (verhindert SQL-Injection bei direkten Queries)
    $suche_esc = $conn->real_escape_string($suche);
    $sql .= " WHERE titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%' OR beschreibung LIKE '%$suche_esc%'";
}
$sql .= " ORDER BY buch_nr ASC";
$ergebnis = $conn->query($sql);

// Verfügbare Kategorien
$kategorien = ["Mechatronik", "Informationstechnik", "Allgemeinbildung", "Elektrotechnik", "Maschinenbau"];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verwaltung – Bücher</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="header-bar">
        <h1><a href="verwaltung.php" style="text-decoration: none; color: inherit;">Bibliotheksverwaltung</a></h1>
        <div class="header-actions">
            <a href="index.php">Öffentliche Übersicht</a>
            <a href="ausleihen.php">Ausleihen</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <h2>Bücher verwalten</h2>
        
        <?php if (!empty($meldung)): ?>
            <p style="color:green"><?php echo htmlspecialchars($meldung); ?></p>
        <?php endif; ?>

        <section>
            <form method="get" action="verwaltung.php">
                <label for="suche">Büchersuche (Titel, Autor oder Beschreibung)</label>
                <input type="text" id="suche" name="suche" value="<?php echo htmlspecialchars($suche); ?>" placeholder="Suchbegriff eingeben...">
                <button type="submit">Suchen</button>
            </form>
        </section>

        <section>
        <section>
            <h3>Alle Bücher</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nr</th>
                        <th>ISBN</th>
                        <th>Titel</th>
                        <th>Autor</th>
                        <th>Verlag</th>
                        <th>Beschreibung</th>
                        <th>Preis</th>
                        <th>Kategorie</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <form method="post" action="verwaltung.php">
                            <td>-</td>
                            <td><input type="text" name="isbn" value="" placeholder="ISBN" required></td>
                            <td><input type="text" name="titel" value="" placeholder="Titel" required></td>
                            <td><input type="text" name="autor" value="" placeholder="Autor" required></td>
                            <td><input type="text" name="verlag" value="" placeholder="Verlag" required></td>
                            <td><textarea name="beschreibung" rows="1" placeholder="Beschreibung"></textarea></td>
                            <td><input type="number" step="0.01" name="anschaffungspreis" value="" placeholder="Preis"></td>
                            <td>
                                <select name="kategorie">
                                    <option value="">Keine</option>
                                    <?php foreach ($kategorien as $kat): ?>
                                        <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="white-space: nowrap;">
                                <button type="submit" name="submit_action" value="add">Hinzufügen</button>
                            </td>
                        </form>
                    </tr>
                    <?php if ($ergebnis && $ergebnis->num_rows > 0): ?>
                        <?php while ($row = $ergebnis->fetch_assoc()): ?>
                            <tr>
                                <form method="post" action="verwaltung.php">
                                    <input type="hidden" name="buch_nr" value="<?php echo $row["buch_nr"]; ?>">
                                    <td><?php echo $row["buch_nr"]; ?></td>
                                    <td><input type="text" name="isbn" value="<?php echo htmlspecialchars($row["isbn"]); ?>" required></td>
                                    <td><input type="text" name="titel" value="<?php echo htmlspecialchars($row["titel"]); ?>" required></td>
                                    <td><input type="text" name="autor" value="<?php echo htmlspecialchars($row["autor"]); ?>" required></td>
                                    <td><input type="text" name="verlag" value="<?php echo htmlspecialchars($row["verlag"]); ?>" required></td>
                                    <td><textarea name="beschreibung" rows="2"><?php echo htmlspecialchars($row["beschreibung"] ?? ""); ?></textarea></td>
                                    <td><input type="number" step="0.01" name="anschaffungspreis" value="<?php echo htmlspecialchars($row["anschaffungspreis"] ?? ""); ?>"></td>
                                    <td>
                                        <select name="kategorie">
                                            <option value="">Keine</option>
                                            <?php foreach ($kategorien as $kat): ?>
                                                <option value="<?php echo htmlspecialchars($kat); ?>" <?php echo ($row["kategorie"] ?? "") === $kat ? "selected" : ""; ?>>
                                                    <?php echo htmlspecialchars($kat); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <button type="submit" name="submit_action" value="update">Speichern</button>
                                        <button type="submit" name="submit_action" value="delete">Löschen</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>
