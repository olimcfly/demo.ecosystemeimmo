<?php
// modules/gmb/cron/gmb-email-processor.php
//
// CRON toutes les 15 minutes :
// crontab: */15 * * * * /usr/bin/php /home/mahe6420/public_html/admin/modules/gmb/cron/gmb-email-processor.php >> /home/mahe6420/logs/gmb-cron.log 2>&1
//
// CRON quotidien reset compteur :
// crontab: 0 0 * * * /usr/bin/php /home/mahe6420/public_html/admin/modules/gmb/cron/gmb-email-processor.php --reset-daily >> /home/mahe6420/logs/gmb-cron.log 2>&1

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI uniquement');
}

$rootPath = realpath(__DIR__ . '/../../../../');

require_once $rootPath . '/config/database.php';
require_once $rootPath . '/includes/Database.php';
require_once __DIR__ . '/../GmbEmailController.php';

echo "[" . date('Y-m-d H:i:s') . "] GMB Email Processor demarre\n";

$db = Database::getInstance()->getConnection();
$emailCtrl = new GmbEmailController($db);

if (in_array('--reset-daily', $argv ?? [])) {
    $db->exec("UPDATE gmb_settings SET setting_value = '0' WHERE setting_key = 'emails_sent_today'");
    echo "Compteur quotidien remis a zero\n";
    exit(0);
}

$scheduled = $emailCtrl->scheduleNextSteps();
echo "Etapes programmees: {$scheduled}\n";

$result = $emailCtrl->processQueue(10);
echo "Emails envoyes: " . ($result['sent'] ?? 0) . "\n";
echo "Emails echoues: " . ($result['failed'] ?? 0) . "\n";
echo "Restants aujourd'hui: " . ($result['remaining_today'] ?? 'N/A') . "\n";

echo "[" . date('Y-m-d H:i:s') . "] Termine\n\n";
