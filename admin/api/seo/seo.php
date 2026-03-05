<?php
/**
 *  /admin/api/seo/seo.php
 *  SEO Analysis + Score
 *  Miroir de : modules/seo/seo/ et modules/seo/seo-semantic/
 *  actions: score, analyze, pages-seo, articles-seo, update-score, bulk-analyze
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='score') {
    $pageId=(int)($p['page_id']??$p['id']??0);
    $type=$p['type']??'page'; // page or article
    $tbl=$type==='article'?'articles':'pages';
    $stmt=$pdo->prepare("SELECT id,title,slug,meta_title,meta_description,content,seo_score FROM `{$tbl}` WHERE id=?"); $stmt->execute([$pageId]);
    $row=$stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Contenu non trouvé','_http_code'=>404];
    return ['success'=>true,'seo'=>['id'=>$row['id'],'title'=>$row['title'],'score'=>(int)($row['seo_score']??0),
        'meta_title_length'=>mb_strlen($row['meta_title']??''),'meta_desc_length'=>mb_strlen($row['meta_description']??''),
        'content_length'=>mb_strlen(strip_tags($row['content']??'')),'has_h1'=>(bool)preg_match('/<h1/i',$row['content']??''),
        'word_count'=>str_word_count(strip_tags($row['content']??''))]];
}

if ($action==='analyze') {
    $content=$p['content']??''; $title=$p['title']??''; $metaTitle=$p['meta_title']??''; $metaDesc=$p['meta_description']??''; $keyword=$p['keyword']??'';
    $checks=[]; $score=0; $maxScore=100;
    // Title
    $tLen=mb_strlen($title);
    $checks[]=['name'=>'Titre','score'=>$tLen>=10&&$tLen<=70?15:($tLen>0?8:0),'max'=>15,'detail'=>"{$tLen} caractères".(($tLen<10||$tLen>70)?' (idéal: 10-70)':'')];
    // Meta title
    $mtLen=mb_strlen($metaTitle);
    $checks[]=['name'=>'Meta Title','score'=>$mtLen>=30&&$mtLen<=60?15:($mtLen>0?7:0),'max'=>15,'detail'=>"{$mtLen} car.".(($mtLen<30||$mtLen>60)?' (idéal: 30-60)':'')];
    // Meta description
    $mdLen=mb_strlen($metaDesc);
    $checks[]=['name'=>'Meta Description','score'=>$mdLen>=120&&$mdLen<=160?15:($mdLen>0?7:0),'max'=>15,'detail'=>"{$mdLen} car.".(($mdLen<120||$mdLen>160)?' (idéal: 120-160)':'')];
    // Content length
    $wc=str_word_count(strip_tags($content));
    $checks[]=['name'=>'Longueur contenu','score'=>$wc>=300?15:($wc>=100?10:($wc>0?5:0)),'max'=>15,'detail'=>"{$wc} mots".(($wc<300)?' (min recommandé: 300)':'')];
    // H1
    $hasH1=(bool)preg_match('/<h1/i',$content);
    $checks[]=['name'=>'Balise H1','score'=>$hasH1?10:0,'max'=>10,'detail'=>$hasH1?'Présente':'Absente'];
    // H2
    preg_match_all('/<h2/i',$content,$h2m);
    $h2c=count($h2m[0]);
    $checks[]=['name'=>'Balises H2','score'=>$h2c>=2?10:($h2c>0?5:0),'max'=>10,'detail'=>"{$h2c} trouvée(s)"];
    // Keyword
    if ($keyword) {
        $kc=substr_count(strtolower(strip_tags($content)),strtolower($keyword));
        $density=$wc>0?round(($kc/$wc)*100,1):0;
        $checks[]=['name'=>'Mot-clé principal','score'=>$kc>=2&&$density<=3?10:($kc>0?5:0),'max'=>10,'detail'=>"{$kc} occurrences ({$density}%)"];
    } else {
        $checks[]=['name'=>'Mot-clé principal','score'=>0,'max'=>10,'detail'=>'Non renseigné'];
    }
    // Images alt
    preg_match_all('/<img[^>]*>/i',$content,$imgs);
    $imgCount=count($imgs[0]);
    $altCount=0; foreach($imgs[0] as $img) { if (preg_match('/alt=["\'][^"\']+["\']/',$img)) $altCount++; }
    $checks[]=['name'=>'Images + alt','score'=>$imgCount>0?($altCount===$imgCount?10:5):5,'max'=>10,'detail'=>$imgCount>0?"{$altCount}/{$imgCount} avec alt":"Aucune image"];

    $totalScore=array_sum(array_column($checks,'score'));
    $totalMax=array_sum(array_column($checks,'max'));
    $pct=$totalMax>0?round(($totalScore/$totalMax)*100):0;

    return ['success'=>true,'score'=>$pct,'checks'=>$checks,'total_score'=>$totalScore,'total_max'=>$totalMax];
}

if ($action==='update-score' && $method==='POST') {
    $id=(int)($p['id']??0); $score=(int)($p['score']??0); $type=$p['type']??'page';
    $tbl=$type==='article'?'articles':'pages';
    $pdo->prepare("UPDATE `{$tbl}` SET seo_score=? WHERE id=?")->execute([$score,$id]);
    return ['success'=>true,'message'=>'Score mis à jour'];
}

if ($action==='pages-seo') {
    $stmt=$pdo->query("SELECT id, title, slug, meta_title, meta_description, seo_score, status FROM pages ORDER BY seo_score ASC");
    return ['success'=>true,'pages'=>$stmt->fetchAll()];
}

if ($action==='articles-seo') {
    $tbl='articles'; try{$pdo->query("SELECT 1 FROM articles LIMIT 1");}catch(Exception $e){$tbl='blog_articles';}
    $stmt=$pdo->query("SELECT id, title, slug, meta_title, meta_description, seo_score, status FROM `{$tbl}` ORDER BY seo_score ASC");
    return ['success'=>true,'articles'=>$stmt->fetchAll()];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['score','analyze','update-score','pages-seo','articles-seo']];
