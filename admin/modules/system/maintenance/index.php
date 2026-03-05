<?php
/**
 * MODULE MAINTENANCE - index.php
 * Placement : /admin/modules/maintenance/index.php
 * 
 * IMPORTANT : Ce fichier est inclus par dashboard.php
 * - $pdo est déjà disponible
 * - La session est déjà démarrée
 * - Le titre et la description sont déjà affichés par le parent
 */

// Récupérer les données de maintenance
$maintenance = ['is_active' => 0, 'message' => '', 'allowed_ips' => '127.0.0.1', 'end_date' => null];
try {
    $stmt = $pdo->query("SELECT * FROM maintenance WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch();
    if ($row) $maintenance = $row;
} catch (Exception $e) {
    // Table n'existe peut-être pas encore
}

$isActive   = (int)($maintenance['is_active'] ?? 0);
$message    = $maintenance['message'] ?? '';
$allowedIps = $maintenance['allowed_ips'] ?? '127.0.0.1';

// Détecter l'IP du visiteur
$visitorIp = $_SERVER['HTTP_CF_CONNECTING_IP'] 
    ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
    ?? $_SERVER['REMOTE_ADDR'] 
    ?? '';
if (strpos($visitorIp, ',') !== false) {
    $visitorIp = trim(explode(',', $visitorIp)[0]);
}

// Vérifier si l'IP est dans la whitelist
$ipList = array_filter(array_map('trim', explode(',', $allowedIps)));
$ipIsAllowed = in_array($visitorIp, $ipList);

// URL de l'API (relative au dashboard)
$apiUrl = '/admin/modules/maintenance/api/save.php';
?>

<style>
/* ============================================
   MAINTENANCE MODULE STYLES
   ============================================ */
@keyframes maintPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(252, 92, 101, 0.5); }
    50%      { box-shadow: 0 0 0 10px rgba(252, 92, 101, 0); }
}
@keyframes maintPulseGreen {
    0%, 100% { box-shadow: 0 0 0 0 rgba(56, 161, 105, 0.4); }
    50%      { box-shadow: 0 0 0 10px rgba(56, 161, 105, 0); }
}

.maint-banner {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
    transition: all 0.4s ease;
}
.maint-banner.active {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border: 2px solid #e53e3e;
}
.maint-banner.inactive {
    background: #f0fff4;
    border: 2px solid #38a169;
}

.maint-banner-top {
    padding: 18px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.maint-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.maint-dot.active {
    background: #fc5c65;
    animation: maintPulse 1.8s ease-in-out infinite;
}
.maint-dot.inactive {
    background: #38a169;
    animation: maintPulseGreen 2s ease-in-out infinite;
}
.maint-label {
    font-size: 17px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.maint-label.active { color: #fc5c65; }
.maint-label.inactive { color: #276749; }

/* Indicateurs en grille */
.maint-indicators {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
.maint-banner.active .maint-indicators {
    border-top: 1px solid rgba(255,255,255,0.08);
}
.maint-banner.inactive .maint-indicators {
    border-top: 1px solid rgba(0,0,0,0.06);
}
.maint-indicator {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.maint-banner.active .maint-indicator {
    border-right: 1px solid rgba(255,255,255,0.06);
}
.maint-banner.inactive .maint-indicator {
    border-right: 1px solid rgba(0,0,0,0.06);
}
.maint-indicator:last-child { border-right: none !important; }

.maint-ind-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.maint-banner.active .maint-ind-icon { background: rgba(255,255,255,0.08); }
.maint-banner.inactive .maint-ind-icon { background: rgba(0,0,0,0.04); }

.maint-ind-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}
.maint-banner.active .maint-ind-label { color: #718096; }
.maint-banner.inactive .maint-ind-label { color: #a0aec0; }

.maint-ind-value {
    font-size: 13px;
    font-weight: 600;
}
.maint-banner.active .maint-ind-value { color: #e2e8f0; }
.maint-banner.inactive .maint-ind-value { color: #1a202c; }

.maint-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.maint-badge.red { background: #fed7d7; color: #9b2c2c; }
.maint-badge.green { background: #c6f6d5; color: #276749; }

/* Cards */
.maint-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid #edf2f7;
}
.maint-card h3 {
    font-size: 15px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 4px;
}
.maint-card .subtitle {
    font-size: 13px;
    color: #a0aec0;
    margin-bottom: 16px;
}

/* Boutons toggle */
.maint-toggle-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.maint-toggle-btn {
    padding: 15px;
    border: 3px solid transparent;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}
.maint-toggle-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.maint-toggle-btn.on {
    background: linear-gradient(135deg, #38a169, #2f855a);
}
.maint-toggle-btn.off {
    background: linear-gradient(135deg, #e53e3e, #c53030);
}
.maint-toggle-btn.current {
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
}
.maint-toggle-btn .current-tag {
    position: absolute;
    top: 6px;
    right: 8px;
    font-size: 9px;
    background: rgba(255,255,255,0.25);
    padding: 2px 8px;
    border-radius: 8px;
    font-weight: 500;
}

/* Textarea */
.maint-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    resize: vertical;
    line-height: 1.6;
    color: #2d3748;
    transition: border-color 0.2s;
}
.maint-textarea:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(49,130,206,0.1);
}
.maint-help {
    font-size: 12px;
    color: #a0aec0;
    margin-top: 6px;
}

/* Bouton save */
.maint-save-btn {
    margin-top: 14px;
    padding: 10px 22px;
    background: #3182ce;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.maint-save-btn:hover {
    background: #2b6cb0;
    transform: translateY(-1px);
}
.maint-save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* IP Box */
.maint-ip-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 12px;
}
.maint-ip-info {
    font-size: 13px;
    color: #4a5568;
}
.maint-ip-info strong {
    color: #2b6cb0;
}
.maint-add-ip-btn {
    padding: 6px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    color: #4a5568;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.maint-add-ip-btn:hover {
    background: #edf2f7;
    border-color: #cbd5e0;
}

/* Toast */
.maint-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 14px 24px;
    border-radius: 10px;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    z-index: 9999;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 8px;
}
.maint-toast.show {
    opacity: 1;
    transform: translateY(0);
}
.maint-toast.success { background: linear-gradient(135deg, #38a169, #2f855a); }
.maint-toast.error { background: linear-gradient(135deg, #e53e3e, #c53030); }

/* Responsive */
@media (max-width: 900px) {
    .maint-indicators {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 600px) {
    .maint-indicators {
        grid-template-columns: 1fr !important;
    }
    .maint-toggle-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ============================================ -->
<!-- BANDEAU STATUT                                -->
<!-- ============================================ -->
<div class="maint-banner <?= $isActive ? 'active' : 'inactive' ?>" id="maint-banner">
    <div class="maint-banner-top">
        <span class="maint-dot <?= $isActive ? 'active' : 'inactive' ?>" id="maint-dot"></span>
        <span class="maint-label <?= $isActive ? 'active' : 'inactive' ?>" id="maint-label">
            <?= $isActive ? '🔧 MODE MAINTENANCE ACTIF' : '🌐 SITE EN LIGNE — Accessible aux visiteurs' ?>
        </span>
    </div>
    
    <div class="maint-indicators">
        <!-- Mode -->
        <div class="maint-indicator">
            <div class="maint-ind-icon">🖥️</div>
            <div>
                <div class="maint-ind-label">Mode</div>
                <div class="maint-ind-value" id="ind-mode">
                    <?php if ($isActive): ?>
                        <span class="maint-badge red">🔴 Maintenance</span>
                    <?php else: ?>
                        <span class="maint-badge green">🟢 Public</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Votre IP -->
        <div class="maint-indicator">
            <div class="maint-ind-icon">🌐</div>
            <div>
                <div class="maint-ind-label">Votre IP</div>
                <div class="maint-ind-value"><?= htmlspecialchars($visitorIp) ?></div>
            </div>
        </div>
        
        <!-- Accès IP -->
        <div class="maint-indicator">
            <div class="maint-ind-icon">🔑</div>
            <div>
                <div class="maint-ind-label">Accès IP</div>
                <div class="maint-ind-value" id="ind-ip">
                    <?php if ($ipIsAllowed): ?>
                        <span class="maint-badge green">✓ Autorisée</span>
                    <?php else: ?>
                        <span class="maint-badge red">✗ Non autorisée</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Visiteurs voient -->
        <div class="maint-indicator">
            <div class="maint-ind-icon">👥</div>
            <div>
                <div class="maint-ind-label">Visiteurs voient</div>
                <div class="maint-ind-value" id="ind-visitors">
                    <?php if ($isActive): ?>
                        <span class="maint-badge red">Page maintenance</span>
                    <?php else: ?>
                        <span class="maint-badge green">Site normal</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- BOUTONS ACTIVER / DÉSACTIVER                  -->
<!-- ============================================ -->
<div class="maint-card">
    <div class="maint-toggle-grid">
        <button onclick="maintToggle(1)" id="btn-on" class="maint-toggle-btn on <?= $isActive ? 'current' : '' ?>">
            🟢 ACTIVER LA MAINTENANCE
            <?php if ($isActive): ?><span class="current-tag">ACTUEL</span><?php endif; ?>
        </button>
        <button onclick="maintToggle(0)" id="btn-off" class="maint-toggle-btn off <?= !$isActive ? 'current' : '' ?>">
            🔴 DÉSACTIVER (SITE PUBLIC)
            <?php if (!$isActive): ?><span class="current-tag">ACTUEL</span><?php endif; ?>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- MESSAGE DE MAINTENANCE                        -->
<!-- ============================================ -->
<div class="maint-card">
    <h3>✏️ Message de maintenance</h3>
    <p class="subtitle">Texte affiché aux visiteurs lorsque le site est en maintenance</p>
    
    <label style="display:block;font-size:13px;font-weight:600;color:#4a5568;margin-bottom:6px;">Message</label>
    <textarea id="maint-message" class="maint-textarea" rows="3" 
        placeholder="Ex: Nous effectuons une mise à jour. Retour prévu demain à 9h."><?= htmlspecialchars($message) ?></textarea>
    <p class="maint-help">Soyez clair et professionnel. Indiquez si possible quand le site sera de retour.</p>
    
    <button onclick="maintSaveMessage()" class="maint-save-btn" id="btn-save-msg">
        💾 Sauvegarder le message
    </button>
</div>

<!-- ============================================ -->
<!-- IPs AUTORISÉES                                -->
<!-- ============================================ -->
<div class="maint-card">
    <h3>🔐 IPs autorisées</h3>
    <p class="subtitle">Ces adresses IP accèdent au site normalement même pendant la maintenance</p>
    
    <label style="display:block;font-size:13px;font-weight:600;color:#4a5568;margin-bottom:6px;">
        Adresses IP <span style="font-weight:400;color:#a0aec0;">(séparées par des virgules)</span>
    </label>
    <textarea id="maint-whitelist" class="maint-textarea" rows="2" 
        placeholder="Ex: 92.184.103.245, 1.2.3.4"><?= htmlspecialchars($allowedIps) ?></textarea>
    
    <div class="maint-ip-box">
        <span class="maint-ip-info">
            🌐 Votre IP : <strong><?= htmlspecialchars($visitorIp) ?></strong>
            <?php if ($ipIsAllowed): ?>
                <span style="color:#38a169;font-weight:600;font-size:12px;margin-left:6px;">✓ Autorisée</span>
            <?php else: ?>
                <span style="color:#e53e3e;font-weight:600;font-size:12px;margin-left:6px;">✗ Non autorisée</span>
            <?php endif; ?>
        </span>
        <button onclick="maintAddMyIp()" class="maint-add-ip-btn">+ Ajouter mon IP</button>
    </div>
    
    <button onclick="maintSaveWhitelist()" class="maint-save-btn" id="btn-save-ip">
        💾 Sauvegarder la whitelist
    </button>
</div>

<!-- Toast notification -->
<div class="maint-toast" id="maint-toast"></div>

<!-- ============================================ -->
<!-- JAVASCRIPT                                    -->
<!-- ============================================ -->
<script>
(function() {
    const API_URL = '<?= $apiUrl ?>';
    const MY_IP = '<?= htmlspecialchars($visitorIp) ?>';
    let currentState = <?= $isActive ? 'true' : 'false' ?>;
    
    // ---- Toast ----
    function showToast(msg, type = 'success') {
        const t = document.getElementById('maint-toast');
        t.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
        t.className = 'maint-toast ' + type + ' show';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.remove('show'), 3500);
    }
    
    // ---- API Call ----
    async function apiCall(action, data = {}) {
        const fd = new FormData();
        fd.append('action', action);
        for (const [k, v] of Object.entries(data)) fd.append(k, v);
        
        try {
            const res = await fetch(API_URL, { method: 'POST', body: fd });
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Réponse API non-JSON:', text);
                return { success: false, message: 'Erreur serveur' };
            }
        } catch (e) {
            console.error('Erreur réseau:', e);
            return { success: false, message: 'Erreur réseau' };
        }
    }
    
    // ---- Mettre à jour le bandeau ----
    function updateBanner(isActive) {
        currentState = isActive;
        const banner = document.getElementById('maint-banner');
        const dot = document.getElementById('maint-dot');
        const label = document.getElementById('maint-label');
        const btnOn = document.getElementById('btn-on');
        const btnOff = document.getElementById('btn-off');
        
        if (isActive) {
            banner.className = 'maint-banner active';
            dot.className = 'maint-dot active';
            label.className = 'maint-label active';
            label.textContent = '🔧 MODE MAINTENANCE ACTIF';
            
            document.getElementById('ind-mode').innerHTML = '<span class="maint-badge red">🔴 Maintenance</span>';
            document.getElementById('ind-visitors').innerHTML = '<span class="maint-badge red">Page maintenance</span>';
            
            btnOn.classList.add('current');
            btnOn.innerHTML = '🟢 ACTIVER LA MAINTENANCE<span class="current-tag">ACTUEL</span>';
            btnOff.classList.remove('current');
            btnOff.innerHTML = '🔴 DÉSACTIVER (SITE PUBLIC)';
        } else {
            banner.className = 'maint-banner inactive';
            dot.className = 'maint-dot inactive';
            label.className = 'maint-label inactive';
            label.textContent = '🌐 SITE EN LIGNE — Accessible aux visiteurs';
            
            document.getElementById('ind-mode').innerHTML = '<span class="maint-badge green">🟢 Public</span>';
            document.getElementById('ind-visitors').innerHTML = '<span class="maint-badge green">Site normal</span>';
            
            btnOff.classList.add('current');
            btnOff.innerHTML = '🔴 DÉSACTIVER (SITE PUBLIC)<span class="current-tag">ACTUEL</span>';
            btnOn.classList.remove('current');
            btnOn.innerHTML = '🟢 ACTIVER LA MAINTENANCE';
        }
    }
    
    // ---- Toggle maintenance ----
    window.maintToggle = async function(val) {
        const result = await apiCall('toggle', { is_active: val });
        if (result.success) {
            updateBanner(val === 1);
            showToast(val ? 'Maintenance activée ! Les visiteurs voient la page de maintenance.' : 'Site remis en ligne ! Les visiteurs voient le site normal.');
        } else {
            showToast(result.message || 'Erreur', 'error');
        }
    };
    
    // ---- Sauvegarder message ----
    window.maintSaveMessage = async function() {
        const btn = document.getElementById('btn-save-msg');
        const msg = document.getElementById('maint-message').value.trim();
        
        btn.disabled = true;
        btn.textContent = '⏳ Sauvegarde...';
        
        const result = await apiCall('save_message', { message: msg });
        
        btn.disabled = false;
        btn.innerHTML = '💾 Sauvegarder le message';
        
        if (result.success) {
            showToast('Message sauvegardé !');
        } else {
            showToast(result.message || 'Erreur', 'error');
        }
    };
    
    // ---- Sauvegarder whitelist ----
    window.maintSaveWhitelist = async function() {
        const btn = document.getElementById('btn-save-ip');
        const ips = document.getElementById('maint-whitelist').value.trim();
        
        btn.disabled = true;
        btn.textContent = '⏳ Sauvegarde...';
        
        const result = await apiCall('save_whitelist', { allowed_ips: ips });
        
        btn.disabled = false;
        btn.innerHTML = '💾 Sauvegarder la whitelist';
        
        if (result.success) {
            // Mettre à jour l'indicateur IP
            const ipList = ips.split(',').map(s => s.trim());
            const isAllowed = ipList.includes(MY_IP);
            document.getElementById('ind-ip').innerHTML = isAllowed
                ? '<span class="maint-badge green">✓ Autorisée</span>'
                : '<span class="maint-badge red">✗ Non autorisée</span>';
            
            showToast('Whitelist sauvegardée !');
        } else {
            showToast(result.message || 'Erreur', 'error');
        }
    };
    
    // ---- Ajouter mon IP ----
    window.maintAddMyIp = function() {
        const ta = document.getElementById('maint-whitelist');
        const current = ta.value.trim();
        const ips = current ? current.split(',').map(s => s.trim()) : [];
        
        if (ips.includes(MY_IP)) {
            showToast('Votre IP est déjà dans la liste');
            return;
        }
        
        ips.push(MY_IP);
        ta.value = ips.join(', ');
        showToast('IP ajoutée ! N\'oubliez pas de sauvegarder.');
    };
})();
</script>