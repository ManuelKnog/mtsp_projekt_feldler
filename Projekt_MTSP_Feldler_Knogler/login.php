<?php
session_start();
if (isset($_SESSION['bibliothekar_angemeldet']) && $_SESSION['bibliothekar_angemeldet'] === true) {
    header("Location: verwaltung.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "bibliothek_mtsp");
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$fehlermeldung = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = isset($_POST["user"]) ? trim($_POST["user"]) : "";
    $pass = $_POST["pass"] ?? "";

    if ($user !== "" && $pass !== "") {
        $stmt = $conn->prepare("SELECT bibliothekar_id, benutzername, passwort FROM bibliothekar WHERE benutzername = ?");
        if ($stmt && $stmt->bind_param("s", $user) && $stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && password_verify($pass, $row["passwort"])) {
                $_SESSION["bibliothekar_angemeldet"] = true;
                $_SESSION["bibliothekar_name"] = $row["benutzername"];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Bibliothek-Knogler</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Zurück zur Übersicht</a>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Anmeldung für Bibliothekare</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($fehlermeldung)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($fehlermeldung); ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="post">
                            <div class="mb-3">
                                <label for="user" class="form-label">Benutzername</label>
                                <input type="text" class="form-control" id="user" name="user" required>
                            </div>
                            <div class="mb-3">
                                <label for="pass" class="form-label">Passwort</label>
                                <input type="password" class="form-control" id="pass" name="pass" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

