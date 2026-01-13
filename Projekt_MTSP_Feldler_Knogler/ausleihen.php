<?php
session_start();
if (!isset($_SESSION['bibliothekar_angemeldet']) || $_SESSION['bibliothekar_angemeldet'] !== true) {
    header("Location: login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$meldung = "";
$fehler = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];

    if ($action === "ausleihen") {
        $kunden_nr = intval($_POST["kunde_id"] ?? 0);
        $buch_nr = intval($_POST["buch_nr"] ?? 0);

        if ($kunden_nr <= 0 || $buch_nr <= 0) {
            $fehler = "Bitte alle Felder ausfüllen.";
        } else {
            $stmt = $conn->prepare("INSERT INTO ausleihen (kunden_nr, buch_nr, bibliothekar_nr, datum) VALUES (?, ?, ?, ?)");
            $datum = date("Y-m-d");
            $bibliothekar_nr = $_SESSION["bibliothekar_id"];
            if ($stmt && $stmt->bind_param("iiis", $kunden_nr, $buch_nr, $bibliothekar_nr, $datum) && $stmt->execute()) {
                $meldung = "Buch wurde erfolgreich ausgeliehen.";
            } else {
                $fehler = "Fehler beim Ausleihen: " . $conn->error;
            }
            $stmt->close();
        }
    } else if ($action === "rueckgabe") {
        $ausleihen_nr = intval($_POST["ausleihe_id"] ?? 0);
        if ($ausleihen_nr <= 0) {
            $fehler = "Ungültige Ausleihe-ID.";
        } else {
            $stmt = $conn->prepare("DELETE FROM ausleihen WHERE ausleihen_nr = ?");
            if ($stmt && $stmt->bind_param("i", $ausleihen_nr) && $stmt->execute()) {
                $meldung = "Buch wurde erfolgreich zurückgegeben.";
            } else {
                $fehler = "Fehler bei der Rückgabe: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

$check_ausleihen = $conn->query("SHOW TABLES LIKE 'ausleihen'");
$check_kunde = $conn->query("SHOW TABLES LIKE 'kunde'");
$tabellen_existieren = ($check_ausleihen && $check_ausleihen->num_rows > 0) && ($check_kunde && $check_kunde->num_rows > 0);

if (!$tabellen_existieren) {
    $fehler = "Die Datenbank-Tabellen wurden noch nicht erstellt.";
    $ausleihen = $kunden = false;
} else {
    $ausleihen = $conn->query("SELECT a.ausleihen_nr, a.datum, k.kunden_nr, k.vorname, k.nachname, b.buch_nr, b.titel, b.autor FROM ausleihen a LEFT JOIN kunde k ON a.kunden_nr = k.kunden_nr LEFT JOIN buch b ON a.buch_nr = b.buch_nr ORDER BY a.datum DESC");
    $kunden = $conn->query("SELECT kunden_nr, vorname, nachname FROM kunde ORDER BY nachname, vorname");
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ausleihen verwalten</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="header-bar">
        <h1><a href="ausleihen.php" style="text-decoration: none; color: inherit;">Ausleihen verwalten</a></h1>
        <div class="header-actions">
            <a href="verwaltung.php">Bücherverwaltung</a>
            <a href="index.php">Öffentliche Übersicht</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <h2>Ausleihen verwalten</h2>
        
        <?php if (!empty($meldung)): ?>
            <p style="color:green"><?php echo htmlspecialchars($meldung); ?></p>
        <?php endif; ?>
        <?php if (!empty($fehler)): ?>
            <p style="color:red"><?php echo htmlspecialchars($fehler); ?></p>
        <?php endif; ?>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <section>
                    <h3>Buch ausleihen</h3>
                    <form method="post" action="ausleihen.php">
                        <input type="hidden" name="action" value="ausleihen">
                        <label for="ausleihe_kunde">Kunde *</label>
                        <select id="ausleihe_kunde" name="kunde_id" required>
                            <option value="">Kunde auswählen</option>
                            <?php if ($kunden && $kunden->num_rows > 0): ?>
                                <?php while ($k = $kunden->fetch_assoc()): ?>
                                    <option value="<?php echo $k["kunden_nr"]; ?>">
                                        <?php echo htmlspecialchars($k["vorname"] . " " . $k["nachname"]); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <br>
                        <label for="ausleihe_buch">Buch *</label>
                        <select id="ausleihe_buch" name="buch_nr" required>
                            <option value="">Buch auswählen</option>
                            <?php 
                            $buecher = $conn->query("SELECT buch_nr, titel, autor FROM buch ORDER BY titel");
                            if ($buecher && $buecher->num_rows > 0): ?>
                                <?php while ($b = $buecher->fetch_assoc()): ?>
                                    <option value="<?php echo $b["buch_nr"]; ?>">
                                        <?php echo htmlspecialchars($b["titel"] . " (" . $b["autor"] . ")"); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <br>
                        <button type="submit">Ausleihen</button>
                    </form>
                </section>
            </div>

            <div style="flex: 2; min-width: 400px;">
                <section>
                    <h3>Alle Ausleihen</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Kunde</th>
                            <th>Buch</th>
                            <th>Datum</th>
                            <th>Aktionen</th>
                        </tr>
                        <?php if ($ausleihen && $ausleihen->num_rows > 0): ?>
                            <?php while ($aus = $ausleihen->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $aus["ausleihen_nr"]; ?></td>
                                    <td><?php echo htmlspecialchars($aus["vorname"] . " " . $aus["nachname"]); ?></td>
                                    <td><?php echo htmlspecialchars($aus["titel"]); ?></td>
                                    <td><?php echo date("d.m.Y", strtotime($aus["datum"])); ?></td>
                                    <td>
                                        <form method="post" action="ausleihen.php" style="display:inline;" onsubmit="return confirm('Buch wirklich zurückgeben?');">
                                            <input type="hidden" name="action" value="rueckgabe">
                                            <input type="hidden" name="ausleihe_id" value="<?php echo $aus["ausleihen_nr"]; ?>">
                                            <button type="submit">Zurückgeben</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Keine Ausleihen gefunden.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </section>
            </div>
        </div>
    </main>

</body>
</html>

