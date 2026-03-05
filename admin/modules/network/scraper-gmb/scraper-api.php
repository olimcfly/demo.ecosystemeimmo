<?php
/**
 * API Scraper Google My Business
 * Utilise l'API Google Places pour rechercher des entreprises
 */
header('Content-Type: application/json');

// Connexion BDD
require_once __DIR__ . '/../../../config/config.php';

if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur connexion BDD']);
        exit;
    }
}

// Clé API Google (à configurer)
$GOOGLE_API_KEY = defined('GOOGLE_PLACES_API_KEY') ? GOOGLE_PLACES_API_KEY : '';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search':
        searchPlaces();
        break;
    case 'get_stats':
        getStats();
        break;
    case 'get_history':
        getHistory();
        break;
    case 'save_results':
        saveResults();
        break;
    case 'get_prospects':
        getProspects();
        break;
    case 'update_prospect':
        updateProspect();
        break;
    case 'delete_prospect':
        deleteProspect();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
}

function searchPlaces() {
    global $pdo, $GOOGLE_API_KEY;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $activity = $data['activity'] ?? '';
    $location = $data['location'] ?? '';
    $radius = ($data['radius'] ?? 5) * 1000; // km to meters
    
    if (empty($activity) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Activité et ville requises']);
        return;
    }
    
    // Si pas de clé API, utiliser des données de démo
    if (empty($GOOGLE_API_KEY)) {
        $demoResults = generateDemoResults($activity, $location);
        
        // Sauvegarder la recherche
        saveSearch($activity, $location, $radius, count($demoResults));
        
        echo json_encode([
            'success' => true,
            'results' => $demoResults,
            'total' => count($demoResults),
            'demo_mode' => true,
            'message' => 'Mode démo - Configurez GOOGLE_PLACES_API_KEY pour des résultats réels'
        ]);
        return;
    }
    
    // Geocoding pour obtenir les coordonnées
    $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($location) . "&key=" . $GOOGLE_API_KEY;
    $geocodeResponse = file_get_contents($geocodeUrl);
    $geocodeData = json_decode($geocodeResponse, true);
    
    if ($geocodeData['status'] !== 'OK') {
        echo json_encode(['success' => false, 'message' => 'Ville non trouvée']);
        return;
    }
    
    $lat = $geocodeData['results'][0]['geometry']['location']['lat'];
    $lng = $geocodeData['results'][0]['geometry']['location']['lng'];
    
    // Recherche Google Places
    $searchUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?" . http_build_query([
        'location' => "$lat,$lng",
        'radius' => $radius,
        'keyword' => $activity,
        'key' => $GOOGLE_API_KEY
    ]);
    
    $searchResponse = file_get_contents($searchUrl);
    $searchData = json_decode($searchResponse, true);
    
    if ($searchData['status'] !== 'OK' && $searchData['status'] !== 'ZERO_RESULTS') {
        echo json_encode(['success' => false, 'message' => 'Erreur API Google: ' . $searchData['status']]);
        return;
    }
    
    $results = [];
    foreach ($searchData['results'] ?? [] as $place) {
        $results[] = [
            'place_id' => $place['place_id'],
            'name' => $place['name'],
            'address' => $place['vicinity'] ?? '',
            'rating' => $place['rating'] ?? null,
            'reviews_count' => $place['user_ratings_total'] ?? 0,
            'types' => $place['types'] ?? [],
            'phone' => '', // Nécessite Place Details API
            'website' => '',
            'lat' => $place['geometry']['location']['lat'],
            'lng' => $place['geometry']['location']['lng']
        ];
    }
    
    // Sauvegarder la recherche
    saveSearch($activity, $location, $radius, count($results));
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);
}

function generateDemoResults($activity, $location) {
    $types = [
        'notaire' => ['Notaire', 'Office Notarial', 'Étude Notariale'],
        'agence immo' => ['Agence Immobilière', 'Immobilier', 'Cabinet Immobilier'],
        'courtier' => ['Courtier', 'Courtage', 'Crédit Immobilier'],
        'banque' => ['Banque', 'Crédit', 'Financement'],
        'architecte' => ['Architecte', 'Cabinet Architecture', 'Bureau Études'],
        'diagnostiqueur' => ['Diagnostic Immobilier', 'DPE', 'Diagnostics'],
        'artisan' => ['Artisan', 'Rénovation', 'Travaux'],
        'syndic' => ['Syndic', 'Copropriété', 'Gestion Immobilière']
    ];
    
    $prefix = $types[strtolower($activity)] ?? [$activity];
    $results = [];
    $count = rand(5, 15);
    
    $rues = ['Avenue de la République', 'Rue du Commerce', 'Boulevard Voltaire', 'Place de la Mairie', 'Rue Jean Jaurès', 'Avenue Foch', 'Rue Pasteur', 'Boulevard Gambetta'];
    $prenoms = ['Jean', 'Pierre', 'Marie', 'Philippe', 'Laurent', 'Sophie', 'Nicolas', 'François'];
    $noms = ['Martin', 'Dubois', 'Bernard', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand'];
    
    for ($i = 0; $i < $count; $i++) {
        $hasPhone = rand(0, 100) > 30;
        $hasWebsite = rand(0, 100) > 50;
        $rating = rand(0, 100) > 20 ? round(rand(30, 50) / 10, 1) : null;
        
        $results[] = [
            'place_id' => 'demo_' . uniqid(),
            'name' => $prefix[array_rand($prefix)] . ' ' . $prenoms[array_rand($prenoms)] . ' ' . $noms[array_rand($noms)],
            'address' => rand(1, 150) . ' ' . $rues[array_rand($rues)] . ', ' . $location,
            'rating' => $rating,
            'reviews_count' => $rating ? rand(5, 200) : 0,
            'phone' => $hasPhone ? '0' . rand(1, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) : '',
            'website' => $hasWebsite ? 'https://www.' . strtolower(str_replace(' ', '', $noms[array_rand($noms)])) . '-' . strtolower($activity) . '.fr' : '',
            'email' => $hasPhone ? 'contact@' . strtolower(str_replace(' ', '', $noms[array_rand($noms)])) . '.fr' : '',
            'lat' => 44.8378 + (rand(-100, 100) / 1000),
            'lng' => -0.5792 + (rand(-100, 100) / 1000)
        ];
    }
    
    return $results;
}

function saveSearch($activity, $location, $radius, $resultsCount) {
    global $pdo;
    try {
        // Vérifier si la table existe
        $pdo->query("SELECT 1 FROM gmb_searches LIMIT 1");
        
        $stmt = $pdo->prepare("INSERT INTO gmb_searches (activity, location, radius, results_count, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$activity, $location, $radius, $resultsCount]);
    } catch (PDOException $e) {
        // Table n'existe pas, on ignore
    }
}

function getStats() {
    global $pdo;
    try {
        $stats = [
            'searches' => 0,
            'prospects' => 0,
            'with_phone' => 0,
            'converted' => 0,
            'avg_rating' => 0
        ];
        
        // Vérifier si les tables existent
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM gmb_searches");
            $stats['searches'] = $stmt->fetchColumn();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN phone != '' THEN 1 ELSE 0 END) as with_phone, SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted, AVG(rating) as avg_rating FROM gmb_prospects");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['prospects'] = $row['total'] ?? 0;
            $stats['with_phone'] = $row['with_phone'] ?? 0;
            $stats['converted'] = $row['converted'] ?? 0;
            $stats['avg_rating'] = round($row['avg_rating'] ?? 0, 1);
        } catch (PDOException $e) {}
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'stats' => ['searches' => 0, 'prospects' => 0, 'with_phone' => 0, 'converted' => 0, 'avg_rating' => 0]]);
    }
}

function getHistory() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM gmb_searches ORDER BY created_at DESC LIMIT 20");
        $searches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'searches' => $searches]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'searches' => []]);
    }
}

function saveResults() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    $results = $data['results'] ?? [];
    
    if (empty($results)) {
        echo json_encode(['success' => false, 'message' => 'Aucun résultat à sauvegarder']);
        return;
    }
    
    try {
        $saved = 0;
        $stmt = $pdo->prepare("
            INSERT INTO gmb_prospects (place_id, name, address, phone, email, website, rating, reviews_count, activity, location, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        
        foreach ($results as $r) {
            $stmt->execute([
                $r['place_id'],
                $r['name'],
                $r['address'] ?? '',
                $r['phone'] ?? '',
                $r['email'] ?? '',
                $r['website'] ?? '',
                $r['rating'],
                $r['reviews_count'] ?? 0,
                $data['activity'] ?? '',
                $data['location'] ?? ''
            ]);
            $saved++;
        }
        
        echo json_encode(['success' => true, 'saved' => $saved]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function getProspects() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM gmb_prospects ORDER BY created_at DESC LIMIT 100");
        $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'prospects' => $prospects]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'prospects' => []]);
    }
}

function updateProspect() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("UPDATE gmb_prospects SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['status'] ?? 'new', $data['notes'] ?? '', $data['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function deleteProspect() {
    global $pdo;
    $id = $_GET['id'] ?? null;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM gmb_prospects WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}