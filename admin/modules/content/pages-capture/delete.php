<?php
// /admin/modules/capture-pages/delete.php
// Suppression d'une page

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: ?page=capture-pages');
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Supprimer la page
    $pdo->prepare("DELETE FROM captures WHERE id = :id")
        ->execute(['id' => $id]);
    
    // Rediriger avec message de succès
    header('Location: ?page=capture-pages&deleted=1');
    exit;
    
} catch (Exception $e) {
    error_log("Erreur suppression: " . $e->getMessage());
    header('Location: ?page=capture-pages&error=1');
    exit;
}
?>