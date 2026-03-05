<?php
/**
 *  /admin/api/builder/builder.php
 *  Builder Pro — save, templates, layouts
 *  Miroir de : modules/builder/builder/
 *  Tables : builder_templates, pages
 *
 *  actions: save, save-direct, templates-list, template-load, template-save, template-delete, template-apply, layouts
 */

$pdo = $ctx['pdo']; $action = $ctx['action']; $method = $ctx['method']; $p = $ctx['params'];

// ─── save (page content from builder) ───
if ($action === 'save' && $method === 'POST') {
    $pageId = (int)($p['page_id'] ?? $p['id'] ?? 0);
    $content = $p['content'] ?? $p['html'] ?? '';
    $css     = $p['css'] ?? '';
    $js      = $p['js'] ?? '';
    if ($pageId > 0) {
        $pdo->prepare("UPDATE pages SET content=?, custom_css=?, custom_js=?, updated_at=NOW() WHERE id=?")->execute([$content, $css, $js, $pageId]);
        return ['success'=>true,'message'=>'Page sauvegardée','id'=>$pageId];
    }
    return ['success'=>false,'error'=>'page_id requis'];
}

// ─── save-direct (full HTML file save) ───
if ($action === 'save-direct' && $method === 'POST') {
    $path = $p['path'] ?? '';
    $html = $p['html'] ?? '';
    if (empty($path) || empty($html)) return ['success'=>false,'error'=>'path et html requis'];
    $fullPath = realpath(__DIR__ . '/../../..') . '/' . ltrim($path, '/');
    if (strpos($fullPath, realpath(__DIR__ . '/../../..')) !== 0) return ['success'=>false,'error'=>'Chemin invalide'];
    file_put_contents($fullPath, $html);
    return ['success'=>true,'message'=>"Fichier sauvegardé: {$path}"];
}

// ─── templates-list ───
if ($action === 'templates-list') {
    $stmt = $pdo->query("SELECT id, name, slug, category, thumbnail, is_default, created_at FROM builder_templates ORDER BY is_default DESC, name ASC");
    return ['success'=>true,'templates'=>$stmt->fetchAll()];
}

// ─── template-load ───
if ($action === 'template-load') {
    $id = (int)($p['id']??0);
    $stmt = $pdo->prepare("SELECT * FROM builder_templates WHERE id=?"); $stmt->execute([$id]);
    $tpl = $stmt->fetch();
    if (!$tpl) return ['success'=>false,'error'=>'Template non trouvé','_http_code'=>404];
    return ['success'=>true,'template'=>$tpl];
}

// ─── template-save ───
if ($action === 'template-save' && $method === 'POST') {
    $id = (int)($p['id']??0);
    $fields = [
        'name'      => $p['name'] ?? 'Template',
        'slug'      => $p['slug'] ?? null,
        'category'  => $p['category'] ?? 'page',
        'content'   => $p['content'] ?? '',
        'css'       => $p['css'] ?? '',
        'js'        => $p['js'] ?? '',
        'thumbnail' => $p['thumbnail'] ?? null,
    ];
    if ($id > 0) {
        $sets=[]; $vals=[];
        foreach ($fields as $c=>$v) { $sets[]="`{$c}`=?"; $vals[]=$v; }
        $vals[]=$id;
        $pdo->prepare("UPDATE builder_templates SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
        return ['success'=>true,'message'=>'Template mis à jour','id'=>$id];
    }
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO builder_templates (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'message'=>'Template créé','id'=>(int)$pdo->lastInsertId()];
}

// ─── template-delete ───
if ($action === 'template-delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM builder_templates WHERE id=?")->execute([(int)($p['id']??0)]);
    return ['success'=>true,'message'=>'Template supprimé'];
}

// ─── template-apply ───
if ($action === 'template-apply' && $method === 'POST') {
    $tplId  = (int)($p['template_id']??0);
    $pageId = (int)($p['page_id']??0);
    $stmt = $pdo->prepare("SELECT content, css, js FROM builder_templates WHERE id=?"); $stmt->execute([$tplId]);
    $tpl = $stmt->fetch();
    if (!$tpl) return ['success'=>false,'error'=>'Template non trouvé'];
    $pdo->prepare("UPDATE pages SET content=?, custom_css=?, custom_js=? WHERE id=?")->execute([$tpl['content'],$tpl['css'],$tpl['js'],$pageId]);
    return ['success'=>true,'message'=>'Template appliqué'];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['save','save-direct','templates-list','template-load','template-save','template-delete','template-apply']];
