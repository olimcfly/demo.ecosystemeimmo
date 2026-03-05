<?php
/**
 * IA Generate - Dispatcher Central
 * Route les requêtes IA vers les modules spécialisés
 * 
 * @project  IMMO LOCAL+ - 
 * @version  2.0
 */

declare(strict_types=1);

// ─── Initialisation ───────────────────────────────────────────────────────────
require_once __DIR__ . '/../admin/includes/init.php';

// Sécurité : session admin requise
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// ─── Headers JSON ─────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── CSRF Check ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        exit;
    }
} else {
    $input = $_GET;
}

// ─── Routing vers les modules ──────────────────────────────────────────────────
$module = preg_replace('/[^a-z_]/', '', strtolower($input['module'] ?? ''));

$allowedModules = [
    'articles',
    'biens',
    'leads',
    'seo',
    'social',
    'gmb',
    'captures',
];

if (empty($module) || !in_array($module, $allowedModules, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Module invalide ou manquant',
        'allowed' => $allowedModules,
    ]);
    exit;
}

$moduleFile = __DIR__ . "/modules/{$module}.php";

if (!file_exists($moduleFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Module '{$module}' introuvable sur le serveur"]);
    exit;
}

// ─── Logger la requête IA ─────────────────────────────────────────────────────
ia_log("Dispatch → module: {$module} | action: " . ($input['action'] ?? 'N/A') . " | admin: " . $_SESSION['admin_id']);

// ─── Passer $input au module et l'exécuter ────────────────────────────────────
try {
    require $moduleFile;
} catch (Throwable $e) {
    ia_log("ERREUR module {$module}: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur interne du module IA',
        'detail'  => (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null,
    ]);
}

// ─── Fonctions helpers globales ───────────────────────────────────────────────

/**
 * Logger les appels IA
 */
function ia_log(string $message, string $level = 'info'): void
{
    $logFile = __DIR__ . '/../logs/ia.log';
    $date    = date('Y-m-d H:i:s');
    $line    = "[{$date}] [{$level}] {$message}" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Appel API Claude (Anthropic)
 */
function callClaude(string $prompt, string $systemPrompt = '', int $maxTokens = 2000, float $temperature = 0.7): array
{
    $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : (getenv('CLAUDE_API_KEY') ?: '');
    
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Clé API Claude manquante'];
    }

    $messages = [['role' => 'user', 'content' => $prompt]];

    $payload = [
        'model'      => 'claude-opus-4-5',
        'max_tokens' => $maxTokens,
        'temperature'=> $temperature,
        'messages'   => $messages,
    ];

    if (!empty($systemPrompt)) {
        $payload['system'] = $systemPrompt;
    }

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        ia_log("cURL error Claude: {$curlError}", 'error');
        return ['success' => false, 'error' => 'Erreur réseau: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? "HTTP {$httpCode}";
        ia_log("API Claude erreur: {$errMsg}", 'error');
        return ['success' => false, 'error' => $errMsg];
    }

    $text = $data['content'][0]['text'] ?? '';
    return ['success' => true, 'content' => $text, 'usage' => $data['usage'] ?? []];
}

/**
 * Appel API OpenAI (GPT / DALL-E)
 */
function callOpenAI(string $prompt, string $model = 'gpt-4o', int $maxTokens = 2000, string $systemPrompt = ''): array
{
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : (getenv('OPENAI_API_KEY') ?: '');

    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Clé API OpenAI manquante'];
    }

    $messages = [];
    if (!empty($systemPrompt)) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];

    $payload = [
        'model'      => $model,
        'messages'   => $messages,
        'max_tokens' => $maxTokens,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => 'Erreur réseau OpenAI: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => $data['error']['message'] ?? "HTTP {$httpCode}"];
    }

    return [
        'success' => true,
        'content' => $data['choices'][0]['message']['content'] ?? '',
        'usage'   => $data['usage'] ?? [],
    ];
}

/**
 * Appel API Perplexity (recherche web enrichie)
 */
function callPerplexity(string $prompt, string $model = 'llama-3.1-sonar-large-128k-online'): array
{
    $apiKey = defined('PERPLEXITY_API_KEY') ? PERPLEXITY_API_KEY : (getenv('PERPLEXITY_API_KEY') ?: '');

    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Clé API Perplexity manquante'];
    }

    $payload = [
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Tu es un expert immobilier français spécialisé sur Bordeaux. Réponds toujours en français.'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ];

    $ch = curl_init('https://api.perplexity.ai/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 45,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => 'Erreur réseau Perplexity: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => $data['error']['message'] ?? "HTTP {$httpCode}"];
    }

    return [
        'success'     => true,
        'content'     => $data['choices'][0]['message']['content'] ?? '',
        'citations'   => $data['citations'] ?? [],
    ];
}

/**
 * Générer une image DALL-E 3
 */
function generateImageDALLE(string $prompt, string $size = '1792x1024', string $quality = 'standard'): array
{
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : (getenv('OPENAI_API_KEY') ?: '');

    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Clé API OpenAI manquante'];
    }

    // Sanitize prompt pour éviter les refus de content policy
    $safePrompt = "Professional real estate photography, {$prompt}, high quality, bright, modern, French real estate market, editorial style";

    $payload = [
        'model'   => 'dall-e-3',
        'prompt'  => $safePrompt,
        'n'       => 1,
        'size'    => $size,
        'quality' => $quality,
        'style'   => 'natural',
    ];

    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 90,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => $data['error']['message'] ?? "HTTP {$httpCode}"];
    }

    return [
        'success'        => true,
        'url'            => $data['data'][0]['url'] ?? '',
        'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
    ];
}

/**
 * Extraire un JSON d'une réponse texte IA
 */
function extractJsonFromAI(string $text): ?array
{
    // Chercher JSON entre ```json ... ```
    if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $m)) {
        $decoded = json_decode($m[1], true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }

    // Chercher JSON brut
    if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
        $decoded = json_decode($m[0], true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }

    return null;
}

/**
 * Générer un slug propre
 */
function generateSlug(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $map  = ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
              'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u',
              'ç'=>'c','ñ'=>'n','æ'=>'ae','œ'=>'oe'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return trim($text, '-');
}

/**
 * Réponse JSON standardisée
 */
function jsonResponse(bool $success, array $data = [], string $error = ''): void
{
    $response = ['success' => $success];
    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['error'] = $error;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}