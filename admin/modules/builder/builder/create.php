<?php
/**
 * API : Créer un header ou footer
 * /admin/modules/design/api/create.php
 *
 * POST JSON : { table: "headers"|"footers", name: "...", type: "...", categories: [...] }
 * Retourne  : { success: true, id: 123 } ou { success: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');

// ─── Auth check ───
session_start();
if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id']) && empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// ─── Connexion DB ───
$connection = null;
$dbConfig = __DIR__ . '/../../../../config/database.php';
if (file_exists($dbConfig)) {
    require_once $dbConfig;
    if (isset($db) && $db instanceof PDO) $connection = $db;
    elseif (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
}
if (!$connection) {
    echo json_encode(['success' => false, 'error' => 'Connexion base de données impossible']);
    exit;
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── Lecture du body JSON ───
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$table = $input['table'] ?? '';
$name  = trim($input['name'] ?? '');
$type  = $input['type'] ?? 'standard';
$categories = $input['categories'] ?? [];

// ─── Validation ───
if (!in_array($table, ['headers', 'footers'])) {
    echo json_encode(['success' => false, 'error' => 'Table invalide']);
    exit;
}
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']);
    exit;
}
if (mb_strlen($name) > 255) {
    echo json_encode(['success' => false, 'error' => 'Nom trop long (max 255 caractères)']);
    exit;
}

// ─── Générer le slug ───
$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
// Vérifier unicité du slug
$checkStmt = $connection->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ?");
$checkStmt->execute([$slug]);
if ($checkStmt->fetchColumn() > 0) {
    $slug .= '-' . time();
}

// ─── Sérialiser les catégories ───
$categoriesJson = !empty($categories) ? json_encode($categories) : null;

// ─── Insertion ───
try {
    $stmt = $connection->prepare("
        INSERT INTO {$table} (name, slug, type, status, is_default, categories, content, builder_content, custom_html, created_at, updated_at)
        VALUES (?, ?, ?, 'draft', 0, ?, '', '', '', NOW(), NOW())
    ");
    $stmt->execute([$name, $slug, $type, $categoriesJson]);
    $newId = $connection->lastInsertId();

    echo json_encode([
        'success' => true,
        'id'      => (int)$newId,
        'slug'    => $slug,
        'message' => ucfirst(rtrim($table, 's')) . ' créé avec succès'
    ]);

} catch (PDOException $e) {
    // Si la colonne "categories" n'existe pas, réessayer sans
    if (strpos($e->getMessage(), 'categories') !== false) {
        try {
            $stmt = $connection->prepare("
                INSERT INTO {$table} (name, slug, type, status, is_default, content, builder_content, custom_html, created_at, updated_at)
                VALUES (?, ?, ?, 'draft', 0, '', '', '', NOW(), NOW())
            ");
            $stmt->execute([$name, $slug, $type]);
            $newId = $connection->lastInsertId();

            echo json_encode([
                'success' => true,
                'id'      => (int)$newId,
                'slug'    => $slug,
                'message' => ucfirst(rtrim($table, 's')) . ' créé avec succès (sans catégories)'
            ]);
        } catch (PDOException $e2) {
            echo json_encode(['success' => false, 'error' => 'Erreur SQL : ' . $e2->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur SQL : ' . $e->getMessage()]);
    }
}