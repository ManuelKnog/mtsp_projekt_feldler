<?php
session_start();

// Session löschen und zerstören
$_SESSION = [];
session_destroy();

header("Location: index.php");
exit;
