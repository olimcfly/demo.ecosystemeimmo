<?php
/**
 * 🔧 DIAGNOSTIC TOOL - Module Pages
 * /admin/modules/pages/diagnostic.php
 * 
 * Outil complet pour diagnostiquer les erreurs 500
 */

// Ne pas utiliser de session pour éviter les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

$diagnostics = [
    'config' => [],
    'files' => [],
    'database' => [],
    'permissions' => [],
    'errors' => [],
    'status' => 'unknown'
];

// ============================================
// 1. VÉRIFIER admin-config.php
// ============================================

$adminConfigPath = __DIR__ . '/../../config/admin-config.php';

if (file_exists($adminConfigPath)) {
    $diagnostics['config']['admin_config_exists'] = '✅ EXISTE';
    $diagnostics['config']['admin_config_path'] = $adminConfigPath;
    $diagnostics['config']['admin_config_readable'] = is_readable($adminConfigPath) ? '✅ LISIBLE' : '❌ NON LISIBLE';
    $diagnostics['config']['admin_config_size'] = filesize($adminConfigPath) . ' bytes';
    
    // Essayer d'inclure
    try {
        require_once $adminConfigPath;
        
        if (defined('CONFIG_LOADED')) {
            $diagnostics['config']['included'] = '✅ INCLUS AVEC SUCCÈS';
            $diagnostics['config']['db_host'] = DB_HOST;
            $diagnostics['config']['db_name'] = DB_NAME;
            $diagnostics['config']['db_user'] = DB_USER;
            $diagnostics['config']['log_level'] = LOG_LEVEL;
        } else {
            $diagnostics['config']['included'] = '❌ INCLUS MAIS CONFIG_LOADED NON DÉFINI';
        }
    } catch (Exception $e) {
        $diagnostics['errors'][] = "Erreur inclusion admin-config.php: " . $e->getMessage();
        $diagnostics['config']['included'] = '❌ ERREUR INCLUSION';
    }
} else {
    $diagnostics['config']['admin_config_exists'] = '❌ N\'EXISTE PAS';
    $diagnostics['config']['admin_config_path'] = $adminConfigPath;
    $diagnostics['errors'][] = "ERREUR CRITIQUE: admin-config.php n'existe pas";
}

// ============================================
// 2. VÉRIFIER LES FICHIERS DU MODULE
// ============================================

$moduleFiles = [
    'index.php',
    'create.php',
    'edit.php'
];

$moduleDir = __DIR__;

foreach ($moduleFiles as $file) {
    $filePath = $moduleDir . '/' . $file;
    $diagnostics['files'][$file] = [];
    
    if (file_exists($filePath)) {
        $diagnostics['files'][$file]['exists'] = '✅ EXISTE';
        $diagnostics['files'][$file]['readable'] = is_readable($filePath) ? '✅ LISIBLE' : '❌ NON LISIBLE';
        $diagnostics['files'][$file]['writable'] = is_writable($filePath) ? '✅ MODIFIABLE' : '⚠️ NON MODIFIABLE';
        $diagnostics['files'][$file]['size'] = filesize($filePath) . ' bytes';
        $diagnostics['files'][$file]['permissions'] = decoct(fileperms($filePath) & 0777);
    } else {
        $diagnostics['files'][$file]['exists'] = '❌ N\'EXISTE PAS';
    }
}

// ============================================
// 3. TESTER CONNEXION BD
// ============================================

if (function_exists('getDbConnection')) {
    try {
        $pdo = getDbConnection();
        $diagnostics['database']['connection'] = '✅ CONNECTÉ';
        
        // Vérifier si la table pages existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'pages'");
        if ($stmt->rowCount() > 0) {
            $diagnostics['database']['pages_table'] = '✅ TABLE EXISTE';
            
            // Compter les lignes
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM pages");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC);
            $diagnostics['database']['pages_count'] = $count['total'] . ' pages';
            
            // Vérifier colonnes
            $colStmt = $pdo->query("DESCRIBE pages");
            $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $diagnostics['database']['pages_columns'] = implode(', ', $columns);
        } else {
            $diagnostics['database']['pages_table'] = '❌ TABLE N\'EXISTE PAS';
            $diagnostics['errors'][] = "Table pages n'existe pas en BD";
        }
        
    } catch (PDOException $e) {
        $diagnostics['database']['connection'] = '❌ ERREUR CONNEXION';
        $diagnostics['database']['error'] = $e->getMessage();
        $diagnostics['errors'][] = "Erreur BD: " . $e->getMessage();
    } catch (Exception $e) {
        $diagnostics['database']['connection'] = '❌ ERREUR';
        $diagnostics['database']['error'] = $e->getMessage();
        $diagnostics['errors'][] = "Erreur: " . $e->getMessage();
    }
} else {
    $diagnostics['database']['connection'] = '❌ getDbConnection() NON DISPONIBLE';
    $diagnostics['errors'][] = "getDbConnection() n'est pas défini";
}

// ============================================
// 4. VÉRIFIER PERMISSIONS
// ============================================

$dirPath = __DIR__;
$diagnostics['permissions']['module_dir'] = [
    'path' => $dirPath,
    'exists' => is_dir($dirPath) ? '✅ EXISTE' : '❌ N\'EXISTE PAS',
    'readable' => is_readable($dirPath) ? '✅ LISIBLE' : '❌ NON LISIBLE',
    'writable' => is_writable($dirPath) ? '✅ MODIFIABLE' : '⚠️ NON MODIFIABLE',
    'permissions' => decoct(fileperms($dirPath) & 0777)
];

$logsDir = __DIR__ . '/../../logs';
$diagnostics['permissions']['logs_dir'] = [
    'path' => $logsDir,
    'exists' => is_dir($logsDir) ? '✅ EXISTE' : '❌ N\'EXISTE PAS',
    'writable' => is_writable($logsDir) ? '✅ MODIFIABLE' : '❌ NON MODIFIABLE'
];

// ============================================
// 5. VÉRIFIER LES LOGS
// ============================================

if (is_dir($logsDir)) {
    $errorLog = $logsDir . '/error.log';
    if (file_exists($errorLog)) {
        $diagnostics['errors'][] = "Voir /logs/error.log pour plus de détails";
        $lastErrors = array_slice(file($errorLog), -10);
        $diagnostics['last_errors'] = $lastErrors;
    }
}

// ============================================
// 6. DÉTERMINER LE STATUT
// ============================================

if (empty($diagnostics['errors'])) {
    $diagnostics['status'] = '✅ TOUT EST OK';
} else {
    $diagnostics['status'] = '❌ ERREURS DÉTECTÉES';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Diagnostic Modules Pages</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #e8e8e8;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #2d2d2d;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        h1 {
            color: #00d4ff;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .status {
            font-size: 18px;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .status-ok {
            background: #0d3b1d;
            color: #00ff41;
            border: 1px solid #00ff41;
        }
        
        .status-error {
            background: #3b0d0d;
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .section {
            margin-bottom: 30px;
            background: #353535;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 20px;
        }
        
        .section h2 {
            color: #00d4ff;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        
        .item {
            margin-bottom: 10px;
            padding: 10px;
            background: #2d2d2d;
            border-left: 3px solid #007bff;
            border-radius: 2px;
        }
        
        .item-label {
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .item-value {
            color: #e8e8e8;
            font-size: 14px;
            margin-top: 5px;
            word-break: break-all;
        }
        
        .success {
            color: #00ff41;
        }
        
        .error {
            color: #ff4444;
        }
        
        .warning {
            color: #ffaa00;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        
        .table th {
            background: #2d2d2d;
            color: #00d4ff;
            font-weight: bold;
        }
        
        .table tr:hover {
            background: #353535;
        }
        
        code {
            background: #1e1e1e;
            padding: 4px 8px;
            border-radius: 3px;
            color: #ffaa00;
        }
        
        .errors-list {
            background: #3b0d0d;
            border: 1px solid #ff4444;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .errors-list li {
            margin-bottom: 10px;
            color: #ff4444;
            list-style: none;
        }
        
        .errors-list li:before {
            content: "❌ ";
            margin-right: 10px;
        }
        
        .solutions {
            background: #0d3b1d;
            border: 1px solid #00ff41;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .solutions h3 {
            color: #00ff41;
            margin-bottom: 10px;
        }
        
        .solutions ol {
            margin-left: 20px;
        }
        
        .solutions li {
            margin-bottom: 8px;
            color: #ccc;
        }
        
        .code-block {
            background: #1e1e1e;
            border: 1px solid #444;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 12px;
        }
        
        button {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
        }
        
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h1>🔧 DIAGNOSTIC - Module Pages</h1>
            <div class="status <?= strpos($diagnostics['status'], '✅') !== false ? 'status-ok' : 'status-error' ?>">
                <?= $diagnostics['status'] ?>
            </div>
        </div>
        
        <!-- Configuration -->
        <div class="section">
            <h2>⚙️ Configuration</h2>
            
            <?php if (isset($diagnostics['config']['admin_config_exists'])): ?>
                <table class="table">
                    <tr>
                        <td>admin-config.php</td>
                        <td><span class="<?= strpos($diagnostics['config']['admin_config_exists'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['config']['admin_config_exists'] ?></span></td>
                    </tr>
                    <?php if (isset($diagnostics['config']['admin_config_path'])): ?>
                        <tr>
                            <td>Chemin</td>
                            <td><code><?= escapeHTML($diagnostics['config']['admin_config_path']) ?></code></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($diagnostics['config']['admin_config_readable'])): ?>
                        <tr>
                            <td>Lisible</td>
                            <td><span class="<?= strpos($diagnostics['config']['admin_config_readable'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['config']['admin_config_readable'] ?></span></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($diagnostics['config']['admin_config_size'])): ?>
                        <tr>
                            <td>Taille</td>
                            <td><?= $diagnostics['config']['admin_config_size'] ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($diagnostics['config']['included'])): ?>
                        <tr>
                            <td>Inclusion</td>
                            <td><span class="<?= strpos($diagnostics['config']['included'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['config']['included'] ?></span></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($diagnostics['config']['db_host'])): ?>
                        <tr>
                            <td>BD Host</td>
                            <td><code><?= escapeHTML($diagnostics['config']['db_host']) ?></code></td>
                        </tr>
                        <tr>
                            <td>BD Nom</td>
                            <td><code><?= escapeHTML($diagnostics['config']['db_name']) ?></code></td>
                        </tr>
                        <tr>
                            <td>BD User</td>
                            <td><code><?= escapeHTML($diagnostics['config']['db_user']) ?></code></td>
                        </tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Fichiers Module -->
        <div class="section">
            <h2>📄 Fichiers du Module</h2>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Existe</th>
                        <th>Lisible</th>
                        <th>Taille</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnostics['files'] as $file => $info): ?>
                        <tr>
                            <td><code><?= $file ?></code></td>
                            <td><span class="<?= strpos($info['exists'], '✅') !== false ? 'success' : 'error' ?>"><?= $info['exists'] ?></span></td>
                            <td><span class="<?= isset($info['readable']) && strpos($info['readable'], '✅') !== false ? 'success' : 'error' ?>"><?= $info['readable'] ?? 'N/A' ?></span></td>
                            <td><?= $info['size'] ?? 'N/A' ?></td>
                            <td><code><?= $info['permissions'] ?? 'N/A' ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Base de Données -->
        <div class="section">
            <h2>🗄️ Base de Données</h2>
            
            <table class="table">
                <tr>
                    <td>Connexion</td>
                    <td><span class="<?= strpos($diagnostics['database']['connection'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['database']['connection'] ?></span></td>
                </tr>
                <?php if (isset($diagnostics['database']['pages_table'])): ?>
                    <tr>
                        <td>Table pages</td>
                        <td><span class="<?= strpos($diagnostics['database']['pages_table'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['database']['pages_table'] ?></span></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($diagnostics['database']['pages_count'])): ?>
                    <tr>
                        <td>Nombre de pages</td>
                        <td><?= $diagnostics['database']['pages_count'] ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($diagnostics['database']['pages_columns'])): ?>
                    <tr>
                        <td>Colonnes</td>
                        <td><code><?= $diagnostics['database']['pages_columns'] ?></code></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($diagnostics['database']['error'])): ?>
                    <tr>
                        <td>Erreur</td>
                        <td><span class="error"><?= escapeHTML($diagnostics['database']['error']) ?></span></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Permissions -->
        <div class="section">
            <h2>🔐 Permissions</h2>
            
            <div class="item">
                <div class="item-label">Dossier Module</div>
                <div class="item-value">
                    <div><?= $diagnostics['permissions']['module_dir']['path'] ?></div>
                    <div><span class="<?= strpos($diagnostics['permissions']['module_dir']['exists'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['permissions']['module_dir']['exists'] ?></span></div>
                    <div><span class="<?= strpos($diagnostics['permissions']['module_dir']['readable'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['permissions']['module_dir']['readable'] ?></span></div>
                    <div>Permissions: <code><?= $diagnostics['permissions']['module_dir']['permissions'] ?></code></div>
                </div>
            </div>
            
            <div class="item">
                <div class="item-label">Dossier Logs</div>
                <div class="item-value">
                    <div><?= $diagnostics['permissions']['logs_dir']['path'] ?></div>
                    <div><span class="<?= strpos($diagnostics['permissions']['logs_dir']['exists'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['permissions']['logs_dir']['exists'] ?></span></div>
                    <div><span class="<?= strpos($diagnostics['permissions']['logs_dir']['writable'], '✅') !== false ? 'success' : 'error' ?>"><?= $diagnostics['permissions']['logs_dir']['writable'] ?></span></div>
                </div>
            </div>
        </div>
        
        <!-- Erreurs -->
        <?php if (!empty($diagnostics['errors'])): ?>
            <div class="section">
                <h2>❌ Erreurs Détectées</h2>
                
                <div class="errors-list">
                    <?php foreach ($diagnostics['errors'] as $error): ?>
                        <li><?= escapeHTML($error) ?></li>
                    <?php endforeach; ?>
                </div>
                
                <!-- Solutions -->
                <div class="solutions">
                    <h3>💡 Solutions Recommandées</h3>
                    
                    <?php if (strpos(implode('', $diagnostics['errors']), 'admin-config.php') !== false): ?>
                        <h4>1. Installer admin-config.php</h4>
                        <ol>
                            <li>Copier admin-config_PRODUCTION.php</li>
                            <li>Vers: /admin/config/admin-config.php</li>
                            <li>Permissions: chmod 644</li>
                            <li>Recharger cette page</li>
                        </ol>
                    <?php endif; ?>
                    
                    <?php if (strpos(implode('', $diagnostics['errors']), 'table pages') !== false): ?>
                        <h4>2. Créer la table pages</h4>
                        <ol>
                            <li>Ouvrir phpMyAdmin</li>
                            <li>Aller sur BD: mahe6420_cms-site-ed-bordeaux</li>
                            <li>Exécuter admin_tables.sql</li>
                            <li>Ou créer manuellement la table pages</li>
                        </ol>
                    <?php endif; ?>
                    
                    <?php if (strpos(implode('', $diagnostics['errors']), 'non modifiable') !== false): ?>
                        <h4>3. Corriger les permissions</h4>
                        <div class="code-block">
chmod 755 /admin/modules/pages/
chmod 644 /admin/modules/pages/*.php
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Dernières erreurs du log -->
        <?php if (isset($diagnostics['last_errors'])): ?>
            <div class="section">
                <h2>📋 Dernières Erreurs (/logs/error.log)</h2>
                <div class="code-block">
                    <?php foreach ($diagnostics['last_errors'] as $line): ?>
                        <?= escapeHTML($line) ?><br>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="section">
            <h2>🔄 Actions</h2>
            <button onclick="location.reload()">🔄 Recharger le Diagnostic</button>
            <button onclick="if(confirm('Vider les logs ?')) fetch('?action=clear_logs').then(() => location.reload())">🗑️ Vider les Logs</button>
        </div>
    </div>
    
    <script>
        // Si on doit vider les logs
        if (new URLSearchParams(location.search).get('action') === 'clear_logs') {
            // Le serveur doit implémenter cette action
        }
    </script>
</body>
</html>

<?php
// Fonction helper si elle n'existe pas
if (!function_exists('escapeHTML')) {
    function escapeHTML($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
?>