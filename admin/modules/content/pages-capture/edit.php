<?php
// /admin/modules/capture-pages/edit.php
// Formulaire d'édition

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$id = (int)($_GET['id'] ?? 0);
$page = null;
$message = '';
$messageType = '';

if ($id === 0) {
    header('Location: ?page=capture-pages');
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $page = $pdo->query("SELECT * FROM captures WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        header('Location: ?page=capture-pages');
        exit;
    }
    
} catch (Exception $e) {
    $message = '❌ Erreur BD: ' . $e->getMessage();
    $messageType = 'error';
}

?>

<style>
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; gap: 15px; }
    .header h1 { margin: 0; font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid; }
    .alert-success { background: #d1fae5; border-color: #10b981; color: #047857; }
    .alert-error { background: #fee2e2; border-color: #dc2626; color: #991b1b; }
    .btn-secondary { background: white; border: 1px solid #e5e7eb; color: #374151; padding: 10px 20px; font-size: 12px; border-radius: 6px; cursor: pointer; text-decoration: none; }
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
    .btn-delete { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .btn-delete:hover { background: #fecaca; }
    @media (max-width: 768px) { .form-row, .form-row-3 { grid-template-columns: 1fr; } }
</style>

<div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($page): ?>
        <div class="header">
            <h1>✎ Éditer: <?php echo htmlspecialchars($page['titre']); ?></h1>
            <a href="?page=capture-pages" class="btn-secondary">← Retour</a>
        </div>

        <form method="POST" action="?page=capture-pages&action=save">
            <input type="hidden" name="id" value="<?php echo $page['id']; ?>">

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
                        <small style="color: #9ca3af; margin-top: 5px; display: block;">URL: /capture/<?php echo htmlspecialchars($page['slug'] ?? 'guides-marketing'); ?></small>
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
                            <option value="guide" <?php echo ($page['type'] ?? '') === 'guide' ? 'selected' : ''; ?>>📚 Guide</option>
                            <option value="estimation" <?php echo ($page['type'] ?? '') === 'estimation' ? 'selected' : ''; ?>>📊 Estimation</option>
                            <option value="contact" <?php echo ($page['type'] ?? '') === 'contact' ? 'selected' : ''; ?>>📧 Contact</option>
                            <option value="newsletter" <?php echo ($page['type'] ?? '') === 'newsletter' ? 'selected' : ''; ?>>📰 Newsletter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Template</label>
                        <select name="template">
                            <option value="simple" <?php echo ($page['template'] ?? '') === 'simple' ? 'selected' : ''; ?>>Simple</option>
                            <option value="premium" <?php echo ($page['template'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="minimal" <?php echo ($page['template'] ?? '') === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status" required>
                            <option value="active" <?php echo ($page['status'] ?? '') === 'active' ? 'selected' : ''; ?>>✓ Active</option>
                            <option value="inactive" <?php echo ($page['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>✎ Inactive</option>
                            <option value="archived" <?php echo ($page['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>🗂️ Archivée</option>
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
                <button type="submit" class="btn-submit">💾 Enregistrer</button>
                <a href="?page=capture-pages&action=delete&id=<?php echo $page['id']; ?>" class="btn-submit btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette page?')">🗑️ Supprimer</a>
                <a href="?page=capture-pages" class="btn-secondary" style="padding: 12px 20px; text-align: center;">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
</div>