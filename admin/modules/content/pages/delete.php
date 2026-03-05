<?php
/**
 * SUPPRESSION D'UNE PAGE
 * /admin/modules/pages/delete.php
 */

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la session admin
if (empty($_SESSION['user_id']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

// Récupérer l'ID de la page à supprimer
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if (!$pageId) {
    header('Location: /admin/modules/pages/index.php?error=no_id');
    exit;
}

// =====================================================
// CONNEXION DB
// =====================================================
$configPath = __DIR__ . '/../../../config/database.php';
$config = require $configPath;
$dbConfig = $config['production'] ?? $config['development'] ?? null;

if (!$dbConfig) {
    header('Location: /admin/modules/pages/index.php?error=config_missing');
    exit;
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s;port=%d',
        $dbConfig['host'],
        $dbConfig['dbname'],
        $dbConfig['charset'] ?? 'utf8mb4',
        $dbConfig['port'] ?? 3306
    );
    
    $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options'] ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("delete.php: Erreur connexion DB - " . $e->getMessage());
    header('Location: /admin/modules/pages/index.php?error=db_error');
    exit;
}

// =====================================================
// VÉRIFIER QUE LA PAGE EXISTE
// =====================================================
try {
    $stmt = $db->prepare("SELECT id, title, slug FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch();
    
    if (!$page) {
        header('Location: /admin/modules/pages/index.php?error=page_not_found');
        exit;
    }
    
    // Protection: ne pas supprimer la page d'accueil (slug = 'index' ou 'accueil')
    if (in_array($page['slug'], ['index', 'accueil', 'home'])) {
        header('Location: /admin/modules/pages/index.php?error=cannot_delete_homepage');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("delete.php: Erreur vérification - " . $e->getMessage());
    header('Location: /admin/modules/pages/index.php?error=db_error');
    exit;
}

// =====================================================
// SUPPRESSION
// =====================================================
try {
    $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    
    $deleted = $stmt->rowCount();
    
    if ($deleted > 0) {
        error_log("delete.php: Page ID=$pageId supprimée (titre: {$page['title']})");
        header('Location: /admin/modules/pages/index.php?success=deleted&title=' . urlencode($page['title']));
        exit;
    } else {
        header('Location: /admin/modules/pages/index.php?error=delete_failed');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("delete.php: Erreur suppression - " . $e->getMessage());
    header('Location: /admin/modules/pages/index.php?error=' . urlencode($e->getMessage()));
    exit;
}