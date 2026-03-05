<?php
// ======================================================
// Module LINKEDIN - Gestion des publications
// /admin/modules/linkedin/index.php
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

$page_title = "LinkedIn";
$current_module = "linkedin";

// ====================================================
// GESTION DES ACTIONS (suppression, changement statut)
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $_SESSION['error_message'] = "Token CSRF invalide.";
        header("Location: /admin/index.php?module=linkedin");
        exit;
    }

    $postId = (int)($_POST['post_id'] ?? 0);

    switch ($_POST['action']) {
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM social_posts WHERE id = ? AND platform = 'linkedin'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication supprimée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur suppression : " . $e->getMessage();
            }
            break;

        case 'publish':
            try {
                $stmt = $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ? AND platform = 'linkedin'");
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
                    $stmt = $pdo->prepare("UPDATE social_posts SET status = 'scheduled', scheduled_at = ? WHERE id = ? AND platform = 'linkedin'");
                    $stmt->execute([$scheduledAt, $postId]);
                    $_SESSION['success_message'] = "Publication planifiée.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Erreur planification : " . $e->getMessage();
                }
            }
            break;
    }

    header("Location: /admin/index.php?module=linkedin");
    exit;
}

// ====================================================
// RÉCUPÉRATION DES DONNÉES
// ====================================================

$activeTab = $_GET['tab'] ?? 'all';

// Stats LinkedIn
$liStats = [
    'total'      => 0,
    'published'  => 0,
    'scheduled'  => 0,
    'draft'      => 0,
    'articles'   => 0,
    'posts'      => 0,
    'documents'  => 0,
    'videos'     => 0,
];

try {
    // Stats par statut
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total 
        FROM social_posts 
        WHERE platform = 'linkedin' 
        GROUP BY status
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $liStats['total'] += $row['total'];
        $status = strtolower($row['status']);
        if (isset($liStats[$status])) {
            $liStats[$status] = (int)$row['total'];
        }
    }

    // Stats par type de contenu
    $stmt = $pdo->query("
        SELECT 
            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')), 'post') as content_type,
            COUNT(*) as total
        FROM social_posts 
        WHERE platform = 'linkedin' 
        GROUP BY content_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['content_type']);
        if ($type === 'article') $liStats['articles'] = (int)$row['total'];
        elseif ($type === 'document' || $type === 'pdf' || $type === 'carousel') $liStats['documents'] = (int)$row['total'];
        elseif ($type === 'video') $liStats['videos'] = (int)$row['total'];
        else $liStats['posts'] = (int)$row['total'];
    }
} catch (Exception $e) {
    // Table pas encore créée
}

// Compte LinkedIn connecté
$liAccount = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM social_accounts WHERE platform = 'linkedin' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $liAccount = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Récupérer les publications
$whereClause = "WHERE platform = 'linkedin'";
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
    case 'articles':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) = 'article'";
        break;
    case 'documents':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('document', 'pdf', 'carousel')";
        break;
    case 'videos':
        $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) = 'video'";
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
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM social_posts {$whereClause}");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();

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
} catch (Exception $e) {}

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
/* ========== LinkedIn Module Styles ========== */

.li-hero {
    background: linear-gradient(135deg, #0A66C2 0%, #004182 60%, #002D5A 100%);
    border-radius: 16px;
    padding: 32px;
    color: #fff;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
    overflow: hidden;
}

.li-hero::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}

.li-hero::after {
    content: '';
    position: absolute;
    bottom: -60px;
    right: 80px;
    width: 140px;
    height: 140px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}

.li-hero-left h1 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
}

.li-hero-left h1 i {
    font-size: 2rem;
}

.li-hero-left p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
    position: relative;
    z-index: 1;
}

.li-hero-right {
    display: flex;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.li-hero-btn {
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

.li-hero-btn.primary {
    background: rgba(255,255,255,0.95);
    color: #0A66C2;
}

.li-hero-btn.primary:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.li-hero-btn.secondary {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
}

.li-hero-btn.secondary:hover {
    background: rgba(255,255,255,0.25);
}

/* Stats */
.li-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.li-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #eee;
    transition: all 0.2s;
}

.li-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.li-stat-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.li-stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
}

.li-stat-label {
    font-size: 0.8rem;
    color: #888;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Account bar */
.li-account-bar {
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

.li-account-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.li-account-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0A66C2, #004182);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
}

.li-account-name {
    font-weight: 700;
    color: #1a1a2e;
}

.li-account-handle {
    font-size: 0.85rem;
    color: #888;
}

.li-account-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
}

.li-account-status.connected {
    background: #e8faf0;
    color: #16a34a;
}

.li-account-status.disconnected {
    background: #fff3e6;
    color: #e67e22;
}

/* Connect box */
.li-connect-box {
    background: #fff;
    border-radius: 12px;
    padding: 48px;
    text-align: center;
    border: 2px dashed #d0dbe8;
    margin-bottom: 24px;
}

.li-connect-box i.main-icon {
    font-size: 3.5rem;
    color: #0A66C2;
    margin-bottom: 16px;
}

.li-connect-box h3 {
    font-size: 1.3rem;
    color: #1a1a2e;
    margin: 0 0 8px 0;
}

.li-connect-box p {
    color: #888;
    margin: 0 0 20px 0;
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
}

.li-connect-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: #0A66C2;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
}

.li-connect-btn:hover {
    background: #004182;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(10,102,194,0.35);
}

/* Tabs */
.li-tabs {
    display: flex;
    gap: 4px;
    background: #f5f5f5;
    padding: 4px;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow-x: auto;
    flex-wrap: wrap;
}

.li-tab {
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

.li-tab:hover {
    background: #e8e8e8;
    color: #333;
}

.li-tab.active {
    background: #fff;
    color: #0A66C2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.li-tab .badge {
    background: #eee;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.li-tab.active .badge {
    background: rgba(10,102,194,0.12);
    color: #0A66C2;
}

/* Toolbar */
.li-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.li-search {
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

.li-search input {
    border: none;
    outline: none;
    font-size: 0.9rem;
    width: 100%;
    background: transparent;
}

.li-search i {
    color: #aaa;
}

/* Content type badges */
.li-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.li-type-badge.article {
    background: #fff4e6;
    color: #e67e22;
}

.li-type-badge.document {
    background: #e8f4fd;
    color: #0A66C2;
}

.li-type-badge.video {
    background: #f0e6ff;
    color: #7c3aed;
}

.li-type-badge.post {
    background: #f0f0f0;
    color: #555;
}

/* Quick create */
.li-quick-create {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #eee;
}

.li-quick-create h3 {
    font-size: 1rem;
    color: #1a1a2e;
    margin: 0 0 16px 0;
}

.li-quick-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.li-quick-type {
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

.li-quick-type:hover {
    border-color: #0A66C2;
    background: #f0f7ff;
    color: #0A66C2;
    transform: translateY(-2px);
}

.li-quick-type i {
    font-size: 1.5rem;
}

.li-quick-type span {
    font-weight: 600;
    font-size: 0.85rem;
}

.li-quick-type small {
    font-size: 0.72rem;
    color: #999;
    text-align: center;
    line-height: 1.3;
}

.li-quick-type:hover small {
    color: #0A66C2;
}

/* Publication list */
.li-pub-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.li-pub-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #eee;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: all 0.2s;
}

.li-pub-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-color: #ddd;
}

.li-pub-icon-col {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.3rem;
}

.li-pub-icon-col.type-post {
    background: #f0f7ff;
    color: #0A66C2;
}

.li-pub-icon-col.type-article {
    background: #fff4e6;
    color: #e67e22;
}

.li-pub-icon-col.type-document {
    background: #e8f4fd;
    color: #0A66C2;
}

.li-pub-icon-col.type-video {
    background: #f0e6ff;
    color: #7c3aed;
}

.li-pub-body {
    flex: 1;
    min-width: 0;
}

.li-pub-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.li-pub-title {
    font-weight: 700;
    color: #1a1a2e;
    font-size: 0.95rem;
}

.li-pub-excerpt {
    color: #666;
    font-size: 0.87rem;
    line-height: 1.5;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.li-pub-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 0.8rem;
    color: #999;
    flex-wrap: wrap;
}

.li-pub-meta i {
    margin-right: 3px;
}

.li-pub-actions {
    display: flex;
    gap: 6px;
    align-items: flex-start;
    flex-shrink: 0;
}

.li-pub-action {
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

.li-pub-action:hover {
    background: #f5f5f5;
    color: #333;
}

.li-pub-action.danger:hover {
    background: #fff5f5;
    color: #e74c3c;
    border-color: #fdd;
}

.li-pub-action.success:hover {
    background: #f0faf4;
    color: #16a34a;
    border-color: #c3e6cb;
}

/* Status badges */
.li-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.li-status-badge.published {
    background: #e8faf0;
    color: #16a34a;
}

.li-status-badge.scheduled {
    background: #e6f2ff;
    color: #3b82f6;
}

.li-status-badge.draft {
    background: #f5f5f5;
    color: #888;
}

/* Empty state */
.li-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.li-empty i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #cddaeb;
}

.li-empty h3 {
    color: #666;
    margin: 0 0 8px 0;
}

.li-empty p {
    margin: 0 0 20px 0;
}

/* Pagination */
.li-pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 24px;
}

.li-pagination a,
.li-pagination span {
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

.li-pagination a {
    background: #fff;
    border: 1px solid #eee;
    color: #666;
}

.li-pagination a:hover {
    background: #f0f7ff;
    border-color: #0A66C2;
    color: #0A66C2;
}

.li-pagination span.current {
    background: #0A66C2;
    color: #fff;
    border: 1px solid #0A66C2;
}

/* Flash messages */
.li-flash {
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.li-flash.success {
    background: #e8faf0;
    color: #16a34a;
    border: 1px solid #c3e6cb;
}

.li-flash.error {
    background: #fff5f5;
    color: #e74c3c;
    border: 1px solid #fdd;
}

/* LinkedIn strategy section */
.li-strategy {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 24px;
}

.li-strategy-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #eee;
}

.li-strategy-card h3 {
    font-size: 1rem;
    color: #0A66C2;
    margin: 0 0 14px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.li-strategy-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.li-strategy-card li {
    padding: 8px 12px;
    background: #f8fafd;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #555;
    line-height: 1.5;
    margin-bottom: 6px;
}

.li-strategy-card li:last-child {
    margin-bottom: 0;
}

.li-strategy-card li strong {
    color: #0A66C2;
}

/* Audience cards */
.li-audience-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 12px;
}

.li-audience-card {
    background: #f8fafd;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    border: 1px solid #e8eff8;
}

.li-audience-card .emoji {
    font-size: 1.8rem;
    margin-bottom: 6px;
}

.li-audience-card h4 {
    font-size: 0.88rem;
    color: #1a1a2e;
    margin: 0 0 4px 0;
}

.li-audience-card p {
    font-size: 0.78rem;
    color: #888;
    margin: 0;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 768px) {
    .li-hero {
        padding: 24px;
        flex-direction: column;
        text-align: center;
    }
    .li-hero-right {
        width: 100%;
        justify-content: center;
    }
    .li-pub-card {
        flex-direction: column;
    }
    .li-pub-icon-col {
        width: 100%;
        height: 48px;
        border-radius: 8px;
    }
    .li-pub-actions {
        width: 100%;
        justify-content: flex-end;
    }
    .li-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .li-strategy {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ===== Messages Flash ===== -->
<?php if ($successMsg): ?>
    <div class="li-flash success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="li-flash error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- ===== Hero Banner ===== -->
<div class="li-hero">
    <div class="li-hero-left">
        <h1><i class="fab fa-linkedin"></i> LinkedIn</h1>
        <p>Développez votre réseau professionnel et votre crédibilité d'expert immobilier</p>
    </div>
    <div class="li-hero-right">
        <a href="/admin/index.php?module=linkedin&action=create" class="li-hero-btn primary">
            <i class="fas fa-plus"></i> Nouvelle publication
        </a>
        <a href="/admin/index.php?module=reseaux-sociaux" class="li-hero-btn secondary">
            <i class="fas fa-th-large"></i> Hub Réseaux
        </a>
    </div>
</div>

<!-- ===== Compte connecté ou Connexion ===== -->
<?php if ($liAccount): ?>
    <div class="li-account-bar">
        <div class="li-account-info">
            <div class="li-account-avatar">
                <?= strtoupper(substr($liAccount['account_name'] ?? 'LI', 0, 1)) ?>
            </div>
            <div>
                <div class="li-account-name"><?= htmlspecialchars($liAccount['account_name'] ?? 'LinkedIn') ?></div>
                <div class="li-account-handle"><?= htmlspecialchars($liAccount['username'] ?? 'Eduardo De Sul') ?></div>
            </div>
        </div>
        <span class="li-account-status connected">
            <i class="fas fa-check-circle"></i> Connecté
        </span>
    </div>
<?php else: ?>
    <div class="li-connect-box">
        <i class="fab fa-linkedin main-icon"></i>
        <h3>Connectez votre profil LinkedIn</h3>
        <p>Liez votre profil LinkedIn pour publier directement, suivre vos statistiques et développer votre réseau professionnel. En attendant, créez vos publications ici et copiez-les sur LinkedIn.</p>
        <button class="li-connect-btn" onclick="alert('La connexion LinkedIn API sera configurée prochainement.\n\nPour l\'instant, créez et planifiez vos publications ici, puis copiez-les sur LinkedIn.')">
            <i class="fab fa-linkedin"></i> Connecter LinkedIn
        </button>
    </div>
<?php endif; ?>

<!-- ===== Stats ===== -->
<div class="li-stats-grid">
    <div class="li-stat-card">
        <div class="li-stat-icon">📊</div>
        <div class="li-stat-value"><?= $liStats['total'] ?></div>
        <div class="li-stat-label">Total publications</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">✅</div>
        <div class="li-stat-value"><?= $liStats['published'] ?></div>
        <div class="li-stat-label">Publiées</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">📅</div>
        <div class="li-stat-value"><?= $liStats['scheduled'] ?></div>
        <div class="li-stat-label">Planifiées</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">📝</div>
        <div class="li-stat-value"><?= $liStats['draft'] ?></div>
        <div class="li-stat-label">Brouillons</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">📰</div>
        <div class="li-stat-value"><?= $liStats['articles'] ?></div>
        <div class="li-stat-label">Articles</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">💬</div>
        <div class="li-stat-value"><?= $liStats['posts'] ?></div>
        <div class="li-stat-label">Posts</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">📑</div>
        <div class="li-stat-value"><?= $liStats['documents'] ?></div>
        <div class="li-stat-label">Documents</div>
    </div>
    <div class="li-stat-card">
        <div class="li-stat-icon">🎥</div>
        <div class="li-stat-value"><?= $liStats['videos'] ?></div>
        <div class="li-stat-label">Vidéos</div>
    </div>
</div>

<!-- ===== Création rapide ===== -->
<div class="li-quick-create">
    <h3><i class="fas fa-bolt" style="color: #0A66C2;"></i> Création rapide</h3>
    <div class="li-quick-types">
        <a class="li-quick-type" href="/admin/index.php?module=linkedin&action=create&type=post">
            <i class="fas fa-comment-dots" style="color: #0A66C2;"></i>
            <span>Post texte</span>
            <small>Partage d'expertise, avis, conseil</small>
        </a>
        <a class="li-quick-type" href="/admin/index.php?module=linkedin&action=create&type=article">
            <i class="fas fa-newspaper" style="color: #e67e22;"></i>
            <span>Article</span>
            <small>Analyse marché, guide complet</small>
        </a>
        <a class="li-quick-type" href="/admin/index.php?module=linkedin&action=create&type=document">
            <i class="fas fa-file-pdf" style="color: #0A66C2;"></i>
            <span>Document PDF</span>
            <small>Carrousel LinkedIn, infographie</small>
        </a>
        <a class="li-quick-type" href="/admin/index.php?module=linkedin&action=create&type=video">
            <i class="fas fa-video" style="color: #7c3aed;"></i>
            <span>Vidéo</span>
            <small>Témoignage, visite, coulisses</small>
        </a>
    </div>
</div>

<!-- ===== Onglets ===== -->
<div class="li-tabs">
    <?php
    $tabs = [
        'all'       => ['label' => 'Tout',       'icon' => 'fas fa-th',         'count' => $liStats['total']],
        'published' => ['label' => 'Publiées',   'icon' => 'fas fa-check',      'count' => $liStats['published']],
        'scheduled' => ['label' => 'Planifiées', 'icon' => 'fas fa-clock',      'count' => $liStats['scheduled']],
        'draft'     => ['label' => 'Brouillons', 'icon' => 'fas fa-pencil-alt', 'count' => $liStats['draft']],
        'articles'  => ['label' => 'Articles',   'icon' => 'fas fa-newspaper',  'count' => $liStats['articles']],
        'documents' => ['label' => 'Documents',  'icon' => 'fas fa-file-pdf',   'count' => $liStats['documents']],
        'videos'    => ['label' => 'Vidéos',     'icon' => 'fas fa-video',      'count' => $liStats['videos']],
    ];
    foreach ($tabs as $key => $tab): ?>
        <a href="/admin/index.php?module=linkedin&tab=<?= $key ?>" 
           class="li-tab <?= $activeTab === $key ? 'active' : '' ?>">
            <i class="<?= $tab['icon'] ?>"></i>
            <?= $tab['label'] ?>
            <span class="badge"><?= $tab['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- ===== Toolbar ===== -->
<div class="li-toolbar">
    <form class="li-search" method="get" action="/admin/index.php">
        <input type="hidden" name="module" value="linkedin">
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
    <div class="li-empty">
        <i class="fab fa-linkedin"></i>
        <h3>Aucune publication LinkedIn</h3>
        <p>Commencez par créer votre première publication pour LinkedIn.</p>
        <a href="/admin/index.php?module=linkedin&action=create" class="li-hero-btn primary" 
           style="display: inline-flex; background: #0A66C2; color: #fff;">
            <i class="fas fa-plus"></i> Créer ma première publication
        </a>
    </div>
<?php else: ?>
    <div class="li-pub-list">
        <?php foreach ($publications as $pub):
            $metadata = json_decode($pub['metadata'] ?? '{}', true) ?: [];
            $contentType = $metadata['content_type'] ?? 'post';
            
            // Icône selon type
            $typeIcons = [
                'post'     => 'fas fa-comment-dots',
                'article'  => 'fas fa-newspaper',
                'document' => 'fas fa-file-pdf',
                'pdf'      => 'fas fa-file-pdf',
                'carousel' => 'fas fa-file-pdf',
                'video'    => 'fas fa-video',
            ];
            $typeIcon = $typeIcons[$contentType] ?? 'fas fa-comment-dots';
            
            // Classe type pour la couleur
            $typeClass = 'post';
            if (in_array($contentType, ['document', 'pdf', 'carousel'])) $typeClass = 'document';
            elseif ($contentType === 'article') $typeClass = 'article';
            elseif ($contentType === 'video') $typeClass = 'video';
            
            // Type labels
            $typeLabels = [
                'post'     => '💬 Post',
                'article'  => '📰 Article',
                'document' => '📑 Document',
                'pdf'      => '📑 PDF',
                'carousel' => '📑 Carrousel',
                'video'    => '🎥 Vidéo',
            ];
            $typeLabel = $typeLabels[$contentType] ?? '💬 Post';
            
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
            
            $title = $pub['title'] ?? 'Sans titre';
            $excerpt = mb_substr(strip_tags($pub['content'] ?? ''), 0, 140);
        ?>
            <div class="li-pub-card">
                <!-- Type icon -->
                <div class="li-pub-icon-col type-<?= $typeClass ?>">
                    <i class="<?= $typeIcon ?>"></i>
                </div>

                <!-- Body -->
                <div class="li-pub-body">
                    <div class="li-pub-header">
                        <span class="li-pub-title"><?= htmlspecialchars($title) ?></span>
                        <span class="li-type-badge <?= $typeClass ?>">
                            <?= $typeLabel ?>
                        </span>
                        <span class="li-status-badge <?= htmlspecialchars($statusClass) ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <?php if ($excerpt): ?>
                        <div class="li-pub-excerpt"><?= htmlspecialchars($excerpt) ?></div>
                    <?php endif; ?>

                    <div class="li-pub-meta">
                        <span><i class="fas fa-calendar"></i> Créé le <?= $createdDate ?></span>
                        <?php if ($scheduledDate && $statusClass === 'scheduled'): ?>
                            <span><i class="fas fa-clock"></i> Planifié : <?= $scheduledDate ?></span>
                        <?php endif; ?>
                        <?php if ($publishedDate && $statusClass === 'published'): ?>
                            <span><i class="fas fa-check-circle"></i> Publié : <?= $publishedDate ?></span>
                        <?php endif; ?>
                        <?php if (!empty($metadata['audience'])): ?>
                            <span><i class="fas fa-users"></i> <?= htmlspecialchars($metadata['audience']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="li-pub-actions">
                    <a href="/admin/index.php?module=linkedin&action=edit&id=<?= $pub['id'] ?>" 
                       class="li-pub-action" title="Modifier">
                        <i class="fas fa-pen"></i>
                    </a>

                    <?php if ($statusClass === 'draft'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="publish">
                            <input type="hidden" name="post_id" value="<?= $pub['id'] ?>">
                            <button type="submit" class="li-pub-action success" title="Marquer comme publiée"
                                    onclick="return confirm('Marquer comme publiée ?')">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="post_id" value="<?= $pub['id'] ?>">
                        <button type="submit" class="li-pub-action danger" title="Supprimer"
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
        <div class="li-pagination">
            <?php if ($page > 1): ?>
                <a href="/admin/index.php?module=linkedin&tab=<?= $activeTab ?>&p=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
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
                    <a href="/admin/index.php?module=linkedin&tab=<?= $activeTab ?>&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="/admin/index.php?module=linkedin&tab=<?= $activeTab ?>&p=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ===== Stratégie LinkedIn Immobilier ===== -->
<div class="li-strategy">
    <!-- Bonnes pratiques -->
    <div class="li-strategy-card">
        <h3><i class="fas fa-lightbulb"></i> Bonnes pratiques LinkedIn</h3>
        <ul>
            <li><strong>Hook :</strong> Les 3 premières lignes sont visibles avant "Voir plus" — accrochez immédiatement</li>
            <li><strong>Posts texte :</strong> Les posts sans lien obtiennent 2x plus de portée — mettez le lien en commentaire</li>
            <li><strong>Documents PDF :</strong> Les carrousels LinkedIn ont le meilleur taux d'engagement — format roi</li>
            <li><strong>Articles :</strong> Positionnement d'expert long format — analyse marché, retour d'expérience</li>
            <li><strong>Fréquence :</strong> 3-5 posts/semaine, publier entre 7h30-8h30 ou 17h-18h en semaine</li>
            <li><strong>Engagement :</strong> Répondez à TOUS les commentaires dans l'heure — l'algorithme récompense l'interaction</li>
            <li><strong>Méthode MERE :</strong> Miroir → Émotion → Réassurance → Exclusivité dans chaque publication</li>
        </ul>
    </div>

    <!-- Audiences cibles -->
    <div class="li-strategy-card">
        <h3><i class="fas fa-bullseye"></i> Audiences cibles Bordeaux</h3>
        <div class="li-audience-grid">
            <div class="li-audience-card">
                <div class="emoji">🏢</div>
                <h4>Cadres & dirigeants</h4>
                <p>Mutation pro, investissement locatif, résidence principale premium</p>
            </div>
            <div class="li-audience-card">
                <div class="emoji">💼</div>
                <h4>Entrepreneurs</h4>
                <p>Locaux commerciaux, investissement patrimonial, défiscalisation</p>
            </div>
            <div class="li-audience-card">
                <div class="emoji">🏗️</div>
                <h4>Pros de l'immobilier</h4>
                <p>Partenariats, co-mandats, networking local, partage d'expertise</p>
            </div>
            <div class="li-audience-card">
                <div class="emoji">🎓</div>
                <h4>Jeunes actifs</h4>
                <p>Premier achat, PTZ, quartiers dynamiques Bordeaux</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/layout.php';
?>