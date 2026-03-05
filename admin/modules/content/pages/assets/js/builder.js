/**
 * ========================================
 * PAGES BUILDER - JAVASCRIPT V4
 * ========================================
 * - Mode plein écran
 * - Édition inline WYSIWYG des sections
 * - Drag & drop amélioré
 * ========================================
 */

const $ = id => document.getElementById(id);
const loader = $('loader'), placeholder = $('placeholder'), iframe = $('previewIframe'), frame = $('previewFrame');
const htmlEditor = $('htmlEditor'), cssEditor = $('cssEditor'), jsEditor = $('jsEditor');

// Init
document.addEventListener('DOMContentLoaded', () => {
    if (content) {
        fetchLayout().then(() => {
            updatePreview();
            parseSections();
        });
    } else {
        fetchLayout();
    }
});

async function fetchLayout() {
    try {
        const r = await fetch('/admin/modules/pages/api.php?action=layout');
        const d = await r.json();
        if (d.success) { 
            layoutHeader = d.header || ''; 
            layoutFooter = d.footer || ''; 
        }
    } catch(e) {
        console.error('Erreur fetchLayout:', e);
    }
}

// Tabs
document.querySelectorAll('.builder-tab').forEach(t => {
    t.onclick = function() {
        document.querySelectorAll('.builder-tab').forEach(x => x.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(x => x.classList.remove('active'));
        this.classList.add('active');
        $('panel-' + this.dataset.tab).classList.add('active');
        
        const tab = this.dataset.tab;
        $('generateBtn').style.display = tab === 'ia' ? 'flex' : 'none';
        
        if (tab === 'code') htmlEditor.value = content;
        if (tab === 'sections') parseSections();
    };
});

// Quick tags
document.querySelectorAll('.quick-tag').forEach(t => {
    t.onclick = function() {
        const ta = $('pageDesc');
        ta.value += (ta.value ? '\n' : '') + this.dataset.t;
        this.style.opacity = '0.5';
    };
});

// Devices
document.querySelectorAll('.preview-device').forEach(b => {
    b.onclick = function() {
        document.querySelectorAll('.preview-device').forEach(x => x.classList.remove('active'));
        this.classList.add('active');
        frame.className = 'preview-frame ' + this.dataset.d;
    };
});

// Settings toggles
$('showHeader').onchange = function() { showHeader = this.checked; updatePreview(); };
$('showFooter').onchange = function() { showFooter = this.checked; updatePreview(); };
$('previewLayout').onchange = function() { previewLayout = this.checked; updatePreview(); };

// Generate with AI
$('generateBtn').onclick = async function() {
    const desc = $('pageDesc').value;
    if (!desc.trim()) { toast('Décrivez votre page', 'error'); return; }
    
    placeholder.style.display = 'none';
    iframe.style.display = 'none';
    loader.classList.add('active');
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
    
    try {
        const r = await fetch('/admin/modules/pages/api.php?action=generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pageName: pageTitle,
                pageType: $('pageType').value,
                targetAudience: $('targetAudience').value,
                description: desc,
                instructions: $('pageInstr').value,
                website_id: websiteId
            })
        });
        const d = await r.json();
        
        if (d.success) {
            content = d.html;
            htmlEditor.value = content;
            updatePreview();
            parseSections();
            toast('Page générée !', 'success');
        } else {
            throw new Error(d.error || 'Erreur');
        }
    } catch(e) {
        toast(e.message, 'error');
        placeholder.style.display = 'flex';
    }
    
    loader.classList.remove('active');
    this.disabled = false;
    this.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Générer avec l\'IA';
};

// Preview
function updatePreview() {
    if (!content) { placeholder.style.display = 'flex'; iframe.style.display = 'none'; return; }
    
    placeholder.style.display = 'none';
    iframe.style.display = 'block';
    
    const h = (previewLayout && showHeader) ? layoutHeader : '';
    const f = (previewLayout && showFooter) ? layoutFooter : '';
    
    const html = `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>* { margin: 0; padding: 0; box-sizing: border-box; } body { font-family: 'Inter', sans-serif; }
${customCss || ''}</style>
</head>
<body>
${h}
${content}
${f}
${customJs ? '<script>' + customJs + '<\/script>' : ''}
</body>
</html>`;
    
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
}

// ========================================
// SECTIONS - Parsing & Edition inline
// ========================================
function parseSections() {
    const list = $('sectionsList');
    
    const regex = /<section([^>]*)>([\s\S]*?)<\/section>/gi;
    const sections = [];
    let match;
    let idx = 0;
    
    while ((match = regex.exec(content)) !== null) {
        const attrs = match[1];
        const inner = match[2];
        
        let name = 'Section ' + (idx + 1);
        const nameMatch = attrs.match(/data-section-name=["']([^"']+)["']/i);
        if (nameMatch) name = nameMatch[1];
        else {
            const h1 = inner.match(/<h1[^>]*>([^<]+)/i);
            const h2 = inner.match(/<h2[^>]*>([^<]+)/i);
            const h3 = inner.match(/<h3[^>]*>([^<]+)/i);
            if (h1) name = h1[1].substring(0, 35);
            else if (h2) name = h2[1].substring(0, 35);
            else if (h3) name = h3[1].substring(0, 35);
        }
        
        sections.push({ idx, name, full: match[0], attrs, inner });
        idx++;
    }
    
    if (sections.length === 0) {
        list.innerHTML = `<div class="no-sections">
            <i class="fas fa-layer-group"></i>
            <p>Aucune section détectée</p>
            <small>Générez du contenu avec l'IA ou ajoutez des balises &lt;section&gt;</small>
        </div>`;
        return;
    }
    
    list.innerHTML = sections.map((s, i) => `
        <div class="section-item" data-idx="${i}" draggable="true">
            <div class="section-item-head">
                <i class="fas fa-grip-vertical grip"></i>
                <span class="name">${escHtml(s.name)}</span>
                <div class="actions">
                    <button class="btn-mini edit-inline" title="Éditer le texte" onclick="toggleInlineEdit(${i})">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="btn-mini code-toggle" title="Voir le code" onclick="toggleSectionCode(${i})">
                        <i class="fas fa-code"></i>
                    </button>
                    <button class="btn-mini del" title="Supprimer" onclick="deleteSection(${i})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-edit-zone" id="edit-zone-${i}">
                <div class="edit-toolbar">
                    <button onclick="execCmd('bold')" title="Gras"><i class="fas fa-bold"></i></button>
                    <button onclick="execCmd('italic')" title="Italique"><i class="fas fa-italic"></i></button>
                    <button onclick="execCmd('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
                    <div class="sep"></div>
                    <button onclick="execCmd('formatBlock', 'h1')" title="Titre 1">H1</button>
                    <button onclick="execCmd('formatBlock', 'h2')" title="Titre 2">H2</button>
                    <button onclick="execCmd('formatBlock', 'h3')" title="Titre 3">H3</button>
                    <button onclick="execCmd('formatBlock', 'p')" title="Paragraphe">P</button>
                    <div class="sep"></div>
                    <button onclick="execCmd('insertUnorderedList')" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
                    <button onclick="execCmd('insertOrderedList')" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
                    <div class="sep"></div>
                    <button onclick="execCmd('createLink', prompt('URL du lien:'))" title="Lien"><i class="fas fa-link"></i></button>
                    <button onclick="execCmd('removeFormat')" title="Supprimer format"><i class="fas fa-eraser"></i></button>
                </div>
                <div class="edit-content" id="edit-content-${i}" contenteditable="true" 
                     oninput="markSectionDirty(${i})">${extractEditableContent(s.inner)}</div>
                <div class="edit-actions">
                    <button class="btn btn-secondary btn-sm" onclick="cancelInlineEdit(${i})">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button class="btn btn-success btn-sm" onclick="applyInlineEdit(${i})">
                        <i class="fas fa-check"></i> Appliquer
                    </button>
                </div>
            </div>
            <div class="section-code" id="section-code-${i}">
                <textarea onchange="updateSectionCode(${i}, this.value)">${escHtml(s.full)}</textarea>
            </div>
        </div>
    `).join('');
    
    initDragDrop();
}

// Extraire le contenu éditable (texte visible)
function extractEditableContent(html) {
    // Créer un DOM temporaire pour extraire le contenu texte/structure
    const temp = document.createElement('div');
    temp.innerHTML = html;
    
    // Supprimer les scripts et styles
    temp.querySelectorAll('script, style').forEach(el => el.remove());
    
    // Garder la structure de base
    return temp.innerHTML;
}

function escHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Édition inline
let currentEditIdx = null;
let originalContent = {};

function toggleInlineEdit(idx) {
    const zone = $('edit-zone-' + idx);
    const btn = document.querySelector(`.section-item[data-idx="${idx}"] .edit-inline`);
    const item = document.querySelector(`.section-item[data-idx="${idx}"]`);
    
    // Fermer les autres zones d'édition
    document.querySelectorAll('.section-edit-zone.open').forEach(z => {
        if (z.id !== 'edit-zone-' + idx) {
            z.classList.remove('open');
            const otherIdx = z.id.replace('edit-zone-', '');
            document.querySelector(`.section-item[data-idx="${otherIdx}"]`)?.classList.remove('editing');
            document.querySelector(`.section-item[data-idx="${otherIdx}"] .edit-inline`)?.classList.remove('active');
        }
    });
    document.querySelectorAll('.section-code.open').forEach(c => c.classList.remove('open'));
    
    if (zone.classList.contains('open')) {
        zone.classList.remove('open');
        btn.classList.remove('active');
        item.classList.remove('editing');
        currentEditIdx = null;
    } else {
        // Sauvegarder le contenu original
        originalContent[idx] = $('edit-content-' + idx).innerHTML;
        zone.classList.add('open');
        btn.classList.add('active');
        item.classList.add('editing');
        currentEditIdx = idx;
        $('edit-content-' + idx).focus();
    }
}

function cancelInlineEdit(idx) {
    if (originalContent[idx]) {
        $('edit-content-' + idx).innerHTML = originalContent[idx];
    }
    toggleInlineEdit(idx);
    toast('Modifications annulées', 'error');
}

function markSectionDirty(idx) {
    const item = document.querySelector(`.section-item[data-idx="${idx}"]`);
    if (item) item.classList.add('editing');
}

function applyInlineEdit(idx) {
    const newHtml = $('edit-content-' + idx).innerHTML;
    
    // Remplacer le contenu de la section dans le HTML global
    const regex = /<section([^>]*)>([\s\S]*?)<\/section>/gi;
    let i = 0;
    content = content.replace(regex, (match, attrs, inner) => {
        if (i === idx) {
            // Essayer de préserver la structure (wrapper divs, etc.)
            // En reconstruisant avec le nouveau contenu
            const tempOld = document.createElement('div');
            tempOld.innerHTML = inner;
            
            const tempNew = document.createElement('div');
            tempNew.innerHTML = newHtml;
            
            // Remplacer le contenu textuel tout en gardant la structure
            const rebuilt = replaceTextContent(inner, newHtml);
            i++;
            return `<section${attrs}>${rebuilt}</section>`;
        }
        i++;
        return match;
    });
    
    htmlEditor.value = content;
    updatePreview();
    toggleInlineEdit(idx);
    toast('Section mise à jour !', 'success');
}

// Remplacer le contenu texte en préservant la structure HTML
function replaceTextContent(oldHtml, newContent) {
    // Pour une approche simple, on remplace directement
    // Une version plus avancée préserverait les classes/styles
    const container = document.createElement('div');
    container.innerHTML = oldHtml;
    
    // Trouver les éléments de contenu principaux
    const contentElements = container.querySelectorAll('h1, h2, h3, h4, p, li, span:not([class]), a');
    const newContainer = document.createElement('div');
    newContainer.innerHTML = newContent;
    const newElements = newContainer.querySelectorAll('h1, h2, h3, h4, p, li, span, a');
    
    // Mettre à jour chaque élément correspondant
    contentElements.forEach((el, i) => {
        if (newElements[i]) {
            el.innerHTML = newElements[i].innerHTML;
        }
    });
    
    return container.innerHTML || newContent;
}

// Commandes d'édition
function execCmd(cmd, value = null) {
    document.execCommand(cmd, false, value);
    if (currentEditIdx !== null) {
        $('edit-content-' + currentEditIdx).focus();
    }
}

// Code toggle
function toggleSectionCode(idx) {
    const code = $('section-code-' + idx);
    const edit = $('edit-zone-' + idx);
    
    // Fermer l'édition inline si ouverte
    if (edit.classList.contains('open')) {
        edit.classList.remove('open');
        document.querySelector(`.section-item[data-idx="${idx}"] .edit-inline`)?.classList.remove('active');
        document.querySelector(`.section-item[data-idx="${idx}"]`)?.classList.remove('editing');
    }
    
    code.classList.toggle('open');
}

function deleteSection(idx) {
    if (!confirm('Supprimer cette section ?')) return;
    
    const regex = /<section([^>]*)>[\s\S]*?<\/section>/gi;
    let i = 0;
    content = content.replace(regex, (m) => {
        if (i === idx) { i++; return ''; }
        i++;
        return m;
    });
    
    htmlEditor.value = content;
    updatePreview();
    parseSections();
    toast('Section supprimée', 'success');
}

function updateSectionCode(idx, newCode) {
    const regex = /<section([^>]*)>[\s\S]*?<\/section>/gi;
    let i = 0;
    content = content.replace(regex, (m) => {
        if (i === idx) { i++; return newCode; }
        i++;
        return m;
    });
    
    htmlEditor.value = content;
    updatePreview();
    toast('Code mis à jour', 'success');
}

// Drag & Drop
function initDragDrop() {
    const list = $('sectionsList');
    let dragged = null;
    
    list.querySelectorAll('.section-item').forEach(item => {
        item.ondragstart = function(e) {
            dragged = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        };
        
        item.ondragend = function() {
            this.classList.remove('dragging');
            dragged = null;
            reorderSections();
        };
        
        item.ondragover = function(e) {
            e.preventDefault();
            if (dragged && dragged !== this) {
                const rect = this.getBoundingClientRect();
                const mid = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    this.parentNode.insertBefore(dragged, this);
                } else {
                    this.parentNode.insertBefore(dragged, this.nextSibling);
                }
            }
        };
    });
}

function reorderSections() {
    const regex = /<section([^>]*)>[\s\S]*?<\/section>/gi;
    const sections = [];
    let match;
    while ((match = regex.exec(content)) !== null) {
        sections.push(match[0]);
    }
    
    const newOrder = [];
    $('sectionsList').querySelectorAll('.section-item').forEach(item => {
        const idx = parseInt(item.dataset.idx);
        newOrder.push(sections[idx]);
    });
    
    const before = content.split(/<section/i)[0] || '';
    const after = content.split(/<\/section>/i).pop() || '';
    
    content = before + newOrder.join('\n\n') + after;
    htmlEditor.value = content;
    updatePreview();
    parseSections();
    toast('Sections réorganisées', 'success');
}

// Editors sync
htmlEditor.oninput = function() { 
    content = this.value; 
    updatePreview(); 
    // Ne pas re-parser les sections à chaque frappe (performance)
};
htmlEditor.onblur = function() {
    parseSections();
};
cssEditor.oninput = function() { customCss = this.value; updatePreview(); };
jsEditor.oninput = function() { customJs = this.value; updatePreview(); };

// Save
$('saveBtn').onclick = () => save(false);
$('publishBtn').onclick = () => {
    if (status === 'published') save(false, 'draft');
    else save(true);
};

async function save(pub = false, forceStatus = null) {
    const btn = pub ? $('publishBtn') : $('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    $('statusDot').classList.add('saving');
    
    try {
        const payload = {
            page_id: pageId,
            content,
            custom_css: customCss,
            custom_js: customJs,
            show_header: showHeader ? 1 : 0,
            show_footer: showFooter ? 1 : 0
        };
        if (pub) payload.status = 'published';
        else if (forceStatus) payload.status = forceStatus;
        
        const r = await fetch('/admin/modules/pages/api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        
        if (d.success) {
            if (pub) status = 'published';
            else if (forceStatus) status = forceStatus;
            updateStatus();
            toast(pub ? 'Publié !' : 'Sauvegardé', 'success');
        } else throw new Error(d.error);
    } catch(e) {
        toast(e.message, 'error');
    }
    
    btn.disabled = false;
    btn.innerHTML = pub ? '<i class="fas fa-check"></i> ' + (status === 'published' ? 'Publié ✓' : 'Publier') : '<i class="fas fa-save"></i> Sauvegarder';
    $('statusDot').classList.remove('saving');
}

function updateStatus() {
    const dot = $('statusDot'), txt = $('statusText'), btn = $('publishBtn');
    if (status === 'published') {
        dot.classList.add('pub');
        txt.textContent = 'Publié';
        btn.innerHTML = '<i class="fas fa-check"></i> Publié ✓';
    } else {
        dot.classList.remove('pub');
        txt.textContent = 'Brouillon';
        btn.innerHTML = '<i class="fas fa-check"></i> Publier';
    }
}

function toast(msg, type = 'success') {
    const t = $('toast');
    t.className = 'toast ' + type + ' show';
    $('toastText').textContent = msg;
    t.querySelector('i').className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Keyboard shortcuts
document.onkeydown = function(e) {
    // Ctrl+S = Save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { 
        e.preventDefault(); 
        save(); 
    }
    // Escape = Close inline edit
    if (e.key === 'Escape' && currentEditIdx !== null) {
        cancelInlineEdit(currentEditIdx);
    }
};

console.log('🎨 Builder V4 initialisé - Ctrl+S: sauvegarder, Escape: fermer édition');