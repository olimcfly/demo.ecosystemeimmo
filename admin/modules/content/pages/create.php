<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Création de Page — Crée en DB puis ouvre Builder Pro
 * /admin/modules/content/pages/create.php
 * ══════════════════════════════════════════════════════════════
 *
 * Inclus par dashboard.php (headers déjà envoyés)
 * Crée une page vide (brouillon), puis redirige via JS vers Builder Pro.
 */

// DB disponible via dashboard.php
if (!isset($pdo) && isset($db)) $pdo = $db;

// Détecter la table
$tableName = 'pages';
foreach (['pages', 'cms_pages'] as $t) {
    try { $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); $tableName = $t; break; } 
    catch (PDOException $e) { continue; }
}

// Générer un slug unique
$baseSlug = 'nouvelle-page';
$slug = $baseSlug;
$n = 1;
while (true) {
    $stmt = $pdo->prepare("SELECT id FROM `{$tableName}` WHERE slug = ?");
    $stmt->execute([$slug]);
    if (!$stmt->fetch()) break;
    $slug = $baseSlug . '-' . $n++;
    if ($n > 100) { $slug = $baseSlug . '-' . bin2hex(random_bytes(3)); break; }
}

$redirectUrl = '/admin/dashboard.php?page=pages'; // Fallback

try {
    $stmt = $pdo->prepare("INSERT INTO `{$tableName}` (title, slug, status, content) VALUES (?, ?, 'draft', '')");
    $stmt->execute(['Nouvelle page', $slug]);
    $newId = (int)$pdo->lastInsertId();
    
    $redirectUrl = '/admin/modules/builder/builder/editor.php?context=landing&entity_id=' . $newId;
    
} catch (PDOException $e) {
    $error = 'Erreur création : ' . htmlspecialchars($e->getMessage());
}
?>

<?php if (!empty($error)): ?>
<div style="text-align:center;padding:40px">
    <i class="fas fa-exclamation-triangle" style="font-size:32px;color:#ef4444;display:block;margin-bottom:12px"></i>
    <p style="color:#ef4444;margin-bottom:16px"><?= $error ?></p>
    <a href="/admin/dashboard.php?page=pages" style="color:#3b82f6">← Retour aux pages</a>
</div>
<?php else: ?>
<script>window.location.href = "<?= $redirectUrl ?>";</script>
<div style="display:flex;align-items:center;justify-content:center;min-height:300px;color:#94a3b8;gap:12px">
    <i class="fas fa-spinner fa-spin" style="font-size:20px;color:#3b82f6"></i>
    <span>Création de la page et ouverture du Builder Pro...</span>
</div>
<?php endif; ?>