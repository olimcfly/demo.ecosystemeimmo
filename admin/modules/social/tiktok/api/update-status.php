<?php
/**
 * API: Mettre à jour le statut d'un script TikTok
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$id || !$status) {
        throw new Exception('ID et statut requis');
    }
    
    $validStatuses = ['draft', 'ready', 'filmed', 'published'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Statut invalide');
    }
    
    $publishedAt = ($status === 'published') ? ', published_at = NOW()' : '';
    
    $stmt = $pdo->prepare("
        UPDATE tiktok_scripts 
        SET status = ?, updated_at = NOW() $publishedAt
        WHERE id = ?
    ");
    $stmt->execute([$status, $id]);
    
    echo json_encode(['success' => true, 'status' => $status]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}