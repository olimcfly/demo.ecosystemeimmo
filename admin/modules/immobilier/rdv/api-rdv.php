<?php
/**
 * API Calendrier & RDV
 * /admin/modules/rdv/api.php
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
    
    // ==================== AJOUTER UN RDV ====================
    case 'add_rdv':
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $startTime = $_POST['start_time'] ?? '09:00';
            $endTime = $_POST['end_time'] ?? '10:00';
            
            $startDatetime = $date . ' ' . $startTime . ':00';
            $endDatetime = $date . ' ' . $endTime . ':00';
            
            $stmt = $pdo->prepare("
                INSERT INTO appointments (
                    title, type, start_datetime, end_datetime, 
                    location, lead_id, notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?)
            ");
            
            $result = $stmt->execute([
                trim($_POST['title'] ?? ''),
                $_POST['type'] ?? 'visite',
                $startDatetime,
                $endDatetime,
                trim($_POST['location'] ?? '') ?: null,
                !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null,
                trim($_POST['notes'] ?? '') ?: null,
                $_SESSION['admin_id'] ?? null
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'id' => $pdo->lastInsertId(),
                    'message' => 'Rendez-vous créé avec succès'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== MODIFIER UN RDV ====================
    case 'update_rdv':
        try {
            $rdvId = (int)$_POST['rdv_id'];
            
            if (!$rdvId) {
                echo json_encode(['success' => false, 'error' => 'ID RDV manquant']);
                break;
            }
            
            $date = $_POST['date'] ?? date('Y-m-d');
            $startTime = $_POST['start_time'] ?? '09:00';
            $endTime = $_POST['end_time'] ?? '10:00';
            
            $startDatetime = $date . ' ' . $startTime . ':00';
            $endDatetime = $date . ' ' . $endTime . ':00';
            
            $stmt = $pdo->prepare("
                UPDATE appointments SET 
                    title = ?,
                    type = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    location = ?,
                    lead_id = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                trim($_POST['title'] ?? ''),
                $_POST['type'] ?? 'visite',
                $startDatetime,
                $endDatetime,
                trim($_POST['location'] ?? '') ?: null,
                !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null,
                trim($_POST['notes'] ?? '') ?: null,
                $rdvId
            ]);
            
            echo json_encode(['success' => $result, 'message' => 'Rendez-vous modifié']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== SUPPRIMER UN RDV ====================
    case 'delete_rdv':
        try {
            $rdvId = (int)$_POST['rdv_id'];
            
            if (!$rdvId) {
                echo json_encode(['success' => false, 'error' => 'ID RDV manquant']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $result = $stmt->execute([$rdvId]);
            
            echo json_encode(['success' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== OBTENIR UN RDV ====================
    case 'get_rdv':
        try {
            $rdvId = (int)($_GET['rdv_id'] ?? $_POST['rdv_id'] ?? 0);
            
            if (!$rdvId) {
                echo json_encode(['success' => false, 'error' => 'ID RDV manquant']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       l.firstname as lead_firstname, l.lastname as lead_lastname, 
                       l.phone as lead_phone, l.email as lead_email
                FROM appointments a
                LEFT JOIN leads l ON a.lead_id = l.id
                WHERE a.id = ?
            ");
            $stmt->execute([$rdvId]);
            $rdv = $stmt->fetch();
            
            if ($rdv) {
                echo json_encode(['success' => true, 'rdv' => $rdv]);
            } else {
                echo json_encode(['success' => false, 'error' => 'RDV non trouvé']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== METTRE À JOUR LE STATUT ====================
    case 'update_status':
        try {
            $rdvId = (int)$_POST['rdv_id'];
            $status = $_POST['status'] ?? 'scheduled';
            
            $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Statut invalide']);
                break;
            }
            
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $rdvId]);
            
            echo json_encode(['success' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== LISTE DES RDV ====================
    case 'get_appointments':
        try {
            $startDate = $_GET['start'] ?? date('Y-m-01');
            $endDate = $_GET['end'] ?? date('Y-m-t');
            
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       l.firstname as lead_firstname, l.lastname as lead_lastname
                FROM appointments a
                LEFT JOIN leads l ON a.lead_id = l.id
                WHERE DATE(a.start_datetime) BETWEEN ? AND ?
                ORDER BY a.start_datetime ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            $appointments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'appointments' => $appointments]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== RDV À VENIR ====================
    case 'get_upcoming':
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       l.firstname as lead_firstname, l.lastname as lead_lastname
                FROM appointments a
                LEFT JOIN leads l ON a.lead_id = l.id
                WHERE a.start_datetime >= NOW() 
                  AND a.status NOT IN ('cancelled', 'completed')
                ORDER BY a.start_datetime ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $appointments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'appointments' => $appointments]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== RDV DU JOUR ====================
    case 'get_today':
        try {
            $today = date('Y-m-d');
            
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       l.firstname as lead_firstname, l.lastname as lead_lastname,
                       l.phone as lead_phone
                FROM appointments a
                LEFT JOIN leads l ON a.lead_id = l.id
                WHERE DATE(a.start_datetime) = ?
                ORDER BY a.start_datetime ASC
            ");
            $stmt->execute([$today]);
            $appointments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'appointments' => $appointments]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== STATISTIQUES ====================
    case 'get_stats':
        try {
            $month = $_GET['month'] ?? date('Y-m');
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $stats = [
                'total' => 0,
                'scheduled' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'no_show' => 0,
                'by_type' => []
            ];
            
            // Total par statut
            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count
                FROM appointments
                WHERE DATE(start_datetime) BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$startDate, $endDate]);
            
            while ($row = $stmt->fetch()) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            // Total par type
            $stmt = $pdo->prepare("
                SELECT type, COUNT(*) as count
                FROM appointments
                WHERE DATE(start_datetime) BETWEEN ? AND ?
                GROUP BY type
            ");
            $stmt->execute([$startDate, $endDate]);
            
            while ($row = $stmt->fetch()) {
                $stats['by_type'][$row['type']] = (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== RDV D'UN LEAD ====================
    case 'get_lead_appointments':
        try {
            $leadId = (int)($_GET['lead_id'] ?? 0);
            
            if (!$leadId) {
                echo json_encode(['success' => false, 'error' => 'ID lead manquant']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM appointments
                WHERE lead_id = ?
                ORDER BY start_datetime DESC
            ");
            $stmt->execute([$leadId]);
            $appointments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'appointments' => $appointments]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== DUPLIQUER UN RDV ====================
    case 'duplicate_rdv':
        try {
            $rdvId = (int)$_POST['rdv_id'];
            $newDate = $_POST['new_date'] ?? null;
            
            if (!$rdvId) {
                echo json_encode(['success' => false, 'error' => 'ID RDV manquant']);
                break;
            }
            
            // Récupérer le RDV original
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmt->execute([$rdvId]);
            $original = $stmt->fetch();
            
            if (!$original) {
                echo json_encode(['success' => false, 'error' => 'RDV non trouvé']);
                break;
            }
            
            // Calculer les nouvelles dates
            if ($newDate) {
                $startTime = substr($original['start_datetime'], 11);
                $endTime = substr($original['end_datetime'], 11);
                $newStartDatetime = $newDate . ' ' . $startTime;
                $newEndDatetime = $newDate . ' ' . $endTime;
            } else {
                // Dupliquer à la même heure le lendemain
                $newStartDatetime = date('Y-m-d H:i:s', strtotime($original['start_datetime'] . ' +1 day'));
                $newEndDatetime = date('Y-m-d H:i:s', strtotime($original['end_datetime'] . ' +1 day'));
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO appointments (
                    title, type, start_datetime, end_datetime, 
                    location, lead_id, notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?)
            ");
            
            $result = $stmt->execute([
                $original['title'] . ' (copie)',
                $original['type'],
                $newStartDatetime,
                $newEndDatetime,
                $original['location'],
                $original['lead_id'],
                $original['notes'],
                $_SESSION['admin_id'] ?? null
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la duplication']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== ACTION NON RECONNUE ====================
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue: ' . $action]);
        break;
}