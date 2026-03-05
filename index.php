<?php
/**
 * INDEX.PHP - PAGE D'ACCUEIL
 * ========================================================================
 * Redirige vers le routeur principal (front/page.php)
 * pour que l'accueil utilise EXACTEMENT le même header/footer
 * que toutes les autres pages du site.
 * 
 * AVANT : index.php avait son propre système de rendu → header différent
 * APRÈS : index.php passe par page.php → header identique partout
 * ========================================================================
 */

// ============================================
// MIDDLEWARE MAINTENANCE - CHARGER EN PREMIER
// ============================================
define('ROOT_PATH', __DIR__);

$maintenanceCheck = ROOT_PATH . '/includes/maintenance-check.php';
if (file_exists($maintenanceCheck)) {
    require_once $maintenanceCheck;
}

// ============================================
// SIMULER LE SLUG 'accueil' POUR LE ROUTEUR
// ============================================
// Le routeur page.php utilise REQUEST_URI pour déterminer le slug.
// Quand on est sur '/', le routeur met le slug à 'accueil'.
// On inclut simplement le routeur — il fera tout le travail.

$routerPath = __DIR__ . '/front/page.php';

if (file_exists($routerPath)) {
    // Le routeur détecte déjà que '/' → slug 'accueil'
    require $routerPath;
} else {
    // Fallback si le routeur n'existe pas encore
    // (ne devrait pas arriver en production)
    header('Location: /accueil');
    exit;
}