<?php
define('INSTANCE_ID', 'olivier-colas');
define('SITE_TITLE', 'Olivier Colas');
define('SITE_DOMAIN', $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'oliviercolas83@gmail.com');

define('DB_HOST', 'localhost');
define('DB_NAME', 'tasq5564_ecosystemeimmo_demo');
define('DB_USER', 'tasq5564_ei_demo_user');
define('DB_PASS', '0785611700Fd!');
define('DB_CHARSET', 'utf8mb4');

define('DEBUG_MODE', true);

function getDB() {
    static $pdo = null;

    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die("Erreur DB : " . $e->getMessage());
    }

    return $pdo;
}
