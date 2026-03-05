<?php
/**
 * Journal Facebook — Onglet journal integre au module Facebook
 * Fichier : admin/modules/facebook/tabs/journal.php
 *
 * REMPLACE l'ancien facebook/tabs/journal.php (V1)
 *
 * Inclusion dans facebook/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : facebook-journal → module facebook, tab journal
 */

$journal_channel       = 'facebook';
$journal_channel_label = 'Facebook';
$journal_channel_icon  = 'fab fa-facebook';
$journal_channel_color = '#1877f2';
$journal_create_url    = '?page=facebook&tab=rediger';

$journal_content_types = ['post-court', 'story', 'reel', 'video-script'];

include __DIR__ . '/../../journal/journal-widget.php';