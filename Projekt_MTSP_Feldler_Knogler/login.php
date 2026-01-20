<?php
session_start();

// Wenn bereits angemeldet, weiterleiten
if (isset($_SESSION['bibliothekar_angemeldet']) && $_SESSION['bibliothekar_angemeldet'] === true) {
    header("Location: verwaltung.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$fehlermeldung = "";

// Login-Verarbeitung
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = isset($_POST["user"]) ? trim($_POST["user"]) : "";
    $pass = $_POST["pass"] ?? "";

    if ($user !== "" && $pass !== "") {
        $stmt = $conn->prepare("SELECT bibliothekar_id, benutzername, passwort FROM bibliothekar WHERE benutzername = ?");
        if ($stmt && $stmt->bind_param("s", $user) && $stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            // Passwort-Verifizierung
            if ($row && password_verify($pass, $row["passwort"])) {
                $_SESSION["bibliothekar_angemeldet"] = true;
                $_SESSION["bibliothekar_id"] = $row["bibliothekar_id"];
                $stmt->close();
                $conn->close();
                header("Location: verwaltung.php");
                exit;
            }
            $fehlermeldung = "Benutzername oder Passwort ist falsch.";
            $stmt->close();
        } else {
            $fehlermeldung = "Fehler bei der Datenbankabfrage.";
        }
    } else {
        $fehlermeldung = "Bitte geben Sie Benutzername und Passwort ein.";
    }
}

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

