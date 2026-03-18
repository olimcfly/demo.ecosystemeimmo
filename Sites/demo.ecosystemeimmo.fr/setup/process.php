<?php

$data = $_POST;

// 🔒 Nettoyage
function clean($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

$agent_name = clean($data['agent_name']);
$city       = clean($data['city']);
$email      = clean($data['email']);

$db_host = clean($data['db_host']);
$db_name = clean($data['db_name']);
$db_user = clean($data['db_user']);
$db_pass = $data['db_pass'];

// 🧪 1. Connexion DB
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (Exception $e) {
    die("❌ Erreur connexion DB : " . $e->getMessage());
}

// 📦 2. Import schema
$sql = file_get_contents(__DIR__ . '/schema.sql');

try {
    $pdo->exec($sql);
} catch (Exception $e) {
    die("❌ Erreur import SQL : " . $e->getMessage());
}

// 🧠 3. Seed advisor_context minimal
$firstname = explode(' ', $agent_name)[0];

$stmt = $pdo->prepare("
INSERT INTO advisor_context 
(section, field_key, field_label, field_value, field_type, sort_order)
VALUES 
('identite','advisor_name','Nom complet du conseiller', ?, 'text', 10),
('identite','advisor_firstname','Prénom', ?, 'text', 11),
('identite','advisor_city','Ville principale', ?, 'text', 30)
");

$stmt->execute([
    $agent_name,
    $firstname,
    $city
]);

// 👤 4. Création ADMIN
$admin_code = rand(100000, 999999);

$stmt = $pdo->prepare("
INSERT INTO users (email, login_code, role, created_at)
VALUES (?, ?, 'admin', NOW())
");

$stmt->execute([
    $email,
    password_hash($admin_code, PASSWORD_DEFAULT)
]);

// ⚙️ 5. Générer config.php
$config = "<?php
define('INSTANCE_ID', '" . strtolower(str_replace(' ', '-', $agent_name)) . "');
define('SITE_TITLE', '{$agent_name}');
define('SITE_DOMAIN', \$_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', '{$email}');

define('DB_HOST', '{$db_host}');
define('DB_NAME', '{$db_name}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');
define('DB_CHARSET', 'utf8mb4');

define('DEBUG_MODE', true);

function getDB() {
    static \$pdo = null;

    if (\$pdo !== null) return \$pdo;

    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException \$e) {
        die(\"Erreur DB : \" . \$e->getMessage());
    }

    return \$pdo;
}
";

file_put_contents(__DIR__ . '/../config/config.php', $config);

?>

<!DOCTYPE html>

<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation terminée</title>

<style>
body {
    margin: 0;
    font-family: 'Inter', Arial, sans-serif;
    background: linear-gradient(135deg, #2d6cdf, #6c8cff);
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.container {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

h1 {
    margin-top: 0;
    color: #2d6cdf;
}

p {
    color: #555;
    margin-bottom: 25px;
}

.actions {
    display: flex;
    gap: 15px;
    flex-direction: column;
}

a {
    text-decoration: none;
}

.btn {
    padding: 14px;
    border-radius: 8px;
    font-size: 15px;
    display: block;
}

.btn-primary {
    background: #2d6cdf;
    color: #fff;
}

.btn-secondary {
    background: #f1f3f7;
    color: #333;
}

.guide {
    margin-top: 25px;
    text-align: left;
    font-size: 14px;
    color: #666;
}

.guide li {
    margin-bottom: 8px;
}
</style>

</head>

<body>

<div class="container">

<h1>✅ Installation réussie</h1>

<p>Votre CRM est prêt.</p>

<p><strong>Email admin :</strong> <?php echo $email; ?></p>
<p><strong>Code temporaire :</strong> <?php echo $admin_code; ?></p>

<div class="actions">
    <a href="/" class="btn btn-primary">🌐 Voir mon site</a>
    <a href="/admin" class="btn btn-secondary">⚙️ Accéder à l’administration</a>
</div>

<div class="guide">
    <strong>Prochaines étapes :</strong>
    <ul>
        <li>Connectez-vous à l'admin</li>
        <li>Complétez votre profil</li>
        <li>Configurez votre stratégie</li>
    </ul>
</div>

</div>

</body>
</html>
