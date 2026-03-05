<?php
/**
 * ========================================
 * MODULE MENUS - GESTION HEADER & FOOTER
 * ========================================
 * 
 * Fichier: /admin/modules/menus/index.php
 * Gestion des liens de navigation et footer
 * 
 * ========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion BDD
$configPath = __DIR__ . '/../../../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Config non trouvée</div>';
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
// CRÉATION DES TABLES
// ========================================

// Table des menus
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `menus` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `description` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Table des items de menu
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `menu_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `menu_id` INT NOT NULL,
        `parent_id` INT DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `url` VARCHAR(500) NOT NULL,
        `target` ENUM('_self', '_blank') DEFAULT '_self',
        `icon` VARCHAR(100),
        `css_class` VARCHAR(100),
        `position` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
        INDEX `idx_menu_position` (`menu_id`, `position`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Table des paramètres du site (pour footer, réseaux sociaux, etc.)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `site_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `setting_group` VARCHAR(50) DEFAULT 'general',
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Insérer les menus par défaut s'ils n'existent pas
$existingMenus = $pdo->query("SELECT slug FROM menus")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('header-main', $existingMenus)) {
    $pdo->exec("INSERT INTO menus (name, slug, description) VALUES ('Menu Principal', 'header-main', 'Navigation principale du site')");
}
if (!in_array('footer-col1', $existingMenus)) {
    $pdo->exec("INSERT INTO menus (name, slug, description) VALUES ('Footer - Services', 'footer-col1', 'Première colonne du footer')");
}
if (!in_array('footer-col2', $existingMenus)) {
    $pdo->exec("INSERT INTO menus (name, slug, description) VALUES ('Footer - Ressources', 'footer-col2', 'Deuxième colonne du footer')");
}
if (!in_array('footer-col3', $existingMenus)) {
    $pdo->exec("INSERT INTO menus (name, slug, description) VALUES ('Footer - Légal', 'footer-col3', 'Liens légaux du footer')");
}

// ========================================
// VARIABLES
// ========================================

$action = $_GET['action'] ?? 'list';
$menuId = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : null;
$message = '';
$messageType = '';

// ========================================
// TRAITEMENT AJAX
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $ajaxAction = $_POST['ajax_action'] ?? '';
        
        switch ($ajaxAction) {
            case 'reorder':
                $items = json_decode($_POST['items'], true);
                foreach ($items as $pos => $itemId) {
                    $pdo->prepare("UPDATE menu_items SET position = ? WHERE id = ?")->execute([$pos, $itemId]);
                }
                echo json_encode(['success' => true]);
                break;
                
            case 'toggle_active':
                $itemId = (int)$_POST['item_id'];
                $pdo->prepare("UPDATE menu_items SET is_active = NOT is_active WHERE id = ?")->execute([$itemId]);
                echo json_encode(['success' => true]);
                break;
                
            case 'delete_item':
                $itemId = (int)$_POST['item_id'];
                $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$itemId]);
                echo json_encode(['success' => true]);
                break;
                
            case 'save_settings':
                $settings = json_decode($_POST['settings'], true);
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                                       VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                foreach ($settings as $key => $value) {
                    $group = strpos($key, 'footer_') === 0 ? 'footer' : (strpos($key, 'social_') === 0 ? 'social' : 'general');
                    $stmt->execute([$key, $value, $group, $value]);
                }
                echo json_encode(['success' => true]);
                break;
                
            default:
                echo json_encode(['error' => 'Action inconnue']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// TRAITEMENT POST (ajout/modif item)
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    try {
        if (isset($_POST['add_item'])) {
            $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, target, icon, css_class, position) 
                                   VALUES (?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(p.position), 0) + 1 FROM menu_items p WHERE p.menu_id = ?))");
            $stmt->execute([
                $_POST['menu_id'],
                trim($_POST['title']),
                trim($_POST['url']),
                $_POST['target'] ?? '_self',
                trim($_POST['icon'] ?? ''),
                trim($_POST['css_class'] ?? ''),
                $_POST['menu_id']
            ]);
            $message = '✓ Lien ajouté';
            $messageType = 'success';
        }
        
        if (isset($_POST['update_item'])) {
            $stmt = $pdo->prepare("UPDATE menu_items SET title = ?, url = ?, target = ?, icon = ?, css_class = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['title']),
                trim($_POST['url']),
                $_POST['target'] ?? '_self',
                trim($_POST['icon'] ?? ''),
                trim($_POST['css_class'] ?? ''),
                (int)$_POST['item_id']
            ]);
            $message = '✓ Lien modifié';
            $messageType = 'success';
        }
        
        if (isset($_POST['save_footer_settings'])) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'footer') ON DUPLICATE KEY UPDATE setting_value = ?");
            
            $fields = ['footer_company_name', 'footer_description', 'footer_address', 'footer_phone', 'footer_email',
                      'footer_copyright', 'social_facebook', 'social_instagram', 'social_linkedin', 'social_youtube'];
            
            foreach ($fields as $field) {
                $value = trim($_POST[$field] ?? '');
                $stmt->execute([$field, $value, $value]);
            }
            
            $message = '✓ Paramètres du footer sauvegardés';
            $messageType = 'success';
        }
        
        if (isset($_POST['create_menu'])) {
            $name = trim($_POST['menu_name']);
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $stmt = $pdo->prepare("INSERT INTO menus (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, trim($_POST['menu_description'] ?? '')]);
            $message = '✓ Menu créé';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = '✗ Erreur: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Charger les menus
$menus = $pdo->query("SELECT * FROM menus ORDER BY name")->fetchAll();

// Charger les paramètres
$settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$settings = [];
foreach ($settingsRaw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

?>

<style>
.menus-module {
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

.menus-module * { box-sizing: border-box; }

.menus-module .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.menus-module .header-bar h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.menus-module .tabs-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--border);
    padding-bottom: 0;
    overflow-x: auto;
}

.menus-module .tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-sec);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    white-space: nowrap;
}

.menus-module .tab-btn:hover { color: var(--primary); }
.menus-module .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

.menus-module .tab-content { display: none; }
.menus-module .tab-content.active { display: block; }

.menus-module .card {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--border);
    margin-bottom: 24px;
}

.menus-module .card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.menus-module .card-body { padding: 20px; }

.menus-module .btn {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.menus-module .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
.menus-module .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.menus-module .btn-secondary { background: white; border: 1px solid var(--border); color: var(--text); }
.menus-module .btn-success { background: var(--success); color: white; }
.menus-module .btn-danger { background: var(--danger); color: white; }
.menus-module .btn-sm { padding: 6px 12px; font-size: 13px; }

.menus-module .alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.menus-module .alert-success { background: #d1fae5; color: #065f46; }
.menus-module .alert-danger { background: #fee2e2; color: #991b1b; }

.menus-module .form-group { margin-bottom: 16px; }
.menus-module .form-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--text); }
.menus-module .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}
.menus-module .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.menus-module .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.menus-module .form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
@media (max-width: 768px) { 
    .menus-module .form-row, .menus-module .form-row-3 { grid-template-columns: 1fr; } 
}
.menus-module .form-help { font-size: 12px; color: var(--text-sec); margin-top: 4px; }

/* Menu items list */
.menus-module .menu-items-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menus-module .menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--light);
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: grab;
    transition: all 0.2s;
}

.menus-module .menu-item:hover { border-color: var(--primary); }
.menus-module .menu-item.dragging { opacity: 0.5; }
.menus-module .menu-item.inactive { opacity: 0.5; background: #fef2f2; }

.menus-module .menu-item .drag-handle {
    color: var(--text-sec);
    cursor: grab;
}

.menus-module .menu-item .item-info { flex: 1; }
.menus-module .menu-item .item-title { font-weight: 600; color: var(--text); }
.menus-module .menu-item .item-url { font-size: 12px; color: var(--text-sec); }

.menus-module .menu-item .item-actions {
    display: flex;
    gap: 6px;
}

.menus-module .menu-item .btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 12px;
}

.menus-module .btn-icon-edit { background: #e0e7ff; color: var(--primary); }
.menus-module .btn-icon-toggle { background: #fef3c7; color: #d97706; }
.menus-module .btn-icon-delete { background: #fee2e2; color: #dc2626; }

.menus-module .empty-list {
    text-align: center;
    padding: 40px;
    color: var(--text-sec);
}

/* Grid layout pour les sections */
.menus-module .grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 1024px) {
    .menus-module .grid-2 { grid-template-columns: 1fr; }
}

/* Modal */
.menus-module .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.menus-module .modal-overlay.active { display: flex; }

.menus-module .modal {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.menus-module .modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menus-module .modal-header h3 { margin: 0; font-size: 18px; }

.menus-module .modal-body { padding: 20px; }

.menus-module .modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.menus-module .close-modal {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-sec);
}

/* Menu selector */
.menus-module .menu-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.menus-module .menu-selector-btn {
    padding: 10px 20px;
    border: 2px solid var(--border);
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.menus-module .menu-selector-btn:hover { border-color: var(--primary); }
.menus-module .menu-selector-btn.active { border-color: var(--primary); background: #eef2ff; color: var(--primary); }
</style>

<div class="menus-module">

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="header-bar">
    <div>
        <h2>🔗 Menus & Navigation</h2>
        <p style="color: var(--text-sec); margin-top: 4px; font-size: 14px;">Gérez les liens du header et du footer</p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-nav">
    <button class="tab-btn active" data-tab="header">🏠 Menu Principal</button>
    <button class="tab-btn" data-tab="footer-links">📋 Liens Footer</button>
    <button class="tab-btn" data-tab="footer-settings">⚙️ Paramètres Footer</button>
</div>

<!-- Tab: Menu Principal -->
<div class="tab-content active" id="tab-header">
    <?php 
    $headerMenu = $pdo->query("SELECT * FROM menus WHERE slug = 'header-main'")->fetch();
    $headerItems = $headerMenu ? $pdo->query("SELECT * FROM menu_items WHERE menu_id = {$headerMenu['id']} ORDER BY position")->fetchAll() : [];
    ?>
    
    <div class="grid-2">
        <!-- Liste des liens -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-bars"></i> Liens du menu</span>
                <span class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 4px 8px;">
                    Glisser pour réorganiser
                </span>
            </div>
            <div class="card-body">
                <?php if (!empty($headerItems)): ?>
                <ul class="menu-items-list" id="headerMenuList" data-menu-id="<?php echo $headerMenu['id']; ?>">
                    <?php foreach ($headerItems as $item): ?>
                    <li class="menu-item <?php echo !$item['is_active'] ? 'inactive' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                        <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                        <div class="item-info">
                            <div class="item-title">
                                <?php if ($item['icon']): ?><i class="<?php echo htmlspecialchars($item['icon']); ?>"></i> <?php endif; ?>
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                            <div class="item-url"><?php echo htmlspecialchars($item['url']); ?></div>
                        </div>
                        <div class="item-actions">
                            <button class="btn-icon btn-icon-edit" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon btn-icon-toggle" onclick="toggleItem(<?php echo $item['id']; ?>)" title="Activer/Désactiver">
                                <i class="fas fa-<?php echo $item['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                            </button>
                            <button class="btn-icon btn-icon-delete" onclick="deleteItem(<?php echo $item['id']; ?>)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-list">
                    <i class="fas fa-link" style="font-size: 32px; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                    <p>Aucun lien dans ce menu</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Formulaire d'ajout -->
        <div class="card">
            <div class="card-header"><i class="fas fa-plus-circle"></i> Ajouter un lien</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="menu_id" value="<?php echo $headerMenu['id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Titre du lien *</label>
                        <input type="text" name="title" class="form-control" required placeholder="Accueil, Services, Contact...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">URL *</label>
                        <input type="text" name="url" class="form-control" required placeholder="/page ou https://...">
                        <p class="form-help">Utilisez / pour le début (ex: /contact) ou une URL complète</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Icône (optionnel)</label>
                            <input type="text" name="icon" class="form-control" placeholder="fas fa-home">
                            <p class="form-help">Classe FontAwesome</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ouvrir dans</label>
                            <select name="target" class="form-control">
                                <option value="_self">Même fenêtre</option>
                                <option value="_blank">Nouvel onglet</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Classe CSS (optionnel)</label>
                        <input type="text" name="css_class" class="form-control" placeholder="btn-cta, highlight...">
                    </div>
                    
                    <button type="submit" name="add_item" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-plus"></i> Ajouter le lien
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Liens Footer -->
<div class="tab-content" id="tab-footer-links">
    <div class="menu-selector">
        <?php 
        $footerMenus = array_filter($menus, fn($m) => strpos($m['slug'], 'footer-') === 0);
        $firstFooter = reset($footerMenus);
        ?>
        <?php foreach ($footerMenus as $fm): ?>
        <button class="menu-selector-btn <?php echo $fm['id'] === $firstFooter['id'] ? 'active' : ''; ?>" 
                data-menu-id="<?php echo $fm['id']; ?>" 
                onclick="selectFooterMenu(this, <?php echo $fm['id']; ?>)">
            <?php echo htmlspecialchars($fm['name']); ?>
        </button>
        <?php endforeach; ?>
    </div>
    
    <?php foreach ($footerMenus as $fm): 
        $footerItems = $pdo->query("SELECT * FROM menu_items WHERE menu_id = {$fm['id']} ORDER BY position")->fetchAll();
    ?>
    <div class="footer-menu-section <?php echo $fm['id'] !== $firstFooter['id'] ? 'hidden' : ''; ?>" 
         id="footer-menu-<?php echo $fm['id']; ?>" 
         style="<?php echo $fm['id'] !== $firstFooter['id'] ? 'display:none;' : ''; ?>">
        
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-list"></i> <?php echo htmlspecialchars($fm['name']); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($footerItems)): ?>
                    <ul class="menu-items-list" data-menu-id="<?php echo $fm['id']; ?>">
                        <?php foreach ($footerItems as $item): ?>
                        <li class="menu-item <?php echo !$item['is_active'] ? 'inactive' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                            <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                            <div class="item-info">
                                <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="item-url"><?php echo htmlspecialchars($item['url']); ?></div>
                            </div>
                            <div class="item-actions">
                                <button class="btn-icon btn-icon-edit" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-icon-delete" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-list">
                        <p>Aucun lien dans cette colonne</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> Ajouter un lien</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="menu_id" value="<?php echo $fm['id']; ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">URL</label>
                            <input type="text" name="url" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ouvrir dans</label>
                            <select name="target" class="form-control">
                                <option value="_self">Même fenêtre</option>
                                <option value="_blank">Nouvel onglet</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_item" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tab: Paramètres Footer -->
<div class="tab-content" id="tab-footer-settings">
    <form method="POST">
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><i class="fas fa-building"></i> Informations entreprise</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Nom de l'entreprise</label>
                        <input type="text" name="footer_company_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['footer_company_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description / Slogan</label>
                        <textarea name="footer_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['footer_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <textarea name="footer_address" class="form-control" rows="2"><?php echo htmlspecialchars($settings['footer_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="footer_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['footer_phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="footer_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['footer_email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Copyright</label>
                        <input type="text" name="footer_copyright" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? '© ' . date('Y') . ' Tous droits réservés'); ?>"
                               placeholder="© 2024 Mon Entreprise. Tous droits réservés.">
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><i class="fas fa-share-alt"></i> Réseaux sociaux</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fab fa-facebook" style="color: #1877f2;"></i> Facebook</label>
                        <input type="url" name="social_facebook" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>"
                               placeholder="https://facebook.com/votre-page">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fab fa-instagram" style="color: #e4405f;"></i> Instagram</label>
                        <input type="url" name="social_instagram" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>"
                               placeholder="https://instagram.com/votre-compte">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fab fa-linkedin" style="color: #0a66c2;"></i> LinkedIn</label>
                        <input type="url" name="social_linkedin" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>"
                               placeholder="https://linkedin.com/company/votre-entreprise">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fab fa-youtube" style="color: #ff0000;"></i> YouTube</label>
                        <input type="url" name="social_youtube" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>"
                               placeholder="https://youtube.com/c/votre-chaine">
                    </div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button type="submit" name="save_footer_settings" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Sauvegarder les paramètres du footer
            </button>
        </div>
    </form>
</div>

<!-- Modal Édition -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier le lien</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="item_id" id="edit_item_id">
                
                <div class="form-group">
                    <label class="form-label">Titre</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL</label>
                    <input type="text" name="url" id="edit_url" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Icône</label>
                        <input type="text" name="icon" id="edit_icon" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ouvrir dans</label>
                        <select name="target" id="edit_target" class="form-control">
                            <option value="_self">Même fenêtre</option>
                            <option value="_blank">Nouvel onglet</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Classe CSS</label>
                    <input type="text" name="css_class" id="edit_css_class" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" name="update_item" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Tabs
document.querySelectorAll('.menus-module .tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.menus-module .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.menus-module .tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

// Footer menu selector
function selectFooterMenu(btn, menuId) {
    document.querySelectorAll('.menu-selector-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.footer-menu-section').forEach(s => s.style.display = 'none');
    document.getElementById('footer-menu-' + menuId).style.display = 'block';
}

// Drag and drop
document.querySelectorAll('.menu-items-list').forEach(list => {
    new Sortable(list, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            const items = Array.from(list.querySelectorAll('.menu-item')).map(el => el.dataset.id);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax=1&ajax_action=reorder&items=' + encodeURIComponent(JSON.stringify(items))
            });
        }
    });
});

// Edit modal
function editItem(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_title').value = item.title;
    document.getElementById('edit_url').value = item.url;
    document.getElementById('edit_icon').value = item.icon || '';
    document.getElementById('edit_target').value = item.target;
    document.getElementById('edit_css_class').value = item.css_class || '';
    document.getElementById('editModal').classList.add('active');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Toggle active
function toggleItem(id) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax=1&ajax_action=toggle_active&item_id=' + id
    }).then(() => location.reload());
}

// Delete
function deleteItem(id) {
    if (confirm('Supprimer ce lien ?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax=1&ajax_action=delete_item&item_id=' + id
        }).then(() => location.reload());
    }
}

// Close modal on overlay click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>