<?php
/**
 * Blog Article Single - Page publique
 * 
 * PLACEMENT : /front/pages/blog/index.php
 * 
 * URL : /blog/mon-article-slug  → Affiche l'article
 * 
 * HTACCESS : RewriteRule ^blog/([a-z0-9-]+)/?$ /front/pages/blog/index.php?slug=$1 [QSA,L]
 */

// ── Initialisation ──
$root = dirname(dirname(dirname(__DIR__))); // blog/ → pages/ → front/ → racine

if (!file_exists($root . '/config/config.php')) {
    $root = $_SERVER['DOCUMENT_ROOT'];
}

require_once $root . '/config/config.php';

// ── Récupérer le slug ──
$slug = trim($_GET['slug'] ?? '');
$slug = preg_replace('/[^a-z0-9-]/', '', $slug);

if (empty($slug)) {
    header('Location: /blog/');
    exit;
}

// ── Charger l'article depuis la BDD ──
$article = null;
$relatedArticles = [];

try {
    $pdo = getDB();
    
    // Récupérer l'article
    $stmt = $pdo->prepare("
        SELECT * FROM articles 
        WHERE slug = :slug AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($article) {
        // Incrémenter les vues
        $pdo->prepare("UPDATE articles SET views = COALESCE(views, 0) + 1 WHERE id = :id")
            ->execute([':id' => $article['id']]);
        
        // Articles similaires (même raison_vente, hors article courant)
        $stmtRelated = $pdo->prepare("
            SELECT id, titre, slug, extrait, featured_image, image, 
                   date_publication, published_at, temps_lecture, reading_time, raison_vente
            FROM articles 
            WHERE status = 'published' AND id != :id
            " . (!empty($article['raison_vente']) ? "AND raison_vente = :cat" : "") . "
            ORDER BY date_publication DESC
            LIMIT 3
        ");
        $params = [':id' => $article['id']];
        if (!empty($article['raison_vente'])) $params[':cat'] = $article['raison_vente'];
        $stmtRelated->execute($params);
        $relatedArticles = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Blog article error: " . $e->getMessage());
}

// ── 404 si pas trouvé ──
if (!$article) {
    http_response_code(404);
}

// ── Fonctions utilitaires ──
function formatDateFr($dateStr) {
    if (empty($dateStr)) return '';
    $mois = ['janvier','février','mars','avril','mai','juin',
             'juillet','août','septembre','octobre','novembre','décembre'];
    $ts = strtotime($dateStr);
    if ($ts === false) return '';
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function getArticleImage($a) {
    if (!empty($a['featured_image']) && strlen($a['featured_image']) < 500 && strpos($a['featured_image'], 'data:') === false) {
        return $a['featured_image'];
    }
    if (!empty($a['image']) && strlen($a['image']) < 500) {
        return $a['image'];
    }
    return 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=450&fit=crop';
}

function getReadingTime($a) {
    $t = (int)($a['temps_lecture'] ?? $a['reading_time'] ?? 0);
    if ($t > 0) return $t;
    // Estimer depuis le contenu
    $wordCount = str_word_count(strip_tags($a['contenu'] ?? ''));
    return max(1, round($wordCount / 200));
}

// ── Préparer les données ──
$pageTitle = $article 
    ? htmlspecialchars($article['meta_title'] ?? $article['titre'] ?? 'Article') . ' | Eduardo De Sul Immobilier'
    : 'Article non trouvé | Eduardo De Sul Immobilier';
$pageDesc = htmlspecialchars($article['meta_description'] ?? $article['extrait'] ?? '');
$pageImage = $article ? getArticleImage($article) : '';
$pageUrl = 'https://eduardo-desul-immobilier.fr/blog/' . htmlspecialchars($slug);

// ── Header/Footer ──
$headerFile = $root . '/front/includes/header.php';
$footerFile = $root . '/front/includes/footer.php';
if (!file_exists($headerFile)) $headerFile = $root . '/public/includes/header.php';
if (!file_exists($footerFile)) $footerFile = $root . '/public/includes/footer.php';

// ── CSS ──
$cssArticle = '';
if (file_exists($root . '/front/assets/css/blog-article.css')) {
    $cssArticle = '/front/assets/css/blog-article.css';
} elseif (file_exists($root . '/assets/css/blog-article.css')) {
    $cssArticle = '/assets/css/blog-article.css';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $pageDesc ?>">
    <link rel="canonical" href="<?= $pageUrl ?>">
    
    <?php if ($article && !empty($article['main_keyword'])): ?>
    <meta name="keywords" content="<?= htmlspecialchars($article['main_keyword']) ?>">
    <?php endif; ?>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($article['titre'] ?? 'Article') ?>">
    <meta property="og:description" content="<?= $pageDesc ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $pageUrl ?>">
    <?php if ($pageImage): ?>
    <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($article['titre'] ?? 'Article') ?>">
    <meta name="twitter:description" content="<?= $pageDesc ?>">
    
    <?php if ($article): ?>
    <!-- Schema.org BlogPosting -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": <?= json_encode($article['titre'] ?? '') ?>,
        "description": <?= json_encode($article['meta_description'] ?? $article['extrait'] ?? '') ?>,
        "url": <?= json_encode($pageUrl) ?>,
        <?php if ($pageImage): ?>"image": <?= json_encode($pageImage) ?>,<?php endif; ?>
        "datePublished": "<?= date('Y-m-d', strtotime($article['date_publication'] ?? $article['created_at'] ?? 'now')) ?>",
        "dateModified": "<?= !empty($article['updated_at']) ? date('Y-m-d', strtotime($article['updated_at'])) : date('Y-m-d') ?>",
        "author": {
            "@type": "Person",
            "name": "Eduardo De Sul",
            "jobTitle": "Conseiller immobilier indépendant"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Eduardo De Sul Immobilier",
            "url": "https://eduardo-desul-immobilier.fr"
        },
        "mainEntityOfPage": { "@type": "WebPage", "@id": <?= json_encode($pageUrl) ?> }
    }
    </script>
    
    <!-- BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://eduardo-desul-immobilier.fr/" },
            { "@type": "ListItem", "position": 2, "name": "Blog", "item": "https://eduardo-desul-immobilier.fr/blog/" },
            { "@type": "ListItem", "position": 3, "name": <?= json_encode($article['titre'] ?? '') ?> }
        ]
    }
    </script>
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <?php if ($cssArticle): ?>
    <link rel="stylesheet" href="<?= $cssArticle ?>">
    <?php endif; ?>
    
    <style>
    /* ═══ ARTICLE STYLES ═══ */
    .ba-article { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    
    /* Breadcrumb */
    .ba-breadcrumb { padding: 20px 0; font-size: 0.85rem; color: #94a3b8; }
    .ba-breadcrumb a { color: #64748b; text-decoration: none; }
    .ba-breadcrumb a:hover { color: #c8956c; }
    .ba-breadcrumb .sep { margin: 0 8px; }
    
    /* Header */
    .ba-header { text-align: center; padding: 20px 0 40px; }
    .ba-category { display: inline-block; padding: 6px 16px; background: #c8956c; color: white; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; }
    .ba-title { font-family: 'Playfair Display', serif; font-size: clamp(1.8rem, 4vw, 2.8rem); color: #1a202c; line-height: 1.3; margin: 0 0 20px; }
    .ba-meta { display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; color: #718096; font-size: 0.9rem; }
    .ba-meta svg { width: 16px; height: 16px; vertical-align: -2px; margin-right: 4px; }
    .ba-author-info { display: flex; align-items: center; gap: 10px; }
    .ba-author-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .ba-author-name { font-weight: 600; color: #1a202c; }
    
    /* Image principale */
    .ba-hero-image { margin: 0 -20px 40px; }
    .ba-hero-image img { width: 100%; max-height: 500px; object-fit: cover; border-radius: 16px; }
    
    /* Contenu */
    .ba-content { font-family: 'Source Sans 3', sans-serif; font-size: 1.1rem; line-height: 1.9; color: #374151; }
    .ba-content h2 { font-family: 'Playfair Display', serif; font-size: 1.6rem; margin: 2.5em 0 0.8em; color: #1a202c; padding-bottom: 8px; border-bottom: 2px solid #f0e6d9; }
    .ba-content h3 { font-size: 1.25rem; margin: 2em 0 0.6em; color: #2d3748; }
    .ba-content p { margin-bottom: 1.2em; }
    .ba-content img { max-width: 100%; height: auto; border-radius: 12px; margin: 1.5em 0; }
    .ba-content blockquote { border-left: 4px solid #c8956c; padding: 16px 24px; margin: 2em 0; background: #fdf8f3; border-radius: 0 12px 12px 0; font-style: italic; color: #78350f; }
    .ba-content ul, .ba-content ol { padding-left: 24px; margin-bottom: 1.2em; }
    .ba-content li { margin-bottom: 0.6em; line-height: 1.7; }
    .ba-content a { color: #c8956c; text-decoration: underline; }
    .ba-content a:hover { color: #a0724e; }
    .ba-content table { width: 100%; border-collapse: collapse; margin: 1.5em 0; }
    .ba-content th, .ba-content td { padding: 10px 14px; border: 1px solid #e5e7eb; text-align: left; }
    .ba-content th { background: #f8fafc; font-weight: 600; }
    
    /* Tags */
    .ba-tags { padding: 30px 0; border-top: 1px solid #e5e7eb; margin-top: 40px; }
    .ba-tag { display: inline-block; padding: 6px 14px; background: #f1f5f9; color: #475569; border-radius: 20px; font-size: 0.8rem; text-decoration: none; margin: 0 6px 6px 0; transition: all 0.2s; }
    .ba-tag:hover { background: #e2e8f0; color: #1e3a5f; }
    
    /* Share */
    .ba-share { display: flex; align-items: center; gap: 12px; padding: 20px 0; border-top: 1px solid #e5e7eb; }
    .ba-share-label { font-weight: 600; color: #1a202c; font-size: 0.9rem; }
    .ba-share a { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1px solid #e5e7eb; color: #64748b; text-decoration: none; transition: all 0.2s; }
    .ba-share a:hover { background: #f1f5f9; color: #1a202c; border-color: #cbd5e1; }
    .ba-share svg { width: 18px; height: 18px; }
    
    /* Articles similaires */
    .ba-related { padding: 60px 0; max-width: 900px; margin: 0 auto; padding-left: 20px; padding-right: 20px; }
    .ba-related h2 { font-family: 'Playfair Display', serif; font-size: 1.6rem; color: #1a202c; margin-bottom: 30px; text-align: center; }
    .ba-related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
    .ba-related-card { border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
    .ba-related-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .ba-related-card img { width: 100%; height: 180px; object-fit: cover; }
    .ba-related-card__body { padding: 16px; flex: 1; }
    .ba-related-card__date { font-size: 0.8rem; color: #94a3b8; }
    .ba-related-card__title { font-size: 1rem; margin-top: 6px; line-height: 1.4; color: #1a202c; font-weight: 600; }
    .ba-related-card__excerpt { font-size: 0.85rem; color: #64748b; margin-top: 8px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    
    /* CTA retour blog */
    .ba-back { text-align: center; padding: 40px 0 60px; }
    .ba-back a { display: inline-block; padding: 14px 32px; background: #c8956c; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
    .ba-back a:hover { background: #a0724e; }
    
    /* 404 */
    .ba-404 { text-align: center; padding: 80px 20px; max-width: 600px; margin: 0 auto; }
    .ba-404 h1 { font-family: 'Playfair Display', serif; font-size: 2rem; color: #1a202c; margin-bottom: 16px; }
    .ba-404 p { color: #64748b; margin-bottom: 32px; font-size: 1.1rem; }
    </style>
</head>
<body>

<?php if (file_exists($headerFile)) include $headerFile; ?>

<main>
<?php if (!$article): ?>
    
    <!-- ═══ 404 ═══ -->
    <div class="ba-404">
        <h1>Article non trouvé</h1>
        <p>Cet article n'existe pas ou a été retiré.</p>
        <a href="/blog/" style="display:inline-block;padding:14px 32px;background:#c8956c;color:white;border-radius:8px;text-decoration:none;font-weight:600;">← Retour au blog</a>
    </div>

<?php else: ?>

    <article class="ba-article">
        
        <!-- Breadcrumb -->
        <nav class="ba-breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span class="sep">›</span>
            <a href="/blog/">Blog</a>
            <span class="sep">›</span>
            <span><?= htmlspecialchars(mb_strimwidth($article['titre'], 0, 50, '...')) ?></span>
        </nav>
        
        <!-- Header article -->
        <header class="ba-header">
            <?php if (!empty($article['raison_vente'])): ?>
            <span class="ba-category"><?= htmlspecialchars($article['raison_vente']) ?></span>
            <?php endif; ?>
            
            <h1 class="ba-title"><?= htmlspecialchars($article['titre']) ?></h1>
            
            <div class="ba-meta">
                <div class="ba-author-info">
                    <img class="ba-author-avatar" src="/uploads/images/eduardo-avatar.jpg" alt="Eduardo De Sul" width="40" height="40">
                    <span class="ba-author-name"><?= htmlspecialchars($article['author'] ?? 'Eduardo De Sul') ?></span>
                </div>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?= formatDateFr($article['date_publication'] ?? $article['published_at'] ?? $article['created_at'] ?? '') ?>
                </span>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= getReadingTime($article) ?> min de lecture
                </span>
            </div>
        </header>
        
        <!-- Image principale -->
        <?php $heroImg = getArticleImage($article); ?>
        <div class="ba-hero-image">
            <img src="<?= htmlspecialchars($heroImg) ?>" 
                 alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['titre']) ?>" 
                 width="900" height="500" loading="eager">
        </div>
        
        <!-- Contenu -->
        <div class="ba-content">
            <?= $article['contenu'] ?? '' ?>
        </div>
        
        <!-- Tags -->
        <?php if (!empty($article['tags'])): ?>
        <div class="ba-tags">
            <?php 
            $tags = is_string($article['tags']) ? explode(',', $article['tags']) : [];
            foreach ($tags as $tag): 
                $tag = trim($tag);
                if ($tag): 
            ?>
            <a class="ba-tag" href="/blog/?q=<?= urlencode($tag) ?>"><?= htmlspecialchars($tag) ?></a>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Partager -->
        <div class="ba-share">
            <span class="ba-share-label">Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener" aria-label="Partager sur Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($article['titre'] ?? '') ?>" target="_blank" rel="noopener" aria-label="Partager sur X">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener" aria-label="Partager sur LinkedIn">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
            <a href="mailto:?subject=<?= rawurlencode($article['titre'] ?? '') ?>&body=<?= rawurlencode($pageUrl) ?>" aria-label="Envoyer par email">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </a>
            <a href="#" data-share="copy" aria-label="Copier le lien">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </a>
        </div>
        
    </article>
    
    <!-- Articles similaires -->
    <?php if (!empty($relatedArticles)): ?>
    <section class="ba-related">
        <h2>Articles similaires</h2>
        <div class="ba-related-grid">
            <?php foreach ($relatedArticles as $rel): 
                $relImg = getArticleImage($rel);
                $relDate = formatDateFr($rel['date_publication'] ?? $rel['published_at'] ?? '');
            ?>
            <a class="ba-related-card" href="/blog/<?= htmlspecialchars($rel['slug']) ?>">
                <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['titre']) ?>" width="400" height="180" loading="lazy">
                <div class="ba-related-card__body">
                    <span class="ba-related-card__date"><?= $relDate ?></span>
                    <h3 class="ba-related-card__title"><?= htmlspecialchars($rel['titre']) ?></h3>
                    <?php if (!empty($rel['extrait'])): ?>
                    <p class="ba-related-card__excerpt"><?= htmlspecialchars($rel['extrait']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Retour blog -->
    <div class="ba-back">
        <a href="/blog/">← Retour au blog</a>
    </div>

<?php endif; ?>
</main>

<?php if (file_exists($footerFile)) include $footerFile; ?>

<script>
// Copier le lien
document.querySelectorAll('[data-share="copy"]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        navigator.clipboard.writeText(window.location.href).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
            setTimeout(function() { btn.innerHTML = orig; }, 2000);
        });
    });
});

// Smooth scroll pour les ancres du sommaire
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        var target = document.getElementById(this.getAttribute('href').substring(1));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

</body>
</html>