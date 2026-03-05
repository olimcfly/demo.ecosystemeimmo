<?php
// ======================================================
// Module INSTAGRAM - Gestion des publications
// /admin/modules/instagram/index.php
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

$page_title = "Instagram";
$current_module = "instagram";

// ====================================================
// GESTION DES ACTIONS (suppression, changement statut)
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $_SESSION['error_message'] = "Token CSRF invalide.";
        header("Location: /admin/index.php?module=instagram");
        exit;
    }

    $postId = (int)($_POST['post_id'] ?? 0);

    switch ($_POST['action']) {
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM social_posts WHERE id = ? AND platform = 'instagram'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication supprimée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur suppression : " . $e->getMessage();
            }
            break;

        case 'publish':
            try {
                $stmt = $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ? AND platform = 'instagram'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication marquée comme publiée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
            break;

        case 'schedule':
            $scheduledAt = $_POST['scheduled_at'] ?? '';
            if ($scheduledAt) {
                try {
                    $stmt = $pdo->prepare("UPDATE social_posts SET status = 'scheduled', scheduled_at = ? WHERE id = ? AND platform = 'instagram'");
                    $stmt->execute([$scheduledAt, $postId]);
                    $_SESSION['success_message'] = "Publication planifiée.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Erreur planification : " . $e->getMessage();
                }
            }
            break;
    }

    header("Location: /admin/index.php?module=instagram");
    exit;
}

// ====================================================
// RÉCUPÉRATION DES DONNÉES
// ====================================================

// Onglet actif
$activeTab = $_GET['tab'] ?? 'all';

// Stats Instagram
$igStats = [
    'total'     => 0,
    'published' => 0,
    'scheduled' => 0,
    'draft'     => 0,
    'reels'     => 0,
    'stories'   => 0,
    'carousels' => 0,
    'posts'     => 0,
];

try {
    // Stats globales
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total 
        FROM social_posts 
        WHERE platform = 'instagram' 
        GROUP BY status
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $igStats['total'] += $row['total'];
        $status = strtolower($row['status']);
        if (isset($igStats[$status])) {
            $igStats[$status] = (int)$row['total'];
        }
    }

    // Stats par type de contenu
    $stmt = $pdo->query("
        SELECT 
            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')), 'post') as content_type,
            COUNT(*) as total
        FROM social_posts 
        WHERE platform = 'instagram' 
        GROUP BY content_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['content_type']);
        if ($type === 'reel' || $type === 'reels') $igStats['reels'] = (int)$row['total'];
        elseif ($type === 'story' || $type === 'stories') $igStats['stories'] = (int)$row['total'];
        elseif ($type === 'carousel' || $type === 'carrousel') $igStats['carousels'] = (int)$row['total'];
        else $igStats['posts'] = (int)$row['total'];
    }
} catch (Exception $e) {
    // Table pas encore créée
}

// Compte Instagram connecté
$igAccount = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM social_accounts WHERE platform = 'instagram' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $igAccount = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Récupérer les publications
$whereClause = "WHERE platform = 'instagram'";
$params = [];

switch ($activeTab) {
    case 'published':
        $whereClause .= " AND status = 'published'";
        break;
    case 'scheduled':
        $whereClause .= " AND status = 'scheduled'";
        break;
    case 'draft':
        $whereClause .= " AND status = 'draft'";
        break;
    case 'reels':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('reel', 'reels')";
        break;
    case 'stories':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('story', 'stories')";
        break;
    case 'carousels':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('carousel', 'carrousel')";
        break;
}

// Recherche
$search = trim($_GET['search'] ?? '');
if ($search) {
    $whereClause .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$publications = [];
$totalItems = 0;

try {
    // Compteur total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM social_posts {$whereClause}");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();

    // Données
    $stmt = $pdo->prepare("
        SELECT * FROM social_posts 
        {$whereClause}
        ORDER BY 
            CASE WHEN status = 'scheduled' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END,
            COALESCE(scheduled_at, created_at) DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table pas encore créée
}

$totalPages = ceil($totalItems / $perPage);

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Messages flash
$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

ob_start();
?>

<style>
/* ========== Instagram Module Styles ========== */

.ig-hero {
    background: linear-gradient(135deg, #833AB4 0%, #FD1D1D 50%, #F77737 100%);
    border-radius: 16px;
    padding: 32px;
    color: #fff;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.ig-hero-left h1 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ig-hero-left h1 i {
    font-size: 2rem;
}

.ig-hero-left p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.ig-hero-right {
    display: flex;
    gap: 10px;
}

.ig-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.ig-hero-btn.primary {
    background: rgba(255,255,255,0.95);
    color: #833AB4;
}

.ig-hero-btn.primary:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.ig-hero-btn.secondary {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.4);
}

.ig-hero-btn.secondary:hover {
    background: rgba(255,255,255,0.3);
}

/* Stats cards */
.ig-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.ig-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #eee;
    transition: all 0.2s;
}

.ig-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.ig-stat-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.ig-stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
}

.ig-stat-label {
    font-size: 0.8rem;
    color: #888;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Account status */
.ig-account-bar {
    background: #fff;
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
}

.ig-account-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ig-account-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
}

.ig-account-name {
    font-weight: 700;
    color: #1a1a2e;
}

.ig-account-handle {
    font-size: 0.85rem;
    color: #888;
}

.ig-account-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
}

.ig-account-status.connected {
    background: #e8faf0;
    color: #16a34a;
}

.ig-account-status.disconnected {
    background: #fff3e6;
    color: #e67e22;
}

/* Connect box */
.ig-connect-box {
    background: #fff;
    border-radius: 12px;
    padding: 48px;
    text-align: center;
    border: 2px dashed #e0e0e0;
    margin-bottom: 24px;
}

.ig-connect-box i.main-icon {
    font-size: 3.5rem;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 16px;
}

.ig-connect-box h3 {
    font-size: 1.3rem;
    color: #1a1a2e;
    margin: 0 0 8px 0;
}

.ig-connect-box p {
    color: #888;
    margin: 0 0 20px 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.ig-connect-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
}

.ig-connect-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(131,58,180,0.35);
}

/* Tabs */
.ig-tabs {
    display: flex;
    gap: 4px;
    background: #f5f5f5;
    padding: 4px;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow-x: auto;
    flex-wrap: wrap;
}

.ig-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}

.ig-tab:hover {
    background: #e8e8e8;
    color: #333;
}

.ig-tab.active {
    background: #fff;
    color: #833AB4;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.ig-tab .badge {
    background: #eee;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.ig-tab.active .badge {
    background: rgba(131,58,180,0.12);
    color: #833AB4;
}

/* Toolbar */
.ig-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.ig-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 8px 16px;
    flex: 1;
    max-width: 400px;
}

.ig-search input {
    border: none;
    outline: none;
    font-size: 0.9rem;
    width: 100%;
    background: transparent;
}

.ig-search i {
    color: #aaa;
}

/* Content type icons */
.content-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.content-type-badge.reel {
    background: #f0e6ff;
    color: #833AB4;
}

.content-type-badge.story {
    background: #fff3e6;
    color: #F77737;
}

.content-type-badge.carousel {
    background: #e6f2ff;
    color: #1877F2;
}

.content-type-badge.post {
    background: #f0f0f0;
    color: #555;
}

/* Publication cards */
.ig-pub-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ig-pub-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #eee;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: all 0.2s;
}

.ig-pub-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-color: #ddd;
}

.ig-pub-thumb {
    width: 72px;
    height: 72px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5f0ff, #fff0f0);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.ig-pub-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.ig-pub-thumb .placeholder-icon {
    font-size: 1.6rem;
    color: #ccc;
}

.ig-pub-body {
    flex: 1;
    min-width: 0;
}

.ig-pub-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.ig-pub-title {
    font-weight: 700;
    color: #1a1a2e;
    font-size: 0.95rem;
}

.ig-pub-excerpt {
    color: #666;
    font-size: 0.87rem;
    line-height: 1.5;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ig-pub-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 0.8rem;
    color: #999;
    flex-wrap: wrap;
}

.ig-pub-meta i {
    margin-right: 3px;
}

.ig-pub-actions {
    display: flex;
    gap: 6px;
    align-items: flex-start;
    flex-shrink: 0;
}

.ig-pub-action {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #eee;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #888;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
}

.ig-pub-action:hover {
    background: #f5f5f5;
    color: #333;
}

.ig-pub-action.danger:hover {
    background: #fff5f5;
    color: #e74c3c;
    border-color: #fdd;
}

.ig-pub-action.success:hover {
    background: #f0faf4;
    color: #16a34a;
    border-color: #c3e6cb;
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.published {
    background: #e8faf0;
    color: #16a34a;
}

.status-badge.scheduled {
    background: #e6f2ff;
    color: #3b82f6;
}

.status-badge.draft {
    background: #f5f5f5;
    color: #888;
}

/* Empty state */
.ig-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.ig-empty i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #ddd;
}

.ig-empty h3 {
    color: #666;
    margin: 0 0 8px 0;
}

.ig-empty p {
    margin: 0 0 20px 0;
}

/* Pagination */
.ig-pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 24px;
}

.ig-pagination a,
.ig-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.ig-pagination a {
    background: #fff;
    border: 1px solid #eee;
    color: #666;
}

.ig-pagination a:hover {
    background: #f5f0ff;
    border-color: #833AB4;
    color: #833AB4;
}

.ig-pagination span.current {
    background: #833AB4;
    color: #fff;
    border: 1px solid #833AB4;
}

/* Flash messages */
.ig-flash {
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ig-flash.success {
    background: #e8faf0;
    color: #16a34a;
    border: 1px solid #c3e6cb;
}

.ig-flash.error {
    background: #fff5f5;
    color: #e74c3c;
    border: 1px solid #fdd;
}

/* Tips card */
.ig-tips {
    background: linear-gradient(135deg, #f9f5ff, #fff5f5);
    border-radius: 12px;
    padding: 24px;
    margin-top: 24px;
    border: 1px solid #ede0ff;
}

.ig-tips h3 {
    font-size: 1rem;
    color: #833AB4;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ig-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 8px;
}

.ig-tips li {
    padding: 8px 12px;
    background: rgba(255,255,255,0.7);
    border-radius: 8px;
    font-size: 0.85rem;
    color: #555;
    line-height: 1.4;
}

.ig-tips li strong {
    color: #833AB4;
}

/* Quick create */
.ig-quick-create {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #eee;
}

.ig-quick-create h3 {
    font-size: 1rem;
    color: #1a1a2e;
    margin: 0 0 16px 0;
}

.ig-quick-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.ig-quick-type {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 16px;
    border-radius: 12px;
    border: 2px solid #eee;
    text-decoration: none;
    color: #555;
    transition: all 0.2s;
    cursor: pointer;
    background: #fafafa;
}

.ig-quick-type:hover {
    border-color: #833AB4;
    background: #f9f5ff;
    color: #833AB4;
    transform: translateY(-2px);
}

.ig-quick-type i {
    font-size: 1.5rem;
}

.ig-quick-type span {
    font-weight: 600;
    font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 768px) {
    .ig-hero {
        padding: 24px;
        flex-direction: column;
        text-align: center;
    }
    .ig-hero-right {
        width: 100%;
        justify-content: center;
    }
    .ig-pub-card {
        flex-direction: column;
    }
    .ig-pub-thumb {
        width: 100%;
        height: 160px;
    }
    .ig-pub-actions {
        width: 100%;
        justify-content: flex-end;
    }
    .ig-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- ===== Messages Flash ===== -->
<?php if ($successMsg): ?>
    <div class="ig-flash success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="ig-flash error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- ===== Hero Banner ===== -->
<div class="ig-hero">
    <div class="ig-hero-left">
        <h1><i class="fab fa-instagram"></i> Instagram</h1>
        <p>Gérez vos publications, Reels, Stories et carrousels pour Instagram</p>
    </div>
    <div class="ig-hero-right">
        <a href="/admin/index.php?module=instagram&action=create" class="ig-hero-btn primary">
            <i class="fas fa-plus"></i> Nouvelle publication
        </a>
        <a href="/admin/index.php?module=reseaux-sociaux" class="ig-hero-btn secondary">
            <i class="fas fa-th-large"></i> Hub Réseaux
        </a>
    </div>
</div>

<!-- ===== Compte connecté ou Connexion ===== -->
<?php if ($igAccount): ?>
    <div class="ig-account-bar">
        <div class="ig-account-info">
            <div class="ig-account-avatar">
                <?= strtoupper(substr($igAccount['account_name'] ?? 'IG', 0, 1)) ?>
            </div>
            <div>
                <div class="ig-account-name"><?= htmlspecialchars($igAccount['account_name'] ?? 'Instagram') ?></div>
                <div class="ig-account-handle">@<?= htmlspecialchars($igAccount['username'] ?? 'eduardo.desul') ?></div>
            </div>
        </div>
        <span class="ig-account-status connected">
            <i class="fas fa-check-circle"></i> Connecté
        </span>
    </div>
<?php else: ?>
    <div class="ig-connect-box">
        <i class="fab fa-instagram main-icon"></i>
        <h3>Connectez votre compte Instagram Business</h3>
        <p>Liez votre compte Instagram pour publier directement depuis l'admin, suivre vos statistiques et planifier vos publications.</p>
        <button class="ig-connect-btn" onclick="alert('La connexion Instagram via Facebook Graph API sera configurée prochainement.\n\nPour l\'instant, vous pouvez créer et planifier vos publications ici, puis les copier/coller sur Instagram.')">
            <i class="fab fa-instagram"></i> Connecter Instagram
        </button>
    </div>
<?php endif; ?>

<!-- ===== Stats ===== -->
<div class="ig-stats-grid">
    <div class="ig-stat-card">
        <div class="ig-stat-icon">📊</div>
        <div class="ig-stat-value"><?= $igStats['total'] ?></div>
        <div class="ig-stat-label">Total publications</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">✅</div>
        <div class="ig-stat-value"><?= $igStats['published'] ?></div>
        <div class="ig-stat-label">Publiées</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">📅</div>
        <div class="ig-stat-value"><?= $igStats['scheduled'] ?></div>
        <div class="ig-stat-label">Planifiées</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">📝</div>
        <div class="ig-stat-value"><?= $igStats['draft'] ?></div>
        <div class="ig-stat-label">Brouillons</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">🎬</div>
        <div class="ig-stat-value"><?= $igStats['reels'] ?></div>
        <div class="ig-stat-label">Reels</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">⏱️</div>
        <div class="ig-stat-value"><?= $igStats['stories'] ?></div>
        <div class="ig-stat-label">Stories</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">🎠</div>
        <div class="ig-stat-value"><?= $igStats['carousels'] ?></div>
        <div class="ig-stat-label">Carrousels</div>
    </div>
    <div class="ig-stat-card">
        <div class="ig-stat-icon">📸</div>
        <div class="ig-stat-value"><?= $igStats['posts'] ?></div>
        <div class="ig-stat-label">Posts photo</div>
    </div>
</div>

<!-- ===== Création rapide ===== -->
<div class="ig-quick-create">
    <h3><i class="fas fa-bolt" style="color: #F77737;"></i> Création rapide</h3>
    <div class="ig-quick-types">
        <a class="ig-quick-type" href="/admin/index.php?module=instagram&action=create&type=post">
            <i class="fas fa-image" style="color: #833AB4;"></i>
            <span>Post photo</span>
        </a>
        <a class="ig-quick-type" href="/admin/index.php?module=instagram&action=create&type=carousel">
            <i class="fas fa-images" style="color: #1877F2;"></i>
            <span>Carrousel</span>
        </a>
        <a class="ig-quick-type" href="/admin/index.php?module=instagram&action=create&type=reel">
            <i class="fas fa-film" style="color: #833AB4;"></i>
            <span>Reel</span>
        </a>
        <a class="ig-quick-type" href="/admin/index.php?module=instagram&action=create&type=story">
            <i class="fas fa-circle-notch" style="color: #F77737;"></i>
            <span>Story</span>
        </a>
    </div>
</div>

<!-- ===== Onglets ===== -->
<div class="ig-tabs">
    <?php
    $tabs = [
        'all'       => ['label' => 'Tout',        'icon' => 'fas fa-th',          'count' => $igStats['total']],
        'published' => ['label' => 'Publiées',    'icon' => 'fas fa-check',       'count' => $igStats['published']],
        'scheduled' => ['label' => 'Planifiées',  'icon' => 'fas fa-clock',       'count' => $igStats['scheduled']],
        'draft'     => ['label' => 'Brouillons',  'icon' => 'fas fa-pencil-alt',  'count' => $igStats['draft']],
        'reels'     => ['label' => 'Reels',       'icon' => 'fas fa-film',        'count' => $igStats['reels']],
        'stories'   => ['label' => 'Stories',     'icon' => 'fas fa-circle-notch','count' => $igStats['stories']],
        'carousels' => ['label' => 'Carrousels',  'icon' => 'fas fa-images',      'count' => $igStats['carousels']],
    ];
    foreach ($tabs as $key => $tab): ?>
        <a href="/admin/index.php?module=instagram&tab=<?= $key ?>" 
           class="ig-tab <?= $activeTab === $key ? 'active' : '' ?>">
            <i class="<?= $tab['icon'] ?>"></i>
            <?= $tab['label'] ?>
            <span class="badge"><?= $tab['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- ===== Toolbar (recherche) ===== -->
<div class="ig-toolbar">
    <form class="ig-search" method="get" action="/admin/index.php">
        <input type="hidden" name="module" value="instagram">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Rechercher une publication..." 
               value="<?= htmlspecialchars($search) ?>">
    </form>
    <div style="font-size: 0.85rem; color: #888;">
        <?= $totalItems ?> publication<?= $totalItems > 1 ? 's' : '' ?>
    </div>
</div>

<!-- ===== Liste des publications ===== -->
<?php if (empty($publications) && $totalItems === 0): ?>
    <div class="ig-empty">
        <i class="fab fa-instagram"></i>
        <h3>Aucune publication Instagram</h3>
        <p>Commencez par créer votre première publication pour Instagram.</p>
        <a href="/admin/index.php?module=instagram&action=create" class="ig-hero-btn primary" 
           style="display: inline-flex; background: #833AB4; color: #fff;">
            <i class="fas fa-plus"></i> Créer ma première publication
        </a>
    </div>
<?php else: ?>
    <div class="ig-pub-list">
        <?php foreach ($publications as $pub):
            // Extraire le type de contenu
            $metadata = json_decode($pub['metadata'] ?? '{}', true) ?: [];
            $contentType = $metadata['content_type'] ?? 'post';
            $hashtags = $metadata['hashtags'] ?? '';
            $thumbnail = $metadata['thumbnail'] ?? ($pub['image_url'] ?? '');
            
            // Statut
            $statusClass = $pub['status'] ?? 'draft';
            $statusLabels = [
                'published' => '✅ Publiée',
                'scheduled' => '📅 Planifiée',
                'draft'     => '📝 Brouillon',
            ];
            $statusLabel = $statusLabels[$statusClass] ?? ucfirst($statusClass);
            
            // Dates
            $createdDate = !empty($pub['created_at']) ? date('d/m/Y', strtotime($pub['created_at'])) : '-';
            $scheduledDate = !empty($pub['scheduled_at']) ? date('d/m/Y H:i', strtotime($pub['scheduled_at'])) : '';
            $publishedDate = !empty($pub['published_at']) ? date('d/m/Y H:i', strtotime($pub['published_at'])) : '';
            
            // Titre / extrait
            $title = $pub['title'] ?? 'Sans titre';
            $excerpt = mb_substr(strip_tags($pub['content'] ?? ''), 0, 120);
        ?>
            <div class="ig-pub-card">
                <!-- Thumbnail -->
                <div class="ig-pub-thumb">
                    <?php if ($thumbnail): ?>
                        <img src="<?= htmlspecialchars($thumbnail) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="placeholder-icon">
                            <?php if ($contentType === 'reel' || $contentType === 'reels'): ?>
                                <i class="fas fa-film"></i>
                            <?php elseif ($contentType === 'story' || $contentType === 'stories'): ?>
                                <i class="fas fa-circle-notch"></i>
                            <?php elseif ($contentType === 'carousel' || $contentType === 'carrousel'): ?>
                                <i class="fas fa-images"></i>
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="ig-pub-body">
                    <div class="ig-pub-header">
                        <span class="ig-pub-title"><?= htmlspecialchars($title) ?></span>
                        <span class="content-type-badge <?= htmlspecialchars($contentType) ?>">
                            <?php
                            $typeLabels = ['reel' => '🎬 Reel', 'reels' => '🎬 Reel', 'story' => '⏱️ Story', 'stories' => '⏱️ Story', 'carousel' => '🎠 Carrousel', 'carrousel' => '🎠 Carrousel'];
                            echo $typeLabels[$contentType] ?? '📸 Post';
                            ?>
                        </span>
                        <span class="status-badge <?= htmlspecialchars($statusClass) ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <?php if ($excerpt): ?>
                        <div class="ig-pub-excerpt"><?= htmlspecialchars($excerpt) ?></div>
                    <?php endif; ?>

                    <div class="ig-pub-meta">
                        <span><i class="fas fa-calendar"></i> Créé le <?= $createdDate ?></span>
                        <?php if ($scheduledDate && $statusClass === 'scheduled'): ?>
                            <span><i class="fas fa-clock"></i> Planifié : <?= $scheduledDate ?></span>
                        <?php endif; ?>
                        <?php if ($publishedDate && $statusClass === 'published'): ?>
                            <span><i class="fas fa-check-circle"></i> Publié : <?= $publishedDate ?></span>
                        <?php endif; ?>
                        <?php if ($hashtags): ?>
                            <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars(mb_substr($hashtags, 0, 60)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ig-pub-actions">
                    <a href="/admin/index.php?module=instagram&action=edit&id=<?= $pub['id'] ?>" 
                       class="ig-pub-action" title="Modifier">
                        <i class="fas fa-pen"></i>
                    </a>

                    <?php if ($statusClass === 'draft'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="publish">
                            <input type="hidden" name="post_id" value="<?= $pub['id'] ?>">
                            <button type="submit" class="ig-pub-action success" title="Marquer comme publiée"
                                    onclick="return confirm('Marquer comme publiée ?')">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="post_id" value="<?= $pub['id'] ?>">
                        <button type="submit" class="ig-pub-action danger" title="Supprimer"
                                onclick="return confirm('Supprimer cette publication ?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="ig-pagination">
            <?php if ($page > 1): ?>
                <a href="/admin/index.php?module=instagram&tab=<?= $activeTab ?>&p=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/index.php?module=instagram&tab=<?= $activeTab ?>&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="/admin/index.php?module=instagram&tab=<?= $activeTab ?>&p=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ===== Conseils Instagram ===== -->
<div class="ig-tips">
    <h3><i class="fas fa-lightbulb"></i> Bonnes pratiques Instagram Immobilier</h3>
    <ul>
        <li><strong>Reels :</strong> Visites virtuelles de 30-60s, avant/après rénovation, coulisses du métier — algorithme favorise les Reels</li>
        <li><strong>Carrousels :</strong> Top 5 quartiers, checklist acheteur, comparatifs — très bon taux d'enregistrement</li>
        <li><strong>Stories :</strong> Sondages (appart ou maison ?), questions/réponses, coulisses — crée du lien</li>
        <li><strong>Posts :</strong> Nouveau bien, témoignage client, conseil expert — soigner la première ligne</li>
        <li><strong>Hashtags :</strong> Mix de #immobilierbordeaux (local) + #conseilimmobilier (général) — 15 à 20 max</li>
        <li><strong>Fréquence :</strong> 4-5 posts/semaine minimum, 3+ stories/jour pour rester visible</li>
        <li><strong>Méthode MERE :</strong> Miroir → Émotion → Réassurance → Exclusivité dans chaque légende</li>
        <li><strong>Heures :</strong> Publier entre 7h-9h ou 18h-21h pour maximiser l'engagement à Bordeaux</li>
    </ul>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/layout.php';
?>