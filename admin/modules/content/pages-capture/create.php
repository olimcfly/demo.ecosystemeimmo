<?php
// /admin/modules/capture-pages/create.php
// Éditeur de page de capture

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$page = null;
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'create';
$message = '';
$messageType = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Charger la page si édition
    if ($action === 'edit' && $pageId > 0) {
        $page = $pdo->query("
            SELECT * FROM capture_pages WHERE id = $pageId
        ")->fetch(PDO::FETCH_ASSOC);
        
        if (!$page) {
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
    $sous_titre = trim($_POST['sous_titre'] ?? '');
    $description_hero = trim($_POST['description_hero'] ?? '');
    $cta_texte = trim($_POST['cta_texte'] ?? '');
    $cta_description = trim($_POST['cta_description'] ?? '');
    $statut = trim($_POST['statut'] ?? 'draft');
    $guides_personas = isset($_POST['guides_personas']) ? $_POST['guides_personas'] : [];
    
    if (empty($titre) || empty($slug)) {
        $message = 'Titre et slug sont obligatoires.';
        $messageType = 'error';
    } else {
        try {
            if ($action === 'create') {
                // Créer une nouvelle page
                $stmt = $pdo->prepare("
                    INSERT INTO capture_pages (
                        titre, slug, sous_titre, description_hero, 
                        cta_texte, cta_description, guides_personas, 
                        statut, created_at
                    ) VALUES (
                        :titre, :slug, :sous_titre, :description_hero,
                        :cta_texte, :cta_description, :guides_personas,
                        :statut, NOW()
                    )
                ");
                
                $stmt->execute([
                    ':titre' => $titre,
                    ':slug' => $slug,
                    ':sous_titre' => $sous_titre,
                    ':description_hero' => $description_hero,
                    ':cta_texte' => $cta_texte,
                    ':cta_description' => $cta_description,
                    ':guides_personas' => json_encode($guides_personas),
                    ':statut' => $statut
                ]);
                
                $message = 'Page créée avec succès!';
                $messageType = 'success';
                $pageId = $pdo->lastInsertId();
                
            } else {
                // Mettre à jour
                $stmt = $pdo->prepare("
                    UPDATE capture_pages SET
                        titre = :titre,
                        slug = :slug,
                        sous_titre = :sous_titre,
                        description_hero = :description_hero,
                        cta_texte = :cta_texte,
                        cta_description = :cta_description,
                        guides_personas = :guides_personas,
                        statut = :statut,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':titre' => $titre,
                    ':slug' => $slug,
                    ':sous_titre' => $sous_titre,
                    ':description_hero' => $description_hero,
                    ':cta_texte' => $cta_texte,
                    ':cta_description' => $cta_description,
                    ':guides_personas' => json_encode($guides_personas),
                    ':statut' => $statut,
                    ':id' => $pageId
                ]);
                
                $message = 'Page mise à jour avec succès!';
                $messageType = 'success';
            }
            
            // Recharger les données
            $page = $pdo->query("SELECT * FROM capture_pages WHERE id = $pageId")->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $message = 'Erreur lors de l\'enregistrement.';
            $messageType = 'error';
            error_log("Erreur save: " . $e->getMessage());
        }
    }
}

?>

<style>
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .editor-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #1a202c;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }

    .form-card h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
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
        font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
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
        min-height: 120px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .required {
        color: #ef4444;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn-group .btn {
        flex: 1;
    }

    .preview-section {
        background: #f9fafb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    .preview-section h3 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 700;
        color: #1a202c;
    }

    .preview-text {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .btn-group {
            flex-direction: column;
        }
    }
</style>

<div>
    <!-- Header -->
    <div class="editor-header">
        <h1><?php echo $action === 'create' ? '✨ Créer une page' : '✎ Éditer la page'; ?></h1>
        <a href="/admin/dashboard.php?page=capture-pages" class="btn btn-secondary">
            ← Retour
        </a>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST">
        <!-- Section 1: Infos générales -->
        <div class="form-card">
            <h2>📋 Informations générales</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="titre">Titre <span class="required">*</span></label>
                    <input type="text" id="titre" name="titre" placeholder="Ex: Guides Marketing Immobilier" required 
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
                <label for="sous_titre">Sous-titre <span class="required">*</span></label>
                <input type="text" id="sous_titre" name="sous_titre" placeholder="Ex: Maîtrisez le digital immobilier" required 
                       value="<?php echo htmlspecialchars($page['sous_titre'] ?? ''); ?>" maxlength="255">
            </div>

            <div class="form-group">
                <label for="description_hero">Description du hero <span class="required">*</span></label>
                <textarea id="description_hero" name="description_hero" placeholder="Texte principal qui s'affiche en grand..." required><?php echo htmlspecialchars($page['description_hero'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Section 2: CTA & Call-to-action -->
        <div class="form-card">
            <h2>🎯 Formulaire de capture</h2>
            
            <div class="form-group">
                <label for="cta_texte">Bouton CTA <span class="required">*</span></label>
                <input type="text" id="cta_texte" name="cta_texte" placeholder="Ex: Accès gratuit aux guides" required 
                       value="<?php echo htmlspecialchars($page['cta_texte'] ?? ''); ?>" maxlength="100">
            </div>

            <div class="form-group">
                <label for="cta_description">Description du formulaire</label>
                <textarea id="cta_description" name="cta_description" placeholder="Texte explicatif du formulaire..."><?php echo htmlspecialchars($page['cta_description'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Section 3: Configuration -->
        <div class="form-card">
            <h2>⚙️ Configuration</h2>
            
            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut">
                    <option value="draft" <?php echo ($page['statut'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>
                        ✎ Brouillon
                    </option>
                    <option value="active" <?php echo ($page['statut'] ?? 'draft') === 'active' ? 'selected' : ''; ?>>
                        ✓ Active
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Personas à afficher</label>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="guides_personas[]" value="Vendeur" 
                               <?php echo !$page || (strpos($page['guides_personas'] ?? '', 'Vendeur') !== false) ? 'checked' : ''; ?>>
                        Vendeur / Agent immobilier
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="guides_personas[]" value="Propriétaire" 
                               <?php echo !$page || (strpos($page['guides_personas'] ?? '', 'Propriétaire') !== false) ? 'checked' : ''; ?>>
                        Propriétaire vendeur
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 4: Aperçu -->
        <div class="form-card">
            <h2>👁️ Aperçu</h2>
            <div class="preview-section">
                <h3>En-tête</h3>
                <div class="preview-text">
                    <strong id="previewTitle"><?php echo htmlspecialchars($page['titre'] ?? 'Titre de la page'); ?></strong><br>
                    <span id="previewSoustitre"><?php echo htmlspecialchars($page['sous_titre'] ?? 'Sous-titre'); ?></span>
                </div>
            </div>

            <div class="preview-section">
                <h3>Description principale</h3>
                <div class="preview-text" id="previewDesc">
                    <?php echo nl2br(htmlspecialchars($page['description_hero'] ?? 'Description...')); ?>
                </div>
            </div>

            <div class="preview-section">
                <h3>Bouton CTA</h3>
                <div class="preview-text">
                    <strong id="previewCTA" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 20px; border-radius: 6px; display: inline-block;">
                        <?php echo htmlspecialchars($page['cta_texte'] ?? '🚀 Accès gratuit'); ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                💾 Enregistrer
            </button>
            <a href="/admin/dashboard.php?page=capture-pages" class="btn btn-secondary">
                Annuler
            </a>
        </div>
    </form>
</div>

<script>
    // Mettre à jour l'aperçu en temps réel
    document.getElementById('titre').addEventListener('input', function() {
        document.getElementById('previewTitle').textContent = this.value || 'Titre de la page';
    });

    document.getElementById('slug').addEventListener('input', function() {
        document.getElementById('slugPreview').textContent = this.value || 'guides-marketing';
    });

    document.getElementById('sous_titre').addEventListener('input', function() {
        document.getElementById('previewSoustitre').textContent = this.value || 'Sous-titre';
    });

    document.getElementById('description_hero').addEventListener('input', function() {
        document.getElementById('previewDesc').textContent = this.value || 'Description...';
    });

    document.getElementById('cta_texte').addEventListener('input', function() {
        document.getElementById('previewCTA').textContent = this.value || '🚀 Accès gratuit';
    });
</script>