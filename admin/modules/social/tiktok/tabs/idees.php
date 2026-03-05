<?php
/**
 * ══════════════════════════════════════════════════════════════
 * REDIRECTION — tiktok/tabs/idees.php
 * Fichier : admin/modules/tiktok/tabs/idees.php
 * ══════════════════════════════════════════════════════════════
 *
 * INSTRUCTIONS :
 *   1. Renommer l'ancien fichier : idees.php → idees.php.bak
 *      (le script setup-journal-v3.sh l'a deja fait normalement)
 *   2. Creer un nouveau idees.php avec ce contenu
 *
 * L'ancien fichier idees.php (V1) est remplace par une
 * redirection vers le journal TikTok V3.
 * ══════════════════════════════════════════════════════════════
 */

// Redirection vers le journal TikTok V3
header('Location: ?page=tiktok-journal');
exit;