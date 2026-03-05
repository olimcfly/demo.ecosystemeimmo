<?php
/**
 * Page publique de capture
 * ÉCOSYSTÈME IMMO LOCAL+
 * 
 * URL: /capture/{slug}
 * - Affiche la page de capture avec formulaire intégré
 * - Traite les soumissions → CRM (table leads)
 * - Redirige vers la page de remerciement
 */

// ── Récupérer le slug ──
$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (empty($slug)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// ── Connexion BD ──
try {
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) $configPath = __DIR__ . '/../config/config.php';
    require_once $configPath;
    if (!isset($pdo)) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Erreur serveur');
}

// ── Charger la page ──
$stmt = $pdo->prepare("SELECT * FROM capture_pages WHERE slug = ? AND status = 'publie'");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// ── Incrémenter les vues ──
$pdo->prepare("UPDATE capture_pages SET views_count = views_count + 1 WHERE id = ?")->execute([$page['id']]);

// ══════════════════════════════════════════════════════════
// TRAITEMENT DU FORMULAIRE (POST)
// ══════════════════════════════════════════════════════════
$showMerci = false;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['capture_submit'])) {
    $formConfig = json_decode($page['form_config'], true) ?: [];
    $leadData = [];
    
    // Récupérer tous les champs
    foreach ($formConfig as $field) {
        $name = $field['name'];
        $value = trim($_POST[$name] ?? '');
        
        // Validation champs obligatoires
        if (!empty($field['required']) && empty($value)) {
            $formError = 'Le champ "' . htmlspecialchars($field['label']) . '" est obligatoire.';
            break;
        }
        
        $leadData[$name] = $value;
    }

    if (empty($formError)) {
        try {
            // ── Extraire les infos principales pour le CRM ──
            $firstName = $leadData['prenom'] ?? $leadData['first_name'] ?? $leadData['firstname'] ?? '';
            $lastName = $leadData['nom'] ?? $leadData['last_name'] ?? $leadData['lastname'] ?? $leadData['name'] ?? '';
            $email = $leadData['email'] ?? $leadData['mail'] ?? '';
            $phone = $leadData['telephone'] ?? $leadData['phone'] ?? $leadData['tel'] ?? '';

            // ── Vérifier si le lead existe déjà (par email) ──
            $existingLead = null;
            if (!empty($email)) {
                $checkStmt = $pdo->prepare("SELECT id FROM leads WHERE email = ?");
                $checkStmt->execute([$email]);
                $existingLead = $checkStmt->fetch();
            }

            if ($existingLead) {
                // Mettre à jour le lead existant
                $updateStmt = $pdo->prepare("UPDATE leads SET 
                    first_name = COALESCE(NULLIF(?, ''), first_name),
                    last_name = COALESCE(NULLIF(?, ''), last_name),
                    phone = COALESCE(NULLIF(?, ''), phone),
                    source = ?,
                    tags = CONCAT(COALESCE(tags, ''), ',', ?),
                    custom_fields = ?,
                    updated_at = NOW()
                    WHERE id = ?");
                $updateStmt->execute([
                    $firstName, $lastName, $phone,
                    $page['lead_source'],
                    $page['lead_tags'] ?? '',
                    json_encode($leadData, JSON_UNESCAPED_UNICODE),
                    $existingLead['id']
                ]);
                $leadId = $existingLead['id'];
            } else {
                // Créer un nouveau lead
                // Vérifier la structure de la table leads
                $columns = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('first_name', $columns)) {
                    // Structure CRM avec first_name/last_name
                    $insertStmt = $pdo->prepare("INSERT INTO leads 
                        (first_name, last_name, email, phone, source, tags, custom_fields, status, temperature, score, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'nouveau', 'froid', 5, NOW())");
                    $insertStmt->execute([
                        $firstName, $lastName, $email, $phone,
                        $page['lead_source'],
                        $page['lead_tags'] ?? '',
                        json_encode($leadData, JSON_UNESCAPED_UNICODE)
                    ]);
                } elseif (in_array('nom', $columns)) {
                    // Structure alternative avec nom/prenom
                    $insertStmt = $pdo->prepare("INSERT INTO leads 
                        (nom, prenom, email, telephone, source, tags, data_json, statut, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'nouveau', NOW())");
                    $insertStmt->execute([
                        $lastName, $firstName, $email, $phone,
                        $page['lead_source'],
                        $page['lead_tags'] ?? '',
                        json_encode($leadData, JSON_UNESCAPED_UNICODE)
                    ]);
                } else {
                    // Fallback: insertion basique
                    $insertStmt = $pdo->prepare("INSERT INTO leads (email, source, created_at) VALUES (?, ?, NOW())");
                    $insertStmt->execute([$email, $page['lead_source']]);
                }
                $leadId = $pdo->lastInsertId();
            }

            // ── Log d'interaction ──
            try {
                $logStmt = $pdo->prepare("INSERT INTO lead_interactions 
                    (lead_id, type, description, metadata, interaction_date)
                    VALUES (?, 'formulaire', ?, ?, NOW())");
                $logStmt->execute([
                    $leadId,
                    'Soumission page de capture: ' . $page['titre'],
                    json_encode([
                        'capture_page_id' => $page['id'],
                        'capture_slug' => $page['slug'],
                        'source' => $page['lead_source'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ], JSON_UNESCAPED_UNICODE)
                ]);
            } catch (Exception $e) {
                // Table interactions peut ne pas exister, on continue
            }

            // ── Incrémenter le compteur de soumissions ──
            $pdo->prepare("UPDATE capture_pages SET 
                submissions_count = submissions_count + 1,
                conversion_rate = ROUND((submissions_count + 1) / GREATEST(views_count, 1) * 100, 2)
                WHERE id = ?")->execute([$page['id']]);

            $showMerci = true;

        } catch (PDOException $e) {
            error_log('Capture form error: ' . $e->getMessage());
            $formError = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

// ══════════════════════════════════════════════════════════
// AFFICHAGE
// ══════════════════════════════════════════════════════════

// Si redirection externe configurée
if ($showMerci && !empty($page['redirect_url'])) {
    header('Location: ' . $page['redirect_url']);
    exit;
}

// ── Générer le HTML du formulaire ──
function buildCaptureForm($page) {
    $formConfig = json_decode($page['form_config'], true) ?: [];
    $btnText = htmlspecialchars($page['form_button_text'] ?? 'Envoyer');
    $btnColor = htmlspecialchars($page['form_button_color'] ?? '#667eea');
    $formTitle = htmlspecialchars($page['form_titre'] ?? '');

    $html = '<div style="max-width:500px;margin:0 auto;padding:30px;background:#fff;border-radius:16px;box-shadow:0 4px 30px rgba(0,0,0,0.08);">';
    
    if ($formTitle) {
        $html .= '<h3 style="margin-bottom:20px;font-size:1.3rem;color:#1a1a2e;text-align:center;">' . $formTitle . '</h3>';
    }

    $html .= '<form method="POST" action="" id="captureForm">';
    $html .= '<input type="hidden" name="capture_submit" value="1">';

    foreach ($formConfig as $field) {
        $name = htmlspecialchars($field['name']);
        $label = htmlspecialchars($field['label']);
        $type = $field['type'] ?? 'text';
        $required = !empty($field['required']) ? 'required' : '';
        $placeholder = htmlspecialchars($field['placeholder'] ?? '');
        $requiredStar = !empty($field['required']) ? ' <span style="color:#ef4444">*</span>' : '';

        if ($type === 'hidden') {
            $html .= "<input type='hidden' name='{$name}' value='{$placeholder}'>";
            continue;
        }

        $html .= '<div style="margin-bottom:16px;">';

        if ($type !== 'checkbox') {
            $html .= "<label style='display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:5px;'>{$label}{$requiredStar}</label>";
        }

        $inputStyle = "width:100%;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:0.95rem;color:#1a1a2e;background:#fafbfc;transition:border-color 0.2s;box-sizing:border-box;font-family:inherit;";

        switch ($type) {
            case 'textarea':
                $html .= "<textarea name='{$name}' placeholder='{$placeholder}' {$required} style='{$inputStyle}min-height:80px;resize:vertical;'></textarea>";
                break;
            case 'select':
                $html .= "<select name='{$name}' {$required} style='{$inputStyle}'><option value=''>Choisir...</option>";
                foreach (($field['options'] ?? []) as $opt) {
                    $optHtml = htmlspecialchars($opt);
                    $html .= "<option value='{$optHtml}'>{$optHtml}</option>";
                }
                $html .= "</select>";
                break;
            case 'checkbox':
                $html .= "<label style='display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:0.9rem;color:#374151;'><input type='checkbox' name='{$name}' value='1' {$required} style='margin-top:3px;'> {$label}{$requiredStar}</label>";
                break;
            default:
                $html .= "<input type='{$type}' name='{$name}' placeholder='{$placeholder}' {$required} style='{$inputStyle}'>";
        }

        $html .= '</div>';
    }

    $html .= "<button type='submit' style='width:100%;padding:14px;background:{$btnColor};color:#fff;border:none;border-radius:10px;font-size:1.05rem;font-weight:700;cursor:pointer;margin-top:8px;transition:all 0.3s;'>{$btnText}</button>";
    $html .= '<p style="text-align:center;font-size:0.75rem;color:#9ca3af;margin-top:12px;">Vos données sont protégées et ne seront jamais partagées.</p>';
    $html .= '</form></div>';

    // Focus styles
    $html .= "<style>
        #captureForm input:focus, #captureForm textarea:focus, #captureForm select:focus {
            outline:none; border-color:{$btnColor}; box-shadow:0 0 0 3px " . $btnColor . "22;
        }
        #captureForm button:hover { opacity:0.9; transform:translateY(-1px); box-shadow:0 4px 15px " . $btnColor . "44; }
    </style>";

    return $html;
}

// ── Choisir le HTML à afficher ──
if ($showMerci) {
    $displayHtml = $page['html_merci'] ?? '';
    if (empty($displayHtml)) {
        // Page de remerciement par défaut
        $displayHtml = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
        <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:system-ui,-apple-system,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:20px}
        .card{background:#fff;border-radius:20px;padding:50px 30px;max-width:500px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.06)}
        .icon{font-size:3.5rem;margin-bottom:16px}h1{font-size:1.8rem;margin-bottom:12px;color:#1a1a2e}p{color:#666;font-size:1rem;line-height:1.6}
        </style></head><body><div class="card"><div class="icon">🎉</div><h1>Merci !</h1><p>Votre demande a bien été envoyée. Nous vous recontacterons très vite.</p></div></body></html>';
    }
} else {
    $displayHtml = $page['html_capture'] ?? '';
    // Injecter le formulaire à la place de {{FORMULAIRE}}
    $formHtml = buildCaptureForm($page);
    
    if (!empty($formError)) {
        $errorHtml = '<div style="max-width:500px;margin:0 auto 16px;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:0.9rem;text-align:center;">' . htmlspecialchars($formError) . '</div>';
        $formHtml = $errorHtml . $formHtml;
    }
    
    $displayHtml = str_replace('{{FORMULAIRE}}', $formHtml, $displayHtml);
    
    // Si pas de {{FORMULAIRE}} trouvé, ajouter à la fin
    if (strpos($page['html_capture'] ?? '', '{{FORMULAIRE}}') === false) {
        $displayHtml .= '<div style="padding:40px 20px;">' . $formHtml . '</div>';
    }
}

// ── Meta tags ──
$metaTitle = $page['meta_title'] ?: $page['titre'];
$metaDesc = $page['meta_description'] ?: $page['sous_titre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <?php if ($metaDesc): ?><meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <?php if ($metaDesc): ?><meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>"><?php endif; ?>
    <?php if ($page['og_image']): ?><meta property="og:image" content="<?= htmlspecialchars($page['og_image']) ?>"><?php endif; ?>
    <meta name="robots" content="index, follow">
</head>
<body>
<?= $displayHtml ?>
</body>
</html>