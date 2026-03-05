<?php
/**
 * Initialisation Admin
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. D'abord les constantes globales
$root = dirname(__DIR__, 2);
$constants_file = $root . '/config/constants.php'; // ou config.php ?
if (file_exists($constants_file) && !defined('ROOT_PATH')) {
    require_once $constants_file;
}

// 2. Puis la DB (INCLUDES_PATH sera déjà défini)
require_once $root . '/config/database.php';

// 3. Auth check
$public_pages = ['login.php', 'diag-pages.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages)) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}