<?php
/**
 *  /admin/api/content/captures.php
 *  CRUD Pages de Capture
 *  Miroir de : modules/content/pages-capture/
 *  Table : captures (fallback capture_pages)
 *
 *  actions: list, get, save, delete, toggle-status, stats
 */

$pdo = $ctx['pdo']; $action = $ctx['action']; $method = $ctx['method']; $p = $ctx['params'];

$tbl = 'captures';
try { $pdo->query("SELECT 1 FROM captures LIMIT 1"); } catch(Exception $e) {
    try { $pdo->query("SELECT 1 FROM capture_pages LIMIT 1"); $tbl = 'capture_pages'; } catch(Exception $e2) {}
}

if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM `{$tbl}` ORDER BY created_at DESC");
    return ['success' => true, 'captures' => $stmt->fetchAll(), 'table' => $tbl];
}

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT * FROM `{$tbl}` WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row = $stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Capture non trouvée','_http_code'=>404];
    return ['success'=>true,'capture'=>$row];
}

if ($action === 'save' && $method === 'POST') {
    $id = (int)($p['id']??0);
    $fields = [
        'name'              => $p['name'] ?? $p['title'] ?? '',
        'slug'              => $p['slug'] ?? strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$p['name']??''),'-')),
        'template'          => $p['template'] ?? 'default',
        'headline'          => $p['headline'] ?? '',
        'subheadline'       => $p['subheadline'] ?? '',
        'cta_text'          => $p['cta_text'] ?? 'Recevoir mon guide',
        'thank_you_message' => $p['thank_you_message'] ?? 'Merci ! Vérifiez votre boîte email.',
        'status'            => $p['status'] ?? 'active',
    ];
    if ($id > 0) {
        $sets=[]; $vals=[];
        foreach ($fields as $c=>$v) { $sets[]="`{$c}`=?"; $vals[]=$v; }
        $vals[]=$id;
        $pdo->prepare("UPDATE `{$tbl}` SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
        return ['success'=>true,'message'=>'Mise à jour OK','id'=>$id];
    }
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO `{$tbl}` (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'message'=>'Créée','id'=>(int)$pdo->lastInsertId()];
}

if ($action === 'delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM `{$tbl}` WHERE id=?")->execute([(int)($p['id']??0)]);
    return ['success'=>true,'message'=>'Supprimée'];
}

if ($action === 'stats') {
    $id = (int)($p['id']??0);
    $stmt = $pdo->prepare("SELECT views, conversions FROM `{$tbl}` WHERE id=?"); $stmt->execute([$id]);
    $row = $stmt->fetch();
    $rate = ($row && $row['views'] > 0) ? round(($row['conversions']/$row['views'])*100,1) : 0;
    return ['success'=>true,'views'=>(int)($row['views']??0),'conversions'=>(int)($row['conversions']??0),'rate'=>$rate];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','stats']];
