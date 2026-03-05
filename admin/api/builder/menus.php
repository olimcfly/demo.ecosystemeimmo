<?php
/**
 *  /admin/api/builder/menus.php
 *  Menus CRUD
 *  Miroir de : modules/builder/menus/
 *  Table : menus
 *  actions: list, get, save, delete, update-links
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $stmt=$pdo->query("SELECT * FROM menus ORDER BY location ASC, name ASC");
    return ['success'=>true,'menus'=>$stmt->fetchAll()];
}
if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM menus WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row=$stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Menu non trouvé','_http_code'=>404];
    $row['items']=json_decode($row['items']??'[]',true);
    return ['success'=>true,'menu'=>$row];
}
if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=['name'=>$p['name']??'Menu','slug'=>$p['slug']??null,'location'=>$p['location']??'header','items'=>is_array($p['items']??null)?json_encode($p['items']):($p['items']??'[]'),'status'=>$p['status']??'active'];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE menus SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO menus (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}
if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM menus WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,'actions'=>['list','get','save','delete']];
