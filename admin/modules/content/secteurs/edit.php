<?php
/**
 * MODULE SECTEURS - Édition / Création
 * /admin/modules/secteurs/edit.php
 * 
 * Formulaire complet connecté à la table `secteurs`
 * Tous les champs : infos générales, hero, SEO, contenu
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/classes/Database.php';

$db = Database::getInstance();

$itemId = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'edit';
$error = null;
$success = null;

// ─── CRÉATION ───
if ($action === 'create') {
    try {
        $stmt = $db->prepare("INSERT INTO secteurs (nom, slug, ville, type_secteur, status, created_at) VALUES (?, ?, 'Bordeaux', 'quartier', 'draft', NOW())");
        $stmt->execute(['Nouveau secteur', 'nouveau-secteur-' . time()]);
        $itemId = $db->lastInsertId();
        header("Location: /admin/modules/secteurs/edit.php?id=$itemId&success=created");
        exit;
    } catch (PDOException $e) {
        $error = "Erreur création: " . $e->getMessage();
    }
}

// ─── CHARGEMENT DU SECTEUR ───
if ($itemId <= 0) {
    header('Location: /admin/dashboard.php?page=secteurs&error=no_id');
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM secteurs WHERE id = ?");
    $stmt->execute([$itemId]);
    $secteur = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur DB: " . $e->getMessage();
    $secteur = null;
}

if (!$secteur) {
    header('Location: /admin/dashboard.php?page=secteurs&error=not_found');
    exit;
}

// ─── SAUVEGARDE POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_secteur'])) {
    try {
        $data = [
            'nom'              => trim($_POST['nom'] ?? ''),
            'slug'             => trim($_POST['slug'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'type_secteur'     => $_POST['type_secteur'] ?? 'quartier',
            'status'           => $_POST['status'] ?? 'draft',
            'meta_title'       => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords'    => trim($_POST['meta_keywords'] ?? ''),
            'canonical_url'    => trim($_POST['canonical_url'] ?? ''),
            'og_image'         => trim($_POST['og_image'] ?? ''),
            'hero_image'       => trim($_POST['hero_image'] ?? ''),
            'hero_title'       => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle'    => trim($_POST['hero_subtitle'] ?? ''),
            'hero_cta_text'    => trim($_POST['hero_cta_text'] ?? ''),
            'hero_cta_url'     => trim($_POST['hero_cta_url'] ?? ''),
            'content'          => $_POST['content'] ?? '',
            'description'      => trim($_POST['description'] ?? ''),
            'atouts'           => trim($_POST['atouts'] ?? ''),
            'prix_moyen'       => trim($_POST['prix_moyen'] ?? ''),
            'transport'        => trim($_POST['transport'] ?? ''),
            'ambiance'         => trim($_POST['ambiance'] ?? ''),
        ];
        
        // Auto-génération du slug si vide
        if (empty($data['slug'])) {
            $data['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['nom']));
            $data['slug'] = trim($data['slug'], '-');
            if ($data['ville']) {
                $data['slug'] .= '-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['ville']));
            }
        }
        
        // Construction dynamique du UPDATE
        $setClauses = [];
        $params = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $itemId;
        
        $sql = "UPDATE secteurs SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Recharger
        $stmt = $db->prepare("SELECT * FROM secteurs WHERE id = ?");
        $stmt->execute([$itemId]);
        $secteur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = "Secteur mis à jour avec succès !";
        
    } catch (PDOException $e) {
        $error = "Erreur sauvegarde: " . $e->getMessage();
    }
}

// Extraire les données
$s = $secteur;
$nom = $s['nom'] ?? '';
$slug = $s['slug'] ?? '';
$ville = $s['ville'] ?? 'Bordeaux';
$typeSecteur = $s['type_secteur'] ?? 'quartier';
$status = $s['status'] ?? 'draft';
$metaTitle = $s['meta_title'] ?? '';
$metaDescription = $s['meta_description'] ?? '';
$metaKeywords = $s['meta_keywords'] ?? '';
$canonicalUrl = $s['canonical_url'] ?? '';
$ogImage = $s['og_image'] ?? '';
$heroImage = $s['hero_image'] ?? '';
$heroTitle = $s['hero_title'] ?? '';
$heroSubtitle = $s['hero_subtitle'] ?? '';
$heroCta = $s['hero_cta_text'] ?? $s['hero_cta'] ?? '';
$heroCtaUrl = $s['hero_cta_url'] ?? '';
$content = $s['content'] ?? '';
$description = $s['description'] ?? '';
$atouts = $s['atouts'] ?? '';
$prixMoyen = $s['prix_moyen'] ?? '';
$transport = $s['transport'] ?? '';
$ambiance = $s['ambiance'] ?? '';

$successParam = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition: <?= htmlspecialchars($nom) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #1e293b; }
        
        /* ─── TOPBAR ─── */
        .topbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        
        .topbar__left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .btn-back {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-back:hover { background: #e2e8f0; color: #1e293b; }
        
        .topbar__title h1 {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .topbar__title .badge-type {
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-type.quartier { background: #fef3c7; color: #92400e; }
        .badge-type.commune { background: #ede9fe; color: #6d28d9; }
        
        .topbar__slug {
            font-size: 11px;
            color: #94a3b8;
            font-family: monospace;
        }
        
        .topbar__right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        
        .btn-builder {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
        }
        .btn-builder:hover { opacity: 0.9; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge.published { background: #d1fae5; color: #065f46; }
        .status-badge.draft { background: #fef3c7; color: #92400e; }
        
        /* ─── MAIN LAYOUT ─── */
        .edit-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 24px;
        }
        
        @media (max-width: 1024px) {
            .edit-layout { grid-template-columns: 1fr; }
        }
        
        /* ─── CARDS ─── */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header h3 i { color: #3b82f6; font-size: 14px; }
        
        .card-body { padding: 20px; }
        
        /* ─── FORM ─── */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        
        .form-group label .required { color: #ef4444; }
        
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            color: #1e293b;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .form-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .char-counter {
            text-align: right;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }
        
        .char-counter.warning { color: #f59e0b; }
        .char-counter.danger { color: #ef4444; }
        
        /* ─── HERO PREVIEW ─── */
        .hero-preview {
            margin-top: 16px;
            border-radius: 10px;
            overflow: hidden;
            background: #1e293b;
            position: relative;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        
        .hero-preview__bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0.5;
        }
        
        .hero-preview__content {
            position: relative;
            z-index: 2;
        }
        
        .hero-preview__title {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        
        .hero-preview__subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            margin-bottom: 12px;
        }
        
        .hero-preview__cta {
            display: inline-block;
            padding: 8px 20px;
            background: #f59e0b;
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .hero-preview__empty {
            color: #64748b;
            font-size: 13px;
        }
        
        /* ─── ALERTS ─── */
        .alert-bar {
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-bar.success { background: #d1fae5; color: #065f46; }
        .alert-bar.error { background: #fee2e2; color: #991b1b; }
        
        /* ─── SEO PREVIEW ─── */
        .seo-preview {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        
        .seo-preview__url {
            font-size: 12px;
            color: #059669;
            margin-bottom: 2px;
        }
        
        .seo-preview__title {
            font-size: 16px;
            color: #1a0dab;
            font-weight: 500;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .seo-preview__desc {
            font-size: 13px;
            color: #545454;
            line-height: 1.4;
        }
        
        /* ─── IMAGE INPUT ─── */
        .image-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        
        .image-input-wrapper input { flex: 1; }
        
        .image-thumb-preview {
            width: 60px;
            height: 45px;
            border-radius: 6px;
            background-size: cover;
            background-position: center;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        
        .image-thumb-empty {
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 16px;
        }
    </style>
</head>
<body>

<form method="POST" id="secteurForm">
    <input type="hidden" name="save_secteur" value="1">

    <!-- ══════ TOPBAR ══════ -->
    <div class="topbar">
        <div class="topbar__left">
            <a href="/admin/dashboard.php?page=secteurs" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <div class="topbar__title">
                <h1>
                    <?= htmlspecialchars($nom) ?>
                    <span class="badge-type <?= $typeSecteur ?>"><?= $typeSecteur === 'commune' ? '🏙️ Commune' : '🏘️ Quartier' ?></span>
                </h1>
                <div class="topbar__slug">/<?= htmlspecialchars($slug) ?></div>
            </div>
        </div>
        <div class="topbar__right">
            <span class="status-badge <?= $status ?>" id="statusIndicator">
                <?= $status === 'published' ? '🟢 Publié' : '🟡 Brouillon' ?>
            </span>
            <a href="/admin/modules/builder-pages/index.php?type=secteur&id=<?= $itemId ?>" class="btn btn-builder">
                <i class="fas fa-magic"></i> Builder Pro
            </a>
            <button type="submit" name="status" value="draft" class="btn btn-secondary">
                <i class="fas fa-save"></i> Brouillon
            </button>
            <button type="submit" name="status" value="published" class="btn btn-success">
                <i class="fas fa-check"></i> Publier
            </button>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success || $successParam === 'created'): ?>
    <div class="alert-bar success">
        <i class="fas fa-check-circle"></i> <?= $success ?: 'Secteur créé ! Complétez les informations ci-dessous.' ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert-bar error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ══════ MAIN LAYOUT ══════ -->
    <div class="edit-layout">
        
        <!-- ── COLONNE PRINCIPALE ── -->
        <div class="main-column">
            
            <!-- Informations générales -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-info-circle"></i> Informations générales</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nom du secteur <span class="required">*</span></label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required placeholder="Ex: Bacalan, Saint-Pierre, Talence...">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Slug (URL)</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($slug) ?>" placeholder="bacalan-bordeaux">
                            <div class="form-hint">Laisser vide pour auto-génération</div>
                        </div>
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="ville" value="<?= htmlspecialchars($ville) ?>" placeholder="Bordeaux">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type de secteur</label>
                            <select name="type_secteur">
                                <option value="quartier" <?= $typeSecteur === 'quartier' ? 'selected' : '' ?>>🏘️ Quartier</option>
                                <option value="commune" <?= $typeSecteur === 'commune' ? 'selected' : '' ?>>🏙️ Commune</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="status" id="statusSelect">
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>🟡 Brouillon</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>🟢 Publié</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description courte</label>
                        <textarea name="description" rows="3" placeholder="Brève description du quartier pour les listings..."><?= htmlspecialchars($description) ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Section Hero -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-image"></i> Section Hero</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Image Hero (URL)</label>
                        <div class="image-input-wrapper">
                            <input type="url" name="hero_image" id="heroImageInput" value="<?= htmlspecialchars($heroImage) ?>" placeholder="https://images.unsplash.com/..." oninput="updateHeroPreview()">
                            <?php if ($heroImage): ?>
                            <div class="image-thumb-preview" style="background-image: url('<?= htmlspecialchars($heroImage) ?>')"></div>
                            <?php else: ?>
                            <div class="image-thumb-preview image-thumb-empty"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Titre Hero</label>
                        <input type="text" name="hero_title" id="heroTitleInput" value="<?= htmlspecialchars($heroTitle) ?>" placeholder="Ex: Bacalan : le quartier en pleine transformation" oninput="updateHeroPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label>Sous-titre Hero</label>
                        <textarea name="hero_subtitle" id="heroSubtitleInput" rows="2" placeholder="Description captivante..." oninput="updateHeroPreview()"><?= htmlspecialchars($heroSubtitle) ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Texte CTA</label>
                            <input type="text" name="hero_cta_text" id="heroCtaInput" value="<?= htmlspecialchars($heroCta) ?>" placeholder="Voir les biens" oninput="updateHeroPreview()">
                        </div>
                        <div class="form-group">
                            <label>URL CTA</label>
                            <input type="text" name="hero_cta_url" value="<?= htmlspecialchars($heroCtaUrl) ?>" placeholder="/biens?secteur=bacalan">
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div class="hero-preview" id="heroPreview">
                        <?php if ($heroImage): ?>
                        <div class="hero-preview__bg" id="heroBg" style="background-image: url('<?= htmlspecialchars($heroImage) ?>')"></div>
                        <?php else: ?>
                        <div class="hero-preview__bg" id="heroBg"></div>
                        <?php endif; ?>
                        <div class="hero-preview__content">
                            <div class="hero-preview__title" id="heroPreviewTitle"><?= htmlspecialchars($heroTitle ?: 'Titre du hero') ?></div>
                            <div class="hero-preview__subtitle" id="heroPreviewSubtitle"><?= htmlspecialchars($heroSubtitle ?: 'Sous-titre') ?></div>
                            <?php if ($heroCta): ?>
                            <div class="hero-preview__cta" id="heroPreviewCta"><?= htmlspecialchars($heroCta) ?></div>
                            <?php else: ?>
                            <div class="hero-preview__cta" id="heroPreviewCta" style="display:none;"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenu -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-align-left"></i> Contenu de la page</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Contenu HTML (ou utilisez le Builder Pro pour une édition visuelle)</label>
                        <textarea name="content" rows="15" style="font-family: 'Monaco', 'Consolas', monospace; font-size: 12px; line-height: 1.6;"><?= htmlspecialchars($content) ?></textarea>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Pour une édition visuelle avancée, utilisez le 
                            <a href="/admin/modules/builder-pages/index.php?type=secteur&id=<?= $itemId ?>" style="color: #7c3aed; font-weight: 600;">Builder Pro</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Infos quartier -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-star"></i> Informations du secteur</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Atouts / Points forts</label>
                        <textarea name="atouts" rows="3" placeholder="Les Chartrons allient patrimoine ancien et esprit bohème..."><?= htmlspecialchars($atouts) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Ambiance</label>
                        <textarea name="ambiance" rows="2" placeholder="Familial, branché, dynamique..."><?= htmlspecialchars($ambiance) ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prix moyen</label>
                            <input type="text" name="prix_moyen" value="<?= htmlspecialchars($prixMoyen) ?>" placeholder="4 500 €/m²">
                        </div>
                        <div class="form-group">
                            <label>Transports</label>
                            <input type="text" name="transport" value="<?= htmlspecialchars($transport) ?>" placeholder="Tram A, B - Bus 9, 16">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ── SIDEBAR ── -->
        <div class="sidebar-column">
            
            <!-- SEO -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-search"></i> SEO</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" id="metaTitleInput" value="<?= htmlspecialchars($metaTitle) ?>" maxlength="70" placeholder="Titre SEO optimisé" oninput="updateSeoPreview(); updateCounter(this, 70)">
                        <div class="char-counter" id="metaTitleCounter"><?= strlen($metaTitle) ?>/70</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" id="metaDescInput" maxlength="160" rows="3" placeholder="Description pour les moteurs de recherche..." oninput="updateSeoPreview(); updateCounter(this, 160)"><?= htmlspecialchars($metaDescription) ?></textarea>
                        <div class="char-counter" id="metaDescCounter"><?= strlen($metaDescription) ?>/160</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Keywords</label>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($metaKeywords) ?>" placeholder="immobilier, bordeaux, quartier...">
                        <div class="form-hint">Séparés par des virgules</div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL Canonique</label>
                        <input type="url" name="canonical_url" value="<?= htmlspecialchars($canonicalUrl) ?>" placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label>Image OG</label>
                        <input type="url" name="og_image" value="<?= htmlspecialchars($ogImage) ?>" placeholder="https://...">
                    </div>
                    
                    <!-- SEO Preview Google -->
                    <div class="seo-preview" id="seoPreview">
                        <div class="seo-preview__url">www.eduardo-desul.fr › <?= htmlspecialchars($slug) ?></div>
                        <div class="seo-preview__title" id="seoPreviewTitle"><?= htmlspecialchars($metaTitle ?: $nom) ?></div>
                        <div class="seo-preview__desc" id="seoPreviewDesc"><?= htmlspecialchars($metaDescription ?: 'Découvrez le secteur...') ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Aperçu rapide -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-eye"></i> Aperçu</h3></div>
                <div class="card-body" style="text-align: center;">
                    <?php if ($status === 'published'): ?>
                    <a href="/<?= htmlspecialchars($slug) ?>" target="_blank" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-external-link-alt"></i> Voir sur le site
                    </a>
                    <?php else: ?>
                    <p style="font-size: 12px; color: #94a3b8;">Publiez le secteur pour le voir sur le site</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Infos système -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-database"></i> Informations</h3></div>
                <div class="card-body">
                    <div style="font-size: 12px; color: #64748b; line-height: 2;">
                        <div><strong>ID :</strong> <?= $itemId ?></div>
                        <div><strong>Créé le :</strong> <?= isset($s['created_at']) ? date('d/m/Y H:i', strtotime($s['created_at'])) : '—' ?></div>
                        <div><strong>Modifié le :</strong> <?= isset($s['updated_at']) ? date('d/m/Y H:i', strtotime($s['updated_at'])) : '—' ?></div>
                        <div><strong>Table :</strong> <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px;">secteurs</code></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</form>

<script>
// Hero preview live
function updateHeroPreview() {
    const img = document.getElementById('heroImageInput')?.value;
    const title = document.getElementById('heroTitleInput')?.value;
    const subtitle = document.getElementById('heroSubtitleInput')?.value;
    const cta = document.getElementById('heroCtaInput')?.value;
    
    const bg = document.getElementById('heroBg');
    const pTitle = document.getElementById('heroPreviewTitle');
    const pSub = document.getElementById('heroPreviewSubtitle');
    const pCta = document.getElementById('heroPreviewCta');
    
    if (bg) bg.style.backgroundImage = img ? `url('${img}')` : 'none';
    if (pTitle) pTitle.textContent = title || 'Titre du hero';
    if (pSub) pSub.textContent = subtitle || 'Sous-titre';
    if (pCta) {
        pCta.textContent = cta;
        pCta.style.display = cta ? 'inline-block' : 'none';
    }
}

// SEO preview live
function updateSeoPreview() {
    const title = document.getElementById('metaTitleInput')?.value;
    const desc = document.getElementById('metaDescInput')?.value;
    
    document.getElementById('seoPreviewTitle').textContent = title || document.querySelector('[name="nom"]')?.value || 'Titre';
    document.getElementById('seoPreviewDesc').textContent = desc || 'Découvrez ce secteur à Bordeaux...';
}

// Char counter
function updateCounter(el, max) {
    const len = el.value.length;
    const counter = el.parentElement.querySelector('.char-counter');
    if (counter) {
        counter.textContent = len + '/' + max;
        counter.className = 'char-counter';
        if (len > max * 0.85) counter.classList.add('warning');
        if (len >= max) counter.classList.add('danger');
    }
}

// Raccourci Ctrl+S
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('secteurForm').submit();
    }
});
</script>
</body>
</html>