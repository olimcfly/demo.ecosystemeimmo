<?php
/**
 * Journal Emails — Onglet journal integre au module Emails
 * Fichier : admin/modules/emails/tabs/journal.php
 *
 * Inclusion dans emails/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : emails-journal → module emails, tab journal
 */

$journal_channel       = 'email';
$journal_channel_label = 'Emails & Sequences';
$journal_channel_icon  = 'fas fa-envelope';
$journal_channel_color = '#e74c3c';
$journal_create_url    = '?page=emails&action=create';

$journal_content_types = ['email', 'lead-magnet'];

include __DIR__ . '/../../journal/journal-widget.php';