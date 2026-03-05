<?php

/**
 * Builder Pro Editor v3.8
 * 
 * CORRECTIONS APPLIQUÉES :
 * 1. require init.php : /../../../ au lieu de /../../ (3 niveaux pour remonter de builder/builder/ à admin/)
 * 2. Table seo_scores : création automatique si absente
 * 3. Asset design-clone.css : fallback inline si fichier manquant
 */

require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/BuilderController.php';

$context  = $_GET['context'] ?? '';
$entityId = (int)($_GET['entity_id'] ?? 0);
if (!in_array($context, BuilderController::CONTEXTS) || !$entityId) {
    header('Location: /admin/dashboard.php?page=builder&error=params'); exit;
}

$builder     = new BuilderController($pdo);
$layouts     = $builder->getLayouts($context);
$templates   = $builder->getTemplates($context);
$blocks      = $builder->getBlockTypes($context);
$content     = $builder->loadContent($context, $entityId);
$saved       = $builder->getSavedBlocks($context);
$entityTitle = $builder->getEntityTitle($context, $entityId);

$currentLayoutId = $content['layout_id'] ?? ($layouts[0]['id'] ?? 0);
$currentLayout = null;
foreach ($layouts as $l) { if ($l['id'] == $currentLayoutId) { $currentLayout = $l; break; } }
if (!$currentLayout) {
    $currentLayout = $layouts[0] ?? ['header_config'=>['type'=>'site'],'footer_config'=>['type'=>'site'],'page_config'=>['maxWidth'=>'1200px']];
}

$entityHtml=$entityCss=$entityJs='';
$sourceTable = match($context) { 'landing'=>'pages','article'=>'articles','secteur'=>'secteurs','capture'=>'captures',default=>'pages' };
$cols = [];
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `{$sourceTable}`")->fetchAll(PDO::FETCH_COLUMN);
    $htmlCol='content';
    if (in_array('html_content',$cols)) $htmlCol='html_content';
    elseif (in_array('html_capture',$cols)) $htmlCol='html_capture';
    $cssCol=in_array('custom_css',$cols)?'custom_css':null;
    $jsCol=in_array('custom_js',$cols)?'custom_js':null;
    $selectCols=["`{$htmlCol}` AS html_content"];
    if ($cssCol) $selectCols[]="`{$cssCol}` AS custom_css";
    if ($jsCol) $selectCols[]="`{$jsCol}` AS custom_js";
    $stmt=$pdo->prepare("SELECT ".implode(',',$selectCols)." FROM `{$sourceTable}` WHERE id = ?");
    $stmt->execute([$entityId]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) { $entityHtml=$row['html_content']??''; $entityCss=$row['custom_css']??''; $entityJs=$row['custom_js']??''; }
} catch (PDOException $e) {}

$headers=[];
foreach (['site_headers','headers','builder_pages'] as $htable) {
    try {
        $stmt = ($htable==='builder_pages')
            ? $pdo->prepare("SELECT id, title AS name, slug, status FROM `{$htable}` WHERE type='header' AND status='active' ORDER BY id")
            : $pdo->prepare("SELECT id, name, slug, status FROM `{$htable}` WHERE status='active' ORDER BY is_default DESC, id");
        $stmt->execute(); $headers=$stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($headers)) break;
    } catch (PDOException $e) { continue; }
}
$footers=[];
foreach (['site_footers','footers','builder_pages'] as $ftable) {
    try {
        $stmt = ($ftable==='builder_pages')
            ? $pdo->prepare("SELECT id, title AS name, slug, status FROM `{$ftable}` WHERE type='footer' AND status='active' ORDER BY id")
            : $pdo->prepare("SELECT id, name, slug, status FROM `{$ftable}` WHERE status='active' ORDER BY is_default DESC, id");
        $stmt->execute(); $footers=$stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($footers)) break;
    } catch (PDOException $e) { continue; }
}

$currentHeaderId=$content['header_id']??null;
$currentFooterId=$content['footer_id']??null;

// FIX #2 : Table seo_scores — création auto si absente
$seoScore=null;
try {
    $stmt=$pdo->prepare("SELECT * FROM seo_scores WHERE context=? AND entity_id=? LIMIT 1");
    $stmt->execute([$context,$entityId]);
    $seoScore=$stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table n'existe pas — on la crée
    if (strpos($e->getMessage(), '1146') !== false || strpos($e->getMessage(), 'doesn\'t exist') !== false) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `seo_scores` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `context` VARCHAR(50) NOT NULL,
                `entity_id` INT NOT NULL,
                `score_global` INT DEFAULT 0,
                `score_technique` INT DEFAULT 0,
                `score_contenu` INT DEFAULT 0,
                `score_semantique` INT DEFAULT 0,
                `focus_keyword` VARCHAR(255) DEFAULT NULL,
                `details` JSON DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `ctx_entity` (`context`, `entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (PDOException $e2) {
            // Silencieux — pas critique
        }
    }
}

$contextLabels=['article'=>['label'=>'Article','icon'=>'fa-newspaper','color'=>'#3498db'],'capture'=>['label'=>'Page Capture','icon'=>'fa-magnet','color'=>'#e74c3c'],'landing'=>['label'=>'Landing Page','icon'=>'fa-rocket','color'=>'#f39c12'],'secteur'=>['label'=>'Secteur','icon'=>'fa-map-marker-alt','color'=>'#27ae60']];
$ctx=$contextLabels[$context]??['label'=>ucfirst($context),'icon'=>'fa-file','color'=>'#6c757d'];
$backUrls=['article'=>'/admin/dashboard.php?page=articles','landing'=>'/admin/dashboard.php?page=pages','secteur'=>'/admin/dashboard.php?page=secteurs','capture'=>'/admin/dashboard.php?page=pages-capture'];
$backUrl=$backUrls[$context]??'/admin/dashboard.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$claudeApiKey=defined('ANTHROPIC_API_KEY')?ANTHROPIC_API_KEY:'';
$entitySlug='';
try { if (in_array('slug',$cols)) { $stmt=$pdo->prepare("SELECT slug FROM `{$sourceTable}` WHERE id=?"); $stmt->execute([$entityId]); $entitySlug=$stmt->fetchColumn()?:''; } } catch (PDOException $e) {}
$previewUrl=match($context) { 'landing'=>"/{$entitySlug}",'secteur'=>"/secteurs/{$entitySlug}",'article'=>"/blog/{$entitySlug}",'capture'=>"/c/{$entitySlug}",default=>"/{$entitySlug}" };

// FIX #3 : Vérifier si design-clone.css existe
$designCloneCssExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/admin/modules/builder/assets/css/design-clone.css');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder Pro — <?= htmlspecialchars($entityTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if ($designCloneCssExists): ?>
    <link rel="stylesheet" href="/admin/modules/builder/assets/css/design-clone.css">
    <?php endif; ?>
    <style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--primary:#3b82f6;--secondary:#8b5cf6;--success:#10b981;--warning:#f59e0b;--danger:#ef4444;--dark:#1e293b;--darker:#0f172a;--light:#f8fafc;--border:#e2e8f0;--text:#1e293b;--text-light:#64748b;--ctx:<?= $ctx['color'] ?>;--code-bg:#1e1e2e;--code-text:#cdd6f4;--sidebar-w:280px;--toolbar-h:52px}
body{font-family:'Inter',sans-serif;background:var(--light);color:var(--text);overflow:hidden;height:100vh}
.toolbar{display:flex;align-items:center;justify-content:space-between;height:var(--toolbar-h);background:#fff;border-bottom:1px solid var(--border);padding:0 12px;position:fixed;top:0;left:0;right:0;z-index:1000}
.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:8px}
.toolbar-center{display:flex;align-items:center;gap:2px;background:var(--light);border-radius:8px;padding:3px}
.tb-back{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);text-decoration:none;border:1px solid var(--border);transition:all .15s}.tb-back:hover{border-color:var(--primary);color:var(--primary)}
.tb-badge{padding:4px 12px;border-radius:6px;font-size:11px;font-weight:700;color:#fff;display:flex;align-items:center;gap:5px}
.tb-title{font-size:13px;font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tb-status{font-size:11px;display:flex;align-items:center;gap:4px}
.status-pill{padding:2px 10px;border-radius:10px;font-size:10px;font-weight:700}.status-pill.draft{background:#fef3c7;color:#d97706}.status-pill.published{background:#dcfce7;color:#16a34a}.status-pill.new{background:#e0e7ff;color:#4f46e5}
.mode-btn{padding:7px 14px;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;color:var(--text-light);background:transparent;font-family:inherit;display:flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap}.mode-btn:hover{color:var(--text)}.mode-btn.active{background:#fff;color:var(--primary);box-shadow:0 1px 3px rgba(0,0,0,.08)}.mode-btn i{font-size:12px}
.btn{padding:7px 14px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:#fff;color:var(--text);display:inline-flex;align-items:center;gap:5px;font-family:inherit;transition:all .15s;text-decoration:none}.btn:hover{border-color:var(--primary);color:var(--primary)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;border:none}.btn-primary:hover{box-shadow:0 4px 12px rgba(59,130,246,.3);transform:translateY(-1px);color:#fff}
.btn-dark{background:var(--dark);color:#fff;border:none}.btn-dark:hover{background:#334155;color:#fff}
.btn-xs{padding:4px 10px;font-size:10px}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text-light)}.btn-outline:hover{border-color:var(--primary);color:var(--primary)}
.btn-clone{background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:none}.btn-clone:hover{box-shadow:0 4px 12px rgba(139,92,246,.3);transform:translateY(-1px);color:#fff}
.seo-quick{display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;cursor:pointer;transition:all .15s;font-size:11px;font-weight:700}.seo-quick:hover{background:var(--light)}.seo-quick .score-val{font-size:13px}
.layout-dropdown{position:relative}.layout-menu{position:absolute;top:100%;right:0;margin-top:4px;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.12);width:300px;z-index:500;overflow:hidden;display:none}.layout-menu.open{display:block}
.layout-menu-section{padding:10px 14px;border-bottom:1px solid #f1f5f9}.layout-menu-section:last-child{border-bottom:none}.layout-menu-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:6px}
.layout-menu select{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:inherit;background:#fff}
.layout-menu-link{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;font-size:12px;color:var(--text);font-weight:500;text-decoration:none;transition:all .15s;cursor:pointer;border:none;background:none;width:100%;font-family:inherit}.layout-menu-link:hover{background:#f8fafc}.layout-menu-link i{width:18px;text-align:center;font-size:12px}
.layout-menu-link.clone-link{font-weight:600}.layout-menu-link.clone-link:hover{background:#faf5ff}.layout-menu-link.clone-link i{color:#ec4899}
.ai-suggest.clone-suggest{background:linear-gradient(135deg,#faf5ff,#fce7f3);border-color:#e9d5ff}.ai-suggest.clone-suggest:hover{border-color:#c084fc}
.workspace{display:flex;height:calc(100vh - var(--toolbar-h));margin-top:var(--toolbar-h)}
.sidebar{width:var(--sidebar-w);background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;overflow:hidden;transition:width .25s}.sidebar.collapsed{width:0;border:none}
.sidebar-tabs{display:flex;border-bottom:1px solid var(--border);flex-shrink:0}
.sidebar-tab{flex:1;padding:10px 6px;border:none;background:transparent;font-size:10px;font-weight:600;color:var(--text-light);cursor:pointer;border-bottom:2px solid transparent;font-family:inherit;display:flex;flex-direction:column;align-items:center;gap:3px;transition:all .15s}.sidebar-tab:hover{color:var(--text)}.sidebar-tab.active{color:var(--primary);border-bottom-color:var(--primary)}.sidebar-tab i{font-size:14px}
.sidebar-panel{display:none;overflow-y:auto;flex:1}.sidebar-panel.active{display:block}
.sidebar-search{padding:8px 10px}.sidebar-search input{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:11px;font-family:inherit}.sidebar-search input:focus{outline:none;border-color:var(--primary)}
.block-cat{margin-bottom:2px}.block-cat-header{padding:8px 12px;font-size:9px;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;gap:5px;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.block-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:6px 8px}
.block-item{padding:10px 6px;border:1px solid var(--border);border-radius:7px;text-align:center;cursor:grab;transition:all .15s;font-size:10px;background:#fff}.block-item:hover{border-color:var(--primary);background:#eff6ff;transform:translateY(-1px)}.block-item i{display:block;font-size:16px;margin-bottom:3px;color:var(--primary)}.block-item span{color:var(--text);font-weight:500}
.tpl-item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:all .15s}.tpl-item:hover{background:#f8fafc}
.tpl-thumb{width:44px;height:32px;border-radius:5px;background:#f1f5f9;flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden}.tpl-thumb img{width:100%;height:100%;object-fit:cover}
.tpl-info{flex:1;min-width:0}.tpl-info strong{font-size:11px;display:block}.tpl-info small{font-size:9px;color:var(--text-light)}
.saved-item{display:flex;align-items:center;gap:6px;padding:8px 12px;border-bottom:1px solid #f1f5f9;cursor:grab}.saved-item:hover{background:#f8fafc}.saved-item i.fa-bookmark{color:var(--warning);font-size:12px}.saved-item span{flex:1;font-size:11px;font-weight:500}
.saved-del{border:none;background:none;color:#cbd5e1;cursor:pointer;padding:4px}.saved-del:hover{color:var(--danger)}
.text-muted{color:var(--text-light);font-size:12px;padding:16px 12px}
.main-area{flex:1;display:flex;flex-direction:column;overflow:hidden;position:relative}
.mode-panel{display:none;flex:1;overflow:hidden}.mode-panel.active{display:flex;flex-direction:column}
.canvas{flex:1;overflow-y:auto;background:#e2e8f0;padding:16px}
.canvas-header,.canvas-footer{background:#fff;border:1px dashed #cbd5e1;border-radius:6px;margin-bottom:8px;padding:8px 14px;font-size:11px;color:var(--text-light);display:flex;align-items:center;gap:5px}.canvas-footer{margin-top:8px;margin-bottom:0}
.canvas-content{background:#fff;border-radius:8px;border:1px solid #cbd5e1;min-height:400px;padding:16px;position:relative}
.canvas-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:350px;color:var(--text-light);text-align:center;gap:10px}.canvas-empty i{font-size:42px;opacity:.25}.canvas-empty p{font-size:13px}
.drop-zone{min-height:60px;border:2px dashed transparent;border-radius:6px;transition:all .2s}.drop-zone.drag-over{border-color:var(--primary);background:rgba(59,130,246,.05)}
.code-panel{flex:1;display:flex;flex-direction:column;overflow:hidden}
.code-tabs{display:flex;background:var(--code-bg);border-bottom:1px solid #313244;flex-shrink:0;padding:0 8px}
.code-tab{padding:10px 18px;border:none;background:transparent;color:#6c7086;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;border-bottom:2px solid transparent;display:flex;align-items:center;gap:6px;transition:all .15s}.code-tab:hover{color:var(--code-text)}.code-tab.active{color:#89b4fa;border-bottom-color:#89b4fa;background:rgba(137,180,250,.08)}
.code-tab .dot{width:8px;height:8px;border-radius:50%}.code-tab .dot.html{background:#fab387}.code-tab .dot.css{background:#89b4fa}.code-tab .dot.js{background:#a6e3a1}
.code-area{flex:1;display:none;position:relative}.code-area.active{display:flex;flex-direction:column}
.code-area textarea{flex:1;background:var(--code-bg);color:var(--code-text);border:none;padding:16px;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.7;resize:none;tab-size:2}.code-area textarea:focus{outline:none}
.code-status{display:flex;justify-content:space-between;padding:6px 14px;background:#181825;color:#6c7086;font-size:10px;font-family:'JetBrains Mono',monospace;border-top:1px solid #313244;flex-shrink:0}
.preview-panel{flex:1;display:flex;background:#e2e8f0}
.preview-sidebar{width:260px;background:#fff;border-right:1px solid var(--border);overflow-y:auto;padding:14px;flex-shrink:0}
.preview-sidebar .ps-section{margin-bottom:16px}.preview-sidebar .ps-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:8px;display:flex;align-items:center;gap:5px}.preview-sidebar .ps-title i{font-size:11px}
.preview-sidebar select,.preview-sidebar input[type="text"]{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:inherit;background:#fff}
.preview-sidebar .ps-row{display:flex;gap:6px;margin-top:6px}.preview-sidebar .ps-row .btn{flex:1;justify-content:center;font-size:10px;padding:5px 8px}
.preview-sidebar .ps-check{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text-light);cursor:pointer;margin-top:6px}.preview-sidebar .ps-check input{accent-color:var(--primary)}
.preview-right{flex:1;display:flex;flex-direction:column}
.device-btn{width:34px;height:34px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;color:var(--text-light);display:flex;align-items:center;justify-content:center;transition:all .15s}.device-btn:hover{border-color:var(--primary);color:var(--primary)}.device-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.preview-frame-wrap{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:16px;overflow:auto}
.preview-frame{background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.08);transition:width .3s;width:100%;height:100%}.preview-frame.tablet{width:768px}.preview-frame.mobile{width:375px}
.preview-frame iframe{width:100%;height:100%;border:none;border-radius:8px}
.sections-panel{flex:1;overflow-y:auto;padding:16px;background:#f8fafc}
.section-block{background:#fff;border:1px solid var(--border);border-radius:10px;margin-bottom:12px;overflow:hidden}.section-block:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}
.section-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border-bottom:1px solid var(--border);cursor:pointer}.section-header h4{font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px}.section-header h4 i{color:var(--primary)}
.section-toggle{border:none;background:none;cursor:pointer;color:var(--text-light);font-size:12px;transition:transform .2s}.section-toggle.open{transform:rotate(180deg)}
.section-body{padding:16px;display:none}.section-body.open{display:block}
.section-field{margin-bottom:14px}.section-field label{display:block;font-size:11px;font-weight:600;color:var(--text-light);margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px}
.section-field input,.section-field textarea,.section-field select{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit}.section-field input:focus,.section-field textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.section-field textarea{min-height:80px;resize:vertical}
.ai-panel{flex:1;display:flex;flex-direction:column;overflow:hidden}
.ai-chat{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px}
.ai-suggest{padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;text-align:left;font-size:11px;color:var(--text);font-family:inherit;transition:all .15s}.ai-suggest:hover{border-color:var(--secondary);background:#f5f3ff;transform:translateY(-1px)}.ai-suggest strong{display:block;margin-bottom:2px;color:var(--secondary)}
.ai-msg{padding:12px 16px;border-radius:10px;font-size:13px;line-height:1.6;max-width:85%}.ai-msg.user{background:var(--primary);color:#fff;align-self:flex-end}.ai-msg.assistant{background:#fff;border:1px solid var(--border);align-self:flex-start}
.ai-msg.loading{color:var(--text-light)}.ai-msg.loading::after{content:'';display:inline-block;width:12px;height:12px;border:2px solid var(--text-light);border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite;margin-left:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
.ai-input-bar{display:flex;gap:8px;padding:12px 16px;background:#fff;border-top:1px solid var(--border);flex-shrink:0}
.ai-input-bar textarea{flex:1;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:none;min-height:44px;max-height:120px}.ai-input-bar textarea:focus{outline:none;border-color:var(--secondary)}
.ai-send{padding:10px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap}.ai-send:hover{box-shadow:0 4px 12px rgba(139,92,246,.3);transform:translateY(-1px)}.ai-send:disabled{opacity:.5;cursor:not-allowed}
.seo-panel{flex:1;overflow-y:auto;padding:20px;background:#f8fafc}.seo-container{max-width:900px;margin:0 auto}
.seo-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:16px}.seo-card h3{font-size:14px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.score-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px}.score-item{text-align:center}
.score-circle{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;margin:0 auto 6px;border:4px solid var(--border)}.score-circle.good{color:var(--success);border-color:var(--success)}.score-circle.ok{color:var(--warning);border-color:var(--warning)}.score-circle.bad{color:var(--danger);border-color:var(--danger)}
.score-label{font-size:10px;color:var(--text-light);text-transform:uppercase;font-weight:600}
.semantic-bar{height:24px;background:#e2e8f0;border-radius:12px;overflow:hidden;position:relative;margin:10px 0}
.semantic-fill{height:100%;border-radius:12px;transition:width .6s;display:flex;align-items:center;justify-content:flex-end;padding-right:10px;font-size:11px;font-weight:700;color:#fff}.semantic-fill.good{background:linear-gradient(90deg,var(--success),#059669)}.semantic-fill.ok{background:linear-gradient(90deg,var(--warning),#d97706)}.semantic-fill.bad{background:linear-gradient(90deg,var(--danger),#dc2626)}
.semantic-target{position:absolute;top:0;bottom:0;width:2px;background:var(--text)}.semantic-target-lbl{position:absolute;top:-16px;font-size:9px;color:var(--text-light);transform:translateX(-50%)}
.seo-checklist{list-style:none}.seo-checklist li{padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;align-items:center;gap:8px}.seo-checklist li:last-child{border:none}
.kw-tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}.kw-tag{background:#f1f5f9;border:1px solid var(--border);padding:2px 10px;border-radius:20px;font-size:11px;color:var(--text-light)}.kw-tag.primary{border-color:var(--primary);color:var(--primary);background:#eff6ff}.kw-tag .cnt{font-weight:700;margin-left:3px;color:var(--text)}
.seo-meta-field{margin-bottom:12px}.seo-meta-field label{display:block;font-size:11px;font-weight:600;color:var(--text-light);margin-bottom:4px}.seo-meta-field input,.seo-meta-field textarea{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit}.seo-meta-field .char-count{float:right;font-size:10px;color:var(--text-light)}
.config-panel{width:280px;background:#fff;border-left:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;overflow:hidden;transition:width .25s}.config-panel.collapsed{width:0;border:none}
.config-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;color:var(--text-light);text-align:center;padding:24px;gap:8px}.config-empty i{font-size:28px;opacity:.25}.config-empty p{font-size:12px}
.preview-loading{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.85);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:8px}.preview-loading.hidden{display:none}
.preview-spinner{text-align:center;color:var(--text-light)}.preview-spinner i{font-size:24px;margin-bottom:8px;display:block;color:var(--primary)}.preview-spinner span{font-size:12px;font-weight:500}
<?php if (!$designCloneCssExists): ?>
/* Fallback design-clone styles */
.design-clone-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center}
.design-clone-modal.open{display:flex}
.design-clone-content{background:#fff;border-radius:12px;width:90%;max-width:600px;max-height:80vh;overflow-y:auto;padding:24px}
<?php endif; ?>
@media(max-width:1024px){.sidebar{width:220px}.config-panel{width:0;border:none}.preview-sidebar{width:200px}}
@media(max-width:768px){.sidebar,.config-panel,.preview-sidebar{display:none}}
    </style>
</head>
<body>
<div class="toolbar">
    <div class="toolbar-left">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="tb-back"><i class="fas fa-arrow-left"></i></a>
        <span class="tb-badge" style="background:var(--ctx)"><i class="fas <?= $ctx['icon'] ?>"></i> <?= $ctx['label'] ?></span>
        <span class="tb-title"><?= htmlspecialchars($entityTitle) ?></span>
        <span class="tb-status"><?php if ($content): ?><span class="status-pill <?= $content['status']??'draft' ?>"><?= ucfirst($content['status']??'draft') ?></span><small style="color:var(--text-light)">v<?= (int)($content['version']??1) ?></small><?php else: ?><span class="status-pill new">Nouveau</span><?php endif; ?></span>
    </div>
    <div class="toolbar-center">
        <button class="mode-btn active" data-mode="preview"><i class="fas fa-eye"></i> Preview</button>
        <button class="mode-btn" data-mode="blocks"><i class="fas fa-th-large"></i> Blocs</button>
        <button class="mode-btn" data-mode="code"><i class="fas fa-code"></i> Code</button>
        <button class="mode-btn" data-mode="sections"><i class="fas fa-paragraph"></i> Sections</button>
        <button class="mode-btn" data-mode="ai"><i class="fas fa-wand-magic-sparkles"></i> IA</button>
        <button class="mode-btn" data-mode="seo"><i class="fas fa-search"></i> SEO</button>
    </div>
    <div class="toolbar-right">
        <div class="seo-quick" onclick="BP.switchMode('seo')" title="Score SEO"><i class="fas fa-chart-line" style="color:var(--text-light);font-size:12px"></i><span class="score-val" id="seoQuickScore" style="color:var(--text-light)">--</span></div>
        <div class="layout-dropdown" id="layoutDropdown">
            <button class="btn btn-xs btn-outline" onclick="this.nextElementSibling.classList.toggle('open')" title="Layout"><i class="fas fa-layer-group"></i><span id="currentLayoutName"><?= htmlspecialchars($currentLayout['name']??'Défaut') ?></span><i class="fas fa-caret-down" style="font-size:9px;opacity:.5"></i></button>
            <div class="layout-menu" id="layoutMenu">
                <div class="layout-menu-section">
                    <div class="layout-menu-label"><i class="fas fa-layer-group"></i> Layout actif</div>
                    <select id="layoutSelect" onchange="document.getElementById('currentLayoutName').textContent=this.options[this.selectedIndex].text.replace('★','').trim()">
                        <?php foreach ($layouts as $l): ?><option value="<?= (int)$l['id'] ?>" <?= $l['id']==$currentLayoutId?'selected':'' ?>><?= htmlspecialchars($l['name']) ?> <?= !empty($l['is_default'])?'★':'' ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="layout-menu-section" style="padding:6px 8px">
                    <a href="/admin/modules/builder/builder/layouts.php" class="layout-menu-link" target="_blank"><i class="fas fa-palette" style="color:var(--secondary)"></i> Gérer les layouts</a>
                    <a href="/admin/modules/builder/builder/templates.php" class="layout-menu-link" target="_blank"><i class="fas fa-swatchbook" style="color:var(--primary)"></i> Bibliothèque templates</a>
                    <button class="layout-menu-link clone-link" onclick="BP.openDesignCloner();document.getElementById('layoutMenu').classList.remove('open')"><i class="fas fa-wand-magic-sparkles"></i> Cloner le design d'une page</button>
                </div>
            </div>
        </div>
        <button class="btn btn-xs btn-clone" onclick="BP.openDesignCloner()" title="Cloner le design d'une autre page"><i class="fas fa-palette"></i> Cloner</button>
        <button class="btn btn-dark" id="btnSave"><i class="fas fa-save"></i> Sauver</button>
        <button class="btn btn-primary" id="btnPublish"><i class="fas fa-check-circle"></i> Publier</button>
    </div>
</div>
<div class="workspace">
    <aside class="sidebar collapsed" id="sidebar">
        <div class="sidebar-tabs">
            <button class="sidebar-tab active" data-stab="blocks"><i class="fas fa-cube"></i>Blocs</button>
            <button class="sidebar-tab" data-stab="templates"><i class="fas fa-swatchbook"></i>Templates</button>
            <button class="sidebar-tab" data-stab="saved"><i class="fas fa-bookmark"></i>Sauvés</button>
        </div>
        <div class="sidebar-panel active" data-spanel="blocks">
            <div class="sidebar-search"><input type="text" id="blockSearch" placeholder="Rechercher..."></div>
            <?php if (empty($blocks)): ?><p class="text-muted">Aucun bloc configuré.</p>
            <?php else: foreach ($blocks as $catKey=>$cat): ?><div class="block-cat"><div class="block-cat-header"><i class="fas <?= htmlspecialchars($cat['icon']??'fa-puzzle-piece') ?>"></i> <?= htmlspecialchars($cat['label']??ucfirst($catKey)) ?></div><div class="block-grid"><?php foreach ($cat['blocks'] as $b): ?><div class="block-item" draggable="true" data-type="<?= htmlspecialchars($b['slug']) ?>" data-config='<?= json_encode($b['default_config'],JSON_HEX_APOS) ?>'><i class="fas <?= htmlspecialchars($b['icon']??'fa-cube') ?>"></i><span><?= htmlspecialchars($b['name']) ?></span></div><?php endforeach; ?></div></div><?php endforeach; endif; ?>
        </div>
        <div class="sidebar-panel" data-spanel="templates">
            <?php if (empty($templates)): ?><p class="text-muted">Aucun template disponible.</p>
            <?php else: foreach ($templates as $tpl): ?><div class="tpl-item" onclick="BP.loadTemplate(<?=(int)$tpl['id']?>)"><div class="tpl-thumb"><?php if(!empty($tpl['thumbnail'])):?><img src="<?=htmlspecialchars($tpl['thumbnail'])?>"><?php else:?><i class="fas fa-file-alt" style="color:#cbd5e1"></i><?php endif;?></div><div class="tpl-info"><strong><?=htmlspecialchars($tpl['name'])?></strong><small><?=count($tpl['blocks_data']??[])?> blocs</small></div></div><?php endforeach; endif; ?>
        </div>
        <div class="sidebar-panel" data-spanel="saved">
            <?php if (empty($saved)): ?><p class="text-muted">Aucun bloc sauvegardé.</p>
            <?php else: foreach ($saved as $sb): ?><div class="saved-item" draggable="true" data-type="<?=htmlspecialchars($sb['block_type'])?>" data-config='<?=json_encode($sb['block_data'],JSON_HEX_APOS)?>'><i class="fas fa-bookmark"></i><span><?=htmlspecialchars($sb['name'])?></span><button class="saved-del" onclick="event.stopPropagation();BP.deleteSaved(<?=(int)$sb['id']?>)"><i class="fas fa-trash"></i></button></div><?php endforeach; endif; ?>
        </div>
    </aside>
    <div class="main-area">
        <!-- PREVIEW -->
        <div class="mode-panel active" id="mode-preview"><div class="preview-panel">
            <div class="preview-sidebar">
                <div class="ps-section"><div class="ps-title"><i class="fas fa-window-maximize"></i> Header</div>
                    <select id="headerSelect" onchange="BP.refreshPreview()"><option value="default" <?=!$currentHeaderId?'selected':''?>>🌐 Header par défaut</option><option value="">— Aucun —</option><?php foreach($headers as $h):?><option value="<?=$h['id']?>" <?=$currentHeaderId==$h['id']?'selected':''?>><?=htmlspecialchars($h['name'])?></option><?php endforeach;?></select>
                    <div class="ps-row"><button class="btn btn-outline btn-xs" onclick="BP.editHeader()"><i class="fas fa-edit"></i> Éditer</button><button class="btn btn-outline btn-xs" onclick="BP.createHeader()"><i class="fas fa-plus"></i> Nouveau</button></div>
                </div>
                <div class="ps-section"><div class="ps-title"><i class="fas fa-window-minimize"></i> Footer</div>
                    <select id="footerSelect" onchange="BP.refreshPreview()"><option value="default" <?=!$currentFooterId?'selected':''?>>🌐 Footer par défaut</option><option value="">— Aucun —</option><?php foreach($footers as $f):?><option value="<?=$f['id']?>" <?=$currentFooterId==$f['id']?'selected':''?>><?=htmlspecialchars($f['name'])?></option><?php endforeach;?></select>
                    <div class="ps-row"><button class="btn btn-outline btn-xs" onclick="BP.editFooter()"><i class="fas fa-edit"></i> Éditer</button><button class="btn btn-outline btn-xs" onclick="BP.createFooter()"><i class="fas fa-plus"></i> Nouveau</button></div>
                </div>
                <div class="ps-section"><div class="ps-title"><i class="fas fa-palette"></i> Styles</div><label class="ps-check"><input type="checkbox" id="globalStylesToggle" checked onchange="BP.refreshPreview()"> Styles globaux</label></div>
                <div class="ps-section"><div class="ps-title"><i class="fas fa-desktop"></i> Aperçu</div><div style="display:flex;gap:6px"><button class="device-btn active" data-device="desktop" title="Desktop"><i class="fas fa-desktop"></i></button><button class="device-btn" data-device="tablet" title="Tablette"><i class="fas fa-tablet-alt"></i></button><button class="device-btn" data-device="mobile" title="Mobile"><i class="fas fa-mobile-alt"></i></button><div style="flex:1"></div><button class="device-btn" onclick="BP.refreshPreview()" title="Rafraîchir"><i class="fas fa-sync-alt"></i></button><a href="<?=$previewUrl?>" target="_blank" class="device-btn" title="Ouvrir"><i class="fas fa-external-link-alt"></i></a></div></div>
                <div class="ps-section"><div class="ps-title"><i class="fas fa-wand-magic-sparkles"></i> IA Design</div><button class="btn btn-clone btn-xs" onclick="BP.openDesignCloner()" style="width:100%;justify-content:center"><i class="fas fa-palette"></i> Cloner le design d'une page</button></div>
                <div class="ps-section"><div class="ps-title"><i class="fas fa-info-circle"></i> Debug</div><div id="previewDebug" style="font-size:10px;color:var(--text-light);background:#f8fafc;padding:8px;border-radius:6px;word-break:break-all">—</div></div>
            </div>
            <div class="preview-right"><div class="preview-frame-wrap"><div class="preview-frame" id="previewContainer" style="position:relative"><div class="preview-loading hidden" id="previewLoading"><div class="preview-spinner"><i class="fas fa-spinner fa-spin"></i><span>Chargement...</span></div></div><iframe id="previewFrame" sandbox="allow-same-origin allow-scripts allow-popups"></iframe></div></div></div>
        </div></div>
        <!-- BLOCKS -->
        <div class="mode-panel" id="mode-blocks"><div class="canvas"><div class="canvas-header"><i class="fas fa-chevron-down"></i> Header: <strong id="canvasHeaderLabel"><?=htmlspecialchars($currentLayout['header_config']['type']??'site')?></strong></div><div class="canvas-content drop-zone" id="blocksContainer"><div class="canvas-empty" id="emptyState"><i class="fas fa-plus-circle"></i><p>Glissez un bloc ici ou choisissez un template</p></div></div><div class="canvas-footer"><i class="fas fa-chevron-up"></i> Footer: <strong id="canvasFooterLabel"><?=htmlspecialchars($currentLayout['footer_config']['type']??'site')?></strong></div></div></div>
        <!-- CODE -->
        <div class="mode-panel" id="mode-code"><div class="code-panel"><div class="code-tabs"><button class="code-tab active" data-ctab="html"><span class="dot html"></span> HTML</button><button class="code-tab" data-ctab="css"><span class="dot css"></span> CSS</button><button class="code-tab" data-ctab="js"><span class="dot js"></span> JavaScript</button></div><div class="code-area active" data-carea="html"><textarea id="codeHtml" spellcheck="false" placeholder="<!-- HTML -->"><?=htmlspecialchars($entityHtml)?></textarea></div><div class="code-area" data-carea="css"><textarea id="codeCss" spellcheck="false" placeholder="/* CSS */"><?=htmlspecialchars($entityCss)?></textarea></div><div class="code-area" data-carea="js"><textarea id="codeJs" spellcheck="false" placeholder="// JS"><?=htmlspecialchars($entityJs)?></textarea></div><div class="code-status"><span id="codeLineInfo">Ligne 1, Col 1</span><span>UTF-8 · <?=$ctx['label']?></span></div></div></div>
        <!-- SECTIONS -->
        <div class="mode-panel" id="mode-sections"><div class="sections-panel" id="sectionsContainer"><p class="text-muted" style="text-align:center;padding:40px"><i class="fas fa-paragraph" style="font-size:32px;opacity:.2;display:block;margin-bottom:12px"></i>Les sections apparaîtront ici une fois du contenu HTML ajouté.</p></div></div>
        <!-- IA -->
        <div class="mode-panel" id="mode-ai"><div class="ai-panel"><div class="ai-chat" id="aiChat"><div class="ai-welcome"><div style="text-align:center;padding:30px 20px"><div style="font-size:52px;margin-bottom:16px">✨</div><h3 style="font-size:18px;font-weight:800;margin-bottom:8px">Créez votre page en 1 clic</h3><p style="font-size:13px;color:#64748b;margin-bottom:20px;max-width:320px;margin:0 auto">Décrivez ce que vous voulez, l'IA créera un design professionnel.</p>
            <button onclick="BP.openAIPopup?BP.openAIPopup():alert('Chargement...')" style="padding:14px 32px;border:none;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(79,70,229,.25)"><i class="fas fa-wand-magic-sparkles"></i> Créer ma page avec l'IA</button>
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9"><p style="font-size:11px;color:#94a3b8;margin-bottom:12px">Ou utilisez un modèle rapide :</p><div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center">
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🚀</strong> Page d'accueil</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🏠</strong> Page immobilière</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>📋</strong> Page estimation</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🏘️</strong> Page quartier</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🎯</strong> Page capture</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>👤</strong> Page à propos</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>💼</strong> Page services</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>📞</strong> Page contact</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>⚖️</strong> Mentions légales</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🔑</strong> Page mandat</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>📊</strong> Page avis clients</button>
                <button class="ai-suggest" onclick="BP.aiSuggest(this)" style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;cursor:pointer;font-family:inherit"><strong>🏦</strong> Page financement</button>
                <button class="ai-suggest clone-suggest" onclick="BP.openDesignCloner()" style="padding:10px 14px;border:1px solid #e9d5ff;border-radius:8px;font-size:11px;cursor:pointer;font-family:inherit;width:100%;text-align:center"><strong>🎨 Cloner un design</strong> Reproduire le style visuel d'une autre page</button>
            </div></div></div></div></div>
            <div id="promptSelectorContainer" style="padding:8px 16px;border-top:1px solid var(--border);background:#fafafe"></div>
            <div id="themeSelectorContainer" style="padding:8px 16px;border-top:1px solid #e2e8f0;background:#fafafe"></div>
            <div class="ai-input-bar"><textarea id="aiInput" placeholder="Décrivez ce que vous voulez..." rows="1"></textarea><button class="ai-send" id="aiSend" onclick="BP.aiGenerate()"><i class="fas fa-paper-plane"></i> Générer</button></div>
        </div></div>
        <!-- SEO -->
        <div class="mode-panel" id="mode-seo"><div class="seo-panel"><div class="seo-container">
            <div class="seo-card"><h3><i class="fas fa-chart-pie" style="color:var(--primary)"></i> Score SEO Global</h3><div class="score-grid"><div class="score-item"><div class="score-circle" id="scoreGlobal">--</div><div class="score-label">Global</div></div><div class="score-item"><div class="score-circle" id="scoreTechnique">--</div><div class="score-label">Technique</div></div><div class="score-item"><div class="score-circle" id="scoreContenu">--</div><div class="score-label">Contenu</div></div><div class="score-item"><div class="score-circle" id="scoreSemantique">--</div><div class="score-label">Sémantique</div></div></div><button class="btn btn-primary" onclick="BP.analyzeSEO()" id="btnAnalyzeSeo"><i class="fas fa-search"></i> Analyser</button></div>
            <div class="seo-card"><h3><i class="fas fa-brain" style="color:var(--secondary)"></i> Richesse Sémantique</h3><p style="font-size:11px;color:var(--text-light);margin-bottom:10px">Objectif : 50-70%</p><div class="semantic-bar"><div class="semantic-fill" id="semanticFill" style="width:0%">0%</div><div class="semantic-target" style="left:50%"><span class="semantic-target-lbl">50%</span></div><div class="semantic-target" style="left:70%"><span class="semantic-target-lbl">70%</span></div></div><div id="semanticDetails" style="font-size:12px;color:var(--text-light);margin-top:8px">Cliquez sur Analyser.</div></div>
            <div class="seo-card"><h3><i class="fas fa-tags" style="color:#06b6d4"></i> Meta Tags</h3><div class="seo-meta-field"><label>Meta Title <span class="char-count" id="metaTitleCnt">0/60</span></label><input type="text" id="metaTitle" maxlength="160" oninput="BP.updateCharCount('metaTitle','metaTitleCnt',60)"></div><div class="seo-meta-field"><label>Meta Description <span class="char-count" id="metaDescCnt">0/160</span></label><textarea id="metaDesc" rows="3" maxlength="320" oninput="BP.updateCharCount('metaDesc','metaDescCnt',160)"></textarea></div><div class="seo-meta-field"><label>Mot-clé principal</label><input type="text" id="focusKeyword" placeholder="ex: acheter appartement bordeaux"></div></div>
            <div class="seo-card"><h3><i class="fas fa-clipboard-check" style="color:var(--success)"></i> Checklist SEO</h3><ul class="seo-checklist" id="seoChecklist"><li><span id="chk0">⏳</span> H1 unique</li><li><span id="chk1">⏳</span> Meta desc (50-160)</li><li><span id="chk2">⏳</span> Mot-clé dans titre</li><li><span id="chk3">⏳</span> Images alt</li><li><span id="chk4">⏳</span> Liens internes</li><li><span id="chk5">⏳</span> Structure H1→H2→H3</li><li><span id="chk6">⏳</span> ≥300 mots</li><li><span id="chk7">⏳</span> Schema.org</li></ul></div>
            <div class="seo-card"><h3><i class="fas fa-key" style="color:var(--warning)"></i> Mots-clés</h3><div class="kw-tags" id="keywordTags"><span style="font-size:11px;color:var(--text-light)">Analysez pour voir les mots-clés</span></div></div>
        </div></div></div>
    </div>
    <aside class="config-panel collapsed" id="configPanel"><div class="config-empty"><i class="fas fa-mouse-pointer"></i><p>Sélectionnez un bloc pour modifier ses propriétés</p></div></aside>
</div>
<script>
const BP={config:{context:'<?=$context?>',entityId:<?=$entityId?>,layoutId:<?=$currentLayoutId?>,csrf:'<?=$_SESSION['csrf_token']?>',apiUrl:'/admin/api/builder/builder.php',previewApiUrl:'/admin/modules/builder/api/preview.php',sourceTable:'<?=$sourceTable?>',claudeKey:'<?=$claudeApiKey?>',hasClaudeKey:<?=!empty($claudeApiKey)?'true':'false'?>,globalStylesUrl:'/public/assets/css/style.css',previewUrl:'<?=addslashes($previewUrl)?>'},currentMode:'preview',seoData:null,_prompts:[],_currentPrompt:null,_promptsLoaded:false,
switchMode(mode){this.currentMode=mode;document.querySelectorAll('.mode-btn').forEach(b=>b.classList.toggle('active',b.dataset.mode===mode));document.querySelectorAll('.mode-panel').forEach(p=>p.classList.toggle('active',p.id==='mode-'+mode));document.getElementById('sidebar').classList.toggle('collapsed',mode!=='blocks');document.getElementById('configPanel').classList.toggle('collapsed',mode!=='blocks');if(mode==='preview')this.refreshPreview();if(mode==='sections')this.parseSections();if(mode==='seo'&&!this.seoData)this.analyzeSEO()},
refreshPreview(){const frame=document.getElementById('previewFrame'),loading=document.getElementById('previewLoading'),debug=document.getElementById('previewDebug'),html=document.getElementById('codeHtml').value||'',css=document.getElementById('codeCss').value||'',js=document.getElementById('codeJs').value||'',useGlobal=document.getElementById('globalStylesToggle').checked;if(loading)loading.classList.remove('hidden');if(!html.trim()&&!css.trim()){const e='<!DOCTYPE html><html><head><meta charset="UTF-8"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc}</style></head><body><div style="text-align:center;color:#94a3b8;padding:40px"><div style="font-size:56px;margin-bottom:16px">📝</div><h2 style="color:#475569;font-size:20px;margin-bottom:8px">Page vide</h2><p style="font-size:14px">Utilisez <strong>Code</strong> ou <strong>IA</strong> pour commencer.</p></div></body></html>';this._setFrame(frame,e);if(debug)debug.textContent='vide';if(loading)loading.classList.add('hidden');return}let gl=useGlobal?'<link rel="stylesheet" href="'+this.config.globalStylesUrl+'">':'';const doc='<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">'+gl+'<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Inter,sans-serif;line-height:1.6;color:#1a2332;background:#fff}img{max-width:100%;height:auto}a{text-decoration:none;color:inherit}:root{--gold:#c8a96e;--dark:#1a2332;--text:#4a5568;--bg:#fff;--bg-alt:#f8f7f4}'+css+'</style></head><body>'+html+'<script>try{'+js+'}catch(e){console.warn(e)}<\/script></body></html>';this._setFrame(frame,doc);if(debug)debug.textContent='HTML:'+html.length+' CSS:'+css.length+' JS:'+js.length;if(loading)loading.classList.add('hidden')},
_setFrame(f,h){if(f.src&&f.src!=='about:blank')f.removeAttribute('src');f.srcdoc=h;setTimeout(()=>{try{const d=f.contentDocument||f.contentWindow?.document;if(d&&(!d.body||!d.body.innerHTML.trim())){d.open();d.write(h);d.close()}}catch(e){}},500)},
setDevice(d){document.querySelectorAll('.device-btn').forEach(b=>b.classList.toggle('active',b.dataset.device===d));const c=document.getElementById('previewContainer');c.className='preview-frame'+(d!=='desktop'?' '+d:'');c.style.position='relative'},
editHeader(){const s=document.getElementById('headerSelect');let id=s.value;if(id==='default'||!id){const f=s.querySelector('option[value]:not([value=""]):not([value="default"])');if(f)id=f.value;else{this.toast('Aucun header','warning');return}}window.open('/admin/modules/builder/builder/editor.php?context=header&entity_id='+id,'_blank')},
editFooter(){const s=document.getElementById('footerSelect');let id=s.value;if(id==='default'||!id){const f=s.querySelector('option[value]:not([value=""]):not([value="default"])');if(f)id=f.value;else{this.toast('Aucun footer','warning');return}}window.open('/admin/modules/builder/builder/editor.php?context=footer&entity_id='+id,'_blank')},
createHeader(){window.open('/admin/modules/builder/builder/headers.php','_blank')},
createFooter(){window.open('/admin/modules/builder/builder/footers.php','_blank')},
analyzeSEO(){const btn=document.getElementById('btnAnalyzeSeo');btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Analyse...';btn.disabled=true;const html=document.getElementById('codeHtml').value||'',mt=document.getElementById('metaTitle').value||'',md=document.getElementById('metaDesc').value||'',fk=document.getElementById('focusKeyword').value||'';const p=new DOMParser(),doc=p.parseFromString('<div>'+html+'</div>','text/html'),body=doc.body,text=body.textContent||'',words=text.trim().split(/\s+/).filter(w=>w.length>2),wc=words.length;const h1s=body.querySelectorAll('h1'),h2s=body.querySelectorAll('h2'),h3s=body.querySelectorAll('h3'),imgs=body.querySelectorAll('img'),links=body.querySelectorAll('a');const il=[...links].filter(l=>{const h=l.getAttribute('href')||'';return h.startsWith('/')||h.includes('eduardo-desul')});const ina=[...imgs].filter(i=>!i.getAttribute('alt'));const kl=fk.toLowerCase(),kt=kl&&mt.toLowerCase().includes(kl),kh=kl&&[...h1s].some(h=>h.textContent.toLowerCase().includes(kl));const sc=html.includes('itemtype')||html.includes('application/ld+json')||html.includes('itemscope');const chks=[h1s.length===1,md.length>=50&&md.length<=160,kt||kh,imgs.length===0||ina.length===0,il.length>=1,h1s.length>=1&&h2s.length>=1,wc>=300,sc];chks.forEach((ok,i)=>{const el=document.getElementById('chk'+i);if(el)el.textContent=ok?'✅':'❌'});let tech=0;if(h1s.length===1)tech+=15;if(h2s.length>=1)tech+=10;if(h3s.length>=1)tech+=5;if(mt.length>=20&&mt.length<=60)tech+=20;if(chks[1])tech+=20;if(sc)tech+=15;if(chks[3])tech+=15;tech=Math.min(100,tech);let cont=0;if(wc>=300)cont+=25;else if(wc>=150)cont+=15;if(wc>=600)cont+=15;if(kl&&text.toLowerCase().includes(kl))cont+=20;if(kh||kt)cont+=15;if(il.length>=2)cont+=15;else if(il.length>=1)cont+=10;if(imgs.length>=1)cont+=10;cont=Math.min(100,cont);const immoKw=['immobilier','appartement','maison','terrain','vente','achat','acheter','vendre','estimation','prix','quartier','bordeaux','métropole','investissement','location','louer','conseiller','accompagnement','mandat','notaire','compromis','visite','financement','crédit','prêt','surface','pièces','chambres','garage','jardin','terrasse','balcon','parking','cave','copropriété','charges','diagnostic','dpe','performance','énergétique','neuf','ancien','rénovation'];const fi=immoKw.filter(k=>text.toLowerCase().includes(k));const sem=Math.min(100,Math.round((fi.length/15)*100));const gl=Math.round(tech*0.3+cont*0.4+sem*0.3);this.updateScore('scoreGlobal',gl);this.updateScore('scoreTechnique',tech);this.updateScore('scoreContenu',cont);this.updateScore('scoreSemantique',sem);const qs=document.getElementById('seoQuickScore');qs.textContent=gl+'/100';qs.style.color=gl>=70?'var(--success)':gl>=40?'var(--warning)':'var(--danger)';const fill=document.getElementById('semanticFill');fill.style.width=sem+'%';fill.textContent=sem+'%';fill.className='semantic-fill '+(sem>=50?'good':sem>=30?'ok':'bad');const uw=new Set(words.map(w=>w.toLowerCase()));const lr=wc>0?Math.round((uw.size/wc)*100):0;document.getElementById('semanticDetails').innerHTML='📝 '+wc+' mots · 📊 '+lr+'% richesse · 🏠 '+fi.length+' termes immo · 🔗 '+il.length+' liens internes';const freq={};const sw=['dans','avec','pour','plus','votre','nous','vous','cette','sont','tout','être','faire','aussi','mais','comme'];words.forEach(w=>{const l=w.toLowerCase();if(l.length>3&&!sw.includes(l))freq[l]=(freq[l]||0)+1});const tk=Object.entries(freq).sort((a,b)=>b[1]-a[1]).slice(0,15);document.getElementById('keywordTags').innerHTML=tk.map(([w,c])=>'<span class="kw-tag '+(kl&&w.includes(kl)?'primary':'')+'">'+w+'<span class="cnt">×'+c+'</span></span>').join('');this.seoData={gl,tech,cont,sem};fetch('/admin/api/seo/seo.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'save_score',context:this.config.context,entity_id:this.config.entityId,scores:{global:gl,technique:tech,contenu:cont,semantique:sem}})}).catch(()=>{});btn.innerHTML='<i class="fas fa-search"></i> Analyser';btn.disabled=false;this.toast('SEO: '+gl+'/100',gl>=70?'success':'warning')},
updateScore(id,v){const el=document.getElementById(id);el.textContent=v;el.className='score-circle '+(v>=70?'good':v>=40?'ok':'bad')},
updateCharCount(iid,cid,max){const len=document.getElementById(iid).value.length;const el=document.getElementById(cid);el.textContent=len+'/'+max;el.style.color=len>max?'var(--danger)':len>=max*0.8?'var(--warning)':'var(--text-light)'},
parseSections(){const html=document.getElementById('codeHtml').value,c=document.getElementById('sectionsContainer');if(!html.trim()){c.innerHTML='<p class="text-muted" style="text-align:center;padding:40px">Ajoutez du HTML d\'abord.</p>';return}const p=new DOMParser(),doc=p.parseFromString(html,'text/html');const els=doc.querySelectorAll('h1,h2,h3,h4,p,a[class*="btn"],a[class*="cta"]');const seen=new Set(),secs=[];let sid=0;els.forEach(el=>{const tag=el.tagName.toLowerCase(),text=el.textContent?.trim().substring(0,100);if(!text||seen.has(text))return;seen.add(text);let icon='fa-paragraph',label=tag.toUpperCase();if(['h1','h2','h3','h4'].includes(tag))icon='fa-heading';else if(tag==='a'){icon='fa-link';label='Bouton'}secs.push({id:sid++,tag,icon,label,text,cls:el.className||''})});if(!secs.length){c.innerHTML='<p class="text-muted" style="text-align:center;padding:40px">Aucune section détectée.</p>';return}c.innerHTML=secs.map(s=>{const isTA=s.text.length>80;return'<div class="section-block"><div class="section-header" onclick="this.querySelector(\'.section-toggle\').classList.toggle(\'open\');this.nextElementSibling.classList.toggle(\'open\')"><h4><i class="fas '+s.icon+'"></i> '+s.label+'</h4><button class="section-toggle open"><i class="fas fa-chevron-down"></i></button></div><div class="section-body open"><div class="section-field"><label>'+s.tag.toUpperCase()+'</label>'+(isTA?'<textarea oninput="BP.updateSection('+s.id+',this.value)">'+s.text+'</textarea>':'<input type="text" value="'+s.text.replace(/"/g,'&quot;')+'" oninput="BP.updateSection('+s.id+',this.value)">')+'</div></div></div>'}).join('');this._sections=secs},
updateSection(id,t){const s=this._sections?.find(x=>x.id===id);if(!s)return;let h=document.getElementById('codeHtml').value;if(s.text&&h.includes(s.text)){h=h.replace(s.text,t);document.getElementById('codeHtml').value=h;s.text=t}},
async loadPrompts(){try{const r=await fetch('/admin/modules/ai/ai-prompts/api.php?action=list&category='+this.config.context+'&active_only=1');const d=await r.json();if(d.success)this._prompts=d.prompts;const r2=await fetch('/admin/modules/ai/ai-prompts/api.php?action=list&category=general&active_only=1');const d2=await r2.json();if(d2.success&&d2.prompts.length){const ids=new Set(this._prompts.map(p=>p.id));d2.prompts.forEach(p=>{if(!ids.has(p.id))this._prompts.push(p)})}}catch(e){}try{const r3=await fetch('/admin/modules/ai/ai-prompts/api.php?action=get_default&category='+this.config.context);const d3=await r3.json();if(d3.success&&d3.prompt)this._currentPrompt=d3.prompt}catch(e){}this._promptsLoaded=true;this._renderPromptSelector()},
_renderPromptSelector(){const c=document.getElementById('promptSelectorContainer');if(!c)return;if(!this._prompts.length){c.innerHTML='<div style="font-size:11px;color:var(--text-light);display:flex;align-items:center;gap:6px"><i class="fas fa-info-circle"></i> Prompt par défaut<a href="/admin/modules/ai/ai-prompts/index.php" target="_blank" style="color:var(--secondary);font-weight:600;margin-left:auto"><i class="fas fa-cog"></i> Gérer</a></div>';return}const opts=this._prompts.map(p=>'<option value="'+p.id+'" '+(this._currentPrompt&&this._currentPrompt.id==p.id?'selected':'')+'>'+BP.escHtml(p.name)+(p.is_default?' ★':'')+'</option>').join('');c.innerHTML='<div style="display:flex;align-items:center;gap:6px"><i class="fas fa-robot" style="color:var(--secondary);font-size:12px"></i><select id="promptSelect" onchange="BP.selectPrompt(this.value)" style="flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:11px;font-family:inherit">'+opts+'</select><a href="/admin/modules/ai/ai-prompts/index.php" target="_blank" style="width:28px;height:28px;border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--text-light);text-decoration:none"><i class="fas fa-cog" style="font-size:11px"></i></a></div>'},
async selectPrompt(id){try{const r=await fetch('/admin/modules/ai/ai-prompts/api.php?action=get&id='+id);const d=await r.json();if(d.success){this._currentPrompt=d.prompt;this.toast('Prompt: '+d.prompt.name,'success')}}catch(e){this.toast('Erreur prompt','error')}},
aiSuggest(btn){const t=btn.querySelector('strong').textContent+' — '+btn.textContent.replace(btn.querySelector('strong').textContent,'').trim();document.getElementById('aiInput').value=t;this.aiGenerate()},
async aiGenerate(){const input=document.getElementById('aiInput'),prompt=input.value.trim();if(!prompt)return;const chat=document.getElementById('aiChat'),w=chat.querySelector('.ai-welcome');if(w)w.remove();chat.innerHTML+='<div class="ai-msg user">'+this.escHtml(prompt)+'</div>';input.value='';input.style.height='auto';const lid='ai-'+Date.now();chat.innerHTML+='<div class="ai-msg assistant loading" id="'+lid+'">Génération...</div>';chat.scrollTop=chat.scrollHeight;const sb=document.getElementById('aiSend');sb.disabled=true;try{let sp='',up=prompt;if(this._currentPrompt&&this._currentPrompt.system_prompt){sp=this._currentPrompt.system_prompt;if(this._currentPrompt.user_prompt_template)up=this._currentPrompt.user_prompt_template.replace(/\{\{input\}\}/g,prompt).replace(/\{\{context\}\}/g,this.config.context).replace(/\{\{entity_title\}\}/g,document.querySelector('.tb-title')?.textContent||'');fetch('/admin/modules/ai/ai-prompts/api.php',{method:'POST',body:new URLSearchParams({action:'track_usage',id:this._currentPrompt.id})}).catch(()=>{})}else{sp='Tu es un expert en design web immobilier. HTML entre <html_code></html_code>, CSS entre <css_code></css_code>, JS entre <js_code></js_code>. JAMAIS de <style>/<script> dans le HTML. Design: bleu nuit #1e3a5f, or #c8a96e, Inter, FA 6.5. Responsive. Images: placehold.co. Français. Bordeaux, Eduardo De Sul, eXp France.'}const model=this._currentPrompt?.model||'claude-sonnet-4-20250514',mt=this._currentPrompt?.max_tokens||4096;const resp=await fetch('https://api.anthropic.com/v1/messages',{method:'POST',headers:{'Content-Type':'application/json','x-api-key':this.config.claudeKey,'anthropic-version':'2023-06-01','anthropic-dangerous-direct-browser-access':'true'},body:JSON.stringify({model,max_tokens:mt,system:sp,messages:[{role:'user',content:up}]})});if(!resp.ok){const ed=await resp.json().catch(()=>({}));throw new Error(ed.error?.message||'API '+resp.status)}const data=await resp.json(),text=data.content?.[0]?.text||'';let eH='',eC='',eJ='';const hm=text.match(/<html_code>([\s\S]*?)<\/html_code>/),cm=text.match(/<css_code>([\s\S]*?)<\/css_code>/),jm=text.match(/<js_code>([\s\S]*?)<\/js_code>/);if(hm)eH=hm[1].trim();if(cm)eC=cm[1].trim();if(jm)eJ=jm[1].trim();if(!eH){const m=text.match(/```html\s*([\s\S]*?)```/);if(m)eH=m[1].trim()}if(!eC){const m=text.match(/```css\s*([\s\S]*?)```/);if(m)eC=m[1].trim()}if(!eJ){const m=text.match(/```(?:javascript|js)\s*([\s\S]*?)```/);if(m)eJ=m[1].trim()}if(eH&&!eC){const cb=[];eH=eH.replace(/<style[^>]*>([\s\S]*?)<\/style>/gi,(_,c)=>{cb.push(c.trim());return''});if(cb.length)eC=cb.join('\n\n')}if(eH&&!eJ){const jb=[];eH=eH.replace(/<script(?!\s+src)[^>]*>([\s\S]*?)<\/script>/gi,(_,j)=>{jb.push(j.trim());return''});if(jb.length)eJ=jb.join('\n\n')}if(!eH){const ab=text.match(/```\w*\s*([\s\S]*?)```/g);if(ab){const lg=ab.map(b=>b.replace(/```\w*\s*/,'').replace(/```$/,'').trim()).sort((a,b)=>b.length-a.length)[0];if(lg&&lg.includes('<'))eH=lg}}if(eH){eH=eH.replace(/<!DOCTYPE[^>]*>/gi,'').replace(/<\/?html[^>]*>/gi,'').replace(/<head>[\s\S]*?<\/head>/gi,'').replace(/<\/?body[^>]*>/gi,'').trim()}let ok=false;if(eH){document.getElementById('codeHtml').value=eH;ok=true}if(eC)document.getElementById('codeCss').value=eC;if(eJ)document.getElementById('codeJs').value=eJ;const le=document.getElementById(lid);if(le){le.classList.remove('loading');if(ok){le.innerHTML='✅ <strong>Design généré !</strong><div style="font-size:11px;color:var(--text-light);margin-top:4px">HTML:'+eH.length+' CSS:'+eC.length+' JS:'+eJ.length+'</div><div style="display:flex;gap:6px;margin-top:10px"><button class="btn btn-xs btn-primary" onclick="BP.switchMode(\'preview\')"><i class="fas fa-eye"></i> Preview</button><button class="btn btn-xs" onclick="BP.switchMode(\'code\')"><i class="fas fa-code"></i> Code</button><button class="btn btn-xs btn-dark" onclick="BP.save(\'draft\')"><i class="fas fa-save"></i> Sauver</button></div>';this.refreshPreview();this.toast('✅ Design généré!','success')}else{le.innerHTML='⚠️ Pas de HTML détecté.<details style="margin-top:8px;font-size:11px"><summary style="cursor:pointer;color:var(--primary)">Réponse brute</summary><pre style="white-space:pre-wrap;max-height:200px;overflow:auto;background:#f8fafc;padding:8px;border-radius:6px;font-size:10px">'+this.escHtml(text.substring(0,2000))+'</pre></details>'}}}catch(err){console.error('AI:',err);const le=document.getElementById(lid);if(le){le.classList.remove('loading');le.innerHTML='❌ '+this.escHtml(err.message)}this.toast('❌ '+err.message,'error')}sb.disabled=false;chat.scrollTop=chat.scrollHeight},
async save(status){status=status||'draft';const bs=document.getElementById('btnSave'),bp=document.getElementById('btnPublish'),os=bs.innerHTML,op=bp.innerHTML;if(status==='draft'){bs.innerHTML='<i class="fas fa-spinner fa-spin"></i>...';bs.disabled=true}else{bp.innerHTML='<i class="fas fa-spinner fa-spin"></i>...';bp.disabled=true}const fd=new FormData();fd.append('action','save_content');fd.append('context',this.config.context);fd.append('entity_id',this.config.entityId);fd.append('source_table',this.config.sourceTable);fd.append('html_content',document.getElementById('codeHtml').value);fd.append('custom_css',document.getElementById('codeCss').value);fd.append('custom_js',document.getElementById('codeJs').value);fd.append('header_id',document.getElementById('headerSelect').value||'');fd.append('footer_id',document.getElementById('footerSelect').value||'');fd.append('layout_id',document.getElementById('layoutSelect').value||'');fd.append('meta_title',document.getElementById('metaTitle')?.value||'');fd.append('meta_description',document.getElementById('metaDesc')?.value||'');fd.append('focus_keyword',document.getElementById('focusKeyword')?.value||'');fd.append('status',status);fd.append('csrf_token',this.config.csrf);let ok=false;try{const r=await fetch(this.config.apiUrl,{method:'POST',body:fd});const d=await r.json();if(d.success)ok=true;else this.toast('❌ '+(d.error||'Erreur'),'error')}catch(e){try{const fd2=new FormData();fd2.append('action','save_direct');fd2.append('context',this.config.context);fd2.append('entity_id',this.config.entityId);fd2.append('source_table',this.config.sourceTable);fd2.append('html_content',document.getElementById('codeHtml').value);fd2.append('custom_css',document.getElementById('codeCss').value);fd2.append('custom_js',document.getElementById('codeJs').value);fd2.append('status',status);fd2.append('csrf_token',this.config.csrf);const r2=await fetch('/admin/api/builder/save-direct.php',{method:'POST',body:fd2});const d2=await r2.json();if(d2.success)ok=true;else this.toast('❌ '+(d2.error||''),'error')}catch(e2){this.toast('❌ Réseau','error')}}if(ok){this.toast(status==='published'?'✅ Publié!':'💾 Sauvé!','success');if(this.currentMode==='preview')this.refreshPreview()}bs.innerHTML=os;bs.disabled=false;bp.innerHTML=op;bp.disabled=false},
escHtml(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML},
toast(msg,type){type=type||'info';const t=document.createElement('div');t.style.cssText='position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:600;z-index:10001;animation:slideUp .3s;color:#fff;max-width:400px;box-shadow:0 4px 12px rgba(0,0,0,.15);background:'+(type==='success'?'var(--success)':type==='error'?'var(--danger)':type==='warning'?'var(--warning)':'var(--primary)');t.textContent=msg;document.body.appendChild(t);setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(()=>t.remove(),300)},3500)},
openDesignCloner(){if(typeof DesignCloner!=='undefined'){DesignCloner.open()}else{this.toast('Module Design Clone non chargé','warning')}},
loadTemplate(id){console.log('Load template',id)},
deleteSaved(id){if(confirm('Supprimer?'))console.log('Delete',id)},
_sections:[]};

// Event listeners
document.querySelectorAll('.mode-btn').forEach(b=>b.addEventListener('click',()=>BP.switchMode(b.dataset.mode)));
document.querySelectorAll('.sidebar-tab').forEach(t=>t.addEventListener('click',()=>{document.querySelectorAll('.sidebar-tab').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.sidebar-panel').forEach(x=>x.classList.remove('active'));t.classList.add('active');document.querySelector('[data-spanel="'+t.dataset.stab+'"]')?.classList.add('active')}));
document.querySelectorAll('.code-tab').forEach(t=>t.addEventListener('click',()=>{document.querySelectorAll('.code-tab').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.code-area').forEach(x=>x.classList.remove('active'));t.classList.add('active');document.querySelector('[data-carea="'+t.dataset.ctab+'"]')?.classList.add('active')}));
document.querySelectorAll('.device-btn[data-device]').forEach(b=>b.addEventListener('click',()=>BP.setDevice(b.dataset.device)));
document.getElementById('blockSearch')?.addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('.block-item').forEach(i=>i.style.display=(!q||i.textContent.toLowerCase().includes(q))?'':'none')});
document.getElementById('btnSave')?.addEventListener('click',()=>BP.save('draft'));
document.getElementById('btnPublish')?.addEventListener('click',()=>BP.save('published'));
document.querySelectorAll('.code-area textarea').forEach(ta=>{ta.addEventListener('keyup',function(){const v=this.value.substring(0,this.selectionStart);document.getElementById('codeLineInfo').textContent='Ligne '+v.split('\n').length+', Col '+(v.split('\n').pop().length+1)});ta.addEventListener('keydown',function(e){if(e.key==='Tab'){e.preventDefault();const s=this.selectionStart;this.value=this.value.substring(0,s)+'  '+this.value.substring(this.selectionEnd);this.selectionStart=this.selectionEnd=s+2}})});
const aiIn=document.getElementById('aiInput');if(aiIn){aiIn.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'});aiIn.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();BP.aiGenerate()}})}
const bc=document.getElementById('blocksContainer');if(bc){bc.addEventListener('dragover',e=>{e.preventDefault();bc.classList.add('drag-over')});bc.addEventListener('dragleave',()=>bc.classList.remove('drag-over'));bc.addEventListener('drop',e=>{e.preventDefault();bc.classList.remove('drag-over');const em=document.getElementById('emptyState');if(em)em.remove()})}
document.addEventListener('click',function(e){var dd=document.getElementById('layoutDropdown'),m=document.getElementById('layoutMenu');if(dd&&m&&!dd.contains(e.target))m.classList.remove('open')});
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();BP.save('draft')}if((e.ctrlKey||e.metaKey)&&e.key==='p'&&e.shiftKey){e.preventDefault();BP.save('published')}});

// Init
document.addEventListener('DOMContentLoaded',()=>{
    BP.refreshPreview();BP.loadPrompts();BP.updateCharCount('metaTitle','metaTitleCnt',60);BP.updateCharCount('metaDesc','metaDescCnt',160);
    <?php if($seoScore):?>BP.updateScore('scoreGlobal',<?=(int)$seoScore['score_global']?>);BP.updateScore('scoreTechnique',<?=(int)$seoScore['score_technique']?>);BP.updateScore('scoreContenu',<?=(int)$seoScore['score_contenu']?>);BP.updateScore('scoreSemantique',<?=(int)$seoScore['score_semantique']?>);document.getElementById('seoQuickScore').textContent='<?=(int)$seoScore['score_global']?>/100';<?php endif;?>
    console.log('✅ Builder Pro v3.9 — '+BP.config.context+' #'+BP.config.entityId);
});
const as=document.createElement('style');as.textContent='@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';document.head.appendChild(as);
</script>
<?php
// Charger les scripts JS seulement s'ils existent
$jsFiles = [
    '/admin/modules/builder/assets/js/ai-builder.js',
    '/admin/modules/builder/assets/js/design-clone.js',
];
foreach ($jsFiles as $jsFile) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $jsFile;
    if (file_exists($fullPath)) {
        echo '<script src="' . htmlspecialchars($jsFile) . '"></script>' . "\n";
    }
}
?>
</body>
</html>