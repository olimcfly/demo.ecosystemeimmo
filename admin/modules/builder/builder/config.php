<?php
// /admin/modules/builder-pages/config.php

return [
    'table' => 'pages',
    'list_page' => 'pages',
    'icon' => 'fas fa-file-alt',
    'label' => 'Pages',
    'title_field' => 'title',
    'slug_field' => 'slug',
    
    // Fonctionnalités
    'has_meta' => true,
    'has_header_footer' => true,
    'has_sections' => false,
    'has_steps' => false,
    'has_imports' => true,
    'has_css_js' => true,
    
    // Tabs disponibles
    'tabs' => [
        'content' => ['icon' => 'fas fa-code', 'label' => 'Contenu HTML'],
        'head' => ['icon' => 'fas fa-heading', 'label' => 'Meta SEO'],
        'imports' => ['icon' => 'fas fa-link', 'label' => 'Imports CSS/JS'],
        'layout' => ['icon' => 'fas fa-layer-group', 'label' => 'Header & Footer'],
        'css' => ['icon' => 'fas fa-palette', 'label' => 'CSS'],
        'js' => ['icon' => 'fas fa-bolt', 'label' => 'JavaScript'],
        'ai' => ['icon' => 'fas fa-wand-magic-sparkles', 'label' => 'IA Claude'],
        'settings' => ['icon' => 'fas fa-cog', 'label' => 'Paramètres'],
    ],
    
    // Presets IA
    'ai_presets' => [
        'hero' => 'Génère une section hero moderne pour agence immobilière...',
        'services' => 'Génère 4 cartes services: Achat, Vente, Location, Estimation...',
        'cta' => 'Génère CTA percutante estimation gratuite...',
        'faq' => 'Génère FAQ accordéon 5 questions immobilier...',
    ]
];
?>