<?php
// ======================================================
// Module RÉSEAUX SOCIAUX - Hub Central V2
// /admin/modules/reseaux-sociaux/index.php
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

$page_title = "Réseaux Sociaux";
$current_module = "reseaux-sociaux";

// ====================================================
// RÉCUPÉRATION DES DONNÉES
// ====================================================

// Plateformes configurées
$platforms = [
    'facebook'  => ['name' => 'Facebook',  'icon' => 'fab fa-facebook-f',  'color' => '#1877F2', 'gradient' => 'linear-gradient(135deg, #1877F2, #0d5bbd)', 'module' => 'facebook'],
    'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram',   'color' => '#E1306C', 'gradient' => 'linear-gradient(135deg, #833AB4, #FD1D1D, #F77737)', 'module' => 'instagram'],
    'linkedin'  => ['name' => 'LinkedIn',  'icon' => 'fab fa-linkedin-in', 'color' => '#0A66C2', 'gradient' => 'linear-gradient(135deg, #0A66C2, #004182)', 'module' => 'linkedin'],
    'tiktok'    => ['name' => 'TikTok',    'icon' => 'fab fa-tiktok',      'color' => '#000000', 'gradient' => 'linear-gradient(135deg, #000000, #25F4EE)', 'module' => 'tiktok'],
    'youtube'   => ['name' => 'YouTube',   'icon' => 'fab fa-youtube',     'color' => '#FF0000', 'gradient' => 'linear-gradient(135deg, #FF0000, #cc0000)', 'module' => 'youtube'],
];

// Stats par plateforme
$platformStats = [];
foreach ($platforms as $key => $p) {
    $platformStats[$key] = ['total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0];
}

$globalStats = ['total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0, 'this_week' => 0, 'this_month' => 0];

try {
    $stmt = $pdo->query("
        SELECT platform, status, COUNT(*) as total 
        FROM social_posts 
        GROUP BY platform, status
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pf = strtolower($row['platform']);
        $st = strtolower($row['status']);
        $count = (int)$row['total'];
        
        if (isset($platformStats[$pf])) {
            $platformStats[$pf]['total'] += $count;
            if (isset($platformStats[$pf][$st])) {
                $platformStats[$pf][$st] = $count;
            }
        }
        $globalStats['total'] += $count;
        if (isset($globalStats[$st])) {
            $globalStats[$st] += $count;
        }
    }
} catch (Exception $e) {}

// Stats calendrier (cette semaine / ce mois)
try {
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN scheduled_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week,
            SUM(CASE WHEN MONTH(scheduled_at) = MONTH(CURDATE()) AND YEAR(scheduled_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_month
        FROM social_posts WHERE status = 'scheduled'
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $globalStats['this_week'] = (int)($row['this_week'] ?? 0);
    $globalStats['this_month'] = (int)($row['this_month'] ?? 0);
} catch (Exception $e) {}

// TikTok scripts count
$tiktokScripts = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM tiktok_scripts");
    $tiktokScripts = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Comptes connectés
$connectedAccounts = [];
try {
    $stmt = $pdo->query("SELECT * FROM social_accounts WHERE is_active = 1 ORDER BY platform");
    $connectedAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$connectedMap = [];
foreach ($connectedAccounts as $acc) {
    $connectedMap[strtolower($acc['platform'])] = $acc;
}

// Prochains posts planifiés (toutes plateformes)
$upcomingPosts = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM social_posts 
        WHERE status = 'scheduled' AND scheduled_at >= NOW() 
        ORDER BY scheduled_at ASC 
        LIMIT 8
    ");
    $upcomingPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Derniers posts publiés
$recentPosts = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM social_posts 
        WHERE status = 'published' 
        ORDER BY published_at DESC 
        LIMIT 8
    ");
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Journal éditorial stats
$journalStats = ['total' => 0, 'pending' => 0];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM editorial_journal GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $journalStats['total'] += (int)$row['total'];
        if ($row['status'] === 'pending' || $row['status'] === 'planned') {
            $journalStats['pending'] += (int)$row['total'];
        }
    }
} catch (Exception $e) {}

ob_start();
?>

<style>
/* ========== Hub Réseaux Sociaux V2 ========== */

.hub-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 16px;
    padding: 36px;
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}

.hub-hero::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -60px;
    width: 260px;
    height: 260px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}

.hub-hero::after {
    content: '';
    position: absolute;
    bottom: -40px;
    left: 30%;
    width: 180px;
    height: 180px;
    background: rgba(255,255,255,0.02);
    border-radius: 50%;
}

.hub-hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.hub-hero h1 {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.hub-hero h1 i {
    font-size: 1.6rem;
    opacity: 0.8;
}

.hub-hero p {
    margin: 0;
    opacity: 0.7;
    font-size: 0.92rem;
}

.hub-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.hub-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.hub-hero-btn.primary {
    background: rgba(255,255,255,0.95);
    color: #1a1a2e;
}

.hub-hero-btn.primary:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.hub-hero-btn.ghost {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
}

.hub-hero-btn.ghost:hover {
    background: rgba(255,255,255,0.2);
}

/* Global stats row inside hero */
.hub-global-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    position: relative;
    z-index: 1;
}

.hub-gstat {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 14px 16px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.08);
}

.hub-gstat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}

.hub-gstat-label {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.55);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Platform cards grid */
.hub-platforms {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.hub-platform-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #eee;
    transition: all 0.25s;
}

.hub-platform-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.hub-pcard-header {
    padding: 20px 20px 16px 20px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.hub-pcard-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 1.1rem;
}

.hub-pcard-name i {
    font-size: 1.3rem;
}

.hub-pcard-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.72rem;
    font-weight: 600;
}

.hub-pcard-status.connected {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

.hub-pcard-status.disconnected {
    background: rgba(0,0,0,0.15);
    color: rgba(255,255,255,0.7);
}

.hub-pcard-body {
    padding: 16px 20px;
}

.hub-pcard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 14px;
}

.hub-pcard-stat {
    text-align: center;
    padding: 8px 4px;
    background: #f8f9fa;
    border-radius: 8px;
}

.hub-pcard-stat-val {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
}

.hub-pcard-stat-lbl {
    font-size: 0.68rem;
    color: #999;
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.hub-pcard-actions {
    display: flex;
    gap: 8px;
}

.hub-pcard-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.82rem;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.hub-pcard-btn.view {
    background: #f0f0f0;
    color: #555;
}

.hub-pcard-btn.view:hover {
    background: #e0e0e0;
    color: #333;
}

.hub-pcard-btn.create {
    color: #fff;
}

.hub-pcard-btn.create:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Two-column layout */
.hub-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 28px;
}

.hub-col-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #eee;
    overflow: hidden;
}

.hub-col-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.hub-col-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1a1a2e;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.hub-col-badge {
    background: #f0f0f0;
    color: #666;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.hub-col-body {
    padding: 16px 22px;
}

/* Post items in columns */
.hub-post-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.hub-post-item:last-child {
    border-bottom: none;
}

.hub-post-platform {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.hub-post-info {
    flex: 1;
    min-width: 0;
}

.hub-post-title {
    font-weight: 600;
    font-size: 0.87rem;
    color: #1a1a2e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hub-post-date {
    font-size: 0.75rem;
    color: #999;
    margin-top: 2px;
}

.hub-post-status {
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    flex-shrink: 0;
}

.hub-post-status.scheduled {
    background: #e6f2ff;
    color: #3b82f6;
}

.hub-post-status.published {
    background: #e8faf0;
    color: #16a34a;
}

.hub-post-empty {
    text-align: center;
    padding: 30px 16px;
    color: #bbb;
}

.hub-post-empty i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

/* Quick access grid */
.hub-quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}

.hub-quick-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #eee;
    text-decoration: none;
    color: #555;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.hub-quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-color: #ccc;
}

.hub-quick-card i {
    font-size: 1.4rem;
}

.hub-quick-card span {
    font-weight: 600;
    font-size: 0.85rem;
}

.hub-quick-card small {
    font-size: 0.72rem;
    color: #aaa;
}

/* Strategy tips */
.hub-tips {
    background: linear-gradient(135deg, #f8f9fa, #eef2f7);
    border-radius: 14px;
    padding: 28px;
    border: 1px solid #e0e8f0;
    margin-bottom: 28px;
}

.hub-tips h3 {
    font-size: 1.1rem;
    color: #1a1a2e;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.hub-tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
}

.hub-tip {
    padding: 14px 16px;
    background: #fff;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #555;
    line-height: 1.5;
    border-left: 3px solid #0A66C2;
}

.hub-tip strong {
    color: #1a1a2e;
}

.hub-tip .tip-platform {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 6px;
}

.hub-tip .tip-platform.fb { background: #e8f0fe; color: #1877F2; }
.hub-tip .tip-platform.ig { background: #fce4ec; color: #E1306C; }
.hub-tip .tip-platform.li { background: #e3f0fa; color: #0A66C2; }
.hub-tip .tip-platform.tk { background: #e0f7fa; color: #000; }
.hub-tip .tip-platform.all { background: #f5f0ff; color: #7c3aed; }

/* Responsive */
@media (max-width: 900px) {
    .hub-columns {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hub-hero {
        padding: 24px;
    }
    .hub-hero-top {
        flex-direction: column;
        text-align: center;
    }
    .hub-hero-actions {
        width: 100%;
        justify-content: center;
    }
    .hub-platforms {
        grid-template-columns: 1fr;
    }
    .hub-global-stats {
        grid-template-columns: repeat(3, 1fr);
    }
    .hub-quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- ===== Hero Banner ===== -->
<div class="hub-hero">
    <div class="hub-hero-top">
        <div>
            <h1><i class="fas fa-share-alt"></i> Réseaux Sociaux</h1>
            <p>Tableau de bord centralisé — gérez toutes vos plateformes depuis un seul endroit</p>
        </div>
        <div class="hub-hero-actions">
            <a href="/admin/index.php?module=journal" class="hub-hero-btn primary">
                <i class="fas fa-calendar-alt"></i> Journal Éditorial
            </a>
            <a href="/admin/index.php?module=strategie-contenu" class="hub-hero-btn ghost">
                <i class="fas fa-chess"></i> Stratégie Contenu
            </a>
        </div>
    </div>

    <!-- Stats globales dans le hero -->
    <div class="hub-global-stats">
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['total'] ?></div>
            <div class="hub-gstat-label">Publications</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['published'] ?></div>
            <div class="hub-gstat-label">Publiées</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['scheduled'] ?></div>
            <div class="hub-gstat-label">Planifiées</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['draft'] ?></div>
            <div class="hub-gstat-label">Brouillons</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['this_week'] ?></div>
            <div class="hub-gstat-label">Cette semaine</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $globalStats['this_month'] ?></div>
            <div class="hub-gstat-label">Ce mois</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $tiktokScripts ?></div>
            <div class="hub-gstat-label">Scripts TikTok</div>
        </div>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= $journalStats['pending'] ?></div>
            <div class="hub-gstat-label">À rédiger</div>
        </div>
    </div>
</div>

<!-- ===== Cartes Plateformes ===== -->
<div class="hub-platforms">
    <?php foreach ($platforms as $key => $pf):
        $stats = $platformStats[$key];
        $account = $connectedMap[$key] ?? null;
        $isConnected = !empty($account);
    ?>
        <div class="hub-platform-card">
            <div class="hub-pcard-header" style="background: <?= $pf['gradient'] ?>;">
                <div class="hub-pcard-name">
                    <i class="<?= $pf['icon'] ?>"></i>
                    <?= $pf['name'] ?>
                </div>
                <span class="hub-pcard-status <?= $isConnected ? 'connected' : 'disconnected' ?>">
                    <i class="fas fa-<?= $isConnected ? 'check-circle' : 'unlink' ?>"></i>
                    <?= $isConnected ? 'Connecté' : 'Non connecté' ?>
                </span>
            </div>
            <div class="hub-pcard-body">
                <div class="hub-pcard-stats">
                    <div class="hub-pcard-stat">
                        <div class="hub-pcard-stat-val"><?= $stats['total'] ?></div>
                        <div class="hub-pcard-stat-lbl">Posts</div>
                    </div>
                    <div class="hub-pcard-stat">
                        <div class="hub-pcard-stat-val"><?= $stats['published'] ?></div>
                        <div class="hub-pcard-stat-lbl">Publiés</div>
                    </div>
                    <div class="hub-pcard-stat">
                        <div class="hub-pcard-stat-val"><?= $stats['scheduled'] ?></div>
                        <div class="hub-pcard-stat-lbl">Planifiés</div>
                    </div>
                </div>
                <div class="hub-pcard-actions">
                    <a href="/admin/index.php?module=<?= $pf['module'] ?>" class="hub-pcard-btn view">
                        <i class="fas fa-list"></i> Voir
                    </a>
                    <a href="/admin/index.php?module=<?= $pf['module'] ?>&action=create" 
                       class="hub-pcard-btn create" style="background: <?= $pf['color'] ?>;">
                        <i class="fas fa-plus"></i> Créer
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===== Accès rapides ===== -->
<div class="hub-quick-grid">
    <a href="/admin/index.php?module=journal" class="hub-quick-card">
        <i class="fas fa-calendar-alt" style="color: #7c3aed;"></i>
        <span>Journal Éditorial</span>
        <small><?= $journalStats['total'] ?> idées de contenu</small>
    </a>
    <a href="/admin/index.php?module=strategie-contenu" class="hub-quick-card">
        <i class="fas fa-chess" style="color: #e67e22;"></i>
        <span>Matrice Stratégique</span>
        <small>Planification par cible</small>
    </a>
    <a href="/admin/index.php?module=tiktok&tab=scripts" class="hub-quick-card">
        <i class="fas fa-scroll" style="color: #000;"></i>
        <span>Scripts TikTok</span>
        <small><?= $tiktokScripts ?> scripts créés</small>
    </a>
    <a href="/admin/index.php?module=articles" class="hub-quick-card">
        <i class="fas fa-blog" style="color: #16a34a;"></i>
        <span>Blog / Articles</span>
        <small>Contenu SEO</small>
    </a>
    <a href="/admin/index.php?module=gmb" class="hub-quick-card">
        <i class="fas fa-map-marker-alt" style="color: #4285f4;"></i>
        <span>Google My Business</span>
        <small>Posts GMB locaux</small>
    </a>
    <a href="/admin/index.php?module=ia" class="hub-quick-card">
        <i class="fas fa-robot" style="color: #833AB4;"></i>
        <span>Générateur IA</span>
        <small>Créer avec l'IA</small>
    </a>
</div>

<!-- ===== Deux colonnes : Planifiés + Publiés ===== -->
<div class="hub-columns">
    <!-- Prochains posts planifiés -->
    <div class="hub-col-card">
        <div class="hub-col-header">
            <h3 class="hub-col-title">
                <i class="fas fa-clock" style="color: #3b82f6;"></i> Prochaines publications
            </h3>
            <span class="hub-col-badge"><?= count($upcomingPosts) ?></span>
        </div>
        <div class="hub-col-body">
            <?php if (empty($upcomingPosts)): ?>
                <div class="hub-post-empty">
                    <i class="fas fa-calendar-plus"></i>
                    Aucune publication planifiée.<br>
                    <small>Planifiez vos prochains posts depuis chaque plateforme.</small>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingPosts as $post):
                    $pf = strtolower($post['platform'] ?? '');
                    $pfInfo = $platforms[$pf] ?? ['color' => '#888', 'icon' => 'fas fa-share-alt'];
                    $postTitle = mb_substr(strip_tags($post['content'] ?? $post['title'] ?? 'Sans titre'), 0, 50);
                    $schedDate = !empty($post['scheduled_at']) ? date('d/m H:i', strtotime($post['scheduled_at'])) : '-';
                ?>
                    <div class="hub-post-item">
                        <div class="hub-post-platform" style="background: <?= $pfInfo['color'] ?>;">
                            <i class="<?= $pfInfo['icon'] ?>"></i>
                        </div>
                        <div class="hub-post-info">
                            <div class="hub-post-title"><?= htmlspecialchars($postTitle) ?></div>
                            <div class="hub-post-date"><i class="fas fa-clock"></i> <?= $schedDate ?></div>
                        </div>
                        <span class="hub-post-status scheduled">Planifié</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Derniers posts publiés -->
    <div class="hub-col-card">
        <div class="hub-col-header">
            <h3 class="hub-col-title">
                <i class="fas fa-check-circle" style="color: #16a34a;"></i> Dernières publications
            </h3>
            <span class="hub-col-badge"><?= count($recentPosts) ?></span>
        </div>
        <div class="hub-col-body">
            <?php if (empty($recentPosts)): ?>
                <div class="hub-post-empty">
                    <i class="fas fa-inbox"></i>
                    Aucune publication récente.<br>
                    <small>Vos dernières publications apparaîtront ici.</small>
                </div>
            <?php else: ?>
                <?php foreach ($recentPosts as $post):
                    $pf = strtolower($post['platform'] ?? '');
                    $pfInfo = $platforms[$pf] ?? ['color' => '#888', 'icon' => 'fas fa-share-alt'];
                    $postTitle = mb_substr(strip_tags($post['content'] ?? $post['title'] ?? 'Sans titre'), 0, 50);
                    $pubDate = !empty($post['published_at']) ? date('d/m H:i', strtotime($post['published_at'])) : '-';
                ?>
                    <div class="hub-post-item">
                        <div class="hub-post-platform" style="background: <?= $pfInfo['color'] ?>;">
                            <i class="<?= $pfInfo['icon'] ?>"></i>
                        </div>
                        <div class="hub-post-info">
                            <div class="hub-post-title"><?= htmlspecialchars($postTitle) ?></div>
                            <div class="hub-post-date"><i class="fas fa-check-circle" style="color:#16a34a;"></i> <?= $pubDate ?></div>
                        </div>
                        <span class="hub-post-status published">Publié</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== Conseils Stratégiques ===== -->
<div class="hub-tips">
    <h3><i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Stratégie Réseaux Sociaux — Eduardo De Sul, Bordeaux</h3>
    <div class="hub-tips-grid">
        <div class="hub-tip">
            <div class="tip-platform all">Stratégie globale</div>
            <strong>Régularité :</strong> Publier minimum 3-5 fois/semaine sur chaque plateforme active. La constance bat la perfection.
        </div>
        <div class="hub-tip">
            <div class="tip-platform fb">Facebook</div>
            <strong>Communauté locale :</strong> Groupes Bordeaux immobilier, événements quartier, témoignages clients — format vidéo natif privilégié.
        </div>
        <div class="hub-tip">
            <div class="tip-platform ig">Instagram</div>
            <strong>Visuel premium :</strong> Reels visites virtuelles, carrousels quartiers, stories coulisses — publier entre 7h-9h ou 18h-21h.
        </div>
        <div class="hub-tip">
            <div class="tip-platform li">LinkedIn</div>
            <strong>Expert immobilier :</strong> Articles marché bordelais, carrousels PDF, posts d'opinion — ne jamais mettre de lien dans le post.
        </div>
        <div class="hub-tip">
            <div class="tip-platform tk">TikTok</div>
            <strong>Authenticité :</strong> Coulisses métier, réponses aux questions, visites express 30s — hook puissant dans les 2 premières secondes.
        </div>
        <div class="hub-tip">
            <div class="tip-platform all">Méthode MERE</div>
            <strong>Sur toutes les plateformes :</strong> Miroir (montrer le problème) → Émotion (créer l'envie) → Réassurance (prouver) → Exclusivité (appel à l'action).
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/layout.php';
?>