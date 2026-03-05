<?php
/**
 * Journal Instagram — Onglet journal integre au module Instagram
 * Fichier : admin/modules/instagram/tabs/journal.php
 *
 * Module Instagram cree par setup-journal-v3.sh
 *
 * Inclusion dans instagram/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : instagram-journal → module instagram, tab journal
 */

$journal_channel       = 'instagram';
$journal_channel_label = 'Instagram';
$journal_channel_icon  = 'fab fa-instagram';
$journal_channel_color = '#e4405f';
$journal_create_url    = '?page=instagram&tab=rediger';

$journal_content_types = ['post-court', 'story', 'reel'];

include __DIR__ . '/../../journal/journal-widget.php';