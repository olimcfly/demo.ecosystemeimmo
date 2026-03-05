<?php
/**
 * API: Sauvegarder un script TikTok
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
    
    $id = $_POST['id'] ?? null;
    $personaId = $_POST['persona_id'] ?? null;
    $consciousnessLevel = $_POST['consciousness_level'] ?? null;
    $videoType = $_POST['video_type'] ?? 'hook';
    $subject = trim($_POST['subject'] ?? '');
    $hook = trim($_POST['hook'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $cta = trim($_POST['cta'] ?? '');
    $filmingNotes = trim($_POST['filming_notes'] ?? '');
    $creationMethod = $_POST['creation_method'] ?? 'self';
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($subject) || empty($hook) || empty($body) || empty($cta)) {
        throw new Exception('Tous les champs du script sont obligatoires');
    }
    
    if ($id) {
        // Mise à jour
        $stmt = $pdo->prepare("
            UPDATE tiktok_scripts SET 
                persona_id = ?,
                consciousness_level = ?,
                video_type = ?,
                subject = ?,
                hook = ?,
                body = ?,
                cta = ?,
                filming_notes = ?,
                creation_method = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $personaId ?: null,
            $consciousnessLevel,
            $videoType,
            $subject,
            $hook,
            $body,
            $cta,
            $filmingNotes,
            $creationMethod,
            $status,
            $id
        ]);
        
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Script mis à jour']);
    } else {
        // Création
        $stmt = $pdo->prepare("
            INSERT INTO tiktok_scripts (
                persona_id, consciousness_level, video_type, subject, 
                hook, body, cta, filming_notes, creation_method, status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $personaId ?: null,
            $consciousnessLevel,
            $videoType,
            $subject,
            $hook,
            $body,
            $cta,
            $filmingNotes,
            $creationMethod,
            $status
        ]);
        
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Script créé']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}