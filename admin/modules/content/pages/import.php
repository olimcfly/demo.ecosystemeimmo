<?php
// /admin/modules/pages/import.php
// Import des pages/articles depuis la base Bordeaux

// Session déjà démarrée dans dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die('Erreur connexion DB');
}

// Traiter l'import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'import') {
    header('Content-Type: application/json');

    $imported = 0;
    $errors = [];

    try {
        // Récupérer les articles Bordeaux
        $articles = $pdo->query("
            SELECT 
                titre as title, slug, seo_title as meta_title,
                seo_description as meta_description, focus_keyword,
                contenu as content, extrait as excerpt,
                image as featured_image, created_at, updated_at
            FROM articles
            WHERE statut = 'published'
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($articles as $article) {
            try {
                // Vérifier si existe déjà
                $check = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
                $check->execute([$article['slug']]);

                if ($check->rowCount() === 0) {
                    $insert = $pdo->prepare("
                        INSERT INTO pages (
                            title, slug, content, excerpt,
                            meta_title, meta_description, focus_keyword,
                            featured_image, status, created_at, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $insert->execute([
                        $article['title'],
                        $article['slug'],
                        $article['content'] ?? '',
                        $article['excerpt'] ?? '',
                        $article['meta_title'] ?? '',
                        $article['meta_description'] ?? '',
                        $article['focus_keyword'] ?? '',
                        $article['featured_image'] ?? null,
                        'published',
                        $article['created_at'] ?? date('Y-m-d H:i:s'),
                        $_SESSION['admin_id']
                    ]);

                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = "Article '{$article['title']}': " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

?>

<style>
    .import-container {
        max-width: 700px;
        margin: 0 auto;
    }

    .import-card {
        background: white;
        border-radius: 8px;
        padding: 30px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .import-card h2 {
        margin: 0 0 15px 0;
        font-size: 22px;
        font-weight: 700;
        color: #1a202c;
    }

    .import-card p {
        color: #6b7280;
        line-height: 1.6;
        margin: 0 0 25px 0;
    }

    .btn-import {
        padding: 12px 24px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-bottom: 15px;
    }

    .btn-import:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
    }

    .btn-import:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    .progress-section {
        display: none;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #e5e7eb;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        width: 0%;
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 10px;
    }

    .results {
        display: none;
        margin-top: 25px;
        padding: 20px;
        background: #d1fae5;
        border-radius: 6px;
        border-left: 4px solid #10b981;
    }

    .results.error {
        background: #fee2e2;
        border-color: #ef4444;
    }

    .results h4 {
        margin: 0 0 15px 0;
        color: #047857;
    }

    .results.error h4 {
        color: #991b1b;
    }

    .results ul {
        margin: 0;
        padding-left: 20px;
    }

    .results li {
        color: #047857;
        margin: 8px 0;
    }

    .results.error li {
        color: #991b1b;
    }

    .info-box {
        background: #f0f9ff;
        border-left: 4px solid #0284c7;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        color: #075985;
        font-size: 14px;
    }
</style>

<div class="import-container">
    <div class="import-card">
        <h2>📥 Importer depuis Bordeaux</h2>
        
        <div class="info-box">
            <strong>ℹ️ Information:</strong> Cette action importe les articles et pages publiées depuis votre base Bordeaux vers le CRM local.
        </div>

        <p>
            Les pages déjà importées ne seront pas dupliquées. Seules les pages publiées seront importées.
        </p>

        <button class="btn-import" onclick="startImport()">
            🚀 Démarrer l'import
        </button>

        <a href="/admin/dashboard.php?page=pages" style="
            display: block;
            text-align: center;
            color: #6b7280;
            text-decoration: none;
            padding: 12px 24px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: all 0.3s ease;
        " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
            ← Retour aux pages
        </a>

        <div class="progress-section" id="progressSection">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text" id="progressText">Préparation de l'import...</div>
        </div>

        <div class="results" id="results"></div>
    </div>
</div>

<script>
    async function startImport() {
        const btn = event.target;
        btn.disabled = true;

        const progressSection = document.getElementById('progressSection');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const resultsDiv = document.getElementById('results');

        progressSection.style.display = 'block';
        resultsDiv.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('action', 'import');

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                progressFill.style.width = '100%';
                progressText.textContent = 'Import terminé! ✓';

                let html = `<h4>✓ ${data.imported} page(s) importée(s)</h4>`;

                if (data.errors.length > 0) {
                    html += '<h4 style="margin-top: 15px; color: #dc2626;">⚠️ Erreurs rencontrées:</h4>';
                    html += '<ul style="color: #991b1b;">';
                    data.errors.forEach(err => {
                        html += `<li>${err}</li>`;
                    });
                    html += '</ul>';
                }

                resultsDiv.classList.remove('error');
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            progressText.textContent = 'Erreur lors de l\'import';
            resultsDiv.classList.add('error');
            resultsDiv.innerHTML = `<h4>❌ Erreur</h4><p>${error.message}</p>`;
            resultsDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
        }
    }
</script>