<?php
// Definisci la password fissa
$fixedPassword = 'laMiaPasswordSicura';

// Inizializza una variabile per tracciare se la password è stata verificata
$passwordVerified = false;

// Controlla se la richiesta POST è stata inviata
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Controlla se la password inserita corrisponde alla password fissa
    if (isset($_POST['password']) && $_POST['password'] === $fixedPassword) {
        $passwordVerified = true;
    } else {
        echo "<p>Password incorretta. Per favore, riprova.</p>";
    }
}

// Se la password è verificata, mostra le informazioni di PHP
if ($passwordVerified) {
    phpinfo();
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Protezione phpinfo()</title>
</head>
<body>
<form action="" method="post">
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Accedi</button>
</form>
</body>
</html>
