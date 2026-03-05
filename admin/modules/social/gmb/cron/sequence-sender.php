<?php
/**
 * Sequence Sender - CRON Job
 * Module : admin/modules/gmb/cron/sequence-sender.php
 * 
 * Exécution : toutes les 15 min via cPanel CRON
 * Commande : /usr/local/bin/php /home/mahe6420/public_html/admin/modules/gmb/cron/sequence-sender.php
 * 
 * Processus :
 * 1. Récupérer les emails en queue (status = 'queued') dont le délai est atteint
 * 2. Remplacer les variables dans le template
 * 3. Envoyer via PHP mail() ou SMTP
 * 4. Mettre à jour le statut (sent/failed)
 * 5. Planifier l'étape suivante si elle existe
 * 6. Respecter les limites d'envoi quotidiennes
 * 
 * DB utilisée :
 * - gmb_email_sends : status, sent_at, opened_at, replied_at, bounced_at
 * - gmb_sequence_steps : sequence_id, step_order, subject, body_html, delay_days, delay_hours
 * - gmb_email_sequences : is_active
 * - gmb_contacts : email, contact_name, business_name...
 * - gmb_scraper_settings : smtp_*, daily_email_limit
 */

// ===== INIT =====
$startTime = microtime(true);
$logFile = __DIR__ . '/../../../../logs/sequence-sender.log';

function logMsg(string $msg, string $logFile): void {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$msg}\n", FILE_APPEND | LOCK_EX);
}

logMsg("=== SEQUENCE SENDER START ===", $logFile);

// Charger l'initialisation
$initPath = __DIR__ . '/../../../includes/init.php';
if (!file_exists($initPath)) {
    // Fallback : chercher depuis la racine
    $initPath = dirname(__DIR__, 4) . '/includes/init.php';
}

if (!file_exists($initPath)) {
    logMsg("ERREUR: init.php introuvable", $logFile);
    exit(1);
}

// Mode CLI : pas de session
if (php_sapi_name() === 'cli') {
    $_SESSION = [];
    $_SESSION['admin_logged_in'] = true; // Bypass auth pour CRON
}

require_once $initPath;
require_once __DIR__ . '/../SequenceController.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    logMsg("ERREUR DB: " . $e->getMessage(), $logFile);
    exit(1);
}

// ===== CONFIGURATION =====

// Charger les settings SMTP
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM gmb_scraper_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    logMsg("ERREUR settings: " . $e->getMessage(), $logFile);
}

$dailyLimit  = (int)($settings['daily_email_limit'] ?? 50);
$smtpHost    = $settings['smtp_host'] ?? '';
$smtpPort    = (int)($settings['smtp_port'] ?? 587);
$smtpUser    = $settings['smtp_username'] ?? '';
$smtpPass    = $settings['smtp_password'] ?? '';
$fromEmail   = $settings['smtp_from_email'] ?? 'eduardo@ecosystemeimmo.fr';
$fromName    = $settings['smtp_from_name'] ?? 'Eduardo De Sul';
$batchSize   = min(20, $dailyLimit); // Max par exécution

// Vérifier les envois du jour
$todaySent = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM gmb_email_sends WHERE DATE(sent_at) = CURDATE() AND status IN ('sent','delivered','opened','clicked','replied')");
    $todaySent = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    logMsg("ERREUR count today: " . $e->getMessage(), $logFile);
}

$remaining = $dailyLimit - $todaySent;
if ($remaining <= 0) {
    logMsg("Limite quotidienne atteinte ({$dailyLimit}/{$dailyLimit}). Arrêt.", $logFile);
    exit(0);
}

$batchSize = min($batchSize, $remaining);
logMsg("Envois aujourd'hui: {$todaySent}/{$dailyLimit} | Batch: {$batchSize}", $logFile);

// ===== RÉCUPÉRER LES EMAILS EN QUEUE =====

$sequenceController = new SequenceController($db);

try {
    $stmt = $db->prepare("
        SELECT 
            es.id as send_id,
            es.contact_id,
            es.sequence_id,
            es.step_id,
            es.list_id,
            ss.subject,
            ss.body_html,
            ss.step_order,
            ss.delay_days,
            ss.delay_hours,
            c.email,
            c.contact_name,
            c.business_name,
            c.business_category,
            c.phone,
            c.city,
            c.postal_code,
            c.website,
            c.rating,
            c.reviews_count,
            c.email_status
        FROM gmb_email_sends es
        INNER JOIN gmb_sequence_steps ss ON es.step_id = ss.id
        INNER JOIN gmb_contacts c ON es.contact_id = c.id
        INNER JOIN gmb_email_sequences seq ON es.sequence_id = seq.id
        WHERE es.status = 'queued'
          AND seq.is_active = 1
          AND c.email IS NOT NULL 
          AND c.email != ''
          AND c.email_status != 'invalid'
          AND c.prospect_status NOT IN ('blackliste', 'pas_interesse')
          AND (
              es.scheduled_at IS NULL 
              OR es.scheduled_at <= NOW()
          )
        ORDER BY es.created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$batchSize]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logMsg("ERREUR queue: " . $e->getMessage(), $logFile);
    exit(1);
}

if (empty($queue)) {
    logMsg("Aucun email en queue. Arrêt.", $logFile);
    exit(0);
}

logMsg(count($queue) . " email(s) à envoyer", $logFile);

// ===== ENVOI =====

$sent = 0;
$failed = 0;

foreach ($queue as $item) {
    $sendId = $item['send_id'];
    $email = $item['email'];
    
    // Préparer les données du contact pour le template
    $contactData = [
        'business_name'     => $item['business_name'],
        'contact_name'      => $item['contact_name'],
        'email'             => $item['email'],
        'phone'             => $item['phone'],
        'city'              => $item['city'],
        'rating'            => $item['rating'],
        'reviews_count'     => $item['reviews_count'],
        'website'           => $item['website'],
        'business_category' => $item['business_category'],
    ];
    
    // Rendre le template
    $subject = $sequenceController->renderTemplate($item['subject'], $contactData, $settings);
    $bodyHtml = $sequenceController->renderTemplate($item['body_html'], $contactData, $settings);
    
    // Ajouter le pixel de tracking
    $trackingPixel = '<img src="https://eduardo-de-sul-bordeaux.fr/admin/modules/gmb/api/gmb-tracking.php?action=open&send_id=' . $sendId . '" width="1" height="1" style="display:none" />';
    $bodyHtml .= $trackingPixel;
    
    // Ajouter le lien de désinscription
    $unsubscribeLink = 'https://eduardo-de-sul-bordeaux.fr/admin/modules/gmb/api/gmb-tracking.php?action=unsubscribe&send_id=' . $sendId;
    $bodyHtml .= '<p style="font-size:11px;color:#999;margin-top:30px;text-align:center;">';
    $bodyHtml .= '<a href="' . $unsubscribeLink . '" style="color:#999;">Se désinscrire</a></p>';
    
    // Wraper dans un HTML complet
    $fullHtml = '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
    $fullHtml .= '<body style="font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6;max-width:600px;margin:0 auto;padding:20px;">';
    $fullHtml .= $bodyHtml;
    $fullHtml .= '</body></html>';
    
    // Envoyer
    $sendResult = sendEmail($email, $subject, $fullHtml, $fromEmail, $fromName, $settings);
    
    if ($sendResult['success']) {
        // Mettre à jour le statut
        $updateStmt = $db->prepare("
            UPDATE gmb_email_sends 
            SET status = 'sent', sent_at = NOW(), subject_rendered = ?, body_rendered = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$subject, $fullHtml, $sendId]);
        
        $sent++;
        logMsg("✓ Envoyé #{$sendId} → {$email} | Sujet: {$subject}", $logFile);
        
        // Planifier l'étape suivante
        scheduleNextStep($db, $item);
        
    } else {
        $updateStmt = $db->prepare("
            UPDATE gmb_email_sends 
            SET status = 'failed', error_message = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$sendResult['error'], $sendId]);
        
        $failed++;
        logMsg("✕ Échec #{$sendId} → {$email} | Erreur: {$sendResult['error']}", $logFile);
    }
    
    // Pause entre les envois (2-5 secondes aléatoire)
    usleep(rand(2000000, 5000000));
}

// ===== RÉSULTAT =====
$elapsed = round(microtime(true) - $startTime, 2);
logMsg("=== TERMINÉ: {$sent} envoyé(s), {$failed} échec(s) en {$elapsed}s ===", $logFile);


// ================================================================
// FONCTIONS
// ================================================================

/**
 * Planifier l'étape suivante de la séquence
 */
function scheduleNextStep(PDO $db, array $currentItem): void
{
    // Trouver l'étape suivante
    $stmt = $db->prepare("
        SELECT * FROM gmb_sequence_steps 
        WHERE sequence_id = ? AND step_order = ?
        LIMIT 1
    ");
    $stmt->execute([
        $currentItem['sequence_id'],
        $currentItem['step_order'] + 1
    ]);
    $nextStep = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nextStep) return; // Pas d'étape suivante
    
    // Calculer la date planifiée
    $delaySeconds = ($nextStep['delay_days'] * 86400) + ($nextStep['delay_hours'] * 3600);
    $scheduledAt = date('Y-m-d H:i:s', time() + $delaySeconds);
    
    // Vérifier qu'on n'a pas déjà planifié cette étape pour ce contact
    $checkStmt = $db->prepare("
        SELECT COUNT(*) FROM gmb_email_sends 
        WHERE contact_id = ? AND sequence_id = ? AND step_id = ?
    ");
    $checkStmt->execute([$currentItem['contact_id'], $currentItem['sequence_id'], $nextStep['id']]);
    
    if ((int)$checkStmt->fetchColumn() > 0) return; // Déjà planifié
    
    $insertStmt = $db->prepare("
        INSERT INTO gmb_email_sends (contact_id, sequence_id, step_id, list_id, status, scheduled_at) 
        VALUES (?, ?, ?, ?, 'queued', ?)
    ");
    $insertStmt->execute([
        $currentItem['contact_id'],
        $currentItem['sequence_id'],
        $nextStep['id'],
        $currentItem['list_id'],
        $scheduledAt,
    ]);
}

/**
 * Envoyer un email via SMTP ou mail()
 */
function sendEmail(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName, array $settings): array
{
    $smtpHost = $settings['smtp_host'] ?? '';
    
    // Si SMTP configuré, utiliser fsockopen SMTP
    if (!empty($smtpHost) && !empty($settings['smtp_username']) && !empty($settings['smtp_password'])) {
        return sendViaSMTP($to, $subject, $htmlBody, $fromEmail, $fromName, $settings);
    }
    
    // Fallback : mail() natif PHP
    return sendViaMail($to, $subject, $htmlBody, $fromEmail, $fromName);
}

/**
 * Envoi via mail() natif PHP
 */
function sendViaMail(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName): array
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=utf-8',
        "From: {$fromName} <{$fromEmail}>",
        "Reply-To: {$fromEmail}",
        'X-Mailer: EcosystemeImmo-GMB/1.0',
    ];
    
    $headerStr = implode("\r\n", $headers);
    $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    $result = @mail($to, $subjectEncoded, $htmlBody, $headerStr);
    
    if ($result) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'mail() a retourné false'];
    }
}

/**
 * Envoi via SMTP direct (fsockopen)
 */
function sendViaSMTP(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName, array $settings): array
{
    $host = $settings['smtp_host'];
    $port = (int)($settings['smtp_port'] ?? 587);
    $user = $settings['smtp_username'];
    $pass = $settings['smtp_password'];
    
    try {
        $prefix = ($port === 465) ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);
        
        if (!$socket) {
            return ['success' => false, 'error' => "Connexion SMTP échouée: {$errstr}"];
        }
        
        stream_set_timeout($socket, 15);
        
        $readLine = function() use ($socket) {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return trim($response);
        };
        
        $sendCmd = function($cmd) use ($socket, $readLine) {
            fwrite($socket, $cmd . "\r\n");
            return $readLine();
        };
        
        // Lire banner
        $readLine();
        
        // EHLO
        $response = $sendCmd("EHLO ecosystemeimmo.fr");
        
        // STARTTLS si port 587
        if ($port === 587) {
            $sendCmd("STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            $sendCmd("EHLO ecosystemeimmo.fr");
        }
        
        // AUTH LOGIN
        $sendCmd("AUTH LOGIN");
        $sendCmd(base64_encode($user));
        $response = $sendCmd(base64_encode($pass));
        
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'error' => 'Auth SMTP échouée: ' . $response];
        }
        
        // MAIL FROM
        $sendCmd("MAIL FROM:<{$fromEmail}>");
        
        // RCPT TO
        $response = $sendCmd("RCPT TO:<{$to}>");
        if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'error' => 'Destinataire rejeté: ' . $response];
        }
        
        // DATA
        $sendCmd("DATA");
        
        // Construire le message
        $boundary = md5(uniqid(time()));
        $message = "From: {$fromName} <{$fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=utf-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "X-Mailer: EcosystemeImmo-GMB/1.0\r\n";
        $message .= "\r\n";
        $message .= chunk_split(base64_encode($htmlBody));
        $message .= "\r\n.\r\n";
        
        fwrite($socket, $message);
        $response = $readLine();
        
        $sendCmd("QUIT");
        fclose($socket);
        
        if (substr($response, 0, 3) === '250') {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Envoi rejeté: ' . $response];
        
    } catch (Exception $e) {
        if (isset($socket) && is_resource($socket)) fclose($socket);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}