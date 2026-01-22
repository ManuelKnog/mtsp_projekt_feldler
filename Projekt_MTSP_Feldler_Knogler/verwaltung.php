<?php
// Session starten - speichert Informationen über den angemeldeten Benutzer
session_start();

// Sicherheitsprüfung: Nur angemeldete Bibliothekare dürfen diese Seite sehen
// Wenn nicht angemeldet, wird der Benutzer automatisch zur Login-Seite weitergeleitet
if (!isset($_SESSION['bibliothekar_angemeldet']) || $_SESSION['bibliothekar_angemeldet'] !== true) {
    header("Location: login.php");
    exit;
}

// Verbindung zur MySQL-Datenbank herstellen
// Parameter: Server (localhost), Benutzername (root), Passwort (leer), Datenbankname (bibliothek_mtsp)
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
// UTF-8 Zeichensatz setzen, damit Umlaute (ä, ö, ü) korrekt gespeichert und angezeigt werden
$conn->set_charset("utf8mb4");

// Variable für Erfolgsmeldungen (wird später ausgegeben)
$meldung = "";

// Prüfen ob ein Formular abgeschickt wurde (POST = Daten werden im Hintergrund gesendet)
// CRUD = Create (Erstellen), Read (Lesen), Update (Aktualisieren), Delete (Löschen)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_action"])) {
    $action = $_POST["submit_action"];
    
    // Aktion: Neues Buch in die Datenbank einfügen
    if ($action === "add") {
        // Formulardaten auslesen und Leerzeichen am Anfang/Ende entfernen (trim)
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");
        $beschreibung = trim($_POST["beschreibung"] ?? "");
        // Preis in Dezimalzahl umwandeln (z.B. "19.99" wird zu 19.99)
        $anschaffungspreis = floatval($_POST["anschaffungspreis"] ?? 0);
        $kategorie = trim($_POST["kategorie"] ?? "");
        
        // Prepared Statement: SQL-Befehl vorbereiten mit Platzhaltern (?)
        // Dies verhindert SQL-Injection (Angriffe durch bösartige Eingaben)
        // Die Fragezeichen werden später durch die tatsächlichen Werte ersetzt
        $stmt = $conn->prepare("INSERT INTO buch (isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Werte an die Platzhalter binden: "sssssds" = 5 Strings, 1 Decimal, 1 String
        $stmt->bind_param("sssssds", $isbn, $titel, $autor, $verlag, $beschreibung, $anschaffungspreis, $kategorie);
        // SQL-Befehl ausführen
        $stmt->execute();
        $meldung = "Buch wurde hinzugefügt.";
        $stmt->close();
    } 
    // Aktion: Bestehendes Buch in der Datenbank aktualisieren
    else if ($action === "update") {
        // Buchnummer auslesen (welches Buch soll geändert werden?)
        // intval() wandelt den Wert in eine Ganzzahl um (Sicherheit)
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");
        $beschreibung = trim($_POST["beschreibung"] ?? "");
        $anschaffungspreis = floatval($_POST["anschaffungspreis"] ?? 0);
        $kategorie = trim($_POST["kategorie"] ?? "");
        
        // UPDATE-Befehl: Ändert die Daten des Buches mit der angegebenen Buchnummer
        $stmt = $conn->prepare("UPDATE buch SET isbn = ?, titel = ?, autor = ?, verlag = ?, beschreibung = ?, anschaffungspreis = ?, kategorie = ? WHERE buch_nr = ?");
        // "sssssdsi" = 5 Strings, 1 Decimal, 1 String, 1 Integer (für buch_nr)
        $stmt->bind_param("sssssdsi", $isbn, $titel, $autor, $verlag, $beschreibung, $anschaffungspreis, $kategorie, $buch_nr);
        $stmt->execute();
        $meldung = "Buch wurde aktualisiert.";
        $stmt->close();
    }
    // Aktion: Buch aus der Datenbank löschen
    else if ($action === "delete") {
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        // DELETE-Befehl: Löscht das Buch mit der angegebenen Buchnummer
        $stmt = $conn->prepare("DELETE FROM buch WHERE buch_nr = ?");
        // "i" = Integer (Ganzzahl) für die Buchnummer
        $stmt->bind_param("i", $buch_nr);
        $stmt->execute();
        $meldung = "Buch wurde gelöscht.";
        $stmt->close();
    }
}

// Suchfunktion: Suchbegriff aus der URL auslesen (GET = Daten werden in der URL übergeben)
$suche = trim($_GET["suche"] ?? "");

// SQL-Query: Alle Bücher aus der Datenbank abrufen
$sql = "SELECT buch_nr, isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie FROM buch";
if ($suche !== "") {
    // real_escape_string: Wandelt gefährliche Zeichen um (z.B. Apostrophe in "O'Reilly")
    // Dies verhindert SQL-Injection-Angriffe bei direkten SQL-Queries
    $suche_esc = $conn->real_escape_string($suche);
    // LIKE '%text%' bedeutet: Suche nach Texten, die den Suchbegriff enthalten
    $sql .= " WHERE titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%' OR beschreibung LIKE '%$suche_esc%'";
}
// Ergebnisse nach Buchnummer sortieren (aufsteigend: 1, 2, 3, ...)
$sql .= " ORDER BY buch_nr ASC";
// SQL-Befehl ausführen und Ergebnisse speichern
$ergebnis = $conn->query($sql);

// Liste der verfügbaren Kategorien für das Dropdown-Menü
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
