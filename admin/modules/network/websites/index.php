<?php
/**
 * ========================================
 * MODULE WEBSITES - AVEC GESTION DNS
 * ========================================
 * 
 * Fichier: /admin/modules/websites/index.php
 * Gère les sites web multi-instances
 * Inclut les instructions DNS pour domaines perso
 * 
 * ========================================
 */

// Éviter double session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ========================================
// CONFIGURATION SERVEUR (À PERSONNALISER)
// ========================================

// ⚠️ IMPORTANT: Remplacer par ton IP O2Switch réelle
// Tu la trouves dans cPanel → Informations générales
define('SERVER_IP', '91.134.XXX.XXX');
define('MAIN_DOMAIN', 'ecosysteme-immo.fr'); // Ton domaine principal

// ========================================
// CONNEXION BASE DE DONNÉES VIA CONFIG
// ========================================

$configPath = __DIR__ . '/../../../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Fichier de configuration non trouvé</div>';
    return;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Erreur BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

// ========================================
// VÉRIFICATION TABLE WEBSITES
// ========================================

$tableExists = $pdo->query("SHOW TABLES LIKE 'websites'")->fetch();

if (!$tableExists) {
    $pdo->exec("
        CREATE TABLE `websites` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `domain` VARCHAR(255) DEFAULT NULL,
            `domain_verified` TINYINT(1) DEFAULT 0,
            `domain_verified_at` DATETIME DEFAULT NULL,
            `logo` VARCHAR(500) DEFAULT NULL,
            `favicon` VARCHAR(500) DEFAULT NULL,
            `primary_color` VARCHAR(20) DEFAULT '#3B82F6',
            `secondary_color` VARCHAR(20) DEFAULT '#1E40AF',
            `font_family` VARCHAR(100) DEFAULT 'Inter',
            `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            `homepage_id` INT(11) DEFAULT NULL,
            `settings` LONGTEXT,
            `seo_title` VARCHAR(255) DEFAULT NULL,
            `seo_description` TEXT,
            `tracking_code` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_slug` (`slug`),
            INDEX `idx_domain` (`domain`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} else {
    // Ajouter colonnes domain_verified si manquantes
    try {
        $pdo->query("SELECT domain_verified FROM websites LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE websites ADD COLUMN `domain_verified` TINYINT(1) DEFAULT 0 AFTER `domain`");
        $pdo->exec("ALTER TABLE websites ADD COLUMN `domain_verified_at` DATETIME DEFAULT NULL AFTER `domain_verified`");
    }
}

// Vérifier/ajouter website_id dans pages
try {
    $pdo->query("SELECT website_id FROM pages LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE `pages` ADD COLUMN `website_id` INT DEFAULT NULL AFTER `id`");
        $pdo->exec("ALTER TABLE `pages` ADD INDEX `idx_website_id` (`website_id`)");
    } catch (PDOException $e2) {}
}

// ========================================
// VARIABLES ET CONFIGURATION
// ========================================

$action = $_GET['action'] ?? 'list';
$action = preg_replace('/[^a-z0-9_-]/i', '', $action);
$message = '';
$messageType = '';

$fonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Playfair Display', 'Merriweather', 'Source Sans Pro'];

// ========================================
// ACTION: DELETE
// ========================================

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE pages SET website_id = NULL WHERE website_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM websites WHERE id = ?");
        $stmt->execute([$id]);
        
        $message = '✓ Site supprimé avec succès';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = '✗ Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
    $action = 'list';
}

// ========================================
// ACTION: VERIFY DNS
// ========================================

if ($action === 'verify-dns' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT domain FROM websites WHERE id = ?");
    $stmt->execute([$id]);
    $site = $stmt->fetch();
    
    if ($site && $site['domain']) {
        // Vérification DNS simplifiée
        $domain = preg_replace('/^www\./', '', $site['domain']);
        $aRecords = @dns_get_record($domain, DNS_A);
        $verified = false;
        
        if ($aRecords) {
            foreach ($aRecords as $record) {
                if ($record['ip'] === SERVER_IP) {
                    $verified = true;
                    break;
                }
            }
        }
        
        if ($verified) {
            $stmt = $pdo->prepare("UPDATE websites SET domain_verified = 1, domain_verified_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $message = '✓ Domaine vérifié avec succès !';
            $messageType = 'success';
        } else {
            $message = '✗ Le domaine ne pointe pas encore vers notre serveur. Vérifiez vos DNS.';
            $messageType = 'danger';
        }
    }
    $action = 'list';
}

// ========================================
// ACTION: CREATE/EDIT (POST)
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $domain = trim($_POST['domain'] ?? '') ?: null;
    $logo = trim($_POST['logo'] ?? '') ?: null;
    $favicon = trim($_POST['favicon'] ?? '') ?: null;
    $primary_color = $_POST['primary_color'] ?? '#3B82F6';
    $secondary_color = $_POST['secondary_color'] ?? '#1E40AF';
    $font_family = trim($_POST['font_family'] ?? 'Inter');
    $status = $_POST['status'] ?? 'draft';
    $seo_title = trim($_POST['seo_title'] ?? '') ?: null;
    $seo_description = trim($_POST['seo_description'] ?? '') ?: null;
    $tracking_code = trim($_POST['tracking_code'] ?? '') ?: null;
    
    // Nettoyer le domaine
    if ($domain) {
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/\/.*$/', '', $domain);
        $domain = strtolower(trim($domain));
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
    }
    
    if (!empty($name)) {
        try {
            if ($id) {
                // Vérifier si le domaine a changé
                $stmt = $pdo->prepare("SELECT domain FROM websites WHERE id = ?");
                $stmt->execute([$id]);
                $oldSite = $stmt->fetch();
                $domainChanged = ($oldSite['domain'] !== $domain);
                
                $stmt = $pdo->prepare("
                    UPDATE websites SET 
                        name = ?, slug = ?, domain = ?, logo = ?, favicon = ?,
                        primary_color = ?, secondary_color = ?, font_family = ?, status = ?,
                        seo_title = ?, seo_description = ?, tracking_code = ?,
                        domain_verified = CASE WHEN ? THEN 0 ELSE domain_verified END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $domain, $logo, $favicon, $primary_color, $secondary_color, $font_family, $status, $seo_title, $seo_description, $tracking_code, $domainChanged, $id]);
                $message = '✓ Site mis à jour avec succès';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO websites (name, slug, domain, logo, favicon, primary_color, secondary_color, font_family, status, seo_title, seo_description, tracking_code)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $slug, $domain, $logo, $favicon, $primary_color, $secondary_color, $font_family, $status, $seo_title, $seo_description, $tracking_code]);
                $message = '✓ Site créé avec succès (ID: ' . $pdo->lastInsertId() . ')';
            }
            $messageType = 'success';
            $action = 'list';
        } catch (PDOException $e) {
            $message = '✗ Erreur: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = '✗ Le nom du site est obligatoire';
        $messageType = 'danger';
    }
}

// ========================================
// RÉCUPÉRATION DES DONNÉES
// ========================================

$sites = $pdo->query("
    SELECT w.*, 
           (SELECT COUNT(*) FROM pages WHERE website_id = w.id) as pages_count
    FROM websites w 
    ORDER BY w.created_at DESC
")->fetchAll();

$editSite = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM websites WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editSite = $stmt->fetch();
    if (!$editSite) {
        $action = 'list';
    }
}

if ($action === 'create') {
    $editSite = [
        'id' => '',
        'name' => '',
        'slug' => '',
        'domain' => '',
        'domain_verified' => 0,
        'logo' => '',
        'favicon' => '',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#1E40AF',
        'font_family' => 'Inter',
        'status' => 'draft',
        'seo_title' => '',
        'seo_description' => '',
        'tracking_code' => ''
    ];
}

$totalSites = count($sites);
?>

<!-- STYLES du module -->
<style>
.websites-module {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --light: #f8fafc;
    --border: #e2e8f0;
    --text: #1e293b;
    --text-sec: #64748b;
}

.websites-module .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.websites-module .header-bar h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.websites-module .btn {
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.websites-module .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
}

.websites-module .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99,102,241,0.4);
}

.websites-module .btn-secondary {
    background: white;
    border: 1px solid var(--border);
    color: var(--text);
}

.websites-module .btn-success {
    background: var(--success);
    color: white;
}

.websites-module .btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.websites-module .alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.websites-module .alert-success { background: #d1fae5; color: #065f46; }
.websites-module .alert-danger { background: #fee2e2; color: #991b1b; }
.websites-module .alert-info { background: #dbeafe; color: #1e40af; }
.websites-module .alert-warning { background: #fef3c7; color: #92400e; }

/* Sites Grid */
.websites-module .sites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 24px;
}

.websites-module .site-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.3s ease;
}

.websites-module .site-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--primary);
}

.websites-module .site-card-header {
    padding: 20px;
    background: linear-gradient(135deg, var(--light) 0%, white 100%);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 16px;
}

.websites-module .site-logo {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.websites-module .site-info { flex: 1; min-width: 0; }

.websites-module .site-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 4px 0;
}

.websites-module .site-urls {
    font-size: 0.8rem;
    color: var(--text-sec);
}

.websites-module .site-urls a {
    color: var(--primary);
    text-decoration: none;
}

.websites-module .site-urls .subdomain {
    display: block;
    color: var(--text-sec);
}

.websites-module .site-card-body { padding: 20px; }

.websites-module .site-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.websites-module .stat-item {
    text-align: center;
    padding: 12px;
    background: var(--light);
    border-radius: 10px;
}

.websites-module .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
}

.websites-module .stat-label {
    font-size: 0.7rem;
    color: var(--text-sec);
    text-transform: uppercase;
}

.websites-module .site-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
    gap: 8px;
}

.websites-module .site-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.websites-module .status-published { background: #d1fae5; color: #065f46; }
.websites-module .status-draft { background: #fef3c7; color: #92400e; }
.websites-module .status-archived { background: #e2e8f0; color: #64748b; }

.websites-module .dns-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.websites-module .dns-verified { background: #d1fae5; color: #065f46; }
.websites-module .dns-pending { background: #fef3c7; color: #92400e; }
.websites-module .dns-none { background: #e2e8f0; color: #64748b; }

.websites-module .site-card-actions {
    padding: 16px 20px;
    background: var(--light);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.websites-module .action-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-sec);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.websites-module .action-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.websites-module .action-btn.danger:hover {
    border-color: var(--danger);
    color: var(--danger);
}

/* Empty State */
.websites-module .empty-state {
    text-align: center;
    padding: 80px 40px;
    background: white;
    border-radius: 16px;
    border: 2px dashed var(--border);
}

.websites-module .empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
}

/* Form Styles */
.websites-module .card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 24px;
}

.websites-module .card-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
}

.websites-module .card-header.secondary {
    background: var(--light);
    color: var(--text);
    border-bottom: 1px solid var(--border);
}

.websites-module .card-body { padding: 24px; }

.websites-module .form-section { margin-bottom: 32px; }
.websites-module .form-section:last-child { margin-bottom: 0; }

.websites-module .form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
}

.websites-module .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.websites-module .form-row.three-cols {
    grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 768px) {
    .websites-module .form-row,
    .websites-module .form-row.three-cols {
        grid-template-columns: 1fr;
    }
}

.websites-module .form-group { margin-bottom: 20px; }
.websites-module .form-group.full-width { grid-column: span 2; }

.websites-module .form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.websites-module .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: white;
    font-family: inherit;
}

.websites-module .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.websites-module textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.websites-module .color-picker-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.websites-module .color-preview {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    border: 2px solid var(--border);
    cursor: pointer;
    padding: 0;
}

.websites-module .form-footer {
    padding: 20px 24px;
    background: var(--light);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid var(--border);
}

.websites-module .form-help {
    font-size: 0.8rem;
    color: var(--text-sec);
    margin-top: 6px;
}

/* DNS Configuration Box */
.websites-module .dns-config {
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 20px;
    margin-top: 16px;
}

.websites-module .dns-config.warning {
    background: #fefce8;
    border-color: #fde047;
}

.websites-module .dns-config h4 {
    margin: 0 0 12px 0;
    font-size: 0.95rem;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.websites-module .dns-record {
    background: white;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 12px 16px;
    margin: 8px 0;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.85rem;
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 8px;
}

.websites-module .dns-record-label {
    color: var(--text-sec);
    font-weight: 600;
}

.websites-module .dns-record-value {
    color: var(--primary);
    word-break: break-all;
}

.websites-module .copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: var(--light);
    border: 1px solid var(--border);
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.75rem;
    margin-left: 8px;
    transition: all 0.2s;
}

.websites-module .copy-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
</style>

<!-- CONTENU du module -->
<div class="websites-module">

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php
// ========================================
// VUE: LISTE DES SITES
// ========================================
if ($action === 'list'):
?>

<div class="header-bar">
    <div>
        <h2>🌐 Mes Sites</h2>
        <p style="color: var(--text-sec); margin-top: 4px; font-size: 14px;">
            Gérez vos sites web et configurez les domaines personnalisés
        </p>
    </div>
    <a href="?page=websites&action=create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouveau site
    </a>
</div>

<!-- Info box DNS -->
<div class="alert alert-info" style="margin-bottom: 24px;">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>Domaines personnalisés :</strong> 
        Chaque site dispose d'un sous-domaine gratuit (<code>slug.<?php echo MAIN_DOMAIN; ?></code>).
        Vous pouvez aussi connecter un domaine personnalisé en configurant les DNS.
    </div>
</div>

<?php if (empty($sites)): ?>
<div class="empty-state">
    <div class="empty-state-icon">🌐</div>
    <h3>Aucun site créé</h3>
    <p>Créez votre premier site pour commencer.</p>
    <a href="?page=websites&action=create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Créer un site
    </a>
</div>
<?php else: ?>
<div class="sites-grid">
    <?php foreach ($sites as $site): ?>
    <div class="site-card">
        <div class="site-card-header">
            <div class="site-logo" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($site['primary_color']); ?>, <?php echo htmlspecialchars($site['secondary_color']); ?>);">
                <?php echo strtoupper(substr($site['name'], 0, 2)); ?>
            </div>
            <div class="site-info">
                <h3 class="site-name"><?php echo htmlspecialchars($site['name']); ?></h3>
                <div class="site-urls">
                    <?php if ($site['domain']): ?>
                        <a href="https://<?php echo htmlspecialchars($site['domain']); ?>" target="_blank">
                            <i class="fas fa-globe"></i> <?php echo htmlspecialchars($site['domain']); ?>
                        </a>
                        <?php if ($site['domain_verified']): ?>
                            <span style="color: #10b981; margin-left: 4px;">✓</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="subdomain">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($site['slug']); ?>.<?php echo MAIN_DOMAIN; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="site-card-body">
            <div class="site-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $site['pages_count']; ?></div>
                    <div class="stat-label">Pages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">-</div>
                    <div class="stat-label">Visites</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">-</div>
                    <div class="stat-label">Leads</div>
                </div>
            </div>
            
            <div class="site-meta">
                <span class="site-status status-<?php echo $site['status']; ?>">
                    <?php 
                    $statusLabels = ['published' => '● Publié', 'draft' => '○ Brouillon', 'archived' => '◌ Archivé'];
                    echo $statusLabels[$site['status']] ?? $site['status'];
                    ?>
                </span>
                
                <?php if ($site['domain']): ?>
                    <?php if ($site['domain_verified']): ?>
                        <span class="dns-status dns-verified"><i class="fas fa-check"></i> DNS OK</span>
                    <?php else: ?>
                        <a href="?page=websites&action=verify-dns&id=<?php echo $site['id']; ?>" class="dns-status dns-pending" title="Cliquer pour vérifier">
                            <i class="fas fa-clock"></i> DNS en attente
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="dns-status dns-none">Sous-domaine</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="site-card-actions">
            <a href="?page=pages&website_id=<?php echo $site['id']; ?>" class="action-btn" title="Voir les pages">
                <i class="fas fa-file-alt"></i>
            </a>
            <a href="?page=builder&website_id=<?php echo $site['id']; ?>" class="action-btn" title="Créer une page">
                <i class="fas fa-plus"></i>
            </a>
            <a href="?page=websites&action=edit&id=<?php echo $site['id']; ?>" class="action-btn" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            <a href="?page=websites&action=delete&id=<?php echo $site['id']; ?>" 
               class="action-btn danger" 
               title="Supprimer"
               onclick="return confirm('Êtes-vous sûr ?')">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
// ========================================
// VUE: FORMULAIRE CRÉATION/ÉDITION
// ========================================
elseif (in_array($action, ['create', 'edit'])):
?>

<div class="header-bar">
    <div>
        <a href="?page=websites" style="color: var(--text-sec); text-decoration: none; font-size: 14px;">
            <i class="fas fa-arrow-left"></i> Retour aux sites
        </a>
        <h2 style="margin-top: 8px;">
            <?php echo $editSite['id'] ? '✏️ Modifier le site' : '➕ Nouveau site'; ?>
        </h2>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start;">
    <!-- Formulaire principal -->
    <div class="card">
        <div class="card-header">
            <?php echo $editSite['id'] ? htmlspecialchars($editSite['name']) : 'Créer un nouveau site'; ?>
        </div>
        
        <form method="POST" action="?page=websites">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editSite['id'] ?? ''); ?>">
            
            <div class="card-body">
                <!-- Informations générales -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-info-circle"></i> Informations générales
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom du site *</label>
                            <input type="text" name="name" class="form-control" id="siteName"
                                   value="<?php echo htmlspecialchars($editSite['name'] ?? ''); ?>" 
                                   placeholder="Mon site immobilier" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Identifiant (slug)</label>
                            <input type="text" name="slug" class="form-control" id="siteSlug"
                                   value="<?php echo htmlspecialchars($editSite['slug'] ?? ''); ?>" 
                                   placeholder="auto-généré">
                            <p class="form-help">Sous-domaine : <strong id="subdomainPreview"><?php echo $editSite['slug'] ?: 'mon-site'; ?></strong>.<?php echo MAIN_DOMAIN; ?></p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Domaine personnalisé <small>(optionnel)</small></label>
                            <input type="text" name="domain" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSite['domain'] ?? ''); ?>" 
                                   placeholder="www.monsite.com">
                            <p class="form-help">Laissez vide pour utiliser uniquement le sous-domaine gratuit</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="draft" <?php echo ($editSite['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>📝 Brouillon</option>
                                <option value="published" <?php echo ($editSite['status'] ?? '') === 'published' ? 'selected' : ''; ?>>✅ Publié</option>
                                <option value="archived" <?php echo ($editSite['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>📦 Archivé</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Apparence -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-palette"></i> Apparence
                    </h3>
                    
                    <div class="form-row three-cols">
                        <div class="form-group">
                            <label class="form-label">Couleur primaire</label>
                            <div class="color-picker-group">
                                <input type="color" id="primary_color" name="primary_color" class="color-preview" 
                                       value="<?php echo htmlspecialchars($editSite['primary_color'] ?? '#3B82F6'); ?>">
                                <input type="text" class="form-control" style="flex:1" id="primary_color_text"
                                       value="<?php echo htmlspecialchars($editSite['primary_color'] ?? '#3B82F6'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Couleur secondaire</label>
                            <div class="color-picker-group">
                                <input type="color" id="secondary_color" name="secondary_color" class="color-preview" 
                                       value="<?php echo htmlspecialchars($editSite['secondary_color'] ?? '#1E40AF'); ?>">
                                <input type="text" class="form-control" style="flex:1" id="secondary_color_text"
                                       value="<?php echo htmlspecialchars($editSite['secondary_color'] ?? '#1E40AF'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Police</label>
                            <select name="font_family" class="form-control">
                                <?php foreach ($fonts as $font): ?>
                                <option value="<?php echo $font; ?>" <?php echo ($editSite['font_family'] ?? 'Inter') === $font ? 'selected' : ''; ?>>
                                    <?php echo $font; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">URL du logo</label>
                            <input type="url" name="logo" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSite['logo'] ?? ''); ?>" 
                                   placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL du favicon</label>
                            <input type="url" name="favicon" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSite['favicon'] ?? ''); ?>" 
                                   placeholder="https://...">
                        </div>
                    </div>
                </div>
                
                <!-- SEO -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-search"></i> SEO
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">Titre SEO</label>
                        <input type="text" name="seo_title" class="form-control" 
                               value="<?php echo htmlspecialchars($editSite['seo_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description SEO</label>
                        <textarea name="seo_description" class="form-control"><?php echo htmlspecialchars($editSite['seo_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Tracking -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-chart-line"></i> Tracking
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">Code de tracking</label>
                        <textarea name="tracking_code" class="form-control" style="font-family: monospace; font-size: 12px;"
                                  placeholder="<!-- Google Analytics, Facebook Pixel, etc. -->"><?php echo htmlspecialchars($editSite['tracking_code'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <a href="?page=websites" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $editSite['id'] ? 'Mettre à jour' : 'Créer le site'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Panneau DNS -->
    <div>
        <?php if ($editSite['id'] && $editSite['domain']): ?>
        <div class="card">
            <div class="card-header secondary">
                <i class="fas fa-globe"></i> Configuration DNS
            </div>
            <div class="card-body">
                <?php if ($editSite['domain_verified']): ?>
                <div class="dns-config">
                    <h4><i class="fas fa-check-circle" style="color: #10b981;"></i> Domaine vérifié</h4>
                    <p style="font-size: 0.9rem; color: var(--text-sec);">
                        Le domaine <strong><?php echo htmlspecialchars($editSite['domain']); ?></strong> est correctement configuré.
                    </p>
                </div>
                <?php else: ?>
                <div class="dns-config warning">
                    <h4><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Configuration requise</h4>
                    <p style="font-size: 0.85rem; color: var(--text-sec); margin-bottom: 16px;">
                        Connectez-vous à votre registrar de domaine et ajoutez ces enregistrements DNS :
                    </p>
                    
                    <strong style="font-size: 0.8rem; color: var(--text);">Option 1 : Enregistrement A</strong>
                    <div class="dns-record">
                        <span class="dns-record-label">Type</span>
                        <span class="dns-record-value">A</span>
                        <span class="dns-record-label">Nom</span>
                        <span class="dns-record-value">@ (ou vide)</span>
                        <span class="dns-record-label">Valeur</span>
                        <span class="dns-record-value">
                            <?php echo SERVER_IP; ?>
                            <button type="button" class="copy-btn" onclick="copyToClipboard('<?php echo SERVER_IP; ?>')">
                                <i class="fas fa-copy"></i> Copier
                            </button>
                        </span>
                    </div>
                    
                    <strong style="font-size: 0.8rem; color: var(--text); margin-top: 12px; display: block;">Pour www :</strong>
                    <div class="dns-record">
                        <span class="dns-record-label">Type</span>
                        <span class="dns-record-value">A</span>
                        <span class="dns-record-label">Nom</span>
                        <span class="dns-record-value">www</span>
                        <span class="dns-record-label">Valeur</span>
                        <span class="dns-record-value"><?php echo SERVER_IP; ?></span>
                    </div>
                    
                    <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 16px;">
                        ⏱️ La propagation DNS peut prendre jusqu'à 48h.
                    </p>
                    
                    <a href="?page=websites&action=verify-dns&id=<?php echo $editSite['id']; ?>" class="btn btn-success btn-sm" style="margin-top: 12px; width: 100%; justify-content: center;">
                        <i class="fas fa-sync"></i> Vérifier maintenant
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($editSite['id']): ?>
        <div class="card">
            <div class="card-header secondary">
                <i class="fas fa-globe"></i> Domaine
            </div>
            <div class="card-body">
                <p style="font-size: 0.9rem; color: var(--text-sec);">
                    Ce site utilise uniquement le sous-domaine gratuit :
                </p>
                <p style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-top: 8px;">
                    <?php echo htmlspecialchars($editSite['slug']); ?>.<?php echo MAIN_DOMAIN; ?>
                </p>
                <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 12px;">
                    💡 Ajoutez un domaine personnalisé dans le formulaire pour connecter votre propre domaine.
                </p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header secondary">
                <i class="fas fa-info-circle"></i> Information
            </div>
            <div class="card-body">
                <p style="font-size: 0.9rem; color: var(--text-sec);">
                    Après création, vous pourrez configurer un domaine personnalisé.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-slug
document.getElementById('siteName')?.addEventListener('input', function() {
    const slugInput = document.getElementById('siteSlug');
    const preview = document.getElementById('subdomainPreview');
    if (slugInput && (!slugInput.value || slugInput.dataset.auto === 'true')) {
        const slug = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        slugInput.value = slug;
        slugInput.dataset.auto = 'true';
        if (preview) preview.textContent = slug || 'mon-site';
    }
});

document.getElementById('siteSlug')?.addEventListener('input', function() {
    this.dataset.auto = 'false';
    const preview = document.getElementById('subdomainPreview');
    if (preview) preview.textContent = this.value || 'mon-site';
});

// Color sync
['primary_color', 'secondary_color'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function() {
        document.getElementById(id + '_text').value = this.value;
    });
    document.getElementById(id + '_text')?.addEventListener('input', function() {
        document.getElementById(id).value = this.value;
    });
});

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copié !');
    });
}
</script>

<?php endif; ?>

</div>