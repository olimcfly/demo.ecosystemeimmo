<?php
/**
 * ══════════════════════════════════════════════════════════════
 * ÉDITEUR DE HEADER — Interface dédiée
 * /admin/modules/builder/edit-header.php
 * 
 * Éditeur visuel avec :
 *   - Configuration logo (texte/image)
 *   - Menu items avec drag & drop
 *   - Boutons CTA
 *   - Couleurs et styles
 *   - Preview temps réel
 * 
 * Chargé dans le dashboard via : ?page=design-edit-header&id=X
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
$connection = null;
if (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
elseif (isset($db) && $db instanceof PDO) $connection = $db;
else {
    $initFile = __DIR__ . '/../../includes/init.php';
    $dbConfig = __DIR__ . '/../../config/database.php';
    if (file_exists($initFile)) {
        require_once $initFile;
        if (isset($pdo)) $connection = $pdo;
        elseif (isset($db)) $connection = $db;
    } elseif (file_exists($dbConfig)) {
        require_once $dbConfig;
        if (isset($db)) $connection = $db;
        elseif (isset($pdo)) $connection = $pdo;
    }
}

if (!$connection) {
    echo '<div style="padding:40px;text-align:center;color:#ef4444"><i class="fas fa-exclamation-triangle"></i> Erreur de connexion base de données</div>';
    return;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

// ── Création d'un nouveau header ──
if ($action === 'create') {
    try {
        $stmt = $connection->prepare("
            INSERT INTO headers (name, slug, type, status, is_default, menu_items, 
                bg_color, text_color, hover_color, height, sticky, shadow, 
                cta_enabled, cta_text, cta_link, cta_style, logo_type, logo_text, created_at) 
            VALUES (?, ?, 'standard', 'active', 0, '[]', 
                '#ffffff', '#1e293b', '#3b82f6', 80, 1, 1, 
                1, 'Contact', '/contact', 'primary', 'text', 'Mon Site', NOW())
        ");
        $stmt->execute(['Nouveau Header', 'header-' . time()]);
        $editId = (int)$connection->lastInsertId();
    } catch (Exception $e) {
        echo '<div style="padding:20px;color:#ef4444">Erreur création : ' . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
}

if (!$editId) {
    echo '<script>window.location.href = "/admin/dashboard.php?page=design-headers";</script>';
    return;
}

// ── Charger les données du header ──
try {
    $stmt = $connection->prepare("SELECT * FROM headers WHERE id = ?");
    $stmt->execute([$editId]);
    $header = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div style="padding:20px;color:#ef4444">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

if (!$header) {
    echo '<div style="padding:40px;text-align:center;color:#ef4444"><i class="fas fa-exclamation-triangle"></i> Header #' . $editId . ' introuvable</div>';
    return;
}

// Décoder JSON
$menuItems = json_decode($header['menu_items'] ?? '[]', true) ?: [];
$socialLinks = json_decode($header['social_links'] ?? '[]', true) ?: [];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
/* ══════════════════════════════════════
   HEADER EDITOR — Styles
   ══════════════════════════════════════ */
.he-wrap{display:grid;grid-template-columns:380px 1fr;gap:0;height:calc(100vh - 120px);min-height:600px;background:#f1f5f9;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0}
.he-form{overflow-y:auto;background:#fff;padding:0;border-right:1px solid #e2e8f0}
.he-preview{display:flex;flex-direction:column;background:#f8fafc;overflow:hidden}
.he-preview-bar{padding:10px 16px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.he-preview-bar span{font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.he-preview-frame{flex:1;padding:16px;overflow:auto}
.he-preview-frame iframe{width:100%;height:200px;border:1px solid #e2e8f0;border-radius:8px;background:#fff}

/* Toolbar */
.he-toolbar{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.he-toolbar h2{font-size:16px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;margin:0}
.he-toolbar h2 i{color:#6366f1}
.he-toolbar-actions{display:flex;gap:8px}

/* Sections */
.he-section{border-bottom:1px solid #f1f5f9}
.he-section-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;cursor:pointer;transition:background .15s}
.he-section-header:hover{background:#f8fafc}
.he-section-header h3{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;display:flex;align-items:center;gap:8px;margin:0}
.he-section-header h3 i{font-size:14px;width:20px;text-align:center}
.he-section-toggle{border:none;background:none;color:#94a3b8;font-size:11px;cursor:pointer;transition:transform .2s;padding:4px}
.he-section-toggle.open{transform:rotate(180deg)}
.he-section-body{padding:0 20px 16px;display:none}
.he-section-body.open{display:block}

/* Form fields */
.he-field{margin-bottom:14px}
.he-field label{display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:5px}
.he-field input,.he-field select,.he-field textarea{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;transition:border-color .15s,box-shadow .15s}
.he-field input:focus,.he-field select:focus,.he-field textarea:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.he-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.he-field-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.he-color{display:flex;align-items:center;gap:8px}
.he-color input[type="color"]{width:36px;height:36px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px}
.he-color input[type="color"]::-webkit-color-swatch-wrapper{padding:0}
.he-color input[type="color"]::-webkit-color-swatch{border-radius:5px;border:none}
.he-color span{font-size:12px;color:#64748b;font-family:monospace}
.he-check{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;color:#475569;padding:4px 0}
.he-check input{accent-color:#6366f1;width:16px;height:16px}

/* Menu items */
.he-menu-list{list-style:none;padding:0;margin:0}
.he-menu-item{display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;transition:all .15s}
.he-menu-item:hover{border-color:#6366f1;background:#f5f3ff}
.he-menu-item .drag-handle{cursor:grab;color:#94a3b8;font-size:12px;padding:4px}
.he-menu-item .drag-handle:active{cursor:grabbing}
.he-menu-item input{flex:1;padding:5px 8px;border:1px solid transparent;border-radius:5px;font-size:12px;background:transparent;font-family:inherit}
.he-menu-item input:focus{border-color:#e2e8f0;background:#fff;outline:none}
.he-menu-item input.menu-label{font-weight:600;max-width:120px}
.he-menu-item input.menu-url{color:#64748b;font-family:monospace;font-size:11px}
.he-menu-item .menu-del{border:none;background:none;color:#cbd5e1;cursor:pointer;padding:4px;font-size:12px;transition:color .15s}
.he-menu-item .menu-del:hover{color:#ef4444}
.he-menu-add{display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;border:1px dashed #cbd5e1;border-radius:8px;cursor:pointer;color:#64748b;font-size:12px;font-weight:600;transition:all .15s;background:#fff;width:100%;font-family:inherit}
.he-menu-add:hover{border-color:#6366f1;color:#6366f1;background:#f5f3ff}

/* Buttons */
.he-btn{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid #e2e8f0;background:#fff;color:#475569;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .15s}
.he-btn:hover{border-color:#6366f1;color:#6366f1}
.he-btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none}
.he-btn-primary:hover{box-shadow:0 4px 12px rgba(99,102,241,.3);transform:translateY(-1px);color:#fff}
.he-btn-success{background:#10b981;color:#fff;border:none}
.he-btn-success:hover{background:#059669;color:#fff}
.he-btn-sm{padding:5px 10px;font-size:11px}
.he-btn-outline{background:transparent}

/* Logo type toggle */
.he-logo-toggle{display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:10px}
.he-logo-toggle button{flex:1;padding:8px;border:none;background:#f8fafc;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;color:#64748b;transition:all .15s}
.he-logo-toggle button.active{background:#6366f1;color:#fff}

/* Status badge */
.he-status{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700}
.he-status.active{background:#dcfce7;color:#16a34a}
.he-status.draft{background:#fef3c7;color:#d97706}
.he-status.inactive{background:#fee2e2;color:#ef4444}

@media(max-width:900px){
    .he-wrap{grid-template-columns:1fr;height:auto}
    .he-preview{min-height:300px}
}
</style>

<!-- ══════════════════════════════════════
     TOOLBAR
     ══════════════════════════════════════ -->
<div class="he-toolbar">
    <h2>
        <i class="fas fa-window-maximize"></i>
        <?= htmlspecialchars($header['name']) ?>
        <span class="he-status <?= $header['status'] ?>"><?= ucfirst($header['status']) ?></span>
        <?php if ($header['is_default']): ?><span style="background:#eff6ff;color:#3b82f6;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:700">⭐ Par défaut</span><?php endif; ?>
    </h2>
    <div class="he-toolbar-actions">
        <a href="/admin/dashboard.php?page=design-headers" class="he-btn"><i class="fas fa-arrow-left"></i> Retour</a>
        <button class="he-btn he-btn-primary" onclick="HE.save()"><i class="fas fa-save"></i> Sauvegarder</button>
        <button class="he-btn he-btn-success" onclick="HE.saveAndPublish()"><i class="fas fa-check-circle"></i> Publier</button>
    </div>
</div>

<!-- ══════════════════════════════════════
     MAIN LAYOUT
     ══════════════════════════════════════ -->
<div class="he-wrap">

    <!-- ═══ FORM PANEL ═══ -->
    <div class="he-form">

        <!-- Section: Général -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-cog" style="color:#6366f1"></i> Général</h3>
                <button class="he-section-toggle open"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body open">
                <div class="he-field">
                    <label>Nom du header</label>
                    <input type="text" id="he-name" value="<?= htmlspecialchars($header['name']) ?>" onchange="HE.preview()">
                </div>
                <div class="he-field-row">
                    <div class="he-field">
                        <label>Type</label>
                        <select id="he-type" onchange="HE.preview()">
                            <option value="standard" <?= $header['type']=='standard'?'selected':'' ?>>Standard</option>
                            <option value="sticky" <?= $header['type']=='sticky'?'selected':'' ?>>Sticky</option>
                            <option value="transparent" <?= $header['type']=='transparent'?'selected':'' ?>>Transparent</option>
                            <option value="minimal" <?= $header['type']=='minimal'?'selected':'' ?>>Minimal</option>
                        </select>
                    </div>
                    <div class="he-field">
                        <label>Statut</label>
                        <select id="he-status">
                            <option value="active" <?= $header['status']=='active'?'selected':'' ?>>Actif</option>
                            <option value="draft" <?= $header['status']=='draft'?'selected':'' ?>>Brouillon</option>
                            <option value="inactive" <?= $header['status']=='inactive'?'selected':'' ?>>Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="he-field">
                    <label class="he-check">
                        <input type="checkbox" id="he-default" <?= $header['is_default']?'checked':'' ?>>
                        Définir comme header par défaut
                    </label>
                </div>
            </div>
        </div>

        <!-- Section: Logo -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-image" style="color:#f59e0b"></i> Logo</h3>
                <button class="he-section-toggle open"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body open">
                <div class="he-logo-toggle">
                    <button id="logo-type-text" class="<?= ($header['logo_type']??'image')=='text'?'active':'' ?>" onclick="HE.setLogoType('text')"><i class="fas fa-font"></i> Texte</button>
                    <button id="logo-type-image" class="<?= ($header['logo_type']??'image')=='image'?'active':'' ?>" onclick="HE.setLogoType('image')"><i class="fas fa-image"></i> Image</button>
                </div>
                <div id="logo-text-fields" style="display:<?= ($header['logo_type']??'image')=='text'?'block':'none' ?>">
                    <div class="he-field">
                        <label>Texte du logo</label>
                        <input type="text" id="he-logo-text" value="<?= htmlspecialchars($header['logo_text'] ?? '') ?>" onchange="HE.preview()">
                    </div>
                </div>
                <div id="logo-image-fields" style="display:<?= ($header['logo_type']??'image')=='image'?'block':'none' ?>">
                    <div class="he-field">
                        <label>URL de l'image</label>
                        <input type="text" id="he-logo-url" value="<?= htmlspecialchars($header['logo_url'] ?? '') ?>" onchange="HE.preview()" placeholder="/assets/images/logo.png">
                    </div>
                    <div class="he-field">
                        <label>Texte alt</label>
                        <input type="text" id="he-logo-alt" value="<?= htmlspecialchars($header['logo_alt'] ?? '') ?>">
                    </div>
                </div>
                <div class="he-field-row">
                    <div class="he-field">
                        <label>Largeur (px)</label>
                        <input type="number" id="he-logo-width" value="<?= (int)($header['logo_width'] ?? 150) ?>" min="50" max="400" onchange="HE.preview()">
                    </div>
                    <div class="he-field">
                        <label>Lien logo</label>
                        <input type="text" id="he-logo-link" value="<?= htmlspecialchars($header['logo_link'] ?? '/') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Menu -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-bars" style="color:#3b82f6"></i> Menu de navigation</h3>
                <button class="he-section-toggle open"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body open">
                <ul class="he-menu-list" id="he-menu-list">
                    <?php foreach ($menuItems as $i => $item): ?>
                    <li class="he-menu-item" draggable="true" data-idx="<?= $i ?>">
                        <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                        <input type="text" class="menu-label" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="Label" onchange="HE.preview()">
                        <input type="text" class="menu-url" value="<?= htmlspecialchars($item['url'] ?? '') ?>" placeholder="/url" onchange="HE.preview()">
                        <button class="menu-del" onclick="HE.removeMenuItem(this)" title="Supprimer"><i class="fas fa-times"></i></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button class="he-menu-add" onclick="HE.addMenuItem()"><i class="fas fa-plus"></i> Ajouter un lien</button>
            </div>
        </div>

        <!-- Section: CTA -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-mouse-pointer" style="color:#10b981"></i> Bouton CTA</h3>
                <button class="he-section-toggle"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body">
                <div class="he-field">
                    <label class="he-check">
                        <input type="checkbox" id="he-cta-enabled" <?= $header['cta_enabled']?'checked':'' ?> onchange="HE.preview()">
                        Activer le bouton CTA
                    </label>
                </div>
                <div class="he-field-row">
                    <div class="he-field">
                        <label>Texte</label>
                        <input type="text" id="he-cta-text" value="<?= htmlspecialchars($header['cta_text'] ?? 'Contact') ?>" onchange="HE.preview()">
                    </div>
                    <div class="he-field">
                        <label>Lien</label>
                        <input type="text" id="he-cta-link" value="<?= htmlspecialchars($header['cta_link'] ?? '/contact') ?>" onchange="HE.preview()">
                    </div>
                </div>
                <div class="he-field">
                    <label>Style</label>
                    <select id="he-cta-style" onchange="HE.preview()">
                        <option value="primary" <?= ($header['cta_style']??'')=='primary'?'selected':'' ?>>Primary (plein)</option>
                        <option value="secondary" <?= ($header['cta_style']??'')=='secondary'?'selected':'' ?>>Secondary</option>
                        <option value="outline" <?= ($header['cta_style']??'')=='outline'?'selected':'' ?>>Outline (contour)</option>
                        <option value="gradient" <?= ($header['cta_style']??'')=='gradient'?'selected':'' ?>>Gradient</option>
                    </select>
                </div>
                <!-- CTA 2 -->
                <div class="he-field" style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9">
                    <label class="he-check">
                        <input type="checkbox" id="he-cta2-enabled" <?= $header['cta2_enabled']?'checked':'' ?> onchange="HE.preview()">
                        Activer un 2ème bouton
                    </label>
                </div>
                <div class="he-field-row">
                    <div class="he-field">
                        <label>Texte 2</label>
                        <input type="text" id="he-cta2-text" value="<?= htmlspecialchars($header['cta2_text'] ?? '') ?>" onchange="HE.preview()">
                    </div>
                    <div class="he-field">
                        <label>Lien 2</label>
                        <input type="text" id="he-cta2-link" value="<?= htmlspecialchars($header['cta2_link'] ?? '') ?>" onchange="HE.preview()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Couleurs & Style -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-palette" style="color:#ec4899"></i> Couleurs & Style</h3>
                <button class="he-section-toggle"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body">
                <div class="he-field-row3">
                    <div class="he-field">
                        <label>Fond</label>
                        <div class="he-color">
                            <input type="color" id="he-bg-color" value="<?= $header['bg_color'] ?? '#ffffff' ?>" onchange="HE.preview()">
                            <span id="he-bg-val"><?= $header['bg_color'] ?? '#ffffff' ?></span>
                        </div>
                    </div>
                    <div class="he-field">
                        <label>Texte</label>
                        <div class="he-color">
                            <input type="color" id="he-text-color" value="<?= $header['text_color'] ?? '#1e293b' ?>" onchange="HE.preview()">
                            <span id="he-text-val"><?= $header['text_color'] ?? '#1e293b' ?></span>
                        </div>
                    </div>
                    <div class="he-field">
                        <label>Hover</label>
                        <div class="he-color">
                            <input type="color" id="he-hover-color" value="<?= $header['hover_color'] ?? '#3b82f6' ?>" onchange="HE.preview()">
                            <span id="he-hover-val"><?= $header['hover_color'] ?? '#3b82f6' ?></span>
                        </div>
                    </div>
                </div>
                <div class="he-field-row">
                    <div class="he-field">
                        <label>Hauteur (px)</label>
                        <input type="number" id="he-height" value="<?= (int)($header['height'] ?? 80) ?>" min="50" max="150" onchange="HE.preview()">
                    </div>
                    <div class="he-field">
                        <label>Menu mobile (px)</label>
                        <input type="number" id="he-mobile-bp" value="<?= (int)($header['mobile_breakpoint'] ?? 1024) ?>" min="768" max="1200">
                    </div>
                </div>
                <div class="he-field" style="display:flex;flex-wrap:wrap;gap:12px">
                    <label class="he-check"><input type="checkbox" id="he-sticky" <?= $header['sticky']?'checked':'' ?> onchange="HE.preview()"> Sticky</label>
                    <label class="he-check"><input type="checkbox" id="he-shadow" <?= $header['shadow']?'checked':'' ?> onchange="HE.preview()"> Shadow</label>
                    <label class="he-check"><input type="checkbox" id="he-border" <?= $header['border_bottom']?'checked':'' ?> onchange="HE.preview()"> Bordure basse</label>
                </div>
            </div>
        </div>

        <!-- Section: Téléphone -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-phone" style="color:#06b6d4"></i> Téléphone</h3>
                <button class="he-section-toggle"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body">
                <div class="he-field">
                    <label class="he-check">
                        <input type="checkbox" id="he-phone-enabled" <?= $header['phone_enabled']?'checked':'' ?> onchange="HE.preview()">
                        Afficher le téléphone
                    </label>
                </div>
                <div class="he-field">
                    <label>Numéro</label>
                    <input type="text" id="he-phone" value="<?= htmlspecialchars($header['phone_number'] ?? '') ?>" onchange="HE.preview()" placeholder="06 12 34 56 78">
                </div>
            </div>
        </div>

        <!-- Section: CSS/JS perso -->
        <div class="he-section">
            <div class="he-section-header" onclick="HE.toggleSection(this)">
                <h3><i class="fas fa-code" style="color:#a855f7"></i> Code personnalisé</h3>
                <button class="he-section-toggle"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="he-section-body">
                <div class="he-field">
                    <label>CSS personnalisé</label>
                    <textarea id="he-custom-css" rows="4" style="font-family:monospace;font-size:12px" onchange="HE.preview()"><?= htmlspecialchars($header['custom_css'] ?? '') ?></textarea>
                </div>
                <div class="he-field">
                    <label>HTML personnalisé (ajouté après le header)</label>
                    <textarea id="he-custom-html" rows="4" style="font-family:monospace;font-size:12px" onchange="HE.preview()"><?= htmlspecialchars($header['custom_html'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- ═══ PREVIEW PANEL ═══ -->
    <div class="he-preview">
        <div class="he-preview-bar">
            <span><i class="fas fa-eye"></i> Preview temps réel</span>
            <div style="display:flex;gap:6px">
                <button class="he-btn he-btn-sm he-btn-outline" onclick="HE.preview()"><i class="fas fa-sync-alt"></i></button>
                <button class="he-btn he-btn-sm he-btn-outline" onclick="HE.previewSize('100%')">Desktop</button>
                <button class="he-btn he-btn-sm he-btn-outline" onclick="HE.previewSize('768px')">Tablet</button>
                <button class="he-btn he-btn-sm he-btn-outline" onclick="HE.previewSize('375px')">Mobile</button>
            </div>
        </div>
        <div class="he-preview-frame">
            <iframe id="he-iframe" style="width:100%;transition:width .3s"></iframe>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     JAVASCRIPT — Header Editor
     ══════════════════════════════════════ -->
<script>
const HE = {
    headerId: <?= $editId ?>,
    
    toggleSection(header) {
        const toggle = header.querySelector('.he-section-toggle');
        const body = header.nextElementSibling;
        toggle.classList.toggle('open');
        body.classList.toggle('open');
    },

    setLogoType(type) {
        document.getElementById('logo-type-text').classList.toggle('active', type === 'text');
        document.getElementById('logo-type-image').classList.toggle('active', type === 'image');
        document.getElementById('logo-text-fields').style.display = type === 'text' ? 'block' : 'none';
        document.getElementById('logo-image-fields').style.display = type === 'image' ? 'block' : 'none';
        this.preview();
    },

    getLogoType() {
        return document.getElementById('logo-type-text').classList.contains('active') ? 'text' : 'image';
    },

    addMenuItem() {
        const list = document.getElementById('he-menu-list');
        const li = document.createElement('li');
        li.className = 'he-menu-item';
        li.draggable = true;
        li.innerHTML = `
            <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
            <input type="text" class="menu-label" value="" placeholder="Label" onchange="HE.preview()">
            <input type="text" class="menu-url" value="" placeholder="/url" onchange="HE.preview()">
            <button class="menu-del" onclick="HE.removeMenuItem(this)" title="Supprimer"><i class="fas fa-times"></i></button>
        `;
        list.appendChild(li);
        this.initDragDrop();
        li.querySelector('.menu-label').focus();
    },

    removeMenuItem(btn) {
        btn.closest('.he-menu-item').remove();
        this.preview();
    },

    getMenuItems() {
        const items = [];
        document.querySelectorAll('.he-menu-item').forEach(li => {
            const label = li.querySelector('.menu-label').value.trim();
            const url = li.querySelector('.menu-url').value.trim();
            if (label) items.push({ label, url: url || '#' });
        });
        return items;
    },

    initDragDrop() {
        const list = document.getElementById('he-menu-list');
        let draggedItem = null;
        list.querySelectorAll('.he-menu-item').forEach(item => {
            item.addEventListener('dragstart', () => { draggedItem = item; item.style.opacity = '0.4'; });
            item.addEventListener('dragend', () => { draggedItem = null; item.style.opacity = '1'; this.preview(); });
            item.addEventListener('dragover', (e) => { e.preventDefault(); });
            item.addEventListener('drop', (e) => {
                e.preventDefault();
                if (draggedItem && draggedItem !== item) {
                    const rect = item.getBoundingClientRect();
                    const mid = rect.top + rect.height / 2;
                    if (e.clientY < mid) {
                        list.insertBefore(draggedItem, item);
                    } else {
                        list.insertBefore(draggedItem, item.nextSibling);
                    }
                }
            });
        });
    },

    collectData() {
        return {
            id: this.headerId,
            name: document.getElementById('he-name').value,
            type: document.getElementById('he-type').value,
            status: document.getElementById('he-status').value,
            is_default: document.getElementById('he-default').checked ? 1 : 0,
            logo_type: this.getLogoType(),
            logo_text: document.getElementById('he-logo-text').value,
            logo_url: document.getElementById('he-logo-url').value,
            logo_alt: document.getElementById('he-logo-alt').value,
            logo_width: parseInt(document.getElementById('he-logo-width').value) || 150,
            logo_link: document.getElementById('he-logo-link').value,
            menu_items: this.getMenuItems(),
            cta_enabled: document.getElementById('he-cta-enabled').checked ? 1 : 0,
            cta_text: document.getElementById('he-cta-text').value,
            cta_link: document.getElementById('he-cta-link').value,
            cta_style: document.getElementById('he-cta-style').value,
            cta2_enabled: document.getElementById('he-cta2-enabled').checked ? 1 : 0,
            cta2_text: document.getElementById('he-cta2-text').value,
            cta2_link: document.getElementById('he-cta2-link').value,
            phone_enabled: document.getElementById('he-phone-enabled').checked ? 1 : 0,
            phone_number: document.getElementById('he-phone').value,
            bg_color: document.getElementById('he-bg-color').value,
            text_color: document.getElementById('he-text-color').value,
            hover_color: document.getElementById('he-hover-color').value,
            height: parseInt(document.getElementById('he-height').value) || 80,
            sticky: document.getElementById('he-sticky').checked ? 1 : 0,
            shadow: document.getElementById('he-shadow').checked ? 1 : 0,
            border_bottom: document.getElementById('he-border').checked ? 1 : 0,
            mobile_breakpoint: parseInt(document.getElementById('he-mobile-bp').value) || 1024,
            custom_css: document.getElementById('he-custom-css').value,
            custom_html: document.getElementById('he-custom-html').value
        };
    },

    generatePreviewHtml() {
        const d = this.collectData();
        const menuHtml = d.menu_items.map(m => 
            `<a href="${m.url}" style="color:${d.text_color};text-decoration:none;font-size:14px;font-weight:500;padding:8px 14px;border-radius:6px;transition:color .2s">${m.label}</a>`
        ).join('');

        let logoHtml = '';
        if (d.logo_type === 'text') {
            logoHtml = `<a href="${d.logo_link}" style="font-size:20px;font-weight:800;color:${d.text_color};text-decoration:none">${d.logo_text || 'Mon Site'}</a>`;
        } else {
            if (d.logo_url) {
                logoHtml = `<a href="${d.logo_link}"><img src="${d.logo_url}" alt="${d.logo_alt || ''}" style="height:auto;max-width:${d.logo_width}px;max-height:${d.height - 20}px"></a>`;
            } else {
                logoHtml = `<a href="${d.logo_link}" style="font-size:20px;font-weight:800;color:${d.text_color};text-decoration:none">Header Principal</a>`;
            }
        }

        let ctaHtml = '';
        if (d.cta_enabled) {
            const ctaStyles = {
                primary: `background:${d.hover_color};color:#fff;border:none`,
                secondary: `background:#f1f5f9;color:${d.text_color};border:none`,
                outline: `background:transparent;color:${d.hover_color};border:2px solid ${d.hover_color}`,
                gradient: `background:linear-gradient(135deg,${d.hover_color},#8b5cf6);color:#fff;border:none`
            };
            ctaHtml = `<a href="${d.cta_link}" style="display:inline-flex;align-items:center;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;${ctaStyles[d.cta_style] || ctaStyles.primary}">${d.cta_text}</a>`;
        }
        if (d.cta2_enabled && d.cta2_text) {
            ctaHtml += `<a href="${d.cta2_link || '#'}" style="display:inline-flex;align-items:center;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;background:transparent;color:${d.text_color};border:1px solid ${d.text_color}3;margin-left:8px">${d.cta2_text}</a>`;
        }

        let phoneHtml = '';
        if (d.phone_enabled && d.phone_number) {
            phoneHtml = `<a href="tel:${d.phone_number.replace(/\s/g,'')}" style="color:${d.text_color};text-decoration:none;font-size:13px;display:flex;align-items:center;gap:6px;margin-right:12px"><i class="fas fa-phone" style="font-size:11px;color:${d.hover_color}"></i>${d.phone_number}</a>`;
        }

        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif}
        ${d.custom_css}
    </style>
</head>
<body>
    <header style="
        display:flex;align-items:center;justify-content:space-between;
        height:${d.height}px;padding:0 24px;
        background:${d.bg_color};color:${d.text_color};
        ${d.shadow ? 'box-shadow:0 2px 10px rgba(0,0,0,.08);' : ''}
        ${d.border_bottom ? 'border-bottom:1px solid #e2e8f0;' : ''}
    ">
        <div style="display:flex;align-items:center;gap:10px">
            ${logoHtml}
        </div>
        <nav style="display:flex;align-items:center;gap:4px">
            ${menuHtml}
        </nav>
        <div style="display:flex;align-items:center;gap:8px">
            ${phoneHtml}
            ${ctaHtml}
        </div>
    </header>
    ${d.custom_html}
    <div style="padding:40px 24px;color:#94a3b8;font-size:13px;text-align:center">
        <p>← Contenu de la page ici →</p>
    </div>
</body>
</html>`;
    },

    preview() {
        // Update color labels
        ['bg', 'text', 'hover'].forEach(k => {
            const val = document.getElementById(`he-${k}-color`).value;
            const span = document.getElementById(`he-${k}-val`);
            if (span) span.textContent = val;
        });

        const frame = document.getElementById('he-iframe');
        frame.srcdoc = this.generatePreviewHtml();
    },

    previewSize(w) {
        document.getElementById('he-iframe').style.width = w;
    },

    async save(publish = false) {
        const data = this.collectData();
        if (publish) data.status = 'active';

        try {
            const response = await fetch('/admin/modules/builder/api/save-header.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                this.toast(publish ? '✅ Header publié !' : '💾 Header sauvegardé !', 'success');
                // Update status badge
                if (publish) {
                    document.getElementById('he-status').value = 'active';
                }
            } else {
                this.toast('❌ ' + (result.error || 'Erreur'), 'error');
            }
        } catch (e) {
            this.toast('❌ Erreur réseau : ' + e.message, 'error');
        }
    },

    saveAndPublish() { this.save(true); },

    toast(msg, type = 'info') {
        const t = document.createElement('div');
        const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#6366f1' };
        t.style.cssText = `position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;color:#fff;max-width:400px;background:${colors[type]||colors.info};box-shadow:0 4px 12px rgba(0,0,0,.15);animation:heSlideUp .3s`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
    }
};

// Animations
const heStyle = document.createElement('style');
heStyle.textContent = '@keyframes heSlideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
document.head.appendChild(heStyle);

// Init
HE.initDragDrop();
HE.preview();
</script>