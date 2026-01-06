<?php
session_start();

if (!isset($_SESSION['bibliothekar_angemeldet']) || $_SESSION['bibliothekar_angemeldet'] !== true) {
    header("Location: login.php");
    exit;
}

$dbName = "mtsp_übung";
$conn = new mysqli("localhost", "root", "", $dbName);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$meldung = "";
$fehler = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];

    if ($action === "add") {
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");

        if ($isbn === "" || $titel === "" || $autor === "" || $verlag === "") {
            $fehler = "Bitte alle Felder zum Hinzufügen ausfüllen.";
        } else {
            $stmt = $conn->prepare("INSERT INTO buch (isbn, titel, autor, verlag) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $isbn, $titel, $autor, $verlag);
                if ($stmt->execute()) {
                    $meldung = "Buch wurde hinzugefügt.";
                } else {
                    $fehler = "Fehler beim Hinzufügen: " . $conn->error;
                }
                $stmt->close();
            } else {
                $fehler = "Fehler beim Vorbereiten der Anfrage.";
            }
        }
    } else if ($action === "update") {
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        $isbn = trim($_POST["isbn"] ?? "");
        $titel = trim($_POST["titel"] ?? "");
        $autor = trim($_POST["autor"] ?? "");
        $verlag = trim($_POST["verlag"] ?? "");

        if ($buch_nr <= 0 || $isbn === "" || $titel === "" || $autor === "" || $verlag === "") {
            $fehler = "Bitte alle Felder zum Bearbeiten ausfüllen.";
        } else {
            $stmt = $conn->prepare("UPDATE buch SET isbn = ?, titel = ?, autor = ?, verlag = ? WHERE buch_nr = ?");
            if ($stmt) {
                $stmt->bind_param("ssssi", $isbn, $titel, $autor, $verlag, $buch_nr);
                if ($stmt->execute()) {
                    $meldung = "Buch wurde aktualisiert.";
                } else {
                    $fehler = "Fehler beim Aktualisieren: " . $conn->error;
                }
                $stmt->close();
            } else {
                $fehler = "Fehler beim Vorbereiten der Anfrage.";
            }
        }
    } else if ($action === "delete") {
        $buch_nr = intval($_POST["buch_nr"] ?? 0);
        if ($buch_nr <= 0) {
            $fehler = "Ungültige Buchnummer zum Löschen.";
        } else {
            $stmt = $conn->prepare("DELETE FROM buch WHERE buch_nr = ?");
            if ($stmt) {
                $stmt->bind_param("i", $buch_nr);
                if ($stmt->execute()) {
                    $meldung = "Buch wurde gelöscht.";
                } else {
                    $fehler = "Fehler beim Löschen: " . $conn->error;
                }
                $stmt->close();
            } else {
                $fehler = "Fehler beim Vorbereiten der Anfrage.";
            }
        }
    }
}

// Suchbegriff für die Verwaltungsansicht
$suche = isset($_GET["suche"]) ? $_GET["suche"] : "";

$edit_buch = null;

if (isset($_GET["edit"])) {
    $edit_id = intval($_GET["edit"]);
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT buch_nr, isbn, titel, autor, verlag FROM buch WHERE buch_nr = ?");
        if ($stmt) {
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $edit_buch = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
}

// Basis-SQL für die Buchliste
$sql = "SELECT buch_nr, isbn, titel, autor, verlag FROM buch";

if ($suche !== "") {
    $suche_esc = $conn->real_escape_string($suche);
    $sql .= " WHERE titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%'";
}

$sql .= " ORDER BY buch_nr ASC";

$ergebnis = $conn->query($sql);
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
    <header class="header-bar">
        <h1>Bibliotheksverwaltung</h1>
        <div class="header-actions">
            <a href="index.php">Zur öffentlichen Übersicht</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main>
        <h2>Bücher verwalten</h2>
        <?php if (!empty($meldung)): ?>
            <p style="color:green;"><?php echo htmlspecialchars($meldung); ?></p>
        <?php endif; ?>
        <?php if (!empty($fehler)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($fehler); ?></p>
        <?php endif; ?>

        <section>
            <h3>Büchersuche</h3>
            <form method="get" action="verwaltung.php">
                <label for="suche">Titel oder Autor</label>
                <input type="text" id="suche" name="suche" value="<?php echo htmlspecialchars($suche); ?>">
                <button type="submit">Suchen</button>
            </form>
        </section>

        <section>
            <h3>Neues Buch hinzufügen</h3>
            <form method="post" action="verwaltung.php">
                <input type="hidden" name="action" value="add">
                <label for="add_isbn">ISBN</label>
                <input type="text" id="add_isbn" name="isbn" required>
                <br>
                <label for="add_titel">Titel</label>
                <input type="text" id="add_titel" name="titel" required>
                <br>
                <label for="add_autor">Autor</label>
                <input type="text" id="add_autor" name="autor" required>
                <br>
                <label for="add_verlag">Verlag</label>
                <input type="text" id="add_verlag" name="verlag" required>
                <br>
                <button type="submit">Hinzufügen</button>
            </form>
        </section>

        <?php if ($edit_buch): ?>
        <section>
            <h3>Buch bearbeiten (Nr. <?php echo htmlspecialchars($edit_buch["buch_nr"]); ?>)</h3>
            <form method="post" action="verwaltung.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="buch_nr" value="<?php echo htmlspecialchars($edit_buch["buch_nr"]); ?>">
                <label for="edit_isbn">ISBN</label>
                <input type="text" id="edit_isbn" name="isbn" required value="<?php echo htmlspecialchars($edit_buch["isbn"]); ?>">
                <br>
                <label for="edit_titel">Titel</label>
                <input type="text" id="edit_titel" name="titel" required value="<?php echo htmlspecialchars($edit_buch["titel"]); ?>">
                <br>
                <label for="edit_autor">Autor</label>
                <input type="text" id="edit_autor" name="autor" required value="<?php echo htmlspecialchars($edit_buch["autor"]); ?>">
                <br>
                <label for="edit_verlag">Verlag</label>
                <input type="text" id="edit_verlag" name="verlag" required value="<?php echo htmlspecialchars($edit_buch["verlag"]); ?>">
                <br>
                <button type="submit">Speichern</button>
                <a href="verwaltung.php">Abbrechen</a>
            </form>
        </section>
        <?php endif; ?>

        <section>
            <h3>Alle Bücher</h3>
            <table>
                <tr>
                    <th>Nr</th>
                    <th>ISBN</th>
                    <th>Titel</th>
                    <th>Autor</th>
                    <th>Verlag</th>
                    <th>Aktionen</th>
                </tr>
                <?php if ($ergebnis && $ergebnis->num_rows > 0): ?>
                    <?php while ($zeile = $ergebnis->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $zeile["buch_nr"]; ?></td>
                            <td><?php echo htmlspecialchars($zeile["isbn"]); ?></td>
                            <td><?php echo htmlspecialchars($zeile["titel"]); ?></td>
                            <td><?php echo htmlspecialchars($zeile["autor"]); ?></td>
                            <td><?php echo htmlspecialchars($zeile["verlag"]); ?></td>
                            <td>
                                <a href="verwaltung.php?edit=<?php echo $zeile['buch_nr']; ?>">Bearbeiten</a>
                                <form method="post" action="verwaltung.php" style="display:inline;" onsubmit="return confirm('Buch wirklich löschen?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="buch_nr" value="<?php echo $zeile['buch_nr']; ?>">
                                    <button type="submit">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Keine Bücher vorhanden.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </section>
    </main>
</body>
</html>

