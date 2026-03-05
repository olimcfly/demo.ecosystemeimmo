<?php
/**
 * /admin/modules/settings/index.php
 * Module Paramètres — Hub complet
 * 
 * Structure du module :
 *   /admin/modules/settings/
 *   ├── api/
 *   │   ├── ApiKeyManager.php      ← Classe gestion clés (chiffrement AES-256)
 *   │   └── api-keys.php           ← Endpoint AJAX (save/delete/check)
 *   ├── assets/js/
 *   │   └── api-key-checker.js     ← Composant popup réutilisable
 *   ├── index.php                  ← CE FICHIER
 *   └── site-identity.php          ← Logo, favicon, nom du site
 * 
 * Onglets :
 *   - general      → Identité du site (nom, URL, description, langue, maintenance)
 *   - agent        → Infos agent/client (nom, email, tel, réseau, RSAC, SIRET)
 *   - social       → Réseaux sociaux
 *   - appearance   → Couleurs, polices
 *   - seo          → Méta par défaut, Schema.org
 *   - tracking     → GA4, GTM, Facebook Pixel
 *   - email        → SMTP
 *   - legal        → Mentions légales, RGPD
 *   - api-keys     → Clés API & IA (système existant)
 *   - integrations → Webhooks, connexions (à venir)
 *   - advanced     → Cache, maintenance, logs (à venir)
 */

// Sécurité
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

// ═══ ApiKeyManager (système existant) ═══
require_once __DIR__ . '/api/ApiKeyManager.php';
$apiManager = ApiKeyManager::getInstance($pdo);

// ═══ Onglet actif ═══
$tab = $_GET['tab'] ?? 'general';
$validTabs = ['general', 'agent', 'social', 'appearance', 'seo', 'tracking', 'email', 'legal', 'api-keys', 'integrations', 'advanced'];
if (!in_array($tab, $validTabs)) $tab = 'general';

// ═══ Données API Keys (pour l'onglet api-keys) ═══
$allKeys = $apiManager->getAllKeys();
$servicesByCategory = ApiKeyManager::getServicesByCategory();
$storedKeys = [];
foreach ($allKeys as $k) {
    $storedKeys[$k['service_key']] = $k;
}
$totalServices = count(ApiKeyManager::SERVICES);
$totalConfigured = count(array_filter($allKeys, fn($k) => $k['is_configured']));

// ═══════════════════════════════════════════════════════════════════
// TRAITEMENT FORMULAIRE SETTINGS (POST)
// ═══════════════════════════════════════════════════════════════════
$settingsMessage = '';
$settingsMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    // Vérifier CSRF
    $csrfOk = isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (!$csrfOk) {
        $settingsMessage = 'Token de sécurité invalide. Rechargez la page.';
        $settingsMessageType = 'error';
    } else {
        $group = $_POST['group'] ?? 'general';
        $data = $_POST['settings'] ?? [];

        try {
            // Utilise la table admin_settings existante (id, setting_key, setting_value, updated_at)
            $stmt = $pdo->prepare(
                "INSERT INTO admin_settings (setting_key, setting_value) 
                 VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
            );

            $count = 0;
            foreach ($data as $key => $value) {
                $cleanKey = preg_replace('/[^a-z0-9_]/', '', $key);
                $cleanValue = trim($value);
                $stmt->execute([$cleanKey, $cleanValue]);
                $count++;
            }

            // ── Synchroniser vers footers & headers ──
            $syncMsg = '';
            try {
                require_once __DIR__ . '/../../../includes/SettingsSync.php';
                $syncResult = SettingsSync::sync($pdo);
                $synced = $syncResult['footers'] + $syncResult['headers'];
                if ($synced > 0) {
                    $syncMsg = " → {$syncResult['footers']} footer(s) et {$syncResult['headers']} header(s) mis à jour.";
                }
            } catch (Exception $e) {
                // Sync échoue silencieusement — les settings sont quand même sauvés
                error_log("SettingsSync error: " . $e->getMessage());
            }

            $settingsMessage = "{$count} paramètre(s) enregistré(s) avec succès.{$syncMsg}";
            $settingsMessageType = 'success';
        } catch (PDOException $e) {
            $settingsMessage = 'Erreur base de données : ' . $e->getMessage();
            $settingsMessageType = 'error';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// CHARGER LES SETTINGS ACTUELS
// ═══════════════════════════════════════════════════════════════════
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM admin_settings");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $currentSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (PDOException $e) {
    // Table vide ou erreur — pas grave, on utilisera les valeurs par défaut
}

/**
 * Helper : récupérer un setting
 */
function s(string $key, string $default = ''): string {
    global $currentSettings;
    return $currentSettings[$key] ?? $default;
}

/**
 * Helper : échapper pour HTML
 */
function esc(?string $val): string {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

// ═══════════════════════════════════════════════════════════════════
// DÉFINITION DES CHAMPS PAR ONGLET
// ═══════════════════════════════════════════════════════════════════
$settingsTabs = [

    'general' => [
        'icon' => 'fas fa-globe',
        'label' => 'Général',
        'sections' => [
            'Identité du site' => [
                'site_name'        => ['label' => 'Nom du site',    'type' => 'text',     'placeholder' => 'Eduardo De Sul Immobilier', 'required' => true],
                'site_url'         => ['label' => 'URL du site',    'type' => 'url',      'placeholder' => 'https://eduardo-desul-immobilier.fr', 'required' => true],
                'site_tagline'     => ['label' => 'Slogan',         'type' => 'text',     'placeholder' => 'Votre partenaire immobilier à Bordeaux'],
                'site_description' => ['label' => 'Description',    'type' => 'textarea', 'placeholder' => 'Courte description du site...', 'rows' => 3],
            ],
            'Paramètres système' => [
                'site_language'    => ['label' => 'Langue', 'type' => 'select', 'options' => ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'pt' => 'Português'], 'default' => 'fr'],
                'site_timezone'    => ['label' => 'Fuseau horaire', 'type' => 'text', 'placeholder' => 'Europe/Paris', 'default' => 'Europe/Paris'],
                'maintenance_mode' => ['label' => 'Mode maintenance', 'type' => 'toggle', 'hint' => 'Active une page de maintenance pour les visiteurs'],
            ],
        ],
    ],

    'agent' => [
        'icon' => 'fas fa-user-tie',
        'label' => 'Agent / Client',
        'sections' => [
            'Identité professionnelle' => [
                'agent_name'    => ['label' => 'Nom complet',              'type' => 'text',  'placeholder' => 'Eduardo De Sul', 'required' => true],
                'agent_title'   => ['label' => 'Titre professionnel',      'type' => 'text',  'placeholder' => 'Conseiller immobilier indépendant', 'default' => 'Conseiller immobilier indépendant'],
                'agent_network' => ['label' => 'Réseau immobilier',        'type' => 'text',  'placeholder' => 'eXp France'],
            ],
            'Contact' => [
                'agent_email' => ['label' => 'Email professionnel', 'type' => 'email', 'placeholder' => 'contact@monsite.fr', 'required' => true],
                'agent_phone' => ['label' => 'Téléphone',           'type' => 'tel',   'placeholder' => '06 XX XX XX XX',     'required' => true],
            ],
            'Adresse' => [
                'agent_address'     => ['label' => 'Adresse',      'type' => 'text', 'placeholder' => '12 Rue de la République'],
                'agent_city'        => ['label' => 'Ville',        'type' => 'text', 'placeholder' => 'Bordeaux', 'required' => true],
                'agent_postal_code' => ['label' => 'Code postal',  'type' => 'text', 'placeholder' => '33000'],
                'agent_region'      => ['label' => 'Région',       'type' => 'text', 'placeholder' => 'Nouvelle-Aquitaine'],
            ],
            'Légal professionnel' => [
                'agent_rsac'  => ['label' => 'N° RSAC',  'type' => 'text', 'placeholder' => 'RSAC Bordeaux 123 456 789'],
                'agent_siret' => ['label' => 'N° SIRET', 'type' => 'text', 'placeholder' => '123 456 789 00012'],
            ],
            'Visuels' => [
                'agent_photo_url' => ['label' => 'Photo de l\'agent', 'type' => 'image', 'hint' => 'URL ou chemin vers la photo'],
                'agent_logo_url'  => ['label' => 'Logo',              'type' => 'image', 'hint' => 'URL ou chemin vers le logo'],
            ],
        ],
    ],

    'social' => [
        'icon' => 'fas fa-share-alt',
        'label' => 'Réseaux sociaux',
        'sections' => [
            'Vos profils' => [
                'social_facebook'  => ['label' => 'Facebook',  'type' => 'url', 'placeholder' => 'https://facebook.com/votreprofil',  'icon' => 'fab fa-facebook'],
                'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://instagram.com/votreprofil', 'icon' => 'fab fa-instagram'],
                'social_linkedin'  => ['label' => 'LinkedIn',  'type' => 'url', 'placeholder' => 'https://linkedin.com/in/votreprofil', 'icon' => 'fab fa-linkedin'],
                'social_youtube'   => ['label' => 'YouTube',   'type' => 'url', 'placeholder' => 'https://youtube.com/@votrechaine',  'icon' => 'fab fa-youtube'],
                'social_tiktok'    => ['label' => 'TikTok',    'type' => 'url', 'placeholder' => 'https://tiktok.com/@votreprofil',   'icon' => 'fab fa-tiktok'],
                'social_whatsapp'  => ['label' => 'WhatsApp',  'type' => 'tel', 'placeholder' => '+33612345678', 'hint' => 'Numéro au format international', 'icon' => 'fab fa-whatsapp'],
            ],
        ],
    ],

    'appearance' => [
        'icon' => 'fas fa-palette',
        'label' => 'Apparence',
        'sections' => [
            'Couleurs' => [
                'color_primary'   => ['label' => 'Couleur principale',  'type' => 'color', 'default' => '#1a365d'],
                'color_secondary' => ['label' => 'Couleur secondaire',  'type' => 'color', 'default' => '#c9a84c'],
                'color_accent'    => ['label' => 'Couleur d\'accent',   'type' => 'color', 'default' => '#e74c3c'],
            ],
            'Typographie' => [
                'font_primary'   => ['label' => 'Police des titres',   'type' => 'text', 'placeholder' => 'Montserrat', 'default' => 'Montserrat', 'hint' => 'Nom Google Fonts'],
                'font_secondary' => ['label' => 'Police du texte',     'type' => 'text', 'placeholder' => 'Open Sans',  'default' => 'Open Sans',  'hint' => 'Nom Google Fonts'],
            ],
        ],
    ],

    'seo' => [
        'icon' => 'fas fa-search',
        'label' => 'SEO',
        'sections' => [
            'Méta par défaut' => [
                'meta_default_title'       => ['label' => 'Titre méta par défaut',       'type' => 'text',     'placeholder' => 'Eduardo De Sul | Immobilier Bordeaux'],
                'meta_default_description' => ['label' => 'Description méta par défaut',  'type' => 'textarea', 'placeholder' => 'Votre conseiller immobilier à Bordeaux...', 'rows' => 3],
            ],
            'Schema.org' => [
                'schema_org_type' => ['label' => 'Type Schema.org', 'type' => 'select', 'options' => ['RealEstateAgent' => 'RealEstateAgent', 'LocalBusiness' => 'LocalBusiness', 'Organization' => 'Organization'], 'default' => 'RealEstateAgent'],
            ],
        ],
    ],

    'tracking' => [
        'icon' => 'fas fa-chart-line',
        'label' => 'Tracking',
        'sections' => [
            'Google' => [
                'google_analytics_id'   => ['label' => 'Google Analytics ID',    'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX'],
                'google_tag_manager_id' => ['label' => 'Google Tag Manager ID',  'type' => 'text', 'placeholder' => 'GTM-XXXXXXX'],
            ],
            'Facebook' => [
                'facebook_pixel_id' => ['label' => 'Facebook Pixel ID', 'type' => 'text', 'placeholder' => 'XXXXXXXXXXXXXXXXXX'],
            ],
        ],
    ],

    'email' => [
        'icon' => 'fas fa-envelope',
        'label' => 'Emails',
        'sections' => [
            'Serveur SMTP' => [
                'smtp_host'       => ['label' => 'Serveur SMTP',       'type' => 'text',     'placeholder' => 'smtp.gmail.com'],
                'smtp_port'       => ['label' => 'Port',               'type' => 'number',   'placeholder' => '587', 'default' => '587'],
                'smtp_user'       => ['label' => 'Utilisateur',        'type' => 'text',     'placeholder' => 'user@gmail.com'],
                'smtp_pass'       => ['label' => 'Mot de passe',       'type' => 'password'],
            ],
            'Expéditeur' => [
                'smtp_from_name'  => ['label' => 'Nom d\'expéditeur',  'type' => 'text',  'placeholder' => 'Eduardo De Sul Immobilier'],
                'smtp_from_email' => ['label' => 'Email d\'expéditeur', 'type' => 'email', 'placeholder' => 'contact@monsite.fr'],
            ],
        ],
    ],

    'legal' => [
        'icon' => 'fas fa-gavel',
        'label' => 'Mentions légales',
        'sections' => [
            'Informations juridiques' => [
                'legal_entity'   => ['label' => 'Raison sociale',  'type' => 'text',     'placeholder' => 'Eduardo De Sul - EI'],
                'legal_mediator' => ['label' => 'Médiateur',       'type' => 'textarea', 'placeholder' => 'Nom et coordonnées du médiateur...', 'rows' => 3],
                'legal_dpo_email'=> ['label' => 'Email DPO (RGPD)', 'type' => 'email',   'placeholder' => 'dpo@monsite.fr'],
            ],
        ],
    ],
];

// ═══ CSRF Token ═══
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
/* ═══ SETTINGS TABS (existant conservé) ═══ */
.settings-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin: -24px -24px 24px; padding: 0 24px; background: var(--light); overflow-x: auto; -webkit-overflow-scrolling: touch; }
.settings-tab { padding: 14px 20px; font-size: 13px; font-weight: 500; color: var(--text-light); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; display: flex; align-items: center; gap: 8px; white-space: nowrap; flex-shrink: 0; }
.settings-tab:hover { color: var(--text); background: rgba(59,130,246,0.05); }
.settings-tab.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
.settings-tab .tab-count { background: var(--primary); color: white; font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 600; }
.settings-tab .tab-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.settings-tab .tab-badge.new { background: #dcfce7; color: #16a34a; }

/* ═══ SETTINGS FORM STYLES ═══ */
.settings-form-card { background: white; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 24px; overflow: hidden; }
.settings-form-section { padding: 20px 24px; border-bottom: 1px solid var(--border); }
.settings-form-section:last-child { border-bottom: none; }
.settings-form-section h3 { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.settings-form-section h3 i { color: var(--primary); font-size: 14px; }

.sf-group { margin-bottom: 18px; max-width: 600px; }
.sf-group:last-child { margin-bottom: 0; }
.sf-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.sf-group label .sf-required { color: #ef4444; margin-left: 2px; }
.sf-group label i.sf-icon { color: var(--text-light); margin-right: 6px; width: 16px; text-align: center; }
.sf-group .sf-hint { font-size: 11px; color: var(--text-light); margin-top: 4px; }
.sf-group .sf-current { font-size: 11px; color: var(--success); margin-top: 4px; font-style: italic; }

.sf-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; color: var(--text); transition: all 0.2s; background: white; font-family: inherit; box-sizing: border-box; }
.sf-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.sf-input::placeholder { color: #94a3b8; }

textarea.sf-input { resize: vertical; min-height: 80px; }
select.sf-input { cursor: pointer; }
input[type="number"].sf-input { max-width: 200px; }
input[type="password"].sf-input { font-family: 'Fira Code', 'Consolas', monospace; }

/* Color picker */
.sf-color-wrapper { display: flex; align-items: center; gap: 10px; }
.sf-color-wrapper input[type="color"] { width: 48px; height: 40px; padding: 2px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; }
.sf-color-wrapper .sf-color-hex { width: 100px; font-family: 'Fira Code', monospace; font-size: 13px; text-align: center; }

/* Image picker */
.sf-image-wrapper { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sf-image-preview { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }

/* Toggle */
.sf-toggle { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.sf-toggle input[type="checkbox"] { display: none; }
.sf-toggle-track { width: 44px; height: 24px; background: #cbd5e1; border-radius: 12px; position: relative; transition: 0.3s; flex-shrink: 0; }
.sf-toggle-track::after { content: ''; width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.sf-toggle input:checked + .sf-toggle-track { background: var(--success, #22c55e); }
.sf-toggle input:checked + .sf-toggle-track::after { left: 22px; }
.sf-toggle-label { font-size: 13px; color: var(--text-light); }

/* Footer save */
.settings-form-footer { padding: 16px 24px; background: var(--light); border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; position: sticky; bottom: 0; z-index: 10; }
.settings-form-footer .btn-save { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; }
.settings-form-footer .btn-save:hover { filter: brightness(1.1); }
.settings-form-footer .btn-save i { font-size: 13px; }
.settings-form-footer .save-hint { font-size: 12px; color: var(--text-light); }

/* Alert notification */
.alert-box { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; }
.alert-box i { margin-top: 2px; flex-shrink: 0; }
.alert-box.info { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.alert-box.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.alert-box.warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.alert-box.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ═══ API KEYS STYLES (existants conservés) ═══ */
.api-category { margin-bottom: 32px; }
.api-category-title { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
.api-category-title .cat-count { font-size: 12px; color: var(--text-light); font-weight: 400; }
.api-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
.api-card { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 20px; transition: all 0.3s; position: relative; overflow: hidden; }
.api-card:hover { border-color: var(--primary); box-shadow: 0 4px 16px rgba(59,130,246,0.1); transform: translateY(-2px); }
.api-card.configured { border-left: 4px solid var(--success); }
.api-card.not-configured { border-left: 4px solid var(--border); }
.api-card-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px; }
.api-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
.api-card-info { flex: 1; min-width: 0; }
.api-card-info h4 { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.api-card-info p { font-size: 12px; color: var(--text-light); line-height: 1.4; }
.api-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.api-status.configured { background: #dcfce7; color: #16a34a; }
.api-status.missing { background: #fef3c7; color: #d97706; }
.api-key-display { background: var(--light); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; font-family: 'Fira Code', 'Consolas', monospace; font-size: 13px; color: var(--text); margin: 10px 0; display: flex; align-items: center; justify-content: space-between; }
.api-key-display .key-value { opacity: 0.7; letter-spacing: 1px; }
.api-key-display .key-actions { display: flex; gap: 6px; }
.api-key-display .key-actions button { background: none; border: none; cursor: pointer; color: var(--text-light); font-size: 13px; padding: 4px; border-radius: 4px; transition: all 0.2s; }
.api-key-display .key-actions button:hover { color: var(--primary); background: rgba(59,130,246,0.1); }
.api-card-actions { display: flex; gap: 8px; margin-top: 12px; }
.btn-configure { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; font-family: inherit; }
.btn-configure.primary { background: var(--primary); color: white; }
.btn-configure.primary:hover { background: #2563eb; }
.btn-configure.outline { background: white; color: var(--text); border: 1px solid var(--border); }
.btn-configure.outline:hover { border-color: var(--primary); color: var(--primary); }
.btn-configure.danger { background: white; color: var(--danger); border: 1px solid rgba(239,68,68,0.3); }
.btn-configure.danger:hover { background: #fef2f2; }
.used-by-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.used-by-tag { font-size: 10px; padding: 2px 8px; background: #eff6ff; color: #3b82f6; border-radius: 4px; font-weight: 500; }

.api-summary { display: flex; gap: 16px; margin-bottom: 24px; padding: 16px 20px; background: white; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap; }
.api-summary-stat { display: flex; align-items: center; gap: 10px; }
.api-summary-stat .sum-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.api-summary-stat .sum-icon.green { background: #dcfce7; color: #16a34a; }
.api-summary-stat .sum-icon.orange { background: #fef3c7; color: #d97706; }
.api-summary-stat .sum-icon.blue { background: #dbeafe; color: #3b82f6; }
.api-summary-stat .sum-value { font-size: 20px; font-weight: 800; color: var(--text); }
.api-summary-stat .sum-label { font-size: 11px; color: var(--text-light); }

/* Modal API (existant conservé) */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.modal-overlay.show { display: flex; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); animation: modalIn 0.3s ease; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.modal-header h3 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-light); padding: 4px 8px; border-radius: 6px; }
.modal-close:hover { background: var(--light); color: var(--text); }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.form-group .form-hint { font-size: 11px; color: var(--text-light); margin-top: 4px; }
.form-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: 'Fira Code', 'Consolas', monospace; transition: all 0.2s; box-sizing: border-box; }
.form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.form-input.error { border-color: var(--danger); }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
.empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; display: block; }
.empty-state h3 { font-size: 18px; color: var(--text); margin-bottom: 8px; }

/* Responsive */
@media (max-width: 768px) {
    .settings-tabs { padding: 0 12px; }
    .settings-tab { padding: 10px 14px; font-size: 12px; }
    .api-cards { grid-template-columns: 1fr; }
    .sf-group { max-width: none; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- ONGLETS                                                            -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tabKey => $tabDef): ?>
        <a href="?module=settings&tab=<?= $tabKey ?>" class="settings-tab <?= $tab === $tabKey ? 'active' : '' ?>">
            <i class="<?= $tabDef['icon'] ?>"></i> <?= esc($tabDef['label']) ?>
        </a>
    <?php endforeach; ?>

    <!-- Séparateur visuel -->
    <span style="border-left:1px solid var(--border);margin:8px 4px;"></span>

    <!-- Onglet API Keys (existant) -->
    <a href="?module=settings&tab=api-keys" class="settings-tab <?= $tab === 'api-keys' ? 'active' : '' ?>">
        <i class="fas fa-key"></i> Clés API & IA
        <span class="tab-count"><?= $totalConfigured ?>/<?= $totalServices ?></span>
    </a>
    <a href="?module=settings&tab=integrations" class="settings-tab <?= $tab === 'integrations' ? 'active' : '' ?>">
        <i class="fas fa-plug"></i> Intégrations
    </a>
    <a href="?module=settings&tab=advanced" class="settings-tab <?= $tab === 'advanced' ? 'active' : '' ?>">
        <i class="fas fa-cogs"></i> Avancé
    </a>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- NOTIFICATION                                                       -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<?php if ($settingsMessage): ?>
    <div class="alert-box <?= $settingsMessageType ?>">
        <i class="fas fa-<?= $settingsMessageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= esc($settingsMessage) ?>
    </div>
<?php endif; ?>

<div id="apiNotification" class="alert-box success" style="display:none;">
    <i class="fas fa-check-circle"></i>
    <span id="apiNotificationText"></span>
</div>


<?php
// ═══════════════════════════════════════════════════════════════════
// RENDU : ONGLETS SETTINGS FORMULAIRE
// ═══════════════════════════════════════════════════════════════════
if (isset($settingsTabs[$tab])):
    $currentTab = $settingsTabs[$tab];
?>

<form method="POST" action="?module=settings&tab=<?= esc($tab) ?>" enctype="multipart/form-data" id="settingsForm">
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="group" value="<?= esc($tab) ?>">
    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">

    <div class="settings-form-card">
        <?php foreach ($currentTab['sections'] as $sectionTitle => $fields): ?>
            <div class="settings-form-section">
                <h3><i class="<?= $currentTab['icon'] ?>"></i> <?= esc($sectionTitle) ?></h3>

                <?php foreach ($fields as $fieldKey => $fieldDef):
                    $fieldType    = $fieldDef['type'] ?? 'text';
                    $fieldLabel   = $fieldDef['label'] ?? $fieldKey;
                    $fieldDefault = $fieldDef['default'] ?? '';
                    $fieldValue   = s($fieldKey, $fieldDefault);
                    $placeholder  = $fieldDef['placeholder'] ?? '';
                    $hint         = $fieldDef['hint'] ?? '';
                    $required     = $fieldDef['required'] ?? false;
                    $icon         = $fieldDef['icon'] ?? '';
                    $rows         = $fieldDef['rows'] ?? 3;
                ?>
                    <div class="sf-group">
                        <label for="sf_<?= $fieldKey ?>">
                            <?php if ($icon): ?><i class="sf-icon <?= $icon ?>"></i><?php endif; ?>
                            <?= esc($fieldLabel) ?>
                            <?php if ($required): ?><span class="sf-required">*</span><?php endif; ?>
                        </label>

                        <?php switch ($fieldType):

                            case 'textarea': ?>
                                <textarea name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>" 
                                          class="sf-input" rows="<?= $rows ?>" 
                                          placeholder="<?= esc($placeholder) ?>"><?= esc($fieldValue) ?></textarea>
                            <?php break;

                            case 'select': ?>
                                <select name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>" class="sf-input">
                                    <?php foreach ($fieldDef['options'] as $optVal => $optLabel): ?>
                                        <option value="<?= esc($optVal) ?>" <?= $fieldValue === $optVal ? 'selected' : '' ?>>
                                            <?= esc($optLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php break;

                            case 'toggle': ?>
                                <label class="sf-toggle">
                                    <input type="hidden" name="settings[<?= $fieldKey ?>]" value="0">
                                    <input type="checkbox" name="settings[<?= $fieldKey ?>]" value="1" 
                                           <?= $fieldValue === '1' ? 'checked' : '' ?>>
                                    <span class="sf-toggle-track"></span>
                                    <span class="sf-toggle-label"><?= $fieldValue === '1' ? 'Activé' : 'Désactivé' ?></span>
                                </label>
                            <?php break;

                            case 'color': ?>
                                <div class="sf-color-wrapper">
                                    <input type="color" name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>"
                                           value="<?= esc($fieldValue ?: '#000000') ?>"
                                           onchange="this.nextElementSibling.value=this.value">
                                    <input type="text" class="sf-input sf-color-hex" value="<?= esc($fieldValue) ?>" 
                                           maxlength="7" onchange="this.previousElementSibling.value=this.value">
                                </div>
                            <?php break;

                            case 'image': ?>
                                <div class="sf-image-wrapper">
                                    <?php if ($fieldValue): ?>
                                        <img src="<?= esc($fieldValue) ?>" alt="" class="sf-image-preview" 
                                             onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <input type="text" name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>"
                                           class="sf-input" value="<?= esc($fieldValue) ?>" 
                                           placeholder="<?= esc($placeholder ?: 'URL de l\'image ou chemin uploads/') ?>">
                                </div>
                            <?php break;

                            case 'password': ?>
                                <input type="password" name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>"
                                       class="sf-input" value="<?= esc($fieldValue) ?>" 
                                       placeholder="<?= esc($placeholder) ?>" autocomplete="off">
                            <?php break;

                            case 'number': ?>
                                <input type="number" name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>"
                                       class="sf-input" value="<?= esc($fieldValue) ?>" 
                                       placeholder="<?= esc($placeholder) ?>">
                            <?php break;

                            default: ?>
                                <input type="<?= $fieldType ?>" name="settings[<?= $fieldKey ?>]" id="sf_<?= $fieldKey ?>"
                                       class="sf-input" value="<?= esc($fieldValue) ?>" 
                                       placeholder="<?= esc($placeholder) ?>"
                                       <?= $required ? 'required' : '' ?>>
                            <?php break;

                        endswitch; ?>

                        <?php if ($hint): ?>
                            <div class="sf-hint"><?= esc($hint) ?></div>
                        <?php endif; ?>

                        <?php if ($fieldValue && $fieldType !== 'toggle' && $fieldType !== 'color'): ?>
                            <div class="sf-current">✓ Valeur actuelle enregistrée</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="settings-form-footer">
            <span class="save-hint">Les modifications prennent effet immédiatement sur le site.</span>
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
</form>


<?php
// ═══════════════════════════════════════════════════════════════════
// RENDU : ONGLET API KEYS (code existant conservé tel quel)
// ═══════════════════════════════════════════════════════════════════
elseif ($tab === 'api-keys'):
?>

    <div class="api-summary">
        <div class="api-summary-stat">
            <div class="sum-icon blue"><i class="fas fa-key"></i></div>
            <div><div class="sum-value"><?= $totalServices ?></div><div class="sum-label">Services disponibles</div></div>
        </div>
        <div class="api-summary-stat">
            <div class="sum-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="sum-value"><?= $totalConfigured ?></div><div class="sum-label">Clés configurées</div></div>
        </div>
        <div class="api-summary-stat">
            <div class="sum-icon orange"><i class="fas fa-exclamation-circle"></i></div>
            <div><div class="sum-value"><?= $totalServices - $totalConfigured ?></div><div class="sum-label">À configurer</div></div>
        </div>
    </div>

    <div class="alert-box info">
        <i class="fas fa-shield-alt"></i>
        <div><strong>Sécurité :</strong> Vos clés API sont chiffrées en AES-256 avant stockage. Seul un masque partiel est visible.</div>
    </div>

    <?php foreach ($servicesByCategory as $catKey => $category): ?>
        <div class="api-category">
            <div class="api-category-title">
                <?= $category['label'] ?>
                <span class="cat-count">
                    <?php 
                    $catConfigured = 0;
                    foreach ($category['services'] as $sk => $sv) {
                        if (isset($storedKeys[$sk]) && $storedKeys[$sk]['is_configured']) $catConfigured++;
                    }
                    echo $catConfigured . '/' . count($category['services']) . ' configuré(s)';
                    ?>
                </span>
            </div>
            <div class="api-cards">
                <?php foreach ($category['services'] as $serviceKey => $service): ?>
                    <?php 
                    $stored = $storedKeys[$serviceKey] ?? null;
                    $isConfigured = $stored && $stored['is_configured'];
                    ?>
                    <div class="api-card <?= $isConfigured ? 'configured' : 'not-configured' ?>" id="card-<?= $serviceKey ?>">
                        <div class="api-card-header">
                            <div class="api-icon" style="background:<?= $service['color'] ?>;"><i class="<?= $service['icon'] ?>"></i></div>
                            <div class="api-card-info">
                                <h4><?= htmlspecialchars($service['name']) ?></h4>
                                <p><?= htmlspecialchars($service['description']) ?></p>
                            </div>
                        </div>
                        <?php if ($isConfigured): ?>
                            <span class="api-status configured"><i class="fas fa-check-circle"></i> Configurée</span>
                            <div class="api-key-display">
                                <span class="key-value"><?= htmlspecialchars($stored['key_masked']) ?></span>
                                <span class="key-actions"><button onclick="openApiModal('<?= $serviceKey ?>', true)" title="Modifier"><i class="fas fa-pen"></i></button></span>
                            </div>
                            <?php if ($stored['updated_at']): ?>
                                <div style="font-size:11px;color:var(--text-light);"><i class="fas fa-clock"></i> Modifiée le <?= date('d/m/Y H:i', strtotime($stored['updated_at'])) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="api-status missing"><i class="fas fa-exclamation-circle"></i> Non configurée</span>
                        <?php endif; ?>
                        <div class="api-card-actions">
                            <?php if ($isConfigured): ?>
                                <button class="btn-configure outline" onclick="openApiModal('<?= $serviceKey ?>', true)"><i class="fas fa-pen"></i> Modifier</button>
                                <button class="btn-configure danger" onclick="deleteApiKey('<?= $serviceKey ?>', '<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            <?php else: ?>
                                <button class="btn-configure primary" onclick="openApiModal('<?= $serviceKey ?>', false)"><i class="fas fa-plus"></i> Configurer</button>
                            <?php endif; ?>
                            <a href="<?= $service['url'] ?>" target="_blank" class="btn-configure outline" title="Console du service"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                        <?php if (!empty($service['used_by'])): ?>
                            <div class="used-by-tags">
                                <?php foreach ($service['used_by'] as $usedBy): ?><span class="used-by-tag"><?= $usedBy ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>


<?php
// ═══════════════════════════════════════════════════════════════════
// ONGLETS VIDES (Intégrations, Avancé)
// ═══════════════════════════════════════════════════════════════════
elseif ($tab === 'integrations'): ?>
    <div class="empty-state"><i class="fas fa-plug"></i><h3>Intégrations</h3><p>Webhooks, connexions tierces, automatisations — bientôt disponible.</p></div>
<?php elseif ($tab === 'advanced'): ?>
    <div class="empty-state"><i class="fas fa-cogs"></i><h3>Paramètres Avancés</h3><p>Cache, maintenance, logs, backups — bientôt disponible.</p></div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODAL API KEY (existant conservé)                                   -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="apiModal">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <span id="modalIcon" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;color:white;font-size:16px;"></span>
                <span id="modalTitle">Configurer</span>
            </h3>
            <button class="modal-close" onclick="closeApiModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalAlert" class="alert-box warning" style="display:none;">
                <i class="fas fa-info-circle"></i><span id="modalAlertText"></span>
            </div>
            <form id="apiKeyForm" onsubmit="return saveApiKey(event)">
                <input type="hidden" id="modalServiceKey" value="">
                <div class="form-group">
                    <label for="modalApiKeyInput">Clé API</label>
                    <input type="password" id="modalApiKeyInput" class="form-input" placeholder="" autocomplete="off" required>
                    <div class="form-hint" id="modalHint"></div>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="showKeyToggle" onchange="toggleKeyVisibility()">
                    <label for="showKeyToggle" style="margin:0;font-size:12px;cursor:pointer;">Afficher la clé</label>
                </div>
                <div id="modalConsoleLink" style="margin-top:12px;">
                    <a href="#" target="_blank" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--primary);text-decoration:none;" id="modalExternalLink">
                        <i class="fas fa-external-link-alt"></i> Obtenir ma clé sur le site du service
                    </a>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-configure outline" onclick="closeApiModal()">Annuler</button>
            <button class="btn-configure primary" onclick="saveApiKey()" id="modalSaveBtn"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT                                                          -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
// ===== API Keys JS (existant conservé) =====
const API_SERVICES = <?= json_encode(ApiKeyManager::SERVICES, JSON_UNESCAPED_UNICODE) ?>;
const API_KEYS_ENDPOINT = '/admin/modules/settings/api/api-keys.php';

function openApiModal(serviceKey, isEdit) {
    const service = API_SERVICES[serviceKey];
    if (!service) return;
    const modal = document.getElementById('apiModal');
    document.getElementById('modalServiceKey').value = serviceKey;
    document.getElementById('modalIcon').style.background = service.color;
    document.getElementById('modalIcon').innerHTML = `<i class="${service.icon}"></i>`;
    document.getElementById('modalTitle').textContent = isEdit ? `Modifier — ${service.name}` : `Configurer — ${service.name}`;
    const input = document.getElementById('modalApiKeyInput');
    input.value = ''; input.type = 'password';
    input.placeholder = service.placeholder || 'Entrez votre clé API...';
    document.getElementById('showKeyToggle').checked = false;
    document.getElementById('modalHint').textContent = service.prefix ? `La clé commence généralement par "${service.prefix}"` : 'Collez votre clé API ici';
    document.getElementById('modalExternalLink').href = service.url;
    const alert = document.getElementById('modalAlert');
    if (isEdit) { alert.style.display = 'flex'; document.getElementById('modalAlertText').textContent = 'La saisie d\'une nouvelle clé remplacera l\'ancienne.'; }
    else { alert.style.display = 'none'; }
    modal.classList.add('show');
    setTimeout(() => input.focus(), 300);
}

function closeApiModal() { document.getElementById('apiModal').classList.remove('show'); document.getElementById('modalApiKeyInput').value = ''; }
function toggleKeyVisibility() { document.getElementById('modalApiKeyInput').type = document.getElementById('showKeyToggle').checked ? 'text' : 'password'; }

async function saveApiKey(e) {
    if (e) e.preventDefault();
    const serviceKey = document.getElementById('modalServiceKey').value;
    const apiKey = document.getElementById('modalApiKeyInput').value.trim();
    const btn = document.getElementById('modalSaveBtn');
    if (!apiKey) { document.getElementById('modalApiKeyInput').classList.add('error'); return; }
    document.getElementById('modalApiKeyInput').classList.remove('error');
    const service = API_SERVICES[serviceKey];
    if (service.prefix && !apiKey.startsWith(service.prefix)) {
        if (!confirm(`Cette clé ne commence pas par "${service.prefix}". Continuer ?`)) return;
    }
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    try {
        const resp = await fetch(API_KEYS_ENDPOINT, { method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'save', service_key:serviceKey, api_key:apiKey, csrf_token:'<?= $_SESSION['csrf_token'] ?? '' ?>' })
        });
        const data = await resp.json();
        if (data.success) { closeApiModal(); showNotification('success', `Clé ${service.name} enregistrée !`); setTimeout(() => location.reload(), 800); }
        else { showNotification('error', data.message || 'Erreur.'); }
    } catch (err) { console.error(err); showNotification('error', 'Erreur de connexion.'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer'; }
}

async function deleteApiKey(serviceKey, serviceName) {
    if (!confirm(`Supprimer la clé API de ${serviceName} ?`)) return;
    try {
        const resp = await fetch(API_KEYS_ENDPOINT, { method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', service_key:serviceKey, csrf_token:'<?= $_SESSION['csrf_token'] ?? '' ?>' })
        });
        const data = await resp.json();
        if (data.success) { showNotification('success', `Clé ${serviceName} supprimée.`); setTimeout(() => location.reload(), 800); }
        else { showNotification('error', data.message || 'Erreur.'); }
    } catch (err) { showNotification('error', 'Erreur de connexion.'); }
}

function showNotification(type, message) {
    const el = document.getElementById('apiNotification');
    el.className = `alert-box ${type}`;
    el.querySelector('i').className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    document.getElementById('apiNotificationText').textContent = message;
    el.style.display = 'flex';
    setTimeout(() => { el.style.display = 'none'; }, 5000);
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ===== Settings Form JS =====
// Toggle label update
document.querySelectorAll('.sf-toggle input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', function() {
        const label = this.closest('.sf-toggle').querySelector('.sf-toggle-label');
        if (label) label.textContent = this.checked ? 'Activé' : 'Désactivé';
    });
});

// Unsaved changes warning
let formChanged = false;
const settingsForm = document.getElementById('settingsForm');
if (settingsForm) {
    settingsForm.addEventListener('input', () => { formChanged = true; });
    settingsForm.addEventListener('submit', () => { formChanged = false; });
}
window.addEventListener('beforeunload', e => {
    if (formChanged) { e.preventDefault(); e.returnValue = ''; }
});

// Close modal on ESC / overlay click
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeApiModal(); });
document.getElementById('apiModal')?.addEventListener('click', function(e) { if (e.target === this) closeApiModal(); });
</script>