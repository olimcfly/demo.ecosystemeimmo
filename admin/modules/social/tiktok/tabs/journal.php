<?php
/**
 * Journal TikTok — Onglet journal integre au module TikTok
 * Fichier : admin/modules/tiktok/tabs/journal.php
 *
 * Inclusion dans tiktok/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : tiktok-journal → module tiktok, tab journal
 */

$journal_channel       = 'tiktok';
$journal_channel_label = 'TikTok';
$journal_channel_icon  = 'fab fa-tiktok';
$journal_channel_color = '#010101';
$journal_create_url    = '?page=tiktok&tab=scripts';

$journal_content_types = ['video-script', 'reel'];

include __DIR__ . '/../../journal/journal-widget.php';