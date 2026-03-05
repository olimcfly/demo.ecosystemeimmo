<?php
/**
 * MODULE TEMPLATES
 * /admin/modules/templates/index.php
 * 
 * Affiche une bibliothèque de pages pré-construites
 * que l'on peut utiliser pour créer rapidement des pages
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('Erreur DB');
}

// ============================================
// TEMPLATES PRÉ-CONSTRUITS
// ============================================
$templates = [
    // IMMOBILIER
    [
        'id' => 'hero-achat',
        'category' => 'immobilier',
        'name' => 'Héro Achat',
        'description' => 'Page d\'accueil complète pour vendre un bien',
        'icon' => '🏠',
        'preview' => 'Large bannière avec image, titre accrocheur, formulaire de contact intégré',
        'html' => '<section style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:80px 20px;text-align:center;">
  <div style="max-width:900px;margin:0 auto;">
    <h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Trouvez votre bien de rêve à Bordeaux</h1>
    <p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Découvrez nos annonces exclusives dans les meilleurs quartiers</p>
    <a href="#contact" style="background:white;color:#667eea;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Consulter les biens</a>
  </div>
</section>'
    ],
    [
        'id' => 'hero-vente',
        'category' => 'immobilier',
        'name' => 'Héro Vente',
        'description' => 'Page d\'accueil pour vendre la maison du client',
        'icon' => '📈',
        'preview' => 'Bannière avec CTA pour estimation gratuite',
        'html' => '<section style="background:linear-gradient(135deg,#f093fb,#f5576c);color:white;padding:80px 20px;text-align:center;">
  <div style="max-width:900px;margin:0 auto;">
    <h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Vendez votre bien au meilleur prix</h1>
    <p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Estimez gratuitement votre propriété en 2 minutes</p>
    <a href="#contact" style="background:white;color:#f5576c;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Obtenir une estimation</a>
  </div>
</section>'
    ],
    [
        'id' => 'hero-location',
        'category' => 'immobilier',
        'name' => 'Héro Location',
        'description' => 'Page pour louer un bien',
        'icon' => '🔑',
        'preview' => 'Bannière dédiée aux locations saisonnières ou longues durées',
        'html' => '<section style="background:linear-gradient(135deg,#4facfe,#00f2fe);color:white;padding:80px 20px;text-align:center;">
  <div style="max-width:900px;margin:0 auto;">
    <h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Trouvez votre appartement à louer</h1>
    <p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Accès rapide à nos meilleures locations</p>
    <a href="#contact" style="background:white;color:#00f2fe;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Voir les annonces</a>
  </div>
</section>'
    ],
    
    // PRÉSENTATION
    [
        'id' => 'about-conseil',
        'category' => 'presentation',
        'name' => 'À Propos - Conseiller',
        'description' => 'Page de présentation avec photo et expertise',
        'icon' => '👤',
        'preview' => 'Section biographie avec photo en fond',
        'html' => '<section style="padding:60px 20px;background:#f8f9fa;">
  <div style="max-width:900px;margin:0 auto;">
    <h2 style="font-size:36px;margin-bottom:30px;text-align:center;font-weight:700;">À propos de moi</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;">
      <div>
        <img src="/placeholder-photo.jpg" alt="Photo de profil" style="width:100%;border-radius:12px;object-fit:cover;height:400px;">
      </div>
      <div>
        <h3 style="font-size:24px;margin-bottom:15px;font-weight:600;">Conseiller immobilier spécialisé</h3>
        <p style="font-size:16px;line-height:1.8;color:#555;margin-bottom:20px;">Avec plus de 10 ans d\'expérience dans l\'immobilier bordelais, je vous accompagne dans la vente, l\'achat ou la location de votre bien.</p>
        <ul style="list-style:none;padding:0;">
          <li style="padding:10px 0;border-bottom:1px solid #ddd;"><strong>✓</strong> 500+ transactions réussies</li>
          <li style="padding:10px 0;border-bottom:1px solid #ddd;"><strong>✓</strong> Expert des quartiers bordelais</li>
          <li style="padding:10px 0;"><strong>✓</strong> Conseils gratuits et sans engagement</li>
        </ul>
      </div>
    </div>
  </div>
</section>'
    ],
    
    // CONTACT
    [
        'id' => 'formulaire-contact',
        'category' => 'contact',
        'name' => 'Formulaire de Contact',
        'description' => 'Formulaire simple pour les prises de contact',
        'icon' => '📧',
        'preview' => 'Formulaire minimaliste avec tous les champs essentiels',
        'html' => '<section style="padding:60px 20px;background:white;">
  <div style="max-width:600px;margin:0 auto;">
    <h2 style="font-size:32px;margin-bottom:10px;text-align:center;font-weight:700;">Me contacter</h2>
    <p style="text-align:center;color:#666;margin-bottom:40px;">Une question ? Je vous réponds rapidement</p>
    <form>
      <div style="margin-bottom:20px;">
        <label style="display:block;margin-bottom:8px;font-weight:600;">Votre nom</label>
        <input type="text" placeholder="Votre nom" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;margin-bottom:8px;font-weight:600;">Votre email</label>
        <input type="email" placeholder="votre@email.com" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;margin-bottom:8px;font-weight:600;">Téléphone</label>
        <input type="tel" placeholder="06 12 34 56 78" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
      </div>
      <div style="margin-bottom:30px;">
        <label style="display:block;margin-bottom:8px;font-weight:600;">Votre message</label>
        <textarea placeholder="Votre message..." rows="5" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;"></textarea>
      </div>
      <button type="submit" style="width:100%;padding:14px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:16px;">Envoyer mon message</button>
    </form>
  </div>
</section>'
    ],
    [
        'id' => 'appel-action',
        'category' => 'contact',
        'name' => 'Appel à l\'Action',
        'description' => 'CTA visuelle avec numéro de téléphone prominent',
        'icon' => '☎️',
        'preview' => 'Section avec numéro de téléphone géant',
        'html' => '<section style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:80px 20px;text-align:center;">
  <div style="max-width:900px;margin:0 auto;">
    <p style="font-size:18px;margin-bottom:20px;opacity:0.9;">Vous avez une question ?</p>
    <h2 style="font-size:56px;margin-bottom:30px;font-weight:700;">Appelez-moi directement</h2>
    <a href="tel:+33612345678" style="display:inline-block;font-size:32px;font-weight:700;color:white;text-decoration:none;padding:20px 40px;background:rgba(255,255,255,0.2);border-radius:12px;transition:all 0.3s;">
      +33 6 12 34 56 78
    </a>
    <p style="margin-top:30px;opacity:0.8;">Disponible du lundi au vendredi, 9h-18h</p>
  </div>
</section>'
    ],
    
    // TÉMOIGNAGES
    [
        'id' => 'temoignages',
        'category' => 'contenu',
        'name' => 'Témoignages Clients',
        'description' => 'Grille de témoignages de clients satisfaits',
        'icon' => '⭐',
        'preview' => 'Grille 3 colonnes avec avis clients',
        'html' => '<section style="padding:60px 20px;background:#f8f9fa;">
  <div style="max-width:1200px;margin:0 auto;">
    <h2 style="font-size:36px;margin-bottom:50px;text-align:center;font-weight:700;">Ce que disent mes clients</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:30px;">
      <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
        <div style="margin-bottom:15px;color:#ffc107;">⭐⭐⭐⭐⭐</div>
        <p style="margin-bottom:20px;color:#555;line-height:1.6;">"Un professionnel très attentif et réactif. Il a vendu ma maison en 3 mois au prix souhaité !"</p>
        <p style="font-weight:600;color:#333;">Marie Dupont</p>
        <p style="color:#999;font-size:14px;">Vente - 2023</p>
      </div>
      <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
        <div style="margin-bottom:15px;color:#ffc107;">⭐⭐⭐⭐⭐</div>
        <p style="margin-bottom:20px;color:#555;line-height:1.6;">"Excellent service du début à la fin. Je recommande vivement !"</p>
        <p style="font-weight:600;color:#333;">Jean Martin</p>
        <p style="color:#999;font-size:14px;">Achat - 2023</p>
      </div>
      <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
        <div style="margin-bottom:15px;color:#ffc107;">⭐⭐⭐⭐⭐</div>
        <p style="margin-bottom:20px;color:#555;line-height:1.6;">"Très à l\'écoute et de bons conseils pour ma location."</p>
        <p style="font-weight:600;color:#333;">Sophie Laurent</p>
        <p style="color:#999;font-size:14px;">Location - 2023</p>
      </div>
    </div>
  </div>
</section>'
    ],
    
    // SEO / QUARTIERS
    [
        'id' => 'fiche-quartier',
        'category' => 'seo',
        'name' => 'Fiche Quartier',
        'description' => 'Page optimisée SEO pour un quartier spécifique',
        'icon' => '🗺️',
        'preview' => 'Page avec description, photos, points d\'intérêt du quartier',
        'html' => '<section style="padding:60px 20px;">
  <div style="max-width:900px;margin:0 auto;">
    <h1 style="font-size:42px;margin-bottom:20px;font-weight:700;">Immobilier à Saint-Émilion</h1>
    <p style="font-size:18px;color:#666;margin-bottom:40px;line-height:1.8;">Saint-Émilion est l\'un des plus beaux villages de Bordeaux, célèbre pour son patrimoine historique et ses vins renommés. Découvrez les biens immobiliers disponibles dans ce quartier prestigieux.</p>
    
    <h2 style="font-size:28px;margin:40px 0 20px;font-weight:700;">Caractéristiques du quartier</h2>
    <ul style="list-style:none;padding:0;">
      <li style="padding:12px 0;border-bottom:1px solid #eee;">✓ Village médiéval classé au patrimoine UNESCO</li>
      <li style="padding:12px 0;border-bottom:1px solid #eee;">✓ Commerce viticole et gastronomie</li>
      <li style="padding:12px 0;border-bottom:1px solid #eee;">✓ Proximité des routes de vins de Bordeaux</li>
      <li style="padding:12px 0;">✓ Maisons de caractère et châteaux</li>
    </ul>
    
    <h2 style="font-size:28px;margin:40px 0 20px;font-weight:700;">Nos annonces à Saint-Émilion</h2>
    <p style="color:#666;margin-bottom:20px;">Consultez ci-dessous les biens actuellement disponibles dans ce quartier recherché.</p>
  </div>
</section>'
    ],
];

// Catégories
$categories = [
    'immobilier' => '🏠 Immobilier',
    'presentation' => '👤 Présentation',
    'contact' => '📞 Contact',
    'contenu' => '📝 Contenu',
    'seo' => '🔍 SEO & Quartiers'
];

// Obtenir la catégorie active
$selected_category = $_GET['category'] ?? 'immobilier';
if (!isset($categories[$selected_category])) {
    $selected_category = 'immobilier';
}

// Filtrer les templates
$filtered_templates = array_filter($templates, function($t) use ($selected_category) {
    return $t['category'] === $selected_category;
});
?>

<style>
    .templates-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .category-filter {
        display: flex;
        gap: 12px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .category-btn {
        padding: 10px 20px;
        border: 2px solid #e2e8f0;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
        color: #64748b;
    }
    
    .category-btn:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }
    
    .category-btn.active {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: white;
        border-color: transparent;
    }
    
    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .template-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .template-card:hover {
        border-color: #3b82f6;
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.12);
    }
    
    .template-preview {
        background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
        padding: 40px 20px;
        text-align: center;
        font-size: 48px;
        min-height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .template-content {
        padding: 20px;
    }
    
    .template-category {
        font-size: 11px;
        color: #8b5cf6;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    
    .template-name {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .template-description {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 20px;
        line-height: 1.5;
        min-height: 36px;
    }
    
    .template-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .template-btn {
        padding: 10px 14px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        text-align: center;
        display: block;
    }
    
    .template-btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }
    
    .template-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    
    .template-btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .template-btn-secondary:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    
    .empty-state-templates {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state-templates i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    
    .empty-state-templates h3 {
        font-size: 18px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }
    
    .empty-state-templates p {
        font-size: 14px;
        color: #94a3b8;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 30px;
    }
    
    .btn {
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .btn-cancel:hover {
        background: #e2e8f0;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    
    .preview-modal-content {
        background: white;
        border-radius: 12px;
        max-width: 900px;
        width: 95%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .preview-header {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .preview-header h3 {
        font-size: 18px;
        font-weight: 600;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #64748b;
    }
    
    .preview-content {
        padding: 0;
        background: #f8f9fa;
    }
</style>

<div class="templates-container">
    <div class="category-filter">
        <?php foreach ($categories as $key => $name): ?>
            <button class="category-btn<?php echo $selected_category === $key ? ' active' : ''; ?>" 
                    onclick="window.location.href='?page=templates&category=<?php echo $key; ?>'">
                <?php echo $name; ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($filtered_templates)): ?>
        <div class="templates-grid">
            <?php foreach ($filtered_templates as $template): ?>
                <div class="template-card">
                    <div class="template-preview">
                        <?php echo $template['icon']; ?>
                    </div>
                    <div class="template-content">
                        <div class="template-category"><?php echo isset($categories[$template['category']]) ? str_replace(array('🏠', '👤', '📞', '📝', '🔍'), '', $categories[$template['category']]) : ''; ?></div>
                        <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                        <div class="template-description"><?php echo htmlspecialchars($template['description']); ?></div>
                        <div class="template-actions">
                            <button class="template-btn template-btn-primary" onclick="openCreateModal('<?php echo htmlspecialchars($template['name']); ?>', '<?php echo $template['id']; ?>', '<?php echo htmlspecialchars(base64_encode($template['html'])); ?>')">
                                ✨ Utiliser
                            </button>
                            <button class="template-btn template-btn-secondary" onclick="previewTemplate('<?php echo htmlspecialchars(base64_encode($template['html'])); ?>')">
                                👁️ Aperçu
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-templates">
            <i class="fas fa-inbox"></i>
            <h3>Aucun template dans cette catégorie</h3>
            <p>Essayez une autre catégorie</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Aperçu -->
<div id="previewModal" class="modal">
    <div class="preview-modal-content">
        <div class="preview-header">
            <h3>Aperçu du template</h3>
            <button class="close-btn" onclick="closePreview()">×</button>
        </div>
        <div class="preview-content" id="previewContent"></div>
    </div>
</div>

<!-- Modal Création -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Créer une page</div>
        <form id="createPageForm">
            <div class="form-group">
                <label>Titre de la page</label>
                <input type="text" id="pageTitle" placeholder="Ex: Vendre ma maison" required>
            </div>
            <div class="form-group">
                <label>URL (slug)</label>
                <input type="text" id="pageSlug" placeholder="Ex: vendre-ma-maison" required>
            </div>
            <div class="form-group">
                <label>Titre SEO (optionnel)</label>
                <input type="text" id="pageMetaTitle" placeholder="Ex: Vendre ma maison à Bordeaux">
            </div>
            <div class="form-group">
                <label>Description SEO (optionnel)</label>
                <textarea id="pageMetaDesc" placeholder="Description qui apparaîtra dans Google..." rows="3"></textarea>
            </div>
            <input type="hidden" id="templateHtml">
            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closeCreate()">Annuler</button>
                <button type="submit" class="btn btn-submit">Créer la page</button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewTemplate(base64Html) {
        const html = atob(base64Html);
        document.getElementById('previewContent').innerHTML = html;
        document.getElementById('previewModal').classList.add('active');
    }
    
    function closePreview() {
        document.getElementById('previewModal').classList.remove('active');
    }
    
    function openCreateModal(name, templateId, base64Html) {
        document.getElementById('templateHtml').value = atob(base64Html);
        document.getElementById('pageTitle').value = name;
        document.getElementById('pageSlug').value = templateId.replace(/-/g, '-');
        document.getElementById('pageMetaTitle').value = name;
        document.getElementById('pageMetaDesc').value = '';
        document.getElementById('createModal').classList.add('active');
    }
    
    function closeCreate() {
        document.getElementById('createModal').classList.remove('active');
    }
    
    document.getElementById('createPageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            title: document.getElementById('pageTitle').value,
            slug: document.getElementById('pageSlug').value,
            content: document.getElementById('templateHtml').value,
            meta_title: document.getElementById('pageMetaTitle').value || document.getElementById('pageTitle').value,
            meta_description: document.getElementById('pageMetaDesc').value,
            status: 'published'
        };
        
        try {
            const res = await fetch('/admin/modules/templates/api/create-page.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await res.json();
            
            if (data.success) {
                alert('✅ Page créée avec succès!');
                window.location.href = '/admin/dashboard.php?page=pages';
            } else {
                alert('❌ Erreur: ' + (data.error || 'Impossible de créer la page'));
            }
        } catch (err) {
            alert('❌ Erreur réseau');
            console.error(err);
        }
    });
    
    // Fermer les modales quand on clique dehors
    document.getElementById('previewModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('previewModal')) {
            closePreview();
        }
    });
    
    document.getElementById('createModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('createModal')) {
            closeCreate();
        }
    });
</script>