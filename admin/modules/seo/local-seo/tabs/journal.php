<?php
/**
 * Journal GMB — Onglet journal integre au module Local SEO
 * Fichier : admin/modules/local-seo/tabs/journal.php
 *
 * Inclusion dans local-seo/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : local-gmb-journal → module local-seo, tab journal
 */

$journal_channel       = 'gmb';
$journal_channel_label = 'Google My Business';
$journal_channel_icon  = 'fas fa-map-marker-alt';
$journal_channel_color = '#4285f4';
$journal_create_url    = '?page=local-seo&tab=publications&action=create';

$journal_content_types = ['fiche-gmb', 'post-court'];

include __DIR__ . '/../../journal/journal-widget.php';