<?php
// /admin/modules/capture-pages/index.php
// ROUTEUR COMPLET - Gère LIST, CREATE, EDIT, SAVE, DELETE

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

// ═══════════════════════════════════════════════════════════
// VARIABLES GLOBALES
// ═══════════════════════════════════════════════════════════

$action = $_GET['action'] ?? 'list';
$pages = [];
$page = null;
$message = '';
$messageType = '';
$pdo = null;

// ═══════════════════════════════════════════════════════════
// CONNEXION BD
// ═══════════════════════════════════════════════════════════

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    $message = '❌ Erreur connexion BD: ' . $e->getMessage();
    $messageType = 'error';
    $action = 'error';
}

// ═══════════════════════════════════════════════════════════
// TRAITER LES ACTIONS
// ═══════════════════════════════════════════════════════════

if ($pdo) {
    switch ($action) {
        // ═══════════════════════════════════════════════════════════
        // ACTION: LIST - Affiche le tableau
        // ═══════════════════════════════════════════════════════════
        case 'list':
        default:
            try {
                $pages = $pdo->query("
                    SELECT id, titre, slug, description, type, status, conversions, vues, created_at
                    FROM captures
                    ORDER BY created_at DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $message = '❌ Erreur: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        // ═══════════════════════════════════════════════════════════
        // ACTION: CREATE - Affiche le formulaire vide
        // ═══════════════════════════════════════════════════════════
        case 'create':
            $page = [
                'id' => null,
                'titre' => '',
                'slug' => '',
                'description' => '',
                'type' => 'guide',
                'template' => 'simple',
                'contenu' => '',
                'headline' => '',
                'sous_titre' => '',
                'image_url' => '',
                'cta_text' => '',
                'champs_formulaire' => '[]',
                'page_merci_url' => '',
                'status' => 'active'
            ];
            break;

        // ═══════════════════════════════════════════════════════════
        // ACTION: EDIT - Affiche le formulaire avec les données
        // ═══════════════════════════════════════════════════════════
        case 'edit':
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0) {
                try {
                    $page = $pdo->query("SELECT * FROM captures WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
                    if (!$page) {
                        $message = 'Page non trouvée.';
                        $messageType = 'error';
                        $action = 'list';
                        $pages = $pdo->query("
                            SELECT id, titre, slug, description, type, status, conversions, vues, created_at
                            FROM captures
                            ORDER BY created_at DESC
                        ")->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $message = '❌ Erreur: ' . $e->getMessage();
                    $messageType = 'error';
                    $action = 'list';
                }
            }
            break;

        // ═══════════════════════════════════════════════════════════
        // ACTION: SAVE - Traite la soumission du formulaire (POST)
        // ═══════════════════════════════════════════════════════════
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
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
                    if (empty($titre) || empty($slug)) {
                        throw new Exception('Titre et slug sont obligatoires.');
                    }

                    if ($id === 0) {
                        // CRÉER une nouvelle page
                        $stmt = $pdo->prepare("
                            INSERT INTO captures (titre, slug, description, type, template, contenu, headline, 
                                                sous_titre, image_url, cta_text, page_merci_url, status, 
                                                conversions, vues, created_at, updated_at)
                            VALUES (:titre, :slug, :description, :type, :template, :contenu, :headline, 
                                   :sous_titre, :image_url, :cta_text, :page_merci_url, :status, 0, 0, NOW(), NOW())
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
                        
                        $message = '✓ Page créée avec succès!';
                        $messageType = 'success';
                    } else {
                        // ÉDITER une page existante
                        $stmt = $pdo->prepare("
                            UPDATE captures 
                            SET titre = :titre, slug = :slug, description = :description, type = :type, 
                                template = :template, contenu = :contenu, headline = :headline, 
                                sous_titre = :sous_titre, image_url = :image_url, cta_text = :cta_text,
                                page_merci_url = :page_merci_url, status = :status, updated_at = NOW()
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
                        
                        $message = '✓ Page mise à jour!';
                        $messageType = 'success';
                    }

                    // Rediriger vers la liste
                    $action = 'list';
                    $pages = $pdo->query("
                        SELECT id, titre, slug, description, type, status, conversions, vues, created_at
                        FROM captures
                        ORDER BY created_at DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                } catch (Exception $e) {
                    $message = '❌ Erreur: ' . $e->getMessage();
                    $messageType = 'error';
                    $action = 'edit';
                }
            }
            break;

        // ═══════════════════════════════════════════════════════════
        // ACTION: DELETE - Supprime une page
        // ═══════════════════════════════════════════════════════════
        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0) {
                try {
                    $pdo->query("DELETE FROM captures WHERE id = $id");
                    $message = '✓ Page supprimée!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = '❌ Erreur: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
            $action = 'list';
            $pages = $pdo->query("
                SELECT id, titre, slug, description, type, status, conversions, vues, created_at
                FROM captures
                ORDER BY created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}

?>

<style>
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; gap: 15px; }
    .header h1 { margin: 0; font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: all 0.3s; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
    .btn-secondary { background: white; border: 1px solid #e5e7eb; color: #374151; padding: 6px 12px; font-size: 12px; border-radius: 6px; }
    .btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; font-size: 12px; border-radius: 6px; }
    .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid; }
    .alert-success { background: #d1fae5; border-color: #10b981; color: #047857; }
    .alert-error { background: #fee2e2; border-color: #dc2626; color: #991b1b; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .stat-box { background: linear-gradient(135deg, #f0f4ff 0%, #f5f3ff 100%); border: 1px solid #e0e7ff; padding: 20px; border-radius: 8px; text-align: center; }
    .stat-number { font-size: 28px; font-weight: 700; color: #667eea; }
    .stat-label { font-size: 11px; color: #6b7280; margin-top: 8px; text-transform: uppercase; font-weight: 600; }
    .table { background: white; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
    .table table { width: 100%; border-collapse: collapse; }
    .table th { background: #f9fafb; padding: 15px; text-align: left; font-weight: 600; font-size: 12px; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
    .table td { padding: 15px; border-bottom: 1px solid #e5e7eb; }
    .table tr:hover { background: #f9fafb; }
    .title { font-weight: 600; color: #1a202c; margin-bottom: 4px; }
    .subtitle { font-size: 12px; color: #9ca3af; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-inactive { background: #fef3c7; color: #92400e; }
    .status-archived { background: #e5e7eb; color: #374151; }
    .type-badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 10px; font-weight: 700; background: #e0e7ff; color: #4f46e5; }
    .actions { display: flex; gap: 8px; }
    .empty { text-align: center; padding: 60px 20px; color: #6b7280; }
    .form-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 25px; margin-bottom: 20px; }
    .form-card h2 { margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: #1a202c; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: 13px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-family: inherit; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .form-group textarea { resize: vertical; min-height: 100px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .button-group { display: flex; gap: 12px; margin-top: 30px; }
    .btn-submit { flex: 1; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
    @media (max-width: 768px) { .form-row, .form-row-3 { grid-template-columns: 1fr; } .table table { min-width: 700px; } }
</style>

<div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list' || $action === ''): ?>
        <!-- ═══════════════════════════════════════════════════════════
             LIST VIEW
             ═══════════════════════════════════════════════════════════ -->
        <div class="header">
            <h1>🎯 Pages de Capture</h1>
            <a href="?page=capture-pages&action=create" class="btn btn-primary">✨ Créer une page</a>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo count($pages); ?></div>
                <div class="stat-label">Pages</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count(array_filter($pages, fn($p) => $p['status'] === 'active')); ?></div>
                <div class="stat-label">Actives</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo array_sum(array_column($pages, 'conversions')); ?></div>
                <div class="stat-label">Conversions</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo array_sum(array_column($pages, 'vues')); ?></div>
                <div class="stat-label">Vues</div>
            </div>
        </div>

        <?php if (count($pages) > 0): ?>
            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Conversions / Vues</th>
                            <th>Créé</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $p): ?>
                            <tr>
                                <td>
                                    <div class="title"><?php echo htmlspecialchars($p['titre']); ?></div>
                                    <div class="subtitle"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 60)); ?></div>
                                </td>
                                <td>
                                    <span class="type-badge"><?php echo htmlspecialchars($p['type']); ?></span>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $p['status']; ?>">
                                        <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $p['conversions']; ?></strong> / <?php echo $p['vues']; ?>
                                </td>
                                <td><small><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></small></td>
                                <td style="text-align: right;">
                                    <div class="actions">
                                        <a href="?page=capture-pages&action=edit&id=<?php echo $p['id']; ?>" class="btn btn-secondary">✎ Éditer</a>
                                        <a href="/capture/<?php echo htmlspecialchars($p['slug']); ?>" target="_blank" class="btn btn-secondary">👁️ Voir</a>
                                        <a href="?page=capture-pages&action=delete&id=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table">
                <div class="empty">
                    <h3>🎯 Aucune page créée</h3>
                    <p>Commencez par créer votre première page de capture.</p>
                    <a href="?page=capture-pages&action=create" class="btn btn-primary" style="margin-top: 20px;">✨ Créer une page</a>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- ═══════════════════════════════════════════════════════════
             FORM VIEW (Create / Edit)
             ═══════════════════════════════════════════════════════════ -->
        <?php if ($page): ?>
            <div class="header">
                <h1><?php echo $action === 'create' ? '✨ Créer une page' : '✎ Éditer la page'; ?></h1>
                <a href="?page=capture-pages" class="btn btn-secondary">← Retour</a>
            </div>

            <form method="POST" action="?page=capture-pages&action=save">
                <input type="hidden" name="id" value="<?php echo $page['id'] ?? 0; ?>">

                <!-- Infos générales -->
                <div class="form-card">
                    <h2>📋 Informations générales</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="titre" placeholder="Ex: Guides marketing immobilier" required 
                                   value="<?php echo htmlspecialchars($page['titre'] ?? ''); ?>" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>URL slug <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="slug" placeholder="ex: guides-marketing" required 
                                   value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" maxlength="255">
                            <small style="color: #9ca3af; margin-top: 5px; display: block;">URL: /capture/<?php echo htmlspecialchars($page['slug'] ?? ''); ?></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description courte</label>
                        <input type="text" name="description" placeholder="Description" 
                               value="<?php echo htmlspecialchars($page['description'] ?? ''); ?>" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label>Contenu principal</label>
                        <textarea name="contenu" placeholder="Contenu de la page..."><?php echo htmlspecialchars($page['contenu'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Copywriting -->
                <div class="form-card">
                    <h2>✍️ Copywriting</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Headline (titre principal)</label>
                            <input type="text" name="headline" placeholder="Ex: Augmentez vos mandats" 
                                   value="<?php echo htmlspecialchars($page['headline'] ?? ''); ?>" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>Sous-titre</label>
                            <input type="text" name="sous_titre" placeholder="Ex: Avec le digital" 
                                   value="<?php echo htmlspecialchars($page['sous_titre'] ?? ''); ?>" maxlength="255">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Texte du bouton CTA</label>
                        <input type="text" name="cta_text" placeholder="Ex: Télécharger gratuit" 
                               value="<?php echo htmlspecialchars($page['cta_text'] ?? ''); ?>" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image_url" placeholder="Ex: /images/guide.jpg" 
                               value="<?php echo htmlspecialchars($page['image_url'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Configuration -->
                <div class="form-card">
                    <h2>⚙️ Configuration</h2>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Type de page</label>
                            <select name="type" required>
                                <option value="guide" <?php echo ($page['type'] ?? 'guide') === 'guide' ? 'selected' : ''; ?>>📚 Guide</option>
                                <option value="estimation" <?php echo ($page['type'] ?? 'guide') === 'estimation' ? 'selected' : ''; ?>>📊 Estimation</option>
                                <option value="contact" <?php echo ($page['type'] ?? 'guide') === 'contact' ? 'selected' : ''; ?>>📧 Contact</option>
                                <option value="newsletter" <?php echo ($page['type'] ?? 'guide') === 'newsletter' ? 'selected' : ''; ?>>📰 Newsletter</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Template</label>
                            <select name="template">
                                <option value="simple" <?php echo ($page['template'] ?? 'simple') === 'simple' ? 'selected' : ''; ?>>Simple</option>
                                <option value="premium" <?php echo ($page['template'] ?? 'simple') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="minimal" <?php echo ($page['template'] ?? 'simple') === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="status" required>
                                <option value="active" <?php echo ($page['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>✓ Actif</option>
                                <option value="inactive" <?php echo ($page['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>✎ Inactif</option>
                                <option value="archived" <?php echo ($page['status'] ?? 'active') === 'archived' ? 'selected' : ''; ?>>🗂️ Archivé</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Page de remerciement (URL)</label>
                        <input type="text" name="page_merci_url" placeholder="Ex: /merci.html" 
                               value="<?php echo htmlspecialchars($page['page_merci_url'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Boutons -->
                <div class="button-group">
                    <button type="submit" class="btn-submit">💾 <?php echo $action === 'create' ? 'Créer' : 'Enregistrer'; ?></button>
                    <?php if ($action === 'edit'): ?>
                        <a href="?page=capture-pages&action=delete&id=<?php echo $page['id']; ?>" class="btn-submit" style="background: #dc2626;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette page?')">🗑️ Supprimer</a>
                    <?php endif; ?>
                    <a href="?page=capture-pages" class="btn btn-secondary" style="padding: 12px 20px;">Annuler</a>
                </div>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <!-- ERROR -->
        <div class="alert alert-error">
            En développement...
        </div>
    <?php endif; ?>
</div>