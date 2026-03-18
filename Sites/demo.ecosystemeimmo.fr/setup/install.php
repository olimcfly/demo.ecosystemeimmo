<?php
// Bloquer si déjà installé
if (file_exists(__DIR__ . '/../config/config.php')) {
    die('⚠️ Application déjà installée.');
}
?>

<!DOCTYPE html>

<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation Écosystème Immo</title>

<style>
* {
    box-sizing: border-box;
    font-family: 'Inter', Arial, sans-serif;
}

body {
    margin: 0;
    background: linear-gradient(135deg, #2d6cdf, #6c8cff);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.container {
    background: #fff;
    padding: 40px;
    width: 100%;
    max-width: 520px;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

h1 {
    margin-top: 0;
    font-size: 22px;
}

h2 {
    font-size: 16px;
    margin-top: 25px;
    color: #555;
}

input {
    width: 100%;
    padding: 12px;
    margin-top: 8px;
    margin-bottom: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

input:focus {
    outline: none;
    border-color: #2d6cdf;
}

button {
    width: 100%;
    padding: 14px;
    background: #2d6cdf;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background: #1f4fbf;
}

.footer {
    text-align: center;
    font-size: 12px;
    margin-top: 15px;
    color: #999;
}
</style>

</head>

<body>

<div class="container">

<h1>🚀 Installation Écosystème Immo</h1>

<form method="POST" action="process.php">

<h2>👤 Informations agent</h2>

<input type="text" name="agent_name" placeholder="Nom complet (ex: Stéphanie Hulen)" required>

<input type="text" name="city" placeholder="Ville (ex: Lannion)" required>

<input type="email" name="email" placeholder="Email" required>

<h2>⚙️ Base de données</h2>

<input type="text" name="db_host" value="localhost">

<input type="text" name="db_name" placeholder="Nom de la base de données" required>

<input type="text" name="db_user" placeholder="Utilisateur DB" required>

<input type="password" name="db_pass" placeholder="Mot de passe DB">

<button type="submit">Installer le CRM</button>

</form>

<div class="footer">
Installation en 30 secondes ⚡
</div>

</div>

</body>
</html>

