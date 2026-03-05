<?php
/**
 * API Scoring Leads
 * /admin/modules/scoring/api.php
 */

session_start();

// Vérifier la session admin
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Charger la config
require_once __DIR__ . '/../../../config/config.php';

// Headers JSON
header('Content-Type: application/json');

// Connexion BDD
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur BDD: ' . $e->getMessage()]);
    exit;
}

// Récupérer l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    // ==================== AJOUTER UNE RÈGLE ====================
    case 'add_rule':
        try {
            $stmt = $pdo->prepare("
                INSERT INTO scoring_rules (name, category, field_name, operator, field_value, points, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                trim($_POST['name'] ?? ''),
                $_POST['category'] ?? 'engagement',
                $_POST['field_name'] ?? 'email',
                $_POST['operator'] ?? 'not_empty',
                !empty($_POST['field_value']) ? trim($_POST['field_value']) : null,
                (int)($_POST['points'] ?? 10)
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur insertion']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== MODIFIER UNE RÈGLE ====================
    case 'update_rule':
        try {
            $ruleId = (int)$_POST['rule_id'];
            
            $stmt = $pdo->prepare("
                UPDATE scoring_rules SET 
                    name = ?, 
                    category = ?, 
                    field_name = ?, 
                    operator = ?, 
                    field_value = ?, 
                    points = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                trim($_POST['name'] ?? ''),
                $_POST['category'] ?? 'engagement',
                $_POST['field_name'] ?? 'email',
                $_POST['operator'] ?? 'not_empty',
                !empty($_POST['field_value']) ? trim($_POST['field_value']) : null,
                (int)($_POST['points'] ?? 10),
                $ruleId
            ]);
            
            echo json_encode(['success' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== ACTIVER/DÉSACTIVER UNE RÈGLE ====================
    case 'toggle_rule':
        try {
            $ruleId = (int)$_POST['rule_id'];
            $isActive = (int)$_POST['is_active'];
            
            $stmt = $pdo->prepare("UPDATE scoring_rules SET is_active = ? WHERE id = ?");
            $result = $stmt->execute([$isActive, $ruleId]);
            
            echo json_encode(['success' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== SUPPRIMER UNE RÈGLE ====================
    case 'delete_rule':
        try {
            $ruleId = (int)$_POST['rule_id'];
            
            $stmt = $pdo->prepare("DELETE FROM scoring_rules WHERE id = ?");
            $result = $stmt->execute([$ruleId]);
            
            echo json_encode(['success' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== OBTENIR TOUTES LES RÈGLES ====================
    case 'get_rules':
        try {
            $rules = $pdo->query("SELECT * FROM scoring_rules ORDER BY category, points DESC")->fetchAll();
            echo json_encode(['success' => true, 'rules' => $rules]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== OBTENIR UNE RÈGLE ====================
    case 'get_rule':
        try {
            $ruleId = (int)($_GET['rule_id'] ?? $_POST['rule_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM scoring_rules WHERE id = ?");
            $stmt->execute([$ruleId]);
            $rule = $stmt->fetch();
            
            if ($rule) {
                echo json_encode(['success' => true, 'rule' => $rule]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Règle non trouvée']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== RECALCULER TOUS LES SCORES ====================
    case 'recalculate_all':
        try {
            // Récupérer les règles actives
            $rules = $pdo->query("SELECT * FROM scoring_rules WHERE is_active = 1")->fetchAll();
            
            // Récupérer tous les leads
            $leads = $pdo->query("SELECT * FROM leads")->fetchAll();
            
            $updated = 0;
            
            foreach ($leads as $lead) {
                $score = 0;
                $createdDays = floor((time() - strtotime($lead['created_at'])) / 86400);
                
                foreach ($rules as $rule) {
                    $fieldValue = null;
                    
                    if ($rule['field_name'] === 'created_days') {
                        $fieldValue = $createdDays;
                    } else {
                        $fieldValue = $lead[$rule['field_name']] ?? null;
                    }
                    
                    $matched = false;
                    
                    switch ($rule['operator']) {
                        case 'equals':
                            $matched = ($fieldValue == $rule['field_value']);
                            break;
                        case 'not_equals':
                            $matched = ($fieldValue != $rule['field_value']);
                            break;
                        case 'not_empty':
                            $matched = !empty($fieldValue);
                            break;
                        case 'empty':
                            $matched = empty($fieldValue);
                            break;
                        case 'greater_than':
                            $matched = (floatval($fieldValue) > floatval($rule['field_value']));
                            break;
                        case 'less_than':
                            $matched = (floatval($fieldValue) < floatval($rule['field_value']));
                            break;
                        case 'contains':
                            $matched = (stripos($fieldValue, $rule['field_value']) !== false);
                            break;
                    }
                    
                    if ($matched) {
                        $score += $rule['points'];
                    }
                }
                
                // Déterminer la température
                $temperature = 'cold';
                if ($score >= 70) $temperature = 'hot';
                elseif ($score >= 35) $temperature = 'warm';
                
                // Mettre à jour
                $stmt = $pdo->prepare("UPDATE leads SET score = ?, temperature = ?, score_updated_at = NOW() WHERE id = ?");
                $stmt->execute([$score, $temperature, $lead['id']]);
                $updated++;
            }
            
            echo json_encode(['success' => true, 'updated' => $updated]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== CALCULER LE SCORE D'UN LEAD ====================
    case 'calculate_lead_score':
        try {
            $leadId = (int)($_GET['lead_id'] ?? $_POST['lead_id'] ?? 0);
            
            // Récupérer le lead
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            
            if (!$lead) {
                echo json_encode(['success' => false, 'error' => 'Lead non trouvé']);
                break;
            }
            
            // Récupérer les règles actives
            $rules = $pdo->query("SELECT * FROM scoring_rules WHERE is_active = 1")->fetchAll();
            
            $score = 0;
            $matchedRules = [];
            $createdDays = floor((time() - strtotime($lead['created_at'])) / 86400);
            
            foreach ($rules as $rule) {
                $fieldValue = null;
                
                if ($rule['field_name'] === 'created_days') {
                    $fieldValue = $createdDays;
                } else {
                    $fieldValue = $lead[$rule['field_name']] ?? null;
                }
                
                $matched = false;
                
                switch ($rule['operator']) {
                    case 'equals':
                        $matched = ($fieldValue == $rule['field_value']);
                        break;
                    case 'not_equals':
                        $matched = ($fieldValue != $rule['field_value']);
                        break;
                    case 'not_empty':
                        $matched = !empty($fieldValue);
                        break;
                    case 'empty':
                        $matched = empty($fieldValue);
                        break;
                    case 'greater_than':
                        $matched = (floatval($fieldValue) > floatval($rule['field_value']));
                        break;
                    case 'less_than':
                        $matched = (floatval($fieldValue) < floatval($rule['field_value']));
                        break;
                    case 'contains':
                        $matched = (stripos($fieldValue, $rule['field_value']) !== false);
                        break;
                }
                
                if ($matched) {
                    $score += $rule['points'];
                    $matchedRules[] = $rule;
                }
            }
            
            // Déterminer la température
            $temperature = 'cold';
            if ($score >= 70) $temperature = 'hot';
            elseif ($score >= 35) $temperature = 'warm';
            
            // Mettre à jour le lead
            $stmt = $pdo->prepare("UPDATE leads SET score = ?, temperature = ?, score_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$score, $temperature, $leadId]);
            
            echo json_encode([
                'success' => true, 
                'score' => $score, 
                'temperature' => $temperature,
                'matched_rules' => $matchedRules
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== STATISTIQUES ====================
    case 'get_stats':
        try {
            $stats = [
                'total' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
                'hot' => $pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'hot'")->fetchColumn(),
                'warm' => $pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'warm'")->fetchColumn(),
                'cold' => $pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'cold'")->fetchColumn(),
                'avg_score' => $pdo->query("SELECT AVG(score) FROM leads")->fetchColumn() ?: 0,
                'rules_count' => $pdo->query("SELECT COUNT(*) FROM scoring_rules WHERE is_active = 1")->fetchColumn()
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== ACTION NON RECONNUE ====================
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue: ' . $action]);
        break;
}