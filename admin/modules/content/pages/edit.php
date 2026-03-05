<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Éditeur de Page — Redirection vers Builder Pro
 * /admin/modules/content/pages/edit.php
 * ══════════════════════════════════════════════════════════════
 *
 * Ce fichier est INCLUS par dashboard.php (headers déjà envoyés)
 * donc on utilise une redirection JavaScript.
 *
 * Flux : Listing → clic Éditer → edit.php → Builder Pro
 */

$pageId = (int)($_GET['id'] ?? 0);

if ($pageId <= 0) {
    echo '<script>window.location.href="/admin/dashboard.php?page=pages";</script>';
    echo '<p style="text-align:center;padding:40px;color:#94a3b8">Redirection vers le listing...</p>';
    return;
}

// Redirection vers Builder Pro
$builderUrl = '/admin/modules/builder/builder/editor.php?context=landing&entity_id=' . $pageId;
?>
<script>window.location.href = "<?= $builderUrl ?>";</script>
<div style="display:flex;align-items:center;justify-content:center;min-height:300px;color:#94a3b8;gap:12px">
    <i class="fas fa-spinner fa-spin" style="font-size:20px;color:#3b82f6"></i>
    <span>Ouverture du Builder Pro...</span>
</div>