<?php
/**
 *  /admin/api/builder/design.php
 *  Headers & Footers CRUD
 *  Miroir de : modules/builder/design/
 *  Tables : headers, footers
 *
 *  actions: list, get, save, delete, set-default
 *  params : type=headers|footers
 */

$pdo = $ctx['pdo']; $action = $ctx['action']; $method = $ctx['method']; $p = $ctx['params'];
$type = ($p['type'] ?? 'headers') === 'footers' ? 'footers' : 'headers';

if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM `{$type}` ORDER BY is_default DESC, created_at DESC");
    return ['success'=>true, $type => $stmt->fetchAll(), 'type' => $type];
}

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT * FROM `{$type}` WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row = $stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>ucfirst($type).' non trouvé','_http_code'=>404];
    return ['success'=>true, rtrim($type,'s') => $row];
}

if ($action === 'save' && $method === 'POST') {
    $id = (int)($p['id']??0);
    $fields = [
        'name'       => $p['name'] ?? 'Sans nom',
        'html'       => $p['html'] ?? $p['content'] ?? '',
        'css'        => $p['css'] ?? '',
        'js'         => $p['js'] ?? '',
        'is_default' => (int)($p['is_default'] ?? 0),
        'status'     => $p['status'] ?? 'active',
    ];
    if ($id > 0) {
        $sets=[]; $vals=[];
        foreach ($fields as $c=>$v) { $sets[]="`{$c}`=?"; $vals[]=$v; }
        $vals[]=$id;
        $pdo->prepare("UPDATE `{$type}` SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
        return ['success'=>true,'message'=>'Mis à jour','id'=>$id];
    }
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO `{$type}` (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'message'=>'Créé','id'=>(int)$pdo->lastInsertId()];
}

if ($action === 'delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM `{$type}` WHERE id=?")->execute([(int)($p['id']??0)]);
    return ['success'=>true,'message'=>'Supprimé'];
}

if ($action === 'set-default' && $method === 'POST') {
    $id = (int)($p['id']??0);
    $pdo->exec("UPDATE `{$type}` SET is_default = 0");
    $pdo->prepare("UPDATE `{$type}` SET is_default = 1 WHERE id=?")->execute([$id]);
    return ['success'=>true,'message'=>'Défini par défaut'];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','set-default']];
