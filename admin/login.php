<?php
/**
 * 🔐 PAGE LOGIN OTP - VERSION ROBUSTE
 * /admin/login.php
 * 
 * Gère les erreurs correctement
 * Pas de dépendances externes fragiles
 */

session_start();

// ═══════════════════════════════════════════════════════════
// 1. CONNEXION BD DIRECTE (Plus robuste)
// ═══════════════════════════════════════════════════════════

$db_host = 'localhost';
$db_user = 'mahe6420_edbordeaux';
$db_pass = '1KX(M3wwBbbW';
$db_name = 'mahe6420_cms-site-ed-bordeaux';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("❌ Erreur de connexion BD: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════
// 2. FONCTIONS UTILES
// ═══════════════════════════════════════════════════════════

function sanitize($input, $type = 'string') {
    if ($type === 'email') {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sendOTPEmail($to, $otp) {
    $subject = '[Eduardo De Sul] Votre code de connexion OTP';
    $message = "Bonjour,\n\n";
    $message .= "Votre code OTP de connexion est : $otp\n\n";
    $message .= "Ce code est valide pendant 10 minutes.\n\n";
    $message .= "Si vous n'avez pas demandé ce code, veuillez l'ignorer.\n\n";
    $message .= "---\n";
    $message .= "Eduardo De Sul - Conseiller Immobilier\n";
    $message .= "https://eduardo-desul-immobilier-bordeaux.fr\n";
    
    $headers = "From: noreply@eduardo-desul-immobilier.fr\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($to, $subject, $message, $headers);
}

function writeLog($message, $level = 'INFO') {
    $log_file = __DIR__ . '/../logs/login.log';
    @mkdir(dirname($log_file), 0755, true);
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message\n";
    
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ═══════════════════════════════════════════════════════════
// 3. INITIALISER LES VARIABLES
// ═══════════════════════════════════════════════════════════

$error = '';
$success = '';
$step = $_POST['step'] ?? $_GET['step'] ?? 'email';

// ═══════════════════════════════════════════════════════════
// 4. TRAITER LE FORMULAIRE
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ÉTAPE 1: Email
    if ($step === 'email' || empty($step)) {
        $email = sanitize($_POST['email'] ?? '', 'email');

        if (empty($email) || !isValidEmail($email)) {
            $error = '❌ Email invalide';
        } else {
            try {
                // Vérifier que l'email existe en BD
                $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $error = '❌ Email non autorisé';
                    writeLog("Unauthorized login attempt: $email", 'WARNING');
                } else {
                    // Générer OTP
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Sauvegarder en session
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_time'] = time();

                    // Envoyer par email
                    if (sendOTPEmail($email, $otp)) {
                        $success = "✅ Code OTP envoyé à " . htmlspecialchars($email);
                        writeLog("OTP sent to: $email", 'INFO');
                        $step = 'otp';
                    } else {
                        $error = '❌ Erreur lors de l\'envoi du code. Vérifiez la config email.';
                        writeLog("Email failed for: $email", 'ERROR');
                    }
                }
            } catch (Exception $e) {
                $error = '❌ Erreur: ' . $e->getMessage();
                writeLog("Database error: " . $e->getMessage(), 'ERROR');
            }
        }
    }
    
    // ÉTAPE 2: Validation OTP
    elseif ($step === 'otp') {
        $otp = sanitize($_POST['otp'] ?? '', 'string');
        $stored_otp = $_SESSION['otp'] ?? null;
        $stored_email = $_SESSION['otp_email'] ?? null;
        $otp_time = $_SESSION['otp_time'] ?? 0;

        // Vérifier l'expiration (10 minutes = 600 secondes)
        if (time() - $otp_time > 600) {
            $error = '⏱️ Code expiré (10 minutes). Veuillez recommencer.';
            $step = 'email';
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
            writeLog("OTP expired for: $stored_email", 'WARNING');
        }
        // Vérifier le code
        elseif (empty($otp) || empty($stored_otp) || $otp !== $stored_otp) {
            $error = '❌ Code OTP incorrect';
            writeLog("Invalid OTP for: $stored_email", 'WARNING');
        }
        // Code correct
        else {
            try {
                // Récupérer l'admin
                $stmt = $db->prepare("SELECT id, email FROM admins WHERE email = ? LIMIT 1");
                $stmt->execute([$stored_email]);
                $admin = $stmt->fetch();

                if ($admin) {
                    // Connexion réussie!
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_login_time'] = time();
                    
                    // Mettre à jour last_login
                    $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);

                    // Nettoyer les données OTP
                    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);

                    writeLog("Successful login for: " . $admin['email'], 'INFO');
                    
                    // Redirection
                    header('Location: /admin/dashboard.php');
                    exit;
                } else {
                    $error = '❌ Admin non trouvé';
                    writeLog("Admin not found for: $stored_email", 'ERROR');
                    $step = 'email';
                }
            } catch (Exception $e) {
                $error = '❌ Erreur: ' . $e->getMessage();
                writeLog("Login error: " . $e->getMessage(), 'ERROR');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Eduardo De Sul</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .logo p {
            font-size: 14px;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        input::placeholder {
            color: #bbb;
        }
        
        button {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fff3f0;
            border-left: 4px solid #ff6b6b;
            color: #c92a2a;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .success {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #16a34a;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .info {
            font-size: 13px;
            color: #666;
            margin-top: 16px;
            text-align: center;
            line-height: 1.6;
        }
        
        .info a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .info a:hover {
            text-decoration: underline;
        }
        
        .step-indicator {
            font-size: 12px;
            color: #999;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .step-indicator span {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🏡 Eduardo De Sul</h1>
            <p>Conseiller Immobilier - Bordeaux</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- FORMULAIRE EMAIL -->
        <?php if ($step === 'email'): ?>
            <div class="step-indicator">Étape 1/2: <span>Entrée Email</span></div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Adresse Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@eduardo-desul-immobilier.fr" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit">Envoyer le Code OTP</button>
                <p class="info">
                    Un code OTP sera envoyé à votre adresse email.<br>
                    <strong>Vérifiez le dossier Spam si vous ne le recevez pas.</strong>
                </p>
            </form>

        <!-- FORMULAIRE OTP -->
        <?php else: ?>
            <div class="step-indicator">Étape 2/2: <span>Validation Code OTP</span></div>
            
            <form method="POST">
                <input type="hidden" name="step" value="otp">
                
                <p class="info" style="margin-bottom: 20px;">
                    Code envoyé à:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['otp_email'] ?? ''); ?></strong>
                </p>
                
                <div class="form-group">
                    <label for="otp">🔐 Code OTP (6 chiffres)</label>
                    <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus>
                </div>
                
                <button type="submit">Valider la Connexion</button>
                
                <p class="info">
                    Code valide pendant <strong>10 minutes</strong><br>
                    <a href="/admin/login.php">↶ Utiliser un autre email</a>
                </p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>