<?php
/**
 * ApiKeyManager - Gestion centralisée et sécurisée des clés API
 * 
 * Emplacement : /admin/modules/settings/api/ApiKeyManager.php
 * 
 * Usage:
 *   require_once __DIR__ . '/api/ApiKeyManager.php';  // depuis settings/index.php
 *   require_once __DIR__ . '/ApiKeyManager.php';       // depuis settings/api/api-keys.php
 *   
 *   $manager = ApiKeyManager::getInstance($pdo);
 *   $key = $manager->getKey('openai');
 *   $manager->hasKey('openai');  // true/false
 *   $manager->saveKey('openai', 'sk-...');
 * 
 * @version 1.0
 */

class ApiKeyManager
{
    private PDO $pdo;
    private static ?ApiKeyManager $instance = null;
    
    /** Cache mémoire pour éviter les requêtes répétées */
    private array $cache = [];
    
    /** 
     * Clé de chiffrement — À PERSONNALISER dans config.php
     * Définir: define('API_ENCRYPTION_KEY', 'votre-clé-secrète-32-chars-min');
     */
    private string $encryptionKey;
    
    /** Algorithme de chiffrement */
    private const CIPHER = 'aes-256-cbc';
    
    /**
     * Registre des services connus avec leur configuration
     */
    public const SERVICES = [
        // ═══ Intelligence Artificielle ═══
        'openai' => [
            'name'        => 'OpenAI (GPT-4, DALL-E)',
            'category'    => 'ai',
            'icon'        => 'fas fa-robot',
            'color'       => '#10a37f',
            'placeholder' => 'sk-proj-...',
            'url'         => 'https://platform.openai.com/api-keys',
            'description' => 'Génération de texte, images, assistant IA',
            'prefix'      => 'sk-',
            'used_by'     => ['ai', 'articles', 'seo-semantic', 'neuropersona']
        ],
        'claude' => [
            'name'        => 'Claude (Anthropic)',
            'category'    => 'ai',
            'icon'        => 'fas fa-brain',
            'color'       => '#cc785c',
            'placeholder' => 'sk-ant-...',
            'url'         => 'https://console.anthropic.com/settings/keys',
            'description' => 'Assistant IA avancé, rédaction, analyse',
            'prefix'      => 'sk-ant-',
            'used_by'     => ['ai', 'articles', 'seo-semantic']
        ],
        'perplexity' => [
            'name'        => 'Perplexity AI',
            'category'    => 'ai',
            'icon'        => 'fas fa-search',
            'color'       => '#20808d',
            'placeholder' => 'pplx-...',
            'url'         => 'https://www.perplexity.ai/settings/api',
            'description' => 'Recherche IA en temps réel, veille marché',
            'prefix'      => 'pplx-',
            'used_by'     => ['ai', 'seo-semantic', 'local-seo']
        ],
        'mistral' => [
            'name'        => 'Mistral AI',
            'category'    => 'ai',
            'icon'        => 'fas fa-wind',
            'color'       => '#f54e42',
            'placeholder' => 'votre-clé-mistral',
            'url'         => 'https://console.mistral.ai/api-keys/',
            'description' => 'IA française, génération de texte rapide',
            'prefix'      => '',
            'used_by'     => ['ai']
        ],
        
        // ═══ Google ═══
        'google_maps' => [
            'name'        => 'Google Maps Platform',
            'category'    => 'google',
            'icon'        => 'fas fa-map-marker-alt',
            'color'       => '#4285f4',
            'placeholder' => 'AIza...',
            'url'         => 'https://console.cloud.google.com/apis/credentials',
            'description' => 'Cartes, géolocalisation, itinéraires biens',
            'prefix'      => 'AIza',
            'used_by'     => ['biens', 'secteurs', 'estimation']
        ],
        'google_analytics' => [
            'name'        => 'Google Analytics (GA4)',
            'category'    => 'google',
            'icon'        => 'fas fa-chart-line',
            'color'       => '#e37400',
            'placeholder' => 'Measurement ID: G-XXXXXXXXXX',
            'url'         => 'https://analytics.google.com/',
            'description' => 'Suivi du trafic et comportement visiteurs',
            'prefix'      => 'G-',
            'used_by'     => ['analytics', 'seo']
        ],
        'google_search_console' => [
            'name'        => 'Google Search Console',
            'category'    => 'google',
            'icon'        => 'fas fa-search',
            'color'       => '#4285f4',
            'placeholder' => 'Token JSON ou clé API',
            'url'         => 'https://search.google.com/search-console',
            'description' => 'Indexation, positions Google, sitemaps',
            'prefix'      => '',
            'used_by'     => ['seo', 'seo-indexation', 'seo-serp']
        ],
        'google_ads' => [
            'name'        => 'Google Ads API',
            'category'    => 'google',
            'icon'        => 'fab fa-google',
            'color'       => '#4285f4',
            'placeholder' => 'Developer Token + Client ID',
            'url'         => 'https://ads.google.com/',
            'description' => 'Gestion campagnes publicitaires Google',
            'prefix'      => '',
            'used_by'     => ['google-ads', 'google-ads-wizard', 'google-ads-keywords']
        ],
        'google_my_business' => [
            'name'        => 'Google My Business',
            'category'    => 'google',
            'icon'        => 'fas fa-store',
            'color'       => '#34a853',
            'placeholder' => 'Token OAuth2',
            'url'         => 'https://business.google.com/',
            'description' => 'Fiche établissement, avis, publications',
            'prefix'      => '',
            'used_by'     => ['local-seo', 'local-gmb-posts', 'local-gmb-avis']
        ],
        
        // ═══ Réseaux Sociaux ═══
        'facebook_app' => [
            'name'        => 'Facebook / Meta API',
            'category'    => 'social',
            'icon'        => 'fab fa-facebook',
            'color'       => '#1877f2',
            'placeholder' => 'App ID | App Secret',
            'url'         => 'https://developers.facebook.com/apps/',
            'description' => 'Publication automatique, Facebook Ads',
            'prefix'      => '',
            'used_by'     => ['facebook', 'facebook-ads']
        ],
        'instagram_api' => [
            'name'        => 'Instagram Graph API',
            'category'    => 'social',
            'icon'        => 'fab fa-instagram',
            'color'       => '#e4405f',
            'placeholder' => 'Access Token Instagram',
            'url'         => 'https://developers.facebook.com/',
            'description' => 'Publication Reels, stories, feed',
            'prefix'      => '',
            'used_by'     => ['instagram']
        ],
        'tiktok_api' => [
            'name'        => 'TikTok API',
            'category'    => 'social',
            'icon'        => 'fab fa-tiktok',
            'color'       => '#000000',
            'placeholder' => 'Client Key TikTok',
            'url'         => 'https://developers.tiktok.com/',
            'description' => 'Publication vidéos, analytics TikTok',
            'prefix'      => '',
            'used_by'     => ['tiktok']
        ],
        
        // ═══ Emails & Autres ═══
        'mailjet' => [
            'name'        => 'Mailjet',
            'category'    => 'other',
            'icon'        => 'fas fa-envelope',
            'color'       => '#fead0d',
            'placeholder' => 'API Key | Secret Key',
            'url'         => 'https://app.mailjet.com/account/apikeys',
            'description' => 'Envoi d\'emails transactionnels et marketing',
            'prefix'      => '',
            'used_by'     => ['emails']
        ],
        'sendinblue' => [
            'name'        => 'Brevo (Sendinblue)',
            'category'    => 'other',
            'icon'        => 'fas fa-paper-plane',
            'color'       => '#0b996e',
            'placeholder' => 'xkeysib-...',
            'url'         => 'https://app.brevo.com/settings/keys/api',
            'description' => 'Emails, SMS, automation marketing',
            'prefix'      => 'xkeysib-',
            'used_by'     => ['emails']
        ],
        'stripe' => [
            'name'        => 'Stripe',
            'category'    => 'other',
            'icon'        => 'fab fa-stripe',
            'color'       => '#635bff',
            'placeholder' => 'sk_live_...',
            'url'         => 'https://dashboard.stripe.com/apikeys',
            'description' => 'Paiements en ligne',
            'prefix'      => 'sk_',
            'used_by'     => ['transactions']
        ],
        'twilio' => [
            'name'        => 'Twilio',
            'category'    => 'other',
            'icon'        => 'fas fa-sms',
            'color'       => '#f22f46',
            'placeholder' => 'Account SID | Auth Token',
            'url'         => 'https://www.twilio.com/console',
            'description' => 'Envoi de SMS automatiques',
            'prefix'      => '',
            'used_by'     => ['crm']
        ]
    ];
    
    // ─────────────────────────────────────────────
    
    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->encryptionKey = defined('API_ENCRYPTION_KEY') 
            ? API_ENCRYPTION_KEY 
            : hash('sha256', 'immo-local-default-key-change-me');
    }
    
    public static function getInstance(PDO $pdo): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }
    
    // ═══ CHIFFREMENT / DÉCHIFFREMENT ═══
    
    private function encrypt(string $plainText): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($plainText, self::CIPHER, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }
    
    private function decrypt(string $cipherText): ?string
    {
        $data = base64_decode($cipherText);
        if ($data === false) return null;
        $parts = explode('::', $data, 2);
        if (count($parts) !== 2) return null;
        [$iv, $encrypted] = $parts;
        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $this->encryptionKey, 0, $iv);
        return $decrypted !== false ? $decrypted : null;
    }
    
    // ═══ CRUD OPERATIONS ═══
    
    public function getKey(string $serviceKey): ?string
    {
        if (isset($this->cache[$serviceKey])) return $this->cache[$serviceKey];
        try {
            $stmt = $this->pdo->prepare("SELECT api_key_encrypted FROM api_keys WHERE service_key = ? AND is_active = 1");
            $stmt->execute([$serviceKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || empty($row['api_key_encrypted'])) { $this->cache[$serviceKey] = null; return null; }
            $decrypted = $this->decrypt($row['api_key_encrypted']);
            $this->cache[$serviceKey] = $decrypted;
            return $decrypted;
        } catch (PDOException $e) {
            error_log("ApiKeyManager::getKey error: " . $e->getMessage());
            return null;
        }
    }
    
    public function hasKey(string $serviceKey): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE service_key = ? AND api_key_encrypted IS NOT NULL AND api_key_encrypted != '' AND is_active = 1");
            $stmt->execute([$serviceKey]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) { return false; }
    }
    
    public function saveKey(string $serviceKey, string $apiKey): bool
    {
        try {
            $encrypted = $this->encrypt($apiKey);
            $stmt = $this->pdo->prepare("UPDATE api_keys SET api_key_encrypted = ?, verification_status = 'unknown', updated_at = NOW() WHERE service_key = ?");
            $result = $stmt->execute([$encrypted, $serviceKey]);
            
            if ($stmt->rowCount() === 0) {
                $info = self::SERVICES[$serviceKey] ?? null;
                $name = $info['name'] ?? $serviceKey;
                $category = $info['category'] ?? 'other';
                $stmt2 = $this->pdo->prepare("INSERT INTO api_keys (service_key, service_name, api_key_encrypted, category) VALUES (?, ?, ?, ?)");
                $result = $stmt2->execute([$serviceKey, $name, $encrypted, $category]);
            }
            
            unset($this->cache[$serviceKey]);
            return $result;
        } catch (PDOException $e) {
            error_log("ApiKeyManager::saveKey error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteKey(string $serviceKey): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE api_keys SET api_key_encrypted = NULL, verification_status = 'unknown' WHERE service_key = ?");
            $stmt->execute([$serviceKey]);
            unset($this->cache[$serviceKey]);
            return true;
        } catch (PDOException $e) {
            error_log("ApiKeyManager::deleteKey error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllKeys(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT service_key, service_name, category, api_key_encrypted, is_active, 
                        verification_status, last_verified_at, notes, updated_at 
                 FROM api_keys ORDER BY category, service_name"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['is_configured'] = !empty($row['api_key_encrypted']);
                $row['key_masked'] = $row['is_configured'] ? $this->maskKey($this->decrypt($row['api_key_encrypted'])) : null;
                unset($row['api_key_encrypted']);
            }
            return $rows;
        } catch (PDOException $e) {
            error_log("ApiKeyManager::getAllKeys error: " . $e->getMessage());
            return [];
        }
    }
    
    private function maskKey(?string $key): string
    {
        if (!$key) return '';
        $len = strlen($key);
        if ($len <= 8) return str_repeat('•', $len);
        return substr($key, 0, 5) . str_repeat('•', min($len - 9, 12)) . substr($key, -4);
    }
    
    public function checkRequiredKeys(string $moduleSlug): array
    {
        $missing = [];
        $available = [];
        foreach (self::SERVICES as $serviceKey => $config) {
            if (in_array($moduleSlug, $config['used_by'] ?? [])) {
                if ($this->hasKey($serviceKey)) { $available[] = $serviceKey; }
                else { $missing[] = $serviceKey; }
            }
        }
        return ['missing' => $missing, 'available' => $available];
    }
    
    public function updateVerificationStatus(string $serviceKey, string $status): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE api_keys SET verification_status = ?, last_verified_at = NOW() WHERE service_key = ?");
            return $stmt->execute([$status, $serviceKey]);
        } catch (PDOException $e) { return false; }
    }
    
    public static function getServiceInfo(string $serviceKey): ?array
    {
        return self::SERVICES[$serviceKey] ?? null;
    }
    
    public static function getServicesByCategory(): array
    {
        $categories = [
            'ai'        => ['label' => '🤖 Intelligence Artificielle', 'services' => []],
            'google'    => ['label' => '🔍 Google Services', 'services' => []],
            'social'    => ['label' => '📱 Réseaux Sociaux', 'services' => []],
            'analytics' => ['label' => '📊 Analytics', 'services' => []],
            'other'     => ['label' => '🔧 Autres Services', 'services' => []],
        ];
        foreach (self::SERVICES as $key => $service) {
            $cat = $service['category'] ?? 'other';
            if (isset($categories[$cat])) $categories[$cat]['services'][$key] = $service;
        }
        return array_filter($categories, fn($c) => !empty($c['services']));
    }
}