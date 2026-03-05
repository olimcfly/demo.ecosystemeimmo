<?php
// /admin/modules/pages/action.php - Routeur pour les actions

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Redirection selon l'action
if ($action === 'edit') {
    header("Location: /admin/dashboard.php?page=pages&action=edit&id=$id");
} elseif ($action === 'create') {
    header("Location: /admin/dashboard.php?page=pages&action=create");
} elseif ($action === 'import') {
    header("Location: /admin/dashboard.php?page=pages&action=import");
} elseif ($action === 'delete') {
    require_once __DIR__ . '/delete.php';
} else {
    header("Location: /admin/dashboard.php?page=pages");
}
exit;
?>
```

---

## 🚀 **STRUCTURE FINALE**
```
/admin/modules/pages/
├── index.php           ← Affiche la liste + stats
├── edit.php            ← Crée/édite une page  
├── delete.php          ← Supprime une page
├── import.php          ← Import depuis Bordeaux
└── action.php          ← Routeur d'actions