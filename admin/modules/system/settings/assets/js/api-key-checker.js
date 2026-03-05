/**
 * API Key Checker — Composant popup réutilisable
 * 
 * Emplacement : /admin/modules/settings/assets/js/api-key-checker.js
 * 
 * USAGE DANS UN MODULE :
 * 
 *   <!-- En bas du module -->
 *   <script src="/admin/modules/settings/assets/js/api-key-checker.js"></script>
 *   <script>
 *     // Vérifier automatiquement les clés pour ce module
 *     ApiKeyChecker.checkModule('seo-semantic');
 *     
 *     // OU vérifier des services spécifiques
 *     ApiKeyChecker.checkServices(['openai', 'perplexity'], {
 *       title: 'Clé IA requise',
 *       message: 'L\'analyse sémantique nécessite une clé API.'
 *     });
 *     
 *     // OU vérifier un seul service avant une action
 *     document.getElementById('btnAnalyze').addEventListener('click', async () => {
 *       const ok = await ApiKeyChecker.requireKey('openai');
 *       if (ok) { lancerAnalyse(); }
 *     });
 *   </script>
 */

const ApiKeyChecker = (() => {

    // ═══ CHEMINS CORRIGÉS ═══
    // Endpoint AJAX dans /admin/modules/settings/api/
    const API_ENDPOINT = '/admin/modules/settings/api/api-keys.php';
    
    // URL de la page Settings (via le routeur admin)
    const SETTINGS_URL = '/admin/index.php?module=settings&tab=api-keys';
    
    // Cache pour éviter les vérifications répétées
    const _cache = {};
    
    // ─── Registre des services (simplifié, copie du PHP) ───
    const SERVICES = {
        openai:                { name: 'OpenAI (GPT-4)',         icon: 'fas fa-robot',          color: '#10a37f' },
        claude:                { name: 'Claude (Anthropic)',     icon: 'fas fa-brain',          color: '#cc785c' },
        perplexity:            { name: 'Perplexity AI',          icon: 'fas fa-search',         color: '#20808d' },
        mistral:               { name: 'Mistral AI',             icon: 'fas fa-wind',           color: '#f54e42' },
        google_maps:           { name: 'Google Maps',            icon: 'fas fa-map-marker-alt', color: '#4285f4' },
        google_analytics:      { name: 'Google Analytics',       icon: 'fas fa-chart-line',     color: '#e37400' },
        google_search_console: { name: 'Search Console',         icon: 'fas fa-search',         color: '#4285f4' },
        google_ads:            { name: 'Google Ads',             icon: 'fab fa-google',         color: '#4285f4' },
        google_my_business:    { name: 'Google My Business',     icon: 'fas fa-store',          color: '#34a853' },
        facebook_app:          { name: 'Facebook API',           icon: 'fab fa-facebook',       color: '#1877f2' },
        instagram_api:         { name: 'Instagram API',          icon: 'fab fa-instagram',      color: '#e4405f' },
        tiktok_api:            { name: 'TikTok API',             icon: 'fab fa-tiktok',         color: '#000' },
        mailjet:               { name: 'Mailjet',                icon: 'fas fa-envelope',       color: '#fead0d' },
        sendinblue:            { name: 'Brevo',                  icon: 'fas fa-paper-plane',    color: '#0b996e' },
        stripe:                { name: 'Stripe',                 icon: 'fab fa-stripe',         color: '#635bff' },
        twilio:                { name: 'Twilio',                 icon: 'fas fa-sms',            color: '#f22f46' },
    };
    
    // ═══ POPUP HTML + CSS ═══
    
    function injectStyles() {
        if (document.getElementById('akc-styles')) return;
        const style = document.createElement('style');
        style.id = 'akc-styles';
        style.textContent = `
            .akc-overlay {
                position: fixed; inset: 0; background: rgba(15,23,42,0.6);
                z-index: 99999; display: flex; align-items: center; justify-content: center;
                backdrop-filter: blur(4px); animation: akcFadeIn 0.3s ease;
            }
            @keyframes akcFadeIn { from { opacity: 0; } to { opacity: 1; } }
            .akc-popup {
                background: white; border-radius: 20px; width: 100%; max-width: 460px;
                box-shadow: 0 25px 60px rgba(0,0,0,0.3); animation: akcSlideUp 0.4s ease;
                overflow: hidden;
            }
            @keyframes akcSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            .akc-header {
                background: linear-gradient(135deg, #1e293b, #0f172a);
                padding: 28px 28px 24px; text-align: center; color: white;
            }
            .akc-header-icon {
                width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 16px;
                display: flex; align-items: center; justify-content: center;
                font-size: 24px; background: rgba(255,255,255,0.15);
            }
            .akc-header h3 { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
            .akc-header p { font-size: 13px; opacity: 0.8; line-height: 1.5; }
            .akc-body { padding: 24px 28px; }
            .akc-missing-list { list-style: none; padding: 0; margin: 0; }
            .akc-missing-item {
                display: flex; align-items: center; gap: 14px; padding: 14px;
                background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px;
                margin-bottom: 10px; transition: all 0.2s;
            }
            .akc-missing-item:last-child { margin-bottom: 0; }
            .akc-missing-item:hover { transform: translateX(4px); }
            .akc-service-icon {
                width: 36px; height: 36px; border-radius: 8px; display: flex;
                align-items: center; justify-content: center; color: white;
                font-size: 16px; flex-shrink: 0;
            }
            .akc-service-info { flex: 1; }
            .akc-service-info strong { display: block; font-size: 13px; color: #1e293b; }
            .akc-service-info span { font-size: 11px; color: #92400e; }
            .akc-service-badge {
                font-size: 9px; padding: 3px 8px; background: #fbbf24;
                color: #78350f; border-radius: 6px; font-weight: 700;
                text-transform: uppercase;
            }
            .akc-footer {
                padding: 16px 28px 24px; display: flex; flex-direction: column; gap: 10px;
            }
            .akc-btn {
                display: flex; align-items: center; justify-content: center; gap: 8px;
                padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600;
                cursor: pointer; border: none; text-decoration: none; transition: all 0.2s;
                font-family: inherit;
            }
            .akc-btn-primary {
                background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
            }
            .akc-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.4); }
            .akc-btn-secondary { background: #f1f5f9; color: #475569; }
            .akc-btn-secondary:hover { background: #e2e8f0; }
        `;
        document.head.appendChild(style);
    }
    
    // ═══ CORE FUNCTIONS ═══
    
    async function isConfigured(serviceKey) {
        if (_cache[serviceKey] !== undefined) return _cache[serviceKey];
        try {
            const resp = await fetch(`${API_ENDPOINT}?action=check&service=${serviceKey}`);
            const data = await resp.json();
            _cache[serviceKey] = data.is_configured || false;
            return _cache[serviceKey];
        } catch (e) {
            console.warn('ApiKeyChecker: erreur vérification', serviceKey, e);
            return false;
        }
    }
    
    async function checkModule(moduleSlug, options = {}) {
        try {
            const resp = await fetch(`${API_ENDPOINT}?action=check_module&module=${moduleSlug}`);
            const data = await resp.json();
            if (!data.success) return true;
            if (data.has_all_keys) return true;
            showPopup(data.missing, {
                title: options.title || 'Configuration requise',
                message: options.message || 'Ce module nécessite des clés API pour fonctionner.',
                allowDismiss: options.allowDismiss !== false,
            });
            return false;
        } catch (e) {
            console.warn('ApiKeyChecker: erreur checkModule', e);
            return true;
        }
    }
    
    async function checkServices(serviceKeys, options = {}) {
        try {
            const resp = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check_multiple', services: serviceKeys })
            });
            const data = await resp.json();
            if (!data.success) return true;
            
            const missing = [];
            for (const [key, info] of Object.entries(data.services)) {
                if (!info.is_configured) {
                    const svc = SERVICES[key] || {};
                    missing.push({
                        service_key: key,
                        name: info.service_name || svc.name || key,
                        icon: svc.icon || 'fas fa-key',
                        color: svc.color || '#64748b'
                    });
                }
            }
            if (missing.length === 0) return true;
            
            showPopup(missing, {
                title: options.title || 'Clés API requises',
                message: options.message || 'Certaines clés API doivent être configurées.',
                allowDismiss: options.allowDismiss !== false,
            });
            return false;
        } catch (e) {
            console.warn('ApiKeyChecker: erreur checkServices', e);
            return true;
        }
    }
    
    async function requireKey(serviceKey, options = {}) {
        const configured = await isConfigured(serviceKey);
        if (configured) return true;
        const svc = SERVICES[serviceKey] || {};
        showPopup([{
            service_key: serviceKey,
            name: svc.name || serviceKey,
            icon: svc.icon || 'fas fa-key',
            color: svc.color || '#64748b'
        }], {
            title: options.title || `${svc.name || serviceKey} requis`,
            message: options.message || `Veuillez configurer votre clé API ${svc.name || serviceKey} pour utiliser cette fonctionnalité.`,
            allowDismiss: options.allowDismiss !== false
        });
        return false;
    }
    
    function showPopup(missingServices, options = {}) {
        injectStyles();
        const existing = document.getElementById('akc-overlay');
        if (existing) existing.remove();
        
        const title = options.title || 'Configuration requise';
        const message = options.message || 'Des clés API doivent être configurées.';
        const allowDismiss = options.allowDismiss !== false;
        
        let missingHTML = '';
        missingServices.forEach(svc => {
            missingHTML += `
                <li class="akc-missing-item">
                    <div class="akc-service-icon" style="background:${svc.color || '#64748b'}">
                        <i class="${svc.icon || 'fas fa-key'}"></i>
                    </div>
                    <div class="akc-service-info">
                        <strong>${svc.name}</strong>
                        <span>Clé API non configurée</span>
                    </div>
                    <span class="akc-service-badge">REQUIS</span>
                </li>
            `;
        });
        
        const overlay = document.createElement('div');
        overlay.id = 'akc-overlay';
        overlay.className = 'akc-overlay';
        overlay.innerHTML = `
            <div class="akc-popup">
                <div class="akc-header">
                    <div class="akc-header-icon"><i class="fas fa-key"></i></div>
                    <h3>${title}</h3>
                    <p>${message}</p>
                </div>
                <div class="akc-body">
                    <ul class="akc-missing-list">${missingHTML}</ul>
                </div>
                <div class="akc-footer">
                    <a href="${SETTINGS_URL}" class="akc-btn akc-btn-primary">
                        <i class="fas fa-cog"></i> Configurer mes clés API
                    </a>
                    ${allowDismiss ? `
                    <button class="akc-btn akc-btn-secondary" onclick="document.getElementById('akc-overlay').remove()">
                        <i class="fas fa-times"></i> Plus tard
                    </button>` : ''}
                </div>
            </div>
        `;
        
        if (allowDismiss) {
            overlay.addEventListener('click', function(e) { if (e.target === this) this.remove(); });
        }
        document.body.appendChild(overlay);
    }
    
    function clearCache(serviceKey = null) {
        if (serviceKey) delete _cache[serviceKey];
        else Object.keys(_cache).forEach(k => delete _cache[k]);
    }
    
    // ═══ API PUBLIQUE ═══
    return { checkModule, checkServices, requireKey, isConfigured, showPopup, clearCache, SERVICES, SETTINGS_URL };
})();