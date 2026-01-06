<?php
session_start();

if (isset($_SESSION['bibliothekar_angemeldet']) && $_SESSION['bibliothekar_angemeldet'] === true) {
    header("Location: verwaltung.php");
    exit;
}

$fehlermeldung = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = isset($_POST["user"]) ? trim($_POST["user"]) : "";
    $pass = $_POST["pass"] ?? "";

    $korrekter_user = "admin";
    $korrektes_passwort = "admin";

    if ($user === $korrekter_user && $pass === $korrektes_passwort) {
        $_SESSION["bibliothekar_angemeldet"] = true;
        $_SESSION["bibliothekar_name"] = $user;
        header("Location: verwaltung.php");
        exit;
    } else {
        $fehlermeldung = "Benutzername oder Passwort ist falsch.";
    }
}
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
    <header class="header-bar">
        <h1>Bibliothek-Knogler</h1>
        <div class="header-actions">
            <a href="index.php">Zurück zur Übersicht</a>
        </div>
    </header>

    <main>
        <h2>Anmeldung für Bibliothekare</h2>

        <?php if (!empty($fehlermeldung)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($fehlermeldung); ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="user">Benutzername</label>
            <input type="text" id="user" name="user" required>
            <br>
            <label for="pass">Passwort</label>
            <input type="password" id="pass" name="pass" required>
            <br>
            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>

