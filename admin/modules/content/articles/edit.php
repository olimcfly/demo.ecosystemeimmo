<?php
/**
 * ============================================================
 *  MODULE ARTICLES — Éditeur v5.0
 *  Fichier : /admin/modules/content/articles/edit.php
 *
 *  Produit  : EcosystèmeImmo — Plateforme CRM & Marketing Immobilier
 *  Marque   : ecosystemeimmo.fr
 *  Auteur   : EcosystèmeImmo Dev Team
 *
 * ─────────────────────────────────────────────────────────────
 *  Chargé via require_once depuis index.php (routing action=edit/create)
 *  $pdo est déjà disponible depuis index.php
 *
 *  ✅ Architecture IA : /admin/api/ai/generate.php (AiDispatcher)
 *  ✅ Actions IA disponibles :
 *       articles.generate      → Article complet (titre, contenu, slug, métas)
 *       articles.improve       → Amélioration contenu existant
 *       articles.meta          → SEO title + meta description + slug
 *       articles.faq           → FAQ Schema.org (5 Q/R)
 *       articles.outline       → Plan éditorial + suggestions titres
 *       articles.keywords      → Extraction mots-clés SEO
 *       articles.rewrite       → Réécriture avec nouvel angle
 *  ✅ Boutons IA par champ (slug, extrait, seo_title, seo_description,
 *       meta_title, meta_description, focus_keyword, secondary_keywords)
 *  ✅ Aperçu SERP Google temps réel
 *  ✅ Score SEO live (calcul JS local)
 *  ✅ Quill.js éditeur riche
 *  ✅ Upload image + preview
 *  ✅ Double écriture colonnes FR/EN (statut/status, temps_lecture/reading_time...)
 *  ✅ CSRF protection
 *  ✅ Ctrl+S raccourci
 *  ✅ Double confirmation suppression
 *
 * ─────────────────────────────────────────────────────────────
 *  Colonnes DB supportées (détection dynamique SHOW COLUMNS) :
 *   titre, alt_titre, slug, category, extrait, contenu
 *   persona, raison_vente, ville, niveau_conscience, objectif, localite
 *   type_article, tags, focus_keyword, main_keyword, secondary_keywords
 *   seo_title, seo_description
 *   meta_title, meta_description, meta_keywords
 *   h1, noindex, canonical
 *   statut (FR) / status (EN)  — double écriture
 *   temps_lecture (FR) / reading_time (EN) — double écriture
 *   word_count
 *   score_technique, score_semantique (FR)
 *   seo_score, semantic_score (EN)
 *   featured_image, featured_image_alt
 *   section_motivation, section_explication, section_recette, section_exercice
 *   faq, author, date_publication
 * ============================================================
 */

// ─── $pdo hérité de index.php ─────────────────────────────────────────────────
if (!isset($pdo)) {
    $cfgPaths = [
        __DIR__ . '/../../../config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/config.php',
    ];
    foreach ($cfgPaths as $p) { if (file_exists($p)) { require_once $p; break; } }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Exception $e) {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ DB: '
             . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ─── Paramètres ───────────────────────────────────────────────────────────────
$action  = $_GET['action'] ?? 'edit';
$id      = (int)($_GET['id'] ?? 0);
$error   = '';
$message = '';

// Messages flash
if (isset($_GET['msg'])) {
    $msgs = [
        'saved'   => '✅ Article enregistré avec succès.',
        'created' => '✅ Article créé avec succès.',
        'deleted' => '✅ Article supprimé.',
    ];
    $message = $msgs[$_GET['msg']] ?? '';
}

// JS redirect (utilisé à l'intérieur du dashboard)
function jsRedirectEdit(string $url): never {
    echo '<script>window.location.href="' . addslashes($url) . '";</script>';
    exit;
}

// ─── Colonnes réelles ─────────────────────────────────────────────────────────
$cols = [];
try {
    $cols = $pdo->query("SHOW COLUMNS FROM articles")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable) {}
$has = fn(string $c): bool => in_array($c, $cols);

// ─── Disponibilité IA ─────────────────────────────────────────────────────────
$aiAvailable = (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY))
            || (defined('OPENAI_API_KEY')    && !empty(OPENAI_API_KEY));
$aiProvider  = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) $aiProvider = 'Claude';
elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY))   $aiProvider = 'OpenAI';

// Point d'entrée IA unifié
$AI_ENDPOINT = '/admin/api/ai/generate.php';

// ══════════════════════════════════════════════════════════════
//  SUPPRESSION
// ══════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    $tok = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $tok)) {
        jsRedirectEdit('?page=articles&msg=csrf_error');
    }
    try {
        $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
        jsRedirectEdit('?page=articles&msg=deleted');
    } catch (Throwable $e) {
        $error = 'Erreur suppression : ' . $e->getMessage();
    }
}

// ══════════════════════════════════════════════════════════════
//  SAUVEGARDE POST
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['edit', 'create'])) {

    // CSRF
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        try {
            // ── Champs texte ──────────────────────────────────────────────────
            $titre           = trim($_POST['titre']           ?? '');
            $slug            = trim($_POST['slug']            ?? '');
            $extrait         = trim($_POST['extrait']         ?? '');
            $contenu         = $_POST['contenu']              ?? '';

            // ── Statut : double écriture FR + EN ─────────────────────────────
            $statusEN = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived'])
                        ? $_POST['status'] : 'draft';
            $statutFR = $statusEN === 'published' ? 'publie' : 'brouillon';

            // ── SEO principal ─────────────────────────────────────────────────
            $seo_title         = trim($_POST['seo_title']         ?? '');
            $seo_description   = trim($_POST['seo_description']   ?? '');

            // ── Méta secondaires ──────────────────────────────────────────────
            $meta_title        = trim($_POST['meta_title']        ?? '');
            $meta_description  = trim($_POST['meta_description']  ?? '');
            $meta_keywords     = trim($_POST['meta_keywords']     ?? '');

            // ── Mots-clés ─────────────────────────────────────────────────────
            $focus_keyword      = trim($_POST['focus_keyword']      ?? '');
            $main_keyword       = trim($_POST['main_keyword']       ?? '');
            $secondary_keywords = trim($_POST['secondary_keywords'] ?? '');

            // ── Autres champs ─────────────────────────────────────────────────
            $ville              = trim($_POST['ville']              ?? '');
            $raison_vente       = trim($_POST['raison_vente']       ?? '');
            $persona            = trim($_POST['persona']            ?? '');
            $type_article       = trim($_POST['type_article']       ?? '');
            $category           = trim($_POST['category']           ?? '');
            $featured_image     = trim($_POST['featured_image']     ?? '');
            $featured_image_alt = trim($_POST['featured_image_alt'] ?? '');
            $h1                 = trim($_POST['h1']                 ?? '');
            $alt_titre          = trim($_POST['alt_titre']          ?? '');
            $author             = trim($_POST['author']             ?? '');
            $noindex            = isset($_POST['noindex']) ? 1 : 0;
            $niveau_conscience  = trim($_POST['niveau_conscience']  ?? '');
            $localite           = trim($_POST['localite']           ?? '');
            $section_motivation  = $_POST['section_motivation']  ?? '';
            $section_explication = $_POST['section_explication'] ?? '';
            $section_recette     = $_POST['section_recette']     ?? '';
            $section_exercice    = $_POST['section_exercice']    ?? '';

            // ── Métriques ─────────────────────────────────────────────────────
            $wordCount   = str_word_count(strip_tags($contenu));
            $readingTime = max(1, (int)ceil($wordCount / 200));

            // ── Auto-slug ─────────────────────────────────────────────────────
            if (empty($slug) && !empty($titre)) {
                $stopWords = ['le','la','les','de','du','des','un','une','en','et','ou','a','au','aux',
                              'ce','cette','ces','son','sa','ses','mon','ma','mes','pour','par','sur',
                              'avec','dans','qui','que','quoi','dont','est','sont','peut','faire','plus',
                              'moins','tout','tous','ne','pas','se','si','nous','vous','ils','elles','leur'];
                $s = mb_strtolower($titre);
                $s = strtr($s, ['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                                 'î'=>'i','ï'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
                                 'æ'=>'ae','œ'=>'oe']);
                $s = preg_replace('/[^a-z0-9\s]/u', '', $s);
                $words = array_filter(explode(' ', $s), fn($w) => $w && !in_array($w, $stopWords));
                $slug  = implode('-', array_slice(array_values($words), 0, 6));
            }

            if (empty($titre)) throw new Exception('Le titre est obligatoire.');

            // ── Carte complète colonnes → valeurs ────────────────────────────
            $colMap = [
                'titre'               => $titre,
                'alt_titre'           => $alt_titre,
                'slug'                => $slug,
                'extrait'             => $extrait,
                'contenu'             => $contenu,
                'h1'                  => $h1,
                'statut'              => $statutFR,
                'status'              => $statusEN,
                'seo_title'           => $seo_title,
                'seo_description'     => $seo_description,
                'meta_title'          => $meta_title,
                'meta_description'    => $meta_description,
                'meta_keywords'       => $meta_keywords,
                'focus_keyword'       => $focus_keyword,
                'main_keyword'        => $main_keyword,
                'secondary_keywords'  => $secondary_keywords,
                'ville'               => $ville,
                'raison_vente'        => $raison_vente,
                'persona'             => $persona,
                'type_article'        => $type_article,
                'category'            => $category,
                'niveau_conscience'   => $niveau_conscience,
                'localite'            => $localite,
                'featured_image'      => $featured_image,
                'featured_image_alt'  => $featured_image_alt,
                'author'              => $author,
                'noindex'             => $noindex,
                'section_motivation'  => $section_motivation,
                'section_explication' => $section_explication,
                'section_recette'     => $section_recette,
                'section_exercice'    => $section_exercice,
                'word_count'          => $wordCount,
                'reading_time'        => $readingTime,
                'temps_lecture'       => $readingTime,
            ];

            // Filtrer sur les colonnes réellement existantes
            $safeMap = array_filter($colMap, fn($col) => $has($col), ARRAY_FILTER_USE_KEY);

            if ($action === 'create') {
                $fields = array_keys($safeMap);
                $sql = 'INSERT INTO articles ('
                     . implode(', ', array_map(fn($c) => "`{$c}`", $fields))
                     . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
                $pdo->prepare($sql)->execute(array_values($safeMap));
                $newId = (int)$pdo->lastInsertId();
                jsRedirectEdit('?page=articles&action=edit&id=' . $newId . '&msg=created');
            } else {
                $sets   = array_map(fn($c) => "`{$c}` = ?", array_keys($safeMap));
                $values = array_values($safeMap);
                $values[] = $id;
                $pdo->prepare('UPDATE articles SET ' . implode(', ', $sets) . ' WHERE id = ?')
                    ->execute($values);
                jsRedirectEdit('?page=articles&action=edit&id=' . $id . '&msg=saved');
            }

        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// ══════════════════════════════════════════════════════════════
//  CHARGER L'ARTICLE
// ══════════════════════════════════════════════════════════════
$article = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    if (!$article) {
        echo '<div style="padding:60px;text-align:center;">
              <div style="font-size:48px;margin-bottom:16px;">🔍</div>
              <h3 style="color:#0f172a;">Article introuvable (ID: ' . $id . ')</h3>
              <a href="?page=articles" style="color:#6366f1;font-weight:600;text-decoration:none;">
              <i class="fas fa-arrow-left"></i> Retour à la liste</a></div>';
        return;
    }
}

// ─── Normalisation statut ─────────────────────────────────────────────────────
$currentStatus = 'draft';
if (!empty($article['status'])  && $article['status']  === 'published') $currentStatus = 'published';
elseif (!empty($article['statut']) && $article['statut'] === 'publie')  $currentStatus = 'published';

// ─── Scores (FR + EN) ─────────────────────────────────────────────────────────
$seoScore = (int)($article['seo_score']      ?? $article['score_technique']  ?? 0);
$semScore = (int)($article['semantic_score'] ?? $article['score_semantique'] ?? 0);
$readingTimeVal = (int)($article['reading_time'] ?? $article['temps_lecture'] ?? 0);

// ─── Escape helper ────────────────────────────────────────────────────────────
$e = fn(string $k, string $d = '') => htmlspecialchars((string)($article[$k] ?? $d));

// ─── SERP : priorité seo_title > meta_title > titre ──────────────────────────
$serpTitleInit = $article['seo_title'] ?? $article['meta_title'] ?? $article['titre'] ?? '';
$serpDescInit  = $article['seo_description'] ?? $article['meta_description'] ?? $article['extrait'] ?? '';

$isEdit    = ($action === 'edit' && $article !== null);
$pageTitle = $isEdit ? 'Modifier l\'article' : 'Nouvel article';
$seoClass  = $seoScore >= 80 ? 'excellent' : ($seoScore >= 60 ? 'good' : ($seoScore >= 40 ? 'warning' : 'bad'));
?>

<!-- ══════════════════════════════════════════════════════════
     QUILL CSS
══════════════════════════════════════════════════════════ -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════════
   ECOSYSTÈMEIMMO — Article Editor v5.0
   Variables CSS cohérentes avec le système admin global
════════════════════════════════════════════════════════════ */
:root {
    --ei-primary:      #6366f1;
    --ei-primary-dark: #4f46e5;
    --ei-success:      #10b981;
    --ei-warning:      #f59e0b;
    --ei-danger:       #ef4444;
    --ei-ai:           #8b5cf6;
    --ei-ai-dark:      #7c3aed;
    --ei-ai-light:     #ede9fe;
    --ei-ai-border:    #c4b5fd;
    --bg-card:   #ffffff;
    --bg-page:   #f8fafc;
    --border:    #e2e8f0;
    --text-1:    #0f172a;
    --text-2:    #374151;
    --text-3:    #94a3b8;
    --radius:    14px;
    --radius-sm: 10px;
    --shadow:    0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    --shadow-lg: 0 10px 40px rgba(0,0,0,.14);
}

/* ─── Layout ─────────────────────────────────────────────── */
.ae5 { font-family: 'Inter', -apple-system, sans-serif; color: var(--text-1); }

/* Header */
.ae5-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.ae5-header h2 {
    font-size: 20px; font-weight: 700; color: var(--text-1);
    display: flex; align-items: center; gap: 10px; margin: 0;
}
.ae5-header h2 .id-tag { font-size: 13px; color: var(--text-3); font-weight: 400; }
.ae5-btns { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

/* Boutons header */
.ae5-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border: none; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all .2s; text-decoration: none; white-space: nowrap; font-family: inherit;
}
.ae5-btn-ghost {
    background: var(--bg-card); color: #64748b;
    border: 1px solid var(--border);
}
.ae5-btn-ghost:hover { border-color: var(--ei-primary); color: var(--ei-primary); }
.ae5-btn-draft   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.ae5-btn-draft:hover   { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.ae5-btn-publish { background: var(--ei-success); color: #fff; }
.ae5-btn-publish:hover { background: #059669; box-shadow: 0 4px 12px rgba(16,185,129,.35); }
.ae5-btn-preview { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.ae5-btn-preview:hover { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }

/* Messages */
.ae5-msg {
    padding: 13px 18px; border-radius: var(--radius-sm);
    margin-bottom: 20px; font-size: 14px; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
}
.ae5-msg.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.ae5-msg.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* Grid 2 colonnes */
.ae5-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1140px) { .ae5-grid { grid-template-columns: 1fr; } }

/* Cards */
.ae5-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.ae5-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    background: #fafbfc;
    display: flex; align-items: center; justify-content: space-between;
}
.ae5-card-title {
    font-size: 13px; font-weight: 700; color: var(--text-1);
    display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: .04em;
}
.ae5-card-title i { color: var(--ei-primary); }
.ae5-card-body { padding: 20px; }

/* Champs */
.ae5-field { margin-bottom: 16px; }
.ae5-field:last-child { margin-bottom: 0; }
.ae5-label {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 6px;
}
.ae5-label-text {
    font-size: 12px; font-weight: 600; color: #374151;
    display: flex; align-items: center; gap: 6px;
    text-transform: uppercase; letter-spacing: .04em;
}
.ae5-label-text i { color: var(--ei-primary); font-size: 11px; }
.ae5-char-count {
    font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
    color: var(--text-3); background: var(--bg-page);
}
.ae5-char-count.ok   { color: #059669; background: #d1fae5; }
.ae5-char-count.warn { color: #d97706; background: #fef3c7; }
.ae5-char-count.err  { color: #dc2626; background: #fee2e2; }

.ae5-input, .ae5-select, .ae5-textarea {
    width: 100%; padding: 10px 14px;
    border: 1px solid var(--border); border-radius: var(--radius-sm);
    font-size: 14px; color: var(--text-1); background: var(--bg-card);
    transition: border .15s, box-shadow .15s; box-sizing: border-box;
    font-family: inherit; outline: none;
}
.ae5-input:focus, .ae5-select:focus, .ae5-textarea:focus {
    border-color: var(--ei-primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.ae5-input-xl { font-size: 18px; font-weight: 600; padding: 13px 16px; }
.ae5-textarea { resize: vertical; line-height: 1.6; }

/* Input + bouton IA en ligne */
.ae5-input-group { display: flex; gap: 8px; align-items: flex-start; }
.ae5-input-group .ae5-input,
.ae5-input-group .ae5-textarea { flex: 1; min-width: 0; }

/* ─── Boutons IA inline ────────────────────────────────────── */
.ae5-ai-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 12px; border: none; border-radius: 8px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    transition: all .2s; white-space: nowrap; flex-shrink: 0;
    font-family: inherit; line-height: 1;
}
.ae5-ai-btn-violet {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: #fff;
}
.ae5-ai-btn-violet:hover  { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(139,92,246,.4); }
.ae5-ai-btn-violet:disabled { opacity: .5; cursor: wait; transform: none; box-shadow: none; }
.ae5-ai-btn-cyan {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: #fff;
}
.ae5-ai-btn-cyan:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(6,182,212,.4); }

/* Slug preview */
.ae5-slug-hint { font-size: 11px; color: var(--text-3); margin-top: 5px; }
.ae5-slug-hint strong { color: var(--ei-primary); }

/* Onglets SEO */
.ae5-seo-tabs {
    display: flex; gap: 3px; background: var(--bg-page);
    border: 1px solid var(--border); border-radius: 10px;
    padding: 3px; margin-bottom: 20px;
}
.ae5-seo-tab {
    flex: 1; padding: 8px 10px; border: none; background: transparent;
    border-radius: 8px; font-size: 12px; font-weight: 600; color: #64748b;
    cursor: pointer; transition: all .2s; font-family: inherit;
}
.ae5-seo-tab.active {
    background: var(--bg-card); color: var(--text-1);
    box-shadow: 0 1px 4px rgba(0,0,0,.09);
}
.ae5-seo-panel { display: none; }
.ae5-seo-panel.active { display: block; }

/* SERP preview */
.ae5-serp {
    background: #f8fafc; border: 1px solid var(--border);
    border-radius: 10px; padding: 16px; margin-top: 16px;
}
.ae5-serp-label { font-size: 11px; color: var(--text-3); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; }
.ae5-serp-title { color: #1a0dab; font-size: 18px; font-weight: 400; margin-bottom: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: Arial, sans-serif; }
.ae5-serp-url   { color: #006621; font-size: 13px; margin-bottom: 3px; font-family: Arial, sans-serif; }
.ae5-serp-desc  { color: #545454; font-size: 13px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-family: Arial, sans-serif; }

/* Quill */
.ae5-quill-wrap { border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
.ae5-quill-wrap .ql-toolbar   { border: none !important; border-bottom: 1px solid var(--border) !important; background: #fafbfc; padding: 10px 14px; }
.ae5-quill-wrap .ql-container { border: none !important; font-size: 15px; }
.ae5-quill-wrap .ql-editor    { min-height: 380px; padding: 20px; line-height: 1.75; }
.ae5-quill-wrap.focused       { border-color: var(--ei-primary); box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.ae5-wordstats {
    display: flex; gap: 18px; padding: 10px 14px;
    border-top: 1px solid var(--border); background: #fafbfc;
    font-size: 12px; color: var(--text-3);
}
.ae5-wordstats strong { color: var(--text-1); }

/* ─── PANNEAU IA PRINCIPAL ──────────────────────────────── */
.ae5-ai-panel {
    border-color: var(--ei-ai-border);
    background: linear-gradient(135deg, #faf5ff, #ede9fe08);
}
.ae5-ai-panel .ae5-card-header {
    background: var(--ei-ai-light);
    border-color: var(--ei-ai-border);
}
.ae5-ai-panel .ae5-card-title,
.ae5-ai-panel .ae5-card-title i { color: var(--ei-ai); }

.ae5-ai-subject { margin-bottom: 12px; }
.ae5-ai-params {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
    margin-bottom: 14px;
}
.ae5-ai-params .ae5-select,
.ae5-ai-params .ae5-input  { font-size: 12px; padding: 8px 10px; }

.ae5-ai-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ae5-ai-action {
    display: flex; align-items: center; gap: 9px;
    padding: 11px 14px; border-radius: 10px;
    background: var(--bg-card); color: var(--text-1);
    border: 1px solid var(--ei-ai-border);
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all .18s; font-family: inherit; text-align: left;
}
.ae5-ai-action:hover {
    background: var(--ei-ai); color: #fff;
    border-color: var(--ei-ai);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139,92,246,.2);
}
.ae5-ai-action.full { grid-column: 1 / -1; justify-content: center; }
.ae5-ai-action.primary {
    background: var(--ei-ai); color: #fff;
    border-color: var(--ei-ai); grid-column: 1 / -1;
    justify-content: center; padding: 13px;
}
.ae5-ai-action.primary:hover { background: var(--ei-ai-dark); }
.ae5-ai-action i { font-size: 14px; width: 18px; text-align: center; flex-shrink: 0; }

/* ─── Sidebar ───────────────────────────────────────────── */
.ae5-side-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); box-shadow: var(--shadow);
    overflow: hidden; margin-bottom: 16px;
}
.ae5-side-header {
    padding: 13px 18px; border-bottom: 1px solid var(--border);
    background: #fafbfc;
}
.ae5-side-title {
    font-size: 12px; font-weight: 700; color: var(--text-1);
    display: flex; align-items: center; gap: 7px;
    text-transform: uppercase; letter-spacing: .04em;
}
.ae5-side-title i { color: var(--ei-primary); }
.ae5-side-body { padding: 16px 18px; }

/* Status radios */
.ae5-status-opts { display: flex; gap: 10px; }
.ae5-status-opt { flex: 1; }
.ae5-status-opt input { display: none; }
.ae5-status-opt label {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px; border: 2px solid var(--border); border-radius: 10px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s;
}
.ae5-status-opt input:checked + .lbl-draft    { border-color: var(--ei-warning); background: #fffbeb; color: #92400e; }
.ae5-status-opt input:checked + .lbl-published { border-color: var(--ei-success); background: #ecfdf5; color: #065f46; }

/* Score circle */
.ae5-score-visual { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
.ae5-score-circle {
    width: 60px; height: 60px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 800; color: #fff; flex-shrink: 0;
}
.ae5-score-circle.excellent { background: linear-gradient(135deg,#10b981,#059669); }
.ae5-score-circle.good      { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.ae5-score-circle.warning   { background: linear-gradient(135deg,#f59e0b,#d97706); }
.ae5-score-circle.bad       { background: linear-gradient(135deg,#ef4444,#dc2626); }
.ae5-score-text { font-size: 13px; font-weight: 600; color: var(--text-2); }
.ae5-score-hint { font-size: 11px; color: var(--text-3); margin-top: 2px; }

/* Score bars */
.ae5-score-bars { display: flex; flex-direction: column; gap: 7px; }
.ae5-score-row { display: flex; flex-direction: column; gap: 2px; }
.ae5-score-row-label { display: flex; justify-content: space-between; font-size: 11px; }
.ae5-score-row-label span:first-child { color: var(--text-2); }
.ae5-score-row-label span:last-child  { font-weight: 700; }
.ae5-bar { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
.ae5-bar-fill { height: 100%; border-radius: 3px; transition: width .4s ease, background .4s; }

/* Stats mini */
.ae5-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
.ae5-stat { background: var(--bg-page); border-radius: 10px; padding: 10px; text-align: center; }
.ae5-stat-val { font-size: 18px; font-weight: 800; color: var(--text-1); }
.ae5-stat-lbl { font-size: 10px; color: var(--text-3); margin-top: 2px; }

/* Quick actions */
.ae5-quick { display: flex; flex-direction: column; gap: 7px; }
.ae5-quick-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    background: var(--bg-page); border: 1px solid var(--border);
    border-radius: 10px; text-decoration: none; color: var(--text-2);
    font-size: 13px; font-weight: 500; transition: all .2s;
}
.ae5-quick-item:hover { background: #eef2ff; border-color: #c7d2fe; color: var(--ei-primary); }
.ae5-quick-item i { color: var(--ei-primary); width: 18px; text-align: center; }

/* Image zone */
.ae5-img-zone {
    width: 100%; min-height: 160px; border: 2px dashed var(--border);
    border-radius: 12px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; cursor: pointer;
    transition: all .2s; position: relative; overflow: hidden;
    background: var(--bg-page);
}
.ae5-img-zone:hover { border-color: var(--ei-primary); background: #eef2ff; }
.ae5-img-zone.has-img { border-style: solid; border-color: var(--border); }
.ae5-img-zone img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ae5-img-placeholder { text-align: center; color: var(--text-3); font-size: 13px; }
.ae5-img-placeholder i { font-size: 28px; margin-bottom: 8px; display: block; opacity: .5; }
.ae5-img-remove {
    position: absolute; top: 8px; right: 8px; width: 28px; height: 28px;
    border-radius: 50%; background: rgba(239,68,68,.9); color: #fff;
    border: none; cursor: pointer; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.ae5-img-remove:hover { background: #dc2626; transform: scale(1.1); }

/* Sidebar fields */
.ae5-sf { margin-bottom: 12px; }
.ae5-sf label { display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }

/* Danger zone */
.ae5-danger {
    border-color: #fca5a5; background: #fff5f5;
}
.ae5-danger .ae5-side-header { background: #fff1f1; border-color: #fca5a5; }
.ae5-danger .ae5-side-title,
.ae5-danger .ae5-side-title i { color: #dc2626; }
.ae5-btn-delete {
    width: 100%; padding: 11px; background: var(--bg-card);
    border: 1px solid #fca5a5; border-radius: 10px;
    color: #dc2626; font-weight: 600; font-size: 13px; cursor: pointer;
    transition: all .2s; display: flex; align-items: center; justify-content: center;
    gap: 8px; font-family: inherit;
}
.ae5-btn-delete:hover { background: var(--ei-danger); color: #fff; border-color: var(--ei-danger); }

/* ─── Modale IA ─────────────────────────────────────────── */
.ae5-modal {
    display: none; position: fixed; inset: 0; z-index: 10000;
    background: rgba(15,23,42,.65); backdrop-filter: blur(4px);
    align-items: center; justify-content: center; padding: 24px;
}
.ae5-modal.open { display: flex; animation: ae5FadeIn .22s ease; }
.ae5-modal-box {
    background: var(--bg-card); border-radius: 16px;
    box-shadow: var(--shadow-lg);
    width: 100%; max-width: 720px; max-height: 88vh;
    display: flex; flex-direction: column; overflow: hidden;
}
.ae5-modal-hdr {
    padding: 18px 24px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    background: var(--ei-ai-light); flex-shrink: 0;
}
.ae5-modal-hdr h3 { font-size: 15px; font-weight: 700; color: var(--ei-ai); display: flex; align-items: center; gap: 8px; margin: 0; }
.ae5-modal-close {
    width: 32px; height: 32px; border-radius: 8px;
    background: none; border: 1px solid var(--border);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    color: var(--text-3); transition: all .15s;
}
.ae5-modal-close:hover { background: var(--ei-danger); color: #fff; border-color: var(--ei-danger); }
.ae5-modal-body { padding: 24px; overflow-y: auto; flex: 1; }
.ae5-modal-ftr {
    padding: 14px 24px; border-top: 1px solid var(--border);
    display: flex; justify-content: flex-end; gap: 10px;
    background: #fafbfc; flex-shrink: 0;
}

/* Loader / Result */
.ae5-loader { text-align: center; padding: 40px 20px; color: var(--text-3); }
.ae5-spinner {
    width: 44px; height: 44px; border: 3px solid var(--border);
    border-top-color: var(--ei-ai); border-radius: 50%;
    animation: ae5Spin .7s linear infinite; margin: 0 auto 16px;
}
.ae5-loader p { font-size: 14px; }
.ae5-result { display: none; }
.ae5-result-text {
    font-size: 13px; line-height: 1.7; color: var(--text-1);
    background: var(--bg-page); border: 1px solid var(--border);
    border-radius: 10px; padding: 16px 18px; max-height: 340px;
    overflow-y: auto; white-space: pre-wrap;
}

/* Toast */
.ae5-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px; border-radius: 12px;
    box-shadow: var(--shadow-lg); font-size: 14px; font-weight: 500;
    color: #fff; opacity: 0; transform: translateY(16px);
    transition: all .3s cubic-bezier(.34,1.56,.64,1);
    pointer-events: none; max-width: 380px;
    font-family: 'Inter', -apple-system, sans-serif;
}
.ae5-toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.ae5-toast.success { background: var(--ei-success); }
.ae5-toast.error   { background: var(--ei-danger); }
.ae5-toast.ai      { background: var(--ei-ai); }
.ae5-toast.warn    { background: var(--ei-warning); }

@keyframes ae5Spin    { to { transform: rotate(360deg); } }
@keyframes ae5FadeIn  { from { opacity: 0; transform: scale(.97); } to { opacity: 1; transform: scale(1); } }
@keyframes ae5SpinInl { to { transform: rotate(360deg); } }
.ae5-spin { display: inline-block; animation: ae5SpinInl .8s linear infinite; }
</style>

<!-- Toast -->
<div class="ae5-toast" id="ae5Toast"><i class="fas fa-check-circle" id="ae5ToastIco"></i><span id="ae5ToastMsg"></span></div>

<!-- ═══ MODALE IA ══════════════════════════════════════════════════════════ -->
<div class="ae5-modal" id="ae5Modal">
    <div class="ae5-modal-box">
        <div class="ae5-modal-hdr">
            <h3><i class="fas fa-robot"></i> <span id="ae5ModalTitle">Assistant IA</span></h3>
            <button class="ae5-modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="ae5-modal-body">
            <div class="ae5-loader" id="ae5ModalLoader">
                <div class="ae5-spinner"></div>
                <p id="ae5ModalLoaderTxt">Génération en cours…</p>
            </div>
            <div class="ae5-result" id="ae5ModalResult">
                <div class="ae5-result-text" id="ae5ModalResultTxt"></div>
            </div>
        </div>
        <div class="ae5-modal-ftr">
            <button class="ae5-btn ae5-btn-ghost" onclick="closeModal()">Fermer</button>
            <button class="ae5-btn ae5-btn-publish" id="ae5ModalApply" onclick="applyModal()" style="display:none">
                <i class="fas fa-check"></i> Appliquer
            </button>
        </div>
    </div>
</div>

<!-- ═══ ÉDITEUR ═══════════════════════════════════════════════════════════ -->
<div class="ae5">

<!-- Header -->
<div class="ae5-header">
    <h2>
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i>
        <?= htmlspecialchars($pageTitle) ?>
        <?php if ($isEdit): ?><span class="id-tag">#<?= $id ?></span><?php endif; ?>
    </h2>
    <div class="ae5-btns">
        <a href="?page=articles" class="ae5-btn ae5-btn-ghost">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
        <?php if ($isEdit && !empty($article['slug'])): ?>
        <a href="/blog/<?= $e('slug') ?>" target="_blank" class="ae5-btn ae5-btn-preview">
            <i class="fas fa-external-link-alt"></i> Voir
        </a>
        <?php endif; ?>
        <button type="button" class="ae5-btn ae5-btn-draft"   onclick="saveArticle('draft')">
            <i class="fas fa-save"></i> Brouillon
        </button>
        <button type="button" class="ae5-btn ae5-btn-publish" onclick="saveArticle('published')">
            <i class="fas fa-check"></i> Publier
        </button>
    </div>
</div>

<!-- Messages -->
<?php if ($message): ?>
<div class="ae5-msg success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="ae5-msg error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Formulaire -->
<form id="ae5Form" method="POST"
      action="?page=articles&action=<?= $action ?><?= $isEdit ? '&id='.$id : '' ?>">

    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="status"     id="ae5Status" value="<?= $currentStatus ?>">
    <input type="hidden" name="contenu"    id="ae5Contenu">

<div class="ae5-grid">

<!-- ════════════════════════════════════════════════════════════
     COLONNE PRINCIPALE
════════════════════════════════════════════════════════════ -->
<div>

    <!-- Titre -->
    <div class="ae5-card">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-heading"></i> Titre de l'article</span>
        </div>
        <div class="ae5-card-body">
            <div class="ae5-field">
                <input type="text" name="titre" id="ae5Titre" class="ae5-input ae5-input-xl"
                       value="<?= $e('titre') ?>"
                       placeholder="Titre accrocheur et optimisé SEO…"
                       required>
            </div>
        </div>
    </div>

    <!-- Slug -->
    <div class="ae5-card">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-link"></i> Slug URL</span>
        </div>
        <div class="ae5-card-body">
            <div class="ae5-field">
                <div class="ae5-input-group">
                    <input type="text" name="slug" id="ae5Slug" class="ae5-input"
                           value="<?= $e('slug') ?>"
                           placeholder="url-de-votre-article">
                    <button type="button" class="ae5-ai-btn ae5-ai-btn-cyan"
                            onclick="genSlugLocal()" title="Générer depuis le titre">
                        <i class="fas fa-sync-alt"></i> Auto
                    </button>
                    <?php if ($aiAvailable): ?>
                    <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                            onclick="aiField('slug')"
                            data-field="slug">
                        <i class="fas fa-robot"></i> IA
                    </button>
                    <?php endif; ?>
                </div>
                <div class="ae5-slug-hint">
                    votresite.fr/blog/<strong id="ae5SlugPreview"><?= $e('slug', '…') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Quill -->
    <div class="ae5-card">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-pen-nib"></i> Contenu</span>
            <div style="display:flex;gap:8px;">
                <?php if ($aiAvailable): ?>
                <button type="button" class="ae5-ai-btn ae5-ai-btn-cyan"
                        onclick="aiImprove()" style="font-size:11px;">
                    <i class="fas fa-sparkles"></i> Améliorer
                </button>
                <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                        onclick="aiGenerate()" style="font-size:11px;">
                    <i class="fas fa-magic"></i> Générer
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="ae5-card-body" style="padding:0;">
            <div class="ae5-quill-wrap" id="ae5QuillWrap">
                <div id="ae5QuillEditor"><?= $article['contenu'] ?? '' ?></div>
            </div>
            <div class="ae5-wordstats">
                <span>Mots : <strong id="ae5Words">0</strong></span>
                <span>Lecture : <strong id="ae5ReadTime">1 min</strong></span>
                <span>Caractères : <strong id="ae5Chars">0</strong></span>
                <span style="margin-left:auto;font-weight:700;" id="ae5ContentScore"></span>
            </div>
        </div>
    </div>

    <!-- Extrait -->
    <div class="ae5-card">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-quote-right"></i> Extrait</span>
            <?php if ($aiAvailable): ?>
            <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                    onclick="aiField('extrait')" data-field="extrait"
                    style="font-size:11px;">
                <i class="fas fa-robot"></i> Générer
            </button>
            <?php endif; ?>
        </div>
        <div class="ae5-card-body">
            <div class="ae5-field">
                <div class="ae5-label">
                    <span class="ae5-label-text"><i class="fas fa-align-left"></i> Résumé accrocheur</span>
                    <span class="ae5-char-count" id="ae5ExtraitCount">0/280</span>
                </div>
                <textarea name="extrait" id="ae5Extrait" class="ae5-textarea" rows="3"
                          placeholder="Résumé qui donne envie de lire — affiché dans les listes et les réseaux…"><?= $e('extrait') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════
         SEO — 3 onglets
    ════════════════════════════════════ -->
    <div class="ae5-card">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-search"></i> Optimisation SEO</span>
            <?php if ($aiAvailable): ?>
            <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                    onclick="aiMeta()" style="font-size:11px;">
                <i class="fas fa-magic"></i> Générer métas
            </button>
            <?php endif; ?>
        </div>
        <div class="ae5-card-body">

            <div class="ae5-seo-tabs">
                <button type="button" class="ae5-seo-tab active" onclick="seoTab('primary',this)">🎯 SEO Principal</button>
                <button type="button" class="ae5-seo-tab" onclick="seoTab('meta',this)">📋 Méta secondaires</button>
                <button type="button" class="ae5-seo-tab" onclick="seoTab('keywords',this)">🔑 Mots-clés</button>
            </div>

            <!-- Onglet 1 : SEO Principal -->
            <div class="ae5-seo-panel active" id="ae5SeoTab-primary">
                <div class="ae5-field">
                    <div class="ae5-label">
                        <span class="ae5-label-text"><i class="fas fa-tag"></i> SEO Title <small style="font-weight:400;text-transform:none;">(balise &lt;title&gt;)</small></span>
                        <span class="ae5-char-count" id="ae5SeoTitleCount">0/70</span>
                    </div>
                    <div class="ae5-input-group">
                        <input type="text" name="seo_title" id="ae5SeoTitle" class="ae5-input"
                               value="<?= $e('seo_title') ?>"
                               placeholder="Titre SEO optimisé (50-60 car.)">
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('seo_title')" data-field="seo_title">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ae5-field">
                    <div class="ae5-label">
                        <span class="ae5-label-text"><i class="fas fa-align-right"></i> SEO Description</span>
                        <span class="ae5-char-count" id="ae5SeoDescCount">0/160</span>
                    </div>
                    <div class="ae5-input-group">
                        <textarea name="seo_description" id="ae5SeoDesc" class="ae5-textarea" rows="2"
                                  placeholder="Description SEO accrocheuse (140-155 car.)"><?= $e('seo_description') ?></textarea>
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('seo_description')" data-field="seo_description">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- SERP Preview -->
                <div class="ae5-serp">
                    <div class="ae5-serp-label">📱 Aperçu Google SERP</div>
                    <div class="ae5-serp-title" id="ae5SerpTitle"><?= htmlspecialchars($serpTitleInit ?: 'Titre de votre article') ?></div>
                    <div class="ae5-serp-url">🏠 votresite.fr › blog › <span id="ae5SerpSlug"><?= $e('slug', 'votre-article') ?></span></div>
                    <div class="ae5-serp-desc"  id="ae5SerpDesc"><?= htmlspecialchars($serpDescInit ?: 'Votre description apparaîtra ici…') ?></div>
                </div>
            </div>

            <!-- Onglet 2 : Méta secondaires -->
            <div class="ae5-seo-panel" id="ae5SeoTab-meta">
                <p style="font-size:12px;color:var(--text-3);margin:0 0 16px;">
                    Champs complémentaires utilisés si différents du SEO Title / Description.
                </p>
                <div class="ae5-field">
                    <div class="ae5-label">
                        <span class="ae5-label-text">Meta Title</span>
                        <span class="ae5-char-count" id="ae5MetaTitleCount">0/70</span>
                    </div>
                    <div class="ae5-input-group">
                        <input type="text" name="meta_title" id="ae5MetaTitle" class="ae5-input"
                               value="<?= $e('meta_title') ?>"
                               placeholder="Si différent du SEO Title…">
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('meta_title')" data-field="meta_title">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ae5-field">
                    <div class="ae5-label">
                        <span class="ae5-label-text">Meta Description</span>
                        <span class="ae5-char-count" id="ae5MetaDescCount">0/160</span>
                    </div>
                    <div class="ae5-input-group">
                        <textarea name="meta_description" id="ae5MetaDesc" class="ae5-textarea" rows="2"
                                  placeholder="Si différente de la SEO Description…"><?= $e('meta_description') ?></textarea>
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('meta_description')" data-field="meta_description">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">Meta Keywords</span></div>
                    <input type="text" name="meta_keywords" class="ae5-input"
                           value="<?= $e('meta_keywords') ?>"
                           placeholder="mot-clé 1, mot-clé 2, mot-clé 3…">
                </div>
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">Balise H1 <small style="font-weight:400;text-transform:none;">(si différent du titre)</small></span></div>
                    <input type="text" name="h1" class="ae5-input"
                           value="<?= $e('h1') ?>"
                           placeholder="Laisser vide = identique au titre">
                </div>
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">Alt titre</span></div>
                    <input type="text" name="alt_titre" class="ae5-input"
                           value="<?= $e('alt_titre') ?>"
                           placeholder="Titre alternatif (A/B test)">
                </div>
                <div class="ae5-field" style="display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" name="noindex" id="ae5Noindex" value="1"
                           <?= !empty($article['noindex']) ? 'checked' : '' ?>
                           style="width:auto;flex-shrink:0;cursor:pointer;">
                    <label for="ae5Noindex" style="font-size:13px;font-weight:500;color:var(--text-2);cursor:pointer;">
                        Noindex — exclure des moteurs de recherche
                    </label>
                </div>
            </div>

            <!-- Onglet 3 : Mots-clés -->
            <div class="ae5-seo-panel" id="ae5SeoTab-keywords">
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">Mot-clé focus</span></div>
                    <div class="ae5-input-group">
                        <input type="text" name="focus_keyword" id="ae5FocusKw" class="ae5-input"
                               value="<?= $e('focus_keyword') ?>"
                               placeholder="ex : vendre maison bordeaux divorce">
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('focus_keyword')" data-field="focus_keyword">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">Mots-clés secondaires (LSI)</span></div>
                    <div class="ae5-input-group">
                        <input type="text" name="secondary_keywords" id="ae5SecKw" class="ae5-input"
                               value="<?= $e('secondary_keywords') ?>"
                               placeholder="terme 1, terme 2, terme 3…">
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="ae5-ai-btn ae5-ai-btn-violet"
                                onclick="aiField('secondary_keywords')" data-field="secondary_keywords">
                            <i class="fas fa-robot"></i> IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ae5-field">
                    <div class="ae5-label"><span class="ae5-label-text">main_keyword <small style="font-weight:400;text-transform:none;">(champ legacy)</small></span></div>
                    <input type="text" name="main_keyword" class="ae5-input"
                           value="<?= $e('main_keyword') ?>"
                           placeholder="Champ de compatibilité">
                </div>
            </div>

        </div>
    </div>

    <!-- ════════════════════════════════════
         PANNEAU IA — Outils avancés
    ════════════════════════════════════ -->
    <?php if ($aiAvailable): ?>
    <div class="ae5-card ae5-ai-panel">
        <div class="ae5-card-header">
            <span class="ae5-card-title"><i class="fas fa-robot"></i> Outils IA</span>
            <span style="font-size:11px;color:var(--ei-ai);font-weight:600;">
                <i class="fas fa-circle" style="font-size:7px;"></i> <?= htmlspecialchars($aiProvider) ?> actif
            </span>
        </div>
        <div class="ae5-card-body">
            <div class="ae5-field ae5-ai-subject">
                <div class="ae5-label">
                    <span class="ae5-label-text"><i class="fas fa-lightbulb"></i> Sujet / brief</span>
                </div>
                <input type="text" id="ae5AiSubject" class="ae5-input"
                       placeholder="Ex : investissement locatif à Bordeaux, rentabilité 2025"
                       value="<?= $e('titre') ?>">
            </div>
            <div class="ae5-ai-params">
                <select id="ae5AiWords" class="ae5-select">
                    <option value="800">800 mots</option>
                    <option value="1200" selected>1 200 mots</option>
                    <option value="1800">1 800 mots</option>
                    <option value="2500">2 500 mots</option>
                </select>
                <select id="ae5AiTone" class="ae5-select">
                    <option value="professionnel">Professionnel</option>
                    <option value="pédagogique">Pédagogique</option>
                    <option value="enthousiaste">Enthousiaste</option>
                    <option value="neutre">Neutre</option>
                </select>
                <select id="ae5AiType" class="ae5-select">
                    <option value="guide">Guide complet</option>
                    <option value="actualite">Actualité marché</option>
                    <option value="conseil">Conseils pratiques</option>
                    <option value="analyse">Analyse de quartier</option>
                </select>
                <input type="text" id="ae5AiKw" class="ae5-input"
                       placeholder="Mots-clés ciblés"
                       value="<?= $e('focus_keyword') ?>">
            </div>
            <div class="ae5-ai-actions">
                <button type="button" class="ae5-ai-action primary" onclick="aiGenerate()">
                    <i class="fas fa-magic"></i> Générer l'article complet
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiOutline()">
                    <i class="fas fa-list"></i> Plan éditorial
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiImprove()">
                    <i class="fas fa-sparkles"></i> Améliorer contenu
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiMeta()">
                    <i class="fas fa-search"></i> Métas SEO
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiFaq()">
                    <i class="fas fa-question-circle"></i> FAQ Schema.org
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiKeywords()">
                    <i class="fas fa-tags"></i> Mots-clés
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiRewrite()">
                    <i class="fas fa-redo"></i> Réécrire
                </button>
                <button type="button" class="ae5-ai-action" onclick="aiField('extrait')">
                    <i class="fas fa-quote-right"></i> Extrait
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main col -->

<!-- ════════════════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════════════════ -->
<div>

    <!-- Statut publication -->
    <div class="ae5-side-card">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-paper-plane"></i> Publication</div>
        </div>
        <div class="ae5-side-body">
            <div class="ae5-status-opts">
                <div class="ae5-status-opt">
                    <input type="radio" name="status_radio" id="ae5StDraft"
                           value="draft" <?= $currentStatus !== 'published' ? 'checked' : '' ?>>
                    <label for="ae5StDraft" class="lbl-draft"><i class="fas fa-pencil-alt"></i> Brouillon</label>
                </div>
                <div class="ae5-status-opt">
                    <input type="radio" name="status_radio" id="ae5StPublished"
                           value="published" <?= $currentStatus === 'published' ? 'checked' : '' ?>>
                    <label for="ae5StPublished" class="lbl-published"><i class="fas fa-check"></i> Publié</label>
                </div>
            </div>
            <?php if ($isEdit && !empty($article['created_at'])): ?>
            <div style="margin-top:12px;font-size:11px;color:var(--text-3);line-height:1.8;">
                Créé : <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?>
                <?php if (!empty($article['updated_at'])): ?>
                <br>Modifié : <?= date('d/m/Y H:i', strtotime($article['updated_at'])) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:8px;margin-top:14px;">
                <button type="button" class="ae5-btn ae5-btn-draft" style="flex:1;justify-content:center;" onclick="saveArticle('draft')">
                    <i class="fas fa-save"></i> Brouillon
                </button>
                <button type="button" class="ae5-btn ae5-btn-publish" style="flex:1;justify-content:center;" onclick="saveArticle('published')">
                    <i class="fas fa-check"></i> Publier
                </button>
            </div>
        </div>
    </div>

    <!-- Score SEO live -->
    <div class="ae5-side-card">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-chart-line"></i> Score SEO</div>
            <button type="button" onclick="calcSeoScore()"
                    style="background:none;border:none;cursor:pointer;color:var(--ei-primary);font-size:12px;padding:0;">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="ae5-side-body">
            <?php
            $seoClass = $seoScore >= 80 ? 'excellent' : ($seoScore >= 60 ? 'good' : ($seoScore >= 40 ? 'warning' : 'bad'));
            ?>
            <div class="ae5-score-visual">
                <div class="ae5-score-circle <?= $seoClass ?>" id="ae5ScoreCircle"><?= $seoScore ?: '—' ?></div>
                <div>
                    <div class="ae5-score-text" id="ae5ScoreLabel">Score global</div>
                    <div class="ae5-score-hint">Sémantique : <strong><?= $semScore ?>%</strong></div>
                </div>
            </div>
            <div class="ae5-score-bars">
                <?php
                $bars = [
                    ['id'=>'titre',   'label'=>'Titre'],
                    ['id'=>'meta',    'label'=>'Méta title'],
                    ['id'=>'content', 'label'=>'Contenu'],
                    ['id'=>'kw',      'label'=>'Mot-clé'],
                ];
                foreach ($bars as $bar):
                ?>
                <div class="ae5-score-row">
                    <div class="ae5-score-row-label">
                        <span><?= $bar['label'] ?></span>
                        <span id="ae5SBar-<?= $bar['id'] ?>-val" style="color:var(--text-3);">—</span>
                    </div>
                    <div class="ae5-bar">
                        <div class="ae5-bar-fill" id="ae5SBar-<?= $bar['id'] ?>-fill"
                             style="width:0;background:var(--text-3);"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($isEdit): ?>
            <div class="ae5-stats">
                <div class="ae5-stat">
                    <div class="ae5-stat-val"><?= number_format((int)($article['views'] ?? 0)) ?></div>
                    <div class="ae5-stat-lbl">Vues</div>
                </div>
                <div class="ae5-stat">
                    <div class="ae5-stat-val"><?= $readingTimeVal ?>m</div>
                    <div class="ae5-stat-lbl">Lecture</div>
                </div>
                <div class="ae5-stat">
                    <div class="ae5-stat-val"><?= number_format((int)($article['word_count'] ?? 0)) ?></div>
                    <div class="ae5-stat-lbl">Mots</div>
                </div>
                <div class="ae5-stat">
                    <div class="ae5-stat-val" id="ae5SeoScoreStat"><?= $seoScore ?></div>
                    <div class="ae5-stat-lbl">SEO %</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions rapides -->
    <?php if ($isEdit): ?>
    <div class="ae5-side-card">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-bolt"></i> Actions rapides</div>
        </div>
        <div class="ae5-side-body">
            <div class="ae5-quick">
                <a href="?page=seo-semantic&analyze=article&id=<?= $id ?>" class="ae5-quick-item">
                    <i class="fas fa-brain"></i> Analyse sémantique
                </a>
                <a href="?page=seo-articles" class="ae5-quick-item">
                    <i class="fas fa-chart-bar"></i> Vue SEO articles
                </a>
                <?php if (!empty($article['slug'])): ?>
                <a href="/blog/<?= $e('slug') ?>" target="_blank" class="ae5-quick-item">
                    <i class="fas fa-external-link-alt"></i> Voir en ligne
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Image à la une -->
    <div class="ae5-side-card">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-image"></i> Image à la une</div>
        </div>
        <div class="ae5-side-body">
            <div class="ae5-img-zone <?= !empty($article['featured_image']) ? 'has-img' : '' ?>"
                 id="ae5ImgZone"
                 onclick="document.getElementById('ae5ImgFile').click()">
                <?php if (!empty($article['featured_image'])): ?>
                    <img id="ae5ImgPreview" src="<?= $e('featured_image') ?>" alt="">
                    <button type="button" class="ae5-img-remove" onclick="event.stopPropagation();removeImg()">
                        <i class="fas fa-times"></i>
                    </button>
                <?php else: ?>
                    <div class="ae5-img-placeholder" id="ae5ImgPlaceholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Cliquer pour ajouter</span>
                    </div>
                    <img id="ae5ImgPreview" src="" alt="" style="display:none;">
                <?php endif; ?>
            </div>
            <input type="file" id="ae5ImgFile" accept="image/*" style="display:none;"
                   onchange="uploadImg(this)">
            <input type="hidden" name="featured_image" id="ae5FeaturedImg"
                   value="<?= $e('featured_image') ?>">
            <input type="text" name="featured_image_alt" class="ae5-input"
                   style="margin-top:8px;font-size:12px;"
                   value="<?= $e('featured_image_alt') ?>"
                   placeholder="Texte alternatif (SEO + accessibilité)">
        </div>
    </div>

    <!-- Catégorisation -->
    <div class="ae5-side-card">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-tags"></i> Catégorisation</div>
        </div>
        <div class="ae5-side-body">
            <div class="ae5-sf">
                <label>Ville</label>
                <input type="text" name="ville" class="ae5-input"
                       value="<?= $e('ville') ?>" placeholder="Bordeaux">
            </div>
            <div class="ae5-sf">
                <label>Raison de vente</label>
                <select name="raison_vente" class="ae5-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (['Divorce / Séparation','Succession / Héritage','Difficulté financière',
                                    'Mutation professionnelle','Retraite','Investissement','Déménagement','Autre'] as $rv): ?>
                    <option value="<?= htmlspecialchars($rv) ?>"
                            <?= ($article['raison_vente'] ?? '') === $rv ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rv) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ae5-sf">
                <label>Persona cible</label>
                <select name="persona" class="ae5-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (['Primo-accédant','Vendeur expérimenté','Investisseur','Expatrié',
                                    'Retraité','Héritier','Divorcé','Professionnel'] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"
                            <?= ($article['persona'] ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ae5-sf">
                <label>Type d'article</label>
                <select name="type_article" class="ae5-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (['guide'=>'Guide pratique','conseil'=>'Conseil expert','analyse'=>'Analyse marché',
                                    'quartier'=>'Quartier','actualite'=>'Actualité','temoignage'=>'Témoignage','juridique'=>'Juridique'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= ($article['type_article'] ?? '') === $k ? 'selected' : '' ?>>
                        <?= $l ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ae5-sf">
                <label>Niveau de conscience</label>
                <select name="niveau_conscience" class="ae5-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (['Problème','Solution','Produit','Marque','Décision'] as $nc): ?>
                    <option value="<?= $nc ?>" <?= ($article['niveau_conscience'] ?? '') === $nc ? 'selected' : '' ?>>
                        <?= $nc ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ae5-sf">
                <label>Localité</label>
                <input type="text" name="localite" class="ae5-input"
                       value="<?= $e('localite') ?>" placeholder="Chartrons, Bastide…">
            </div>
            <div class="ae5-sf">
                <label>Catégorie</label>
                <input type="text" name="category" class="ae5-input"
                       value="<?= $e('category') ?>" placeholder="Divorce, Marché…">
            </div>
            <div class="ae5-sf">
                <label>Auteur</label>
                <input type="text" name="author" class="ae5-input"
                       value="<?= $e('author') ?>" placeholder="Nom de l'auteur">
            </div>
        </div>
    </div>

    <!-- Zone dangereuse -->
    <?php if ($isEdit): ?>
    <div class="ae5-side-card ae5-danger">
        <div class="ae5-side-header">
            <div class="ae5-side-title"><i class="fas fa-exclamation-triangle"></i> Zone dangereuse</div>
        </div>
        <div class="ae5-side-body">
            <p style="font-size:12px;color:#7f1d1d;margin:0 0 12px;">La suppression est définitive et irréversible.</p>
            <button type="button" class="ae5-btn-delete" onclick="delArticle()">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /sidebar -->
</div><!-- /grid -->
</form>
</div><!-- /ae5 -->

<!-- ═══ SCRIPTS ═══════════════════════════════════════════════════════════ -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
'use strict';

// ════════════════════════════════════════════════════════════
//  CONFIG
// ════════════════════════════════════════════════════════════
const AI_ENDPOINT = '<?= $AI_ENDPOINT ?>';
const CSRF        = '<?= $csrfToken ?>';
const ARTICLE_ID  = <?= $id ?: 0 ?>;
const IS_EDIT     = <?= $isEdit ? 'true' : 'false' ?>;

// Mapping champ → ID élément cible
const FIELD_MAP = {
    'slug'              : 'ae5Slug',
    'extrait'           : 'ae5Extrait',
    'seo_title'         : 'ae5SeoTitle',
    'seo_description'   : 'ae5SeoDesc',
    'meta_title'        : 'ae5MetaTitle',
    'meta_description'  : 'ae5MetaDesc',
    'focus_keyword'     : 'ae5FocusKw',
    'secondary_keywords': 'ae5SecKw',
};

// ════════════════════════════════════════════════════════════
//  QUILL
// ════════════════════════════════════════════════════════════
const quill = new Quill('#ae5QuillEditor', {
    theme: 'snow',
    placeholder: 'Rédigez votre article ici… ou utilisez les outils IA pour générer le contenu.',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'code-block'],
            ['link', 'image'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            ['clean'],
        ]
    }
});

quill.on('text-change', () => {
    updateWordStats();
    calcSeoScore();
});

quill.root.addEventListener('focus', () => document.getElementById('ae5QuillWrap').classList.add('focused'));
quill.root.addEventListener('blur',  () => document.getElementById('ae5QuillWrap').classList.remove('focused'));

function updateWordStats() {
    const text  = quill.getText().trim();
    const words = text ? text.split(/\s+/).length : 0;
    const chars = text.length;
    const mins  = Math.max(1, Math.ceil(words / 200));
    document.getElementById('ae5Words').textContent    = words.toLocaleString('fr-FR');
    document.getElementById('ae5Chars').textContent    = chars.toLocaleString('fr-FR');
    document.getElementById('ae5ReadTime').textContent = mins + ' min';
    return words;
}
updateWordStats();

// ════════════════════════════════════════════════════════════
//  SLUG
// ════════════════════════════════════════════════════════════
const titreEl  = document.getElementById('ae5Titre');
const slugEl   = document.getElementById('ae5Slug');
const slugPrev = document.getElementById('ae5SlugPreview');
let slugManual = <?= !empty($article['slug']) ? 'true' : 'false' ?>;

titreEl?.addEventListener('input', function () {
    if (!slugManual) {
        const s = slugify(this.value);
        slugEl.value = s;
        if (slugPrev) slugPrev.textContent = s || '…';
    }
    updateSerp();
});

slugEl?.addEventListener('input', function () {
    slugManual = !!this.value;
    if (slugPrev) slugPrev.textContent = this.value || '…';
    updateSerp();
});

window.genSlugLocal = function () {
    const s = slugify(titreEl?.value || '');
    if (slugEl) slugEl.value = s;
    if (slugPrev) slugPrev.textContent = s || '…';
    slugManual = false;
};

function slugify(text) {
    const stops = ['le','la','les','de','du','des','un','une','en','et','ou','a','au','aux',
                   'ce','cette','ces','son','sa','ses','mon','ma','mes','pour','par','sur',
                   'avec','dans','qui','que','quoi','dont','est','sont','peut','faire','plus',
                   'moins','tout','tous','ne','pas','se','si','nous','vous','ils','elles','leur'];
    return text.toLowerCase()
        .replace(/[àáâãäå]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíîï]/g,'i')
        .replace(/[òóôõö]/g,'o').replace(/[ùúûü]/g,'u').replace(/[ç]/g,'c').replace(/[æ]/g,'ae').replace(/[œ]/g,'oe')
        .replace(/[^a-z0-9\s-]/g,'')
        .split(/\s+/).filter(w => w && !stops.includes(w)).slice(0, 6).join('-');
}

// ════════════════════════════════════════════════════════════
//  SERP PREVIEW
//  Priorité : seo_title > meta_title > titre
//             seo_description > meta_description > extrait
// ════════════════════════════════════════════════════════════
function updateSerp() {
    const seoTitle = val('ae5SeoTitle')  || val('ae5MetaTitle') || val('ae5Titre') || 'Titre de votre article';
    const seoDesc  = val('ae5SeoDesc')   || val('ae5MetaDesc')  || val('ae5Extrait') || 'Votre description…';
    const slug     = val('ae5Slug') || 'votre-article';

    set('ae5SerpTitle', seoTitle);
    set('ae5SerpSlug',  slug);
    set('ae5SerpDesc',  seoDesc);
}

['ae5SeoTitle','ae5SeoDesc','ae5MetaTitle','ae5MetaDesc','ae5Extrait'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateSerp);
});
updateSerp();

// ════════════════════════════════════════════════════════════
//  SEO TABS
// ════════════════════════════════════════════════════════════
window.seoTab = function (name, btn) {
    document.querySelectorAll('.ae5-seo-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ae5-seo-tab').forEach(b  => b.classList.remove('active'));
    document.getElementById('ae5SeoTab-' + name)?.classList.add('active');
    btn.classList.add('active');
};

// ════════════════════════════════════════════════════════════
//  COMPTEURS DE CARACTÈRES
// ════════════════════════════════════════════════════════════
function setupCounter(inputId, counterId, maxLen, goodMin, goodMax) {
    const input   = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!input || !counter) return;
    const update = () => {
        const n = (input.tagName === 'TEXTAREA' ? input : input).value.length;
        counter.textContent = n + '/' + maxLen;
        counter.className = 'ae5-char-count' + (
            n >= goodMin && n <= goodMax ? ' ok'   :
            n > maxLen                   ? ' err'  :
            n > 0                        ? ' warn' : ''
        );
    };
    input.addEventListener('input', update);
    update();
}
setupCounter('ae5Extrait',  'ae5ExtraitCount',   280, 100, 250);
setupCounter('ae5SeoTitle', 'ae5SeoTitleCount',   70,  50,  60);
setupCounter('ae5SeoDesc',  'ae5SeoDescCount',   160, 140, 155);
setupCounter('ae5MetaTitle','ae5MetaTitleCount',   70,  50,  60);
setupCounter('ae5MetaDesc', 'ae5MetaDescCount',  160, 140, 155);

// ════════════════════════════════════════════════════════════
//  SCORE SEO LIVE (calcul local JS)
// ════════════════════════════════════════════════════════════
window.calcSeoScore = function () {
    const title   = val('ae5Titre') || val('ae5SeoTitle') || '';
    const metaT   = val('ae5SeoTitle') || '';
    const metaD   = val('ae5SeoDesc')  || '';
    const kw      = (val('ae5FocusKw') || '').toLowerCase();
    const content = quill.root.innerHTML.replace(/<[^>]+>/g,' ').toLowerCase();
    const wc      = updateWordStats();

    // Score titre
    let sT = 0;
    if (title.length >= 30 && title.length <= 70) sT = 100; else if (title.length) sT = 50;
    if (kw && title.toLowerCase().includes(kw)) sT = Math.min(100, sT + 20);

    // Score méta
    let sM = 0;
    if (metaT.length >= 50 && metaT.length <= 65) sM = 100; else if (metaT.length) sM = 60;
    const descScore = metaD.length >= 140 && metaD.length <= 160 ? 100 : metaD.length ? 60 : 0;
    sM = Math.round((sM + descScore) / 2);

    // Score contenu
    let sC = wc >= 1200 ? 100 : wc >= 800 ? 80 : wc >= 500 ? 60 : wc >= 300 ? 40 : wc > 0 ? 20 : 0;

    // Score mot-clé
    let sK = 0;
    if (kw && content.includes(kw)) {
        const cnt = (content.match(new RegExp(kw.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'g')) || []).length;
        sK = cnt >= 3 ? 100 : cnt >= 1 ? 60 : 30;
    }

    const global = Math.round((sT + sM + sC + sK) / 4);

    // Barres
    [['titre',sT],['meta',sM],['content',sC],['kw',sK]].forEach(([id, score]) => {
        const col = score >= 80 ? '#10b981' : score >= 60 ? '#3b82f6' : score >= 40 ? '#f59e0b' : '#ef4444';
        const bar = document.getElementById('ae5SBar-' + id + '-fill');
        const val2 = document.getElementById('ae5SBar-' + id + '-val');
        if (bar) { bar.style.width = score + '%'; bar.style.background = col; }
        if (val2) { val2.textContent = score; val2.style.color = col; }
    });

    // Cercle
    const circle = document.getElementById('ae5ScoreCircle');
    const label  = document.getElementById('ae5ScoreLabel');
    const stat   = document.getElementById('ae5SeoScoreStat');
    if (circle) {
        circle.textContent = global;
        circle.className = 'ae5-score-circle ' + (global >= 80 ? 'excellent' : global >= 60 ? 'good' : global >= 40 ? 'warning' : 'bad');
    }
    if (label) label.textContent = 'Score SEO : ' + global + '/100';
    if (stat)  stat.textContent  = global;

    const scoreEl = document.getElementById('ae5ContentScore');
    if (scoreEl) {
        scoreEl.textContent = 'SEO : ' + global + '/100';
        scoreEl.style.color = global >= 80 ? '#10b981' : global >= 60 ? '#3b82f6' : global >= 40 ? '#f59e0b' : '#ef4444';
    }
};

// Bind score aux champs SEO
['ae5Titre','ae5SeoTitle','ae5SeoDesc','ae5FocusKw'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', calcSeoScore);
});
calcSeoScore();

// ════════════════════════════════════════════════════════════
//  SAVE
// ════════════════════════════════════════════════════════════
window.saveArticle = function (status) {
    document.getElementById('ae5Status').value  = status;
    document.getElementById('ae5Contenu').value = quill.root.innerHTML;
    const r = document.getElementById('ae5St' + (status === 'published' ? 'Published' : 'Draft'));
    if (r) r.checked = true;
    document.getElementById('ae5Form').submit();
};

document.querySelectorAll('input[name="status_radio"]').forEach(r => {
    r.addEventListener('change', function () {
        document.getElementById('ae5Status').value = this.value;
    });
});

// Ctrl+S
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('ae5Contenu').value = quill.root.innerHTML;
        document.getElementById('ae5Form').submit();
    }
});

// ════════════════════════════════════════════════════════════
//  SUPPRIMER
// ════════════════════════════════════════════════════════════
window.delArticle = function () {
    const title = <?= json_encode((string)($article['titre'] ?? 'cet article')) ?>;
    if (!confirm('⚠️ Supprimer l\'article :\n\n"' + title + '"\n\nCette action est définitive.')) return;
    if (!confirm('Dernière confirmation — supprimer définitivement ?')) return;
    window.location.href = '?page=articles&action=delete&id=<?= $id ?>&csrf_token=<?= $csrfToken ?>';
};

// ════════════════════════════════════════════════════════════
//  IMAGE UPLOAD
// ════════════════════════════════════════════════════════════
window.uploadImg = function (input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { toast('Image trop lourde (max 5 Mo)', 'error'); return; }
    const fd = new FormData();
    fd.append('image', file); fd.append('type', 'article');
    fetch('/admin/api/system/upload.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => d.success && d.url ? setImg(d.url) : fallbackImg(file))
        .catch(() => fallbackImg(file));
};

function fallbackImg(file) {
    const r = new FileReader();
    r.onload = e => setImg(e.target.result);
    r.readAsDataURL(file);
}

function setImg(url) {
    const zone = document.getElementById('ae5ImgZone');
    const prev = document.getElementById('ae5ImgPreview');
    const ph   = document.getElementById('ae5ImgPlaceholder');
    if (zone) zone.classList.add('has-img');
    if (prev) { prev.src = url; prev.style.display = 'block'; }
    if (ph)   ph.style.display = 'none';
    let rm = zone?.querySelector('.ae5-img-remove');
    if (!rm && zone) {
        rm = document.createElement('button');
        rm.type = 'button'; rm.className = 'ae5-img-remove';
        rm.innerHTML = '<i class="fas fa-times"></i>';
        rm.onclick = e => { e.stopPropagation(); removeImg(); };
        zone.appendChild(rm);
    }
    document.getElementById('ae5FeaturedImg').value = url;
    toast('Image chargée', 'success');
}

window.removeImg = function () {
    const zone = document.getElementById('ae5ImgZone');
    const prev = document.getElementById('ae5ImgPreview');
    const ph   = document.getElementById('ae5ImgPlaceholder');
    if (zone) zone.classList.remove('has-img');
    if (prev) { prev.src = ''; prev.style.display = 'none'; }
    if (ph)   ph.style.display = '';
    zone?.querySelector('.ae5-img-remove')?.remove();
    document.getElementById('ae5FeaturedImg').value = '';
    document.getElementById('ae5ImgFile').value = '';
};

// ════════════════════════════════════════════════════════════
//  TOAST
// ════════════════════════════════════════════════════════════
let toastTimer;
window.toast = function (msg, type = 'success', dur = 3500) {
    const el   = document.getElementById('ae5Toast');
    const ico  = document.getElementById('ae5ToastIco');
    const txt  = document.getElementById('ae5ToastMsg');
    const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', ai:'fa-robot', warn:'fa-exclamation-triangle' };
    if (ico) ico.className = 'fas ' + (icons[type] || 'fa-info-circle');
    if (txt) txt.textContent = msg;
    if (el)  { el.className = 'ae5-toast show ' + type; clearTimeout(toastTimer); toastTimer = setTimeout(() => el.classList.remove('show'), dur); }
};

// ════════════════════════════════════════════════════════════
//  MODALE IA
// ════════════════════════════════════════════════════════════
let _modalApply = null;

function openModal(title, loaderMsg = 'Génération en cours…') {
    set('ae5ModalTitle', title);
    set('ae5ModalLoaderTxt', loaderMsg);
    show('ae5ModalLoader'); hide('ae5ModalResult');
    document.getElementById('ae5ModalApply').style.display = 'none';
    document.getElementById('ae5Modal').classList.add('open');
    _modalApply = null;
}

window.closeModal = function () {
    document.getElementById('ae5Modal').classList.remove('open');
};

function showModalResult(text, applyFn = null) {
    hide('ae5ModalLoader'); show('ae5ModalResult');
    set('ae5ModalResultTxt', typeof text === 'object' ? JSON.stringify(text, null, 2) : text);
    _modalApply = applyFn;
    document.getElementById('ae5ModalApply').style.display = applyFn ? 'inline-flex' : 'none';
}

window.applyModal = function () {
    if (_modalApply) _modalApply();
    closeModal();
};

// Fermer avec Échap ou clic fond
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
document.getElementById('ae5Modal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('ae5Modal')) closeModal();
});

// ════════════════════════════════════════════════════════════
//  APPEL IA — Helper central
//  Endpoint unique : /admin/api/ai/generate.php
//  Payload : { module, action, csrf_token, ...params }
// ════════════════════════════════════════════════════════════
async function callAI(module, action, params = {}) {
    const r = await fetch(AI_ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ module, action, csrf_token: CSRF, ...params }),
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'Erreur IA');
    return d;
}

// ════════════════════════════════════════════════════════════
//  IA — BOUTONS INLINE PAR CHAMP
//  articles.meta → renseigne seo_title, meta_description, slug
// ════════════════════════════════════════════════════════════
window.aiField = async function (field) {
    const btn = event?.currentTarget;
    const ico = btn?.querySelector('i');
    const orig = ico?.className;
    if (ico) ico.className = 'fas fa-spinner ae5-spin';
    if (btn) btn.disabled = true;

    try {
        let result = '';
        const titre   = val('ae5Titre')    || '';
        const focusKw = val('ae5FocusKw')  || '';
        const content = quill.root.innerHTML.substring(0, 1500);

        if (field === 'extrait') {
            // Génère un extrait via l'action meta (qui retourne meta_description)
            const d = await callAI('articles', 'meta', { title: titre, keyword: focusKw, content });
            result = d.meta_description || d.data?.meta_description || '';

        } else if (['seo_title','meta_title'].includes(field)) {
            const d = await callAI('articles', 'meta', { title: titre, keyword: focusKw, content });
            result = d.meta_title || d.data?.meta_title || '';

        } else if (['seo_description','meta_description'].includes(field)) {
            const d = await callAI('articles', 'meta', { title: titre, keyword: focusKw, content });
            result = d.meta_description || d.data?.meta_description || '';

        } else if (field === 'slug') {
            const d = await callAI('articles', 'meta', { title: titre, keyword: focusKw, content });
            result = d.slug || d.data?.slug || slugify(titre);

        } else if (field === 'focus_keyword') {
            const d = await callAI('articles', 'keywords', { content: quill.getText().substring(0,2000), subject: titre });
            result = d.keywords?.primary_keyword || '';

        } else if (field === 'secondary_keywords') {
            const d = await callAI('articles', 'keywords', { content: quill.getText().substring(0,2000), subject: titre });
            const kws = d.keywords?.secondary_keywords?.slice(0,5) || [];
            result = kws.map(k => k.keyword || k).join(', ');
        }

        result = result.trim().replace(/^["'`]+|["'`]+$/g, '');
        const targetId = FIELD_MAP[field];
        if (targetId && result) {
            const el = document.getElementById(targetId);
            if (el) { el.value = result; el.dispatchEvent(new Event('input')); }
        }

        // Mise à jour SERP et slug si besoin
        if (field === 'slug') {
            if (slugPrev) slugPrev.textContent = result || '…';
            slugManual = true;
        }
        updateSerp(); calcSeoScore();
        toast('✨ ' + field + ' généré', 'ai');

    } catch (err) {
        toast('❌ ' + err.message, 'error');
    }

    if (ico) ico.className = orig || 'fas fa-robot';
    if (btn) btn.disabled = false;
};

// ════════════════════════════════════════════════════════════
//  IA — GÉNÉRER ARTICLE COMPLET
// ════════════════════════════════════════════════════════════
window.aiGenerate = async function () {
    const subject = val('ae5AiSubject') || val('ae5Titre') || '';
    if (!subject?.trim()) { toast('Saisissez un sujet dans "Sujet / brief"', 'warn'); return; }

    openModal('✨ Génération de l\'article complet', 'Rédaction en cours… (30-60 secondes)');

    try {
        const d = await callAI('articles', 'generate', {
            subject,
            keywords:   val('ae5AiKw')    || val('ae5FocusKw') || '',
            word_count: val('ae5AiWords') || '1200',
            tone:       val('ae5AiTone')  || 'professionnel',
            type:       val('ae5AiType')  || 'guide',
        });
        const art = d.article || d;

        showModalResult(
            '✅ Article généré !\n\n'
            + 'Titre   : ' + (art.title || '—') + '\n'
            + 'Slug    : ' + (art.slug  || '—') + '\n'
            + 'Mots    : ~' + (val('ae5AiWords') || '1200') + '\n'
            + 'Méta    : ' + (art.meta_title ? '✅' : '—') + '\n\n'
            + 'Cliquez "Appliquer" pour insérer dans l\'éditeur.',
            () => {
                if (art.title)            { setVal('ae5Titre', art.title); }
                if (art.slug)             { setVal('ae5Slug', art.slug); slugManual = true; if (slugPrev) slugPrev.textContent = art.slug; }
                if (art.content)          { quill.clipboard.dangerouslyPasteHTML(art.content); document.getElementById('ae5Contenu').value = art.content; }
                if (art.meta_title)       { setVal('ae5SeoTitle', art.meta_title); setVal('ae5MetaTitle', art.meta_title); }
                if (art.meta_description) { setVal('ae5SeoDesc', art.meta_description); setVal('ae5MetaDesc', art.meta_description); }
                if (art.excerpt)          { setVal('ae5Extrait', art.excerpt); }
                if (art.primary_keyword)  { setVal('ae5FocusKw', art.primary_keyword); }
                // Déclencher les recalculs
                ['ae5Titre','ae5SeoTitle','ae5SeoDesc','ae5Extrait','ae5FocusKw'].forEach(id => {
                    document.getElementById(id)?.dispatchEvent(new Event('input'));
                });
                updateSerp(); calcSeoScore();
                toast('Article complet appliqué !', 'ai', 4000);
            }
        );
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — AMÉLIORER LE CONTENU
// ════════════════════════════════════════════════════════════
window.aiImprove = async function () {
    const content = quill.root.innerHTML;
    if (!content || content === '<p><br></p>') { toast('Aucun contenu à améliorer', 'warn'); return; }

    openModal('⚡ Amélioration du contenu', 'Analyse et réécriture en cours…');
    try {
        const d = await callAI('articles', 'improve', {
            content, title: val('ae5Titre') || '', objectives: 'SEO, lisibilité, engagement'
        });
        const improved = d.data?.improved_content || d.improved_content || '';
        const changes  = d.data?.changes_summary  || [];

        showModalResult(
            '✅ Contenu amélioré !\n\nModifications :\n' + changes.map(c => '• ' + c).join('\n'),
            () => {
                if (improved) {
                    quill.clipboard.dangerouslyPasteHTML(improved);
                    document.getElementById('ae5Contenu').value = improved;
                    calcSeoScore();
                }
                toast('Contenu amélioré !', 'ai');
            }
        );
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — MÉTAS SEO
// ════════════════════════════════════════════════════════════
window.aiMeta = async function () {
    openModal('🔍 Génération des métas SEO', 'Optimisation SEO en cours…');
    try {
        const d = await callAI('articles', 'meta', {
            title:   val('ae5Titre')   || '',
            keyword: val('ae5FocusKw') || '',
            content: quill.root.innerHTML.substring(0, 1200),
        });
        const meta = d.meta_title ? d : (d.data || d);

        showModalResult(
            '✅ Métas générées !\n\n'
            + 'Meta title : ' + (meta.meta_title || '—') + '\n'
            + 'Meta desc  : ' + (meta.meta_description || '—') + '\n'
            + 'Slug       : ' + (meta.slug || '—'),
            () => {
                if (meta.meta_title) {
                    setVal('ae5SeoTitle',  meta.meta_title);
                    setVal('ae5MetaTitle', meta.meta_title);
                    document.getElementById('ae5SeoTitle')?.dispatchEvent(new Event('input'));
                }
                if (meta.meta_description) {
                    setVal('ae5SeoDesc',  meta.meta_description);
                    setVal('ae5MetaDesc', meta.meta_description);
                    document.getElementById('ae5SeoDesc')?.dispatchEvent(new Event('input'));
                }
                if (meta.slug && !slugManual) {
                    setVal('ae5Slug', meta.slug);
                    if (slugPrev) slugPrev.textContent = meta.slug;
                    slugManual = true;
                }
                updateSerp(); calcSeoScore();
                toast('Métas SEO appliquées !', 'ai');
            }
        );
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — FAQ Schema.org
// ════════════════════════════════════════════════════════════
window.aiFaq = async function () {
    openModal('❓ FAQ Schema.org', 'Génération des questions/réponses…');
    try {
        const d = await callAI('articles', 'faq', {
            title:   val('ae5Titre') || '',
            content: quill.root.innerHTML.substring(0, 2500),
            count:   5,
        });
        const faq = Array.isArray(d.faq) ? d.faq : [];
        const preview = faq.map((f, i) => `Q${i+1}: ${f.question}\nR: ${f.answer}`).join('\n\n');

        showModalResult(
            preview || 'Aucune FAQ générée',
            () => {
                if (!faq.length) return;
                const schema = JSON.stringify({
                    '@context':'https://schema.org','@type':'FAQPage',
                    mainEntity: faq.map(f => ({
                        '@type':'Question','name':f.question,
                        acceptedAnswer:{'@type':'Answer','text':f.answer}
                    }))
                }, null, 2);
                const current = quill.root.innerHTML;
                document.getElementById('ae5Contenu').value = current
                    + '\n<!-- FAQ Schema.org -->\n<script type="application/ld+json">' + schema + '<\/script>';
                toast('FAQ Schema.org insérée !', 'ai');
            }
        );
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — PLAN ÉDITORIAL
// ════════════════════════════════════════════════════════════
window.aiOutline = async function () {
    const subject = val('ae5AiSubject') || val('ae5Titre') || '';
    if (!subject?.trim()) { toast('Saisissez un sujet', 'warn'); return; }

    openModal('📋 Plan éditorial', 'Construction du plan…');
    try {
        const d = await callAI('articles', 'outline', { subject, keyword: val('ae5FocusKw') || '' });
        const outline  = d.outline?.outline || d.outline || [];
        const titles   = d.outline?.title_suggestions || [];

        let preview = '';
        if (titles.length) preview += '💡 Titres suggérés :\n' + titles.map((t,i) => `${i+1}. ${t}`).join('\n') + '\n\n';
        preview += '📋 Plan :\n';
        outline.forEach(item => {
            preview += `[${(item.level||'H2').toUpperCase()}] ${item.title || item} (~${item.estimated_words||'?'} mots)\n`;
            if (item.description) preview += `    → ${item.description}\n`;
        });

        showModalResult(preview || 'Aucun plan généré', () => {
            if (titles[0] && !val('ae5Titre')) {
                setVal('ae5Titre', titles[0]);
                document.getElementById('ae5Titre')?.dispatchEvent(new Event('input'));
            }
            toast('Plan prêt — utilisez-le comme guide !', 'ai');
        });
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — EXTRACTION MOTS-CLÉS
// ════════════════════════════════════════════════════════════
window.aiKeywords = async function () {
    openModal('🏷 Extraction mots-clés', 'Analyse sémantique…');
    try {
        const d = await callAI('articles', 'keywords', {
            content: quill.getText().substring(0, 2500),
            subject: val('ae5Titre') || '',
        });
        const kw = d.keywords || d;
        let preview = '';
        if (kw.primary_keyword)       preview += '🎯 Principal : ' + kw.primary_keyword + '\n\n';
        if (kw.secondary_keywords?.length) preview += '📌 Secondaires :\n' + kw.secondary_keywords.map(k=>'  → '+(k.keyword||k)).join('\n') + '\n\n';
        if (kw.long_tail_keywords?.length) preview += '🔍 Longue traîne :\n' + kw.long_tail_keywords.map(k=>'  → '+k).join('\n') + '\n\n';
        if (kw.local_keywords?.length)     preview += '📍 Local :\n' + kw.local_keywords.map(k=>'  → '+k).join('\n');

        showModalResult(preview || 'Aucun mot-clé', () => {
            if (kw.primary_keyword) {
                setVal('ae5FocusKw', kw.primary_keyword);
                document.getElementById('ae5FocusKw')?.dispatchEvent(new Event('input'));
            }
            if (kw.secondary_keywords?.length) {
                const sec = kw.secondary_keywords.slice(0,5).map(k=>k.keyword||k).join(', ');
                if (!val('ae5SecKw')) setVal('ae5SecKw', sec);
            }
            calcSeoScore();
            toast('Mots-clés appliqués !', 'ai');
        });
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  IA — RÉÉCRIRE
// ════════════════════════════════════════════════════════════
window.aiRewrite = async function () {
    const content = quill.root.innerHTML;
    if (!content || content === '<p><br></p>') { toast('Aucun contenu à réécrire', 'warn'); return; }
    const angle = prompt('Angle de réécriture :\n(ex: investisseur, primo-accédant, vendeur pressé…)', 'primo-accédant');
    if (!angle) return;

    openModal('🔄 Réécriture du contenu', 'Réécriture avec le nouvel angle…');
    try {
        const d = await callAI('articles', 'rewrite', { content, angle });
        const rewritten = d.rewritten_content || '';

        showModalResult(
            '✅ Contenu réécrit avec l\'angle : "' + angle + '"\n\nCliquez "Appliquer" pour remplacer.',
            () => {
                if (rewritten) {
                    quill.clipboard.dangerouslyPasteHTML(rewritten);
                    document.getElementById('ae5Contenu').value = rewritten;
                    calcSeoScore();
                }
                toast('Contenu réécrit !', 'ai');
            }
        );
    } catch (err) { closeModal(); toast('❌ ' + err.message, 'error'); }
};

// ════════════════════════════════════════════════════════════
//  HELPERS DOM
// ════════════════════════════════════════════════════════════
function val(id)      { const e = document.getElementById(id); return e?.tagName === 'TEXTAREA' ? e.value : (e?.value || ''); }
function setVal(id,v) { const e = document.getElementById(id); if (e) e.value = v; }
function set(id, txt) { const e = document.getElementById(id); if (e) e.textContent = txt; }
function show(id)     { const e = document.getElementById(id); if (e) e.style.display = ''; }
function hide(id)     { const e = document.getElementById(id); if (e) e.style.display = 'none'; }

// Exposer slugify pour aiField
window.slugify = slugify;

// ════════════════════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════════════════════
document.getElementById('ae5Form')?.addEventListener('submit', () => {
    document.getElementById('ae5Contenu').value = quill.root.innerHTML;
});

console.log('📝 EcosystèmeImmo — Article Editor v5.0 | ' + (IS_EDIT ? 'Edit #' + ARTICLE_ID : 'Nouveau'));
})();
</script>