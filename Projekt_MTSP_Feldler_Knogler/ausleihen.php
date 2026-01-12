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
    } else if ($action === "add_kunde") {
        $vorname = trim($_POST["vorname"] ?? "");
        $nachname = trim($_POST["nachname"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $tel = trim($_POST["telefon"] ?? "");

        if ($vorname === "" || $nachname === "") {
            $fehler = "Bitte Vorname und Nachname eingeben.";
        } else {
            $stmt = $conn->prepare("INSERT INTO kunde (vorname, nachname, email, tel) VALUES (?, ?, ?, ?)");
            if ($stmt && $stmt->bind_param("ssss", $vorname, $nachname, $email, $tel) && $stmt->execute()) {
                $meldung = "Kunde wurde erfolgreich hinzugefügt.";
            } else {
                $fehler = "Fehler beim Hinzufügen: " . $conn->error;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="ausleihen.php">Ausleihen verwalten</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="verwaltung.php">Bücherverwaltung</a>
                <a class="nav-link" href="index.php">Öffentliche Übersicht</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <h2 class="mb-4">Ausleihen verwalten</h2>
        
        <?php if (!empty($meldung)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($meldung); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (!empty($fehler)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($fehler); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <section>
                    <h3>Neuen Kunden hinzufügen</h3>
                    <form method="post" action="ausleihen.php">
                        <input type="hidden" name="action" value="add_kunde">
                        <label for="kunde_vorname">Vorname *</label>
                        <input type="text" id="kunde_vorname" name="vorname" required>
                        <br>
                        <label for="kunde_nachname">Nachname *</label>
                        <input type="text" id="kunde_nachname" name="nachname" required>
                        <br>
                        <label for="kunde_email">Email</label>
                        <input type="email" id="kunde_email" name="email">
                        <br>
                        <label for="kunde_telefon">Telefon</label>
                        <input type="text" id="kunde_telefon" name="telefon">
                        <br>
                        <button type="submit">Kunde hinzufügen</button>
                    </form>
                </section>

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

            <div class="col-md-8">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

