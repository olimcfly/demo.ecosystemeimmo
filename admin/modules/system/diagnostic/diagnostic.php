<?php
/**
 * admin/modules/diagnostic/diagnostic.php
 * 
 * IMPORTANT: Le fichier s'appelle diagnostic.php (PAS index.php)
 * pour éviter les conflits avec le routeur admin .htaccess
 * 
 * Accès : https://eduardo-desul-immobilier.fr/admin/modules/diagnostic/diagnostic.php
 */

// =========================================================================
// ERROR HANDLING - capturer tout
// =========================================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/diagnostic-error.log');

// Gestionnaire d'erreurs fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(200);
        echo '<div style="background:#fee2e2;padding:20px;margin:20px;border-radius:8px;font-family:monospace;">';
        echo '<b>Erreur fatale :</b><br>';
        echo htmlspecialchars($error['message']) . '<br>';
        echo 'Fichier : ' . htmlspecialchars($error['file']) . ' ligne ' . $error['line'];
        echo '</div>';
    }
});

// =========================================================================
// CHARGEMENT
// =========================================================================
ob_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($db) || !($db instanceof PDO)) {
    die('Erreur : variable $db non disponible');
}

require_once __DIR__ . '/ModuleDiagnostic.php';

$modulesPath = realpath(__DIR__ . '/../');
$diagnostic  = new ModuleDiagnostic($db, $modulesPath);
$report      = $diagnostic->runFullDiagnostic();

$summary    = $report['summary'];
$modules    = $report['modules'];
$dbHealth   = $report['db_health'];
$scorePct   = $summary['total'] > 0 ? round(($summary['ok'] / $summary['total']) * 100) : 0;

// Grouper par catégorie
$categories = [];
foreach ($modules as $slug => $mod) {
    $categories[$mod['category']][$slug] = $mod;
}
$catOrder = ['CRM','CMS','Immobilier','SEO','Marketing','IA','Système','Non référencé'];
uksort($categories, function($a,$b) use ($catOrder) {
    $ia = array_search($a,$catOrder); $ib = array_search($b,$catOrder);
    return ($ia===false?99:$ia) - ($ib===false?99:$ib);
});

// Préparer le JSON pour l'export (séparé pour éviter les problèmes d'encodage)
$jsonReport = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

ob_end_clean();

// =========================================================================
// RENDU HTML - 100% standalone
// =========================================================================
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diagnostic Modules</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f1f5f9;color:#1e293b;line-height:1.5}
.wrap{max-width:1400px;margin:0 auto;padding:20px}

/* Header */
.hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.hdr h1{font-size:24px;font-weight:800}
.hdr h1 i{color:#6366f1}
.hdr-r{display:flex;gap:8px;align-items:center}
.ts{color:#94a3b8;font-size:12px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;color:#fff}
.btn-p{background:#6366f1}.btn-p:hover{background:#4f46e5}
.btn-s{background:#475569}.btn-s:hover{background:#334155}

/* Score + Stats */
.top{display:flex;gap:20px;margin-bottom:24px;flex-wrap:wrap}
.ring{width:120px;height:120px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
  background:conic-gradient(#22c55e 0% <?=$scorePct?>%, #e2e8f0 <?=$scorePct?>% 100%)}
.ring-in{width:94px;height:94px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}
.ring-in .n{font-size:30px;font-weight:800;line-height:1}
.ring-in .l{font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.stats{display:flex;gap:10px;flex:1;flex-wrap:wrap}
.st{flex:1;min-width:110px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.04);border-left:4px solid #e2e8f0}
.st.t{border-left-color:#6366f1}.st.o{border-left-color:#22c55e}.st.w{border-left-color:#f59e0b}.st.e{border-left-color:#ef4444}
.st .sn{font-size:28px;font-weight:800;line-height:1}
.st.t .sn{color:#6366f1}.st.o .sn{color:#22c55e}.st.w .sn{color:#f59e0b}.st.e .sn{color:#ef4444}
.st .sl{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:4px}

/* Bar */
.bar{background:#e2e8f0;border-radius:99px;height:8px;display:flex;overflow:hidden;margin-bottom:20px}
.bar div{transition:width .5s}
.b-o{background:#22c55e}.b-w{background:#f59e0b}.b-e{background:#ef4444}

/* Filters */
.flt{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap}
.fb{padding:5px 12px;border-radius:99px;border:1px solid #e2e8f0;background:#fff;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px}
.fb:hover{background:#f8fafc}
.fb.ac{background:#1e293b;color:#fff;border-color:#1e293b}
.fb .c{background:rgba(0,0,0,.06);padding:0 6px;border-radius:99px;font-size:10px}
.fb.ac .c{background:rgba(255,255,255,.2)}

/* DB */
.dbx{background:#fff;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);margin-bottom:24px}
.dbx h3{font-size:15px;font-weight:700;margin-bottom:12px}.dbx h3 i{color:#6366f1}
.dbx table{width:100%;border-collapse:collapse}
.dbx td{padding:7px 12px;border-bottom:1px solid #f1f5f9;font-size:12px}
.dbx tr:last-child td{border-bottom:none}
.dd{width:9px;height:9px;border-radius:50%;display:inline-block}
.dd.ok{background:#22c55e}.dd.warning{background:#f59e0b}.dd.error{background:#ef4444}

/* Categories */
.cat{margin-bottom:24px}
.cat-t{font-size:15px;font-weight:700;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #e2e8f0;display:flex;align-items:center;gap:8px}
.cat-c{background:#f1f5f9;color:#64748b;font-size:11px;font-weight:500;padding:1px 8px;border-radius:99px}

/* Cards */
.grd{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:12px}
.cd{background:#fff;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.04);overflow:hidden;cursor:pointer;border:1px solid #f1f5f9;transition:box-shadow .2s}
.cd:hover{box-shadow:0 3px 12px rgba(0,0,0,.07)}
.cd-h{display:flex;align-items:center;padding:12px 16px;gap:10px}
.cd-i{width:34px;height:34px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.cd.s-ok .cd-i{background:#dcfce7;color:#16a34a}
.cd.s-warning .cd-i{background:#fef3c7;color:#d97706}
.cd.s-error .cd-i{background:#fee2e2;color:#dc2626}
.cd-n{font-weight:600;font-size:13px}
.cd-s{font-size:10px;color:#94a3b8;font-family:monospace}
.cd-b{margin-left:auto;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;flex-shrink:0}
.cd.s-ok .cd-b{background:#dcfce7;color:#16a34a}
.cd.s-warning .cd-b{background:#fef3c7;color:#d97706}
.cd.s-error .cd-b{background:#fee2e2;color:#dc2626}

/* Details */
.cd-d{max-height:0;overflow:hidden;transition:max-height .3s;border-top:1px solid transparent}
.cd.op .cd-d{max-height:600px;border-top-color:#f1f5f9}
.cd-di{padding:8px 16px 12px}
.ck{display:flex;align-items:flex-start;gap:6px;padding:4px 0;font-size:11px;color:#475569;border-bottom:1px solid #fafafa}
.ck:last-child{border-bottom:none}
.ck-d{width:7px;height:7px;border-radius:50%;margin-top:4px;flex-shrink:0}
.ck-d.ok{background:#22c55e}.ck-d.warning{background:#f59e0b}.ck-d.error{background:#ef4444}

@media(max-width:768px){.grd{grid-template-columns:1fr}.top{flex-direction:column;align-items:center}}
.hidden{display:none!important}
</style>
</head>
<body>
<div class="wrap">

<div class="hdr">
    <h1><i class="fas fa-stethoscope"></i> Diagnostic Modules</h1>
    <div class="hdr-r">
        <span class="ts"><?=htmlspecialchars($report['timestamp'])?></span>
        <button class="btn btn-s" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Relancer</button>
        <button class="btn btn-p" onclick="xport()"><i class="fas fa-download"></i> JSON</button>
    </div>
</div>

<div class="top">
    <div class="ring"><div class="ring-in"><span class="n"><?=$scorePct?>%</span><span class="l">Santé</span></div></div>
    <div class="stats">
        <div class="st t"><div class="sn"><?=$summary['total']?></div><div class="sl">Modules</div></div>
        <div class="st o"><div class="sn"><?=$summary['ok']?></div><div class="sl">OK</div></div>
        <div class="st w"><div class="sn"><?=$summary['warning']?></div><div class="sl">Warnings</div></div>
        <div class="st e"><div class="sn"><?=$summary['error']?></div><div class="sl">Erreurs</div></div>
    </div>
</div>

<?php if($summary['total']>0):?>
<div class="bar">
    <div class="b-o" style="width:<?=round($summary['ok']/$summary['total']*100)?>%"></div>
    <div class="b-w" style="width:<?=round($summary['warning']/$summary['total']*100)?>%"></div>
    <div class="b-e" style="width:<?=round($summary['error']/$summary['total']*100)?>%"></div>
</div>
<?php endif;?>

<div class="flt">
    <button class="fb ac" data-f="all">Tous <span class="c"><?=$summary['total']?></span></button>
    <button class="fb" data-f="ok">&#9679; OK <span class="c"><?=$summary['ok']?></span></button>
    <button class="fb" data-f="warning">&#9679; Warn <span class="c"><?=$summary['warning']?></span></button>
    <button class="fb" data-f="error">&#9679; Err <span class="c"><?=$summary['error']?></span></button>
</div>

<div class="dbx">
    <h3><i class="fas fa-database"></i> Base de données</h3>
    <table>
    <?php foreach($dbHealth as $r):?>
        <tr>
            <td style="width:20px"><span class="dd <?=$r['status']?>"></span></td>
            <td style="font-weight:500"><?=htmlspecialchars($r['check'])?></td>
            <td style="color:#64748b;text-align:right"><?=htmlspecialchars($r['value']??'')?></td>
        </tr>
    <?php endforeach;?>
    </table>
</div>

<?php foreach($categories as $catName=>$catMods):?>
<div class="cat" data-cat="<?=htmlspecialchars($catName)?>">
    <div class="cat-t"><?=htmlspecialchars($catName)?> <span class="cat-c"><?=count($catMods)?></span></div>
    <div class="grd">
    <?php foreach($catMods as $slug=>$m):?>
        <div class="cd s-<?=$m['status']?>" data-st="<?=$m['status']?>" onclick="this.classList.toggle('op')">
            <div class="cd-h">
                <div class="cd-i"><i class="<?=htmlspecialchars($m['icon'])?>"></i></div>
                <div>
                    <div class="cd-n"><?=htmlspecialchars($m['label'])?></div>
                    <div class="cd-s">/<?=htmlspecialchars($slug)?>/</div>
                </div>
                <span class="cd-b"><?php
                    if($m['status']==='ok') echo '&#10003; OK';
                    elseif($m['status']==='warning') echo '&#9888; Warn';
                    else echo '&#10007; Err';
                ?></span>
            </div>
            <div class="cd-d"><div class="cd-di">
                <?php foreach($m['checks'] as $c):?>
                <div class="ck"><span class="ck-d <?=$c['status']?>"></span><span><?=htmlspecialchars($c['message'])?></span></div>
                <?php endforeach;?>
            </div></div>
        </div>
    <?php endforeach;?>
    </div>
</div>
<?php endforeach;?>

</div>

<script>
document.querySelectorAll('.fb').forEach(function(btn){
    btn.addEventListener('click', function(){
        // Toggle active button
        document.querySelectorAll('.fb').forEach(function(x){ x.classList.remove('ac'); });
        btn.classList.add('ac');
        
        var filter = btn.getAttribute('data-f');
        
        // Show/hide cards
        document.querySelectorAll('.cd').forEach(function(card){
            var status = card.getAttribute('data-st');
            if(filter === 'all' || status === filter){
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
        
        // Show/hide empty categories
        document.querySelectorAll('.cat').forEach(function(cat){
            var visibleCards = cat.querySelectorAll('.cd:not(.hidden)');
            if(visibleCards.length === 0){
                cat.classList.add('hidden');
            } else {
                cat.classList.remove('hidden');
            }
        });
    });
});

function xport(){
    var d=<?=$jsonReport?>;
    var b=new Blob([JSON.stringify(d,null,2)],{type:'application/json'});
    var a=document.createElement('a');
    a.href=URL.createObjectURL(b);
    a.download='diagnostic-'+new Date().toISOString().slice(0,10)+'.json';
    a.click();
}
</script>
</body>
</html>