-- ============================================================
-- MODULE : Headers & Footers (headers-footers)
-- Fichier : headers-footers.sql
-- Généré le : 2026-02-12
-- Tables existantes : 2
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : headers
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `headers`
--

CREATE TABLE IF NOT EXISTS `headers` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('standard','sticky','transparent','minimal','mega-menu') DEFAULT 'standard',
  `status` enum('draft','active','inactive') DEFAULT 'draft',
  `is_default` tinyint(1) DEFAULT 0,
  `logo_url` varchar(500) DEFAULT NULL,
  `logo_alt` varchar(255) DEFAULT NULL,
  `logo_width` int(11) DEFAULT 150,
  `logo_link` varchar(500) DEFAULT '/',
  `menu_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`menu_items`)),
  `cta_enabled` tinyint(1) DEFAULT 1,
  `cta_text` varchar(100) DEFAULT 'Contact',
  `cta_link` varchar(500) DEFAULT '/contact',
  `cta_style` enum('primary','secondary','outline','gradient') DEFAULT 'primary',
  `cta2_enabled` tinyint(1) DEFAULT 0,
  `cta2_text` varchar(100) DEFAULT NULL,
  `cta2_link` varchar(500) DEFAULT NULL,
  `cta2_style` enum('primary','secondary','outline','gradient') DEFAULT 'secondary',
  `phone_enabled` tinyint(1) DEFAULT 1,
  `phone_number` varchar(50) DEFAULT NULL,
  `phone_icon` varchar(50) DEFAULT 'phone',
  `social_enabled` tinyint(1) DEFAULT 0,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `bg_color` varchar(50) DEFAULT '#ffffff',
  `text_color` varchar(50) DEFAULT '#1e293b',
  `hover_color` varchar(50) DEFAULT '#3b82f6',
  `height` int(11) DEFAULT 80,
  `sticky` tinyint(1) DEFAULT 1,
  `shadow` tinyint(1) DEFAULT 1,
  `border_bottom` tinyint(1) DEFAULT 0,
  `mobile_breakpoint` int(11) DEFAULT 1024,
  `mobile_menu_style` enum('slide-left','slide-right','fullscreen','dropdown') DEFAULT 'slide-right',
  `custom_html` text DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `builder_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`builder_content`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `logo_type` enum('text','image') DEFAULT 'image',
  `logo_text` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `headers`
--

INSERT INTO `headers` (`id`, `site_id`, `name`, `slug`, `type`, `status`, `is_default`, `logo_url`, `logo_alt`, `logo_width`, `logo_link`, `menu_items`, `cta_enabled`, `cta_text`, `cta_link`, `cta_style`, `cta2_enabled`, `cta2_text`, `cta2_link`, `cta2_style`, `phone_enabled`, `phone_number`, `phone_icon`, `social_enabled`, `social_links`, `bg_color`, `text_color`, `hover_color`, `height`, `sticky`, `shadow`, `border_bottom`, `mobile_breakpoint`, `mobile_menu_style`, `custom_html`, `custom_css`, `custom_js`, `builder_content`, `created_at`, `updated_at`, `logo_type`, `logo_text`, `content`) VALUES
(1, NULL, 'Header Principal', 'default', 'standard', '', 1, '', '', 150, '/', '[{\"label\":\"Accueil\",\"url\":\"\\/\"},{\"label\":\"Acheter\",\"url\":\"\\/acheter\"},{\"label\":\"Vendre\",\"url\":\"\\/vendre\"},{\"label\":\"Estimer\",\"url\":\"\\/estimation\"},{\"label\":\"Secteurs\",\"url\":\"https:\\/\\/eduardo-desul-immobilier.fr\\/secteurs\"},{\"label\":\"Blog\",\"url\":\"\\/blog\"}]', 1, 'Contact', '/contact', 'primary', 0, '', '', 'secondary', 0, '', 'phone', 0, '[]', '#ffffff', '#1e293b', '#3b82f6', 80, 1, 1, 0, 1024, 'slide-right', '', '', '', NULL, '2026-01-29 02:16:44', '2026-02-09 01:34:28', 'image', '', NULL),
(4, NULL, 'Header Défaut', 'header-default', 'standard', 'active', 1, NULL, NULL, 150, '/', NULL, 1, 'Contact', '/contact', 'primary', 0, NULL, NULL, 'secondary', 1, NULL, 'phone', 0, NULL, '#1e3a5f', '#ffffff', '#3b82f6', 80, 1, 1, 0, 1024, 'slide-right', NULL, NULL, NULL, NULL, '2026-02-04 23:48:21', '2026-02-05 00:02:21', 'image', NULL, NULL),
(21, NULL, 'Nouveau Header', 'header-1770595146', 'standard', 'draft', 0, NULL, NULL, 150, '/', '[]', 1, 'Contact', '/contact', 'primary', 0, NULL, NULL, 'secondary', 1, NULL, 'phone', 0, NULL, '#1e3a5f', '#ffffff', '#3b82f6', 80, 1, 1, 0, 1024, 'slide-right', NULL, NULL, NULL, NULL, '2026-02-08 23:59:06', '2026-02-08 23:59:06', 'image', NULL, NULL);


-- ------------------------------------------------------------
-- Table : footers
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `footers`
--

CREATE TABLE IF NOT EXISTS `footers` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `type` enum('standard','minimal','extended','mega','centered') DEFAULT 'standard',
  `status` enum('draft','active','inactive') DEFAULT 'draft',
  `is_default` tinyint(1) DEFAULT 0,
  `logo_enabled` tinyint(1) DEFAULT 1,
  `logo_url` varchar(500) DEFAULT NULL,
  `logo_alt` varchar(255) DEFAULT NULL,
  `logo_width` int(11) DEFAULT 120,
  `description` text DEFAULT NULL,
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  `contact_enabled` tinyint(1) DEFAULT 1,
  `contact_title` varchar(100) DEFAULT 'Contact',
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `horaires` text DEFAULT NULL,
  `social_enabled` tinyint(1) DEFAULT 1,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `newsletter_enabled` tinyint(1) DEFAULT 0,
  `newsletter_title` varchar(255) DEFAULT 'Newsletter',
  `newsletter_text` text DEFAULT NULL,
  `newsletter_placeholder` varchar(100) DEFAULT 'Votre email',
  `newsletter_button` varchar(50) DEFAULT 'S''inscrire',
  `copyright_text` varchar(500) DEFAULT NULL,
  `copyright_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`copyright_links`)),
  `legal_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`legal_links`)),
  `bg_color` varchar(50) DEFAULT '#1e293b',
  `text_color` varchar(50) DEFAULT '#94a3b8',
  `heading_color` varchar(50) DEFAULT '#ffffff',
  `link_color` varchar(50) DEFAULT '#cbd5e1',
  `link_hover_color` varchar(50) DEFAULT '#3b82f6',
  `border_top` tinyint(1) DEFAULT 0,
  `padding_top` int(11) DEFAULT 60,
  `padding_bottom` int(11) DEFAULT 40,
  `custom_html` text DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `builder_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`builder_content`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `content` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `footers`
--

INSERT INTO `footers` (`id`, `site_id`, `name`, `slug`, `company_name`, `contact_phone`, `contact_email`, `type`, `status`, `is_default`, `logo_enabled`, `logo_url`, `logo_alt`, `logo_width`, `description`, `columns`, `contact_enabled`, `contact_title`, `address`, `phone`, `email`, `horaires`, `social_enabled`, `social_links`, `newsletter_enabled`, `newsletter_title`, `newsletter_text`, `newsletter_placeholder`, `newsletter_button`, `copyright_text`, `copyright_links`, `legal_links`, `bg_color`, `text_color`, `heading_color`, `link_color`, `link_hover_color`, `border_top`, `padding_top`, `padding_bottom`, `custom_html`, `custom_css`, `custom_js`, `builder_content`, `created_at`, `updated_at`, `content`) VALUES
(1, NULL, 'Footer Principal', 'default', NULL, NULL, NULL, 'standard', 'active', 1, 1, '/assets/images/logo-white.png', NULL, 120, 'Votre partenaire immobilier de confiance à Bordeaux et sa métropole. Expertise locale, accompagnement personnalisé.', '[{\"title\":\"Navigation\",\"links\":[{\"label\":\"Accueil\",\"url\":\"\\/\"},{\"label\":\"Acheter\",\"url\":\"\\/acheter\"},{\"label\":\"Vendre\",\"url\":\"\\/vendre\"},{\"label\":\"Estimer\",\"url\":\"\\/estimation\"},{\"label\":\"Blog\",\"url\":\"\\/blog\"}]},{\"title\":\"Secteurs\",\"links\":[{\"label\":\"Chartrons\",\"url\":\"\\/secteurs\\/chartrons-bordeaux\"},{\"label\":\"Bacalan\",\"url\":\"\\/secteurs\\/bacalan\"},{\"label\":\"Talence\",\"url\":\"\\/secteurs\\/talence\"},{\"label\":\"Pessac\",\"url\":\"\\/secteurs\\/pessac\"}]},{\"title\":\"Ressources\",\"links\":[{\"label\":\"Guide acheteur\",\"url\":\"\\/guides\\/acheteur\"},{\"label\":\"Guide vendeur\",\"url\":\"\\/guides\\/vendeur\"},{\"label\":\"Simulateur crédit\",\"url\":\"\\/simulation\"},{\"label\":\"FAQ\",\"url\":\"\\/faq\"}]}]', 1, 'Contact', '123 Rue de la République, 33000 Bordeaux', '06 00 00 00 00', 'contact@example.com', 'Lun-Ven: 9h-19h | Sam: 10h-17h', 1, '[{\"platform\":\"facebook\",\"url\":\"#\"},{\"platform\":\"instagram\",\"url\":\"#\"},{\"platform\":\"linkedin\",\"url\":\"#\"},{\"platform\":\"youtube\",\"url\":\"#\"}]', 0, 'Newsletter', NULL, 'Votre email', 'S\'inscrire', '© 2026 Immo Local+. Tous droits réservés.', NULL, '[{\"label\":\"Mentions légales\",\"url\":\"\\/mentions-legales\"},{\"label\":\"Politique de confidentialité\",\"url\":\"\\/confidentialite\"},{\"label\":\"CGU\",\"url\":\"\\/cgu\"}]', '#041a4e', '#94a3b8', '#ffffff', '#cbd5e1', '#3b82f6', 0, 60, 40, '', '', '', NULL, '2026-01-29 02:16:44', '2026-02-04 16:41:38', NULL),
(2, NULL, 'Footer Minimal', 'minimal', NULL, NULL, NULL, 'minimal', 'active', 0, 0, NULL, NULL, 120, NULL, NULL, 0, 'Contact', NULL, NULL, NULL, NULL, 1, '[{\"platform\":\"facebook\",\"url\":\"#\",\"icon\":\"fab fa-facebook-f\"},{\"platform\":\"instagram\",\"url\":\"#\",\"icon\":\"fab fa-instagram\"}]', 0, 'Newsletter', NULL, 'Votre email', 'S\'inscrire', '© 2026 Immo Local+', NULL, '[{\"label\":\"Mentions légales\",\"url\":\"\\/mentions-legales\"},{\"label\":\"Confidentialité\",\"url\":\"\\/confidentialite\"}]', '#1e293b', '#94a3b8', '#ffffff', '#cbd5e1', '#3b82f6', 0, 30, 30, NULL, NULL, NULL, NULL, '2026-01-29 02:16:44', '2026-01-29 02:16:44', NULL),
(3, NULL, 'Footer Newsletter', 'newsletter', NULL, NULL, NULL, 'extended', 'active', 0, 1, '/assets/images/logo-white.png', NULL, 120, 'Restez informé des dernières opportunités immobilières.', '[{\"title\":\"Liens utiles\",\"links\":[{\"label\":\"Accueil\",\"url\":\"\\/\"},{\"label\":\"Services\",\"url\":\"\\/services\"},{\"label\":\"Contact\",\"url\":\"\\/contact\"}]}]', 0, 'Contact', NULL, NULL, NULL, NULL, 1, '[{\"platform\":\"facebook\",\"url\":\"#\",\"icon\":\"fab fa-facebook-f\"},{\"platform\":\"instagram\",\"url\":\"#\",\"icon\":\"fab fa-instagram\"}]', 1, 'Newsletter', 'Recevez nos meilleures offres directement dans votre boîte mail.', 'Votre email', 'S\'inscrire', '© 2026 Immo Local+', NULL, '[{\"label\":\"Mentions légales\",\"url\":\"\\/mentions-legales\"}]', '#1e293b', '#94a3b8', '#ffffff', '#cbd5e1', '#3b82f6', 0, 60, 40, NULL, NULL, NULL, NULL, '2026-01-29 02:16:44', '2026-01-29 02:16:44', NULL),
(4, NULL, 'Footer Défaut', 'footer-default', 'Eduardo De Sul - Immobilier Bordeaux', NULL, 'contact@example.com', 'standard', 'active', 1, 1, NULL, NULL, 120, NULL, NULL, 1, 'Contact', NULL, NULL, NULL, NULL, 1, NULL, 0, 'Newsletter', NULL, 'Votre email', 'S\'inscrire', NULL, NULL, NULL, '#1e3a5f', '#ffffff', '#ffffff', '#cbd5e1', '#3b82f6', 0, 60, 40, '<footer style=\"padding: 40px 0; background: #1e3a5f; color: white; text-align: center;\">\r\n        <div style=\"max-width: 1200px; margin: 0 auto; padding: 0 20px;\">\r\n            <p style=\"margin-bottom: 10px;\">© Eduardo De Sul - Conseiller Immobilier</p>\r\n            <p style=\"opacity: 0.8; font-size: 0.9em;\">Bordeaux, Gironde • Tous droits réservés</p>\r\n        </div>\r\n    </footer>', NULL, NULL, NULL, '2026-02-05 00:02:21', '2026-02-05 00:02:21', NULL),
(5, NULL, 'Nouveau Footer', 'footer-1770596053', NULL, NULL, NULL, 'standard', 'draft', 0, 1, NULL, NULL, 120, NULL, '[]', 1, 'Contact', NULL, NULL, NULL, NULL, 1, '[]', 0, 'Newsletter', NULL, 'Votre email', 'S\'inscrire', NULL, NULL, NULL, '#1e3a5f', '#ffffff', '#ffffff', '#cbd5e1', '#3b82f6', 0, 60, 40, NULL, NULL, NULL, NULL, '2026-02-09 00:14:13', '2026-02-09 00:14:13', NULL),
(6, NULL, 'Nouveau Footer', 'footer-1770596168', NULL, NULL, NULL, 'standard', 'draft', 0, 1, NULL, NULL, 120, NULL, '[]', 1, 'Contact', NULL, NULL, NULL, NULL, 1, '[]', 0, 'Newsletter', NULL, 'Votre email', 'S\'inscrire', NULL, NULL, NULL, '#1e3a5f', '#ffffff', '#ffffff', '#cbd5e1', '#3b82f6', 0, 60, 40, NULL, NULL, NULL, NULL, '2026-02-09 00:16:08', '2026-02-09 00:16:08', NULL);


