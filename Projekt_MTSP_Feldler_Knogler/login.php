<?php
// Session starten - speichert Informationen über den angemeldeten Benutzer
session_start();

// Wenn der Benutzer bereits angemeldet ist, direkt zur Verwaltungsseite weiterleiten
// (Man sollte nicht nochmal einloggen müssen, wenn man schon eingeloggt ist)
if (isset($_SESSION['bibliothekar_angemeldet']) && $_SESSION['bibliothekar_angemeldet'] === true) {
    header("Location: verwaltung.php");
    exit;
}

// Verbindung zur MySQL-Datenbank herstellen
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
// UTF-8 Zeichensatz setzen, damit Umlaute korrekt angezeigt werden
$conn->set_charset("utf8mb4");

// Variable für Fehlermeldungen
$fehlermeldung = "";

// Prüfen ob das Login-Formular abgeschickt wurde (POST = Daten werden im Hintergrund gesendet)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Benutzername und Passwort aus dem Formular auslesen
    // trim() entfernt Leerzeichen am Anfang und Ende
    $user = isset($_POST["user"]) ? trim($_POST["user"]) : "";
    $pass = $_POST["pass"] ?? "";

    // Prüfen ob beide Felder ausgefüllt wurden
    if ($user !== "" && $pass !== "") {
        // Prepared Statement: Bibliothekar in der Datenbank suchen
        // Die Fragezeichen (?) werden später durch die tatsächlichen Werte ersetzt (Sicherheit!)
        $stmt = $conn->prepare("SELECT bibliothekar_id, benutzername, passwort FROM bibliothekar WHERE benutzername = ?");
        // "s" = String (Text) für den Benutzernamen
        if ($stmt && $stmt->bind_param("s", $user) && $stmt->execute()) {
            // Ergebnis aus der Datenbank abrufen
            $row = $stmt->get_result()->fetch_assoc();
            // Passwort-Verifizierung: password_verify() vergleicht das eingegebene Passwort
            // mit dem verschlüsselten Passwort in der Datenbank (Passwörter werden nie im Klartext gespeichert!)
            if ($row && password_verify($pass, $row["passwort"])) {
                // Login erfolgreich: Session-Variablen setzen
                // Diese Variablen bleiben erhalten, solange der Benutzer angemeldet ist
                $_SESSION["bibliothekar_angemeldet"] = true;
                $_SESSION["bibliothekar_id"] = $row["bibliothekar_id"];
                $stmt->close();
                $conn->close();
                // Zur Verwaltungsseite weiterleiten
                header("Location: verwaltung.php");
                exit;
            }
            // Wenn Passwort falsch ist, Fehlermeldung anzeigen
            $fehlermeldung = "Benutzername oder Passwort ist falsch.";
            $stmt->close();
        } else {
            // Wenn Datenbankfehler auftritt, Fehlermeldung anzeigen
            $fehlermeldung = "Fehler bei der Datenbankabfrage.";
        }
    } else {
        // Wenn Felder leer sind, Fehlermeldung anzeigen
        $fehlermeldung = "Bitte geben Sie Benutzername und Passwort ein.";
    }
}

// Datenbankverbindung schließen
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung Bibliothekar</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="header-bar">
        <h1><a href="index.php" style="text-decoration: none; color: inherit;">Bibliothek-Knogler</a></h1>
        <div class="header-actions">
            <a href="index.php">Zurück zur Übersicht</a>
        </div>
    </nav>

    <main>
        <section style="max-width: 400px; margin: 50px auto;">
            <h3>Anmeldung für Bibliothekare</h3>
            <?php if (!empty($fehlermeldung)): ?>
                <p style="color:red"><?php echo htmlspecialchars($fehlermeldung); ?></p>
            <?php endif; ?>

            <form action="login.php" method="post">
                <label for="user">Benutzername</label>
                <input type="text" id="user" name="user" required>
                <label for="pass">Passwort</label>
                <input type="password" id="pass" name="pass" required>
                <button type="submit">Login</button>
            </form>
        </section>
    </main>

</body>
</html>

