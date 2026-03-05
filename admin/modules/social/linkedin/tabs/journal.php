<?php
/**
 * Journal LinkedIn — Onglet journal integre au module LinkedIn
 * Fichier : admin/modules/linkedin/tabs/journal.php
 *
 * Module LinkedIn cree par setup-journal-v3.sh
 *
 * Inclusion dans linkedin/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : linkedin-journal → module linkedin, tab journal
 */

$journal_channel       = 'linkedin';
$journal_channel_label = 'LinkedIn';
$journal_channel_icon  = 'fab fa-linkedin';
$journal_channel_color = '#0a66c2';
$journal_create_url    = '?page=linkedin&tab=rediger';

$journal_content_types = ['post-court', 'article-pilier'];

include __DIR__ . '/../../journal/journal-widget.php';