<?php
/**
 * Design — Éditeur de Footer → Redirection Builder Pro
 */
$connection = null;
if (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
elseif (isset($db) && $db instanceof PDO) $connection = $db;
else {
    $dbConfig = __DIR__ . '/../../config/database.php';
    if (file_exists($dbConfig)) {
        require_once $dbConfig;
        if (isset($db) && $db instanceof PDO) $connection = $db;
        elseif (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
    }
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($action === 'create' && $connection) {
    try {
        $stmt = $connection->prepare("
            INSERT INTO footers (name, slug, status, is_default, created_at) 
            VALUES (?, ?, 'active', 0, NOW())
        ");
        $stmt->execute(['Nouveau Footer', 'footer-' . time()]);
        $editId = (int)$connection->lastInsertId();
    } catch (Exception $e) {
        echo '<script>window.location.href = "/admin/dashboard.php?page=design-footers&msg=error";</script>';
        return;
    }
}

if ($editId > 0) {
    $url = '/admin/modules/builder/editor.php?context=footer&entity_id=' . $editId;
    echo '<div style="display:flex;align-items:center;justify-content:center;min-height:300px;gap:12px;color:#64748b;">
        <i class="fas fa-spinner fa-spin" style="font-size:20px;color:#3b82f6;"></i>
        <span style="font-size:14px;">Ouverture du Builder Pro...</span>
    </div>';
    echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
    return;
}

echo '<script>window.location.href = "/admin/dashboard.php?page=design-footers";</script>';
