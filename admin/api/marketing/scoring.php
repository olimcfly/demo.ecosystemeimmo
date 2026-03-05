<?php
/**
 *  /admin/api/marketing/scoring.php
 *  Lead Scoring BANT
 *  Miroir de : modules/marketing/scoring/
 *  Table : lead_scoring
 *  actions: get, save, recalculate, leaderboard
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='get') {
    $leadId=(int)($p['lead_id']??$p['id']??0);
    $stmt=$pdo->prepare("SELECT * FROM lead_scoring WHERE lead_id=?"); $stmt->execute([$leadId]);
    $score=$stmt->fetch();
    return ['success'=>true,'score'=>$score ?: null];
}

if ($action==='save' && $method==='POST') {
    $leadId=(int)($p['lead_id']??0);
    $b=(int)($p['score_budget']??0); $a=(int)($p['score_authority']??0); $n=(int)($p['score_need']??0); $t=(int)($p['score_timing']??0);
    $total=$b+$a+$n+$t;
    $grade=$total>=80?'A':($total>=60?'B':($total>=40?'C':($total>=20?'D':'F')));
    $pdo->prepare("INSERT INTO lead_scoring (lead_id,score_total,score_budget,score_authority,score_need,score_timing,grade,notes,last_activity)
        VALUES (?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE score_total=?,score_budget=?,score_authority=?,score_need=?,score_timing=?,grade=?,notes=?,last_activity=NOW()")
        ->execute([$leadId,$total,$b,$a,$n,$t,$grade,$p['notes']??null, $total,$b,$a,$n,$t,$grade,$p['notes']??null]);
    return ['success'=>true,'score_total'=>$total,'grade'=>$grade];
}

if ($action==='leaderboard') {
    $limit=min((int)($p['limit']??20),100);
    $stmt=$pdo->query("SELECT ls.*, l.first_name, l.last_name, l.email FROM lead_scoring ls JOIN leads l ON ls.lead_id=l.id ORDER BY ls.score_total DESC LIMIT {$limit}");
    return ['success'=>true,'leaderboard'=>$stmt->fetchAll()];
}

if ($action==='recalculate' && $method==='POST') {
    // Recalculate grades for all scores
    $stmt=$pdo->query("SELECT id, score_total FROM lead_scoring");
    $updated=0;
    while ($row=$stmt->fetch()) {
        $t=$row['score_total'];
        $g=$t>=80?'A':($t>=60?'B':($t>=40?'C':($t>=20?'D':'F')));
        $pdo->prepare("UPDATE lead_scoring SET grade=? WHERE id=?")->execute([$g,$row['id']]);
        $updated++;
    }
    return ['success'=>true,'updated'=>$updated];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['get','save','recalculate','leaderboard']];
