<?php
/**
 * ══════════════════════════════════════════════════════════════
 * BUILDER PRO — Bibliothèque de Templates
 * /admin/modules/builder/templates.php
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
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        $flash = 'Erreur de sécurité (CSRF).';
        $flashType = 'error';
    } else {
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalide.');
                    $check=$pdo->prepare("SELECT name FROM builder_templates WHERE id=?"); $check->execute([$id]); $name=$check->fetchColumn();
                    if(!$name) throw new Exception('Template introuvable.');
                    $pdo->prepare("DELETE FROM builder_templates WHERE id=?")->execute([$id]);
                    $flash="Template « {$name} » supprimé."; break;
                case 'duplicate':
                    $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalide.');
                    $orig=$pdo->prepare("SELECT * FROM builder_templates WHERE id=?"); $orig->execute([$id]); $tpl=$orig->fetch(PDO::FETCH_ASSOC);
                    if(!$tpl) throw new Exception('Template introuvable.');
                    $newName=$tpl['name'].' (copie)'; $newSlug=$tpl['slug'].'-copy-'.time();
                    $pdo->prepare("INSERT INTO builder_templates (name,slug,context,layout_id,description,html_content,css_content,js_content,thumbnail,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,'draft',NOW())")->execute([$newName,$newSlug,$tpl['context']??'page',$tpl['layout_id']??null,$tpl['description']??'',$tpl['html_content']??'',$tpl['css_content']??'',$tpl['js_content']??'',$tpl['thumbnail']??null]);
                    $flash="Template dupliqué : « {$newName} »."; break;
                case 'toggle_status':
                    $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalide.');
                    $cur=$pdo->prepare("SELECT status,name FROM builder_templates WHERE id=?"); $cur->execute([$id]); $row=$cur->fetch(PDO::FETCH_ASSOC);
                    if(!$row) throw new Exception('Template introuvable.');
                    $newStatus=($row['status']==='active')?'draft':'active';
                    $pdo->prepare("UPDATE builder_templates SET status=? WHERE id=?")->execute([$newStatus,$id]);
                    $flash="Template « {$row['name']} » → ".($newStatus==='active'?'Actif':'Brouillon'); break;
                case 'create':
                    $name=trim($_POST['name']??''); $context=trim($_POST['context']??'page'); $layoutId=(int)($_POST['layout_id']??0)?:null; $desc=trim($_POST['description']??'');
                    if(empty($name)) throw new Exception('Le nom est obligatoire.');
                    $slug=trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$name)),'-').'-'.time();
                    $pdo->prepare("INSERT INTO builder_templates (name,slug,context,layout_id,description,html_content,css_content,js_content,status,created_at) VALUES (?,?,?,?,?,'','','','draft',NOW())")->execute([$name,$slug,$context,$layoutId,$desc]);
                    $flash="Template « {$name} » créé."; break;
            }
        } catch(Exception $e){ $flash=$e->getMessage(); $flashType='error'; }
    }
}

if(!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));

$filterContext=$_GET['context']??'';
$filterStatus =$_GET['status']??'';
$search       =trim($_GET['q']??'');

try {
    $sql="SELECT t.*,l.name as layout_name FROM builder_templates t LEFT JOIN builder_layouts l ON t.layout_id=l.id WHERE 1=1";
    $params=[];
    if($filterContext&&in_array($filterContext,['page','article','secteur','capture','landing','header','footer'])){ $sql.=" AND t.context=?"; $params[]=$filterContext; }
    if($filterStatus&&in_array($filterStatus,['active','draft'])){ $sql.=" AND t.status=?"; $params[]=$filterStatus; }
    if($search){ $sql.=" AND (t.name LIKE ? OR t.slug LIKE ? OR t.description LIKE ?)"; $params[]="%{$search}%"; $params[]="%{$search}%"; $params[]="%{$search}%"; }
    $sql.=" ORDER BY t.created_at DESC";
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    $templates=$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){ $templates=[]; $flash="Table builder_templates introuvable."; $flashType='error'; }

$allLayouts=[];
try { $allLayouts=$pdo->query("SELECT id,name,context FROM builder_layouts ORDER BY context,name")->fetchAll(PDO::FETCH_ASSOC); } catch(PDOException $e){}

$totalActive=count(array_filter($templates,fn($t)=>($t['status']??'')==='active'));
$totalDraft =count($templates)-$totalActive;

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
<?php if($standaloneMode): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Templates — Builder Pro</title>
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
    $ns  = 'display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:#94a3b8;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s;margin-bottom:2px;';
    $na  = 'display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:#a78bfa;text-decoration:none;font-size:.875rem;font-weight:500;background:rgba(139,92,246,.15);margin-bottom:2px;';
    $is  = 'width:18px;text-align:center;font-size:.85rem;flex-shrink:0;';
    $st  = 'font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;color:#475569;font-weight:700;padding:0 8px;margin-bottom:6px;';
    ?>
    <div style="padding:20px 12px 8px;">
        <div style="<?= $st ?>">Navigation</div>
        <a href="/admin/dashboard.php"              style="<?= $ns ?>"><i class="fas fa-home"         style="<?= $is ?>"></i> Dashboard</a>
        <a href="/admin/dashboard.php?page=articles" style="<?= $ns ?>"><i class="fas fa-newspaper"   style="<?= $is ?>"></i> Articles</a>
        <a href="/admin/dashboard.php?page=contacts" style="<?= $ns ?>"><i class="fas fa-address-book" style="<?= $is ?>"></i> Contacts</a>
        <a href="/admin/dashboard.php?page=leads"    style="<?= $ns ?>"><i class="fas fa-funnel-dollar" style="<?= $is ?>"></i> Leads</a>
    </div>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 20px;"></div>
    <div style="padding:16px 12px 8px;">
        <div style="<?= $st ?>">Builder Pro</div>
        <a href="/admin/modules/builder/layouts.php"   style="<?= $ns ?>"><i class="fas fa-layer-group" style="<?= $is ?>"></i> Layouts</a>
        <a href="/admin/modules/builder/templates.php" style="<?= $na ?>">
            <i class="fas fa-swatchbook" style="<?= $is ?>"></i> Templates
            <span style="margin-left:auto;background:rgba(139,92,246,.2);color:#a78bfa;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:10px;"><?= count($templates) ?></span>
        </a>
        <a href="/admin/dashboard.php?page=builder" style="<?= $ns ?>"><i class="fas fa-magic" style="<?= $is ?>"></i> Builder</a>
    </div>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 20px;"></div>
    <div style="padding:16px 12px 8px;">
        <div style="<?= $st ?>">Paramètres</div>
        <a href="/admin/dashboard.php?page=settings" style="<?= $ns ?>"><i class="fas fa-cog"          style="<?= $is ?>"></i> Paramètres</a>
        <a href="/admin/logout.php"                  style="<?= $ns ?>"><i class="fas fa-sign-out-alt" style="<?= $is ?>"></i> Déconnexion</a>
    </div>
    <div style="margin-top:auto;padding:16px 20px;border-top:1px solid rgba(255,255,255,.07);font-size:.78rem;color:#475569;">Builder Pro v2.0</div>
</aside>

<!-- MAIN WRAPPER -->
<div style="margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh;">
    <header style="background:#fff;border-bottom:1px solid #e2e8f0;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;font-size:.875rem;color:#64748b;">
            <a href="/admin/dashboard.php" style="color:#64748b;text-decoration:none;"><i class="fas fa-home"></i></a>
            <span style="color:#cbd5e1;">›</span>
            <span>Builder Pro</span>
            <span style="color:#cbd5e1;">›</span>
            <span style="color:#1e293b;font-weight:600;">Templates</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <form method="GET" style="display:inline;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un template..."
                    style="padding:7px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;width:220px;color:#334155;">
                <?php if($filterContext):?><input type="hidden" name="context" value="<?= $filterContext ?>"><?php endif; ?>
                <?php if($filterStatus):?><input type="hidden" name="status" value="<?= $filterStatus ?>"><?php endif; ?>
            </form>
            <button onclick="toggleForm()" style="padding:8px 18px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-plus"></i> Nouveau
            </button>
        </div>
    </header>
    <main style="padding:28px;flex:1;">
<?php endif; ?>

<!-- ═══ CSS ALWAYS INJECTED ═══ -->
<style>
.tm-page * { box-sizing: border-box; }

/* Hero */
.tm-hero { background: linear-gradient(135deg,#4c1d95 0%,#7c3aed 60%,#a855f7 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; color: #fff; }
.tm-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
.tm-hero p  { color: #ddd6fe; margin: 0; font-size: .9rem; }
.tm-stats   { display: flex; gap: 16px; }
.tm-stat    { text-align: center; padding: 12px 20px; background: rgba(255,255,255,.12); border-radius: 10px; min-width: 80px; }
.tm-stat-v  { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.tm-stat-l  { font-size: .68rem; color: #ddd6fe; text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

/* Flash */
.tm-flash   { padding: 13px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; font-size: .9rem; }
.tm-flash.s { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.tm-flash.e { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Form panel */
.tm-panel   { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.tm-panel h2{ font-size: 1.1rem; font-weight: 700; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; color: #1e293b; }
.tm-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.tm-fg      { display: flex; flex-direction: column; gap: 5px; }
.tm-fg.full { grid-column: 1/-1; }
.tm-fg label{ font-size: .82rem; font-weight: 600; color: #475569; }
.tm-fg input,.tm-fg select,.tm-fg textarea {
    padding: 9px 13px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .875rem;
    color: #334155; background: #fff; transition: border-color .2s,box-shadow .2s; width: 100%;
}
.tm-fg input:focus,.tm-fg select:focus,.tm-fg textarea:focus {
    outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}
.tm-fg textarea { resize: vertical; min-height: 70px; }
.tm-fg small{ color: #94a3b8; font-size: .76rem; }
.tm-btns    { display: flex; gap: 10px; margin-top: 16px; }
.tm-btn-p   { padding: 9px 22px; background: #7c3aed; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: .875rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .2s; }
.tm-btn-p:hover { background: #6d28d9; }
.tm-btn-s   { padding: 9px 22px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500; font-size: .875rem; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all .2s; }
.tm-btn-s:hover { background: #e2e8f0; }

/* Toolbar */
.tm-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.tm-filters { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.tm-fbtn    { padding: 7px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: .82rem; font-weight: 500; cursor: pointer; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.tm-fbtn:hover  { border-color: #7c3aed; color: #7c3aed; background: #faf5ff; }
.tm-fbtn.on     { background: #7c3aed; color: #fff; border-color: #7c3aed; }
.tm-sep         { width: 1px; height: 28px; background: #e2e8f0; }

/* Grid templates */
.tm-grid-cards { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 18px; }

/* Card */
.tm-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; transition: all .25s; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.tm-card:hover { border-color: #c4b5fd; box-shadow: 0 6px 24px rgba(124,58,237,.1); transform: translateY(-2px); }

/* Thumb */
.tm-thumb { height: 150px; background: linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.tm-thumb img { width: 100%; height: 100%; object-fit: cover; }
.tm-thumb .no-img { color: #cbd5e1; font-size: 2.2rem; }
.tm-thumb-top { position: absolute; top: 10px; right: 10px; }
.tm-sbadge   { padding: 4px 10px; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
.tm-sbadge.active { background: #dcfce7; color: #166534; }
.tm-sbadge.draft  { background: #fef3c7; color: #92400e; }

/* Body */
.tm-body    { padding: 16px 18px; }
.tm-body h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 10px; line-height: 1.3; }
.tm-tags    { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
.tm-tag     { padding: 3px 10px; border-radius: 6px; font-size: .73rem; font-weight: 500; background: #f1f5f9; color: #475569; display: flex; align-items: center; gap: 4px; border-left: 3px solid transparent; }
.tm-desc    { font-size: .82rem; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Footer */
.tm-footer  { padding: 10px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.tm-date    { font-size: .76rem; color: #94a3b8; display: flex; align-items: center; gap: 5px; }
.tm-actions { display: flex; gap: 5px; }
.tm-ico     { width: 32px; height: 32px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; font-size: .8rem; text-decoration: none; }
.tm-ico:hover    { border-color: #7c3aed; color: #7c3aed; background: #faf5ff; }
.tm-ico.d:hover  { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
.tm-ico.ok:hover { border-color: #10b981; color: #10b981; background: #ecfdf5; }

/* Empty */
.tm-empty { text-align: center; padding: 60px 20px; color: #94a3b8; background: #fff; border-radius: 14px; border: 1px dashed #e2e8f0; }
.tm-empty i { font-size: 2.5rem; margin-bottom: 14px; display: block; opacity: .3; }
.tm-empty p { font-size: .95rem; margin-bottom: 16px; }

@media(max-width:768px){
    .tm-hero{flex-direction:column;gap:16px;}
    .tm-grid{grid-template-columns:1fr;}
    .tm-grid-cards{grid-template-columns:1fr;}
}
</style>

<div class="tm-page">

<!-- HERO -->
<div class="tm-hero">
    <div>
        <h1><i class="fas fa-swatchbook"></i> Bibliothèque de Templates</h1>
        <p>Pages pré-construites pour le Builder Pro — prêtes à personnaliser</p>
    </div>
    <div class="tm-stats">
        <div class="tm-stat"><div class="tm-stat-v"><?= count($templates) ?></div><div class="tm-stat-l">Templates</div></div>
        <div class="tm-stat"><div class="tm-stat-v"><?= $totalActive ?></div><div class="tm-stat-l">Actifs</div></div>
        <div class="tm-stat"><div class="tm-stat-v"><?= $totalDraft ?></div><div class="tm-stat-l">Brouillons</div></div>
    </div>
</div>

<!-- FLASH -->
<?php if($flash): ?>
<div class="tm-flash <?= $flashType==='success'?'s':'e' ?>">
    <i class="fas fa-<?= $flashType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($flash) ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="tm-panel" id="tplForm" style="<?= !isset($_GET['create'])?'display:none':'' ?>">
    <h2><i class="fas fa-plus-circle"></i> Nouveau template</h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action"     value="create">
        <div class="tm-grid">
            <div class="tm-fg">
                <label>Nom du template *</label>
                <input type="text" name="name" required placeholder="Ex: Landing Estimation Premium">
            </div>
            <div class="tm-fg">
                <label>Contexte</label>
                <select name="context">
                    <?php foreach($contexts as $key=>$ctx): ?>
                    <option value="<?= $key ?>" <?= $filterContext===$key?'selected':'' ?>><?= $ctx['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tm-fg">
                <label>Layout associé</label>
                <select name="layout_id">
                    <option value="">— Aucun (libre) —</option>
                    <?php foreach($allLayouts as $lay): ?>
                    <option value="<?= $lay['id'] ?>"><?= htmlspecialchars($lay['name']) ?> (<?= $lay['context'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small>Contraint les zones disponibles dans le Builder</small>
            </div>
            <div class="tm-fg full">
                <label>Description</label>
                <textarea name="description" placeholder="Description du template..."></textarea>
            </div>
        </div>
        <div class="tm-btns">
            <button type="submit" class="tm-btn-p"><i class="fas fa-plus"></i> Créer</button>
            <a href="#" class="tm-btn-s" onclick="document.getElementById('tplForm').style.display='none';return false;"><i class="fas fa-times"></i> Annuler</a>
        </div>
    </form>
</div>

<!-- TOOLBAR -->
<div class="tm-toolbar">
    <div class="tm-filters">
        <a href="templates.php" class="tm-fbtn <?= (!$filterContext&&!$filterStatus)?'on':'' ?>"><i class="fas fa-th"></i> Tous</a>
        <?php foreach($contexts as $key=>$ctx): ?>
        <a href="templates.php?context=<?= $key ?><?= $filterStatus?'&status='.$filterStatus:'' ?>"
           class="tm-fbtn <?= $filterContext===$key?'on':'' ?>">
            <i class="fas fa-<?= $ctx['icon'] ?>"></i> <?= $ctx['label'] ?>
        </a>
        <?php endforeach; ?>
        <div class="tm-sep"></div>
        <a href="templates.php?status=active<?= $filterContext?'&context='.$filterContext:'' ?>" class="tm-fbtn <?= $filterStatus==='active'?'on':'' ?>"><i class="fas fa-check-circle"></i> Actifs</a>
        <a href="templates.php?status=draft<?= $filterContext?'&context='.$filterContext:'' ?>"  class="tm-fbtn <?= $filterStatus==='draft'?'on':'' ?>"><i class="fas fa-pen"></i> Brouillons</a>
    </div>
</div>

<!-- GRID CARDS -->
<?php if(empty($templates)): ?>
<div class="tm-empty">
    <i class="fas fa-swatchbook"></i>
    <p>Aucun template trouvé<?= $filterContext?" pour « {$filterContext} »":'' ?><?= $search?" contenant « {$search} »":'' ?>.</p>
    <button class="tm-btn-p" onclick="toggleForm()"><i class="fas fa-plus"></i> Créer votre premier template</button>
</div>
<?php else: ?>
<div class="tm-grid-cards">
    <?php foreach($templates as $tpl):
        $ctx    = $contexts[$tpl['context']??'page']??['label'=>'Page','icon'=>'file','color'=>'#3b82f6'];
        $status = $tpl['status']??'draft';
        $thumb  = $tpl['thumbnail']??'';
        $date   = isset($tpl['created_at'])?date('d/m/Y',strtotime($tpl['created_at'])):'—';
    ?>
    <div class="tm-card">
        <div class="tm-thumb">
            <?php if($thumb&&file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$thumb)): ?>
                <img src="/<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($tpl['name']) ?>">
            <?php else: ?>
                <i class="fas fa-<?= $ctx['icon'] ?> no-img"></i>
            <?php endif; ?>
            <div class="tm-thumb-top">
                <span class="tm-sbadge <?= $status ?>"><?= $status==='active'?'Actif':'Brouillon' ?></span>
            </div>
        </div>
        <div class="tm-body">
            <h3><?= htmlspecialchars($tpl['name']) ?></h3>
            <div class="tm-tags">
                <span class="tm-tag" style="border-left-color:<?= $ctx['color'] ?>">
                    <i class="fas fa-<?= $ctx['icon'] ?>" style="color:<?= $ctx['color'] ?>"></i> <?= $ctx['label'] ?>
                </span>
                <?php if(!empty($tpl['layout_name'])): ?>
                <span class="tm-tag"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($tpl['layout_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php if(!empty($tpl['description'])): ?>
            <div class="tm-desc"><?= htmlspecialchars($tpl['description']) ?></div>
            <?php endif; ?>
        </div>
        <div class="tm-footer">
            <span class="tm-date"><i class="far fa-calendar"></i> <?= $date ?></span>
            <div class="tm-actions">
                <a href="/admin/dashboard.php?page=builder&type=template&action=edit&id=<?= $tpl['id'] ?>&context=<?= $tpl['context']??'page' ?>" class="tm-ico" title="Éditer dans le Builder"><i class="fas fa-edit"></i></a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id"     value="<?= $tpl['id'] ?>">
                    <button type="submit" class="tm-ico ok" title="<?= $status==='active'?'Passer en brouillon':'Activer' ?>">
                        <i class="fas fa-<?= $status==='active'?'toggle-on':'toggle-off' ?>"></i>
                    </button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="duplicate">
                    <input type="hidden" name="id"     value="<?= $tpl['id'] ?>">
                    <button type="submit" class="tm-ico" title="Dupliquer"><i class="fas fa-copy"></i></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($tpl['name'])) ?> » ?');">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id"     value="<?= $tpl['id'] ?>">
                    <button type="submit" class="tm-ico d" title="Supprimer"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- .tm-page -->

<script>
function toggleForm(){
    var f=document.getElementById('tplForm');
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
$content=ob_get_clean();
if(defined('ADMIN_ROUTER')&&file_exists(__DIR__.'/../../../includes/layout.php')){
    $pageTitle='Bibliothèque de Templates';
    require_once __DIR__.'/../../../includes/layout.php';
} else {
    echo $content;
}
?>