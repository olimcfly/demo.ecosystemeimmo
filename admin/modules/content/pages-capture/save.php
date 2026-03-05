<?php
// /admin/modules/capture-pages/save.php
// Traitement du formulaire (créer/éditer)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=capture-pages');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

// Récupérer les données
$id = (int)($_POST['id'] ?? 0);
$titre = trim($_POST['titre'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$type = trim($_POST['type'] ?? 'guide');
$template = trim($_POST['template'] ?? 'simple');
$contenu = trim($_POST['contenu'] ?? '');
$headline = trim($_POST['headline'] ?? '');
$sous_titre = trim($_POST['sous_titre'] ?? '');
$image_url = trim($_POST['image_url'] ?? '');
$cta_text = trim($_POST['cta_text'] ?? '');
$page_merci_url = trim($_POST['page_merci_url'] ?? '');
$status = trim($_POST['status'] ?? 'active');

// Validation
$errors = [];

if (empty($titre)) {
    $errors[] = 'Le titre est obligatoire.';
}

if (empty($slug)) {
    $errors[] = 'Le slug est obligatoire.';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ?page=capture-pages&action=' . ($id === 0 ? 'create' : 'edit&id=' . $id));
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    if ($id === 0) {
        // CRÉER
        $stmt = $pdo->prepare("
            INSERT INTO captures (titre, slug, description, type, template, contenu, headline, sous_titre, 
                                image_url, cta_text, page_merci_url, status, conversions, vues, created_at, updated_at)
            VALUES (:titre, :slug, :description, :type, :template, :contenu, :headline, :sous_titre, 
                   :image_url, :cta_text, :page_merci_url, :status, 0, 0, NOW(), NOW())
        ");
        
        $stmt->execute([
            'titre' => $titre,
            'slug' => $slug,
            'description' => $description,
            'type' => $type,
            'template' => $template,
            'contenu' => $contenu,
            'headline' => $headline,
            'sous_titre' => $sous_titre,
            'image_url' => $image_url,
            'cta_text' => $cta_text,
            'page_merci_url' => $page_merci_url,
            'status' => $status
        ]);
        
        $_SESSION['success_message'] = '✓ Page créée avec succès!';
        
    } else {
        // ÉDITER
        $stmt = $pdo->prepare("
            UPDATE captures 
            SET titre = :titre, slug = :slug, description = :description, type = :type, template = :template,
                contenu = :contenu, headline = :headline, sous_titre = :sous_titre, image_url = :image_url,
                cta_text = :cta_text, page_merci_url = :page_merci_url, status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'titre' => $titre,
            'slug' => $slug,
            'description' => $description,
            'type' => $type,
            'template' => $template,
            'contenu' => $contenu,
            'headline' => $headline,
            'sous_titre' => $sous_titre,
            'image_url' => $image_url,
            'cta_text' => $cta_text,
            'page_merci_url' => $page_merci_url,
            'status' => $status,
            'id' => $id
        ]);
        
        $_SESSION['success_message'] = '✓ Page mise à jour!';
    }
    
    header('Location: ?page=capture-pages');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Erreur: ' . $e->getMessage();
    header('Location: ?page=capture-pages&action=' . ($id === 0 ? 'create' : 'edit&id=' . $id));
    exit;
}
?>