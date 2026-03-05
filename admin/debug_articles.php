<?php
/**
 * DEBUG ARTICLES — Script diagnostic
 * Uploader à la racine de /admin/ et accéder via navigateur
 * URL : https://votre-site.com/admin/debug_articles.php
 * ⚠️ SUPPRIMER après diagnostic !
 */

// Connexion directe DB (bypass init.php)
$host   = 'localhost';
$dbname = ''; // ← REMPLIR
$user   = ''; // ← REMPLIR
$pass   = ''; // ← REMPLIR

// Tentative via config existante
if (empty($dbname)) {
    $configPaths = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/config.php',
    ];
    foreach ($configPaths as $p) {
        if (file_exists($p)) {
            @include_once $p;
            // Récupérer les constantes ou variables définies
            if (defined('DB_NAME'))     $dbname = DB_NAME;
            if (defined('DB_USER'))     $user   = DB_USER;
            if (defined('DB_PASS'))     $pass   = DB_PASS;
            if (defined('DB_HOST'))     $host   = DB_HOST;
            if (isset($db_name))        $dbname = $db_name;
            if (isset($db_user))        $user   = $db_user;
            if (isset($db_password))    $pass   = $db_password;
            break;
        }
    }
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>
<title>Debug Articles</title>
<style>
body{font-family:monospace;background:#0f0f0f;color:#e0e0e0;padding:30px;font-size:13px;}
h2{color:#f59e0b;margin:20px 0 8px;}
.ok{color:#4ade80;} .err{color:#f87171;} .warn{color:#fbbf24;}
pre{background:#1a1a1a;border:1px solid #333;padding:12px;border-radius:6px;overflow-x:auto;white-space:pre-wrap;}
table{border-collapse:collapse;width:100%;margin:8px 0;}
th{background:#1f1f1f;color:#f59e0b;padding:6px 10px;border:1px solid #333;text-align:left;}
td{padding:5px 10px;border:1px solid #333;}
.box{background:#1a1a1a;border:1px solid #333;border-radius:8px;padding:16px;margin:12px 0;}
</style></head><body>";

echo "<h1>🔍 Debug Articles — IMMO LOCAL+</h1>";
echo "<p style='color:#666'>Script temporaire — supprimer après diagnostic</p>";

// ─── 1. Connexion PDO ───
echo "<h2>1. Connexion Base de Données</h2>";
try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<span class='ok'>✅ Connecté à : {$dbname}@{$host}</span><br>";
} catch (PDOException $e) {
    echo "<span class='err'>❌ Connexion échouée : " . $e->getMessage() . "</span><br>";
    echo "<p class='warn'>⚠️ Remplissez les credentials en haut du fichier</p>";
    exit;
}

// ─── 2. Lister les tables ───
echo "<h2>2. Tables disponibles</h2><div class='box'><pre>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $highlight = stripos($t, 'article') !== false || stripos($t, 'blog') !== false
        ? "<span class='ok'>★ {$t}</span>"
        : $t;
    echo $highlight . "\n";
}
echo "</pre></div>";

// ─── 3. Détecter table articles ───
echo "<h2>3. Détection table articles</h2>";
$tableName   = null;
$candidates  = ['articles', 'blog_articles', 'blog_posts', 'posts', 'contenus', 'article'];
foreach ($candidates as $candidate) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$candidate}`")->fetchColumn();
        echo "<span class='ok'>✅ Table trouvée : <strong>{$candidate}</strong> → {$count} enregistrements</span><br>";
        if (!$tableName) $tableName = $candidate;
    } catch (PDOException $e) {
        echo "<span class='err'>❌ {$candidate} : introuvable</span><br>";
    }
}

if (!$tableName) {
    echo "<p class='err'>❌ Aucune table articles trouvée. Vérifiez le nom exact dans phpMyAdmin.</p>";
    // Chercher dans toutes les tables
    echo "<h2>Recherche dans toutes les tables...</h2><div class='box'>";
    foreach ($tables as $t) {
        try {
            $c = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            if ($c > 10) echo "<span class='warn'>→ {$t} : {$c} lignes</span><br>";
        } catch (PDOException $e) {}
    }
    echo "</div>";
    echo "</body></html>"; exit;
}

// ─── 4. Structure de la table ───
echo "<h2>4. Structure de <code>{$tableName}</code></h2><div class='box'>";
echo "<table><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>";
$cols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_ASSOC);
$colNames = [];
foreach ($cols as $col) {
    $colNames[] = $col['Field'];
    echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>" . ($col['Default'] ?? 'NULL') . "</td></tr>";
}
echo "</table></div>";

// ─── 5. Valeurs distinctes du champ status ───
echo "<h2>5. Valeurs du champ <code>status</code></h2><div class='box'>";
if (in_array('status', $colNames)) {
    $statuses = $pdo->query("SELECT status, COUNT(*) as nb FROM `{$tableName}` GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($statuses)) {
        echo "<span class='warn'>⚠️ Aucune donnée dans la table</span>";
    } else {
        echo "<table><tr><th>Status</th><th>Nombre</th></tr>";
        foreach ($statuses as $s) {
            echo "<tr><td><strong>{$s['status']}</strong></td><td>{$s['nb']}</td></tr>";
        }
        echo "</table>";
        // Vérification : si status n'est pas 'published'/'draft'
        $expected = ['published', 'draft', 'archived'];
        foreach ($statuses as $s) {
            if (!in_array($s['status'], $expected)) {
                echo "<br><span class='warn'>⚠️ Valeur inattendue : « {$s['status']} » — le filtre 'all' devrait quand même afficher ces articles</span>";
            }
        }
    }
} else {
    echo "<span class='err'>❌ Colonne 'status' absente !</span> Colonnes disponibles : " . implode(', ', $colNames);
}
echo "</div>";

// ─── 6. Requête réelle utilisée par index.php ───
echo "<h2>6. Test de la requête SELECT utilisée dans index.php</h2><div class='box'>";
$testSQL = "SELECT id, title, slug, status FROM `{$tableName}` ORDER BY created_at DESC LIMIT 5";
try {
    $rows = $pdo->query($testSQL)->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "<span class='warn'>⚠️ Requête OK mais 0 résultats — table vide ?</span>";
    } else {
        echo "<span class='ok'>✅ " . count($rows) . " résultats (sur 5 max)</span><br><br>";
        echo "<table><tr><th>ID</th><th>Titre</th><th>Slug</th><th>Status</th></tr>";
        foreach ($rows as $r) {
            echo "<tr><td>{$r['id']}</td><td>" . htmlspecialchars($r['title'] ?? '') . "</td><td>" . htmlspecialchars($r['slug'] ?? '') . "</td><td><strong>{$r['status']}</strong></td></tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<span class='err'>❌ Erreur SQL : " . $e->getMessage() . "</span>";
    // Essai sans created_at
    try {
        $rows = $pdo->query("SELECT * FROM `{$tableName}` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<br><span class='ok'>✅ SELECT * fonctionne :</span><pre>" . print_r($rows, true) . "</pre>";
    } catch (PDOException $e2) {
        echo "<br><span class='err'>❌ SELECT * aussi échoue : " . $e2->getMessage() . "</span>";
    }
}
echo "</div>";

// ─── 7. Test filtre status = 'all' (aucun WHERE) ───
echo "<h2>7. Simulation du filtre 'Tous' (sans WHERE)</h2><div class='box'>";
try {
    $total = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
    echo "<span class='ok'>✅ COUNT(*) = <strong>{$total}</strong> articles</span><br>";

    if ($total > 0) {
        // Simuler exactement la requête de index.php
        $colsAvail = implode(', ', array_map(fn($c) => "a.`{$c}`", array_slice($colNames, 0, 6)));
        $simSQL = "SELECT {$colsAvail} FROM `{$tableName}` a ORDER BY a.created_at DESC LIMIT 10";
        try {
            $sim = $pdo->query($simSQL)->fetchAll(PDO::FETCH_ASSOC);
            echo "<span class='ok'>✅ Requête simulée retourne " . count($sim) . " lignes</span>";
        } catch (PDOException $e) {
            echo "<span class='err'>❌ Erreur requête simulée : " . $e->getMessage() . "</span>";
            // Problème probable : colonne 'created_at' absente ?
            if (!in_array('created_at', $colNames)) {
                echo "<br><span class='warn'>⚠️ Colonne 'created_at' ABSENTE ! C'est probablement la cause du problème.</span>";
                echo "<br>Colonnes de tri disponibles : " . implode(', ', array_filter($colNames, fn($c) => stripos($c, 'date') !== false || stripos($c, 'time') !== false || stripos($c, 'at') !== false));
            }
        }
    }
} catch (PDOException $e) {
    echo "<span class='err'>❌ " . $e->getMessage() . "</span>";
}
echo "</div>";

// ─── 8. Résumé & Solution ───
echo "<h2>8. Résumé du diagnostic</h2><div class='box'>";
echo "<p><strong>Table détectée :</strong> <span class='ok'>{$tableName}</span></p>";
echo "<p><strong>Colonnes présentes :</strong> " . implode(', ', $colNames) . "</p>";

$problems = [];
if ($tableName !== 'articles') $problems[] = "La table s'appelle <strong>{$tableName}</strong> et non <em>articles</em> — mettre à jour \$tableName dans index.php";
if (!in_array('status', $colNames)) $problems[] = "Colonne <strong>status</strong> absente — le filtre WHERE échoue silencieusement";
if (!in_array('created_at', $colNames)) $problems[] = "Colonne <strong>created_at</strong> absente — ORDER BY created_at plante";
if (!in_array('slug', $colNames)) $problems[] = "Colonne <strong>slug</strong> absente";

if (empty($problems)) {
    echo "<span class='ok'>✅ Aucun problème structurel évident — vérifier les logs PHP pour l'erreur exacte</span>";
} else {
    echo "<ul>";
    foreach ($problems as $p) echo "<li class='err'>❌ {$p}</li>";
    echo "</ul>";
}
echo "</div>";

// ─── 9. Fix SQL suggéré ───
echo "<h2>9. Correctif à appliquer dans articles/index.php</h2><div class='box'>";
$orderCol = in_array('created_at', $colNames) ? 'created_at' :
           (in_array('date_creation', $colNames) ? 'date_creation' :
           (in_array('date', $colNames) ? 'date' : 'id'));
echo "<p>Remplacez dans index.php :</p>";
echo "<pre style='color:#fbbf24'>ORDER BY a.created_at DESC</pre>";
echo "<p>Par :</p>";
echo "<pre style='color:#4ade80'>ORDER BY a.{$orderCol} DESC</pre>";

if ($tableName !== 'articles') {
    echo "<p>Et remplacez la détection de table :</p>";
    echo "<pre style='color:#4ade80'>\$tableName = '{$tableName}';</pre>";
}
echo "</div>";

echo "<p style='color:#555;margin-top:40px'>⚠️ Supprimer ce fichier après utilisation : <code>rm /admin/debug_articles.php</code></p>";
echo "</body></html>";