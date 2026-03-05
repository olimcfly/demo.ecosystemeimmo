<?php
/**
 *  /admin/api/immobilier/biens.php
 *  Annonces immobilières CRUD
 *  Miroir de : modules/immobilier/biens/
 *  Table : biens (fallback properties)
 *  actions: list, get, save, delete, toggle-featured, toggle-status
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];
$tbl='biens'; try{$pdo->query("SELECT 1 FROM biens LIMIT 1");}catch(Exception $e){$tbl='properties';}

if ($action==='list') {
    $sql="SELECT id,reference,title,slug,type,transaction,price,surface,rooms,bedrooms,city,status,featured,created_at FROM `{$tbl}` WHERE 1=1"; $params=[];
    if (!empty($p['status']))      { $sql.=" AND status=?"; $params[]=$p['status']; }
    if (!empty($p['type']))        { $sql.=" AND type=?"; $params[]=$p['type']; }
    if (!empty($p['transaction'])) { $sql.=" AND transaction=?"; $params[]=$p['transaction']; }
    if (!empty($p['city']))        { $sql.=" AND city=?"; $params[]=$p['city']; }
    if (!empty($p['search']))      { $sql.=" AND (title LIKE ? OR reference LIKE ? OR city LIKE ?)"; $s="%{$p['search']}%"; $params=array_merge($params,[$s,$s,$s]); }
    $sql.=" ORDER BY featured DESC, created_at DESC LIMIT ".min((int)($p['limit']??50),200);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    $total=(int)$pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
    return ['success'=>true,'biens'=>$stmt->fetchAll(),'total'=>$total,'table'=>$tbl];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM `{$tbl}` WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $bien=$stmt->fetch();
    if (!$bien) return ['success'=>false,'error'=>'Bien non trouvé','_http_code'=>404];
    $bien['features']=json_decode($bien['features']??'[]',true);
    $bien['images']=json_decode($bien['images']??'[]',true);
    return ['success'=>true,'bien'=>$bien];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=[
        'reference'=>$p['reference']??'REF-'.strtoupper(substr(uniqid(),0,6)),
        'title'=>$p['title']??'','slug'=>$p['slug']??strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$p['title']??''),'-')),
        'type'=>$p['type']??'appartement','transaction'=>$p['transaction']??'vente',
        'price'=>$p['price']??null,'surface'=>$p['surface']??null,'rooms'=>$p['rooms']??null,'bedrooms'=>$p['bedrooms']??null,
        'description'=>$p['description']??'','address'=>$p['address']??'','city'=>$p['city']??'Bordeaux','postal_code'=>$p['postal_code']??null,
        'latitude'=>$p['latitude']??null,'longitude'=>$p['longitude']??null,
        'features'=>is_array($p['features']??null)?json_encode($p['features']):($p['features']??'[]'),
        'images'=>is_array($p['images']??null)?json_encode($p['images']):($p['images']??'[]'),
        'status'=>$p['status']??'available','featured'=>(int)($p['featured']??0),
    ];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE `{$tbl}` SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO `{$tbl}` (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM `{$tbl}` WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }
if ($action==='toggle-featured' && $method==='POST') { $id=(int)($p['id']??0); $pdo->prepare("UPDATE `{$tbl}` SET featured = NOT featured WHERE id=?")->execute([$id]); return ['success'=>true]; }
if ($action==='toggle-status' && $method==='POST') { $id=(int)($p['id']??0); $s=$pdo->prepare("SELECT status FROM `{$tbl}` WHERE id=?"); $s->execute([$id]); $cur=$s->fetchColumn(); $new=$cur==='available'?'sold':'available'; $pdo->prepare("UPDATE `{$tbl}` SET status=? WHERE id=?")->execute([$new,$id]); return ['success'=>true,'new_status'=>$new]; }

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','toggle-featured','toggle-status']];
