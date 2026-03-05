<?php
/**
 * ══════════════════════════════════════════════════════════════
 * API Pages — IMMO LOCAL+ v8.1
 * /admin/api/content/pages.php
 * ══════════════════════════════════════════════════════════════
 *
 * CHANGELOG v8.1 :
 *  ✅ Ajout bulk actions : set_public / set_private
 *  ✅ Support colonnes : visibility, google_indexed, semantic_score
 *  ✅ Auto-add colonnes manquantes au démarrage
 *  ✅ Stats enrichies (avg_semantic, indexed_count, public/private)
 *
 * Endpoints (POST JSON ou FormData):
 *   list           → Liste paginée + stats
 *   get            → Détail page par ID
 *   create         → Créer une page
 *   update         → Modifier une page
 *   delete         → Supprimer une page
 *   toggle_status  → Changer statut (draft/published/archived)
 *   duplicate      → Dupliquer une page
 *   check_slug     → Vérifier disponibilité slug (GET)
 *   autosave       → Sauvegarde automatique
 *   bulk_action    → Actions groupées (publish/draft/archive/delete/set_public/set_private)
 *   reorder        → Modifier l'ordre
 *   upload_image   → Upload image pour l'éditeur
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Connexion DB via init.php existant ───
// Depuis /admin/api/content/pages.php → remonter 2 niveaux → /admin/includes/init.php
$initPath = dirname(dirname(__DIR__)) . '/includes/init.php';
if (file_exists($initPath)) {
    require_once $initPath;
}

// Normaliser la variable DB → $pdo
if (!isset($pdo)) {
    if (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } elseif (class_exists('Database')) {
        try {
            $pdo = Database::getInstance();
        } catch (Exception $e) {}
    }
}

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connexion DB impossible']);
    exit;
}

// ─── Détecter la table pages ───
$tableName = 'pages';
$tableFound = false;
foreach (['pages', 'cms_pages'] as $candidate) {
    try {
        $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
        $tableName = $candidate;
        $tableFound = true;
        break;
    } catch (PDOException $e) { continue; }
}

if (!$tableFound) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Table pages introuvable"]);
    exit;
}

// ─── Colonnes disponibles ───
$existingCols = [];
try {
    $existingCols = array_map('strtolower', $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {}

// ─── Ajouter colonnes manquantes si nécessaire ───
$neededCols = [
    'html_content'     => "LONGTEXT AFTER `content`",
    'custom_css'       => "TEXT",
    'custom_js'        => "TEXT",
    'meta_title'       => "VARCHAR(160)",
    'meta_description' => "VARCHAR(320)",
    'meta_keywords'    => "VARCHAR(255)",
    'og_image'         => "VARCHAR(500)",
    'is_file_based'    => "TINYINT(1) DEFAULT 0",
    'file_path'        => "VARCHAR(255)",
    'template'         => "VARCHAR(100) DEFAULT 'default'",
    'parent_id'        => "INT DEFAULT NULL",
    'sort_order'       => "INT DEFAULT 0",
    'seo_score'        => "INT DEFAULT 0",
    'semantic_score'   => "INT DEFAULT 0",
    'word_count'       => "INT DEFAULT 0",
    'published_at'     => "DATETIME DEFAULT NULL",
    'focus_keyword'    => "VARCHAR(255)",
    'content_type'     => "VARCHAR(50) DEFAULT 'page'",
    'visibility'       => "ENUM('public','private') DEFAULT 'public'",
    'google_indexed'   => "ENUM('yes','no','pending','unknown') DEFAULT 'unknown'",
];
foreach ($neededCols as $col => $def) {
    if (!in_array($col, $existingCols)) {
        try {
            $pdo->exec("ALTER TABLE `{$tableName}` ADD COLUMN `{$col}` {$def}");
            $existingCols[] = $col;
        } catch (PDOException $e) { /* already exists or other issue */ }
    }
}

// ─── Fonctions utilitaires ───

function apiSlug($title) {
    $slug = mb_strtolower($title, 'UTF-8');
    $tr = ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
           'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u',
           'ÿ'=>'y','ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae',"'"=>'-','–'=>'-','—'=>'-'];
    $slug = strtr($slug, $tr);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

function apiUniqueSlug($pdo, $table, $slug, $excludeId = null) {
    $base = $slug;
    $n = 1;
    while (true) {
        $sql = "SELECT id FROM `{$table}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $n++;
        if ($n > 50) return $base . '-' . bin2hex(random_bytes(3));
    }
}

function apiWordCount($html) {
    $text = strip_tags($html ?? '');
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text ? str_word_count($text, 0, 'àâäéèêëîïôöùûüÿçñ') : 0;
}

function apiSeoScore($d) {
    $s = 0;
    $tl = mb_strlen($d['title'] ?? '');
    if ($tl >= 30 && $tl <= 60) $s += 20; elseif ($tl >= 20) $s += 10; elseif ($tl > 0) $s += 5;
    $ml = mb_strlen($d['meta_title'] ?? '');
    if ($ml >= 50 && $ml <= 60) $s += 15; elseif ($ml >= 30) $s += 10; elseif ($ml > 0) $s += 5;
    $dl = mb_strlen($d['meta_description'] ?? '');
    if ($dl >= 150 && $dl <= 160) $s += 15; elseif ($dl >= 100) $s += 10; elseif ($dl > 0) $s += 5;
    $content = $d['content'] ?? $d['html_content'] ?? '';
    $wc = apiWordCount($content);
    if ($wc >= 800) $s += 25; elseif ($wc >= 500) $s += 20; elseif ($wc >= 300) $s += 15; elseif ($wc >= 100) $s += 8; elseif ($wc > 0) $s += 3;
    if (preg_match('/<h2/i', $content)) $s += 5;
    if (preg_match('/<h3/i', $content)) $s += 5;
    if (preg_match('/<a\s/i', $content)) $s += 5;
    if (preg_match('/<img/i', $content)) $s += 5;
    $sl = mb_strlen($d['slug'] ?? '');
    if ($sl >= 3 && $sl <= 50) $s += 5; elseif ($sl > 0) $s += 2;
    return min($s, 100);
}

function apiResponse($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiSafeInsert($pdo, $table, $data, $existingCols) {
    $cols = []; $vals = []; $phs = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols)) {
            $cols[] = "`{$col}`";
            $vals[] = $val;
            $phs[] = '?';
        }
    }
    if (empty($cols)) return false;
    $sql = "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $phs) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    return (int)$pdo->lastInsertId();
}

function apiSafeUpdate($pdo, $table, $id, $data, $existingCols) {
    $sets = []; $vals = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols) && strtolower($col) !== 'id') {
            $sets[] = "`{$col}` = ?";
            $vals[] = $val;
        }
    }
    if (empty($sets)) return false;
    $vals[] = $id;
    $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($vals);
}

// ═══════════════════════════════════════════════════════════
// ROUTING
// ═══════════════════════════════════════════════════════════

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Support JSON body
if ($method === 'POST' && empty($_POST['action'])) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if ($json && is_array($json)) {
        $_POST = array_merge($_POST, $json);
        if (!$action) $action = $json['action'] ?? '';
    }
}

try {

switch ($action) {

    // ═══════════════════════════════════════════
    // LIST
    // ═══════════════════════════════════════════
    case 'list':
        $status  = $_GET['status'] ?? 'all';
        $search  = $_GET['search'] ?? '';
        $pg      = max(1, (int)($_GET['pg'] ?? $_GET['page_num'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
        $orderBy = $_GET['order_by'] ?? 'updated_at';
        $orderDir = strtoupper($_GET['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $allowed = ['title','slug','status','seo_score','updated_at','created_at','sort_order','word_count'];
        if (!in_array($orderBy, $allowed)) $orderBy = 'updated_at';

        $where = []; $params = [];
        if ($status !== 'all' && in_array($status, ['draft','published','archived'])) {
            $where[] = "status = ?"; $params[] = $status;
        }
        if ($search) {
            $where[] = "(title LIKE ? OR slug LIKE ? OR meta_title LIKE ?)";
            $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
        }
        $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` {$wClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Data
        $offset = ($pg - 1) * $perPage;
        $selectCols = ['id','title','slug','status','created_at','updated_at'];
        foreach (['seo_score','semantic_score','is_file_based','file_path','template','published_at','word_count','meta_title','meta_description','content_type','visibility','google_indexed'] as $oc) {
            if (in_array($oc, $existingCols)) $selectCols[] = $oc;
        }
        $selStr = implode(',', array_map(fn($c) => "`{$c}`", $selectCols));

        $sql = "SELECT {$selStr} FROM `{$tableName}` {$wClause} ORDER BY `{$orderBy}` {$orderDir} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats enrichies
        $seoAvg      = in_array('seo_score', $existingCols) ? 'ROUND(AVG(NULLIF(seo_score,0)),0)' : '0';
        $semanticAvg = in_array('semantic_score', $existingCols) ? ', ROUND(AVG(NULLIF(semantic_score,0)),0) as avg_semantic' : '';
        $indexedCnt  = in_array('google_indexed', $existingCols) ? ", SUM(google_indexed='yes') as indexed_count" : '';
        $visCounts   = in_array('visibility', $existingCols) ? ", SUM(visibility='public') as public_count, SUM(visibility='private') as private_count" : '';

        $stats = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(status='published') as published,
            SUM(status='draft') as drafts,
            SUM(status='archived') as archived,
            {$seoAvg} as avg_seo
            {$semanticAvg}
            {$indexedCnt}
            {$visCounts}
            FROM `{$tableName}`")->fetch(PDO::FETCH_ASSOC);

        apiResponse(true, [
            'pages' => $pages,
            'pagination' => ['total'=>$total,'page'=>$pg,'per_page'=>$perPage,'total_pages'=>ceil($total/$perPage)],
            'stats' => $stats,
            'table' => $tableName
        ]);
        break;

    // ═══════════════════════════════════════════
    // GET
    // ═══════════════════════════════════════════
    case 'get':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) apiResponse(false, ['error' => 'ID invalide'], 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$page) apiResponse(false, ['error' => 'Page introuvable'], 404);

        apiResponse(true, ['page' => $page]);
        break;

    // ═══════════════════════════════════════════
    // CREATE
    // ═══════════════════════════════════════════
    case 'create':
        $title = trim(strip_tags($_POST['title'] ?? ''));
        if (!$title) apiResponse(false, ['error' => 'Titre obligatoire'], 400);

        $slug = $_POST['slug'] ?? '';
        $slug = $slug ? apiSlug($slug) : apiSlug($title);
        $slug = apiUniqueSlug($pdo, $tableName, $slug);

        $content   = $_POST['content'] ?? $_POST['html_content'] ?? '';
        $status    = in_array($_POST['status'] ?? '', ['draft','published','archived']) ? $_POST['status'] : 'draft';
        $wordCount = apiWordCount($content);
        $seoScore  = apiSeoScore(['title'=>$title,'slug'=>$slug,'content'=>$content,
                                  'meta_title'=>$_POST['meta_title']??'','meta_description'=>$_POST['meta_description']??'']);
        $pubAt     = $status === 'published' ? date('Y-m-d H:i:s') : null;

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'html_content'     => $content,
            'custom_css'       => $_POST['custom_css'] ?? '',
            'custom_js'        => $_POST['custom_js'] ?? '',
            'meta_title'       => trim(strip_tags($_POST['meta_title'] ?? '')),
            'meta_description' => trim(strip_tags($_POST['meta_description'] ?? '')),
            'meta_keywords'    => trim(strip_tags($_POST['meta_keywords'] ?? '')),
            'og_image'         => trim(strip_tags($_POST['og_image'] ?? '')),
            'focus_keyword'    => trim(strip_tags($_POST['focus_keyword'] ?? '')),
            'content_type'     => trim(strip_tags($_POST['content_type'] ?? 'page')),
            'status'           => $status,
            'visibility'       => in_array($_POST['visibility'] ?? '', ['public','private']) ? $_POST['visibility'] : 'public',
            'template'         => trim(strip_tags($_POST['template'] ?? 'default')),
            'is_file_based'    => (int)($_POST['is_file_based'] ?? 0),
            'file_path'        => trim(strip_tags($_POST['file_path'] ?? '')),
            'parent_id'        => ($_POST['parent_id'] ?? null) ? (int)$_POST['parent_id'] : null,
            'sort_order'       => (int)($_POST['sort_order'] ?? 0),
            'seo_score'        => $seoScore,
            'word_count'       => $wordCount,
            'published_at'     => $pubAt,
        ];

        $newId = apiSafeInsert($pdo, $tableName, $data, $existingCols);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$newId]);
        apiResponse(true, ['message' => 'Page créée', 'page' => $stmt->fetch(PDO::FETCH_ASSOC)], 201);
        break;

    // ═══════════════════════════════════════════
    // UPDATE
    // ═══════════════════════════════════════════
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) apiResponse(false, ['error' => 'ID invalide'], 400);

        $check = $pdo->prepare("SELECT id, status FROM `{$tableName}` WHERE id = ?");
        $check->execute([$id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if (!$existing) apiResponse(false, ['error' => 'Page introuvable'], 404);

        $title = trim(strip_tags($_POST['title'] ?? ''));
        if (!$title) apiResponse(false, ['error' => 'Titre obligatoire'], 400);

        $slug = $_POST['slug'] ?? '';
        $slug = $slug ? apiSlug($slug) : apiSlug($title);
        $slug = apiUniqueSlug($pdo, $tableName, $slug, $id);

        $content = $_POST['content'] ?? $_POST['html_content'] ?? '';
        $status  = in_array($_POST['status'] ?? '', ['draft','published','archived']) ? $_POST['status'] : $existing['status'];
        $wc = apiWordCount($content);
        $seo = apiSeoScore(['title'=>$title,'slug'=>$slug,'content'=>$content,
                            'meta_title'=>$_POST['meta_title']??'','meta_description'=>$_POST['meta_description']??'']);

        $pubAt = null;
        if ($status === 'published' && $existing['status'] !== 'published') {
            $pubAt = date('Y-m-d H:i:s');
        }

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'html_content'     => $content,
            'custom_css'       => $_POST['custom_css'] ?? '',
            'custom_js'        => $_POST['custom_js'] ?? '',
            'meta_title'       => trim(strip_tags($_POST['meta_title'] ?? '')),
            'meta_description' => trim(strip_tags($_POST['meta_description'] ?? '')),
            'meta_keywords'    => trim(strip_tags($_POST['meta_keywords'] ?? '')),
            'og_image'         => trim(strip_tags($_POST['og_image'] ?? '')),
            'focus_keyword'    => trim(strip_tags($_POST['focus_keyword'] ?? '')),
            'content_type'     => trim(strip_tags($_POST['content_type'] ?? 'page')),
            'status'           => $status,
            'visibility'       => in_array($_POST['visibility'] ?? '', ['public','private']) ? $_POST['visibility'] : null,
            'template'         => trim(strip_tags($_POST['template'] ?? 'default')),
            'is_file_based'    => (int)($_POST['is_file_based'] ?? 0),
            'file_path'        => trim(strip_tags($_POST['file_path'] ?? '')),
            'parent_id'        => ($_POST['parent_id'] ?? null) ? (int)$_POST['parent_id'] : null,
            'sort_order'       => (int)($_POST['sort_order'] ?? 0),
            'seo_score'        => $seo,
            'word_count'       => $wc,
        ];
        // Ne pas écraser visibility si non fournie
        if ($data['visibility'] === null) unset($data['visibility']);
        if ($pubAt) $data['published_at'] = $pubAt;

        apiSafeUpdate($pdo, $tableName, $id, $data, $existingCols);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        apiResponse(true, ['message' => 'Page mise à jour', 'page' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        break;

    // ═══════════════════════════════════════════
    // DELETE
    // ═══════════════════════════════════════════
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) apiResponse(false, ['error' => 'ID invalide'], 400);

        $check = $pdo->prepare("SELECT id, title, is_file_based FROM `{$tableName}` WHERE id = ?");
        $check->execute([$id]);
        $page = $check->fetch(PDO::FETCH_ASSOC);
        if (!$page) apiResponse(false, ['error' => 'Page introuvable'], 404);

        $pdo->prepare("DELETE FROM `{$tableName}` WHERE id = ?")->execute([$id]);

        // Nettoyer seo_scores si existe
        try {
            $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id = ?")->execute([$id]);
        } catch (PDOException $e) {}

        apiResponse(true, ['message' => "Page \"{$page['title']}\" supprimée", 'deleted_id' => $id]);
        break;

    // ═══════════════════════════════════════════
    // TOGGLE STATUS
    // ═══════════════════════════════════════════
    case 'toggle_status':
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if ($id <= 0 || !in_array($newStatus, ['draft','published','archived'])) {
            apiResponse(false, ['error' => 'Paramètres invalides'], 400);
        }

        $sql = "UPDATE `{$tableName}` SET status = ?";
        $params = [$newStatus];
        if ($newStatus === 'published' && in_array('published_at', $existingCols)) {
            $sql .= ", published_at = COALESCE(published_at, NOW())";
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);

        apiResponse(true, ['message' => 'Statut mis à jour', 'status' => $newStatus]);
        break;

    // ═══════════════════════════════════════════
    // DUPLICATE
    // ═══════════════════════════════════════════
    case 'duplicate':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) apiResponse(false, ['error' => 'ID invalide'], 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) apiResponse(false, ['error' => 'Page introuvable'], 404);

        $newTitle = $orig['title'] . ' (copie)';
        $newSlug = apiUniqueSlug($pdo, $tableName, ($orig['slug'] ?? '') . '-copie');

        $skip = ['id','created_at','updated_at','published_at'];
        $copyData = [];
        foreach ($orig as $col => $val) {
            if (in_array(strtolower($col), $skip)) continue;
            $copyData[$col] = $val;
        }
        $copyData['title'] = $newTitle;
        $copyData['slug'] = $newSlug;
        $copyData['status'] = 'draft';

        $newId = apiSafeInsert($pdo, $tableName, $copyData, $existingCols);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$newId]);
        apiResponse(true, ['message' => 'Page dupliquée', 'page' => $stmt->fetch(PDO::FETCH_ASSOC)], 201);
        break;

    // ═══════════════════════════════════════════
    // CHECK SLUG (GET)
    // ═══════════════════════════════════════════
    case 'check_slug':
        $slug = apiSlug($_GET['slug'] ?? $_POST['slug'] ?? '');
        $excludeId = (int)($_GET['exclude_id'] ?? $_POST['exclude_id'] ?? 0);
        if (!$slug) apiResponse(false, ['error' => 'Slug vide'], 400);

        $sql = "SELECT id FROM `{$tableName}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = (bool)$stmt->fetch();

        apiResponse(true, [
            'slug' => $slug,
            'available' => !$exists,
            'suggestion' => $exists ? apiUniqueSlug($pdo, $tableName, $slug, $excludeId) : $slug
        ]);
        break;

    // ═══════════════════════════════════════════
    // AUTOSAVE
    // ═══════════════════════════════════════════
    case 'autosave':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) apiResponse(false, ['error' => 'ID invalide'], 400);

        $content = $_POST['content'] ?? $_POST['html_content'] ?? '';
        $title   = trim(strip_tags($_POST['title'] ?? ''));
        $wc = apiWordCount($content);

        $data = ['word_count' => $wc];
        if ($title) $data['title'] = $title;
        if (in_array('content', $existingCols)) $data['content'] = $content;
        if (in_array('html_content', $existingCols)) $data['html_content'] = $content;
        if (isset($_POST['custom_css']) && in_array('custom_css', $existingCols)) $data['custom_css'] = $_POST['custom_css'];
        if (isset($_POST['custom_js']) && in_array('custom_js', $existingCols)) $data['custom_js'] = $_POST['custom_js'];

        apiSafeUpdate($pdo, $tableName, $id, $data, $existingCols);
        apiResponse(true, ['message' => 'Autosave OK', 'saved_at' => date('H:i:s')]);
        break;

    // ═══════════════════════════════════════════
    // BULK ACTION (v8.1 — ajout set_public / set_private)
    // ═══════════════════════════════════════════
    case 'bulk_action':
        $ids  = $_POST['ids'] ?? [];
        $bulk = $_POST['bulk_action'] ?? '';

        // Support ids envoyés comme JSON string ou comme array
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (is_array($decoded)) $ids = $decoded;
        }

        if (empty($ids) || !is_array($ids)) apiResponse(false, ['error' => 'Aucune sélection'], 400);

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        if (empty($ids)) apiResponse(false, ['error' => 'IDs invalides'], 400);

        $ph = implode(',', array_fill(0, count($ids), '?'));

        switch ($bulk) {
            case 'publish':
                $sql = "UPDATE `{$tableName}` SET status='published'";
                if (in_array('published_at', $existingCols)) {
                    $sql .= ", published_at = COALESCE(published_at, NOW())";
                }
                $sql .= " WHERE id IN ({$ph})";
                $pdo->prepare($sql)->execute($ids);
                $msg = count($ids) . ' page(s) publiée(s)';
                break;

            case 'draft':
                $pdo->prepare("UPDATE `{$tableName}` SET status='draft' WHERE id IN ({$ph})")->execute($ids);
                $msg = count($ids) . ' page(s) en brouillon';
                break;

            case 'archive':
                $pdo->prepare("UPDATE `{$tableName}` SET status='archived' WHERE id IN ({$ph})")->execute($ids);
                $msg = count($ids) . ' page(s) archivée(s)';
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM `{$tableName}` WHERE id IN ({$ph})");
                $stmt->execute($ids);
                // Nettoyer seo_scores
                try {
                    $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id IN ({$ph})")->execute($ids);
                } catch (PDOException $e) {}
                $msg = $stmt->rowCount() . ' page(s) supprimée(s)';
                break;

            case 'set_public':
                if (!in_array('visibility', $existingCols)) {
                    apiResponse(false, ['error' => 'Colonne visibility non disponible'], 400);
                }
                $pdo->prepare("UPDATE `{$tableName}` SET visibility='public' WHERE id IN ({$ph})")->execute($ids);
                $msg = count($ids) . ' page(s) passée(s) en publique';
                break;

            case 'set_private':
                if (!in_array('visibility', $existingCols)) {
                    apiResponse(false, ['error' => 'Colonne visibility non disponible'], 400);
                }
                $pdo->prepare("UPDATE `{$tableName}` SET visibility='private' WHERE id IN ({$ph})")->execute($ids);
                $msg = count($ids) . ' page(s) passée(s) en privée';
                break;

            default:
                apiResponse(false, ['error' => "Action bulk inconnue: {$bulk}"], 400);
        }
        apiResponse(true, ['message' => $msg, 'affected' => count($ids)]);
        break;

    // ═══════════════════════════════════════════
    // REORDER
    // ═══════════════════════════════════════════
    case 'reorder':
        $order = $_POST['order'] ?? [];
        if (!is_array($order)) apiResponse(false, ['error' => 'Format invalide'], 400);
        $stmt = $pdo->prepare("UPDATE `{$tableName}` SET sort_order = ? WHERE id = ?");
        foreach ($order as $pos => $id) { $stmt->execute([(int)$pos, (int)$id]); }
        apiResponse(true, ['message' => 'Ordre mis à jour']);
        break;

    // ═══════════════════════════════════════════
    // UPLOAD IMAGE
    // ═══════════════════════════════════════════
    case 'upload_image':
        if (!isset($_FILES['image'])) apiResponse(false, ['error' => 'Aucun fichier'], 400);

        $file = $_FILES['image'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $maxSize = 5 * 1024 * 1024;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) apiResponse(false, ['error' => 'Type non autorisé'], 400);
        if ($file['size'] > $maxSize) apiResponse(false, ['error' => 'Max 5MB'], 400);

        $root = dirname(dirname(dirname(__DIR__)));
        $dir = $root . '/uploads/images/pages/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = 'page_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
            apiResponse(true, ['url' => '/uploads/images/pages/' . $name, 'filename' => $name]);
        } else {
            apiResponse(false, ['error' => 'Erreur upload'], 500);
        }
        break;

    // ═══════════════════════════════════════════
    // DEFAULT
    // ═══════════════════════════════════════════
    default:
        apiResponse(false, ['error' => "Action inconnue: {$action}"], 400);
}

} catch (PDOException $e) {
    error_log("API Pages Error [PDO]: " . $e->getMessage());
    apiResponse(false, ['error' => 'Erreur DB', 'debug' => $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("API Pages Error: " . $e->getMessage());
    apiResponse(false, ['error' => 'Erreur serveur', 'debug' => $e->getMessage()], 500);
}