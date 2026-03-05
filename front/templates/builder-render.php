<?php
/**
 * ========================================
 * RENDU FRONT-END DES PAGES BUILDER
 * ========================================
 * 
 * Fichier: /front/templates/builder-render.php
 * 
 * Affiche les pages créées avec le builder visuel
 * Inclure ce fichier et appeler renderBuilderPage($page)
 * 
 * ========================================
 */

/**
 * Rend une page complète du builder
 */
function renderBuilderPage($page) {
    if (!$page) return '';
    
    $html = '';
    
    // Hero
    $heroData = !empty($page['hero_data']) ? json_decode($page['hero_data'], true) : null;
    if ($heroData && !empty($heroData['title'])) {
        $html .= renderHeroSection($heroData);
    }
    
    // Sections
    $sections = !empty($page['sections_data']) ? json_decode($page['sections_data'], true) : [];
    foreach ($sections as $section) {
        $html .= renderSection($section);
    }
    
    // CTA final
    $ctaData = !empty($page['cta_data']) ? json_decode($page['cta_data'], true) : null;
    if ($ctaData && !empty($ctaData['title'])) {
        $html .= renderCtaSection($ctaData);
    }
    
    return $html;
}

/**
 * Rend le Hero
 */
function renderHeroSection($hero) {
    $style = $hero['style'] ?? 'centered';
    $bgImage = !empty($hero['image']) ? "background-image: url('{$hero['image']}');" : '';
    $bgStyle = !empty($hero['background']) ? $hero['background'] : 'gradient';
    
    $classes = "hero-section hero-{$style}";
    if ($bgStyle === 'gradient') $classes .= ' hero-gradient';
    if (!empty($bgImage)) $classes .= ' hero-image';
    
    $html = '<section class="' . $classes . '" style="' . $bgImage . '">';
    $html .= '<div class="hero-overlay"></div>';
    $html .= '<div class="hero-container">';
    $html .= '<div class="hero-content">';
    
    if (!empty($hero['subtitle_top'])) {
        $html .= '<span class="hero-subtitle-top">' . htmlspecialchars($hero['subtitle_top']) . '</span>';
    }
    
    $html .= '<h1>' . htmlspecialchars($hero['title'] ?? '') . '</h1>';
    
    if (!empty($hero['subtitle'])) {
        $html .= '<p class="hero-subtitle">' . htmlspecialchars($hero['subtitle']) . '</p>';
    }
    
    // Boutons
    if (!empty($hero['cta_primary']['text']) || !empty($hero['cta_secondary']['text'])) {
        $html .= '<div class="hero-buttons">';
        
        if (!empty($hero['cta_primary']['text'])) {
            $url = $hero['cta_primary']['url'] ?? '#';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-primary">' . htmlspecialchars($hero['cta_primary']['text']) . '</a>';
        }
        
        if (!empty($hero['cta_secondary']['text'])) {
            $url = $hero['cta_secondary']['url'] ?? '#';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-secondary">' . htmlspecialchars($hero['cta_secondary']['text']) . '</a>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';
    
    return $html;
}

/**
 * Rend une section
 */
function renderSection($section) {
    $type = $section['type'] ?? 'text';
    $id = !empty($section['cssId']) ? ' id="' . htmlspecialchars($section['cssId']) . '"' : '';
    $classes = 'section section-' . $type;
    if (!empty($section['cssClass'])) $classes .= ' ' . htmlspecialchars($section['cssClass']);
    if (!empty($section['hideOnMobile'])) $classes .= ' hide-mobile';
    
    $style = '';
    if (!empty($section['bgColor']) && $section['bgColor'] !== '#ffffff') {
        $style .= "background-color: {$section['bgColor']};";
    }
    if (!empty($section['paddingY'])) {
        $style .= "padding-top: {$section['paddingY']}px; padding-bottom: {$section['paddingY']}px;";
    }
    
    $styleAttr = $style ? ' style="' . $style . '"' : '';
    
    $html = '<section class="' . $classes . '"' . $id . $styleAttr . '>';
    $html .= '<div class="container">';
    
    switch ($type) {
        case 'text':
            $html .= renderTextSection($section);
            break;
        case 'text_image':
            $html .= renderTextImageSection($section);
            break;
        case 'features':
            $html .= renderFeaturesSection($section);
            break;
        case 'cards':
            $html .= renderCardsSection($section);
            break;
        case 'stats':
            $html .= renderStatsSection($section);
            break;
        case 'steps':
            $html .= renderStepsSection($section);
            break;
        case 'testimonials':
            $html .= renderTestimonialsSection($section);
            break;
        case 'cta':
            $html .= renderCtaSection($section);
            break;
        case 'accordion':
        case 'faq':
            $html .= renderAccordionSection($section);
            break;
        case 'contact_info':
            $html .= renderContactInfoSection($section);
            break;
        case 'contact_split':
            $html .= renderContactSplitSection($section);
            break;
        case 'form':
            $html .= renderFormSection($section);
            break;
        case 'gallery':
            $html .= renderGallerySection($section);
            break;
        case 'video':
            $html .= renderVideoSection($section);
            break;
        case 'map':
            $html .= renderMapSection($section);
            break;
        case 'divider':
            $html .= '<hr class="divider">';
            break;
        case 'spacer':
            $height = $section['height'] ?? 60;
            $html .= '<div class="spacer" style="height: ' . (int)$height . 'px;"></div>';
            break;
        default:
            $html .= '<!-- Section type non supporté: ' . htmlspecialchars($type) . ' -->';
    }
    
    $html .= '</div>';
    $html .= '</section>';
    
    return $html;
}

/**
 * Section Texte
 */
function renderTextSection($s) {
    $html = '';
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    $html .= '<div class="text-content">' . ($s['content'] ?? '') . '</div>';
    return $html;
}

/**
 * Section Texte + Image
 */
function renderTextImageSection($s) {
    $position = $s['imagePosition'] ?? 'right';
    $html = '<div class="text-image-grid position-' . $position . '">';
    
    $html .= '<div class="text-image-content">';
    if (!empty($s['title'])) {
        $html .= '<h2>' . htmlspecialchars($s['title']) . '</h2>';
    }
    $html .= '<div class="content">' . ($s['content'] ?? '') . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="text-image-visual">';
    if (!empty($s['image'])) {
        $html .= '<img src="' . htmlspecialchars($s['image']) . '" alt="' . htmlspecialchars($s['title'] ?? '') . '" loading="lazy">';
    }
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

/**
 * Section Caractéristiques
 */
function renderFeaturesSection($s) {
    $cols = $s['cols'] ?? 3;
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="features-grid cols-' . (int)$cols . '">';
    foreach (($s['items'] ?? []) as $item) {
        $html .= '<div class="feature-card">';
        if (!empty($item['icon'])) {
            $html .= '<div class="feature-icon">' . $item['icon'] . '</div>';
        }
        $html .= '<h3>' . htmlspecialchars($item['title'] ?? '') . '</h3>';
        $html .= '<p>' . htmlspecialchars($item['text'] ?? '') . '</p>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Cartes
 */
function renderCardsSection($s) {
    $cols = $s['cols'] ?? 3;
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="cards-grid cols-' . (int)$cols . '">';
    foreach (($s['items'] ?? []) as $item) {
        $html .= '<div class="card">';
        if (!empty($item['icon'])) {
            $html .= '<div class="card-icon">' . $item['icon'] . '</div>';
        }
        $html .= '<h3>' . htmlspecialchars($item['title'] ?? '') . '</h3>';
        $html .= '<p>' . htmlspecialchars($item['text'] ?? '') . '</p>';
        if (!empty($item['link'])) {
            $html .= '<a href="' . htmlspecialchars($item['link']) . '" class="card-link">En savoir plus →</a>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Statistiques
 */
function renderStatsSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="stats-grid">';
    foreach (($s['items'] ?? []) as $item) {
        $html .= '<div class="stat-item">';
        $html .= '<div class="stat-value">' . htmlspecialchars($item['value'] ?? '') . '</div>';
        $html .= '<div class="stat-label">' . htmlspecialchars($item['label'] ?? '') . '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Étapes
 */
function renderStepsSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="steps-grid">';
    $i = 1;
    foreach (($s['items'] ?? []) as $item) {
        $html .= '<div class="step-item">';
        $html .= '<div class="step-number">' . $i . '</div>';
        $html .= '<div class="step-content">';
        $html .= '<h3>' . htmlspecialchars($item['title'] ?? '') . '</h3>';
        $html .= '<p>' . htmlspecialchars($item['text'] ?? '') . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $i++;
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Témoignages
 */
function renderTestimonialsSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="testimonials-grid">';
    foreach (($s['items'] ?? []) as $item) {
        $html .= '<div class="testimonial-card">';
        $html .= '<div class="testimonial-quote">"</div>';
        $html .= '<p class="testimonial-text">' . htmlspecialchars($item['text'] ?? '') . '</p>';
        $html .= '<div class="testimonial-author">';
        $html .= '<strong>' . htmlspecialchars($item['author'] ?? '') . '</strong>';
        if (!empty($item['location'])) {
            $html .= '<span>' . htmlspecialchars($item['location']) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section CTA
 */
function renderCtaSection($s) {
    $style = $s['style'] ?? 'gradient';
    
    $html = '<div class="cta-block cta-' . $style . '">';
    $html .= '<h2>' . htmlspecialchars($s['title'] ?? '') . '</h2>';
    
    if (!empty($s['text'])) {
        $html .= '<p>' . htmlspecialchars($s['text']) . '</p>';
    }
    
    if (!empty($s['button_text'])) {
        $url = $s['button_url'] ?? '#contact';
        $html .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-cta">' . htmlspecialchars($s['button_text']) . '</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Accordéon / FAQ
 */
function renderAccordionSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="accordion">';
    $i = 0;
    foreach (($s['items'] ?? []) as $item) {
        $isOpen = $i === 0 ? ' open' : '';
        $html .= '<div class="accordion-item' . $isOpen . '">';
        $html .= '<button class="accordion-header">';
        $html .= '<span>' . htmlspecialchars($item['question'] ?? $item['title'] ?? '') . '</span>';
        $html .= '<svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"></polyline></svg>';
        $html .= '</button>';
        $html .= '<div class="accordion-body">';
        $html .= '<p>' . htmlspecialchars($item['answer'] ?? $item['text'] ?? '') . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $i++;
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Contact Info
 */
function renderContactInfoSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="contact-info-grid">';
    
    if (!empty($s['phone'])) {
        $html .= '<a href="tel:' . preg_replace('/[^0-9+]/', '', $s['phone']) . '" class="contact-item">';
        $html .= '<div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg></div>';
        $html .= '<span>' . htmlspecialchars($s['phone']) . '</span>';
        $html .= '</a>';
    }
    
    if (!empty($s['email'])) {
        $html .= '<a href="mailto:' . htmlspecialchars($s['email']) . '" class="contact-item">';
        $html .= '<div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></div>';
        $html .= '<span>' . htmlspecialchars($s['email']) . '</span>';
        $html .= '</a>';
    }
    
    if (!empty($s['address'])) {
        $html .= '<div class="contact-item">';
        $html .= '<div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></div>';
        $html .= '<span>' . htmlspecialchars($s['address']) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Contact Split (Infos + Formulaire)
 */
function renderContactSplitSection($s) {
    $html = '<div class="contact-split-grid">';
    
    // Colonne gauche: Infos
    $html .= '<div class="contact-split-info">';
    $html .= '<h3>Contactez-nous</h3>';
    
    if (!empty($s['phone'])) {
        $html .= '<div class="info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><span>' . htmlspecialchars($s['phone']) . '</span></div>';
    }
    if (!empty($s['email'])) {
        $html .= '<div class="info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg><span>' . htmlspecialchars($s['email']) . '</span></div>';
    }
    if (!empty($s['address'])) {
        $html .= '<div class="info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><span>' . htmlspecialchars($s['address']) . '</span></div>';
    }
    if (!empty($s['hours'])) {
        $html .= '<div class="info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><span>' . htmlspecialchars($s['hours']) . '</span></div>';
    }
    
    $html .= '</div>';
    
    // Colonne droite: Formulaire
    $html .= '<div class="contact-split-form">';
    $html .= '<form class="contact-form" method="post" action="/api/contact.php">';
    $html .= '<div class="form-row">';
    $html .= '<div class="form-group"><input type="text" name="name" placeholder="Votre nom *" required></div>';
    $html .= '<div class="form-group"><input type="email" name="email" placeholder="Votre email *" required></div>';
    $html .= '</div>';
    $html .= '<div class="form-group"><input type="tel" name="phone" placeholder="Votre téléphone"></div>';
    $html .= '<div class="form-group"><textarea name="message" rows="4" placeholder="Votre message *" required></textarea></div>';
    $html .= '<button type="submit" class="btn btn-primary btn-block">Envoyer le message</button>';
    $html .= '</form>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Formulaire
 */
function renderFormSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<form class="builder-form" method="post" action="/api/contact.php">';
    
    $fields = $s['fields'] ?? ['name', 'email', 'phone', 'message'];
    
    foreach ($fields as $field) {
        $field = strtolower($field);
        switch ($field) {
            case 'name':
            case 'nom':
                $html .= '<div class="form-group"><input type="text" name="name" placeholder="Votre nom *" required></div>';
                break;
            case 'email':
                $html .= '<div class="form-group"><input type="email" name="email" placeholder="Votre email *" required></div>';
                break;
            case 'phone':
            case 'telephone':
            case 'tel':
                $html .= '<div class="form-group"><input type="tel" name="phone" placeholder="Votre téléphone"></div>';
                break;
            case 'message':
                $html .= '<div class="form-group"><textarea name="message" rows="5" placeholder="Votre message *" required></textarea></div>';
                break;
            case 'subject':
            case 'sujet':
                $html .= '<div class="form-group"><input type="text" name="subject" placeholder="Sujet"></div>';
                break;
        }
    }
    
    $btnText = $s['button_text'] ?? 'Envoyer';
    $html .= '<button type="submit" class="btn btn-primary">' . htmlspecialchars($btnText) . '</button>';
    $html .= '</form>';
    
    return $html;
}

/**
 * Section Galerie
 */
function renderGallerySection($s) {
    $cols = $s['cols'] ?? 3;
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $html .= '<div class="gallery-grid cols-' . (int)$cols . '">';
    foreach (($s['images'] ?? []) as $image) {
        $src = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
        $alt = is_array($image) ? ($image['alt'] ?? '') : '';
        if ($src) {
            $html .= '<a href="' . htmlspecialchars($src) . '" class="gallery-item" data-lightbox="gallery">';
            $html .= '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy">';
            $html .= '</a>';
        }
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Section Vidéo
 */
function renderVideoSection($s) {
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    $url = $s['url'] ?? '';
    
    if ($url) {
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
            $html .= '<div class="video-wrapper">';
            $html .= '<iframe src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
            $html .= '</div>';
        }
        // Vimeo
        elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $videoId = $matches[1];
            $html .= '<div class="video-wrapper">';
            $html .= '<iframe src="https://player.vimeo.com/video/' . $videoId . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
            $html .= '</div>';
        }
        // URL directe
        else {
            $html .= '<div class="video-wrapper">';
            $html .= '<video controls><source src="' . htmlspecialchars($url) . '"></video>';
            $html .= '</div>';
        }
    }
    
    return $html;
}

/**
 * Section Map
 */
function renderMapSection($s) {
    $address = $s['address'] ?? '';
    $zoom = $s['zoom'] ?? 15;
    
    $html = '';
    
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-title">' . htmlspecialchars($s['title']) . '</h2>';
    }
    
    if ($address) {
        $encodedAddress = urlencode($address);
        $html .= '<div class="map-wrapper">';
        $html .= '<iframe src="https://maps.google.com/maps?q=' . $encodedAddress . '&z=' . (int)$zoom . '&output=embed" frameborder="0" allowfullscreen loading="lazy"></iframe>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * CSS pour le rendu front-end
 */
function getBuilderFrontendCSS() {
    return <<<CSS
/* ========================================
   BUILDER FRONTEND CSS
   ======================================== */

/* Variables */
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --secondary: #8b5cf6;
    --text: #334155;
    --text-light: #64748b;
    --bg: #f8fafc;
    --white: #ffffff;
    --border: #e2e8f0;
    --radius: 12px;
}

/* Sections */
.section {
    padding: 80px 0;
}

.section-bg {
    background: var(--bg);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.section-title {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 48px;
    color: #1e293b;
}

/* Hero */
.hero-section {
    position: relative;
    padding: 120px 20px;
    color: white;
    background-size: cover;
    background-position: center;
}

.hero-gradient {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
}

.hero-image .hero-overlay {
    background: rgba(0,0,0,0.5);
}

.hero-container {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
}

.hero-centered {
    text-align: center;
}

.hero-content h1 {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 32px;
}

.hero-subtitle-top {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 14px;
    margin-bottom: 20px;
}

.hero-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.hero-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    text-align: left;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.hero-section .btn-primary {
    background: white;
    color: var(--primary);
}

.btn-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.3);
}

.btn-block {
    width: 100%;
}

.btn-cta {
    background: white;
    color: var(--primary);
    padding: 16px 36px;
    font-size: 18px;
}

/* Features */
.features-grid {
    display: grid;
    gap: 30px;
}

.features-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.features-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.features-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

.feature-card {
    text-align: center;
    padding: 40px 30px;
    background: var(--bg);
    border-radius: var(--radius);
    transition: all 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 12px;
    color: #1e293b;
}

.feature-card p {
    color: var(--text-light);
    line-height: 1.6;
}

/* Cards */
.cards-grid {
    display: grid;
    gap: 30px;
}

.cards-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.cards-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.cards-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 40px 30px;
    text-align: center;
    transition: all 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border-color: transparent;
}

.card-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.card h3 {
    font-size: 22px;
    margin-bottom: 12px;
}

.card p {
    color: var(--text-light);
    margin-bottom: 20px;
}

.card-link {
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
}

.card-link:hover {
    text-decoration: underline;
}

/* Stats */
.stats-grid {
    display: flex;
    justify-content: center;
    gap: 80px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 56px;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 16px;
    color: var(--text-light);
}

/* Steps */
.steps-grid {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.step-item {
    flex: 1;
    min-width: 220px;
    max-width: 280px;
    text-align: center;
}

.step-number {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    margin: 0 auto 20px;
}

.step-content h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.step-content p {
    color: var(--text-light);
}

/* Testimonials */
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
}

.testimonial-card {
    background: var(--bg);
    border-radius: var(--radius);
    padding: 40px;
    position: relative;
}

.testimonial-quote {
    position: absolute;
    top: 20px;
    left: 30px;
    font-size: 80px;
    color: var(--primary);
    opacity: 0.2;
    font-family: Georgia, serif;
    line-height: 1;
}

.testimonial-text {
    font-size: 18px;
    line-height: 1.7;
    color: var(--text);
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.testimonial-author strong {
    display: block;
    font-size: 16px;
    color: #1e293b;
}

.testimonial-author span {
    color: var(--text-light);
    font-size: 14px;
}

/* CTA */
.cta-block {
    padding: 80px 60px;
    border-radius: var(--radius);
    text-align: center;
}

.cta-gradient {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.cta-simple {
    background: var(--bg);
}

.cta-bordered {
    border: 2px solid var(--primary);
}

.cta-block h2 {
    font-size: 36px;
    margin-bottom: 16px;
}

.cta-block p {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 32px;
}

.cta-simple .btn-cta,
.cta-bordered .btn-cta {
    background: var(--primary);
    color: white;
}

/* Accordion */
.accordion {
    max-width: 800px;
    margin: 0 auto;
}

.accordion-item {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 12px;
    overflow: hidden;
}

.accordion-header {
    width: 100%;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg);
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    text-align: left;
    transition: background 0.2s;
}

.accordion-header:hover {
    background: var(--border);
}

.accordion-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.3s;
}

.accordion-item.open .accordion-icon {
    transform: rotate(180deg);
}

.accordion-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.accordion-item.open .accordion-body {
    max-height: 500px;
}

.accordion-body p {
    padding: 20px 24px;
    line-height: 1.7;
    color: var(--text);
}

/* Contact Info */
.contact-info-grid {
    display: flex;
    justify-content: center;
    gap: 60px;
    flex-wrap: wrap;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: var(--text);
    transition: color 0.2s;
}

.contact-item:hover {
    color: var(--primary);
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-icon svg {
    width: 24px;
    height: 24px;
    stroke: white;
}

/* Contact Split */
.contact-split-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
}

.contact-split-info h3 {
    font-size: 28px;
    margin-bottom: 30px;
}

.contact-split-info .info-item {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    color: var(--text);
}

.contact-split-info svg {
    color: var(--primary);
    flex-shrink: 0;
}

/* Forms */
.contact-form,
.builder-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 16px 20px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 16px;
    font-family: inherit;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* Gallery */
.gallery-grid {
    display: grid;
    gap: 20px;
}

.gallery-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.gallery-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.gallery-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

.gallery-item {
    display: block;
    overflow: hidden;
    border-radius: var(--radius);
}

.gallery-item img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

/* Video */
.video-wrapper {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    border-radius: var(--radius);
    overflow: hidden;
}

.video-wrapper iframe,
.video-wrapper video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Map */
.map-wrapper {
    position: relative;
    padding-bottom: 50%;
    height: 0;
    border-radius: var(--radius);
    overflow: hidden;
}

.map-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

/* Text Image */
.text-image-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.text-image-grid.position-left {
    direction: rtl;
}

.text-image-grid.position-left > * {
    direction: ltr;
}

.text-image-content h2 {
    font-size: 32px;
    margin-bottom: 20px;
}

.text-image-content .content {
    color: var(--text);
    line-height: 1.7;
}

.text-image-visual img {
    width: 100%;
    border-radius: var(--radius);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

/* Utilities */
.divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 40px 0;
}

.text-content {
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.8;
}

.text-content h2 {
    font-size: 28px;
    margin-bottom: 20px;
}

.hide-mobile {
    display: block;
}

/* Responsive */
@media (max-width: 992px) {
    .features-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    .cards-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    .contact-split-grid { grid-template-columns: 1fr; }
    .text-image-grid { grid-template-columns: 1fr; }
    .hero-split { grid-template-columns: 1fr; text-align: center; }
}

@media (max-width: 768px) {
    .section { padding: 60px 0; }
    .section-title { font-size: 28px; margin-bottom: 36px; }
    .hero-content h1 { font-size: 36px; }
    .hero-subtitle { font-size: 18px; }
    .features-grid.cols-3,
    .cards-grid.cols-3 { grid-template-columns: 1fr; }
    .stats-grid { gap: 40px; }
    .stat-value { font-size: 42px; }
    .steps-grid { flex-direction: column; align-items: center; }
    .cta-block { padding: 50px 30px; }
    .cta-block h2 { font-size: 28px; }
    .form-row { grid-template-columns: 1fr; }
    .gallery-grid.cols-3,
    .gallery-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    .hide-mobile { display: none; }
}

/* Accordion JS */
<script>
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
        const item = header.parentElement;
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    });
});
</script>
CSS;
}