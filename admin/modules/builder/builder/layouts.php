<?php
/**
 * ══════════════════════════════════════════════════════════════
 * BUILDER PRO — Gestion des Layouts
 * /admin/modules/builder/layouts.php
 * ══════════════════════════════════════════════════════════════
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once __DIR__ . '/../../../includes/init.php';
    }
}
if (isset($pdo) && !isset($db)) $db = $pdo;
if (isset($db) && !isset($pdo)) $pdo = $db;

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $token  = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        $flash = 'Erreur de sécurité (CSRF). Rechargez la page.';
        $flashType = 'error';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $name    = trim($_POST['name'] ?? '');
                    $slug    = trim($_POST['slug'] ?? '');
                    $context = trim($_POST['context'] ?? 'page');
                    $desc    = trim($_POST['description'] ?? '');
                    $zones   = trim($_POST['zones_json'] ?? '["main"]');
                    if (empty($name)) throw new Exception('Le nom est obligatoire.');
                    if (empty($slug)) { $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-'); }
                    $pdo->prepare("INSERT INTO builder_layouts (name,slug,context,description,zones_json,is_default,created_at) VALUES (?,?,?,?,?,0,NOW())")->execute([$name,$slug,$context,$desc,$zones]);
                    $flash = "Layout « {$name} » créé avec succès.";
                    break;
                case 'update':
                    $id=$id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $slug=trim($_POST['slug']??''); $context=trim($_POST['context']??'page'); $desc=trim($_POST['description']??''); $zones=trim($_POST['zones_json']??'["main"]');
                    if (!$id||empty($name)) throw new Exception('Données invalides.');
                    $pdo->prepare("UPDATE builder_layouts SET name=?,slug=?,context=?,description=?,zones_json=?,updated_at=NOW() WHERE id=?")->execute([$name,$slug,$context,$desc,$zones,$id]);
                    $flash = "Layout « {$name} » mis à jour.";
                    break;
                case 'delete':
                    $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalide.');
                    $check=$pdo->prepare("SELECT is_default,name FROM builder_layouts WHERE id=?"); $check->execute([$id]); $row=$check->fetch(PDO::FETCH_ASSOC);
                    if(!$row) throw new Exception('Layout introuvable.');
                    if($row['is_default']) throw new Exception('Impossible de supprimer un layout par défaut.');
                    $usage=$pdo->prepare("SELECT COUNT(*) FROM builder_templates WHERE layout_id=?"); $usage->execute([$id]);
                    if($usage->fetchColumn()>0) throw new Exception("Ce layout est utilisé par des templates.");
                    $pdo->prepare("DELETE FROM builder_layouts WHERE id=?")->execute([$id]);
                    $flash = "Layout « {$row['name']} » supprimé.";
                    break;
                case 'set_default':
                    $id=(int)($_POST['id']??0); $context=trim($_POST['context']??'page');
                    if(!$id) throw new Exception('ID invalide.');
                    $pdo->prepare("UPDATE builder_layouts SET is_default=0 WHERE context=?")->execute([$context]);
                    $pdo->prepare("UPDATE builder_layouts SET is_default=1 WHERE id=?")->execute([$id]);
                    $flash = "Layout défini par défaut pour « {$context} ».";
                    break;
            }
        } catch (Exception $e) { $flash=$e->getMessage(); $flashType='error'; }
    }
}

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$filterContext = $_GET['context'] ?? '';
try {
    if ($filterContext && in_array($filterContext,['page','article','secteur','capture','landing','header','footer'])) {
        $stmt=$pdo->prepare("SELECT * FROM builder_layouts WHERE context=? ORDER BY is_default DESC,name ASC"); $stmt->execute([$filterContext]);
    } else {
        $stmt=$pdo->query("SELECT * FROM builder_layouts ORDER BY context ASC,is_default DESC,name ASC");
    }
    $layouts=$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $layouts=[]; $flash="Table builder_layouts introuvable."; $flashType='error'; }

$templateCounts=[];
try {
    $tc=$pdo->query("SELECT layout_id,COUNT(*) as cnt FROM builder_templates GROUP BY layout_id");
    while($r=$tc->fetch(PDO::FETCH_ASSOC)) $templateCounts[$r['layout_id']]=(int)$r['cnt'];
} catch(PDOException $e){}

$editLayout=null; $editId=(int)($_GET['edit']??0);
if($editId) foreach($layouts as $l) { if($l['id']==$editId){$editLayout=$l;break;} }

$contexts=[
    'page'    =>['label'=>'Page',    'icon'=>'file',      'color'=>'#3b82f6'],
    'article' =>['label'=>'Article', 'icon'=>'newspaper', 'color'=>'#8b5cf6'],
    'secteur' =>['label'=>'Secteur', 'icon'=>'map-pin',   'color'=>'#10b981'],
    'capture' =>['label'=>'Capture', 'icon'=>'target',    'color'=>'#f59e0b'],
    'landing' =>['label'=>'Landing', 'icon'=>'rocket',    'color'=>'#ef4444'],
    'header'  =>['label'=>'Header',  'icon'=>'layout',    'color'=>'#06b6d4'],
    'footer'  =>['label'=>'Footer',  'icon'=>'columns',   'color'=>'#64748b'],
];

$standaloneMode = !defined('ADMIN_ROUTER');

ob_start();
?>
<?php if ($standaloneMode): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Layouts — Builder Pro</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f1f5f9;color:#334155;display:flex;min-height:100vh;">

<!-- SIDEBAR -->
<aside style="width:260px;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);min-height:100vh;position:fixed;left:0;top:0;bottom:0;display:flex;flex-direction:column;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,.15);">
    <div style="padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,.07);">
        <div style="font-size:1.1rem;font-weight:800;color:#fff;">🏠 ÉCOSYSTÈME</div>
        <div style="font-size:.72rem;color:#64748b;margin-top:2px;text-transform:uppercase;letter-spacing:1px;">Admin Pro</div>
    </div>
    <?php
    $navStyle   = 'display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:#94a3b8;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s;margin-bottom:2px;';
    $navActive  = 'display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:#60a5fa;text-decoration:none;font-size:.875rem;font-weight:500;background:rgba(59,130,246,.15);margin-bottom:2px;';
    $iStyle     = 'width:18px;text-align:center;font-size:.85rem;flex-shrink:0;';
    $sectionTtl = 'font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;color:#475569;font-weight:700;padding:0 8px;margin-bottom:6px;';
    ?>
    <div style="padding:20px 12px 8px;">
        <div style="<?= $sectionTtl ?>">Navigation</div>
        <a href="/admin/dashboard.php" style="<?= $navStyle ?>"><i class="fas fa-home" style="<?= $iStyle ?>"></i> Dashboard</a>
        <a href="/admin/dashboard.php?page=articles" style="<?= $navStyle ?>"><i class="fas fa-newspaper" style="<?= $iStyle ?>"></i> Articles</a>
        <a href="/admin/dashboard.php?page=contacts" style="<?= $navStyle ?>"><i class="fas fa-address-book" style="<?= $iStyle ?>"></i> Contacts</a>
        <a href="/admin/dashboard.php?page=leads" style="<?= $navStyle ?>"><i class="fas fa-funnel-dollar" style="<?= $iStyle ?>"></i> Leads</a>
    </div>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 20px;"></div>
    <div style="padding:16px 12px 8px;">
        <div style="<?= $sectionTtl ?>">Builder Pro</div>
        <a href="/admin/modules/builder/layouts.php" style="<?= $navActive ?>">
            <i class="fas fa-layer-group" style="<?= $iStyle ?>"></i> Layouts
            <span style="margin-left:auto;background:rgba(59,130,246,.2);color:#60a5fa;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:10px;"><?= count($layouts) ?></span>
        </a>
        <a href="/admin/modules/builder/templates.php" style="<?= $navStyle ?>"><i class="fas fa-swatchbook" style="<?= $iStyle ?>"></i> Templates</a>
        <a href="/admin/dashboard.php?page=builder" style="<?= $navStyle ?>"><i class="fas fa-magic" style="<?= $iStyle ?>"></i> Builder</a>
    </div>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 20px;"></div>
    <div style="padding:16px 12px 8px;">
        <div style="<?= $sectionTtl ?>">Paramètres</div>
        <a href="/admin/dashboard.php?page=settings" style="<?= $navStyle ?>"><i class="fas fa-cog" style="<?= $iStyle ?>"></i> Paramètres</a>
        <a href="/admin/logout.php" style="<?= $navStyle ?>"><i class="fas fa-sign-out-alt" style="<?= $iStyle ?>"></i> Déconnexion</a>
    </div>
    <div style="margin-top:auto;padding:16px 20px;border-top:1px solid rgba(255,255,255,.07);font-size:.78rem;color:#475569;">Builder Pro v2.0</div>
</aside>

<!-- MAIN WRAPPER -->
<div style="margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh;">
    <!-- TOPBAR -->
    <header style="background:#fff;border-bottom:1px solid #e2e8f0;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;">
        <div style="display:flex;align-items:center;gap:8px;font-size:.875rem;color:#64748b;">
            <a href="/admin/dashboard.php" style="color:#64748b;text-decoration:none;"><i class="fas fa-home"></i></a>
            <span style="color:#cbd5e1;">›</span>
            <span style="color:#64748b;">Builder Pro</span>
            <span style="color:#cbd5e1;">›</span>
            <span style="color:#1e293b;font-weight:600;">Layouts</span>
        </div>
        <button onclick="toggleForm()" style="padding:8px 18px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-plus"></i> Nouveau layout
        </button>
    </header>
    <main style="padding:28px;flex:1;">
<?php endif; ?>

<!-- ═══ CSS ALWAYS INJECTED ═══ -->
<style>
/* Reset scoped */
.lm-page * { box-sizing: border-box; }

/* Hero */
.lm-hero {
    background: linear-gradient(135deg,#1e293b 0%,#334155 100%);
    border-radius: 16px; padding: 28px 32px; margin-bottom: 24px;
    display: flex; justify-content: space-between; align-items: center; color: #fff;
}
.lm-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
.lm-hero p  { color: #94a3b8; margin: 0; font-size: .9rem; }
.lm-stats   { display: flex; gap: 16px; }
.lm-stat    { text-align: center; padding: 12px 20px; background: rgba(255,255,255,.08); border-radius: 10px; min-width: 80px; }
.lm-stat-v  { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.lm-stat-l  { font-size: .68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

/* Flash */
.lm-flash   { padding: 13px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; font-size: .9rem; }
.lm-flash.s { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.lm-flash.e { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Form panel */
.lm-panel   { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.lm-panel h2{ font-size: 1.1rem; font-weight: 700; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; color: #1e293b; }
.lm-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.lm-fg      { display: flex; flex-direction: column; gap: 5px; }
.lm-fg.full { grid-column: 1/-1; }
.lm-fg label{ font-size: .82rem; font-weight: 600; color: #475569; }
.lm-fg input,.lm-fg select,.lm-fg textarea {
    padding: 9px 13px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .875rem;
    color: #334155; background: #fff; transition: border-color .2s,box-shadow .2s; width: 100%;
}
.lm-fg input:focus,.lm-fg select:focus,.lm-fg textarea:focus {
    outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.lm-fg textarea { resize: vertical; min-height: 70px; }
.lm-fg small{ color: #94a3b8; font-size: .76rem; }
.lm-btns    { display: flex; gap: 10px; margin-top: 16px; }
.lm-btn-p   { padding: 9px 22px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: .875rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .2s; }
.lm-btn-p:hover { background: #2563eb; }
.lm-btn-s   { padding: 9px 22px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500; font-size: .875rem; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all .2s; }
.lm-btn-s:hover { background: #e2e8f0; }

/* Toolbar */
.lm-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.lm-filters { display: flex; gap: 6px; flex-wrap: wrap; }
.lm-fbtn    { padding: 7px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: .82rem; font-weight: 500; cursor: pointer; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.lm-fbtn:hover  { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.lm-fbtn.on     { background: #3b82f6; color: #fff; border-color: #3b82f6; }

/* Grid */
.lm-grid-cards { display: grid; grid-template-columns: repeat(auto-fill,minmax(340px,1fr)); gap: 18px; }

/* Card */
.lm-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; transition: all .25s; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.lm-card:hover { border-color: #93c5fd; box-shadow: 0 6px 24px rgba(59,130,246,.1); transform: translateY(-2px); }
.lm-card-hd { padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; }
.lm-card-hd h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.lm-ctx-badge { padding: 4px 10px; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #fff; display: flex; align-items: center; gap: 4px; }
.lm-card-bd { padding: 18px; }
.lm-meta    { display: flex; flex-direction: column; gap: 10px; }
.lm-meta-r  { display: flex; justify-content: space-between; align-items: flex-start; font-size: .84rem; }
.lm-meta-lbl{ color: #94a3b8; font-weight: 500; flex-shrink: 0; margin-right: 12px; }
.lm-meta-val{ font-weight: 600; color: #334155; text-align: right; }
.lm-meta-val.mono { font-family:'Courier New',monospace; font-size: .78rem; color: #475569; }
.lm-zones   { display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end; }
.lm-zone    { padding: 2px 9px; background: #f1f5f9; border-radius: 5px; font-size: .73rem; color: #475569; font-family:'Courier New',monospace; border: 1px solid #e2e8f0; }
.lm-desc    { font-size: .82rem; color: #64748b; line-height: 1.5; padding-top: 8px; border-top: 1px solid #f8fafc; margin-top: 4px; }
.lm-card-ft { padding: 11px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.lm-default { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 6px; font-size: .72rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }
.lm-actions { display: flex; gap: 5px; }
.lm-ico     { width: 32px; height: 32px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; font-size: .8rem; text-decoration: none; }
.lm-ico:hover     { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.lm-ico.d:hover   { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
.lm-ico.str:hover { border-color: #f59e0b; color: #f59e0b; background: #fffbeb; }
.lm-ico.off       { opacity: .3; cursor: not-allowed; }

/* Empty */
.lm-empty { text-align: center; padding: 60px 20px; color: #94a3b8; background: #fff; border-radius: 14px; border: 1px dashed #e2e8f0; }
.lm-empty i { font-size: 2.5rem; margin-bottom: 14px; display: block; opacity: .3; }
.lm-empty p { font-size: .95rem; margin-bottom: 16px; }

@media(max-width:768px){
    .lm-hero{flex-direction:column;gap:16px;}
    .lm-grid{grid-template-columns:1fr;}
    .lm-grid-cards{grid-template-columns:1fr;}
}
</style>

<div class="lm-page">

<!-- HERO -->
<div class="lm-hero">
    <div>
        <h1><i class="fas fa-layer-group"></i> Gestion des Layouts</h1>
        <p>Structures de page réutilisables pour le Builder Pro</p>
    </div>
    <div class="lm-stats">
        <div class="lm-stat"><div class="lm-stat-v"><?= count($layouts) ?></div><div class="lm-stat-l">Layouts</div></div>
        <div class="lm-stat"><div class="lm-stat-v"><?= count(array_unique(array_column($layouts,'context'))) ?></div><div class="lm-stat-l">Contextes</div></div>
        <div class="lm-stat"><div class="lm-stat-v"><?= count(array_filter($layouts,fn($l)=>($l['is_default']??0))) ?></div><div class="lm-stat-l">Défauts</div></div>
    </div>
</div>

<!-- FLASH -->
<?php if($flash): ?>
<div class="lm-flash <?= $flashType==='success'?'s':'e' ?>">
    <i class="fas fa-<?= $flashType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($flash) ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="lm-panel" id="layoutForm" style="<?= (!$editLayout&&!isset($_GET['create']))?'display:none':'' ?>">
    <h2><i class="fas fa-<?= $editLayout?'edit':'plus-circle' ?>"></i> <?= $editLayout?'Modifier le layout':'Nouveau layout' ?></h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action"     value="<?= $editLayout?'update':'create' ?>">
        <?php if($editLayout): ?><input type="hidden" name="id" value="<?= $editLayout['id'] ?>"><?php endif; ?>
        <div class="lm-grid">
            <div class="lm-fg">
                <label>Nom du layout *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editLayout['name']??'') ?>" placeholder="Ex: Article Pleine Largeur">
            </div>
            <div class="lm-fg">
                <label>Slug</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($editLayout['slug']??'') ?>" placeholder="auto-généré si vide">
                <small>Identifiant unique (auto si vide)</small>
            </div>
            <div class="lm-fg">
                <label>Contexte</label>
                <select name="context">
                    <?php foreach($contexts as $key=>$ctx): ?>
                    <option value="<?= $key ?>" <?= ($editLayout['context']??'page')===$key?'selected':'' ?>><?= $ctx['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lm-fg">
                <label>Zones (JSON)</label>
                <input type="text" name="zones_json" value="<?= htmlspecialchars($editLayout['zones_json']??'["main"]') ?>" placeholder='["header","main","sidebar","footer"]'>
                <small>Ex: ["main"] ou ["hero","content","sidebar"]</small>
            </div>
            <div class="lm-fg full">
                <label>Description</label>
                <textarea name="description" placeholder="Description optionnelle..."><?= htmlspecialchars($editLayout['description']??'') ?></textarea>
            </div>
        </div>
        <div class="lm-btns">
            <button type="submit" class="lm-btn-p"><i class="fas fa-<?= $editLayout?'save':'plus' ?>"></i> <?= $editLayout?'Enregistrer':'Créer le layout' ?></button>
            <a href="layouts.php" class="lm-btn-s" onclick="document.getElementById('layoutForm').style.display='none';return false;"><i class="fas fa-times"></i> Annuler</a>
        </div>
    </form>
</div>

<!-- TOOLBAR -->
<div class="lm-toolbar">
    <div class="lm-filters">
        <a href="layouts.php" class="lm-fbtn <?= !$filterContext?'on':'' ?>"><i class="fas fa-th"></i> Tous</a>
        <?php foreach($contexts as $key=>$ctx): ?>
        <a href="layouts.php?context=<?= $key ?>" class="lm-fbtn <?= $filterContext===$key?'on':'' ?>">
            <i class="fas fa-<?= $ctx['icon'] ?>"></i> <?= $ctx['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>
    <button class="lm-btn-p" onclick="toggleForm()"><i class="fas fa-plus"></i> Nouveau layout</button>
</div>

<!-- GRID -->
<?php if(empty($layouts)): ?>
<div class="lm-empty">
    <i class="fas fa-layer-group"></i>
    <p>Aucun layout trouvé<?= $filterContext?" pour « {$filterContext} »":'' ?>.</p>
    <button class="lm-btn-p" onclick="toggleForm()"><i class="fas fa-plus"></i> Créer votre premier layout</button>
</div>
<?php else: ?>
<div class="lm-grid-cards">
    <?php foreach($layouts as $layout):
        $ctx      = $contexts[$layout['context']]??['label'=>$layout['context'],'icon'=>'file','color'=>'#64748b'];
        $zones    = json_decode($layout['zones_json']??'["main"]',true)?:['main'];
        $tplCount = $templateCounts[$layout['id']]??0;
        $isDef    = (bool)($layout['is_default']??false);
    ?>
    <div class="lm-card">
        <div class="lm-card-hd">
            <h3><?= htmlspecialchars($layout['name']) ?></h3>
            <span class="lm-ctx-badge" style="background:<?= $ctx['color'] ?>"><i class="fas fa-<?= $ctx['icon'] ?>"></i> <?= $ctx['label'] ?></span>
        </div>
        <div class="lm-card-bd">
            <div class="lm-meta">
                <div class="lm-meta-r">
                    <span class="lm-meta-lbl">Slug</span>
                    <span class="lm-meta-val mono"><?= htmlspecialchars($layout['slug']) ?></span>
                </div>
                <div class="lm-meta-r">
                    <span class="lm-meta-lbl">Templates liés</span>
                    <span class="lm-meta-val"><?= $tplCount ?> template<?= $tplCount>1?'s':'' ?></span>
                </div>
                <div class="lm-meta-r">
                    <span class="lm-meta-lbl">Zones</span>
                    <div class="lm-zones"><?php foreach($zones as $z): ?><span class="lm-zone"><?= htmlspecialchars($z) ?></span><?php endforeach; ?></div>
                </div>
                <?php if(!empty($layout['description'])): ?>
                <div class="lm-desc"><?= htmlspecialchars($layout['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="lm-card-ft">
            <div><?php if($isDef): ?><span class="lm-default"><i class="fas fa-star"></i> Par défaut</span><?php endif; ?></div>
            <div class="lm-actions">
                <?php if(!$isDef): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Définir par défaut pour « <?= $ctx['label'] ?> » ?');">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action"  value="set_default">
                    <input type="hidden" name="id"      value="<?= $layout['id'] ?>">
                    <input type="hidden" name="context" value="<?= $layout['context'] ?>">
                    <button type="submit" class="lm-ico str" title="Définir par défaut"><i class="fas fa-star"></i></button>
                </form>
                <?php endif; ?>
                <a href="layouts.php?edit=<?= $layout['id'] ?>" class="lm-ico" title="Modifier"><i class="fas fa-edit"></i></a>
                <?php if(!$isDef&&$tplCount===0): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($layout['name'])) ?> » ?');">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id"     value="<?= $layout['id'] ?>">
                    <button type="submit" class="lm-ico d" title="Supprimer"><i class="fas fa-trash"></i></button>
                </form>
                <?php else: ?>
                <span class="lm-ico off" title="<?= $isDef?'Layout par défaut':'Utilisé par des templates' ?>"><i class="fas fa-trash"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- .lm-page -->

<script>
function toggleForm(){
    var f=document.getElementById('layoutForm');
    f.style.display=f.style.display==='none'?'block':'none';
    if(f.style.display==='block') window.scrollTo({top:0,behavior:'smooth'});
}
</script>

<?php if($standaloneMode): ?>
    </main>
</div>
</body>
</html>
<?php endif; ?>
<?php
$content = ob_get_clean();
if(defined('ADMIN_ROUTER')&&file_exists(__DIR__.'/../../../includes/layout.php')){
    $pageTitle='Gestion des Layouts';
    require_once __DIR__.'/../../../includes/layout.php';
} else {
    echo $content;
}
?>