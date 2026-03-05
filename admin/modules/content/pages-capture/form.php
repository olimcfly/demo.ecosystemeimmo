<?php
// /admin/modules/capture-pages/form.php
// Builder de pages de capture

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$page = null;
$guides = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Charger les guides
    $guides = $pdo->query("
        SELECT id, titre, persona 
        FROM guides 
        WHERE status IN ('published', 'public')
        ORDER BY persona, titre
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Charger la page si édition
    if ($id > 0) {
        $page = $pdo->query("SELECT * FROM capture_pages WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        if (!$page) {
            $_SESSION['success'] = 'Page non trouvée.';
            header('Location: /admin/dashboard.php?page=capture-pages');
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
}

// Traiter la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description_courte = trim($_POST['description_courte'] ?? '');
    $description_hero = trim($_POST['description_hero'] ?? '');
    $cta_titre = trim($_POST['cta_titre'] ?? '');
    $cta_description = trim($_POST['cta_description'] ?? '');
    $cta_texte = trim($_POST['cta_texte'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');
    $guide_ids = isset($_POST['guide_ids']) ? json_encode($_POST['guide_ids']) : '[]';
    
    if (empty($titre) || empty($slug)) {
        $message = 'Titre et slug obligatoires.';
        $messageType = 'error';
    } else {
        try {
            if ($id === 0) {
                // Créer
                $stmt = $pdo->prepare("
                    INSERT INTO capture_pages (
                        titre, slug, description_courte, description_hero,
                        cta_titre, cta_description, cta_texte,
                        guide_ids, status, conversions, created_at
                    ) VALUES (
                        :titre, :slug, :description_courte, :description_hero,
                        :cta_titre, :cta_description, :cta_texte,
                        :guide_ids, :status, 0, NOW()
                    )
                ");
                $stmt->execute([
                    ':titre' => $titre,
                    ':slug' => $slug,
                    ':description_courte' => $description_courte,
                    ':description_hero' => $description_hero,
                    ':cta_titre' => $cta_titre,
                    ':cta_description' => $cta_description,
                    ':cta_texte' => $cta_texte,
                    ':guide_ids' => $guide_ids,
                    ':status' => $status
                ]);
                $message = 'Page créée!';
                $id = $pdo->lastInsertId();
            } else {
                // Éditer
                $stmt = $pdo->prepare("
                    UPDATE capture_pages SET
                        titre = :titre,
                        slug = :slug,
                        description_courte = :description_courte,
                        description_hero = :description_hero,
                        cta_titre = :cta_titre,
                        cta_description = :cta_description,
                        cta_texte = :cta_texte,
                        guide_ids = :guide_ids,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':titre' => $titre,
                    ':slug' => $slug,
                    ':description_courte' => $description_courte,
                    ':description_hero' => $description_hero,
                    ':cta_titre' => $cta_titre,
                    ':cta_description' => $cta_description,
                    ':cta_texte' => $cta_texte,
                    ':guide_ids' => $guide_ids,
                    ':status' => $status,
                    ':id' => $id
                ]);
                $message = 'Page mise à jour!';
            }
            
            $messageType = 'success';
            $page = $pdo->query("SELECT * FROM capture_pages WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $message = 'Erreur: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$selectedGuides = $page ? json_decode($page['guide_ids'] ?? '[]', true) : [];

?>

<style>
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .header h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #1a202c;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }

    .btn-secondary {
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid;
    }

    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #047857;
    }

    .alert-error {
        background: #fee2e2;
        border-color: #dc2626;
        color: #991b1b;
    }

    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
    }

    .form-card h2 {
        margin: 0 0 20px 0;
        font-size: 16px;
        font-weight: 700;
        color: #1a202c;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
        font-size: 13px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 13px;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .guides-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .guide-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 6px;
    }

    .guide-item input[type="checkbox"] {
        width: auto;
    }

    .guide-item label {
        margin: 0;
        font-weight: normal;
        font-size: 13px;
        flex: 1;
    }

    .guide-persona {
        font-size: 11px;
        color: #9ca3af;
        padding: 2px 8px;
        background: white;
        border-radius: 3px;
    }

    .required {
        color: #ef4444;
    }

    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 30px;
    }

    .btn-submit {
        flex: 1;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <h1><?php echo $id > 0 ? '✎ Éditer la page' : '✨ Créer une page'; ?></h1>
        <a href="/admin/dashboard.php?page=capture-pages" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="POST">
        <!-- Section 1: Infos -->
        <div class="form-card">
            <h2>📋 Informations générales</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="titre">Titre <span class="required">*</span></label>
                    <input type="text" id="titre" name="titre" placeholder="Ex: Guides marketing immobilier" required 
                           value="<?php echo htmlspecialchars($page['titre'] ?? ''); ?>" maxlength="255">
                </div>
                <div class="form-group">
                    <label for="slug">URL slug <span class="required">*</span></label>
                    <input type="text" id="slug" name="slug" placeholder="ex: guides-marketing" required 
                           value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" maxlength="255">
                    <small style="color: #9ca3af; margin-top: 5px; display: block;">
                        URL: /capture/<span id="slugPreview"><?php echo htmlspecialchars($page['slug'] ?? 'guides-marketing'); ?></span>
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label for="description_courte">Sous-titre <span class="required">*</span></label>
                <input type="text" id="description_courte" name="description_courte" placeholder="Ex: Maîtrisez le digital" required 
                       value="<?php echo htmlspecialchars($page['description_courte'] ?? ''); ?>" maxlength="255">
            </div>

            <div class="form-group">
                <label for="description_hero">Description principale <span class="required">*</span></label>
                <textarea id="description_hero" name="description_hero" placeholder="Texte qui s'affiche en grand..." required><?php echo htmlspecialchars($page['description_hero'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Section 2: Formulaire -->
        <div class="form-card">
            <h2>🎯 Formulaire de capture</h2>
            
            <div class="form-group">
                <label for="cta_titre">Titre du formulaire <span class="required">*</span></label>
                <input type="text" id="cta_titre" name="cta_titre" placeholder="Ex: Accès gratuit" required 
                       value="<?php echo htmlspecialchars($page['cta_titre'] ?? ''); ?>" maxlength="100">
            </div>

            <div class="form-group">
                <label for="cta_description">Description du formulaire</label>
                <textarea id="cta_description" name="cta_description" placeholder="Explique ce qu'il faut faire..."><?php echo htmlspecialchars($page['cta_description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="cta_texte">Texte du bouton <span class="required">*</span></label>
                <input type="text" id="cta_texte" name="cta_texte" placeholder="Ex: Télécharger" required 
                       value="<?php echo htmlspecialchars($page['cta_texte'] ?? ''); ?>" maxlength="100">
            </div>
        </div>

        <!-- Section 3: Guides -->
        <div class="form-card">
            <h2>📚 Guides à afficher</h2>
            <p style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
                Sélectionnez les guides à afficher sur cette page de capture:
            </p>
            
            <div class="guides-list">
                <?php foreach ($guides as $guide): ?>
                    <div class="guide-item">
                        <input type="checkbox" id="guide_<?php echo $guide['id']; ?>" name="guide_ids[]" 
                               value="<?php echo $guide['id']; ?>"
                               <?php echo in_array($guide['id'], $selectedGuides) ? 'checked' : ''; ?>>
                        <label for="guide_<?php echo $guide['id']; ?>">
                            <?php echo htmlspecialchars($guide['titre']); ?>
                        </label>
                        <span class="guide-persona"><?php echo htmlspecialchars($guide['persona']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 4: Configuration -->
        <div class="form-card">
            <h2>⚙️ Configuration</h2>
            
            <div class="form-group">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <option value="draft" <?php echo ($page['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>
                        ✎ Brouillon (non visible)
                    </option>
                    <option value="published" <?php echo ($page['status'] ?? 'draft') === 'published' ? 'selected' : ''; ?>>
                        ✓ Publié (visible)
                    </option>
                </select>
            </div>
        </div>

        <!-- Boutons -->
        <div class="button-group">
            <button type="submit" class="btn-submit">💾 Enregistrer</button>
            <a href="/admin/dashboard.php?page=capture-pages" class="btn btn-secondary" style="padding: 12px 20px;">Annuler</a>
        </div>
    </form>
</div>

<script>
    // Mettre à jour slug preview
    document.getElementById('slug').addEventListener('input', function() {
        document.getElementById('slugPreview').textContent = this.value || 'guides-marketing';
    });
</script>