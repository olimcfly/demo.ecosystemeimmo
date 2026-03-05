<?php
/**
 *  /admin/api/immobilier/rdv.php
 *  Agenda / Rendez-vous CRUD
 *  Miroir de : modules/immobilier/rdv/
 *  Table : appointments
 *  actions: list, get, save, delete, update-status, upcoming
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $sql="SELECT a.*, c.first_name, c.last_name, c.email FROM appointments a LEFT JOIN contacts c ON a.contact_id=c.id WHERE 1=1"; $params=[];
    if (!empty($p['status'])) { $sql.=" AND a.status=?"; $params[]=$p['status']; }
    if (!empty($p['type']))   { $sql.=" AND a.type=?"; $params[]=$p['type']; }
    if (!empty($p['from']))   { $sql.=" AND a.start_at >= ?"; $params[]=$p['from']; }
    if (!empty($p['to']))     { $sql.=" AND a.start_at <= ?"; $params[]=$p['to']; }
    $sql.=" ORDER BY a.start_at ASC LIMIT ".min((int)($p['limit']??100),500);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    return ['success'=>true,'appointments'=>$stmt->fetchAll()];
}

if ($action==='upcoming') {
    $stmt=$pdo->query("SELECT a.*, c.first_name, c.last_name FROM appointments a LEFT JOIN contacts c ON a.contact_id=c.id WHERE a.start_at >= NOW() AND a.status IN ('scheduled','confirmed') ORDER BY a.start_at ASC LIMIT 10");
    return ['success'=>true,'upcoming'=>$stmt->fetchAll()];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM appointments WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row=$stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'RDV non trouvé','_http_code'=>404];
    return ['success'=>true,'appointment'=>$row];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=['contact_id'=>$p['contact_id']??null,'lead_id'=>$p['lead_id']??null,'title'=>$p['title']??'RDV',
        'description'=>$p['description']??null,'type'=>$p['type']??'autre','location'=>$p['location']??null,
        'start_at'=>$p['start_at']??date('Y-m-d H:i:s'),'end_at'=>$p['end_at']??null,
        'status'=>$p['status']??'scheduled','notes'=>$p['notes']??null];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE appointments SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO appointments (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM appointments WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

if ($action==='update-status' && $method==='POST') {
    $pdo->prepare("UPDATE appointments SET status=? WHERE id=?")->execute([$p['status']??'scheduled',(int)($p['id']??0)]);
    return ['success'=>true];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','update-status','upcoming']];
