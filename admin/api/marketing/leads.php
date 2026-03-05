<?php
/**
 *  /admin/api/marketing/leads.php
 *  Prospects CRUD
 *  Miroir de : modules/marketing/leads/
 *  Table : leads
 *  actions: list, get, save, delete, convert-to-contact, assign-score
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $sql="SELECT l.*, cp.name AS capture_name FROM leads l LEFT JOIN captures cp ON l.capture_page_id=cp.id WHERE 1=1";
    $params=[];
    if (!empty($p['status'])) { $sql.=" AND l.status=?"; $params[]=$p['status']; }
    if (!empty($p['source'])) { $sql.=" AND l.source=?"; $params[]=$p['source']; }
    if (!empty($p['search'])) { $sql.=" AND (l.first_name LIKE ? OR l.last_name LIKE ? OR l.email LIKE ?)"; $s="%{$p['search']}%"; $params=array_merge($params,[$s,$s,$s]); }
    $sql.=" ORDER BY l.created_at DESC LIMIT ".min((int)($p['limit']??50),200);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    $total=(int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    return ['success'=>true,'leads'=>$stmt->fetchAll(),'total'=>$total];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM leads WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $lead=$stmt->fetch();
    if (!$lead) return ['success'=>false,'error'=>'Lead non trouvé','_http_code'=>404];
    return ['success'=>true,'lead'=>$lead];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=['email'=>$p['email']??'','phone'=>$p['phone']??null,'first_name'=>$p['first_name']??'','last_name'=>$p['last_name']??'',
        'source'=>$p['source']??null,'capture_page_id'=>$p['capture_page_id']??null,'status'=>$p['status']??'new','notes'=>$p['notes']??null,'gdpr_consent'=>(int)($p['gdpr_consent']??0)];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE leads SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO leads (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

if ($action==='convert-to-contact' && $method==='POST') {
    $id=(int)($p['id']??0);
    $stmt=$pdo->prepare("SELECT * FROM leads WHERE id=?"); $stmt->execute([$id]); $lead=$stmt->fetch();
    if (!$lead) return ['success'=>false,'error'=>'Lead non trouvé'];
    $pdo->prepare("INSERT INTO contacts (first_name,last_name,email,phone,source,notes,gdpr_consent,created_at) VALUES (?,?,?,?,?,?,?,NOW())")
        ->execute([$lead['first_name'],$lead['last_name'],$lead['email'],$lead['phone'],$lead['source'],$lead['notes'],$lead['gdpr_consent']]);
    $contactId=(int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE leads SET status='converted' WHERE id=?")->execute([$id]);
    return ['success'=>true,'contact_id'=>$contactId,'message'=>'Lead converti en contact'];
}

if ($action==='assign-score' && $method==='POST') {
    $leadId=(int)($p['lead_id']??$p['id']??0);
    $fields=['lead_id'=>$leadId,'score_total'=>(int)($p['score_total']??0),'score_budget'=>(int)($p['score_budget']??0),
        'score_authority'=>(int)($p['score_authority']??0),'score_need'=>(int)($p['score_need']??0),'score_timing'=>(int)($p['score_timing']??0),
        'grade'=>$p['grade']??'F','notes'=>$p['notes']??null];
    $pdo->prepare("INSERT INTO lead_scoring (lead_id,score_total,score_budget,score_authority,score_need,score_timing,grade,notes)
        VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE score_total=VALUES(score_total),score_budget=VALUES(score_budget),
        score_authority=VALUES(score_authority),score_need=VALUES(score_need),score_timing=VALUES(score_timing),grade=VALUES(grade),notes=VALUES(notes)")
        ->execute(array_values($fields));
    return ['success'=>true,'message'=>'Score assigné'];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','convert-to-contact','assign-score']];
