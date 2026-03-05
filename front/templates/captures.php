<?php
/**
 * CAPTURE TEMPLATE
 * Landing page pour capture de leads avec formulaire
 * Variables disponibles:
 * - $page_title, $meta_description
 * - $page_content (contient tout le HTML + formulaire)
 * - $current_slug
 * - $pdo (connexion DB)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Formulaire'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? ''); ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="robots" content="index, follow">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --dark: #0f172a;
            --light: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --success: #10b981;
            --error: #ef4444;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* HEADER */
        header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        nav a:hover {
            color: var(--primary);
        }
        
        /* MAIN */
        main {
            min-height: calc(100vh - 80px);
        }
        
        /* FORM STYLES */
        .form-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .form-container h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .form-container p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 1.05rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-primary);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #cbd5e1;
        }
        
        /* RADIO / CHECKBOX */
        .form-group.radio-group,
        .form-group.checkbox-group {
            margin-bottom: 25px;
        }
        
        .radio-option,
        .checkbox-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .radio-option input[type="radio"],
        .checkbox-option input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }
        
        .radio-option label,
        .checkbox-option label {
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }
        
        /* FORM BUTTONS */
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-reset {
            background: #f1f5f9;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .btn-reset:hover {
            background: #e2e8f0;
        }
        
        /* MESSAGES */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: none;
        }
        
        .alert.success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: block;
        }
        
        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: block;
        }
        
        /* FOOTER */
        footer {
            background: var(--dark);
            color: white;
            padding: 50px 40px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 100px;
        }
        
        footer p {
            margin: 5px 0;
            opacity: 0.8;
            font-size: 0.95rem;
        }
        
        footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            header { padding: 15px 20px; }
            .form-container { 
                margin: 40px 20px; 
                padding: 30px 20px;
            }
            .form-container h2 { font-size: 26px; }
            nav { gap: 15px; font-size: 0.9rem; }
            .form-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-content">
            <a href="/" class="logo">
                <span>🏠</span>
                <span>Eduardo De Sul</span>
            </a>
            <nav>
                <a href="/">Home</a>
                <a href="/vendre">Vendre</a>
                <a href="/acheter">Acheter</a>
                <a href="#contact">Contact</a>
            </nav>
        </div>
    </header>

    <!-- MAIN CAPTURE CONTENT -->
    <main>
        <?php echo $page_content ?? ''; ?>
    </main>

    <!-- FOOTER -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Eduardo De Sul - Conseiller Immobilier eXp France</p>
        <p>Bordeaux Métropole · SIRET · Assurance RC Professionnelle</p>
        <p><a href="#mentions">Mentions légales</a> · <a href="#rgpd">Politique de confidentialité</a></p>
    </footer>

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "<?php echo addslashes(htmlspecialchars($page_title ?? '')); ?>",
      "description": "<?php echo addslashes(htmlspecialchars($meta_description ?? '')); ?>",
      "publisher": {
        "@type": "Organization",
        "name": "Eduardo De Sul"
      }
    }
    </script>

    <!-- FORM SCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const btn = form.querySelector('button[type="submit"]');
                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Envoi en cours...';
                    
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData);
                    
                    try {
                        const response = await fetch(form.action || '/api/form-submit.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            form.reset();
                            showAlert(form, 'Merci! Votre demande a été envoyée.', 'success');
                            setTimeout(() => {
                                window.location.href = result.redirect || '/';
                            }, 2000);
                        } else {
                            showAlert(form, result.message || 'Une erreur est survenue', 'error');
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    } catch (error) {
                        showAlert(form, 'Erreur réseau: ' + error.message, 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                });
            });
            
            function showAlert(form, message, type) {
                let alert = form.querySelector('.alert');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.className = 'alert';
                    form.insertBefore(alert, form.firstChild);
                }
                alert.textContent = message;
                alert.className = 'alert ' + type;
            }
        });
    </script>
</body>
</html>