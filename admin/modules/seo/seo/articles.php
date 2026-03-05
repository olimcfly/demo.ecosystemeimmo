<?php
/**
 * ============================================================
 * MODULE SEO DES ARTICLES — PROXY
 * ============================================================
 * Fichier : /admin/modules/seo/articles.php
 * Route   : dashboard.php?page=seo-articles
 * 
 * Charge articles/index.php qui détecte automatiquement
 * le contexte SEO via $_GET['page'] === 'seo-articles'
 * 
 * VERSION 4.0 — ÉCOSYSTÈME IMMO LOCAL+
 * ============================================================
 */
require_once __DIR__ . '/../articles/index.php';