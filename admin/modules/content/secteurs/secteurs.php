<?php
/**
 * PAGE PUBLIQUE : LISTING DES SECTEURS
 * Fichier : /secteurs.php (ou /public/pages/secteurs.php selon routing)
 * 
 * Affiche tous les quartiers/communes publiés depuis la table `secteurs`
 * Design immobilier premium avec cartes, filtres, recherche
 * 
 * v3.0 - Refonte complète : corrige le bug "Variables boucle" 
 *        et le listing vide
 */

// ─── INIT ───
$rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);

// Essayer plusieurs chemins pour Database.php (compatibilité)
$dbPaths = [
    $rootPath . '/includes/classes/Database.php',
    $rootPath . '/includes/Database.php',
    dirname(__DIR__) . '/includes/classes/Database.php',
    dirname(__DIR__) . '/includes/Database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/Database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/Database.php',
];

$dbLoaded = false;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $dbLoaded = true;
        break;
    }
}

if (!$dbLoaded) {
    die('Erreur: Impossible de charger la classe Database.');
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die('Erreur de connexion à la base de données.');
}

// ─── RÉCUPÉRER LES SECTEURS PUBLIÉS ───
$secteurs = [];
$stats = ['total' => 0, 'quartiers' => 0, 'communes' => 0];

try {
    // Secteurs publiés, triés par nom
    $stmt = $db->query("
        SELECT id, nom, slug, ville, type_secteur, 
               hero_image, hero_title, hero_subtitle,
               meta_title, meta_description,
               prix_min, prix_max, rendement_min, rendement_max,
               latitude, longitude, code_postal,
               presentation, atouts, transport, ambiance
        FROM secteurs 
        WHERE status = 'published' 
        ORDER BY ville ASC, nom ASC
    ");
    $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stats
    $stmtStats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type_secteur = 'quartier' THEN 1 ELSE 0 END) as quartiers,
            SUM(CASE WHEN type_secteur = 'commune' THEN 1 ELSE 0 END) as communes
        FROM secteurs 
        WHERE status = 'published'
    ");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: $stats;
    
} catch (PDOException $e) {
    error_log("Erreur secteurs listing: " . $e->getMessage());
}

// ─── GROUPER PAR VILLE ───
$parVille = [];
foreach ($secteurs as $s) {
    $ville = $s['ville'] ?: 'Autre';
    $parVille[$ville][] = $s;
}

// ─── SEO ───
$pageTitle = "Quartiers et communes de Bordeaux | Eduardo De Sul Immobilier";
$pageDescription = "Découvrez tous les quartiers et communes de Bordeaux Métropole : prix immobiliers, cadre de vie, conseils d'expert. Guide complet par Eduardo De Sul.";
$pageKeywords = "quartiers bordeaux, communes bordeaux métropole, immobilier bordeaux, prix m2 bordeaux, guide quartiers";

// ─── HEADER ───
// Adapter selon votre inclusion de header
$headerPaths = [
    $rootPath . '/public/includes/header.php',
    $rootPath . '/includes/header.php',
    $_SERVER['DOCUMENT_ROOT'] . '/public/includes/header.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php',
];

$headerLoaded = false;
foreach ($headerPaths as $hPath) {
    if (file_exists($hPath)) {
        include $hPath;
        $headerLoaded = true;
        break;
    }
}

if (!$headerLoaded) {
    // Header de secours
    echo '<!DOCTYPE html><html lang="fr"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . '</title>
    <meta name="description" content="' . htmlspecialchars($pageDescription) . '">
    <meta name="keywords" content="' . htmlspecialchars($pageKeywords) . '">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head><body>';
}
?>

<style>
/* ══════════════════════════════════════════════════════════════
   PAGE SECTEURS / QUARTIERS - Frontend Public
   Design immobilier premium - Eduardo De Sul
   ══════════════════════════════════════════════════════════════ */

:root {
    --sec-primary: #1a1a2e;
    --sec-accent: #e67e22;
    --sec-accent-dark: #d35400;
    --sec-text: #2c3e50;
    --sec-text-light: #7f8c8d;
    --sec-bg: #fafaf8;
    --sec-white: #ffffff;
    --sec-border: #ecf0f1;
    --sec-shadow: 0 4px 20px rgba(0,0,0,0.06);
    --sec-radius: 12px;
    --font-display: 'Playfair Display', Georgia, serif;
    --font-body: 'DM Sans', -apple-system, sans-serif;
}

/* ─── HERO SECTION ─── */
.sec-hero {
    position: relative;
    background: linear-gradient(135deg, var(--sec-primary) 0%, #16213e 50%, #0f3460 100%);
    padding: 100px 0 80px;
    overflow: hidden;
    color: white;
}

.sec-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.sec-hero__inner {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    text-align: center;
}

.sec-hero__breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    opacity: 0.7;
}

.sec-hero__breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.2s;
}

.sec-hero__breadcrumb a:hover { opacity: 1; }
.sec-hero__breadcrumb span { opacity: 0.5; }

.sec-hero__title {
    font-family: var(--font-display);
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 16px;
    letter-spacing: -0.5px;
}

.sec-hero__title em {
    font-style: normal;
    color: var(--sec-accent);
}

.sec-hero__subtitle {
    font-family: var(--font-body);
    font-size: 18px;
    font-weight: 400;
    opacity: 0.85;
    max-width: 640px;
    margin: 0 auto 40px;
    line-height: 1.6;
}

/* Stats */
.sec-hero__stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.sec-hero__stat {
    text-align: center;
}

.sec-hero__stat-value {
    font-family: var(--font-display);
    font-size: 36px;
    font-weight: 700;
    color: var(--sec-accent);
    line-height: 1;
    margin-bottom: 4px;
}

.sec-hero__stat-label {
    font-size: 13px;
    font-weight: 500;
    opacity: 0.65;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ─── SEARCH / FILTERS ─── */
.sec-filters {
    background: var(--sec-white);
    border-bottom: 1px solid var(--sec-border);
    padding: 20px 0;
    position: sticky;
    top: 0;
    z-index: 50;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.sec-filters__inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.sec-search {
    position: relative;
    flex: 1;
    max-width: 400px;
    min-width: 200px;
}

.sec-search__icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sec-text-light);
    font-size: 14px;
    pointer-events: none;
}

.sec-search__input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid var(--sec-border);
    border-radius: 10px;
    font-family: var(--font-body);
    font-size: 14px;
    color: var(--sec-text);
    background: var(--sec-bg);
    transition: all 0.3s;
}

.sec-search__input:focus {
    outline: none;
    border-color: var(--sec-accent);
    background: white;
    box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.1);
}

.sec-filter-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.sec-pill {
    padding: 10px 20px;
    border: 2px solid var(--sec-border);
    border-radius: 50px;
    font-family: var(--font-body);
    font-size: 13px;
    font-weight: 600;
    color: var(--sec-text-light);
    background: white;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sec-pill:hover { border-color: var(--sec-accent); color: var(--sec-accent); }
.sec-pill.active { 
    background: var(--sec-accent); 
    color: white; 
    border-color: var(--sec-accent);
}

.sec-count {
    font-size: 14px;
    color: var(--sec-text-light);
    font-weight: 500;
    white-space: nowrap;
}

/* ─── MAIN LISTING ─── */
.sec-listing {
    max-width: 1200px;
    margin: 0 auto;
    padding: 48px 24px 80px;
}

/* Groupe par ville */
.sec-ville-group {
    margin-bottom: 48px;
}

.sec-ville-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--sec-border);
}

.sec-ville-header__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--sec-accent), var(--sec-accent-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.sec-ville-header__name {
    font-family: var(--font-display);
    font-size: 24px;
    font-weight: 700;
    color: var(--sec-primary);
}

.sec-ville-header__count {
    font-size: 13px;
    color: var(--sec-text-light);
    font-weight: 500;
    background: var(--sec-bg);
    padding: 4px 12px;
    border-radius: 20px;
}

/* Grille de cartes */
.sec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

/* ─── CARTE SECTEUR ─── */
.sec-card {
    background: var(--sec-white);
    border-radius: var(--sec-radius);
    overflow: hidden;
    box-shadow: var(--sec-shadow);
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 1px solid var(--sec-border);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}

.sec-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    border-color: var(--sec-accent);
}

.sec-card__img {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #e8e8e8;
}

.sec-card__img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.sec-card:hover .sec-card__img img {
    transform: scale(1.05);
}

.sec-card__img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    color: #94a3b8;
    font-size: 40px;
}

.sec-card__badges {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    display: flex;
    justify-content: space-between;
    z-index: 3;
}

.sec-card__type-badge {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.sec-card__type-badge.quartier {
    background: rgba(230, 126, 34, 0.9);
    color: white;
}

.sec-card__type-badge.commune {
    background: rgba(142, 68, 173, 0.9);
    color: white;
}

.sec-card__prix-badge {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.95);
    color: var(--sec-text);
    backdrop-filter: blur(10px);
}

.sec-card__body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.sec-card__ville {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: var(--sec-accent);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.sec-card__title {
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 700;
    color: var(--sec-primary);
    margin-bottom: 8px;
    line-height: 1.3;
}

.sec-card__desc {
    font-size: 14px;
    color: var(--sec-text-light);
    line-height: 1.5;
    margin-bottom: 16px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Métriques */
.sec-card__metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding-top: 14px;
    border-top: 1px solid var(--sec-border);
}

.sec-card__metric {
    display: flex;
    align-items: center;
    gap: 6px;
}

.sec-card__metric-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.sec-card__metric-icon.prix { background: #fef3c7; color: #d97706; }
.sec-card__metric-icon.rendement { background: #d1fae5; color: #059669; }

.sec-card__metric-data {
    display: flex;
    flex-direction: column;
}

.sec-card__metric-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--sec-text);
    line-height: 1.2;
}

.sec-card__metric-label {
    font-size: 10px;
    color: var(--sec-text-light);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Footer carte */
.sec-card__footer {
    padding: 14px 20px;
    background: var(--sec-bg);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid var(--sec-border);
}

.sec-card__cta {
    font-size: 13px;
    font-weight: 700;
    color: var(--sec-accent);
    display: flex;
    align-items: center;
    gap: 6px;
    transition: gap 0.3s;
}

.sec-card:hover .sec-card__cta { gap: 10px; }

.sec-card__cp {
    font-size: 12px;
    color: var(--sec-text-light);
    font-weight: 500;
}

/* ─── SECTION CTA ─── */
.sec-cta {
    background: linear-gradient(135deg, var(--sec-primary), #0f3460);
    padding: 80px 24px;
    text-align: center;
    color: white;
}

.sec-cta__title {
    font-family: var(--font-display);
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 16px;
}

.sec-cta__text {
    font-size: 16px;
    opacity: 0.8;
    max-width: 500px;
    margin: 0 auto 32px;
    line-height: 1.6;
}

.sec-cta__btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: var(--sec-accent);
    color: white;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
}

.sec-cta__btn:hover {
    background: var(--sec-accent-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(230, 126, 34, 0.4);
}

/* ─── EMPTY STATE ─── */
.sec-empty {
    text-align: center;
    padding: 80px 20px;
    color: var(--sec-text-light);
}

.sec-empty__icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.sec-empty__title {
    font-family: var(--font-display);
    font-size: 24px;
    font-weight: 700;
    color: var(--sec-text);
    margin-bottom: 8px;
}

/* ─── SCHEMA.ORG (hidden) ─── */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .sec-hero { padding: 60px 0 50px; }
    .sec-hero__stats { gap: 24px; }
    .sec-hero__stat-value { font-size: 28px; }
    .sec-filters__inner { flex-direction: column; align-items: stretch; }
    .sec-search { max-width: 100%; }
    .sec-grid { grid-template-columns: 1fr; }
    .sec-card__metrics { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .sec-hero__title { font-size: 28px; }
    .sec-hero__subtitle { font-size: 15px; }
    .sec-filter-pills { justify-content: center; }
}
</style>

<!-- ══════════════════════════════════════════════════════════════
     HERO SECTION
     ══════════════════════════════════════════════════════════════ -->
<section class="sec-hero">
    <div class="sec-hero__inner">
        <nav class="sec-hero__breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span>›</span>
            <span>Quartiers & Communes</span>
        </nav>
        
        <h1 class="sec-hero__title">
            Découvrez les <em>quartiers</em> de Bordeaux
        </h1>
        <p class="sec-hero__subtitle">
            Guide complet des quartiers et communes de Bordeaux Métropole : 
            prix immobiliers, cadre de vie, transports et conseils d'expert.
        </p>
        
        <div class="sec-hero__stats">
            <div class="sec-hero__stat">
                <div class="sec-hero__stat-value"><?= intval($stats['total']) ?></div>
                <div class="sec-hero__stat-label">Secteurs</div>
            </div>
            <div class="sec-hero__stat">
                <div class="sec-hero__stat-value"><?= intval($stats['quartiers']) ?></div>
                <div class="sec-hero__stat-label">Quartiers</div>
            </div>
            <div class="sec-hero__stat">
                <div class="sec-hero__stat-value"><?= intval($stats['communes']) ?></div>
                <div class="sec-hero__stat-label">Communes</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     FILTRES & RECHERCHE
     ══════════════════════════════════════════════════════════════ -->
<div class="sec-filters">
    <div class="sec-filters__inner">
        <div class="sec-search">
            <i class="fas fa-search sec-search__icon"></i>
            <input type="text" 
                   class="sec-search__input" 
                   id="secSearchInput" 
                   placeholder="Rechercher un quartier ou une commune..."
                   autocomplete="off">
        </div>
        
        <div class="sec-filter-pills">
            <button type="button" class="sec-pill active" data-filter="all">
                <i class="fas fa-globe-europe"></i> Tous
            </button>
            <button type="button" class="sec-pill" data-filter="quartier">
                <i class="fas fa-map-pin"></i> Quartiers (<?= intval($stats['quartiers']) ?>)
            </button>
            <button type="button" class="sec-pill" data-filter="commune">
                <i class="fas fa-city"></i> Communes (<?= intval($stats['communes']) ?>)
            </button>
        </div>
        
        <div class="sec-count">
            <span id="secVisibleCount"><?= count($secteurs) ?></span> résultat<?= count($secteurs) > 1 ? 's' : '' ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     LISTING SECTEURS
     ══════════════════════════════════════════════════════════════ -->
<main class="sec-listing">
    
    <?php if (empty($secteurs)): ?>
    <div class="sec-empty">
        <div class="sec-empty__icon"><i class="fas fa-map-marker-alt"></i></div>
        <h2 class="sec-empty__title">Aucun quartier disponible</h2>
        <p>Les quartiers seront bientôt en ligne. Revenez vite !</p>
    </div>
    <?php else: ?>
    
    <?php foreach ($parVille as $ville => $villesSecteurs): ?>
    <div class="sec-ville-group" data-ville="<?= htmlspecialchars(strtolower($ville)) ?>">
        
        <div class="sec-ville-header">
            <div class="sec-ville-header__icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h2 class="sec-ville-header__name"><?= htmlspecialchars($ville) ?></h2>
            <span class="sec-ville-header__count"><?= count($villesSecteurs) ?> secteur<?= count($villesSecteurs) > 1 ? 's' : '' ?></span>
        </div>
        
        <div class="sec-grid">
            <?php foreach ($villesSecteurs as $s): ?>
            <?php
                $slug = htmlspecialchars($s['slug']);
                $nom = htmlspecialchars($s['nom']);
                $type = $s['type_secteur'] ?? 'quartier';
                $heroImg = $s['hero_image'] ?? '';
                $heroSubtitle = $s['hero_subtitle'] ?? '';
                $prixMin = $s['prix_min'] ?? 0;
                $prixMax = $s['prix_max'] ?? 0;
                $rendMin = $s['rendement_min'] ?? 0;
                $rendMax = $s['rendement_max'] ?? 0;
                $cp = $s['code_postal'] ?? '';
                
                // Description courte : hero_subtitle tronqué
                $descCourte = mb_substr(strip_tags($heroSubtitle), 0, 150);
                if (mb_strlen($heroSubtitle) > 150) $descCourte .= '...';
                
                // Prix formaté
                $prixLabel = '';
                if ($prixMin && $prixMax) {
                    $prixLabel = number_format($prixMin, 0, ',', ' ') . ' - ' . number_format($prixMax, 0, ',', ' ') . ' €/m²';
                } elseif ($prixMin) {
                    $prixLabel = 'À partir de ' . number_format($prixMin, 0, ',', ' ') . ' €/m²';
                }
                
                // Rendement formaté
                $rendLabel = '';
                if ($rendMin && $rendMax) {
                    $rendLabel = number_format($rendMin, 1, ',', '') . '% - ' . number_format($rendMax, 1, ',', '') . '%';
                }
            ?>
            <a href="/<?= $slug ?>" 
               class="sec-card" 
               data-type="<?= $type ?>"
               data-nom="<?= strtolower($nom) ?>"
               data-ville="<?= htmlspecialchars(strtolower($s['ville'] ?? '')) ?>">
                
                <!-- Image -->
                <div class="sec-card__img">
                    <?php if ($heroImg): ?>
                    <img src="<?= htmlspecialchars($heroImg) ?>" 
                         alt="<?= $nom ?> - <?= htmlspecialchars($s['ville'] ?? '') ?>"
                         loading="lazy">
                    <?php else: ?>
                    <div class="sec-card__img-placeholder">
                        <i class="fas fa-<?= $type === 'commune' ? 'city' : 'map-pin' ?>"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sec-card__badges">
                        <span class="sec-card__type-badge <?= $type ?>">
                            <?= $type === 'commune' ? 'Commune' : 'Quartier' ?>
                        </span>
                        <?php if ($prixLabel): ?>
                        <span class="sec-card__prix-badge"><?= $prixLabel ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="sec-card__body">
                    <div class="sec-card__ville">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($s['ville'] ?? '') ?>
                    </div>
                    <h3 class="sec-card__title"><?= $nom ?></h3>
                    <?php if ($descCourte): ?>
                    <p class="sec-card__desc"><?= htmlspecialchars($descCourte) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($prixLabel || $rendLabel): ?>
                    <div class="sec-card__metrics">
                        <?php if ($prixLabel): ?>
                        <div class="sec-card__metric">
                            <div class="sec-card__metric-icon prix"><i class="fas fa-euro-sign"></i></div>
                            <div class="sec-card__metric-data">
                                <span class="sec-card__metric-value"><?= $prixLabel ?></span>
                                <span class="sec-card__metric-label">Prix au m²</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($rendLabel): ?>
                        <div class="sec-card__metric">
                            <div class="sec-card__metric-icon rendement"><i class="fas fa-chart-line"></i></div>
                            <div class="sec-card__metric-data">
                                <span class="sec-card__metric-value"><?= $rendLabel ?></span>
                                <span class="sec-card__metric-label">Rendement</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Footer -->
                <div class="sec-card__footer">
                    <span class="sec-card__cta">
                        Découvrir <i class="fas fa-arrow-right"></i>
                    </span>
                    <?php if ($cp): ?>
                    <span class="sec-card__cp"><?= htmlspecialchars($cp) ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>
</main>

<!-- ══════════════════════════════════════════════════════════════
     CTA BOTTOM
     ══════════════════════════════════════════════════════════════ -->
<section class="sec-cta">
    <h2 class="sec-cta__title">Vous ne savez pas quel quartier choisir ?</h2>
    <p class="sec-cta__text">
        Eduardo vous accompagne et vous aide à trouver le secteur 
        idéal pour votre projet immobilier à Bordeaux.
    </p>
    <a href="/contact" class="sec-cta__btn">
        <i class="fas fa-phone-alt"></i> Prendre rendez-vous
    </a>
</section>

<!-- ══════════════════════════════════════════════════════════════
     SCHEMA.ORG - SEO structuré
     ══════════════════════════════════════════════════════════════ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Quartiers et communes de Bordeaux",
    "description": "<?= htmlspecialchars($pageDescription) ?>",
    "numberOfItems": <?= count($secteurs) ?>,
    "itemListElement": [
        <?php foreach ($secteurs as $i => $s): ?>
        {
            "@type": "ListItem",
            "position": <?= $i + 1 ?>,
            "item": {
                "@type": "Place",
                "name": "<?= htmlspecialchars($s['nom']) ?>",
                "url": "https://eduardo-desul-immobilier.fr/<?= htmlspecialchars($s['slug']) ?>"
                <?php if (!empty($s['hero_image'])): ?>,
                "image": "<?= htmlspecialchars($s['hero_image']) ?>"
                <?php endif; ?>
                <?php if (!empty($s['latitude']) && !empty($s['longitude'])): ?>,
                "geo": {
                    "@type": "GeoCoordinates",
                    "latitude": <?= $s['latitude'] ?>,
                    "longitude": <?= $s['longitude'] ?>
                }
                <?php endif; ?>
            }
        }<?= $i < count($secteurs) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ]
}
</script>

<!-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT - Filtres & Recherche
     ══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('secSearchInput');
    const pills = document.querySelectorAll('.sec-pill');
    const cards = document.querySelectorAll('.sec-card');
    const villeGroups = document.querySelectorAll('.sec-ville-group');
    const countEl = document.getElementById('secVisibleCount');
    let activeFilter = 'all';
    
    function filterCards() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visible = 0;
        
        cards.forEach(card => {
            const type = card.dataset.type;
            const nom = card.dataset.nom || '';
            const ville = card.dataset.ville || '';
            
            const matchType = (activeFilter === 'all') || (type === activeFilter);
            const matchSearch = !query || nom.includes(query) || ville.includes(query);
            
            const show = matchType && matchSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        
        // Masquer les groupes de ville vides
        villeGroups.forEach(group => {
            const visibleCards = group.querySelectorAll('.sec-card:not([style*="display: none"])');
            group.style.display = visibleCards.length > 0 ? '' : 'none';
        });
        
        // Mettre à jour le compteur
        if (countEl) countEl.textContent = visible;
    }
    
    // Recherche
    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }
    
    // Filtres pills
    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            pills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.dataset.filter;
            filterCards();
        });
    });
});
</script>

<?php
// ─── FOOTER ───
$footerPaths = [
    $rootPath . '/public/includes/footer.php',
    $rootPath . '/includes/footer.php',
    $_SERVER['DOCUMENT_ROOT'] . '/public/includes/footer.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php',
];

foreach ($footerPaths as $fPath) {
    if (file_exists($fPath)) {
        include $fPath;
        break;
    }
}

if (!$headerLoaded) {
    echo '</body></html>';
}
?>