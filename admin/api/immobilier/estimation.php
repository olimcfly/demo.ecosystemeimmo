<?php
/**
 *  /admin/api/immobilier/estimation.php
 *  Estimations CRUD + soumission publique
 *  Miroir de : modules/immobilier/estimation/
 *  Table : estimations
 *  actions: list, get, save, delete, submit, stats
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $sql="SELECT e.*, l.first_name, l.last_name, l.email FROM estimations e LEFT JOIN leads l ON e.lead_id=l.id WHERE 1=1"; $params=[];
    if (!empty($p['status'])) { $sql.=" AND e.status=?"; $params[]=$p['status']; }
    $sql.=" ORDER BY e.created_at DESC LIMIT ".min((int)($p['limit']??50),200);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    return ['success'=>true,'estimations'=>$stmt->fetchAll()];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM estimations WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row=$stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Estimation non trouvée','_http_code'=>404];
    return ['success'=>true,'estimation'=>$row];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=['lead_id'=>$p['lead_id']??null,'contact_id'=>$p['contact_id']??null,
        'property_type'=>$p['property_type']??'appartement','address'=>$p['address']??'',
        'city'=>$p['city']??'Bordeaux','postal_code'=>$p['postal_code']??null,
        'surface'=>$p['surface']??null,'rooms'=>$p['rooms']??null,'bedrooms'=>$p['bedrooms']??null,
        'floor'=>$p['floor']??null,'parking'=>(int)($p['parking']??0),'condition_state'=>$p['condition_state']??'bon',
        'estimated_price_low'=>$p['estimated_price_low']??null,'estimated_price_high'=>$p['estimated_price_high']??null,
        'estimated_price_avg'=>$p['estimated_price_avg']??null,'bant_score'=>$p['bant_score']??null,
        'status'=>$p['status']??'pending','notes'=>$p['notes']??null];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE estimations SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO estimations (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM estimations WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

if ($action==='submit' && $method==='POST') {
    // Public submission: create lead + estimation
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO leads (email,phone,first_name,last_name,source,gdpr_consent,created_at) VALUES (?,?,?,?,'estimation',?,NOW())")
        ->execute([$p['email']??'',$p['phone']??null,$p['first_name']??'',$p['last_name']??'',(int)($p['gdpr_consent']??0)]);
    $leadId=(int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO estimations (lead_id,property_type,address,city,postal_code,surface,rooms,bedrooms,status,created_at) VALUES (?,?,?,?,?,?,?,?,'pending',NOW())")
        ->execute([$leadId,$p['property_type']??'appartement',$p['address']??'',$p['city']??'Bordeaux',$p['postal_code']??null,$p['surface']??null,$p['rooms']??null,$p['bedrooms']??null]);
    $estId=(int)$pdo->lastInsertId();
    $pdo->commit();
    return ['success'=>true,'lead_id'=>$leadId,'estimation_id'=>$estId,'message'=>'Estimation soumise'];
}

if ($action==='stats') {
    $total=(int)$pdo->query("SELECT COUNT(*) FROM estimations")->fetchColumn();
    $pending=(int)$pdo->query("SELECT COUNT(*) FROM estimations WHERE status='pending'")->fetchColumn();
    $completed=(int)$pdo->query("SELECT COUNT(*) FROM estimations WHERE status='completed'")->fetchColumn();
    $avgPrice=$pdo->query("SELECT AVG(estimated_price_avg) FROM estimations WHERE estimated_price_avg IS NOT NULL")->fetchColumn();
    return ['success'=>true,'stats'=>compact('total','pending','completed','avgPrice')];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','submit','stats']];
