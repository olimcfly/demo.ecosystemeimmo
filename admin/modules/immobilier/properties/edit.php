<?php
/**
 * MODULE PROPERTIES - Formulaire Création / Édition
 * Chemin : /admin/modules/properties/edit.php
 * 
 * Paramètres GET :
 *   ?module=properties&page=edit&id=123  → Édition
 *   ?module=properties&page=create       → Création
 */

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ====================================================
// CHARGER LE CONTROLLER
// ====================================================
require_once __DIR__ . '/PropertyController.php';
$controller = new PropertyController($pdo);

// ====================================================
// DÉTERMINER LE MODE (create ou edit)
// ====================================================
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$property = null;
$errors = [];
$success = '';

if ($isEdit) {
    $property = $controller->getById($id);
    if (!$property) {
        echo '<div style="padding:40px; text-align:center; color:#dc2626;">
                <h2>Bien introuvable (ID: ' . $id . ')</h2>
                <a href="/admin/index.php?module=properties&page=index">← Retour à la liste</a>
              </div>';
        return;
    }
}

// ====================================================
// VARIABLES LAYOUT
// ====================================================
$page_title    = $isEdit ? 'Modifier : ' . htmlspecialchars($property['title']) : 'Ajouter un bien';
$current_module = "properties";
$current_page   = $isEdit ? "edit" : "create";

// ====================================================
// TRAITEMENT DU FORMULAIRE
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title'             => trim($_POST['title'] ?? ''),
        'reference'         => trim($_POST['reference'] ?? ''),
        'type'              => $_POST['type'] ?? 'appartement',
        'transaction'       => $_POST['transaction'] ?? 'vente',
        'price'             => $_POST['price'] ?? '',
        'charges'           => $_POST['charges'] ?? '',
        'surface'           => $_POST['surface'] ?? '',
        'land_surface'      => $_POST['land_surface'] ?? '',
        'rooms'             => $_POST['rooms'] ?? '',
        'bedrooms'          => $_POST['bedrooms'] ?? '',
        'bathrooms'         => $_POST['bathrooms'] ?? '',
        'floor'             => $_POST['floor'] ?? '',
        'total_floors'      => $_POST['total_floors'] ?? '',
        'construction_year' => $_POST['construction_year'] ?? '',
        'energy_class'      => $_POST['energy_class'] ?? '',
        'ges_class'         => $_POST['ges_class'] ?? '',
        'description'       => $_POST['description'] ?? '',
        'address'           => trim($_POST['address'] ?? ''),
        'city'              => trim($_POST['city'] ?? ''),
        'postal_code'       => trim($_POST['postal_code'] ?? ''),
        'neighborhood'      => trim($_POST['neighborhood'] ?? ''),
        'latitude'          => $_POST['latitude'] ?? '',
        'longitude'         => $_POST['longitude'] ?? '',
        'virtual_tour_url'  => trim($_POST['virtual_tour_url'] ?? ''),
        'status'            => $_POST['status'] ?? 'draft',
        'featured'          => isset($_POST['featured']) ? 1 : 0,
        'features'          => json_decode($_POST['features_json'] ?? '[]', true) ?: [],
        'images'            => json_decode($_POST['images_json'] ?? '[]', true) ?: [],
    ];
    
    if ($isEdit) {
        $result = $controller->update($id, $data);
    } else {
        $result = $controller->create($data);
    }
    
    if ($result['success']) {
        $newId = $result['id'];
        $success = $isEdit ? 'Bien mis à jour avec succès.' : 'Bien créé avec succès.';
        
        // Rediriger vers l'édition si création
        if (!$isEdit) {
            header('Location: /admin/index.php?module=properties&page=edit&id=' . $newId . '&saved=1');
            exit;
        }
        
        // Recharger les données
        $property = $controller->getById($id);
    } else {
        $errors = $result['errors'] ?? ['Erreur inconnue.'];
    }
}

// ====================================================
// Données pour le formulaire
// ====================================================
$p = $property ?: [
    'title' => '', 'reference' => '', 'type' => 'appartement', 'transaction' => 'vente',
    'price' => '', 'charges' => '', 'surface' => '', 'land_surface' => '',
    'rooms' => '', 'bedrooms' => '', 'bathrooms' => '',
    'floor' => '', 'total_floors' => '', 'construction_year' => '',
    'energy_class' => '', 'ges_class' => '',
    'description' => '', 'address' => '', 'city' => '', 'postal_code' => '',
    'neighborhood' => '', 'latitude' => '', 'longitude' => '',
    'virtual_tour_url' => '', 'status' => 'draft', 'featured' => 0,
    'features' => [], 'images' => [],
];

// Message après redirection
if (isset($_GET['saved'])) {
    $success = 'Bien créé avec succès !';
}

// Listes
$cities = $controller->getCities();
$neighborhoods = $controller->getNeighborhoods();

// ====================================================
// DÉBUT DU CONTENU
// ====================================================
ob_start();
?>

<style>
/* ── Edit Form Styles ── */
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.form-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1024px) {
    .form-grid { grid-template-columns: 1fr; }
}
.form-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}
.form-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 700;
    font-size: 15px;
    color: #1f2937;
    background: #f9fafb;
}
.form-card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.form-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.form-row > .form-group { flex: 1; min-width: 140px; }

.form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}
.form-group label .required {
    color: #dc2626;
}
.form-group input,
.form-group select,
.form-group textarea {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: #1f2937;
    background: #fff;
    transition: border-color 0.2s;
    font-family: inherit;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.form-group textarea {
    min-height: 200px;
    resize: vertical;
}

/* Alerts */
.alert {
    padding: 14px 20px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
}
.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
.alert-error ul {
    margin: 6px 0 0 20px;
    padding: 0;
}

/* Image list */
.image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.image-item {
    position: relative;
    width: 80px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}
.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.image-item .remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(220,38,38,0.9);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Features tags */
.features-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.feature-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 20px;
    font-size: 13px;
    color: #1e40af;
}
.feature-tag .remove-feat {
    cursor: pointer;
    font-weight: bold;
    color: #93c5fd;
}
.feature-tag .remove-feat:hover {
    color: #dc2626;
}

/* Energy label */
.energy-grid {
    display: flex;
    gap: 8px;
}
.energy-option {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}
.energy-option.selected {
    border-color: currentColor;
    box-shadow: 0 0 0 2px currentColor;
}
.energy-A { color: #16a34a; } .energy-B { color: #65a30d; }
.energy-C { color: #ca8a04; } .energy-D { color: #ea580c; }
.energy-E { color: #dc2626; } .energy-F { color: #be185d; }
.energy-G { color: #7c2d12; }

/* Buttons */
.btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; background: #2563eb; color: #fff;
    border: none; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer; text-decoration: none;
}
.btn-primary:hover { background: #1d4ed8; color:#fff; }
.btn-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; background: #fff; color: #374151;
    border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer; text-decoration: none;
}
.btn-secondary:hover { background: #f9fafb; }
</style>

<!-- Messages -->
<?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        ❌ Erreurs :
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="form-header">
    <div>
        <a href="/admin/index.php?module=properties&page=index" style="color:#6b7280; font-size:13px; text-decoration:none;">
            ← Retour à la liste
        </a>
        <h1><?= $isEdit ? '✏️ Modifier le bien' : '➕ Ajouter un bien' ?></h1>
    </div>
    <div style="display:flex; gap:10px;">
        <button type="submit" form="propertyForm" class="btn-primary">
            💾 <?= $isEdit ? 'Enregistrer' : 'Créer le bien' ?>
        </button>
    </div>
</div>

<form method="POST" id="propertyForm">
    <div class="form-grid">
        
        <!-- ====== COLONNE PRINCIPALE ====== -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            
            <!-- Informations générales -->
            <div class="form-card">
                <div class="form-card-header">📋 Informations générales</div>
                <div class="form-card-body">
                    <div class="form-group">
                        <label>Titre <span class="required">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>" 
                               placeholder="Ex: Appartement T3 lumineux - Bordeaux Centre" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Référence</label>
                            <input type="text" name="reference" value="<?= htmlspecialchars($p['reference'] ?? '') ?>" 
                                   placeholder="Auto-générée si vide">
                        </div>
                        <div class="form-group">
                            <label>Type de bien</label>
                            <select name="type">
                                <?php foreach (['appartement'=>'🏢 Appartement','maison'=>'🏠 Maison','terrain'=>'🌳 Terrain','commerce'=>'🏪 Commerce','autre'=>'📦 Autre'] as $val=>$label): ?>
                                    <option value="<?= $val ?>" <?= $p['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Transaction</label>
                            <select name="transaction">
                                <option value="vente" <?= $p['transaction'] === 'vente' ? 'selected' : '' ?>>💰 Vente</option>
                                <option value="location" <?= $p['transaction'] === 'location' ? 'selected' : '' ?>>🔑 Location</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Prix & Caractéristiques -->
            <div class="form-card">
                <div class="form-card-header">💰 Prix & Caractéristiques</div>
                <div class="form-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prix (€)</label>
                            <input type="number" name="price" value="<?= htmlspecialchars($p['price'] ?? '') ?>" 
                                   placeholder="250000" step="1">
                        </div>
                        <div class="form-group">
                            <label>Charges (€/mois)</label>
                            <input type="number" name="charges" value="<?= htmlspecialchars($p['charges'] ?? '') ?>" 
                                   placeholder="150" step="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Surface (m²)</label>
                            <input type="number" name="surface" value="<?= htmlspecialchars($p['surface'] ?? '') ?>" placeholder="65">
                        </div>
                        <div class="form-group">
                            <label>Terrain (m²)</label>
                            <input type="number" name="land_surface" value="<?= htmlspecialchars($p['land_surface'] ?? '') ?>" placeholder="300">
                        </div>
                        <div class="form-group">
                            <label>Pièces</label>
                            <input type="number" name="rooms" value="<?= htmlspecialchars($p['rooms'] ?? '') ?>" placeholder="3" min="1">
                        </div>
                        <div class="form-group">
                            <label>Chambres</label>
                            <input type="number" name="bedrooms" value="<?= htmlspecialchars($p['bedrooms'] ?? '') ?>" placeholder="2" min="0">
                        </div>
                        <div class="form-group">
                            <label>SDB</label>
                            <input type="number" name="bathrooms" value="<?= htmlspecialchars($p['bathrooms'] ?? '') ?>" placeholder="1" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Étage</label>
                            <input type="number" name="floor" value="<?= htmlspecialchars($p['floor'] ?? '') ?>" placeholder="3" min="0">
                        </div>
                        <div class="form-group">
                            <label>Étages total</label>
                            <input type="number" name="total_floors" value="<?= htmlspecialchars($p['total_floors'] ?? '') ?>" placeholder="5" min="0">
                        </div>
                        <div class="form-group">
                            <label>Année construction</label>
                            <input type="number" name="construction_year" value="<?= htmlspecialchars($p['construction_year'] ?? '') ?>" 
                                   placeholder="1990" min="1800" max="2030">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DPE -->
            <div class="form-card">
                <div class="form-card-header">⚡ Diagnostic Énergétique (DPE)</div>
                <div class="form-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Classe Énergie</label>
                            <div class="energy-grid" id="energyGrid">
                                <?php foreach (['A','B','C','D','E','F','G'] as $cls): ?>
                                    <div class="energy-option energy-<?= $cls ?> <?= strtoupper($p['energy_class'] ?? '') === $cls ? 'selected' : '' ?>"
                                         onclick="selectEnergy('energy_class', '<?= $cls ?>', this)">
                                        <?= $cls ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="energy_class" id="energy_class" value="<?= htmlspecialchars($p['energy_class'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Classe GES</label>
                            <div class="energy-grid" id="gesGrid">
                                <?php foreach (['A','B','C','D','E','F','G'] as $cls): ?>
                                    <div class="energy-option energy-<?= $cls ?> <?= strtoupper($p['ges_class'] ?? '') === $cls ? 'selected' : '' ?>"
                                         onclick="selectEnergy('ges_class', '<?= $cls ?>', this)">
                                        <?= $cls ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="ges_class" id="ges_class" value="<?= htmlspecialchars($p['ges_class'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div class="form-card">
                <div class="form-card-header">📝 Description</div>
                <div class="form-card-body">
                    <div class="form-group">
                        <textarea name="description" placeholder="Description détaillée du bien..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Localisation -->
            <div class="form-card">
                <div class="form-card-header">📍 Localisation</div>
                <div class="form-card-body">
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($p['address'] ?? '') ?>" 
                               placeholder="12 Rue Sainte-Catherine">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($p['city'] ?? '') ?>" 
                                   placeholder="Bordeaux" list="citiesList">
                            <datalist id="citiesList">
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>Code postal</label>
                            <input type="text" name="postal_code" value="<?= htmlspecialchars($p['postal_code'] ?? '') ?>" 
                                   placeholder="33000" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label>Quartier</label>
                            <input type="text" name="neighborhood" value="<?= htmlspecialchars($p['neighborhood'] ?? '') ?>" 
                                   placeholder="Chartrons" list="neighborhoodsList">
                            <datalist id="neighborhoodsList">
                                <?php foreach ($neighborhoods as $n): ?>
                                    <option value="<?= htmlspecialchars($n) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="latitude" value="<?= htmlspecialchars($p['latitude'] ?? '') ?>" 
                                   placeholder="44.8378">
                        </div>
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="longitude" value="<?= htmlspecialchars($p['longitude'] ?? '') ?>" 
                                   placeholder="-0.5792">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ====== COLONNE LATÉRALE ====== -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            
            <!-- Publication -->
            <div class="form-card">
                <div class="form-card-header">🚀 Publication</div>
                <div class="form-card-body">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status">
                            <option value="draft" <?= ($p['status'] ?? '') === 'draft' ? 'selected' : '' ?>>📝 Brouillon</option>
                            <option value="available" <?= ($p['status'] ?? '') === 'available' ? 'selected' : '' ?>>✅ Disponible</option>
                            <option value="under_offer" <?= ($p['status'] ?? '') === 'under_offer' ? 'selected' : '' ?>>⏳ Sous offre</option>
                            <option value="sold" <?= ($p['status'] ?? '') === 'sold' ? 'selected' : '' ?>>🔴 Vendu</option>
                            <option value="rented" <?= ($p['status'] ?? '') === 'rented' ? 'selected' : '' ?>>🟣 Loué</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="featured" value="1" <?= !empty($p['featured']) ? 'checked' : '' ?>>
                            ⭐ Mettre en avant
                        </label>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                        💾 <?= $isEdit ? 'Enregistrer' : 'Créer' ?>
                    </button>
                </div>
            </div>
            
            <!-- Images -->
            <div class="form-card">
                <div class="form-card-header">📸 Images</div>
                <div class="form-card-body">
                    <div class="image-list" id="imageList">
                        <?php foreach (($p['images'] ?? []) as $i => $img): ?>
                            <div class="image-item">
                                <img src="<?= htmlspecialchars($img) ?>" alt="">
                                <button type="button" class="remove-btn" onclick="removeImage(<?= $i ?>)">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group" style="margin-top:8px;">
                        <label>Ajouter une URL d'image</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="newImageUrl" placeholder="https://..." style="flex:1;">
                            <button type="button" class="btn-secondary" onclick="addImage()" style="padding:8px 14px;">＋</button>
                        </div>
                    </div>
                    <input type="hidden" name="images_json" id="imagesJson" 
                           value='<?= htmlspecialchars(json_encode($p['images'] ?? [])) ?>'>
                </div>
            </div>
            
            <!-- Visite virtuelle -->
            <div class="form-card">
                <div class="form-card-header">🎬 Visite virtuelle</div>
                <div class="form-card-body">
                    <div class="form-group">
                        <label>URL de la visite</label>
                        <input type="url" name="virtual_tour_url" value="<?= htmlspecialchars($p['virtual_tour_url'] ?? '') ?>" 
                               placeholder="https://...">
                    </div>
                </div>
            </div>
            
            <!-- Caractéristiques / Features -->
            <div class="form-card">
                <div class="form-card-header">🏷️ Caractéristiques</div>
                <div class="form-card-body">
                    <div class="features-container" id="featuresList">
                        <?php foreach (($p['features'] ?? []) as $feat): ?>
                            <span class="feature-tag">
                                <?= htmlspecialchars($feat) ?>
                                <span class="remove-feat" onclick="removeFeature(this)">✕</span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <input type="text" id="newFeature" placeholder="Ex: Parking, Balcon, Cave..." 
                               style="flex:1; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;"
                               onkeypress="if(event.key==='Enter'){event.preventDefault(); addFeature();}">
                        <button type="button" class="btn-secondary" onclick="addFeature()" style="padding:8px 14px;">＋</button>
                    </div>
                    <input type="hidden" name="features_json" id="featuresJson" 
                           value='<?= htmlspecialchars(json_encode($p['features'] ?? [])) ?>'>
                    
                    <!-- Quick-add -->
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        <?php 
                        $quickFeatures = ['Parking', 'Balcon', 'Terrasse', 'Cave', 'Garage', 'Ascenseur', 'Gardien', 'Piscine', 'Jardin', 'Climatisation', 'Parquet', 'Double vitrage'];
                        foreach ($quickFeatures as $qf): ?>
                            <button type="button" 
                                    style="padding:4px 8px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; font-size:11px; cursor:pointer; color:#6b7280;"
                                    onclick="quickAddFeature('<?= $qf ?>')">
                                + <?= $qf ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- ====================================================
     JAVASCRIPT
     ==================================================== -->
<script>
// ── Energy class selector ──
function selectEnergy(field, value, el) {
    // Deselect siblings
    el.parentElement.querySelectorAll('.energy-option').forEach(o => o.classList.remove('selected'));
    // Toggle
    const input = document.getElementById(field);
    if (input.value === value) {
        input.value = '';
    } else {
        el.classList.add('selected');
        input.value = value;
    }
}

// ── Images management ──
let images = <?= json_encode($p['images'] ?? []) ?>;

function renderImages() {
    const list = document.getElementById('imageList');
    list.innerHTML = images.map((img, i) => `
        <div class="image-item">
            <img src="${img}" alt="" onerror="this.parentElement.innerHTML='<div style=\\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-size:10px;color:#9ca3af;\\'>Erreur</div>'">
            <button type="button" class="remove-btn" onclick="removeImage(${i})">✕</button>
        </div>
    `).join('');
    document.getElementById('imagesJson').value = JSON.stringify(images);
}

function addImage() {
    const input = document.getElementById('newImageUrl');
    const url = input.value.trim();
    if (!url) return;
    images.push(url);
    renderImages();
    input.value = '';
}

function removeImage(index) {
    images.splice(index, 1);
    renderImages();
}

// ── Features management ──
let features = <?= json_encode($p['features'] ?? []) ?>;

function renderFeatures() {
    const list = document.getElementById('featuresList');
    list.innerHTML = features.map(f => `
        <span class="feature-tag">
            ${f}
            <span class="remove-feat" onclick="removeFeature(this)">✕</span>
        </span>
    `).join('');
    document.getElementById('featuresJson').value = JSON.stringify(features);
}

function addFeature() {
    const input = document.getElementById('newFeature');
    const val = input.value.trim();
    if (!val || features.includes(val)) return;
    features.push(val);
    renderFeatures();
    input.value = '';
}

function removeFeature(el) {
    const text = el.parentElement.textContent.trim().replace('✕', '').trim();
    features = features.filter(f => f !== text);
    renderFeatures();
}

function quickAddFeature(name) {
    if (!features.includes(name)) {
        features.push(name);
        renderFeatures();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>