<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  Module Pages de Capture — Builder IA intégré
 *  /admin/modules/pages-capture/index.php
 *
 *  Même builder que Pages / Articles / Quartiers :
 *  - Génération IA (Claude API)
 *  - Templates pré-construits
 *  - Aperçu / Code toggle
 *  - Preview responsive plein écran
 *  + Spécificités capture :
 *  - Formulaire configurable (champs drag & drop)
 *  - Page de remerciement
 *  - Intégration CRM (source, tags)
 *  - Stats conversion (vues, leads, taux)
 * ══════════════════════════════════════════════════════════════
 */

// ─── DB (héritée de dashboard.php) ───
if (!isset($pdo) && !isset($db)) {
    $dbClassPath = dirname(dirname(__DIR__)) . '/includes/classes/Database.php';
    if (file_exists($dbClassPath)) {
        require_once $dbClassPath;
        $db = Database::getInstance();
    } else {
        // Fallback config
        $cfgPath = dirname(dirname(__DIR__)) . '/../config/database.php';
        if (!file_exists($cfgPath)) $cfgPath = dirname(dirname(__DIR__)) . '/../config/config.php';
        if (file_exists($cfgPath)) require_once $cfgPath;
    }
}
if (isset($pdo) && !isset($db)) $db = $pdo;
if (isset($db) && !isset($pdo)) $pdo = $db;

if (!isset($pdo)) {
    echo '<div style="padding:20px;color:#ef4444;background:rgba(239,68,68,0.1);border-radius:8px;margin:20px 0;">Erreur : connexion BD non disponible. Vérifiez /includes/classes/Database.php</div>';
    return;
}

// ─── Créer la table si elle n'existe pas ───
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `capture_pages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titre` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `sous_titre` VARCHAR(500) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `html_capture` LONGTEXT DEFAULT NULL COMMENT 'HTML de la page de capture',
        `html_merci` LONGTEXT DEFAULT NULL COMMENT 'HTML de la page remerciement',
        `form_config` JSON DEFAULT NULL COMMENT 'Configuration des champs du formulaire',
        `form_titre` VARCHAR(255) DEFAULT 'Remplissez le formulaire',
        `form_button_text` VARCHAR(100) DEFAULT 'Envoyer',
        `form_button_color` VARCHAR(20) DEFAULT '#667eea',
        `lead_source` VARCHAR(100) DEFAULT 'capture' COMMENT 'Source pour le CRM',
        `lead_tags` VARCHAR(500) DEFAULT NULL COMMENT 'Tags auto pour les leads',
        `redirect_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL externe de redirection',
        `meta_title` VARCHAR(255) DEFAULT NULL,
        `meta_description` TEXT DEFAULT NULL,
        `og_image` VARCHAR(500) DEFAULT NULL,
        `status` ENUM('brouillon','publie','archive') DEFAULT 'brouillon',
        `views_count` INT DEFAULT 0,
        `submissions_count` INT DEFAULT 0,
        `conversion_rate` DECIMAL(5,2) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_slug` (`slug`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table existe déjà — on continue
}

// ─── Routeur ───
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$message = $_SESSION['flash_message'] ?? null;
$messageType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ══════════════════════════════════════════════════════════════
// ACTION: SAVE (POST)
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $postId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $titre = trim($_POST['titre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $sous_titre = trim($_POST['sous_titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $html_capture = $_POST['html_capture'] ?? '';
    $html_merci = $_POST['html_merci'] ?? '';
    $form_titre = trim($_POST['form_titre'] ?? 'Remplissez le formulaire');
    $form_button_text = trim($_POST['form_button_text'] ?? 'Envoyer');
    $form_button_color = trim($_POST['form_button_color'] ?? '#667eea');
    $lead_source = trim($_POST['lead_source'] ?? 'capture');
    $lead_tags = trim($_POST['lead_tags'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'brouillon';

    // Form config (champs dynamiques)
    $form_fields = $_POST['form_fields'] ?? [];
    $form_config = json_encode($form_fields, JSON_UNESCAPED_UNICODE);

    // Auto-slug
    if (empty($slug) && !empty($titre)) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $titre)), '-'));
    }

    if (empty($titre) || empty($slug)) {
        $_SESSION['flash_message'] = 'Le titre et le slug sont obligatoires.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ?page=pages-capture&action=' . ($postId ? "edit&id=$postId" : 'create'));
        exit;
    }

    try {
        if ($postId) {
            $stmt = $pdo->prepare("UPDATE capture_pages SET 
                titre=?, slug=?, sous_titre=?, description=?, 
                html_capture=?, html_merci=?, form_config=?,
                form_titre=?, form_button_text=?, form_button_color=?,
                lead_source=?, lead_tags=?, meta_title=?, meta_description=?, status=?
                WHERE id=?");
            $stmt->execute([$titre, $slug, $sous_titre, $description,
                $html_capture, $html_merci, $form_config,
                $form_titre, $form_button_text, $form_button_color,
                $lead_source, $lead_tags, $meta_title, $meta_description, $status, $postId]);
            $_SESSION['flash_message'] = 'Page de capture mise à jour !';
        } else {
            $stmt = $pdo->prepare("INSERT INTO capture_pages 
                (titre, slug, sous_titre, description, html_capture, html_merci, form_config,
                 form_titre, form_button_text, form_button_color, lead_source, lead_tags, 
                 meta_title, meta_description, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$titre, $slug, $sous_titre, $description,
                $html_capture, $html_merci, $form_config,
                $form_titre, $form_button_text, $form_button_color,
                $lead_source, $lead_tags, $meta_title, $meta_description, $status]);
            $postId = $pdo->lastInsertId();
            $_SESSION['flash_message'] = 'Page de capture créée !';
        }
        header('Location: ?page=pages-capture&action=edit&id=' . $postId);
        exit;
    } catch (PDOException $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// ══════════════════════════════════════════════════════════════
// ACTION: DELETE
// ══════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM capture_pages WHERE id = ?")->execute([$id]);
    $_SESSION['flash_message'] = 'Page supprimée.';
    header('Location: ?page=pages-capture');
    exit;
}

// ══════════════════════════════════════════════════════════════
// ACTION: DUPLICATE
// ══════════════════════════════════════════════════════════════
if ($action === 'duplicate' && $id) {
    $orig = $pdo->prepare("SELECT * FROM capture_pages WHERE id = ?");
    $orig->execute([$id]);
    $pg = $orig->fetch(PDO::FETCH_ASSOC);
    if ($pg) {
        $newSlug = $pg['slug'] . '-copie-' . time();
        $stmt = $pdo->prepare("INSERT INTO capture_pages 
            (titre, slug, sous_titre, description, html_capture, html_merci, form_config,
             form_titre, form_button_text, form_button_color, lead_source, lead_tags, 
             meta_title, meta_description, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $pg['titre'] . ' (copie)', $newSlug, $pg['sous_titre'], $pg['description'],
            $pg['html_capture'], $pg['html_merci'], $pg['form_config'],
            $pg['form_titre'], $pg['form_button_text'], $pg['form_button_color'],
            $pg['lead_source'], $pg['lead_tags'], $pg['meta_title'], $pg['meta_description'], 'brouillon'
        ]);
        $_SESSION['flash_message'] = 'Page dupliquée !';
    }
    header('Location: ?page=pages-capture');
    exit;
}

// ══════════════════════════════════════════════════════════════
// Charger les données pour edit
// ══════════════════════════════════════════════════════════════
$pageData = null;
$formFields = [];
if (($action === 'edit' || $action === 'builder') && $id) {
    $stmt = $pdo->prepare("SELECT * FROM capture_pages WHERE id = ?");
    $stmt->execute([$id]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pageData && $pageData['form_config']) {
        $formFields = json_decode($pageData['form_config'], true) ?: [];
    }
}

// Champs par défaut si création
if (empty($formFields)) {
    $formFields = [
        ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'placeholder' => 'Votre nom'],
        ['name' => 'prenom', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'placeholder' => 'Votre prénom'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'votre@email.com'],
        ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'tel', 'required' => true, 'placeholder' => '06 12 34 56 78'],
    ];
}

// ─── Stats pour la liste ───
$stats = ['total' => 0, 'publie' => 0, 'brouillon' => 0, 'total_leads' => 0];
if ($action === 'list') {
    try {
        $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM capture_pages")->fetchColumn();
        $stats['publie'] = (int)$pdo->query("SELECT COUNT(*) FROM capture_pages WHERE status='publie'")->fetchColumn();
        $stats['brouillon'] = (int)$pdo->query("SELECT COUNT(*) FROM capture_pages WHERE status='brouillon'")->fetchColumn();
        $stats['total_leads'] = (int)$pdo->query("SELECT COALESCE(SUM(submissions_count),0) FROM capture_pages")->fetchColumn();
    } catch (Exception $e) {}
}

$siteDomain = $_SERVER['HTTP_HOST'] ?? 'mon-site.fr';
?>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- CSS INTÉGRÉ                                                   -->
<!-- ══════════════════════════════════════════════════════════════ -->
<style>
:root {
    --cp-primary: #667eea;
    --cp-primary-dark: #5a6fd6;
    --cp-success: #10b981;
    --cp-warning: #f59e0b;
    --cp-danger: #ef4444;
    --cp-bg: #0f1117;
    --cp-card: #1a1d28;
    --cp-card-hover: #222536;
    --cp-border: #2a2d3a;
    --cp-text: #e2e8f0;
    --cp-text-muted: #94a3b8;
    --cp-radius: 12px;
}

.cp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.cp-header h1 { font-size: 1.6rem; font-weight: 700; color: var(--cp-text); display: flex; align-items: center; gap: 10px; margin: 0; }
.cp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
.cp-btn-primary { background: linear-gradient(135deg, var(--cp-primary), #764ba2); color: #fff; }
.cp-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(102,126,234,0.3); }
.cp-btn-secondary { background: var(--cp-card); color: var(--cp-text); border: 1px solid var(--cp-border); }
.cp-btn-secondary:hover { background: var(--cp-card-hover); }
.cp-btn-danger { background: var(--cp-danger); color: #fff; }
.cp-btn-success { background: var(--cp-success); color: #fff; }
.cp-btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.cp-btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }

/* Stats */
.cp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.cp-stat { background: var(--cp-card); border: 1px solid var(--cp-border); border-radius: var(--cp-radius); padding: 20px; text-align: center; }
.cp-stat-value { font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, var(--cp-primary), #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.cp-stat-label { font-size: 0.8rem; color: var(--cp-text-muted); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

/* Table */
.cp-table-wrap { background: var(--cp-card); border: 1px solid var(--cp-border); border-radius: var(--cp-radius); overflow-x: auto; }
.cp-table { width: 100%; border-collapse: collapse; }
.cp-table th { background: rgba(102,126,234,0.1); padding: 14px 16px; text-align: left; font-size: 0.8rem; color: var(--cp-text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.cp-table td { padding: 14px 16px; border-top: 1px solid var(--cp-border); font-size: 0.9rem; color: var(--cp-text); }
.cp-table tr:hover td { background: var(--cp-card-hover); }
.cp-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.cp-badge-success { background: rgba(16,185,129,0.15); color: #34d399; }
.cp-badge-warning { background: rgba(245,158,11,0.15); color: #fbbf24; }
.cp-badge-muted { background: rgba(148,163,184,0.15); color: #94a3b8; }

/* Flash */
.cp-flash { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
.cp-flash-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
.cp-flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }

/* Form */
.cp-form-card { background: var(--cp-card); border: 1px solid var(--cp-border); border-radius: var(--cp-radius); padding: 24px; margin-bottom: 20px; }
.cp-form-card h2 { font-size: 1.1rem; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px; color: var(--cp-text); }
.cp-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.cp-form-group { margin-bottom: 16px; }
.cp-form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--cp-text); margin-bottom: 6px; }
.cp-form-group label .required { color: var(--cp-danger); }
.cp-input, .cp-textarea, .cp-select { width: 100%; padding: 10px 14px; background: var(--cp-bg); border: 1px solid var(--cp-border); border-radius: 8px; color: var(--cp-text); font-size: 0.9rem; transition: border-color 0.2s; box-sizing: border-box; }
.cp-input:focus, .cp-textarea:focus, .cp-select:focus { outline: none; border-color: var(--cp-primary); box-shadow: 0 0 0 3px rgba(102,126,234,0.15); }
.cp-textarea { min-height: 100px; resize: vertical; font-family: inherit; }
.cp-hint { font-size: 0.8rem; color: var(--cp-text-muted); margin-top: 4px; }

/* Tabs */
.cp-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--cp-border); margin-bottom: 20px; overflow-x: auto; }
.cp-tab { padding: 12px 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--cp-text-muted); border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
.cp-tab:hover { color: var(--cp-text); }
.cp-tab.active { color: var(--cp-primary); border-bottom-color: var(--cp-primary); }
.cp-tab-content { display: none; }
.cp-tab-content.active { display: block; }

/* Builder */
.cp-builder-area { background: var(--cp-bg); border: 2px dashed var(--cp-border); border-radius: var(--cp-radius); min-height: 300px; position: relative; }
.cp-builder-toolbar { display: flex; gap: 8px; padding: 12px; border-bottom: 1px solid var(--cp-border); background: var(--cp-card); border-radius: var(--cp-radius) var(--cp-radius) 0 0; flex-wrap: wrap; align-items: center; }
.cp-builder-preview { padding: 20px; min-height: 250px; }
.cp-builder-preview iframe { width: 100%; border: none; border-radius: 8px; background: #fff; min-height: 400px; }
.cp-builder-code { display: none; }
.cp-builder-code textarea { width: 100%; min-height: 300px; padding: 16px; background: #0d1117; color: #c9d1d9; border: none; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.85rem; resize: vertical; border-radius: 0 0 var(--cp-radius) var(--cp-radius); box-sizing: border-box; }

/* AI Prompt */
.cp-ai-prompt { background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1)); border: 1px solid rgba(102,126,234,0.2); border-radius: var(--cp-radius); padding: 20px; margin-bottom: 16px; }
.cp-ai-prompt textarea { width: 100%; min-height: 80px; padding: 12px; background: var(--cp-bg); border: 1px solid var(--cp-border); border-radius: 8px; color: var(--cp-text); font-size: 0.9rem; font-family: inherit; resize: vertical; box-sizing: border-box; }
.cp-ai-prompt .cp-prompt-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
.cp-ai-loading { display: none; align-items: center; gap: 8px; color: var(--cp-primary); padding: 12px; }
.cp-ai-loading.active { display: flex; }
.cp-spinner { width: 20px; height: 20px; border: 2px solid var(--cp-border); border-top-color: var(--cp-primary); border-radius: 50%; animation: cpspin 0.8s linear infinite; }
@keyframes cpspin { to { transform: rotate(360deg); } }

/* Form builder */
.cp-field-list { min-height: 60px; }
.cp-field-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--cp-bg); border: 1px solid var(--cp-border); border-radius: 8px; margin-bottom: 8px; cursor: grab; transition: all 0.2s; }
.cp-field-item:hover { border-color: var(--cp-primary); }
.cp-field-item .drag-handle { color: var(--cp-text-muted); cursor: grab; }
.cp-field-item .field-info { flex: 1; min-width: 0; }
.cp-field-item .field-name { font-weight: 600; font-size: 0.9rem; color: var(--cp-text); }
.cp-field-item .field-type { font-size: 0.75rem; color: var(--cp-text-muted); }
.cp-field-item .field-actions { display: flex; gap: 4px; }

/* Preview modes */
.cp-preview-modes { display: flex; gap: 4px; background: var(--cp-bg); border-radius: 8px; padding: 4px; }
.cp-preview-mode { padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; color: var(--cp-text-muted); transition: all 0.2s; border: none; background: none; }
.cp-preview-mode.active { background: var(--cp-primary); color: #fff; }

/* Empty */
.cp-empty { text-align: center; padding: 60px 20px; }
.cp-empty-icon { font-size: 3rem; margin-bottom: 12px; }
.cp-empty-text { color: var(--cp-text-muted); margin-bottom: 20px; }

/* Save bar */
.cp-save-bar { display: flex; gap: 12px; margin-top: 24px; padding: 20px; background: var(--cp-card); border: 1px solid var(--cp-border); border-radius: var(--cp-radius); position: sticky; bottom: 0; z-index: 100; }

@media (max-width: 768px) {
    .cp-form-row { grid-template-columns: 1fr; }
    .cp-stats { grid-template-columns: 1fr 1fr; }
    .cp-header { flex-direction: column; align-items: flex-start; }
}
</style>

<?php if ($message): ?>
    <div class="cp-flash cp-flash-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════
// VIEW: LIST
// ══════════════════════════════════════════════════════════════
if ($action === 'list'):
    $pages = $pdo->query("SELECT * FROM capture_pages ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="cp-header">
    <h1><i class="fas fa-bullseye" style="color:var(--cp-primary)"></i> Pages de capture</h1>
    <a href="?page=pages-capture&action=create" class="cp-btn cp-btn-primary"><i class="fas fa-plus"></i> Créer une page</a>
</div>

<div class="cp-stats">
    <div class="cp-stat"><div class="cp-stat-value"><?php echo $stats['total']; ?></div><div class="cp-stat-label">Total pages</div></div>
    <div class="cp-stat"><div class="cp-stat-value"><?php echo $stats['publie']; ?></div><div class="cp-stat-label">Publiées</div></div>
    <div class="cp-stat"><div class="cp-stat-value"><?php echo $stats['brouillon']; ?></div><div class="cp-stat-label">Brouillons</div></div>
    <div class="cp-stat"><div class="cp-stat-value"><?php echo $stats['total_leads']; ?></div><div class="cp-stat-label">Leads captés</div></div>
</div>

<?php if (empty($pages)): ?>
    <div class="cp-form-card">
        <div class="cp-empty">
            <div class="cp-empty-icon"><i class="fas fa-bullseye" style="color:var(--cp-primary)"></i></div>
            <div class="cp-empty-text">Aucune page de capture créée.<br>Créez votre première page pour commencer à capter des leads !</div>
            <a href="?page=pages-capture&action=create" class="cp-btn cp-btn-primary"><i class="fas fa-plus"></i> Créer ma première page</a>
        </div>
    </div>
<?php else: ?>
    <div class="cp-table-wrap">
        <table class="cp-table">
            <thead><tr><th>Page</th><th>URL</th><th>Statut</th><th>Vues</th><th>Leads</th><th>Taux</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($p['titre']); ?></strong>
                        <?php if (!empty($p['sous_titre'])): ?>
                            <br><small style="color:var(--cp-text-muted)"><?php echo htmlspecialchars($p['sous_titre']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code style="font-size:0.8rem;color:var(--cp-primary)">/capture/<?php echo htmlspecialchars($p['slug']); ?></code>
                        <?php if ($p['status'] === 'publie'): ?>
                            <a href="/capture/<?php echo htmlspecialchars($p['slug']); ?>" target="_blank" style="margin-left:6px;color:var(--cp-primary)"><i class="fas fa-external-link-alt" style="font-size:11px"></i></a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['status'] === 'publie'): ?>
                            <span class="cp-badge cp-badge-success"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Publiée</span>
                        <?php elseif ($p['status'] === 'brouillon'): ?>
                            <span class="cp-badge cp-badge-warning"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Brouillon</span>
                        <?php else: ?>
                            <span class="cp-badge cp-badge-muted"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Archivée</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($p['views_count']); ?></td>
                    <td><strong><?php echo number_format($p['submissions_count']); ?></strong></td>
                    <td><?php echo $p['views_count'] > 0 ? number_format(($p['submissions_count']/$p['views_count'])*100, 1) . '%' : '—'; ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="?page=pages-capture&action=edit&id=<?php echo $p['id']; ?>" class="cp-btn cp-btn-secondary cp-btn-sm" title="Éditer"><i class="fas fa-pen"></i></a>
                            <a href="?page=pages-capture&action=duplicate&id=<?php echo $p['id']; ?>" class="cp-btn cp-btn-secondary cp-btn-sm" title="Dupliquer"><i class="fas fa-copy"></i></a>
                            <a href="?page=pages-capture&action=delete&id=<?php echo $p['id']; ?>" class="cp-btn cp-btn-danger cp-btn-sm" onclick="return confirm('Supprimer cette page ?')" title="Supprimer"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════
// VIEW: CREATE / EDIT (Builder)
// ══════════════════════════════════════════════════════════════
elseif ($action === 'create' || $action === 'edit'):
?>

<div class="cp-header">
    <h1><i class="fas fa-<?php echo $pageData ? 'pen' : 'plus'; ?>" style="color:var(--cp-primary)"></i> <?php echo $pageData ? 'Modifier' : 'Créer'; ?> une page de capture</h1>
    <div style="display:flex;gap:8px;">
        <a href="?page=pages-capture" class="cp-btn cp-btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
        <?php if ($pageData && $pageData['status'] === 'publie'): ?>
            <a href="/capture/<?php echo htmlspecialchars($pageData['slug']); ?>" target="_blank" class="cp-btn cp-btn-secondary"><i class="fas fa-eye"></i> Voir en ligne</a>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="?page=pages-capture&action=save" id="captureForm">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($pageData['id'] ?? ''); ?>">

    <!-- ═══ ONGLETS ═══ -->
    <div class="cp-tabs" id="mainTabs">
        <div class="cp-tab active" data-tab="infos"><i class="fas fa-info-circle"></i> Informations</div>
        <div class="cp-tab" data-tab="capture"><i class="fas fa-bullseye"></i> Page de capture</div>
        <div class="cp-tab" data-tab="merci"><i class="fas fa-heart"></i> Page remerciement</div>
        <div class="cp-tab" data-tab="formulaire"><i class="fas fa-list-alt"></i> Formulaire</div>
        <div class="cp-tab" data-tab="seo"><i class="fas fa-search"></i> SEO & CRM</div>
    </div>

    <!-- ═══ TAB: INFORMATIONS ═══ -->
    <div class="cp-tab-content active" id="tab-infos">
        <div class="cp-form-card">
            <h2><i class="fas fa-info-circle"></i> Informations générales</h2>
            <div class="cp-form-row">
                <div class="cp-form-group">
                    <label>Titre <span class="required">*</span></label>
                    <input type="text" name="titre" class="cp-input" value="<?php echo htmlspecialchars($pageData['titre'] ?? ''); ?>" placeholder="Ex: Votre projet achat immobilier" required id="inputTitre">
                </div>
                <div class="cp-form-group">
                    <label>URL slug <span class="required">*</span></label>
                    <input type="text" name="slug" class="cp-input" value="<?php echo htmlspecialchars($pageData['slug'] ?? ''); ?>" placeholder="ex: projet-achat" id="inputSlug">
                    <div class="cp-hint">URL: /capture/<span id="slugPreview"><?php echo htmlspecialchars($pageData['slug'] ?? '...'); ?></span></div>
                </div>
            </div>
            <div class="cp-form-group">
                <label>Sous-titre</label>
                <input type="text" name="sous_titre" class="cp-input" value="<?php echo htmlspecialchars($pageData['sous_titre'] ?? ''); ?>" placeholder="Ex: Recevez les biens en avant-première">
            </div>
            <div class="cp-form-group">
                <label>Description</label>
                <textarea name="description" class="cp-textarea"><?php echo htmlspecialchars($pageData['description'] ?? ''); ?></textarea>
            </div>
            <div class="cp-form-row">
                <div class="cp-form-group">
                    <label>Statut</label>
                    <select name="status" class="cp-select">
                        <option value="brouillon" <?php echo ($pageData['status'] ?? '') === 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="publie" <?php echo ($pageData['status'] ?? '') === 'publie' ? 'selected' : ''; ?>>Publié</option>
                        <option value="archive" <?php echo ($pageData['status'] ?? '') === 'archive' ? 'selected' : ''; ?>>Archivé</option>
                    </select>
                </div>
                <div class="cp-form-group">
                    <label>Source lead (CRM)</label>
                    <select name="lead_source" class="cp-select">
                        <option value="capture" <?php echo ($pageData['lead_source'] ?? '') === 'capture' ? 'selected' : ''; ?>>Page de capture</option>
                        <option value="acheteur" <?php echo ($pageData['lead_source'] ?? '') === 'acheteur' ? 'selected' : ''; ?>>Acheteur</option>
                        <option value="vendeur" <?php echo ($pageData['lead_source'] ?? '') === 'vendeur' ? 'selected' : ''; ?>>Vendeur</option>
                        <option value="estimation" <?php echo ($pageData['lead_source'] ?? '') === 'estimation' ? 'selected' : ''; ?>>Estimation</option>
                        <option value="financement" <?php echo ($pageData['lead_source'] ?? '') === 'financement' ? 'selected' : ''; ?>>Financement</option>
                        <option value="ressource" <?php echo ($pageData['lead_source'] ?? '') === 'ressource' ? 'selected' : ''; ?>>Ressource</option>
                        <option value="newsletter" <?php echo ($pageData['lead_source'] ?? '') === 'newsletter' ? 'selected' : ''; ?>>Newsletter</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: PAGE DE CAPTURE (Builder IA) ═══ -->
    <div class="cp-tab-content" id="tab-capture">
        <div class="cp-form-card">
            <h2><i class="fas fa-bullseye"></i> Builder — Page de capture</h2>
            <p style="color:var(--cp-text-muted);margin-bottom:16px;">Utilisez l'IA pour générer votre page ou modifiez le code HTML directement.</p>

            <div class="cp-ai-prompt">
                <label style="font-weight:700;margin-bottom:8px;display:block;"><i class="fas fa-robot"></i> Générer avec l'IA</label>
                <textarea id="aiPromptCapture" placeholder="Décrivez votre page de capture. Ex: Page pour des acheteurs cherchant un appartement à Bordeaux, avec promesse 'Recevez les biens avant tout le monde'. Style moderne, couleurs bleu et blanc."></textarea>
                <div class="cp-prompt-actions">
                    <button type="button" class="cp-btn cp-btn-primary cp-btn-sm" onclick="generateWithAI('capture')"><i class="fas fa-robot"></i> Générer la page</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('capture','acheteur')"><i class="fas fa-box"></i> Template Acheteur</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('capture','vendeur')"><i class="fas fa-box"></i> Template Vendeur</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('capture','estimation')"><i class="fas fa-box"></i> Template Estimation</button>
                </div>
                <div class="cp-ai-loading" id="aiLoadingCapture"><div class="cp-spinner"></div><span>Génération en cours...</span></div>
            </div>

            <div class="cp-builder-area">
                <div class="cp-builder-toolbar">
                    <div class="cp-preview-modes">
                        <button type="button" class="cp-preview-mode active" data-mode="preview" data-target="capture"><i class="fas fa-eye"></i> Aperçu</button>
                        <button type="button" class="cp-preview-mode" data-mode="code" data-target="capture"><i class="fas fa-code"></i> Code</button>
                    </div>
                    <div style="flex:1;"></div>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="previewFullPage('capture')"><i class="fas fa-expand"></i> Plein écran</button>
                </div>
                <div class="cp-builder-preview" id="previewCapture">
                    <iframe id="iframeCapture" srcdoc="<?php echo htmlspecialchars($pageData['html_capture'] ?? '<div style=&quot;padding:40px;text-align:center;color:#888;&quot;><i class=&quot;fas fa-bullseye&quot; style=&quot;font-size:3rem;margin-bottom:12px;display:block;&quot;></i>Utilisez l\'IA ou un template pour commencer</div>'); ?>"></iframe>
                </div>
                <div class="cp-builder-code" id="codeCapture">
                    <textarea name="html_capture" id="htmlCapture"><?php echo htmlspecialchars($pageData['html_capture'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: PAGE REMERCIEMENT ═══ -->
    <div class="cp-tab-content" id="tab-merci">
        <div class="cp-form-card">
            <h2><i class="fas fa-heart"></i> Builder — Page de remerciement</h2>
            <p style="color:var(--cp-text-muted);margin-bottom:16px;">La page affichée après l'envoi du formulaire. Proposez un RDV, un téléchargement ou un simple merci.</p>

            <div class="cp-ai-prompt">
                <label style="font-weight:700;margin-bottom:8px;display:block;"><i class="fas fa-robot"></i> Générer avec l'IA</label>
                <textarea id="aiPromptMerci" placeholder="Décrivez votre page de remerciement. Ex: Page de confirmation avec message de remerciement, proposition de RDV Calendly, et téléchargement d'un guide PDF."></textarea>
                <div class="cp-prompt-actions">
                    <button type="button" class="cp-btn cp-btn-primary cp-btn-sm" onclick="generateWithAI('merci')"><i class="fas fa-robot"></i> Générer la page</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('merci','rdv')"><i class="fas fa-box"></i> Template RDV</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('merci','telechargement')"><i class="fas fa-box"></i> Template Téléchargement</button>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="insertTemplate('merci','simple')"><i class="fas fa-box"></i> Template Simple</button>
                </div>
                <div class="cp-ai-loading" id="aiLoadingMerci"><div class="cp-spinner"></div><span>Génération en cours...</span></div>
            </div>

            <div class="cp-builder-area">
                <div class="cp-builder-toolbar">
                    <div class="cp-preview-modes">
                        <button type="button" class="cp-preview-mode active" data-mode="preview" data-target="merci"><i class="fas fa-eye"></i> Aperçu</button>
                        <button type="button" class="cp-preview-mode" data-mode="code" data-target="merci"><i class="fas fa-code"></i> Code</button>
                    </div>
                    <div style="flex:1;"></div>
                    <button type="button" class="cp-btn cp-btn-secondary cp-btn-sm" onclick="previewFullPage('merci')"><i class="fas fa-expand"></i> Plein écran</button>
                </div>
                <div class="cp-builder-preview" id="previewMerci">
                    <iframe id="iframeMerci" srcdoc="<?php echo htmlspecialchars($pageData['html_merci'] ?? '<div style=&quot;padding:40px;text-align:center;color:#888;&quot;><i class=&quot;fas fa-heart&quot; style=&quot;font-size:3rem;margin-bottom:12px;display:block;&quot;></i>Utilisez l\'IA ou un template pour commencer</div>'); ?>"></iframe>
                </div>
                <div class="cp-builder-code" id="codeMerci">
                    <textarea name="html_merci" id="htmlMerci"><?php echo htmlspecialchars($pageData['html_merci'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: FORMULAIRE ═══ -->
    <div class="cp-tab-content" id="tab-formulaire">
        <div class="cp-form-card">
            <h2><i class="fas fa-list-alt"></i> Configuration du formulaire</h2>
            <p style="color:var(--cp-text-muted);margin-bottom:20px;">Le formulaire apparaîtra à l'emplacement <code style="background:var(--cp-bg);padding:2px 8px;border-radius:4px;">{{FORMULAIRE}}</code> dans votre HTML.</p>

            <div class="cp-form-row">
                <div class="cp-form-group">
                    <label>Titre du formulaire</label>
                    <input type="text" name="form_titre" class="cp-input" value="<?php echo htmlspecialchars($pageData['form_titre'] ?? 'Remplissez le formulaire'); ?>">
                </div>
                <div class="cp-form-group">
                    <label>Texte du bouton</label>
                    <input type="text" name="form_button_text" class="cp-input" value="<?php echo htmlspecialchars($pageData['form_button_text'] ?? 'Envoyer'); ?>">
                </div>
            </div>
            <div class="cp-form-row">
                <div class="cp-form-group">
                    <label>Couleur du bouton</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="color" name="form_button_color" value="<?php echo htmlspecialchars($pageData['form_button_color'] ?? '#667eea'); ?>" style="width:50px;height:36px;border:none;cursor:pointer;border-radius:6px;">
                        <input type="text" class="cp-input" value="<?php echo htmlspecialchars($pageData['form_button_color'] ?? '#667eea'); ?>" style="width:120px;" oninput="this.previousElementSibling.value=this.value">
                    </div>
                </div>
                <div class="cp-form-group">
                    <label>Tags automatiques (CRM)</label>
                    <input type="text" name="lead_tags" class="cp-input" value="<?php echo htmlspecialchars($pageData['lead_tags'] ?? ''); ?>" placeholder="Ex: acheteur, bordeaux, urgent">
                    <div class="cp-hint">Tags séparés par des virgules, ajoutés au lead dans le CRM</div>
                </div>
            </div>
        </div>

        <div class="cp-form-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="margin:0;"><i class="fas fa-th-list"></i> Champs du formulaire</h2>
                <button type="button" class="cp-btn cp-btn-primary cp-btn-sm" onclick="addField()"><i class="fas fa-plus"></i> Ajouter un champ</button>
            </div>
            <div class="cp-field-list" id="fieldList"></div>
        </div>

        <div class="cp-form-card">
            <h2><i class="fas fa-eye"></i> Aperçu du formulaire</h2>
            <div id="formPreview" style="max-width:500px;margin:0 auto;padding:20px;background:#fff;border-radius:12px;"></div>
        </div>
    </div>

    <!-- ═══ TAB: SEO & CRM ═══ -->
    <div class="cp-tab-content" id="tab-seo">
        <div class="cp-form-card">
            <h2><i class="fas fa-search"></i> SEO</h2>
            <div class="cp-form-group">
                <label>Meta title</label>
                <input type="text" name="meta_title" class="cp-input" value="<?php echo htmlspecialchars($pageData['meta_title'] ?? ''); ?>" placeholder="Titre pour Google (60 chars max)">
            </div>
            <div class="cp-form-group">
                <label>Meta description</label>
                <textarea name="meta_description" class="cp-textarea" style="min-height:60px;"><?php echo htmlspecialchars($pageData['meta_description'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="cp-form-card">
            <h2><i class="fas fa-link"></i> Intégration CRM</h2>
            <p style="color:var(--cp-text-muted);margin-bottom:16px;">Chaque soumission crée automatiquement un lead dans votre CRM :</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div style="background:var(--cp-bg);padding:12px;border-radius:8px;color:var(--cp-text);"><strong>Source :</strong> <?php echo htmlspecialchars($pageData['lead_source'] ?? 'capture'); ?></div>
                <div style="background:var(--cp-bg);padding:12px;border-radius:8px;color:var(--cp-text);"><strong>Tags :</strong> <?php echo htmlspecialchars($pageData['lead_tags'] ?? 'aucun'); ?></div>
                <div style="background:var(--cp-bg);padding:12px;border-radius:8px;color:var(--cp-text);"><strong>Page :</strong> /capture/<?php echo htmlspecialchars($pageData['slug'] ?? '...'); ?></div>
            </div>
        </div>
    </div>

    <!-- ═══ BOUTONS SAVE ═══ -->
    <div class="cp-save-bar">
        <button type="submit" class="cp-btn cp-btn-primary" style="flex:1;"><i class="fas fa-save"></i> Sauvegarder</button>
        <button type="submit" name="status" value="publie" class="cp-btn cp-btn-success"><i class="fas fa-rocket"></i> Publier</button>
        <a href="?page=pages-capture" class="cp-btn cp-btn-secondary">Annuler</a>
    </div>
</form>

<!-- ═══ MODAL PREVIEW PLEIN ÉCRAN ═══ -->
<div id="fullPreviewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;">
    <div style="position:absolute;top:12px;right:12px;display:flex;gap:8px;z-index:10001;">
        <button onclick="setPreviewSize('375px')" class="cp-btn cp-btn-secondary cp-btn-sm"><i class="fas fa-mobile-alt"></i> Mobile</button>
        <button onclick="setPreviewSize('768px')" class="cp-btn cp-btn-secondary cp-btn-sm"><i class="fas fa-tablet-alt"></i> Tablette</button>
        <button onclick="setPreviewSize('100%')" class="cp-btn cp-btn-secondary cp-btn-sm"><i class="fas fa-desktop"></i> Desktop</button>
        <button onclick="closeFullPreview()" class="cp-btn cp-btn-danger cp-btn-sm"><i class="fas fa-times"></i> Fermer</button>
    </div>
    <iframe id="fullPreviewFrame" style="width:100%;height:calc(100% - 60px);margin-top:50px;border:none;background:#fff;display:block;margin-left:auto;margin-right:auto;border-radius:8px;"></iframe>
</div>

<!-- ═══ MODAL CHAMP ═══ -->
<div id="fieldModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--cp-card);border:1px solid var(--cp-border);border-radius:var(--cp-radius);padding:24px;width:500px;max-width:90%;">
        <h3 style="margin:0 0 16px 0;color:var(--cp-text);" id="fieldModalTitle">Ajouter un champ</h3>
        <div class="cp-form-group"><label>Nom technique</label><input type="text" id="fieldName" class="cp-input" placeholder="ex: budget, ville, type_bien"></div>
        <div class="cp-form-group"><label>Label affiché</label><input type="text" id="fieldLabel" class="cp-input" placeholder="ex: Votre budget"></div>
        <div class="cp-form-row">
            <div class="cp-form-group"><label>Type</label>
                <select id="fieldType" class="cp-select">
                    <option value="text">Texte</option><option value="email">Email</option><option value="tel">Téléphone</option>
                    <option value="number">Nombre</option><option value="select">Liste déroulante</option><option value="textarea">Zone de texte</option>
                    <option value="checkbox">Case à cocher</option><option value="date">Date</option><option value="hidden">Champ caché</option>
                </select>
            </div>
            <div class="cp-form-group"><label>Obligatoire</label>
                <select id="fieldRequired" class="cp-select"><option value="1">Oui</option><option value="0">Non</option></select>
            </div>
        </div>
        <div class="cp-form-group"><label>Placeholder</label><input type="text" id="fieldPlaceholder" class="cp-input" placeholder="Texte indicatif..."></div>
        <div class="cp-form-group" id="fieldOptionsGroup" style="display:none;">
            <label>Options (liste déroulante)</label>
            <textarea id="fieldOptions" class="cp-textarea" style="min-height:60px;" placeholder="Une option par ligne&#10;Appartement&#10;Maison&#10;Terrain"></textarea>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="cp-btn cp-btn-primary" onclick="saveField()"><i class="fas fa-save"></i> Enregistrer</button>
            <button type="button" class="cp-btn cp-btn-secondary" onclick="closeFieldModal()">Annuler</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
// ══════════════════════════════════════════════════════════════
// JAVASCRIPT — Builder Pages de Capture
// ══════════════════════════════════════════════════════════════

// ── Tabs ──
document.querySelectorAll('.cp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.cp-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cp-tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

// ── Auto-slug ──
const inputTitre = document.getElementById('inputTitre');
const inputSlug = document.getElementById('inputSlug');
const slugPreview = document.getElementById('slugPreview');
if (inputTitre && inputSlug) {
    inputTitre.addEventListener('input', () => {
        if (!inputSlug.dataset.manual) {
            const slug = inputTitre.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            inputSlug.value = slug;
            if (slugPreview) slugPreview.textContent = slug || '...';
        }
    });
    inputSlug.addEventListener('input', () => {
        inputSlug.dataset.manual = '1';
        if (slugPreview) slugPreview.textContent = inputSlug.value || '...';
    });
}

// ── Preview Mode Toggle ──
document.querySelectorAll('.cp-preview-mode').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.target;
        const mode = btn.dataset.mode;
        btn.parentElement.querySelectorAll('.cp-preview-mode').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const previewEl = document.getElementById('preview' + capitalize(target));
        const codeEl = document.getElementById('code' + capitalize(target));
        if (mode === 'preview') {
            previewEl.style.display = 'block';
            codeEl.style.display = 'none';
            refreshPreview(target);
        } else {
            previewEl.style.display = 'none';
            codeEl.style.display = 'block';
        }
    });
});

function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function refreshPreview(target) {
    const textarea = document.getElementById('html' + capitalize(target));
    const iframe = document.getElementById('iframe' + capitalize(target));
    if (textarea && iframe) {
        iframe.srcdoc = textarea.value || '<div style="padding:40px;text-align:center;color:#888;">Aucun contenu</div>';
    }
}

// ── Full Preview ──
function previewFullPage(target) {
    const html = document.getElementById('html' + capitalize(target)).value;
    document.getElementById('fullPreviewFrame').srcdoc = html || '<p style="padding:40px;text-align:center;">Aucun contenu</p>';
    document.getElementById('fullPreviewModal').style.display = 'block';
}
function setPreviewSize(w) {
    document.getElementById('fullPreviewFrame').style.width = w;
    document.getElementById('fullPreviewFrame').style.maxWidth = '100%';
}
function closeFullPreview() {
    document.getElementById('fullPreviewModal').style.display = 'none';
}

// ══════════════════════════════════════════════════════════════
// AI GENERATION
// ══════════════════════════════════════════════════════════════
async function generateWithAI(target) {
    const promptEl = document.getElementById('aiPrompt' + capitalize(target));
    const loadingEl = document.getElementById('aiLoading' + capitalize(target));
    const prompt = promptEl.value.trim();

    if (!prompt) { alert('Décrivez votre page pour que l\'IA puisse la générer.'); return; }
    loadingEl.classList.add('active');

    const systemPrompt = target === 'capture' 
        ? `Tu es un expert en landing pages immobilières. Génère UNIQUEMENT du HTML complet et professionnel pour une page de capture immobilière.
           La page doit inclure :
           - Un header accrocheur avec titre et sous-titre
           - Une section de bénéfices (3-4 points)
           - Des preuves sociales / témoignages
           - Un placeholder {{FORMULAIRE}} où le formulaire sera injecté automatiquement
           - Un design responsive moderne avec CSS intégré (pas de fichiers externes sauf Google Fonts)
           - Palette de couleurs professionnelle immobilier
           IMPORTANT: Utilise {{FORMULAIRE}} comme placeholder pour le formulaire, il sera remplacé automatiquement.
           Retourne UNIQUEMENT le code HTML, sans commentaires, sans markdown, sans backticks.`
        : `Tu es un expert en pages de remerciement post-conversion. Génère UNIQUEMENT du HTML complet pour une page de remerciement après soumission de formulaire immobilier.
           La page doit inclure :
           - Un message de confirmation chaleureux
           - Les prochaines étapes clairement indiquées
           - Éventuellement un lien vers un calendrier de RDV ou téléchargement
           - Un design cohérent et professionnel avec CSS intégré
           Retourne UNIQUEMENT le code HTML, sans commentaires, sans markdown, sans backticks.`;

    try {
        const response = await fetch('/admin/api/generate-content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: prompt, system: systemPrompt, type: 'capture_page' })
        });
        const data = await response.json();
        if (data.success && data.content) {
            let html = data.content.replace(/^```html?\n?/i, '').replace(/\n?```$/i, '').trim();
            document.getElementById('html' + capitalize(target)).value = html;
            refreshPreview(target);
        } else {
            alert('Erreur: ' + (data.error || 'Pas de contenu généré'));
        }
    } catch (err) {
        alert('Erreur de connexion: ' + err.message);
    }
    loadingEl.classList.remove('active');
}

// ══════════════════════════════════════════════════════════════
// TEMPLATES PRÉ-CONSTRUITS
// ══════════════════════════════════════════════════════════════
const templates = {
    capture: {
        acheteur: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;background:#fafbfc;color:#1a1a2e}.hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:80px 20px;text-align:center}.hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;margin-bottom:16px;line-height:1.2}.hero p{font-size:1.2rem;opacity:0.9;max-width:600px;margin:0 auto 30px}.badge{display:inline-block;background:rgba(255,255,255,0.15);padding:8px 20px;border-radius:30px;font-size:0.9rem;margin-bottom:24px}.benefits{padding:60px 20px;max-width:900px;margin:0 auto}.benefits h2{text-align:center;font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:40px}.benefit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px}.benefit-card{background:#fff;padding:30px;border-radius:16px;box-shadow:0 2px 20px rgba(0,0,0,0.06);text-align:center}.benefit-card .icon{font-size:2.5rem;margin-bottom:12px}.benefit-card h3{font-size:1.1rem;margin-bottom:8px;color:#1a1a2e}.benefit-card p{color:#666;font-size:0.95rem;line-height:1.5}.form-section{background:#f0f4ff;padding:60px 20px}.form-section h2{text-align:center;font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:12px}.form-section>p{text-align:center;color:#666;margin-bottom:30px}</style></head><body><div class="hero"><div class="badge">Exclusivité locale</div><h1>Trouvez votre bien idéal<br>avant tout le monde</h1><p>Recevez en avant-première les biens correspondant à vos critères, avant même leur publication sur les portails.</p></div><div class="benefits"><h2>Pourquoi s'inscrire ?</h2><div class="benefit-grid"><div class="benefit-card"><div class="icon">🔑</div><h3>Accès prioritaire</h3><p>Soyez le premier informé des nouveaux biens disponibles dans votre secteur.</p></div><div class="benefit-card"><div class="icon">🎯</div><h3>Biens ciblés</h3><p>Uniquement les biens qui correspondent à vos critères : budget, surface, localisation.</p></div><div class="benefit-card"><div class="icon">📞</div><h3>Accompagnement dédié</h3><p>Un conseiller local vous accompagne de A à Z dans votre projet d'achat.</p></div></div></div><div class="form-section"><h2>Recevez vos alertes personnalisées</h2><p>Remplissez le formulaire ci-dessous pour accéder à notre sélection exclusive.</p>{{FORMULAIRE}}</div></body></html>`,
        vendeur: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;background:#fafbfc;color:#1a1a2e}.hero{background:linear-gradient(135deg,#2d1b69 0%,#6b21a8 100%);color:#fff;padding:80px 20px;text-align:center}.hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;margin-bottom:16px;line-height:1.2}.hero p{font-size:1.2rem;opacity:0.9;max-width:600px;margin:0 auto 30px}.stats{display:flex;gap:30px;justify-content:center;margin-top:30px;flex-wrap:wrap}.stat{text-align:center}.stat-value{font-size:2rem;font-weight:700}.stat-label{font-size:0.85rem;opacity:0.8}.benefits{padding:60px 20px;max-width:900px;margin:0 auto}.benefit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px}.benefit-card{background:#fff;padding:30px;border-radius:16px;box-shadow:0 2px 20px rgba(0,0,0,0.06);border-left:4px solid #6b21a8}.benefit-card h3{font-size:1.1rem;margin-bottom:8px;color:#2d1b69}.benefit-card p{color:#666;font-size:0.95rem;line-height:1.5}.form-section{background:linear-gradient(to bottom,#f5f0ff,#fafbfc);padding:60px 20px;text-align:center}.form-section h2{font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:12px}.form-section>p{color:#666;margin-bottom:30px}</style></head><body><div class="hero"><h1>Vendez votre bien au meilleur prix</h1><p>Estimation gratuite et sans engagement. Profitez d'un accompagnement local et personnalisé pour vendre vite et bien.</p><div class="stats"><div class="stat"><div class="stat-value">98%</div><div class="stat-label">de satisfaction</div></div><div class="stat"><div class="stat-value">45j</div><div class="stat-label">délai moyen de vente</div></div><div class="stat"><div class="stat-value">+12%</div><div class="stat-label">vs prix du marché</div></div></div></div><div class="benefits"><div class="benefit-grid"><div class="benefit-card"><h3>📊 Estimation précise</h3><p>Analyse comparative du marché basée sur les ventes réelles de votre quartier.</p></div><div class="benefit-card"><h3>📸 Mise en valeur</h3><p>Photos professionnelles, visite virtuelle et annonce optimisée pour maximiser la visibilité.</p></div><div class="benefit-card"><h3>🤝 Négociation experte</h3><p>Un négociateur local qui connaît votre quartier et défend votre prix.</p></div></div></div><div class="form-section"><h2>Obtenez votre estimation gratuite</h2><p>En 2 minutes, recevez une estimation précise de votre bien.</p>{{FORMULAIRE}}</div></body></html>`,
        estimation: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;background:#fafbfc;color:#1a1a2e}.hero{background:linear-gradient(135deg,#065f46 0%,#059669 100%);color:#fff;padding:80px 20px;text-align:center}.hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;margin-bottom:16px;line-height:1.2}.hero p{font-size:1.2rem;opacity:0.9;max-width:600px;margin:0 auto}.steps{padding:60px 20px;max-width:800px;margin:0 auto}.steps h2{text-align:center;font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:40px}.step{display:flex;gap:20px;margin-bottom:30px;align-items:flex-start}.step-num{width:50px;height:50px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;flex-shrink:0}.step h3{font-size:1.1rem;margin-bottom:4px}.step p{color:#666;font-size:0.95rem}.form-section{background:#f0fdf4;padding:60px 20px;text-align:center}.form-section h2{font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:12px}.form-section>p{color:#666;margin-bottom:30px}</style></head><body><div class="hero"><h1>Estimation gratuite de votre bien</h1><p>Recevez une estimation précise en 24h, basée sur les données réelles du marché local.</p></div><div class="steps"><h2>Comment ça marche ?</h2><div class="step"><div class="step-num">1</div><div><h3>Remplissez le formulaire</h3><p>Quelques informations sur votre bien suffisent.</p></div></div><div class="step"><div class="step-num">2</div><div><h3>Analyse du marché</h3><p>Notre expert analyse les ventes récentes de votre quartier.</p></div></div><div class="step"><div class="step-num">3</div><div><h3>Recevez votre estimation</h3><p>Estimation détaillée avec comparatifs en 24h.</p></div></div></div><div class="form-section"><h2>Commencez maintenant</h2><p>C'est gratuit, sans engagement, et vous recevez le résultat en 24h.</p>{{FORMULAIRE}}</div></body></html>`
    },
    merci: {
        rdv: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0f4ff,#faf5ff);padding:20px}.card{background:#fff;border-radius:24px;padding:60px 40px;max-width:600px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.08)}.icon{font-size:4rem;margin-bottom:20px}.card h1{font-size:2rem;margin-bottom:12px;color:#1a1a2e}.card p{color:#666;font-size:1.1rem;line-height:1.6;margin-bottom:20px}.steps{text-align:left;background:#f8fafc;border-radius:16px;padding:24px;margin:24px 0}.step-item{display:flex;gap:12px;margin-bottom:16px;align-items:flex-start}.step-item:last-child{margin-bottom:0}.step-icon{font-size:1.5rem}.btn{display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:16px 40px;border-radius:12px;text-decoration:none;font-weight:600;font-size:1.1rem;margin-top:20px;transition:all 0.3s}.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(102,126,234,0.3)}</style></head><body><div class="card"><div class="icon">🎉</div><h1>Merci pour votre demande !</h1><p>Votre demande a bien été enregistrée. Voici les prochaines étapes :</p><div class="steps"><div class="step-item"><span class="step-icon">📧</span><div><strong>Email de confirmation</strong><br>Vous allez recevoir un email récapitulatif dans les prochaines minutes.</div></div><div class="step-item"><span class="step-icon">📞</span><div><strong>Appel personnalisé</strong><br>Un conseiller vous contactera sous 24h pour affiner votre projet.</div></div><div class="step-item"><span class="step-icon">📅</span><div><strong>Prenez rendez-vous</strong><br>Gagnez du temps en réservant directement un créneau.</div></div></div><a href="#" class="btn">📅 Prendre rendez-vous</a></div></body></html>`,
        telechargement: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);padding:20px}.card{background:#fff;border-radius:24px;padding:60px 40px;max-width:600px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.08)}.icon{font-size:4rem;margin-bottom:20px}.card h1{font-size:2rem;margin-bottom:12px;color:#1a1a2e}.card p{color:#666;font-size:1.1rem;line-height:1.6;margin-bottom:24px}.download-box{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #86efac;border-radius:16px;padding:30px;margin:20px 0}.download-box h3{color:#065f46;margin-bottom:8px}.download-box p{color:#166534;font-size:0.95rem;margin-bottom:16px}.btn{display:inline-block;background:linear-gradient(135deg,#059669,#10b981);color:#fff;padding:16px 40px;border-radius:12px;text-decoration:none;font-weight:600;font-size:1.1rem;transition:all 0.3s}.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(5,150,105,0.3)}</style></head><body><div class="card"><div class="icon">✅</div><h1>Votre guide est prêt !</h1><p>Merci pour votre inscription. Téléchargez votre guide gratuitement.</p><div class="download-box"><h3>📘 Votre guide immobilier</h3><p>Le guide complet pour réussir votre projet immobilier.</p><a href="#" class="btn">📥 Télécharger le guide</a></div><p style="margin-top:24px;font-size:0.9rem;color:#999;">Un email avec le lien de téléchargement vous a également été envoyé.</p></div></body></html>`,
        simple: `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Source Sans 3',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:20px}.card{background:#fff;border-radius:24px;padding:60px 40px;max-width:500px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.08)}.icon{font-size:4rem;margin-bottom:20px}h1{font-size:2rem;margin-bottom:12px;color:#1a1a2e}p{color:#666;font-size:1.1rem;line-height:1.6}.back{display:inline-block;margin-top:30px;color:#667eea;text-decoration:none;font-weight:600}</style></head><body><div class="card"><div class="icon">🙏</div><h1>Merci !</h1><p>Votre demande a été envoyée avec succès. Nous vous contacterons dans les meilleurs délais.</p><a href="/" class="back">← Retour à l'accueil</a></div></body></html>`
    }
};

function insertTemplate(target, name) {
    const html = templates[target]?.[name];
    if (html) {
        document.getElementById('html' + capitalize(target)).value = html;
        refreshPreview(target);
    }
}

// ══════════════════════════════════════════════════════════════
// FORM FIELDS BUILDER
// ══════════════════════════════════════════════════════════════
let formFields = <?php echo json_encode($formFields, JSON_UNESCAPED_UNICODE); ?>;
let editingFieldIndex = -1;

function renderFields() {
    const list = document.getElementById('fieldList');
    if (!list) return;
    
    list.innerHTML = formFields.map((f, i) => `
        <div class="cp-field-item" data-index="${i}">
            <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
            <div class="field-info">
                <div class="field-name">${escHtml(f.label || f.name)}</div>
                <div class="field-type">${f.type} ${f.required ? '• obligatoire' : '• optionnel'} • ${f.name}</div>
            </div>
            <div class="field-actions">
                <button type="button" class="cp-btn cp-btn-secondary cp-btn-icon cp-btn-sm" onclick="editField(${i})" title="Modifier"><i class="fas fa-pen"></i></button>
                <button type="button" class="cp-btn cp-btn-danger cp-btn-icon cp-btn-sm" onclick="removeField(${i})" title="Supprimer"><i class="fas fa-times"></i></button>
            </div>
            <input type="hidden" name="form_fields[${i}][name]" value="${escAttr(f.name)}">
            <input type="hidden" name="form_fields[${i}][label]" value="${escAttr(f.label)}">
            <input type="hidden" name="form_fields[${i}][type]" value="${escAttr(f.type)}">
            <input type="hidden" name="form_fields[${i}][required]" value="${f.required ? '1' : '0'}">
            <input type="hidden" name="form_fields[${i}][placeholder]" value="${escAttr(f.placeholder || '')}">
            <input type="hidden" name="form_fields[${i}][options]" value="${escAttr((f.options || []).join('|'))}">
        </div>
    `).join('');

    if (window.Sortable) {
        Sortable.create(list, {
            handle: '.drag-handle', animation: 150,
            onEnd: function(evt) {
                const item = formFields.splice(evt.oldIndex, 1)[0];
                formFields.splice(evt.newIndex, 0, item);
                renderFields();
            }
        });
    }
    renderFormPreview();
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function addField() {
    editingFieldIndex = -1;
    document.getElementById('fieldModalTitle').textContent = 'Ajouter un champ';
    document.getElementById('fieldName').value = '';
    document.getElementById('fieldLabel').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('fieldRequired').value = '1';
    document.getElementById('fieldPlaceholder').value = '';
    document.getElementById('fieldOptions').value = '';
    document.getElementById('fieldModal').style.display = 'flex';
    toggleOptionsField();
}

function editField(index) {
    editingFieldIndex = index;
    const f = formFields[index];
    document.getElementById('fieldModalTitle').textContent = 'Modifier le champ';
    document.getElementById('fieldName').value = f.name || '';
    document.getElementById('fieldLabel').value = f.label || '';
    document.getElementById('fieldType').value = f.type || 'text';
    document.getElementById('fieldRequired').value = f.required ? '1' : '0';
    document.getElementById('fieldPlaceholder').value = f.placeholder || '';
    document.getElementById('fieldOptions').value = (f.options || []).join('\n');
    document.getElementById('fieldModal').style.display = 'flex';
    toggleOptionsField();
}

function removeField(index) {
    if (confirm('Supprimer ce champ ?')) { formFields.splice(index, 1); renderFields(); }
}

function saveField() {
    const field = {
        name: document.getElementById('fieldName').value.trim(),
        label: document.getElementById('fieldLabel').value.trim(),
        type: document.getElementById('fieldType').value,
        required: document.getElementById('fieldRequired').value === '1',
        placeholder: document.getElementById('fieldPlaceholder').value.trim(),
        options: document.getElementById('fieldOptions').value.split('\n').map(o => o.trim()).filter(o => o)
    };
    if (!field.name || !field.label) { alert('Le nom et le label sont obligatoires.'); return; }
    if (editingFieldIndex >= 0) { formFields[editingFieldIndex] = field; } else { formFields.push(field); }
    closeFieldModal();
    renderFields();
}

function closeFieldModal() { document.getElementById('fieldModal').style.display = 'none'; }

function toggleOptionsField() {
    document.getElementById('fieldOptionsGroup').style.display = document.getElementById('fieldType').value === 'select' ? 'block' : 'none';
}
document.getElementById('fieldType')?.addEventListener('change', toggleOptionsField);

function renderFormPreview() {
    const preview = document.getElementById('formPreview');
    if (!preview) return;
    const title = document.querySelector('[name="form_titre"]')?.value || 'Formulaire';
    const btnText = document.querySelector('[name="form_button_text"]')?.value || 'Envoyer';
    const btnColor = document.querySelector('[name="form_button_color"]')?.value || '#667eea';

    let html = `<h3 style="margin-bottom:20px;font-size:1.2rem;color:#1a1a2e;">${escHtml(title)}</h3>`;
    formFields.forEach(f => {
        if (f.type === 'hidden') return;
        html += `<div style="margin-bottom:16px;">`;
        if (f.type !== 'checkbox') {
            html += `<label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:4px;">${escHtml(f.label)}${f.required ? ' *' : ''}</label>`;
        }
        if (f.type === 'select') {
            html += `<select style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;"><option>Choisir...</option>`;
            (f.options || []).forEach(o => html += `<option>${escHtml(o)}</option>`);
            html += `</select>`;
        } else if (f.type === 'textarea') {
            html += `<textarea style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;min-height:60px;box-sizing:border-box;" placeholder="${escAttr(f.placeholder || '')}"></textarea>`;
        } else if (f.type === 'checkbox') {
            html += `<label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox"> ${escHtml(f.label)}</label>`;
        } else {
            html += `<input type="${f.type}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;box-sizing:border-box;" placeholder="${escAttr(f.placeholder || '')}">`;
        }
        html += `</div>`;
    });
    html += `<button style="width:100%;padding:14px;background:${btnColor};color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:8px;">${escHtml(btnText)}</button>`;
    preview.innerHTML = html;
}

// Init
renderFields();

// Sync code ↔ preview
document.getElementById('htmlCapture')?.addEventListener('input', () => refreshPreview('capture'));
document.getElementById('htmlMerci')?.addEventListener('input', () => refreshPreview('merci'));

// Ctrl+S shortcut
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('captureForm')?.submit();
    }
});
</script>

<?php endif; ?>