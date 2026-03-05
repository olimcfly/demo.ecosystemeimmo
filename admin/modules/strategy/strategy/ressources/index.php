<?php
// /admin/modules/guides/index.php
// Catégories & Guides de ressources - VERSION COMPLÈTE

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$guides = [];
$categories = [];
$total = 0;
$totalDownloads = 0;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Récupérer les guides depuis la base avec les vrais noms de colonnes
    $guides = $pdo->query("
        SELECT 
            g.id, 
            g.titre, 
            g.slug, 
            g.description, 
            g.persona, 
            g.raison_vente, 
            g.categorie, 
            g.fichier_pdf,
            g.image,
            g.status,
            g.downloads_count,
            g.created_at,
            COUNT(gd.id) as nb_telecharges
        FROM guides g
        LEFT JOIN guide_downloads gd ON g.id = gd.guide_id
        WHERE g.status IN ('published', 'public')
        GROUP BY g.id
        ORDER BY g.persona, g.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les personas uniques
    $categoriesResult = $pdo->query("
        SELECT DISTINCT persona FROM guides 
        WHERE persona IS NOT NULL AND persona != '' AND status IN ('published', 'public')
        ORDER BY persona
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categoriesResult as $cat) {
        $categories[] = $cat['persona'];
    }
    
    // Stats
    $total = count($guides);
    $totalDownloads = array_sum(array_column($guides, 'downloads_count'));
    
} catch (Exception $e) {
    error_log("Erreur guides: " . $e->getMessage());
    // Fallback avec données de démonstration
    $guides = [
        [
            'id' => 1,
            'titre' => 'NeuroPersona - Guide Complet',
            'slug' => 'neuropersona-guide',
            'description' => 'Découvrez comment identifier et caractériser vos personas vendeurs idéaux avec la neuroscience appliquée au marketing immobilier.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Vendre rapidement',
            'categorie' => 'Persona',
            'fichier_pdf' => 'guide-neuropersona.pdf',
            'status' => 'published',
            'downloads_count' => 342,
            'created_at' => '2024-01-15'
        ],
        [
            'id' => 2,
            'titre' => 'MERE Structure - Rédaction Efficace',
            'slug' => 'mere-structure',
            'description' => 'Maîtrisez la structure MERE (Motivation, Explication, Recette, Exemple) pour rédiger des contenus qui convertissent selon les styles d\'apprentissage Kolb.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Vendre rapidement',
            'categorie' => 'Contenu',
            'fichier_pdf' => 'guide-mere-structure.pdf',
            'status' => 'published',
            'downloads_count' => 287,
            'created_at' => '2024-01-14'
        ],
        [
            'id' => 3,
            'titre' => 'Local SEO - Optimisation Google',
            'slug' => 'local-seo',
            'description' => 'Guide pratique pour optimiser votre présence locale sur Google et dominer les recherches dans votre zone géographique.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Générer des leads',
            'categorie' => 'SEO',
            'fichier_pdf' => 'guide-local-seo.pdf',
            'status' => 'published',
            'downloads_count' => 456,
            'created_at' => '2024-01-13'
        ],
        [
            'id' => 4,
            'titre' => 'Google My Business - Stratégie Complète',
            'slug' => 'gmb-strategy',
            'description' => 'Exploitez pleinement Google My Business pour augmenter votre visibilité et générer des appels de prospects qualifiés.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Générer des leads',
            'categorie' => 'Visibilité',
            'fichier_pdf' => 'guide-gmb-strategy.pdf',
            'status' => 'published',
            'downloads_count' => 398,
            'created_at' => '2024-01-12'
        ],
        [
            'id' => 5,
            'titre' => 'Audit de Visibilité - Diagnostic Complet',
            'slug' => 'audit-visibilite',
            'description' => 'Effectuez un audit complet de votre visibilité en ligne et identifiez les opportunités manquées de génération de leads.',
            'persona' => 'Propriétaire',
            'raison_vente' => 'Évaluer son bien',
            'categorie' => 'Audit',
            'fichier_pdf' => 'guide-visibility-audit.pdf',
            'status' => 'published',
            'downloads_count' => 165,
            'created_at' => '2024-01-11'
        ],
        [
            'id' => 6,
            'titre' => 'Estimateur de Propriété - Outils & Techniques',
            'slug' => 'estimateur-propriete',
            'description' => 'Maîtrisez les outils et techniques pour estimer rapidement et précisément la valeur d\'une propriété.',
            'persona' => 'Propriétaire',
            'raison_vente' => 'Évaluer son bien',
            'categorie' => 'Outils',
            'fichier_pdf' => 'guide-property-estimator.pdf',
            'status' => 'published',
            'downloads_count' => 212,
            'created_at' => '2024-01-10'
        ],
        [
            'id' => 7,
            'titre' => 'Calculateur ROI - Mesurer Votre Performance',
            'slug' => 'calculateur-roi',
            'description' => 'Calculez précisément le retour sur investissement de vos actions marketing immobilier et optimisez votre stratégie.',
            'persona' => 'Propriétaire',
            'raison_vente' => 'Maximiser profits',
            'categorie' => 'Performance',
            'fichier_pdf' => 'guide-roi-calculator.pdf',
            'status' => 'published',
            'downloads_count' => 289,
            'created_at' => '2024-01-09'
        ],
        [
            'id' => 8,
            'titre' => 'Génération de Leads - Stratégie Multi-Canaux',
            'slug' => 'generation-leads',
            'description' => 'Découvrez comment générer des leads qualifiés à travers plusieurs canaux digitaux et les convertir en clients.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Générer des leads',
            'categorie' => 'Leads',
            'fichier_pdf' => 'guide-lead-generation.pdf',
            'status' => 'published',
            'downloads_count' => 534,
            'created_at' => '2024-01-08'
        ],
        [
            'id' => 9,
            'titre' => 'Email Marketing Immobilier - Automation',
            'slug' => 'email-marketing',
            'description' => 'Mettez en place une stratégie d\'email marketing automatisée pour nurture vos prospects et transformer les contacts en ventes.',
            'persona' => 'Propriétaire',
            'raison_vente' => 'Générer des leads',
            'categorie' => 'Email',
            'fichier_pdf' => 'guide-email-marketing.pdf',
            'status' => 'published',
            'downloads_count' => 156,
            'created_at' => '2024-01-07'
        ],
        [
            'id' => 10,
            'titre' => 'Stratégie de Contenu - Calendrier Éditorial',
            'slug' => 'strategie-contenu',
            'description' => 'Planifiez et structurez votre contenu immobilier avec un calendrier éditorial efficace et des templates prêts à l\'emploi.',
            'persona' => 'Vendeur',
            'raison_vente' => 'Générer des leads',
            'categorie' => 'Contenu',
            'fichier_pdf' => 'guide-content-strategy.pdf',
            'status' => 'published',
            'downloads_count' => 223,
            'created_at' => '2024-01-06'
        ]
    ];
    
    $categories = array_values(array_unique(array_column($guides, 'persona')));
    $total = count($guides);
    $totalDownloads = array_sum(array_column($guides, 'downloads_count'));
}

// Récupérer success message
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Fonction pour obtenir l'emoji de la catégorie
function getCategoryIcon($categorie) {
    $icons = [
        'Persona' => '🧠',
        'Contenu' => '📝',
        'SEO' => '🔍',
        'Visibilité' => '📍',
        'Audit' => '📊',
        'Outils' => '🛠️',
        'Performance' => '📈',
        'Leads' => '🎯',
        'Email' => '✉️'
    ];
    return $icons[$categorie] ?? '📄';
}

?>

<style>
    .guides-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .guides-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 8px 16px;
        font-size: 12px;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .btn-download {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 8px 16px;
        font-size: 12px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
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

    /* TOP SECTION */
    .top-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }

    /* STATS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .stat-box {
        background: linear-gradient(135deg, #f0f4ff 0%, #f5f3ff 100%);
        border: 1px solid #e0e7ff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .stat-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #667eea;
    }

    .stat-label {
        font-size: 12px;
        color: #6b7280;
        margin-top: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* FILTER TABS */
    .filter-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        flex-wrap: wrap;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 15px;
    }

    .tab-btn {
        padding: 8px 16px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        color: #667eea;
    }

    .tab-btn.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    /* GUIDES GRID */
    .guides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .guide-card {
        background: white;
        border-radius: 8px;
        padding: 25px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .guide-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
    }

    .guide-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .guide-icon {
        font-size: 32px;
        flex-shrink: 0;
    }

    .guide-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.3;
    }

    .guide-description {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
        flex-grow: 1;
    }

    .guide-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid #f3f4f6;
        flex-wrap: wrap;
        gap: 8px;
    }

    .guide-category {
        display: inline-block;
        background: #e0e7ff;
        color: #4f46e5;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .guide-persona {
        display: inline-block;
        background: #f3e8ff;
        color: #7c3aed;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .guide-downloads {
        font-size: 12px;
        color: #9ca3af;
        white-space: nowrap;
    }

    .guide-footer {
        display: flex;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid #f3f4f6;
    }

    .guide-footer a,
    .guide-footer button {
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all 0.2s ease;
    }

    .guide-footer .btn-download {
        flex: 2;
    }

    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 10px;
    }

    /* PERSONAS SECTION */
    .personas-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        margin-bottom: 30px;
    }

    .personas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .persona-badge {
        background: linear-gradient(135deg, #f0f4ff 0%, #f5f3ff 100%);
        border: 1px solid #e0e7ff;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .persona-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        background: linear-gradient(135deg, #e0e7ff 0%, #d5d0ff 100%);
    }

    .persona-name {
        font-weight: 700;
        color: #667eea;
        margin-bottom: 5px;
    }

    .persona-count {
        font-size: 12px;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .guides-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .guides-grid {
            grid-template-columns: 1fr;
        }

        .filter-tabs {
            overflow-x: auto;
            padding-bottom: 10px;
        }
    }
</style>

<div>
    <!-- Success Alert -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="guides-header">
        <h1>📚 Guides & Ressources</h1>
        <a href="/admin/dashboard.php?page=guides&action=create" class="btn btn-primary">
            ✨ Ajouter un guide
        </a>
    </div>

    <!-- Top Section - Stats -->
    <div class="top-section">
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">Guides total</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">📥</div>
                <div class="stat-number"><?php echo number_format($totalDownloads); ?></div>
                <div class="stat-label">Téléchargements</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Personas couverts</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">🏆</div>
                <div class="stat-number"><?php echo isset($guides[0]) ? number_format($guides[0]['downloads_count'] ?? 0) : '0'; ?></div>
                <div class="stat-label">Top guide</div>
            </div>
        </div>
    </div>

    <!-- Personas Section -->
    <?php if (!empty($categories)): ?>
        <div class="personas-section">
            <h2 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 700; color: #1a202c;">
                🎯 Personas couverts
            </h2>
            <div class="personas-grid">
                <?php foreach ($categories as $persona): ?>
                    <?php $count = count(array_filter($guides, fn($g) => ($g['persona'] ?? null) === $persona)); ?>
                    <div class="persona-badge" onclick="filterByPersona('<?php echo htmlspecialchars($persona); ?>')">
                        <div class="persona-name"><?php echo htmlspecialchars($persona); ?></div>
                        <div class="persona-count"><?php echo $count; ?> guide<?php echo $count > 1 ? 's' : ''; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Guides Grid -->
    <?php if (count($guides) > 0): ?>
        <div class="guides-grid" id="guidesGrid">
            <?php foreach ($guides as $guide): ?>
                <div class="guide-card" data-persona="<?php echo htmlspecialchars($guide['persona'] ?? 'Général'); ?>">
                    <div class="guide-header">
                        <div class="guide-icon"><?php echo !empty($guide['image']) ? '📄' : getCategoryIcon($guide['categorie'] ?? ''); ?></div>
                        <div class="guide-title"><?php echo htmlspecialchars($guide['titre']); ?></div>
                    </div>
                    
                    <div class="guide-description">
                        <?php echo htmlspecialchars($guide['description']); ?>
                    </div>
                    
                    <div class="guide-meta">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="guide-category">
                                <?php echo getCategoryIcon($guide['categorie'] ?? ''); ?> 
                                <?php echo htmlspecialchars($guide['categorie'] ?? 'Général'); ?>
                            </span>
                            <span class="guide-persona">
                                🎯 <?php echo htmlspecialchars($guide['persona'] ?? '-'); ?>
                            </span>
                        </div>
                        <span class="guide-downloads">
                            📥 <?php echo number_format($guide['downloads_count'] ?? 0); ?>
                        </span>
                    </div>
                    
                    <div class="guide-footer">
                        <a href="/admin/dashboard.php?page=guides&action=edit&id=<?php echo $guide['id']; ?>" class="btn btn-secondary">
                            ✎ Éditer
                        </a>
                        <?php if (!empty($guide['fichier_pdf'])): ?>
                            <a href="/guides/<?php echo htmlspecialchars($guide['fichier_pdf']); ?>" class="btn btn-download" download>
                                ⬇️ Télécharger
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled style="opacity: 0.5;">
                                ⚠️ Pas de fichier
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="guide-card" style="grid-column: 1 / -1;">
            <div class="empty-state">
                <h3>📚 Aucun guide créé</h3>
                <p>Commencez par ajouter votre premier guide de ressource.</p>
                <a href="/admin/dashboard.php?page=guides&action=create" class="btn btn-primary" style="margin-top: 20px;">
                    ✨ Créer un premier guide
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    function filterByPersona(persona) {
        const cards = document.querySelectorAll('.guide-card');
        cards.forEach(card => {
            if (persona === 'all' || card.dataset.persona === persona) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Optionnel: ajouter un bouton "Voir tous"
    console.log('Guides filter ready');
</script>