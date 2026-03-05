<?php
/**
 * ========================================================================
 * Front Page Router - VERSION CORRIGÉE v2
 * ========================================================================
 * /front/page.php
 * 
 * CORRECTIONS v2 (27/02/2026) :
 *   - getHeaderFooter() : status='active' au lieu de is_active=1
 *   - renderHeader()    : custom_html au lieu de html
 *   - renderFooter()    : custom_html au lieu de html
 *   - Fallbacks robustes (status vide, is_default, premier enregistrement)
 * 
 * ORDRE DE RÉSOLUTION :
 *   1. Blog article  → blog/{slug}  → table `articles`
 *   2. Listing secteurs → /secteurs   → table `secteurs` (listing)
 *   3. Single secteur  → /{slug}     → table `secteurs` (si match)
 *   4. Page CMS        → /{slug}     → table `pages`    (fallback)
 *   5. 404
 */

ini_set('display_errors', 0);
error_reporting(0);

$rawSlug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (empty($rawSlug)) $rawSlug = 'accueil';

$isBlogArticle = false;
$articleSlug = '';
$pageSlug = $rawSlug;

if (preg_match('#^blog/([a-z0-9][a-z0-9-]+)$#', $rawSlug, $matches)) {
    $isBlogArticle = true;
    $articleSlug = $matches[1];
    $pageSlug = 'blog';
}

require_once dirname(__DIR__) . '/includes/classes/Database.php';

$siteSettingsPath = dirname(__DIR__) . '/includes/SiteSettings.php';
$hasSiteSettings = false;
if (file_exists($siteSettingsPath)) {
    require_once $siteSettingsPath;
    $hasSiteSettings = true;
}

try {
    $db = Database::getInstance();
    if ($hasSiteSettings) { SiteSettings::init($db); }
    
   if ($isBlogArticle) { renderBlogArticle($db, $articleSlug, $pageSlug); exit; }
if ($pageSlug === 'blog') { renderBlogListing($db); exit; }
if ($pageSlug === 'secteurs') { renderSecteursListing($db); exit; }
    $secteur = findSecteur($db, $pageSlug);
    if ($secteur) { renderSecteurSingle($db, $secteur); exit; }
    
    renderPage($db, $pageSlug);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Page error: " . $e->getMessage());
    echo "Une erreur est survenue.";
}


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  TROUVER UN SECTEUR PAR SLUG                                     ║
// ╚═══════════════════════════════════════════════════════════════════╝

function findSecteur(PDO $db, string $slug): ?array {
    try {
        $stmt = $db->prepare("SELECT * FROM secteurs WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (Exception $e) { return null; }
}


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU LISTING SECTEURS                                           ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderSecteursListing(PDO $db) {
    $secteurs = [];
    $stats = ['total' => 0, 'quartiers' => 0, 'communes' => 0];
    try {
        $stmt = $db->query("SELECT id, nom, slug, ville, type_secteur, hero_image, hero_title, hero_subtitle, meta_title, meta_description, prix_min, prix_max, rendement_min, rendement_max, latitude, longitude, code_postal, presentation, atouts, transport, ambiance FROM secteurs WHERE status = 'published' ORDER BY ville ASC, nom ASC");
        $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmtStats = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN type_secteur = 'quartier' THEN 1 ELSE 0 END) as quartiers, SUM(CASE WHEN type_secteur = 'commune' THEN 1 ELSE 0 END) as communes FROM secteurs WHERE status = 'published'");
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: $stats;
    } catch (PDOException $e) { error_log("Erreur listing secteurs: " . $e->getMessage()); }
    
    $parVille = [];
    foreach ($secteurs as $s) { $parVille[$s['ville'] ?: 'Autre'][] = $s; }
    
    $hf = getHeaderFooter($db, 'secteurs');
    $siteName = _ss('site_name', 'Eduardo De Sul Immobilier');
    $metaTitle = "Quartiers et communes de Bordeaux | $siteName";
    $metaDesc = "Découvrez tous les quartiers et communes de Bordeaux Métropole : prix immobiliers, cadre de vie, conseils. Guide complet par Eduardo De Sul.";
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <link rel="canonical" href="<?= rtrim(_ss('site_url', 'https://eduardo-desul-immobilier.fr'), '/') ?>/secteurs">
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:'DM Sans',-apple-system,sans-serif;line-height:1.6;color:#2c3e50}img{max-width:100%;height:auto}
    .sl-hero{position:relative;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);padding:100px 0 80px;overflow:hidden;color:white;text-align:center}
    .sl-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .sl-hero__inner{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:0 24px}
    .sl-hero__breadcrumb{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px;font-size:14px;opacity:.7}
    .sl-hero__breadcrumb a{color:white;text-decoration:none}.sl-hero__breadcrumb a:hover{opacity:1}.sl-hero__breadcrumb span{opacity:.5}
    .sl-hero__title{font-family:'Playfair Display',Georgia,serif;font-size:clamp(32px,5vw,52px);font-weight:700;line-height:1.15;margin-bottom:16px;letter-spacing:-.5px}
    .sl-hero__title em{font-style:normal;color:#e67e22}
    .sl-hero__subtitle{font-size:18px;opacity:.85;max-width:640px;margin:0 auto 40px;line-height:1.6}
    .sl-hero__stats{display:flex;justify-content:center;gap:40px;flex-wrap:wrap}
    .sl-hero__stat-value{font-family:'Playfair Display',serif;font-size:36px;font-weight:700;color:#e67e22;line-height:1;margin-bottom:4px}
    .sl-hero__stat-label{font-size:13px;font-weight:500;opacity:.65;text-transform:uppercase;letter-spacing:1px}
    .sl-filters{background:white;border-bottom:1px solid #ecf0f1;padding:20px 0;position:sticky;top:0;z-index:50;box-shadow:0 2px 10px rgba(0,0,0,.04)}
    .sl-filters__inner{max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .sl-search{position:relative;flex:1;max-width:400px;min-width:200px}
    .sl-search__icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#7f8c8d;font-size:14px;pointer-events:none}
    .sl-search__input{width:100%;padding:12px 16px 12px 44px;border:2px solid #ecf0f1;border-radius:10px;font-family:inherit;font-size:14px;color:#2c3e50;background:#fafaf8;transition:all .3s}
    .sl-search__input:focus{outline:none;border-color:#e67e22;background:white;box-shadow:0 0 0 4px rgba(230,126,34,.1)}
    .sl-pills{display:flex;gap:8px;flex-wrap:wrap}
    .sl-pill{padding:10px 20px;border:2px solid #ecf0f1;border-radius:50px;font-family:inherit;font-size:13px;font-weight:600;color:#7f8c8d;background:white;cursor:pointer;transition:all .25s;display:flex;align-items:center;gap:6px}
    .sl-pill:hover{border-color:#e67e22;color:#e67e22}.sl-pill.active{background:#e67e22;color:white;border-color:#e67e22}
    .sl-count{font-size:14px;color:#7f8c8d;font-weight:500;white-space:nowrap}
    .sl-listing{max-width:1200px;margin:0 auto;padding:48px 24px 80px}.sl-ville-group{margin-bottom:48px}
    .sl-ville-header{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #ecf0f1}
    .sl-ville-header__icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#e67e22,#d35400);display:flex;align-items:center;justify-content:center;color:white;font-size:16px}
    .sl-ville-header__name{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#1a1a2e}
    .sl-ville-header__count{font-size:13px;color:#7f8c8d;background:#fafaf8;padding:4px 12px;border-radius:20px}
    .sl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px}
    .sl-card{background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #ecf0f1;transition:all .35s cubic-bezier(.25,.46,.45,.94);text-decoration:none;color:inherit;display:flex;flex-direction:column}
    .sl-card:hover{transform:translateY(-6px);box-shadow:0 12px 40px rgba(0,0,0,.12);border-color:#e67e22}
    .sl-card__img{position:relative;height:200px;overflow:hidden;background:#e8e8e8}
    .sl-card__img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .sl-card:hover .sl-card__img img{transform:scale(1.05)}
    .sl-card__img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);color:#94a3b8;font-size:40px}
    .sl-card__badges{position:absolute;top:12px;left:12px;right:12px;display:flex;justify-content:space-between;z-index:3}
    .sl-card__type-badge{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;backdrop-filter:blur(10px)}
    .sl-card__type-badge.quartier{background:rgba(230,126,34,.9);color:white}.sl-card__type-badge.commune{background:rgba(142,68,173,.9);color:white}
    .sl-card__prix-badge{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;background:rgba(255,255,255,.95);color:#2c3e50}
    .sl-card__body{padding:20px;flex:1;display:flex;flex-direction:column}
    .sl-card__ville{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#e67e22;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
    .sl-card__title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#1a1a2e;margin-bottom:8px;line-height:1.3}
    .sl-card__desc{font-size:14px;color:#7f8c8d;line-height:1.5;margin-bottom:16px;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .sl-card__metrics{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-top:14px;border-top:1px solid #ecf0f1}
    .sl-card__metric{display:flex;align-items:center;gap:6px}
    .sl-card__metric-icon{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
    .sl-card__metric-icon.prix{background:#fef3c7;color:#d97706}.sl-card__metric-icon.rendement{background:#d1fae5;color:#059669}
    .sl-card__metric-value{font-size:13px;font-weight:700;color:#2c3e50;line-height:1.2}
    .sl-card__metric-label{font-size:10px;color:#7f8c8d;text-transform:uppercase}
    .sl-card__footer{padding:14px 20px;background:#fafaf8;display:flex;align-items:center;justify-content:space-between;border-top:1px solid #ecf0f1}
    .sl-card__cta{font-size:13px;font-weight:700;color:#e67e22;display:flex;align-items:center;gap:6px;transition:gap .3s}
    .sl-card:hover .sl-card__cta{gap:10px}.sl-card__cp{font-size:12px;color:#7f8c8d}
    .sl-cta{background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:80px 24px;text-align:center;color:white}
    .sl-cta__title{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;margin-bottom:16px}
    .sl-cta__text{font-size:16px;opacity:.8;max-width:500px;margin:0 auto 32px;line-height:1.6}
    .sl-cta__btn{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;background:#e67e22;color:white;border-radius:50px;font-size:16px;font-weight:700;text-decoration:none;transition:all .3s;box-shadow:0 4px 15px rgba(230,126,34,.3)}
    .sl-cta__btn:hover{background:#d35400;transform:translateY(-2px)}
    .sl-empty{text-align:center;padding:80px 20px;color:#7f8c8d}
    .sl-empty__icon{font-size:48px;margin-bottom:16px;opacity:.3}
    .sl-empty__title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#2c3e50;margin-bottom:8px}
    @media(max-width:768px){.sl-hero{padding:60px 0 50px}.sl-filters__inner{flex-direction:column;align-items:stretch}.sl-search{max-width:100%}.sl-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>
<main>
<section class="sl-hero"><div class="sl-hero__inner">
    <nav class="sl-hero__breadcrumb" aria-label="Fil d'Ariane"><a href="/">Accueil</a> <span>›</span> <span>Quartiers & Communes</span></nav>
    <h1 class="sl-hero__title">Découvrez les <em>quartiers</em> de Bordeaux</h1>
    <p class="sl-hero__subtitle">Guide complet des quartiers et communes de Bordeaux Métropole : prix immobiliers, cadre de vie, transports et conseils d'expert.</p>
    <div class="sl-hero__stats">
        <div><div class="sl-hero__stat-value"><?= intval($stats['total']) ?></div><div class="sl-hero__stat-label">Secteurs</div></div>
        <div><div class="sl-hero__stat-value"><?= intval($stats['quartiers']) ?></div><div class="sl-hero__stat-label">Quartiers</div></div>
        <div><div class="sl-hero__stat-value"><?= intval($stats['communes']) ?></div><div class="sl-hero__stat-label">Communes</div></div>
    </div>
</div></section>
<div class="sl-filters"><div class="sl-filters__inner">
    <div class="sl-search"><i class="fas fa-search sl-search__icon"></i><input type="text" class="sl-search__input" id="slSearch" placeholder="Rechercher un quartier ou une commune..." autocomplete="off"></div>
    <div class="sl-pills">
        <button type="button" class="sl-pill active" data-filter="all"><i class="fas fa-globe-europe"></i> Tous</button>
        <button type="button" class="sl-pill" data-filter="quartier"><i class="fas fa-map-pin"></i> Quartiers (<?= intval($stats['quartiers']) ?>)</button>
        <button type="button" class="sl-pill" data-filter="commune"><i class="fas fa-city"></i> Communes (<?= intval($stats['communes']) ?>)</button>
    </div>
    <div class="sl-count"><span id="slCount"><?= count($secteurs) ?></span> résultat<?= count($secteurs) > 1 ? 's' : '' ?></div>
</div></div>
<div class="sl-listing">
<?php if (empty($secteurs)): ?>
    <div class="sl-empty"><div class="sl-empty__icon"><i class="fas fa-map-marker-alt"></i></div><h2 class="sl-empty__title">Aucun quartier disponible</h2><p>Les quartiers seront bientôt en ligne.</p></div>
<?php else: ?>
    <?php foreach ($parVille as $ville => $villesSecteurs): ?>
    <div class="sl-ville-group" data-ville="<?= htmlspecialchars(strtolower($ville)) ?>">
        <div class="sl-ville-header"><div class="sl-ville-header__icon"><i class="fas fa-map-marker-alt"></i></div><h2 class="sl-ville-header__name"><?= htmlspecialchars($ville) ?></h2><span class="sl-ville-header__count"><?= count($villesSecteurs) ?> secteur<?= count($villesSecteurs) > 1 ? 's' : '' ?></span></div>
        <div class="sl-grid">
        <?php foreach ($villesSecteurs as $s):
            $sSlug = htmlspecialchars($s['slug']); $sNom = htmlspecialchars($s['nom']); $sType = $s['type_secteur'] ?? 'quartier';
            $sImg = $s['hero_image'] ?? ''; $sDesc = mb_substr(strip_tags($s['hero_subtitle'] ?? ''), 0, 150);
            if (mb_strlen($s['hero_subtitle'] ?? '') > 150) $sDesc .= '...';
            $sPrixMin = $s['prix_min'] ?? 0; $sPrixMax = $s['prix_max'] ?? 0; $sRendMin = $s['rendement_min'] ?? 0; $sRendMax = $s['rendement_max'] ?? 0; $sCp = $s['code_postal'] ?? '';
            $sPrix = ($sPrixMin && $sPrixMax) ? number_format($sPrixMin,0,',',' ').' - '.number_format($sPrixMax,0,',',' ').' €/m²' : '';
            $sRend = ($sRendMin && $sRendMax) ? number_format($sRendMin,1,',','').'% - '.number_format($sRendMax,1,',','').'%' : '';
        ?>
            <a href="/<?= $sSlug ?>" class="sl-card" data-type="<?= $sType ?>" data-nom="<?= strtolower($sNom) ?>" data-ville="<?= htmlspecialchars(strtolower($s['ville'] ?? '')) ?>">
                <div class="sl-card__img">
                    <?php if ($sImg): ?><img src="<?= htmlspecialchars($sImg) ?>" alt="<?= $sNom ?>" loading="lazy">
                    <?php else: ?><div class="sl-card__img-placeholder"><i class="fas fa-<?= $sType === 'commune' ? 'city' : 'map-pin' ?>"></i></div><?php endif; ?>
                    <div class="sl-card__badges"><span class="sl-card__type-badge <?= $sType ?>"><?= $sType === 'commune' ? 'Commune' : 'Quartier' ?></span><?php if ($sPrix): ?><span class="sl-card__prix-badge"><?= $sPrix ?></span><?php endif; ?></div>
                </div>
                <div class="sl-card__body">
                    <div class="sl-card__ville"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($s['ville'] ?? '') ?></div>
                    <h3 class="sl-card__title"><?= $sNom ?></h3>
                    <?php if ($sDesc): ?><p class="sl-card__desc"><?= htmlspecialchars($sDesc) ?></p><?php endif; ?>
                    <?php if ($sPrix || $sRend): ?><div class="sl-card__metrics">
                        <?php if ($sPrix): ?><div class="sl-card__metric"><div class="sl-card__metric-icon prix"><i class="fas fa-euro-sign"></i></div><div><span class="sl-card__metric-value"><?= $sPrix ?></span><br><span class="sl-card__metric-label">Prix au m²</span></div></div><?php endif; ?>
                        <?php if ($sRend): ?><div class="sl-card__metric"><div class="sl-card__metric-icon rendement"><i class="fas fa-chart-line"></i></div><div><span class="sl-card__metric-value"><?= $sRend ?></span><br><span class="sl-card__metric-label">Rendement</span></div></div><?php endif; ?>
                    </div><?php endif; ?>
                </div>
                <div class="sl-card__footer"><span class="sl-card__cta">Découvrir <i class="fas fa-arrow-right"></i></span><?php if ($sCp): ?><span class="sl-card__cp"><?= htmlspecialchars($sCp) ?></span><?php endif; ?></div>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<section class="sl-cta"><h2 class="sl-cta__title">Vous ne savez pas quel quartier choisir ?</h2><p class="sl-cta__text">Eduardo vous accompagne et vous aide à trouver le secteur idéal pour votre projet immobilier à Bordeaux.</p><a href="/contact" class="sl-cta__btn"><i class="fas fa-phone-alt"></i> Prendre rendez-vous</a></section>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"ItemList","name":"Quartiers et communes de Bordeaux","numberOfItems":<?= count($secteurs) ?>,"itemListElement":[<?php foreach ($secteurs as $i => $ss): ?>{"@type":"ListItem","position":<?= $i+1 ?>,"item":{"@type":"Place","name":"<?= htmlspecialchars($ss['nom']) ?>","url":"<?= rtrim(_ss('site_url','https://eduardo-desul-immobilier.fr'),'/') ?>/<?= htmlspecialchars($ss['slug']) ?>"}}<?= $i < count($secteurs)-1 ? ',' : '' ?><?php endforeach; ?>]}</script>
</main>
<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var search = document.getElementById('slSearch'), pills = document.querySelectorAll('.sl-pill'), cards = document.querySelectorAll('.sl-card'), groups = document.querySelectorAll('.sl-ville-group'), countEl = document.getElementById('slCount'), activeFilter = 'all';
    function filter() {
        var q = (search ? search.value : '').toLowerCase().trim(), visible = 0;
        cards.forEach(function(c) { var t=c.dataset.type, n=c.dataset.nom||'', v=c.dataset.ville||''; var show = (activeFilter==='all'||t===activeFilter) && (!q||n.indexOf(q)!==-1||v.indexOf(q)!==-1); c.style.display=show?'':'none'; if(show)visible++; });
        groups.forEach(function(g) { g.style.display = g.querySelectorAll('.sl-card:not([style*="display: none"])').length > 0 ? '' : 'none'; });
        if (countEl) countEl.textContent = visible;
    }
    if (search) search.addEventListener('input', filter);
    pills.forEach(function(p) { p.addEventListener('click', function() { pills.forEach(function(x){x.classList.remove('active')}); this.classList.add('active'); activeFilter=this.dataset.filter; filter(); }); });
});
</script>
</body></html>
<?php } // end renderSecteursListing


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU SINGLE SECTEUR                                             ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderSecteurSingle(PDO $db, array $s) {
    $jsonDecode = function($val) { if (empty($val)) return []; $d = json_decode($val, true); return is_array($d) ? $d : []; };
    
    $presentation  = $jsonDecode($s['presentation'] ?? '');
    $atoutsData    = $jsonDecode($s['atouts'] ?? '');
    $marcheDesc    = $jsonDecode($s['marche_description'] ?? '');
    $profilsCibles = $jsonDecode($s['profils_cibles'] ?? '');
    $conseils      = $jsonDecode($s['conseils'] ?? '');
    $faq           = $jsonDecode($s['faq'] ?? '');
    $secteursLies  = $jsonDecode($s['secteurs_lies'] ?? '');
    
    $tpl = null;
    if (!empty($s['template_id'])) { try { $st = $db->prepare("SELECT * FROM builder_templates WHERE id = ? LIMIT 1"); $st->execute([$s['template_id']]); $tpl = $st->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) {} }
    $builderContent = $s['content'] ?? '';
    
    $relatedSecteurs = [];
    if (!empty($secteursLies)) { $ph = implode(',', array_fill(0, count($secteursLies), '?')); try { $st = $db->prepare("SELECT id, nom, slug, ville, type_secteur, hero_image, prix_min, prix_max, code_postal FROM secteurs WHERE slug IN ($ph) AND status = 'published'"); $st->execute($secteursLies); $relatedSecteurs = $st->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {} }
    
    $hf = getHeaderFooter($db, 'secteurs');
    if (!empty($s['header_id'])) { try { $h=$db->prepare("SELECT * FROM headers WHERE id=?"); $h->execute([$s['header_id']]); $hdr=$h->fetch(PDO::FETCH_ASSOC); if($hdr) $hf['header']=$hdr; } catch(Exception $e){} }
    if (!empty($s['footer_id'])) { try { $f=$db->prepare("SELECT * FROM footers WHERE id=?"); $f->execute([$s['footer_id']]); $ftr=$f->fetch(PDO::FETCH_ASSOC); if($ftr) $hf['footer']=$ftr; } catch(Exception $e){} }
    
    $siteName = _ss('site_name', 'Eduardo De Sul Immobilier');
    $siteUrl = _ss('site_url', 'https://eduardo-desul-immobilier.fr');
    $metaTitle = htmlspecialchars($s['meta_title'] ?: $s['nom'] . ' - ' . ($s['ville'] ?? 'Bordeaux')) . ' | ' . $siteName;
    $metaDesc = htmlspecialchars($s['meta_description'] ?: 'Découvrez ' . $s['nom'] . ' à ' . ($s['ville'] ?? 'Bordeaux'));
    $ogImage = $s['og_image'] ?? ($s['hero_image'] ?? '');
    $canonical = rtrim($siteUrl, '/') . '/' . $s['slug'];
    $metaRobots = $s['meta_robots'] ?? 'index, follow';
    $cta1Text = $s['hero_cta1_text'] ?? $s['hero_cta_text'] ?? 'Voir les prix du marché';
    $cta1Link = $s['hero_cta1_link'] ?? $s['hero_cta_url'] ?? '#prix-marche';
    $cta2Text = $s['hero_cta2_text'] ?? 'Estimer mon bien';
    $cta2Link = $s['hero_cta2_link'] ?? '/estimation';
    $externalCss = $jsonDecode($s['external_css'] ?? '');
    $externalJs = $jsonDecode($s['external_js'] ?? '');
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>"><meta name="robots" content="<?= htmlspecialchars($metaRobots) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <?php if (!empty($s['meta_keywords'])): ?><meta name="keywords" content="<?= htmlspecialchars($s['meta_keywords']) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($s['hero_title'] ?: $s['nom']) ?>"><meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:type" content="website"><meta property="og:url" content="<?= htmlspecialchars($canonical) ?>"><meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <?php if ($ogImage): ?><meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>"><?php endif; ?>
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach ($externalCss as $cssUrl): ?><link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>"><?php endforeach; ?>
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:'DM Sans',-apple-system,sans-serif;line-height:1.6;color:#2c3e50}img{max-width:100%;height:auto}
    .ss-hero{position:relative;height:480px;display:flex;align-items:flex-end;overflow:hidden;color:white}
    .ss-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;background-color:#1a1a2e}
    .ss-hero__bg::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.2) 50%,rgba(0,0,0,.1) 100%)}
    .ss-hero__inner{position:relative;z-index:2;width:100%;max-width:1200px;margin:0 auto;padding:0 24px 48px}
    .ss-hero__breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:20px;opacity:.8}
    .ss-hero__breadcrumb a{color:white;text-decoration:none}.ss-hero__breadcrumb a:hover{text-decoration:underline}.ss-hero__breadcrumb span{opacity:.5}
    .ss-hero__badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
    .ss-hero__badge.quartier{background:rgba(230,126,34,.9)}.ss-hero__badge.commune{background:rgba(142,68,173,.9)}
    .ss-hero__title{font-family:'Playfair Display',serif;font-size:clamp(28px,4.5vw,46px);font-weight:700;line-height:1.15;margin-bottom:12px;text-shadow:0 2px 8px rgba(0,0,0,.3)}
    .ss-hero__subtitle{font-size:17px;max-width:700px;line-height:1.6;opacity:.9;margin-bottom:24px}
    .ss-hero__ctas{display:flex;gap:12px;flex-wrap:wrap}
    .ss-hero__cta{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:50px;font-size:14px;font-weight:700;text-decoration:none;transition:all .3s}
    .ss-hero__cta--primary{background:#e67e22;color:white;box-shadow:0 4px 15px rgba(230,126,34,.3)}.ss-hero__cta--primary:hover{background:#d35400;transform:translateY(-2px)}
    .ss-hero__cta--secondary{background:rgba(255,255,255,.15);color:white;border:2px solid rgba(255,255,255,.4);backdrop-filter:blur(5px)}.ss-hero__cta--secondary:hover{background:rgba(255,255,255,.25)}
    .ss-container{max-width:1200px;margin:0 auto;padding:0 24px}.ss-section{padding:64px 0}.ss-section--alt{background:#fafaf8}
    .ss-section__header{text-align:center;margin-bottom:40px}
    .ss-section__label{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#e67e22;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
    .ss-section__title{font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#1a1a2e;margin-bottom:10px}
    .ss-section__subtitle{font-size:16px;color:#7f8c8d;max-width:600px;margin:0 auto}
    .ss-stats{background:white;border-bottom:1px solid #ecf0f1;padding:24px 0}
    .ss-stats__inner{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px}
    .ss-stat{display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fafaf8;border-radius:10px}
    .ss-stat__icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
    .ss-stat__value{font-size:16px;font-weight:700;color:#1a1a2e;line-height:1.2}.ss-stat__label{font-size:11px;color:#7f8c8d;text-transform:uppercase}
    .ss-presentation{column-count:2;column-gap:40px}.ss-presentation p{margin-bottom:16px;line-height:1.7;font-size:15px;break-inside:avoid}
    .ss-atouts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
    .ss-atout{display:flex;gap:14px;padding:20px;background:white;border-radius:12px;border:1px solid #ecf0f1;transition:all .3s}
    .ss-atout:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08);border-color:#e67e22}
    .ss-atout__icon{font-size:28px;flex-shrink:0}.ss-atout__title{font-size:15px;font-weight:700;color:#1a1a2e;margin-bottom:4px}.ss-atout__desc{font-size:13px;color:#7f8c8d;line-height:1.5}
    .ss-marche{max-width:800px;margin:0 auto}.ss-marche p{margin-bottom:16px;line-height:1.7;font-size:15px}
    .ss-profils-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px}
    .ss-profil{padding:24px;background:white;border-radius:12px;border:1px solid #ecf0f1;text-align:center}
    .ss-profil__icon{font-size:36px;margin-bottom:12px}.ss-profil__title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#1a1a2e;margin-bottom:8px}.ss-profil__desc{font-size:14px;color:#7f8c8d;line-height:1.6}
    .ss-content{max-width:800px;margin:0 auto}.ss-content h2{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:#1a1a2e;margin:32px 0 16px}.ss-content h3{font-size:20px;font-weight:700;margin:24px 0 12px}.ss-content p{margin-bottom:16px;line-height:1.7;font-size:15px}.ss-content img{border-radius:10px;margin:16px 0}
    .ss-conseils-list{max-width:700px;margin:0 auto;display:flex;flex-direction:column;gap:12px}
    .ss-conseil{display:flex;align-items:flex-start;gap:12px;padding:16px;background:white;border-radius:10px;border:1px solid #ecf0f1}.ss-conseil__icon{font-size:20px;flex-shrink:0}.ss-conseil__text{font-size:14px;line-height:1.6}
    .ss-faq-list{max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:12px}
    .ss-faq{background:white;border-radius:12px;border:1px solid #ecf0f1;overflow:hidden}
    .ss-faq__q{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;cursor:pointer;font-size:15px;font-weight:600;color:#1a1a2e;background:none;border:none;width:100%;text-align:left;font-family:inherit;transition:background .2s}
    .ss-faq__q:hover{background:#fafaf8}.ss-faq__q i{transition:transform .3s;color:#e67e22}.ss-faq.open .ss-faq__q i{transform:rotate(180deg)}
    .ss-faq__a{padding:0 20px;max-height:0;overflow:hidden;transition:all .3s}.ss-faq.open .ss-faq__a{max-height:300px;padding:0 20px 18px}.ss-faq__a p{font-size:14px;color:#7f8c8d;line-height:1.7}
    .ss-related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
    .ss-related-card{display:flex;align-items:center;gap:14px;padding:16px;background:white;border-radius:12px;border:1px solid #ecf0f1;text-decoration:none;color:inherit;transition:all .3s}
    .ss-related-card:hover{border-color:#e67e22;transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
    .ss-related-card__img{width:64px;height:48px;border-radius:8px;background-size:cover;background-position:center;background-color:#e8e8e8;flex-shrink:0}
    .ss-related-card__name{font-weight:700;font-size:14px;color:#1a1a2e}.ss-related-card__info{font-size:12px;color:#7f8c8d}
    .ss-bottom-cta{background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:64px 24px;text-align:center;color:white}
    .ss-bottom-cta__title{font-family:'Playfair Display',serif;font-size:30px;font-weight:700;margin-bottom:12px}
    .ss-bottom-cta__text{font-size:16px;opacity:.8;margin-bottom:28px;max-width:500px;margin-left:auto;margin-right:auto;line-height:1.6}
    .ss-bottom-cta__btn{display:inline-flex;align-items:center;gap:8px;padding:16px 36px;background:#e67e22;color:white;border-radius:50px;font-size:16px;font-weight:700;text-decoration:none;transition:all .3s}
    .ss-bottom-cta__btn:hover{background:#d35400;transform:translateY(-2px)}
    @media(max-width:768px){.ss-hero{height:400px}.ss-hero__ctas{flex-direction:column}.ss-hero__cta{justify-content:center}.ss-presentation{column-count:1}.ss-stats__inner{grid-template-columns:repeat(2,1fr)}.ss-atouts-grid,.ss-profils-grid{grid-template-columns:1fr}}
    </style>
    <?php if (!empty($s['custom_css'])): ?><style><?= $s['custom_css'] ?></style><?php endif; ?>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>
<main><div class="ss-page">

<section class="ss-hero">
    <div class="ss-hero__bg" style="<?= !empty($s['hero_image']) ? "background-image:url('".htmlspecialchars($s['hero_image'])."')" : '' ?>"></div>
    <div class="ss-hero__inner">
        <nav class="ss-hero__breadcrumb" aria-label="Fil d'Ariane"><a href="/">Accueil</a> <span>›</span> <a href="/secteurs">Quartiers</a> <span>›</span> <span><?= htmlspecialchars($s['nom']) ?></span></nav>
        <span class="ss-hero__badge <?= $s['type_secteur'] ?? 'quartier' ?>"><i class="fas fa-<?= ($s['type_secteur'] ?? 'quartier') === 'commune' ? 'city' : 'map-pin' ?>"></i> <?= ucfirst($s['type_secteur'] ?? 'quartier') ?> · <?= htmlspecialchars($s['ville'] ?? 'Bordeaux') ?></span>
        <h1 class="ss-hero__title"><?= htmlspecialchars($s['hero_title'] ?: $s['nom']) ?></h1>
        <?php if (!empty($s['hero_subtitle'])): ?><p class="ss-hero__subtitle"><?= htmlspecialchars($s['hero_subtitle']) ?></p><?php endif; ?>
        <div class="ss-hero__ctas">
            <a href="<?= htmlspecialchars($cta1Link) ?>" class="ss-hero__cta ss-hero__cta--primary"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($cta1Text) ?></a>
            <a href="<?= htmlspecialchars($cta2Link) ?>" class="ss-hero__cta ss-hero__cta--secondary"><i class="fas fa-calculator"></i> <?= htmlspecialchars($cta2Text) ?></a>
        </div>
    </div>
</section>

<div class="ss-stats"><div class="ss-container"><div class="ss-stats__inner">
    <?php if (!empty($s['prix_min']) && !empty($s['prix_max'])): ?><div class="ss-stat"><div class="ss-stat__icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-euro-sign"></i></div><div><div class="ss-stat__value"><?= number_format($s['prix_min'],0,',',' ') ?> - <?= number_format($s['prix_max'],0,',',' ') ?> €/m²</div><div class="ss-stat__label">Prix immobilier</div></div></div><?php endif; ?>
    <?php if (!empty($s['rendement_min']) && !empty($s['rendement_max'])): ?><div class="ss-stat"><div class="ss-stat__icon" style="background:#d1fae5;color:#059669"><i class="fas fa-chart-line"></i></div><div><div class="ss-stat__value"><?= number_format($s['rendement_min'],1,',','') ?>% - <?= number_format($s['rendement_max'],1,',','') ?>%</div><div class="ss-stat__label">Rendement locatif</div></div></div><?php endif; ?>
    <?php if (!empty($s['evolution_prix'])): ?><div class="ss-stat"><div class="ss-stat__icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-arrow-trend-up"></i></div><div><div class="ss-stat__value"><?= htmlspecialchars($s['evolution_prix']) ?></div><div class="ss-stat__label">Évolution</div></div></div><?php endif; ?>
    <?php if (!empty($s['delai_vente'])): ?><div class="ss-stat"><div class="ss-stat__icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-clock"></i></div><div><div class="ss-stat__value"><?= htmlspecialchars($s['delai_vente']) ?></div><div class="ss-stat__label">Délai de vente</div></div></div><?php endif; ?>
</div></div></div>

<?php if (!empty($presentation)): ?><section class="ss-section"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-info-circle"></i> Présentation</span><h2 class="ss-section__title">Découvrez <?= htmlspecialchars($s['nom']) ?></h2></div><div class="ss-presentation"><?php foreach ($presentation as $p): ?><p><?= htmlspecialchars($p) ?></p><?php endforeach; ?></div></div></section><?php endif; ?>

<?php if (!empty($atoutsData)): ?><section class="ss-section ss-section--alt"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-star"></i> Atouts</span><h2 class="ss-section__title">Les points forts de <?= htmlspecialchars($s['nom']) ?></h2></div><div class="ss-atouts-grid"><?php foreach ($atoutsData as $a): ?><div class="ss-atout"><span class="ss-atout__icon"><?= $a['icon'] ?? '✨' ?></span><div><h3 class="ss-atout__title"><?= htmlspecialchars($a['title'] ?? '') ?></h3><p class="ss-atout__desc"><?= htmlspecialchars($a['description'] ?? '') ?></p></div></div><?php endforeach; ?></div></div></section><?php endif; ?>

<?php if (!empty($marcheDesc)): ?><section class="ss-section" id="prix-marche"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-chart-bar"></i> Marché</span><h2 class="ss-section__title">Le marché immobilier à <?= htmlspecialchars($s['nom']) ?></h2></div><div class="ss-marche"><?php foreach ($marcheDesc as $p): ?><p><?= htmlspecialchars($p) ?></p><?php endforeach; ?></div></div></section><?php endif; ?>

<?php if (!empty($profilsCibles)): ?><section class="ss-section ss-section--alt"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-users"></i> Pour qui ?</span><h2 class="ss-section__title">Ce quartier est fait pour vous si...</h2></div><div class="ss-profils-grid"><?php foreach ($profilsCibles as $p): ?><div class="ss-profil"><div class="ss-profil__icon"><?= $p['icon'] ?? '🎯' ?></div><h3 class="ss-profil__title"><?= htmlspecialchars($p['title'] ?? '') ?></h3><p class="ss-profil__desc"><?= htmlspecialchars($p['description'] ?? '') ?></p></div><?php endforeach; ?></div></div></section><?php endif; ?>

<?php if (!empty($builderContent)): ?><section class="ss-section"><div class="ss-container"><div class="ss-content"><?= $builderContent ?></div></div></section><?php endif; ?>

<?php if (!empty($conseils)): ?><section class="ss-section ss-section--alt"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-lightbulb"></i> Conseils</span><h2 class="ss-section__title">Nos conseils pour acheter à <?= htmlspecialchars($s['nom']) ?></h2></div><div class="ss-conseils-list"><?php foreach ($conseils as $c): ?><div class="ss-conseil"><span class="ss-conseil__icon">💡</span><div class="ss-conseil__text"><?= htmlspecialchars(is_array($c) ? ($c['text'] ?? $c['content'] ?? '') : $c) ?></div></div><?php endforeach; ?></div></div></section><?php endif; ?>

<?php if (!empty($faq)): ?>
<section class="ss-section" id="faq"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-question-circle"></i> FAQ</span><h2 class="ss-section__title">Questions fréquentes sur <?= htmlspecialchars($s['nom']) ?></h2></div><div class="ss-faq-list"><?php foreach ($faq as $f): ?><div class="ss-faq"><button type="button" class="ss-faq__q"><?= htmlspecialchars($f['question'] ?? $f['q'] ?? '') ?> <i class="fas fa-chevron-down"></i></button><div class="ss-faq__a"><p><?= htmlspecialchars($f['answer'] ?? $f['reponse'] ?? $f['a'] ?? '') ?></p></div></div><?php endforeach; ?></div></div></section>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[<?php foreach ($faq as $i => $f): ?>{"@type":"Question","name":<?= json_encode($f['question'] ?? $f['q'] ?? '', JSON_UNESCAPED_UNICODE) ?>,"acceptedAnswer":{"@type":"Answer","text":<?= json_encode($f['answer'] ?? $f['reponse'] ?? $f['a'] ?? '', JSON_UNESCAPED_UNICODE) ?>}}<?= $i < count($faq)-1 ? ',' : '' ?><?php endforeach; ?>]}</script>
<?php endif; ?>

<?php if (!empty($relatedSecteurs)): ?><section class="ss-section ss-section--alt"><div class="ss-container"><div class="ss-section__header"><span class="ss-section__label"><i class="fas fa-compass"></i> À découvrir aussi</span><h2 class="ss-section__title">Quartiers proches</h2></div><div class="ss-related-grid"><?php foreach ($relatedSecteurs as $rs): ?><a href="/<?= htmlspecialchars($rs['slug']) ?>" class="ss-related-card"><div class="ss-related-card__img" style="<?= !empty($rs['hero_image']) ? "background-image:url('".htmlspecialchars($rs['hero_image'])."')" : '' ?>"></div><div><div class="ss-related-card__name"><?= htmlspecialchars($rs['nom']) ?></div><div class="ss-related-card__info"><?= htmlspecialchars($rs['ville'] ?? '') ?><?php if (!empty($rs['prix_min']) && !empty($rs['prix_max'])): ?> · <?= number_format($rs['prix_min'],0,',',' ') ?> - <?= number_format($rs['prix_max'],0,',',' ') ?> €/m²<?php endif; ?></div></div></a><?php endforeach; ?></div></div></section><?php endif; ?>

<section class="ss-bottom-cta"><h2 class="ss-bottom-cta__title">Intéressé par <?= htmlspecialchars($s['nom']) ?> ?</h2><p class="ss-bottom-cta__text">Eduardo vous accompagne dans votre projet immobilier à <?= htmlspecialchars($s['ville'] ?? 'Bordeaux') ?>. Estimation gratuite, conseils personnalisés.</p><a href="/contact" class="ss-bottom-cta__btn"><i class="fas fa-phone-alt"></i> Prendre rendez-vous</a></section>

<script type="application/ld+json">{"@context":"https://schema.org","@type":"Place","name":<?= json_encode($s['nom'], JSON_UNESCAPED_UNICODE) ?>,"description":<?= json_encode($s['meta_description'] ?: 'Quartier de ' . ($s['ville'] ?? 'Bordeaux'), JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($canonical) ?>,<?php if (!empty($s['latitude']) && !empty($s['longitude'])): ?>"geo":{"@type":"GeoCoordinates","latitude":<?= floatval($s['latitude']) ?>,"longitude":<?= floatval($s['longitude']) ?>},<?php endif; ?>"address":{"@type":"PostalAddress","addressLocality":<?= json_encode($s['ville'] ?? 'Bordeaux', JSON_UNESCAPED_UNICODE) ?>,<?php if (!empty($s['code_postal'])): ?>"postalCode":<?= json_encode($s['code_postal']) ?>,<?php endif; ?>"addressCountry":"FR"}<?php if ($ogImage): ?>,"image":<?= json_encode($ogImage) ?><?php endif; ?>}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Accueil","item":"<?= rtrim($siteUrl,'/') ?>/"},{"@type":"ListItem","position":2,"name":"Quartiers","item":"<?= rtrim($siteUrl,'/') ?>/secteurs"},{"@type":"ListItem","position":3,"name":<?= json_encode($s['nom'], JSON_UNESCAPED_UNICODE) ?>,"item":<?= json_encode($canonical) ?>}]}</script>

</div></main>

<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ss-faq__q').forEach(function(btn) { btn.addEventListener('click', function() { var p=this.closest('.ss-faq'), w=p.classList.contains('open'); document.querySelectorAll('.ss-faq').forEach(function(f){f.classList.remove('open')}); if(!w)p.classList.add('open'); }); });
    document.querySelectorAll('a[href^="#"]').forEach(function(a) { a.addEventListener('click', function(e) { var t=document.querySelector(this.getAttribute('href')); if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})} }); });
});
</script>
<?php foreach ($externalJs as $jsUrl): ?><script src="<?= htmlspecialchars($jsUrl) ?>"></script><?php endforeach; ?>
<?php if (!empty($s['custom_js'])): ?><script><?= $s['custom_js'] ?></script><?php endif; ?>
</body></html>

<?php } // end renderSecteurSingle


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU ARTICLE BLOG                                               ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderBlogArticle(PDO $db, string $articleSlug, string $pageSlug) {
    $article = null;
    try {
        $stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? AND (status = 'published' OR statut = 'publie' OR statut = 'publié') LIMIT 1");
        $stmt->execute([$articleSlug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Blog article error: " . $e->getMessage()); }
    
    if (!$article) { http_response_code(404); renderPage($db, '404'); return; }
    
    try { $db->prepare("UPDATE articles SET views = COALESCE(views, 0) + 1 WHERE id = ?")->execute([$article['id']]); } catch (Exception $e) {}
    
    $hf = getHeaderFooter($db, 'blog');
    
    $titre = $article['titre'] ?? $article['title'] ?? 'Article';
    $contenu = $article['contenu'] ?? $article['content'] ?? '';
    $image = $article['featured_image'] ?? $article['image'] ?? '';
    $categorie = $article['raison_vente'] ?? $article['categorie'] ?? $article['category'] ?? '';
    $datePubli = $article['date_publication'] ?? $article['published_at'] ?? $article['created_at'] ?? date('Y-m-d');
    $auteur = $article['auteur'] ?? $article['author'] ?? 'Eduardo De Sul';
    $readingTime = $article['reading_time'] ?? $article['temps_lecture'] ?? ceil(str_word_count(strip_tags($contenu)) / 200);
    $metaTitle = $article['meta_title'] ?? $titre;
    $metaDesc = $article['meta_description'] ?? mb_substr(strip_tags($contenu), 0, 155);
    $tags = [];
    if (!empty($article['tags'])) { $decoded = json_decode($article['tags'], true); $tags = is_array($decoded) ? $decoded : explode(',', $article['tags']); }
    
    $siteName = _ss('site_name', 'Eduardo De Sul Immobilier');
    $siteUrl = rtrim(_ss('site_url', 'https://eduardo-desul-immobilier.fr'), '/');
    $canonical = $siteUrl . '/blog/' . $articleSlug;
    
    $related = [];
    try {
        $relSql = "SELECT id, titre, title, slug, featured_image, image, raison_vente, categorie, date_publication, published_at, created_at FROM articles WHERE slug != ? AND (status = 'published' OR statut = 'publie' OR statut = 'publié')";
        $relParams = [$articleSlug];
        if (!empty($categorie)) { $relSql .= " AND (raison_vente = ? OR categorie = ?)"; $relParams[] = $categorie; $relParams[] = $categorie; }
        $relSql .= " ORDER BY COALESCE(date_publication, published_at, created_at) DESC LIMIT 3";
        $stmtRel = $db->prepare($relSql); $stmtRel->execute($relParams); $related = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    $tpl = null;
    try { $st = $db->prepare("SELECT * FROM builder_templates WHERE slug = 'article-blog-classique' OR slug = 'blog-article' OR name LIKE '%blog%article%' LIMIT 1"); $st->execute(); $tpl = $st->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) {}
    
    if ($tpl && !empty($tpl['html'])) { renderBlogWithTemplate($db, $article, $tpl, $hf, $related, $canonical, $siteName, $siteUrl); return; }
    
    $dateFr = formatDateFr($datePubli);
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= htmlspecialchars($siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>"><meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type" content="article"><meta property="og:url" content="<?= htmlspecialchars($canonical) ?>"><meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <?php if ($image): ?><meta property="og:image" content="<?= htmlspecialchars($image) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:'DM Sans',-apple-system,sans-serif;line-height:1.6;color:#2c3e50;background:#fafaf8}img{max-width:100%;height:auto}
    .ba-hero{position:relative;height:420px;display:flex;align-items:flex-end;overflow:hidden;color:white}
    .ba-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;background-color:#1a1a2e}
    .ba-hero__bg::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.8) 0%,rgba(0,0,0,.2) 60%,rgba(0,0,0,.1) 100%)}
    .ba-hero__inner{position:relative;z-index:2;max-width:800px;margin:0 auto;padding:0 24px 48px;width:100%}
    .ba-hero__breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:16px;opacity:.8}
    .ba-hero__breadcrumb a{color:white;text-decoration:none}.ba-hero__breadcrumb a:hover{text-decoration:underline}
    .ba-hero__meta{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:12px;font-size:13px}
    .ba-hero__cat{padding:4px 12px;background:#e67e22;border-radius:4px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px}
    .ba-hero__date,.ba-hero__read{opacity:.8;display:flex;align-items:center;gap:5px}
    .ba-hero__title{font-family:'Playfair Display',serif;font-size:clamp(26px,4vw,40px);font-weight:700;line-height:1.2}
    .ba-content{max-width:800px;margin:-40px auto 0;padding:0 24px;position:relative;z-index:3}
    .ba-article{background:white;border-radius:16px;padding:48px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
    .ba-article h2{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#1a1a2e;margin:32px 0 16px}
    .ba-article h3{font-size:20px;font-weight:700;color:#2c3e50;margin:24px 0 12px}
    .ba-article p{margin-bottom:16px;line-height:1.8;font-size:16px}
    .ba-article ul,.ba-article ol{margin:0 0 16px 24px}.ba-article li{margin-bottom:8px;line-height:1.7}
    .ba-article img{border-radius:10px;margin:20px 0}
    .ba-article blockquote{border-left:4px solid #e67e22;padding:16px 20px;margin:20px 0;background:#fef9f3;border-radius:0 8px 8px 0;font-style:italic;color:#555}
    .ba-article a{color:#e67e22;text-decoration:none;border-bottom:1px solid rgba(230,126,34,.3)}.ba-article a:hover{border-bottom-color:#e67e22}
    .ba-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:32px;padding-top:24px;border-top:1px solid #ecf0f1}
    .ba-tag{padding:6px 14px;background:#f0f0f0;border-radius:20px;font-size:12px;font-weight:600;color:#7f8c8d;text-decoration:none;transition:all .2s}.ba-tag:hover{background:#e67e22;color:white}
    .ba-share{display:flex;align-items:center;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #ecf0f1}
    .ba-share__label{font-size:13px;font-weight:700;color:#7f8c8d}
    .ba-share__btn{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;text-decoration:none;color:white;transition:transform .2s}.ba-share__btn:hover{transform:scale(1.1)}
    .ba-share__btn--fb{background:#1877f2}.ba-share__btn--tw{background:#1da1f2}.ba-share__btn--li{background:#0a66c2}.ba-share__btn--mail{background:#ea4335}
    .ba-share__btn--copy{background:#7f8c8d;cursor:pointer;border:none}
    .ba-author{display:flex;align-items:center;gap:16px;margin-top:32px;padding:24px;background:#fafaf8;border-radius:12px}
    .ba-author__avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#e67e22,#d35400);display:flex;align-items:center;justify-content:center;color:white;font-size:20px;font-weight:700;flex-shrink:0}
    .ba-author__name{font-weight:700;font-size:15px;color:#1a1a2e}.ba-author__role{font-size:13px;color:#7f8c8d}
    .ba-related{max-width:800px;margin:48px auto 60px;padding:0 24px}
    .ba-related__title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#1a1a2e;margin-bottom:20px}
    .ba-related__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
    .ba-related__card{background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.06);text-decoration:none;color:inherit;transition:all .3s;border:1px solid #ecf0f1}
    .ba-related__card:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.1)}
    .ba-related__card-img{height:140px;background-size:cover;background-position:center;background-color:#e8e8e8}
    .ba-related__card-body{padding:16px}
    .ba-related__card-title{font-weight:700;font-size:14px;color:#1a1a2e;margin-bottom:4px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .ba-related__card-date{font-size:12px;color:#7f8c8d}
    @media(max-width:768px){.ba-hero{height:340px}.ba-article{padding:28px 20px;border-radius:12px}.ba-related__grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>
<main>
<section class="ba-hero">
    <div class="ba-hero__bg" style="<?= $image ? "background-image:url('".htmlspecialchars($image)."')" : '' ?>"></div>
    <div class="ba-hero__inner">
        <nav class="ba-hero__breadcrumb" aria-label="Fil d'Ariane"><a href="/">Accueil</a> <span style="opacity:.5">›</span> <a href="/blog">Blog</a> <span style="opacity:.5">›</span> <span><?= htmlspecialchars(mb_substr($titre, 0, 50)) ?></span></nav>
        <div class="ba-hero__meta">
            <?php if ($categorie): ?><span class="ba-hero__cat"><?= htmlspecialchars($categorie) ?></span><?php endif; ?>
            <span class="ba-hero__date"><i class="far fa-calendar-alt"></i> <?= $dateFr ?></span>
            <span class="ba-hero__read"><i class="far fa-clock"></i> <?= intval($readingTime) ?> min de lecture</span>
        </div>
        <h1 class="ba-hero__title"><?= htmlspecialchars($titre) ?></h1>
    </div>
</section>
<div class="ba-content">
    <article class="ba-article">
        <?= $contenu ?>
        <?php if (!empty($tags)): ?><div class="ba-tags"><?php foreach ($tags as $tag): ?><span class="ba-tag"><?= htmlspecialchars(trim(is_array($tag) ? ($tag['name'] ?? '') : $tag)) ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="ba-share">
            <span class="ba-share__label">Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonical) ?>" target="_blank" rel="noopener" class="ba-share__btn ba-share__btn--fb"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($canonical) ?>&text=<?= urlencode($titre) ?>" target="_blank" rel="noopener" class="ba-share__btn ba-share__btn--tw"><i class="fab fa-x-twitter"></i></a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($canonical) ?>" target="_blank" rel="noopener" class="ba-share__btn ba-share__btn--li"><i class="fab fa-linkedin-in"></i></a>
            <a href="mailto:?subject=<?= rawurlencode($titre) ?>&body=<?= rawurlencode($canonical) ?>" class="ba-share__btn ba-share__btn--mail"><i class="fas fa-envelope"></i></a>
            <button type="button" class="ba-share__btn ba-share__btn--copy" onclick="navigator.clipboard.writeText('<?= $canonical ?>');this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-link\'></i>',2000)" title="Copier le lien"><i class="fas fa-link"></i></button>
        </div>
        <div class="ba-author">
            <div class="ba-author__avatar"><?= mb_strtoupper(mb_substr($auteur, 0, 1)) ?></div>
            <div><div class="ba-author__name"><?= htmlspecialchars($auteur) ?></div><div class="ba-author__role">Conseiller immobilier · eXp France Bordeaux</div></div>
        </div>
    </article>
</div>
<?php if (!empty($related)): ?>
<div class="ba-related"><h2 class="ba-related__title">Articles similaires</h2><div class="ba-related__grid">
<?php foreach ($related as $r):
    $rTitre = $r['titre'] ?? $r['title'] ?? ''; $rSlug = $r['slug'] ?? ''; $rImg = $r['featured_image'] ?? $r['image'] ?? '';
    $rDate = formatDateFr($r['date_publication'] ?? $r['published_at'] ?? $r['created_at'] ?? '');
?>
<a href="/blog/<?= htmlspecialchars($rSlug) ?>" class="ba-related__card">
    <div class="ba-related__card-img" style="<?= $rImg ? "background-image:url('".htmlspecialchars($rImg)."')" : '' ?>"></div>
    <div class="ba-related__card-body"><h3 class="ba-related__card-title"><?= htmlspecialchars($rTitre) ?></h3><span class="ba-related__card-date"><?= $rDate ?></span></div>
</a>
<?php endforeach; ?>
</div></div>
<?php endif; ?>

<script type="application/ld+json">{"@context":"https://schema.org","@type":"BlogPosting","headline":<?= json_encode($titre, JSON_UNESCAPED_UNICODE) ?>,"description":<?= json_encode($metaDesc, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($canonical) ?>,"datePublished":"<?= date('c', strtotime($datePubli)) ?>","dateModified":"<?= date('c', strtotime($article['updated_at'] ?? $datePubli)) ?>","author":{"@type":"Person","name":<?= json_encode($auteur, JSON_UNESCAPED_UNICODE) ?>},"publisher":{"@type":"Organization","name":<?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>}<?php if ($image): ?>,"image":<?= json_encode($image) ?><?php endif; ?>}</script>
</main>
<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>
</body></html>
<?php } // end renderBlogArticle


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU BLOG AVEC TEMPLATE BUILDER                                 ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderBlogWithTemplate(PDO $db, array $article, array $tpl, array $hf, array $related, string $canonical, string $siteName, string $siteUrl) {
    $titre = $article['titre'] ?? $article['title'] ?? '';
    $contenu = $article['contenu'] ?? $article['content'] ?? '';
    $image = $article['featured_image'] ?? $article['image'] ?? '';
    $categorie = $article['raison_vente'] ?? $article['categorie'] ?? '';
    $datePubli = $article['date_publication'] ?? $article['published_at'] ?? $article['created_at'] ?? '';
    $auteur = $article['auteur'] ?? $article['author'] ?? 'Eduardo De Sul';
    $readingTime = $article['reading_time'] ?? $article['temps_lecture'] ?? ceil(str_word_count(strip_tags($contenu)) / 200);
    $metaTitle = $article['meta_title'] ?? $titre;
    $metaDesc = $article['meta_description'] ?? mb_substr(strip_tags($contenu), 0, 155);
    $excerpt = $article['excerpt'] ?? $article['extrait'] ?? mb_substr(strip_tags($contenu), 0, 200);
    
    $toc = '';
    if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $contenu, $headings)) {
        $toc = '<nav class="toc"><h3>Sommaire</h3><ul>';
        foreach ($headings[1] as $i => $h) { $anchor = 'section-' . ($i+1); $contenu = preg_replace('/<h2/', '<h2 id="'.$anchor.'"', $contenu, 1); $toc .= '<li><a href="#'.$anchor.'">'.strip_tags($h).'</a></li>'; }
        $toc .= '</ul></nav>';
    }
    
    $relatedHtml = '';
    foreach ($related as $r) {
        $rT = htmlspecialchars($r['titre'] ?? $r['title'] ?? ''); $rS = htmlspecialchars($r['slug'] ?? ''); $rI = $r['featured_image'] ?? $r['image'] ?? '';
        $rD = formatDateFr($r['date_publication'] ?? $r['published_at'] ?? $r['created_at'] ?? '');
        $relatedHtml .= '<a href="/blog/'.$rS.'" class="related-card">'; if ($rI) $relatedHtml .= '<img src="'.htmlspecialchars($rI).'" alt="'.$rT.'" loading="lazy">';
        $relatedHtml .= '<h4>'.$rT.'</h4><span>'.$rD.'</span></a>';
    }
    
    $tagsHtml = '';
    $tagsRaw = $article['tags'] ?? '';
    if ($tagsRaw) { $tagsArr = json_decode($tagsRaw, true) ?: explode(',', $tagsRaw); foreach ($tagsArr as $t) { $t = trim(is_array($t) ? ($t['name'] ?? '') : $t); if ($t) $tagsHtml .= '<span class="tag">'.htmlspecialchars($t).'</span> '; } }
    
    $vars = [
        '{{titre}}' => htmlspecialchars($titre), '{{title}}' => htmlspecialchars($titre),
        '{{contenu}}' => $contenu, '{{content}}' => $contenu,
        '{{image}}' => htmlspecialchars($image), '{{featured_image}}' => htmlspecialchars($image),
        '{{categorie}}' => htmlspecialchars($categorie), '{{category}}' => htmlspecialchars($categorie),
        '{{date}}' => formatDateFr($datePubli), '{{date_publication}}' => formatDateFr($datePubli),
        '{{auteur}}' => htmlspecialchars($auteur), '{{author}}' => htmlspecialchars($auteur),
        '{{reading_time}}' => intval($readingTime), '{{temps_lecture}}' => intval($readingTime),
        '{{slug}}' => htmlspecialchars($article['slug']), '{{canonical}}' => htmlspecialchars($canonical), '{{url}}' => htmlspecialchars($canonical),
        '{{meta_title}}' => htmlspecialchars($metaTitle), '{{meta_description}}' => htmlspecialchars($metaDesc),
        '{{excerpt}}' => htmlspecialchars($excerpt), '{{extrait}}' => htmlspecialchars($excerpt),
        '{{toc}}' => $toc, '{{sommaire}}' => $toc, '{{related}}' => $relatedHtml, '{{articles_similaires}}' => $relatedHtml,
        '{{tags}}' => $tagsHtml, '{{site_name}}' => htmlspecialchars($siteName), '{{site_url}}' => htmlspecialchars($siteUrl),
        '{{views}}' => intval($article['views'] ?? 0), '{{year}}' => date('Y'),
    ];
    if (class_exists('SiteSettings')) { foreach (SiteSettings::all() as $key => $val) { $vars['{{setting.'.$key.'}}'] = htmlspecialchars($val); $vars['{{'.$key.'}}'] = htmlspecialchars($val); } }
    
    $html = str_replace(array_keys($vars), array_values($vars), $tpl['html'] ?? '');
    $css = str_replace(array_keys($vars), array_values($vars), $tpl['css'] ?? '');
    $js = str_replace(array_keys($vars), array_values($vars), $tpl['js'] ?? '');
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= htmlspecialchars($siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"><link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>"><meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type" content="article"><meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <?php if ($image): ?><meta property="og:image" content="<?= htmlspecialchars($image) ?>"><?php endif; ?>
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <?php if ($css): ?><style><?= $css ?></style><?php endif; ?>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>
<main><?= $html ?></main>
<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>
<?php if ($js): ?><script><?= $js ?></script><?php endif; ?>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"BlogPosting","headline":<?= json_encode($titre, JSON_UNESCAPED_UNICODE) ?>,"description":<?= json_encode($metaDesc, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($canonical) ?>,"datePublished":"<?= date('c', strtotime($datePubli)) ?>","author":{"@type":"Person","name":<?= json_encode($auteur, JSON_UNESCAPED_UNICODE) ?>},"publisher":{"@type":"Organization","name":<?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>}<?php if ($image): ?>,"image":<?= json_encode($image) ?><?php endif; ?>}</script>
</body></html>
<?php } // end renderBlogWithTemplate


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU PAGE CMS STANDARD                                         ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderPage(PDO $db, string $slug) {
    $page = null;
    try { $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1"); $stmt->execute([$slug]); $page = $stmt->fetch(PDO::FETCH_ASSOC); } catch (PDOException $e) { error_log("Page DB error: " . $e->getMessage()); }
    
    if (!$page) {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Page non trouvée</title>';
        echo '<style>body{font-family:"DM Sans",-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#fafaf8;color:#2c3e50;text-align:center}.e{max-width:500px;padding:40px}.e h1{font-family:"Playfair Display",serif;font-size:72px;color:#e67e22;margin-bottom:10px}.e h2{font-size:24px;margin-bottom:16px}.e p{color:#7f8c8d;margin-bottom:24px}.e a{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:#e67e22;color:white;border-radius:50px;text-decoration:none;font-weight:700;transition:all .3s}.e a:hover{background:#d35400;transform:translateY(-2px)}</style>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head>';
        echo '<body><div class="e"><h1>404</h1><h2>Page non trouvée</h2><p>La page que vous cherchez n\'existe pas ou a été déplacée.</p><a href="/"><i class="fas fa-home"></i> Retour à l\'accueil</a></div></body></html>';
        return;
    }
    
    $tpl = null;
    if (!empty($page['template_id'])) { try { $st = $db->prepare("SELECT * FROM builder_templates WHERE id = ? LIMIT 1"); $st->execute([$page['template_id']]); $tpl = $st->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) {} }
    
    $hf = getHeaderFooter($db, $slug);
    if (!empty($page['header_id'])) { try { $h=$db->prepare("SELECT * FROM headers WHERE id=?"); $h->execute([$page['header_id']]); $hdr=$h->fetch(PDO::FETCH_ASSOC); if($hdr) $hf['header']=$hdr; } catch(Exception $e){} }
    if (!empty($page['footer_id'])) { try { $f=$db->prepare("SELECT * FROM footers WHERE id=?"); $f->execute([$page['footer_id']]); $ftr=$f->fetch(PDO::FETCH_ASSOC); if($ftr) $hf['footer']=$ftr; } catch(Exception $e){} }
    
    $siteName = _ss('site_name', 'Eduardo De Sul Immobilier');
    $siteUrl = rtrim(_ss('site_url', 'https://eduardo-desul-immobilier.fr'), '/');
    $metaTitle = htmlspecialchars($page['meta_title'] ?: $page['title']) . ' | ' . $siteName;
    $metaDesc = htmlspecialchars($page['meta_description'] ?? '');
    $canonical = $siteUrl . '/' . ($slug === 'accueil' ? '' : $slug);
    
    $vars = [
        '{{title}}' => htmlspecialchars($page['title'] ?? ''), '{{titre}}' => htmlspecialchars($page['title'] ?? ''),
        '{{content}}' => $page['content'] ?? '', '{{contenu}}' => $page['content'] ?? '',
        '{{slug}}' => htmlspecialchars($slug), '{{meta_title}}' => htmlspecialchars($page['meta_title'] ?? ''),
        '{{meta_description}}' => htmlspecialchars($page['meta_description'] ?? ''),
        '{{canonical}}' => htmlspecialchars($canonical), '{{site_name}}' => htmlspecialchars($siteName),
        '{{site_url}}' => htmlspecialchars($siteUrl), '{{year}}' => date('Y'),
    ];
    if (class_exists('SiteSettings')) { foreach (SiteSettings::all() as $key => $val) { $vars['{{setting.'.$key.'}}'] = htmlspecialchars($val); $vars['{{'.$key.'}}'] = htmlspecialchars($val); } }
    
    $html = ''; $css = ''; $js = '';
    if ($tpl) {
        $html = str_replace(array_keys($vars), array_values($vars), $tpl['html'] ?? '');
        $css = str_replace(array_keys($vars), array_values($vars), $tpl['css'] ?? '');
        $js = str_replace(array_keys($vars), array_values($vars), $tpl['js'] ?? '');
    } elseif (!empty($page['content'])) {
        $html = str_replace(array_keys($vars), array_values($vars), $page['content']);
    }
    
    $externalCss = []; $externalJs = [];
    if (!empty($page['external_css'])) { $externalCss = json_decode($page['external_css'], true) ?: []; }
    if (!empty($page['external_js'])) { $externalJs = json_decode($page['external_js'], true) ?: []; }
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metaTitle ?></title>
    <?php if ($metaDesc): ?><meta name="description" content="<?= $metaDesc ?>"><?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page['meta_title'] ?: $page['title']) ?>">
    <?php if ($metaDesc): ?><meta property="og:description" content="<?= $metaDesc ?>"><?php endif; ?>
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>"><meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach ($externalCss as $cssUrl): ?><link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>"><?php endforeach; ?>
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <?php if ($css): ?><style><?= $css ?></style><?php endif; ?>
    <?php if (!empty($page['custom_css'])): ?><style><?= $page['custom_css'] ?></style><?php endif; ?>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>
<main><?= $html ?></main>
<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>
<?php foreach ($externalJs as $jsUrl): ?><script src="<?= htmlspecialchars($jsUrl) ?>"></script><?php endforeach; ?>
<?php if ($js): ?><script><?= $js ?></script><?php endif; ?>
<?php if (!empty($page['custom_js'])): ?><script><?= $page['custom_js'] ?></script><?php endif; ?>
</body></html>
<?php } // end renderPage


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  FONCTIONS UTILITAIRES                                            ║
// ╚═══════════════════════════════════════════════════════════════════╝

function _ss(string $key, string $default = ''): string {
    if (class_exists('SiteSettings')) { return SiteSettings::get($key, $default); }
    return $default;
}

function formatDateFr(string $date): string {
    if (empty($date)) return '';
    $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $ts = strtotime($date);
    if (!$ts) return $date;
    $d = intval(date('d', $ts)); $m = $months[intval(date('n', $ts)) - 1]; $y = date('Y', $ts);
    return ($d === 1 ? '1er' : $d) . ' ' . $m . ' ' . $y;
}


// ╔═══════════════════════════════════════════════════════════════════╗
// ║  HEADER / FOOTER - CORRIGÉ v2                                     ║
// ║  Utilise status='active' (pas is_active)                          ║
// ║  Utilise custom_html (pas html)                                   ║
// ╚═══════════════════════════════════════════════════════════════════╝

/**
 * Charge le header et footer depuis la base de données
 * Compatible avec la vraie structure des tables headers/footers :
 *   - status ENUM('draft','active','inactive')
 *   - is_default TINYINT(1)
 *   - custom_html TEXT (pas de champ 'html')
 */
function getHeaderFooter(PDO $db, string $pageSlug = ''): array {
    $result = ['header' => null, 'footer' => null];
    
    // ── HEADER ──
    try {
        // 1. Chercher un header actif
        $stmt = $db->query("SELECT * FROM headers WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
        $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        // 2. Fallback : status vide mais marqué comme default (cas actuel en base)
        if (!$result['header']) {
            $stmt = $db->query("SELECT * FROM headers WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
            $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        
        // 3. Dernier fallback : n'importe quel header
        if (!$result['header']) {
            $stmt = $db->query("SELECT * FROM headers ORDER BY id ASC LIMIT 1");
            $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (PDOException $e) { error_log("Header load error: " . $e->getMessage()); }
    
    // ── FOOTER ──
    try {
        // 1. Chercher un footer actif
        $stmt = $db->query("SELECT * FROM footers WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
        $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        // 2. Fallback : status vide mais marqué comme default
        if (!$result['footer']) {
            $stmt = $db->query("SELECT * FROM footers WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
            $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        
        // 3. Dernier fallback : n'importe quel footer
        if (!$result['footer']) {
            $stmt = $db->query("SELECT * FROM footers ORDER BY id ASC LIMIT 1");
            $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (PDOException $e) { error_log("Footer load error: " . $e->getMessage()); }
    
    return $result;
}


/**
 * Génère le HTML du header
 * Utilise custom_html en priorité, sinon génère un header automatique
 */
function renderHeader(array $h): string {
    // ── 1. Si custom_html existe, l'utiliser directement ──
    if (!empty($h['custom_html'])) {
        $html = $h['custom_html'];
        $css = $h['custom_css'] ?? '';
        $js = $h['custom_js'] ?? '';
        
        $vars = [
            '{{site_name}}' => htmlspecialchars(_ss('site_name', 'Eduardo De Sul Immobilier')),
            '{{site_url}}' => htmlspecialchars(_ss('site_url', 'https://eduardo-desul-immobilier.fr')),
            '{{logo}}' => htmlspecialchars($h['logo_url'] ?? _ss('logo_url', '')),
            '{{logo_url}}' => htmlspecialchars($h['logo_url'] ?? _ss('logo_url', '')),
            '{{phone}}' => htmlspecialchars($h['phone_number'] ?? _ss('phone', '')),
            '{{email}}' => htmlspecialchars(_ss('email', '')),
            '{{year}}' => date('Y'),
        ];
        if (class_exists('SiteSettings')) {
            foreach (SiteSettings::all() as $key => $val) {
                $vars['{{setting.'.$key.'}}'] = htmlspecialchars($val);
                $vars['{{'.$key.'}}'] = htmlspecialchars($val);
            }
        }
        
        $html = str_replace(array_keys($vars), array_values($vars), $html);
        $output = '';
        if ($css) $output .= '<style>' . $css . '</style>';
        $output .= $html;
        if ($js) $output .= '<script>' . $js . '</script>';
        return $output;
    }
    
    // ── 2. Sinon : générer un header automatique depuis les champs structurés ──
    $name = htmlspecialchars($h['company_name'] ?? $h['name'] ?? _ss('site_name', 'Eduardo De Sul'));
    $logo = $h['logo_url'] ?? _ss('logo_url', '');
    $logoType = $h['logo_type'] ?? 'image';
    $logoText = $h['logo_text'] ?? $name;
    $phone = $h['phone_number'] ?? $h['contact_phone'] ?? _ss('phone', '');
    
    $menuItems = [];
    if (!empty($h['menu_items'])) {
        $decoded = json_decode($h['menu_items'], true);
        if (is_array($decoded)) $menuItems = $decoded;
    }
    if (empty($menuItems)) {
        $menuItems = [
            ['label' => 'Accueil', 'url' => '/'],
            ['label' => 'Acheter', 'url' => '/acheter'],
            ['label' => 'Vendre', 'url' => '/vendre'],
            ['label' => 'Estimer', 'url' => '/estimation'],
            ['label' => 'Quartiers', 'url' => '/secteurs'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => 'Contact', 'url' => '/contact'],
        ];
    }
    
    $bgColor = $h['bg_color'] ?? '#ffffff';
    $textColor = $h['text_color'] ?? '#1e293b';
    $hoverColor = $h['hover_color'] ?? '#3b82f6';
    $height = intval($h['height'] ?? 80);
    $isSticky = !empty($h['sticky']);
    
    $out = '<style>';
    $out .= '.fh{background:'.$bgColor.';border-bottom:1px solid #ecf0f1;padding:0 24px;'.($isSticky ? 'position:sticky;top:0;' : '').'z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.04)}';
    $out .= '.fh__inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:'.$height.'px;gap:20px}';
    $out .= '.fh__logo img{height:'.min(intval($h['logo_width'] ?? 150), $height - 20).'px;width:auto}';
    $out .= '.fh__logo-text{font-family:"Playfair Display",serif;font-size:18px;font-weight:700;color:'.$textColor.';text-decoration:none}';
    $out .= '.fh__nav{display:flex;align-items:center;gap:4px}';
    $out .= '.fh__link{padding:8px 14px;font-size:14px;font-weight:500;color:'.$textColor.';text-decoration:none;border-radius:6px;transition:all .2s}';
    $out .= '.fh__link:hover{background:rgba(230,126,34,.1);color:'.$hoverColor.'}';
    $out .= '.fh__cta{padding:10px 22px;background:#e67e22;color:white;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;transition:all .3s;display:flex;align-items:center;gap:6px}';
    $out .= '.fh__cta:hover{background:#d35400;transform:translateY(-1px)}';
    $out .= '.fh__burger{display:none;background:none;border:none;font-size:22px;color:'.$textColor.';cursor:pointer;padding:8px}';
    $out .= '@media(max-width:'.intval($h['mobile_breakpoint'] ?? 1024).'px){.fh__nav{display:none;position:absolute;top:'.$height.'px;left:0;right:0;background:'.$bgColor.';flex-direction:column;padding:16px;box-shadow:0 8px 20px rgba(0,0,0,.1);border-top:1px solid #ecf0f1}.fh__nav.open{display:flex}.fh__burger{display:block}}';
    $out .= '</style>';
    
    $out .= '<header class="fh"><div class="fh__inner">';
    
    // ========================================================
    // SUITE DE renderHeader() - à partir de "// Logo"
    // ========================================================
    
    // Logo
    if ($logoType === 'image' && $logo) {
        $out .= '<a href="'.htmlspecialchars($h['logo_link'] ?? '/').'" class="fh__logo"><img src="'.htmlspecialchars($logo).'" alt="'.htmlspecialchars($h['logo_alt'] ?? $name).'"></a>';
    } else {
        $out .= '<a href="'.htmlspecialchars($h['logo_link'] ?? '/').'" class="fh__logo-text">'.htmlspecialchars($logoText ?: $name).'</a>';
    }
    
    // Burger mobile
    $out .= '<button type="button" class="fh__burger" onclick="document.querySelector(\'.fh__nav\').classList.toggle(\'open\')" aria-label="Menu"><i class="fas fa-bars"></i></button>';
    
    // Navigation
    $out .= '<nav class="fh__nav">';
    foreach ($menuItems as $item) {
        $out .= '<a href="'.htmlspecialchars($item['url'] ?? '#').'" class="fh__link">'.htmlspecialchars($item['label'] ?? '').'</a>';
    }
    
    // CTA
    if (!empty($h['cta_enabled']) && !empty($h['cta_text'])) {
        $out .= '<a href="'.htmlspecialchars($h['cta_link'] ?? '/contact').'" class="fh__cta"><i class="fas fa-phone-alt"></i> '.htmlspecialchars($h['cta_text']).'</a>';
    } elseif (!empty($h['phone_enabled']) && $phone) {
        $out .= '<a href="tel:'.htmlspecialchars(preg_replace('/\s+/', '', $phone)).'" class="fh__cta"><i class="fas fa-phone-alt"></i> '.htmlspecialchars($phone).'</a>';
    }
    
    $out .= '</nav></div></header>';
    return $out;
}


/**
 * Génère le HTML du footer
 * Utilise custom_html en priorité, sinon génère un footer automatique
 */
function renderFooter(array $f): string {
    // ── 1. Si custom_html existe, l'utiliser directement ──
    if (!empty($f['custom_html'])) {
        $html = $f['custom_html'];
        $css = $f['custom_css'] ?? '';
        $js = $f['custom_js'] ?? '';
        
        $vars = [
            '{{site_name}}' => htmlspecialchars(_ss('site_name', 'Eduardo De Sul Immobilier')),
            '{{site_url}}' => htmlspecialchars(_ss('site_url', 'https://eduardo-desul-immobilier.fr')),
            '{{logo}}' => htmlspecialchars($f['logo_url'] ?? _ss('logo_url', '')),
            '{{logo_url}}' => htmlspecialchars($f['logo_url'] ?? _ss('logo_url', '')),
            '{{phone}}' => htmlspecialchars($f['phone'] ?? $f['contact_phone'] ?? _ss('phone', '')),
            '{{email}}' => htmlspecialchars($f['email'] ?? $f['contact_email'] ?? _ss('email', '')),
            '{{year}}' => date('Y'),
        ];
        if (class_exists('SiteSettings')) {
            foreach (SiteSettings::all() as $key => $val) {
                $vars['{{setting.'.$key.'}}'] = htmlspecialchars($val);
                $vars['{{'.$key.'}}'] = htmlspecialchars($val);
            }
        }
        
        $html = str_replace(array_keys($vars), array_values($vars), $html);
        $output = '';
        if ($css) $output .= '<style>' . $css . '</style>';
        $output .= $html;
        if ($js) $output .= '<script>' . $js . '</script>';
        return $output;
    }
    
    // ── 2. Sinon : générer un footer automatique depuis les champs structurés ──
    $name = htmlspecialchars($f['company_name'] ?? $f['name'] ?? _ss('site_name', 'Eduardo De Sul Immobilier'));
    $phone = htmlspecialchars($f['phone'] ?? $f['contact_phone'] ?? _ss('phone', ''));
    $email = htmlspecialchars($f['email'] ?? $f['contact_email'] ?? _ss('email', ''));
    $description = htmlspecialchars($f['description'] ?? 'Votre conseiller immobilier à Bordeaux. Accompagnement personnalisé pour tous vos projets.');
    $bgColor = $f['bg_color'] ?? '#1e293b';
    $textColor = $f['text_color'] ?? '#94a3b8';
    $headingColor = $f['heading_color'] ?? '#ffffff';
    $linkColor = $f['link_color'] ?? '#cbd5e1';
    $linkHoverColor = $f['link_hover_color'] ?? '#3b82f6';
    $copyrightText = $f['copyright_text'] ?? '© '.date('Y').' '.$name.' — Tous droits réservés';
    $paddingTop = intval($f['padding_top'] ?? 60);
    $paddingBottom = intval($f['padding_bottom'] ?? 40);
    
    $socialLinks = [];
    if (!empty($f['social_links'])) { $decoded = json_decode($f['social_links'], true); if (is_array($decoded)) $socialLinks = $decoded; }
    
    $columns = [];
    if (!empty($f['columns'])) { $decoded = json_decode($f['columns'], true); if (is_array($decoded)) $columns = $decoded; }
    
    $legalLinks = [];
    if (!empty($f['legal_links'])) { $decoded = json_decode($f['legal_links'], true); if (is_array($decoded)) $legalLinks = $decoded; }
    
    $out = '<style>';
    $out .= '.ff{background:'.$bgColor.';color:'.$textColor.';padding:'.$paddingTop.'px 24px '.$paddingBottom.'px}';
    $out .= '.ff__inner{max-width:1200px;margin:0 auto}';
    $out .= '.ff__top{display:flex;justify-content:space-between;gap:40px;flex-wrap:wrap;margin-bottom:32px;padding-bottom:32px;border-bottom:1px solid rgba(255,255,255,.1)}';
    $out .= '.ff__brand{max-width:300px}';
    $out .= '.ff__name{font-family:"Playfair Display",serif;font-size:20px;font-weight:700;color:'.$headingColor.';margin-bottom:8px}';
    $out .= '.ff__desc{font-size:13px;opacity:.7;line-height:1.6}';
    $out .= '.ff__col h4{color:'.$headingColor.';font-size:15px;font-weight:700;margin-bottom:12px}';
    $out .= '.ff__col ul{list-style:none;padding:0;margin:0}';
    $out .= '.ff__col li{margin-bottom:8px}';
    $out .= '.ff__col a{color:'.$linkColor.';text-decoration:none;font-size:14px;opacity:.8;transition:all .2s}';
    $out .= '.ff__col a:hover{color:'.$linkHoverColor.';opacity:1}';
    $out .= '.ff__contact p{font-size:14px;margin-bottom:6px;opacity:.8;display:flex;align-items:center;gap:8px}';
    $out .= '.ff__contact a{color:'.$linkColor.';text-decoration:none}.ff__contact a:hover{color:'.$linkHoverColor.'}';
    $out .= '.ff__social{display:flex;gap:10px;margin-top:12px}';
    $out .= '.ff__social a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:'.$linkColor.';text-decoration:none;transition:all .2s;font-size:14px}';
    $out .= '.ff__social a:hover{background:#e67e22;color:white;transform:translateY(-2px)}';
    $out .= '.ff__bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:13px}';
    $out .= '.ff__copyright{opacity:.7}';
    $out .= '.ff__legal{display:flex;gap:16px;flex-wrap:wrap}';
    $out .= '.ff__legal a{color:'.$linkColor.';text-decoration:none;font-size:13px;opacity:.7;transition:all .2s}.ff__legal a:hover{color:'.$linkHoverColor.';opacity:1}';
    $out .= '@media(max-width:768px){.ff__top{flex-direction:column;gap:32px}.ff__bottom{flex-direction:column;text-align:center;gap:8px}}';
    $out .= '</style>';
    
    $out .= '<footer class="ff"><div class="ff__inner">';
    
    // ── Top section ──
    $out .= '<div class="ff__top">';
    
    // Brand column
    $out .= '<div class="ff__brand">';
    $logo = $f['logo_url'] ?? _ss('logo_url', '');
    if ($logo) {
        $out .= '<a href="/"><img src="'.htmlspecialchars($logo).'" alt="'.htmlspecialchars($name).'" style="height:40px;width:auto;margin-bottom:12px"></a>';
    }
    $out .= '<div class="ff__name">'.$name.'</div>';
    $out .= '<p class="ff__desc">'.$description.'</p>';
    
    // Social links
    if (!empty($socialLinks)) {
        $socialIcons = [
            'facebook' => 'fab fa-facebook-f', 'instagram' => 'fab fa-instagram',
            'linkedin' => 'fab fa-linkedin-in', 'twitter' => 'fab fa-x-twitter',
            'youtube' => 'fab fa-youtube', 'tiktok' => 'fab fa-tiktok',
            'pinterest' => 'fab fa-pinterest-p', 'whatsapp' => 'fab fa-whatsapp',
        ];
        $out .= '<div class="ff__social">';
        foreach ($socialLinks as $social) {
            $sUrl = $social['url'] ?? $social['link'] ?? '#';
            $sName = strtolower($social['name'] ?? $social['platform'] ?? $social['type'] ?? 'link');
            $sIcon = $socialIcons[$sName] ?? 'fas fa-link';
            $out .= '<a href="'.htmlspecialchars($sUrl).'" target="_blank" rel="noopener" aria-label="'.htmlspecialchars(ucfirst($sName)).'"><i class="'.$sIcon.'"></i></a>';
        }
        $out .= '</div>';
    }
    $out .= '</div>'; // end ff__brand
    
    // Dynamic columns
    if (!empty($columns)) {
        foreach ($columns as $col) {
            $out .= '<div class="ff__col">';
            $out .= '<h4>'.htmlspecialchars($col['title'] ?? $col['label'] ?? '').'</h4>';
            if (!empty($col['links']) && is_array($col['links'])) {
                $out .= '<ul>';
                foreach ($col['links'] as $link) {
                    $out .= '<li><a href="'.htmlspecialchars($link['url'] ?? '#').'">'.htmlspecialchars($link['label'] ?? $link['text'] ?? '').'</a></li>';
                }
                $out .= '</ul>';
            }
            $out .= '</div>';
        }
    } else {
        // Default columns if none configured
        $out .= '<div class="ff__col"><h4>Navigation</h4><ul>';
        $defaultLinks = [
            ['label' => 'Accueil', 'url' => '/'],
            ['label' => 'Acheter', 'url' => '/acheter'],
            ['label' => 'Vendre', 'url' => '/vendre'],
            ['label' => 'Estimer', 'url' => '/estimation'],
            ['label' => 'Quartiers', 'url' => '/secteurs'],
            ['label' => 'Blog', 'url' => '/blog'],
        ];
        foreach ($defaultLinks as $dl) { $out .= '<li><a href="'.$dl['url'].'">'.$dl['label'].'</a></li>'; }
        $out .= '</ul></div>';
        
        $out .= '<div class="ff__col"><h4>Informations</h4><ul>';
        $out .= '<li><a href="/mentions-legales">Mentions légales</a></li>';
        $out .= '<li><a href="/politique-confidentialite">Politique de confidentialité</a></li>';
        $out .= '<li><a href="/contact">Contact</a></li>';
        $out .= '</ul></div>';
    }
    
    // Contact column
    $out .= '<div class="ff__col ff__contact"><h4>Contact</h4>';
    if ($phone) { $out .= '<p><i class="fas fa-phone-alt"></i> <a href="tel:'.htmlspecialchars(preg_replace('/\s+/', '', $phone)).'">'.$phone.'</a></p>'; }
    if ($email) { $out .= '<p><i class="fas fa-envelope"></i> <a href="mailto:'.$email.'">'.$email.'</a></p>'; }
    $address = htmlspecialchars($f['address'] ?? $f['company_address'] ?? _ss('address', ''));
    if ($address) { $out .= '<p><i class="fas fa-map-marker-alt"></i> '.$address.'</p>'; }
    $out .= '</div>';
    
    $out .= '</div>'; // end ff__top
    
    // ── Bottom section ──
    $out .= '<div class="ff__bottom">';
    $out .= '<div class="ff__copyright">'.htmlspecialchars(str_replace(['{year}', '{{year}}'], date('Y'), $copyrightText)).'</div>';
    
    if (!empty($legalLinks)) {
        $out .= '<div class="ff__legal">';
        foreach ($legalLinks as $ll) {
            $out .= '<a href="'.htmlspecialchars($ll['url'] ?? '#').'">'.htmlspecialchars($ll['label'] ?? $ll['text'] ?? '').'</a>';
        }
        $out .= '</div>';
    } else {
        $out .= '<div class="ff__legal">';
        $out .= '<a href="/mentions-legales">Mentions légales</a>';
        $out .= '<a href="/politique-confidentialite">Confidentialité</a>';
        $out .= '</div>';
    }
    
    $out .= '</div>'; // end ff__bottom
    $out .= '</div></footer>'; // end ff__inner + footer
    
    return $out;
}
// ╔═══════════════════════════════════════════════════════════════════╗
// ║  RENDU LISTING BLOG                                               ║
// ╚═══════════════════════════════════════════════════════════════════╝

function renderBlogListing(PDO $db) {
    // ── Pagination ──
    $perPage = 9;
    $currentPage = max(1, intval($_GET['page'] ?? 1));
    $offset = ($currentPage - 1) * $perPage;
    
    // ── Filtre catégorie ──
    $filterCat = trim($_GET['categorie'] ?? $_GET['cat'] ?? '');
    
    // ── Récupérer les catégories ──
    $categories = [];
    try {
        $stmt = $db->query("SELECT id, name, slug, color, icon FROM article_categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Blog categories error: " . $e->getMessage()); }
    
    // ── Compter le total d'articles publiés ──
$whereClause = "(a.status = 'published' OR a.statut = 'publie' OR a.statut = 'publié')";
    $params = [];
    
    if ($filterCat) {
        // Chercher par slug de catégorie ou par category_id
        $catId = null;
        foreach ($categories as $c) {
            if ($c['slug'] === $filterCat) { $catId = $c['id']; break; }
        }
        if ($catId) {
            $whereClause .= " AND category_id = ?";
            $params[] = $catId;
        } else {
            $whereClause .= " AND (raison_vente = ? OR persona = ?)";
            $params[] = $filterCat;
            $params[] = $filterCat;
        }
    }
    
    $totalArticles = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE $whereClause");
        $stmt->execute($params);
        $totalArticles = intval($stmt->fetchColumn());
    } catch (PDOException $e) { error_log("Blog count error: " . $e->getMessage()); }
    
    $totalPages = max(1, ceil($totalArticles / $perPage));
    if ($currentPage > $totalPages) $currentPage = $totalPages;
    
    // ── Récupérer les articles ──
    $articles = [];
    try {
        $sql = "SELECT a.id, a.titre, a.slug, a.extrait, a.contenu, a.featured_image, a.image, 
                       a.raison_vente, a.persona, a.category_id, a.date_publication, a.published_at, 
                       a.created_at, a.views, a.temps_lecture, a.reading_time, a.author, a.auteur,
                       c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, c.icon AS cat_icon
                FROM articles a
                LEFT JOIN article_categories c ON a.category_id = c.id
                WHERE $whereClause
                ORDER BY COALESCE(a.date_publication, a.published_at, a.created_at) DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Blog listing error: " . $e->getMessage()); }
    
    // ── Article mis en avant (le plus récent, première page seulement) ──
    $featured = null;
    if ($currentPage === 1 && !$filterCat && !empty($articles)) {
        $featured = array_shift($articles);
    }
    
    // ── Compteur par catégorie ──
    $catCounts = [];
    try {
        $stmt = $db->query("SELECT category_id, COUNT(*) as cnt FROM articles WHERE (status = 'published' OR statut = 'publie' OR statut = 'publié') AND category_id IS NOT NULL GROUP BY category_id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $catCounts[$row['category_id']] = intval($row['cnt']);
        }
    } catch (Exception $e) {}
    
    $hf = getHeaderFooter($db, 'blog');
    $siteName = _ss('site_name', 'Eduardo De Sul Immobilier');
    $siteUrl = rtrim(_ss('site_url', 'https://eduardo-desul-immobilier.fr'), '/');
    $metaTitle = ($filterCat ? ucfirst($filterCat) . ' - ' : '') . "Blog Immobilier Bordeaux | $siteName";
    $metaDesc = "Retrouvez tous nos articles et conseils immobiliers à Bordeaux : achat, vente, estimation, investissement, quartiers. Par Eduardo De Sul, conseiller eXp France.";
    $canonical = $siteUrl . '/blog' . ($filterCat ? '?categorie=' . urlencode($filterCat) : '') . ($currentPage > 1 ? ($filterCat ? '&' : '?') . 'page=' . $currentPage : '');
    
    ?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <?php if ($currentPage > 1): ?><link rel="prev" href="<?= htmlspecialchars($siteUrl . '/blog' . ($filterCat ? '?categorie=' . urlencode($filterCat) . '&' : '?') . 'page=' . ($currentPage - 1)) ?>"><?php endif; ?>
    <?php if ($currentPage < $totalPages): ?><link rel="next" href="<?= htmlspecialchars($siteUrl . '/blog' . ($filterCat ? '?categorie=' . urlencode($filterCat) . '&' : '?') . 'page=' . ($currentPage + 1)) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <?php if (class_exists('SiteSettings')): ?><?= SiteSettings::cssVars() ?><?= SiteSettings::googleFonts() ?><?= SiteSettings::trackingHead() ?><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($hf['header']['custom_css'])): ?><style><?= $hf['header']['custom_css'] ?></style><?php endif; ?>
    <?php if (!empty($hf['footer']['custom_css'])): ?><style><?= $hf['footer']['custom_css'] ?></style><?php endif; ?>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'DM Sans',-apple-system,BlinkMacSystemFont,sans-serif;line-height:1.6;color:#2c3e50;background:#fafaf8}
    img{max-width:100%;height:auto}

    /* ── Hero ── */
    .bl-hero{position:relative;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);padding:100px 0 80px;overflow:hidden;color:white;text-align:center}
    .bl-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .bl-hero__inner{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:0 24px}
    .bl-hero__breadcrumb{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px;font-size:14px;opacity:.7}
    .bl-hero__breadcrumb a{color:white;text-decoration:none}.bl-hero__breadcrumb a:hover{opacity:1}
    .bl-hero__title{font-family:'Playfair Display',Georgia,serif;font-size:clamp(32px,5vw,52px);font-weight:700;line-height:1.15;margin-bottom:16px;letter-spacing:-.5px}
    .bl-hero__title em{font-style:normal;color:#e67e22}
    .bl-hero__subtitle{font-size:18px;opacity:.85;max-width:640px;margin:0 auto 40px;line-height:1.6}
    .bl-hero__stats{display:flex;justify-content:center;gap:40px;flex-wrap:wrap}
    .bl-hero__stat-value{font-family:'Playfair Display',serif;font-size:36px;font-weight:700;color:#e67e22;line-height:1;margin-bottom:4px}
    .bl-hero__stat-label{font-size:13px;font-weight:500;opacity:.65;text-transform:uppercase;letter-spacing:1px}

    /* ── Filtres catégories ── */
    .bl-filters{background:white;border-bottom:1px solid #ecf0f1;padding:20px 0;position:sticky;top:0;z-index:50;box-shadow:0 2px 10px rgba(0,0,0,.04)}
    .bl-filters__inner{max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .bl-pill{padding:8px 18px;border:2px solid #ecf0f1;border-radius:50px;font-family:inherit;font-size:13px;font-weight:600;color:#7f8c8d;background:white;cursor:pointer;transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .bl-pill:hover{border-color:#e67e22;color:#e67e22}
    .bl-pill.active{background:#e67e22;color:white;border-color:#e67e22}
    .bl-pill__count{font-size:11px;opacity:.7}

    /* ── Featured article ── */
    .bl-featured{max-width:1200px;margin:0 auto;padding:48px 24px 0}
    .bl-featured__card{display:grid;grid-template-columns:1.2fr 1fr;gap:0;background:white;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);border:1px solid #ecf0f1;text-decoration:none;color:inherit;transition:all .4s}
    .bl-featured__card:hover{transform:translateY(-4px);box-shadow:0 16px 50px rgba(0,0,0,.12)}
    .bl-featured__img{position:relative;min-height:360px;background-size:cover;background-position:center;background-color:#1a1a2e}
    .bl-featured__img-badge{position:absolute;top:20px;left:20px;padding:6px 14px;background:rgba(230,126,34,.95);color:white;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .bl-featured__body{padding:40px;display:flex;flex-direction:column;justify-content:center}
    .bl-featured__cat{font-size:12px;font-weight:700;color:#e67e22;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:6px}
    .bl-featured__title{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#1a1a2e;line-height:1.25;margin-bottom:12px}
    .bl-featured__excerpt{font-size:15px;color:#7f8c8d;line-height:1.7;margin-bottom:20px;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
    .bl-featured__meta{display:flex;align-items:center;gap:16px;font-size:13px;color:#95a5a6;margin-bottom:20px}
    .bl-featured__meta i{margin-right:4px}
    .bl-featured__cta{display:inline-flex;align-items:center;gap:8px;color:#e67e22;font-weight:700;font-size:14px;transition:gap .3s}
    .bl-featured__card:hover .bl-featured__cta{gap:12px}

    /* ── Grille articles ── */
    .bl-listing{max-width:1200px;margin:0 auto;padding:40px 24px 80px}
    .bl-listing__label{font-size:13px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px}
    .bl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:28px}
    .bl-card{background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.05);border:1px solid #ecf0f1;transition:all .35s cubic-bezier(.25,.46,.45,.94);text-decoration:none;color:inherit;display:flex;flex-direction:column}
    .bl-card:hover{transform:translateY(-6px);box-shadow:0 12px 40px rgba(0,0,0,.1);border-color:#e67e22}
    .bl-card__img{position:relative;height:210px;overflow:hidden;background:#e8e8e8}
    .bl-card__img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .bl-card:hover .bl-card__img img{transform:scale(1.05)}
    .bl-card__img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a1a2e,#0f3460);color:rgba(255,255,255,.3);font-size:48px}
    .bl-card__img-badge{position:absolute;top:12px;left:12px;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:white}
    .bl-card__body{padding:22px;flex:1;display:flex;flex-direction:column}
    .bl-card__cat{font-size:11px;font-weight:700;color:#e67e22;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:flex;align-items:center;gap:5px}
    .bl-card__title{font-family:'Playfair Display',serif;font-size:19px;font-weight:700;color:#1a1a2e;line-height:1.3;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .bl-card__excerpt{font-size:14px;color:#7f8c8d;line-height:1.6;margin-bottom:16px;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .bl-card__footer{display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid #ecf0f1}
    .bl-card__date{font-size:12px;color:#95a5a6;display:flex;align-items:center;gap:5px}
    .bl-card__read{font-size:12px;color:#95a5a6;display:flex;align-items:center;gap:5px}
    .bl-card__cta{font-size:13px;font-weight:700;color:#e67e22;display:flex;align-items:center;gap:6px;transition:gap .3s}
    .bl-card:hover .bl-card__cta{gap:10px}

    /* ── Pagination ── */
    .bl-pagination{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:48px}
    .bl-pagination a,.bl-pagination span{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:all .25s;border:2px solid #ecf0f1;color:#7f8c8d;background:white}
    .bl-pagination a:hover{border-color:#e67e22;color:#e67e22;background:#fef9f3}
    .bl-pagination .active{background:#e67e22;color:white;border-color:#e67e22}
    .bl-pagination .dots{border:none;background:none;color:#95a5a6;width:auto;padding:0 4px}
    .bl-pagination .nav-arrow{width:auto;padding:0 16px;gap:6px;font-size:13px}

    /* ── Empty state ── */
    .bl-empty{text-align:center;padding:80px 20px;color:#7f8c8d}
    .bl-empty__icon{font-size:48px;margin-bottom:16px;opacity:.3}
    .bl-empty__title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#2c3e50;margin-bottom:8px}
    .bl-empty__text{font-size:15px;margin-bottom:24px}
    .bl-empty__btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:#e67e22;color:white;border-radius:50px;text-decoration:none;font-weight:700;font-size:14px;transition:all .3s}
    .bl-empty__btn:hover{background:#d35400;transform:translateY(-2px)}

    /* ── CTA bottom ── */
    .bl-cta{background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:80px 24px;text-align:center;color:white}
    .bl-cta__title{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;margin-bottom:16px}
    .bl-cta__text{font-size:16px;opacity:.8;max-width:500px;margin:0 auto 32px;line-height:1.6}
    .bl-cta__btn{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;background:#e67e22;color:white;border-radius:50px;font-size:16px;font-weight:700;text-decoration:none;transition:all .3s;box-shadow:0 4px 15px rgba(230,126,34,.3)}
    .bl-cta__btn:hover{background:#d35400;transform:translateY(-2px)}

    @media(max-width:768px){
        .bl-hero{padding:60px 0 50px}
        .bl-featured__card{grid-template-columns:1fr}
        .bl-featured__img{min-height:220px}
        .bl-featured__body{padding:24px}
        .bl-grid{grid-template-columns:1fr}
        .bl-filters__inner{justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap;-webkit-overflow-scrolling:touch}
        .bl-pill{white-space:nowrap;flex-shrink:0}
    }
    </style>
</head>
<body>
<?php if (class_exists('SiteSettings')): ?><?= SiteSettings::trackingBody() ?><?php endif; ?>
<?php if (!empty($hf['header'])): echo renderHeader($hf['header']); endif; ?>

<main>

<!-- ═══ HERO ═══ -->
<section class="bl-hero">
    <div class="bl-hero__inner">
        <nav class="bl-hero__breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a> <span>›</span> <span>Blog</span>
        </nav>
        <h1 class="bl-hero__title">Le <em>blog</em> immobilier</h1>
        <p class="bl-hero__subtitle">Conseils d'expert, analyses du marché bordelais et guides pratiques pour réussir votre projet immobilier.</p>
        <div class="bl-hero__stats">
            <div><div class="bl-hero__stat-value"><?= $totalArticles ?></div><div class="bl-hero__stat-label">Articles</div></div>
            <div><div class="bl-hero__stat-value"><?= count($categories) ?></div><div class="bl-hero__stat-label">Catégories</div></div>
        </div>
    </div>
</section>

<!-- ═══ FILTRES CATÉGORIES ═══ -->
<?php if (!empty($categories)): ?>
<div class="bl-filters">
    <div class="bl-filters__inner">
        <a href="/blog" class="bl-pill <?= !$filterCat ? 'active' : '' ?>"><i class="fas fa-globe-europe"></i> Tous <span class="bl-pill__count">(<?= $totalArticles ?>)</span></a>
        <?php foreach ($categories as $cat):
            $cnt = $catCounts[$cat['id']] ?? 0;
            if ($cnt === 0) continue;
            $isActive = ($filterCat === $cat['slug']);
        ?>
        <a href="/blog?categorie=<?= htmlspecialchars($cat['slug']) ?>" class="bl-pill <?= $isActive ? 'active' : '' ?>" <?= $isActive ? '' : 'style="--cat-color:'.htmlspecialchars($cat['color'] ?? '#e67e22').'"' ?>>
            <i class="<?= htmlspecialchars($cat['icon'] ?? 'fas fa-folder') ?>"></i>
            <?= htmlspecialchars($cat['name']) ?>
            <span class="bl-pill__count">(<?= $cnt ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($totalArticles === 0): ?>

<!-- ═══ EMPTY STATE ═══ -->
<div class="bl-listing">
    <div class="bl-empty">
        <div class="bl-empty__icon"><i class="fas fa-newspaper"></i></div>
        <h2 class="bl-empty__title"><?= $filterCat ? 'Aucun article dans cette catégorie' : 'Aucun article publié' ?></h2>
        <p class="bl-empty__text"><?= $filterCat ? 'Essayez une autre catégorie ou consultez tous les articles.' : 'Les articles arrivent bientôt. Restez connecté !' ?></p>
        <?php if ($filterCat): ?><a href="/blog" class="bl-empty__btn"><i class="fas fa-arrow-left"></i> Voir tous les articles</a><?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- ═══ ARTICLE MIS EN AVANT ═══ -->
<?php if ($featured):
    $fTitre = htmlspecialchars($featured['titre'] ?? '');
    $fSlug = htmlspecialchars($featured['slug'] ?? '');
    $fImg = $featured['featured_image'] ?? $featured['image'] ?? '';
    $fExtrait = htmlspecialchars($featured['extrait'] ?? mb_substr(strip_tags($featured['contenu'] ?? ''), 0, 250));
    $fCat = htmlspecialchars($featured['cat_name'] ?? $featured['raison_vente'] ?? $featured['persona'] ?? '');
    $fCatColor = $featured['cat_color'] ?? '#e67e22';
    $fDate = formatDateFr($featured['date_publication'] ?? $featured['published_at'] ?? $featured['created_at'] ?? '');
    $fRead = intval($featured['reading_time'] ?? $featured['temps_lecture'] ?? 5);
    $fViews = intval($featured['views'] ?? 0);
?>
<div class="bl-featured">
    <a href="/blog/<?= $fSlug ?>" class="bl-featured__card">
        <div class="bl-featured__img" style="<?= $fImg ? "background-image:url('".htmlspecialchars($fImg)."')" : 'background:linear-gradient(135deg,#1a1a2e,#0f3460)' ?>">
            <span class="bl-featured__img-badge">À la une</span>
        </div>
        <div class="bl-featured__body">
            <?php if ($fCat): ?><div class="bl-featured__cat"><i class="<?= htmlspecialchars($featured['cat_icon'] ?? 'fas fa-tag') ?>"></i> <?= $fCat ?></div><?php endif; ?>
            <h2 class="bl-featured__title"><?= $fTitre ?></h2>
            <p class="bl-featured__excerpt"><?= $fExtrait ?></p>
            <div class="bl-featured__meta">
                <span><i class="far fa-calendar-alt"></i> <?= $fDate ?></span>
                <span><i class="far fa-clock"></i> <?= $fRead ?> min</span>
                <?php if ($fViews > 0): ?><span><i class="far fa-eye"></i> <?= number_format($fViews, 0, ',', ' ') ?></span><?php endif; ?>
            </div>
            <span class="bl-featured__cta">Lire l'article <i class="fas fa-arrow-right"></i></span>
        </div>
    </a>
</div>
<?php endif; ?>

<!-- ═══ GRILLE D'ARTICLES ═══ -->
<div class="bl-listing">
    <div class="bl-listing__label"><?= $filterCat ? htmlspecialchars(ucfirst($filterCat)) : 'Tous les articles' ?> — <?= $totalArticles ?> article<?= $totalArticles > 1 ? 's' : '' ?></div>
    
    <div class="bl-grid">
    <?php foreach ($articles as $a):
        $aTitre = htmlspecialchars($a['titre'] ?? '');
        $aSlug = htmlspecialchars($a['slug'] ?? '');
        $aImg = $a['featured_image'] ?? $a['image'] ?? '';
        $aExtrait = htmlspecialchars($a['extrait'] ?? mb_substr(strip_tags($a['contenu'] ?? ''), 0, 150));
        $aCat = htmlspecialchars($a['cat_name'] ?? $a['raison_vente'] ?? $a['persona'] ?? '');
        $aCatColor = $a['cat_color'] ?? '#e67e22';
        $aDate = formatDateFr($a['date_publication'] ?? $a['published_at'] ?? $a['created_at'] ?? '');
        $aRead = intval($a['reading_time'] ?? $a['temps_lecture'] ?? 5);
    ?>
        <a href="/blog/<?= $aSlug ?>" class="bl-card">
            <div class="bl-card__img">
                <?php if ($aImg): ?>
                    <img src="<?= htmlspecialchars($aImg) ?>" alt="<?= $aTitre ?>" loading="lazy">
                <?php else: ?>
                    <div class="bl-card__img-placeholder"><i class="fas fa-newspaper"></i></div>
                <?php endif; ?>
                <?php if ($aCat): ?>
                    <span class="bl-card__img-badge" style="background:<?= htmlspecialchars($aCatColor) ?>"><?= $aCat ?></span>
                <?php endif; ?>
            </div>
            <div class="bl-card__body">
                <?php if ($aCat): ?><div class="bl-card__cat"><i class="fas fa-tag"></i> <?= $aCat ?></div><?php endif; ?>
                <h3 class="bl-card__title"><?= $aTitre ?></h3>
                <p class="bl-card__excerpt"><?= $aExtrait ?></p>
                <div class="bl-card__footer">
                    <span class="bl-card__date"><i class="far fa-calendar-alt"></i> <?= $aDate ?></span>
                    <span class="bl-card__read"><i class="far fa-clock"></i> <?= $aRead ?> min</span>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>

    <!-- ═══ PAGINATION ═══ -->
    <?php if ($totalPages > 1): ?>
    <nav class="bl-pagination" aria-label="Pagination">
        <?php 
        $baseUrl = '/blog' . ($filterCat ? '?categorie=' . urlencode($filterCat) : '');
        $sep = $filterCat ? '&' : '?';
        
        if ($currentPage > 1): ?>
            <a href="<?= $baseUrl . $sep ?>page=<?= $currentPage - 1 ?>" class="nav-arrow"><i class="fas fa-chevron-left"></i> Précédent</a>
        <?php endif;
        
        for ($p = 1; $p <= $totalPages; $p++):
            if ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 2):
                if ($p === $currentPage): ?>
                    <span class="active"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $baseUrl . ($p > 1 ? $sep . 'page=' . $p : '') ?>"><?= $p ?></a>
                <?php endif;
            elseif (abs($p - $currentPage) === 3): ?>
                <span class="dots">…</span>
            <?php endif;
        endfor;
        
        if ($currentPage < $totalPages): ?>
            <a href="<?= $baseUrl . $sep ?>page=<?= $currentPage + 1 ?>" class="nav-arrow">Suivant <i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

</div>
<?php endif; ?>

<!-- ═══ CTA BOTTOM ═══ -->
<section class="bl-cta">
    <h2 class="bl-cta__title">Un projet immobilier à Bordeaux ?</h2>
    <p class="bl-cta__text">Eduardo vous accompagne avec des conseils personnalisés pour acheter, vendre ou investir à Bordeaux et ses environs.</p>
    <a href="/contact" class="bl-cta__btn"><i class="fas fa-phone-alt"></i> Prendre rendez-vous</a>
</section>

<!-- ═══ SCHEMA.ORG ═══ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Blog",
    "name": "Blog Immobilier - <?= htmlspecialchars($siteName) ?>",
    "description": "<?= htmlspecialchars($metaDesc) ?>",
    "url": "<?= htmlspecialchars($siteUrl) ?>/blog",
    "publisher": {
        "@type": "Organization",
        "name": "<?= htmlspecialchars($siteName) ?>"
    }<?php if (!empty($articles) || $featured): ?>,
    "blogPost": [
        <?php 
        $allForSchema = $featured ? array_merge([$featured], $articles) : $articles;
        foreach ($allForSchema as $i => $sa):
            $saTitle = $sa['titre'] ?? '';
            $saUrl = $siteUrl . '/blog/' . ($sa['slug'] ?? '');
            $saDate = $sa['date_publication'] ?? $sa['published_at'] ?? $sa['created_at'] ?? '';
            $saAuthor = $sa['author'] ?? $sa['auteur'] ?? 'Eduardo De Sul';
        ?>
        {
            "@type": "BlogPosting",
            "headline": <?= json_encode($saTitle, JSON_UNESCAPED_UNICODE) ?>,
            "url": <?= json_encode($saUrl) ?>,
            "datePublished": "<?= date('c', strtotime($saDate ?: 'now')) ?>",
            "author": {"@type": "Person", "name": <?= json_encode($saAuthor, JSON_UNESCAPED_UNICODE) ?>}
        }<?= $i < count($allForSchema) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ]
    <?php endif; ?>
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Accueil", "item": "<?= htmlspecialchars($siteUrl) ?>/"},
        {"@type": "ListItem", "position": 2, "name": "Blog", "item": "<?= htmlspecialchars($siteUrl) ?>/blog"}
    ]
}
</script>

</main>

<?php if (!empty($hf['footer'])): echo renderFooter($hf['footer']); endif; ?>
</body>
</html>
<?php } // end renderBlogListing