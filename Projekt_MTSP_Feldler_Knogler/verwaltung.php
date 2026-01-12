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
    
    switch ($action) {
        case "add":
        case "update":
            $daten = [
                "isbn" => trim($_POST["isbn"] ?? ""),
                "titel" => trim($_POST["titel"] ?? ""),
                "autor" => trim($_POST["autor"] ?? ""),
                "verlag" => trim($_POST["verlag"] ?? ""),
                "beschreibung" => trim($_POST["beschreibung"] ?? ""),
                "anschaffungspreis" => floatval($_POST["anschaffungspreis"] ?? 0),
                "kategorie" => trim($_POST["kategorie"] ?? "")
            ];
            
            if (empty($daten["isbn"]) || empty($daten["titel"]) || empty($daten["autor"]) || empty($daten["verlag"])) {
                $fehler = "Bitte alle Pflichtfelder ausfüllen (ISBN, Titel, Autor, Verlag).";
                break;
            }
            
            if ($action === "update") {
                $buch_nr = intval($_POST["buch_nr"] ?? 0);
                if ($buch_nr <= 0) {
                    $fehler = "Ungültige Buchnummer.";
                    break;
                }
                $sql = "UPDATE buch SET isbn = ?, titel = ?, autor = ?, verlag = ?, beschreibung = ?, anschaffungspreis = ?, kategorie = ? WHERE buch_nr = ?";
                $types = "ssssddsi";
                $params = [$daten["isbn"], $daten["titel"], $daten["autor"], $daten["verlag"], $daten["beschreibung"], $daten["anschaffungspreis"], $daten["kategorie"], $buch_nr];
                $erfolg = "Buch wurde aktualisiert.";
            } else {
                $sql = "INSERT INTO buch (isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $types = "ssssdds";
                $params = [$daten["isbn"], $daten["titel"], $daten["autor"], $daten["verlag"], $daten["beschreibung"], $daten["anschaffungspreis"], $daten["kategorie"]];
                $erfolg = "Buch wurde hinzugefügt.";
            }
            
            $stmt = $conn->prepare($sql);
            if ($stmt && $stmt->bind_param($types, ...$params) && $stmt->execute()) {
                $meldung = $erfolg;
            } else {
                $fehler = "Fehler: " . $conn->error;
            }
            if (isset($stmt)) $stmt->close();
            break;
            
        case "delete":
            $buch_nr = intval($_POST["buch_nr"] ?? 0);
            if ($buch_nr > 0) {
                $stmt = $conn->prepare("DELETE FROM buch WHERE buch_nr = ?");
                if ($stmt && $stmt->bind_param("i", $buch_nr) && $stmt->execute()) {
                    $meldung = "Buch wurde gelöscht.";
                } else {
                    $fehler = "Fehler beim Löschen: " . $conn->error;
                }
                $stmt->close();
            } else {
                $fehler = "Ungültige Buchnummer.";
            }
            break;
            
        case "add_user":
            $daten = [
                "benutzername" => trim($_POST["benutzername"] ?? ""),
                "passwort" => $_POST["passwort"] ?? "",
                "vorname" => trim($_POST["vorname"] ?? ""),
                "nachname" => trim($_POST["nachname"] ?? ""),
                "email" => trim($_POST["email"] ?? "")
            ];
            
            if (empty($daten["benutzername"]) || empty($daten["passwort"]) || empty($daten["vorname"]) || empty($daten["nachname"])) {
                $fehler = "Bitte alle Pflichtfelder ausfüllen (Benutzername, Passwort, Vorname, Nachname).";
                break;
            }
            
            $stmt = $conn->prepare("SELECT bibliothekar_id FROM bibliothekar WHERE benutzername = ?");
            if ($stmt && $stmt->bind_param("s", $daten["benutzername"]) && $stmt->execute() && $stmt->get_result()->num_rows > 0) {
                $fehler = "Dieser Benutzername existiert bereits.";
                $stmt->close();
                break;
            }
            if (isset($stmt)) $stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO bibliothekar (benutzername, passwort, vorname, nachname, email) VALUES (?, ?, ?, ?, ?)");
            $passwort_hash = password_hash($daten["passwort"], PASSWORD_DEFAULT);
            if ($stmt && $stmt->bind_param("sssss", $daten["benutzername"], $passwort_hash, $daten["vorname"], $daten["nachname"], $daten["email"]) && $stmt->execute()) {
                $meldung = "Benutzer wurde erfolgreich hinzugefügt.";
            } else {
                $fehler = "Fehler beim Hinzufügen des Benutzers: " . $conn->error;
            }
            $stmt->close();
            break;
    }
}

$suche = trim($_GET["suche"] ?? "");
$edit_buch = null;

if (isset($_GET["edit"]) && ($edit_id = intval($_GET["edit"])) > 0) {
    $stmt = $conn->prepare("SELECT buch_nr, isbn, titel, autor, verlag, beschreibung, anschaffungspreis, kategorie FROM buch WHERE buch_nr = ?");
    if ($stmt && $stmt->bind_param("i", $edit_id) && $stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) $edit_buch = $result->fetch_assoc();
    }
    $stmt->close();
}

$sql = "SELECT buch_nr, isbn, titel, autor, verlag, kategorie FROM buch";
if ($suche !== "") {
    $suche_esc = $conn->real_escape_string($suche);
    $sql .= " WHERE titel LIKE '%$suche_esc%' OR autor LIKE '%$suche_esc%' OR beschreibung LIKE '%$suche_esc%'";
}
$sql .= " ORDER BY buch_nr ASC";
$ergebnis = $conn->query($sql);

$kategorien = ["Mechatronik", "Informationstechnik", "Allgemeinbildung", "Elektrotechnik", "Maschinenbau"];

function buchFormular($prefix, $daten = []) {
    global $kategorien;
    $daten = array_merge(["isbn" => "", "titel" => "", "autor" => "", "verlag" => "", "beschreibung" => "", "anschaffungspreis" => "", "kategorie" => ""], $daten);
    echo '<div class="mb-2"><label for="'.$prefix.'_isbn" class="form-label small">ISBN *</label><input type="text" class="form-control form-control-sm" id="'.$prefix.'_isbn" name="isbn" required value="'.htmlspecialchars($daten["isbn"]).'"></div>';
    echo '<div class="mb-2"><label for="'.$prefix.'_titel" class="form-label small">Titel *</label><input type="text" class="form-control form-control-sm" id="'.$prefix.'_titel" name="titel" required value="'.htmlspecialchars($daten["titel"]).'"></div>';
    echo '<div class="mb-2"><label for="'.$prefix.'_autor" class="form-label small">Autor *</label><input type="text" class="form-control form-control-sm" id="'.$prefix.'_autor" name="autor" required value="'.htmlspecialchars($daten["autor"]).'"></div>';
    echo '<div class="mb-2"><label for="'.$prefix.'_verlag" class="form-label small">Verlag *</label><input type="text" class="form-control form-control-sm" id="'.$prefix.'_verlag" name="verlag" required value="'.htmlspecialchars($daten["verlag"]).'"></div>';
    echo '<div class="mb-2"><label for="'.$prefix.'_beschreibung" class="form-label small">Beschreibung</label><textarea class="form-control form-control-sm" id="'.$prefix.'_beschreibung" name="beschreibung" rows="2">'.htmlspecialchars($daten["beschreibung"]).'</textarea></div>';
    echo '<div class="mb-2"><label for="'.$prefix.'_anschaffungspreis" class="form-label small">Anschaffungspreis (€)</label><input type="number" step="0.01" class="form-control form-control-sm" id="'.$prefix.'_anschaffungspreis" name="anschaffungspreis" value="'.htmlspecialchars($daten["anschaffungspreis"]).'"></div>';
    echo '<div class="mb-3"><label for="'.$prefix.'_kategorie" class="form-label small">Kategorie</label><select class="form-select form-select-sm" id="'.$prefix.'_kategorie" name="kategorie"><option value="">Keine</option>';
    foreach ($kategorien as $kat) {
        $selected = ($daten["kategorie"] === $kat) ? "selected" : "";
        echo '<option value="'.htmlspecialchars($kat).'" '.$selected.'>'.htmlspecialchars($kat).'</option>';
    }
    echo '</select></div>';
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verwaltung – Bücher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="verwaltung.php">Bibliotheksverwaltung</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Öffentliche Übersicht</a>
                <a class="nav-link" href="ausleihen.php">Ausleihen</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <h2 class="mb-4">Bücher verwalten</h2>
        
        <?php if (!empty($meldung)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($meldung); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (!empty($fehler)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($fehler); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" action="verwaltung.php" class="row g-2">
                            <div class="col-md-10">
                                <label for="suche" class="form-label">Büchersuche (Titel, Autor oder Beschreibung)</label>
                                <input type="text" class="form-control" id="suche" name="suche" value="<?php echo htmlspecialchars($suche); ?>" placeholder="Suchbegriff eingeben...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Suchen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo !$edit_buch ? 'active' : ''; ?>" id="buch-tab" data-bs-toggle="tab" data-bs-target="#buch" type="button" role="tab">Neues Buch</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button" role="tab">Neuer Benutzer</button>
                            </li>
                            <?php if ($edit_buch): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">Bearbeiten</button>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade <?php echo !$edit_buch ? 'show active' : ''; ?>" id="buch" role="tabpanel">
                                <form method="post" action="verwaltung.php">
                                    <input type="hidden" name="action" value="add">
                                    <?php buchFormular("add"); ?>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Buch hinzufügen</button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="user" role="tabpanel">
                                <form method="post" action="verwaltung.php">
                                    <input type="hidden" name="action" value="add_user">
                                    <div class="mb-2"><label for="add_vorname" class="form-label small">Vorname *</label><input type="text" class="form-control form-control-sm" id="add_vorname" name="vorname" required></div>
                                    <div class="mb-2"><label for="add_nachname" class="form-label small">Nachname *</label><input type="text" class="form-control form-control-sm" id="add_nachname" name="nachname" required></div>
                                    <div class="mb-2"><label for="add_email" class="form-label small">Email</label><input type="email" class="form-control form-control-sm" id="add_email" name="email"></div>
                                    <div class="mb-2"><label for="add_benutzername" class="form-label small">Benutzername *</label><input type="text" class="form-control form-control-sm" id="add_benutzername" name="benutzername" required></div>
                                    <div class="mb-3"><label for="add_passwort" class="form-label small">Passwort *</label><input type="password" class="form-control form-control-sm" id="add_passwort" name="passwort" required></div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Benutzer hinzufügen</button>
                                </form>
                            </div>
                            <?php if ($edit_buch): ?>
                            <div class="tab-pane fade show active" id="edit" role="tabpanel">
                                <form method="post" action="verwaltung.php">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="buch_nr" value="<?php echo htmlspecialchars($edit_buch["buch_nr"]); ?>">
                                    <?php buchFormular("edit", $edit_buch); ?>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-sm">Speichern</button>
                                        <a href="verwaltung.php" class="btn btn-secondary btn-sm">Abbrechen</a>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Alle Bücher</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nr</th>
                                        <th>ISBN</th>
                                        <th>Titel</th>
                                        <th>Autor</th>
                                        <th>Verlag</th>
                                        <th>Kategorie</th>
                                        <th>Aktionen</th>
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
                                                <td>
                                                    <a href="verwaltung.php?edit=<?php echo $zeile['buch_nr']; ?>" class="btn btn-sm btn-warning">Bearbeiten</a>
                                                    <form method="post" action="verwaltung.php" style="display:inline;" onsubmit="return confirm('Buch wirklich löschen?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="buch_nr" value="<?php echo $zeile['buch_nr']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Keine Bücher vorhanden.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
