-- ============================================================
-- MODULE : Templates (templates)
-- Fichier : templates.sql
-- Généré le : 2026-02-12
-- Tables existantes : 1
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : templates
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `templates`
--

CREATE TABLE IF NOT EXISTS `templates` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `type` enum('page','landing','blog','article','secteur','contact','estimation','guide','portfolio','404','maintenance','header','footer') DEFAULT 'page',
  `category` varchar(100) DEFAULT 'general',
  `header_id` int(11) DEFAULT NULL,
  `footer_id` int(11) DEFAULT NULL,
  `sidebar_enabled` tinyint(1) DEFAULT 0,
  `sidebar_position` enum('left','right') DEFAULT 'right',
  `default_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_sections`)),
  `builder_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`builder_content`)),
  `default_meta_title` varchar(255) DEFAULT NULL,
  `default_meta_description` text DEFAULT NULL,
  `container_width` enum('full','boxed','narrow') DEFAULT 'boxed',
  `bg_color` varchar(50) DEFAULT '#ffffff',
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('draft','active','inactive') DEFAULT 'draft',
  `is_default` tinyint(1) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0,
  `usage_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `templates`
--

INSERT INTO `templates` (`id`, `site_id`, `name`, `content`, `slug`, `description`, `thumbnail`, `type`, `category`, `header_id`, `footer_id`, `sidebar_enabled`, `sidebar_position`, `default_sections`, `builder_content`, `default_meta_title`, `default_meta_description`, `container_width`, `bg_color`, `custom_css`, `custom_js`, `is_active`, `status`, `is_default`, `is_system`, `usage_count`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Page Standard', NULL, 'page-standard', 'Template de base pour les pages classiques', NULL, 'page', 'general', 1, 1, 0, 'right', '[{\"type\":\"hero\",\"title\":\"Titre de la page\"},{\"type\":\"content\",\"title\":\"Contenu principal\"},{\"type\":\"cta\",\"title\":\"Call to Action\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 1, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(2, NULL, 'Landing Page Conversion', NULL, 'landing-conversion', 'Template optimisé pour la conversion avec sections marketing', NULL, 'landing', 'marketing', 1, 1, 0, 'right', '[{\"type\":\"hero-landing\",\"title\":\"Hero Landing\"},{\"type\":\"features\",\"title\":\"Caract\\u00e9ristiques\"},{\"type\":\"testimonials\",\"title\":\"T\\u00e9moignages\"},{\"type\":\"pricing\",\"title\":\"Tarifs\"},{\"type\":\"faq\",\"title\":\"FAQ\"},{\"type\":\"cta\",\"title\":\"CTA Final\"}]', NULL, NULL, NULL, 'full', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(3, NULL, 'Page Secteur Immobilier', NULL, 'secteur-immobilier', 'Template pour pages de quartiers avec stats et biens', NULL, 'secteur', 'immobilier', 1, 1, 0, 'right', '[{\"type\":\"hero-secteur\",\"title\":\"Hero Secteur\"},{\"type\":\"presentation-quartier\",\"title\":\"Pr\\u00e9sentation\"},{\"type\":\"prix-immobilier\",\"title\":\"Prix du march\\u00e9\"},{\"type\":\"biens-grid\",\"title\":\"Biens disponibles\"},{\"type\":\"map-google\",\"title\":\"Carte\"},{\"type\":\"cta\",\"title\":\"Estimation gratuite\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(4, NULL, 'Article de Blog', NULL, 'article-blog', 'Template pour articles avec sidebar et auteur', NULL, 'article', 'blog', 1, 1, 1, 'right', '[{\"type\":\"article-header\",\"title\":\"En-t\\u00eate article\"},{\"type\":\"article-content\",\"title\":\"Contenu\"},{\"type\":\"author-box\",\"title\":\"Auteur\"},{\"type\":\"related-articles\",\"title\":\"Articles li\\u00e9s\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(5, NULL, 'Page Contact', NULL, 'page-contact', 'Template de contact avec formulaire et carte', NULL, 'contact', 'general', 1, 1, 0, 'right', '[{\"type\":\"hero\",\"title\":\"Contactez-nous\"},{\"type\":\"contact-form\",\"title\":\"Formulaire\"},{\"type\":\"contact-info\",\"title\":\"Coordonn\\u00e9es\"},{\"type\":\"map-google\",\"title\":\"Localisation\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(6, NULL, 'Page Estimation', NULL, 'page-estimation', 'Template pour estimation immobilière avec formulaire multi-étapes', NULL, 'estimation', 'immobilier', 1, 1, 0, 'right', '[{\"type\":\"hero-estimation\",\"title\":\"Estimez votre bien\"},{\"type\":\"estimation-form\",\"title\":\"Formulaire\"},{\"type\":\"features\",\"title\":\"Pourquoi nous ?\"},{\"type\":\"testimonials\",\"title\":\"Ils nous font confiance\"},{\"type\":\"faq\",\"title\":\"Questions fr\\u00e9quentes\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(7, NULL, 'Guide / Ressource', NULL, 'page-guide', 'Template pour guides avec table des matières', NULL, 'guide', 'ressources', 1, 1, 1, 'right', '[{\"type\":\"hero\",\"title\":\"Titre du guide\"},{\"type\":\"table-of-contents\",\"title\":\"Sommaire\"},{\"type\":\"content\",\"title\":\"Contenu\"},{\"type\":\"cta\",\"title\":\"T\\u00e9l\\u00e9charger\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(8, NULL, 'Liste Blog', NULL, 'blog-listing', 'Page de liste des articles du blog', NULL, 'blog', 'blog', 1, 1, 1, 'right', '[{\"type\":\"hero\",\"title\":\"Notre Blog\"},{\"type\":\"articles-grid\",\"title\":\"Articles\"},{\"type\":\"newsletter\",\"title\":\"Newsletter\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(9, NULL, 'Page 404', NULL, 'page-404', 'Page d\'erreur 404 personnalisée', NULL, '404', 'system', 1, 1, 0, 'right', '[{\"type\":\"404-content\",\"title\":\"Page non trouv\\u00e9e\"}]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(10, NULL, 'Page Vide (Builder)', NULL, 'blank', 'Template vierge pour construction libre', NULL, 'page', 'general', 1, 1, 0, 'right', '[]', NULL, NULL, NULL, 'boxed', '#ffffff', NULL, NULL, 1, 'active', 0, 1, 0, '2026-01-29 02:45:19', '2026-01-29 02:45:19'),
(11, NULL, 'Header Principal', '<div class=\"top-bar\"><div class=\"container\"><span>Bordeaux Metropole</span><span>Tel: 06 XX XX XX XX</span></div></div><header class=\"header\"><div class=\"container\"><a href=\"/\" class=\"header__logo\">Eduardo De Sul</a><nav class=\"header__nav\"><a href=\"/\">Accueil</a><a href=\"/a-propos\">A propos</a><a href=\"/estimation-gratuite\" class=\"header__btn\">Estimation gratuite</a><a href=\"/contact\">Contact</a></nav></div></header>', 'header', NULL, NULL, 'page', 'general', NULL, NULL, 0, 'right', NULL, NULL, NULL, NULL, 'boxed', '#ffffff', '.top-bar{background:#1e3a5f;color:#fff;padding:8px 0}.top-bar .container{display:flex;justify-content:space-between;max-width:1200px;margin:0 auto;padding:0 20px}.header{background:#1e3a5f;padding:15px 0;position:sticky;top:0;z-index:1000}.header .container{display:flex;justify-content:space-between;align-items:center;max-width:1200px;margin:0 auto;padding:0 20px}.header__logo{color:#fff;text-decoration:none;font-size:1.5rem;font-weight:700}.header__nav{display:flex;gap:30px}.header__nav a{color:#fff;text-decoration:none}.header__btn{background:#d35400;padding:10px 20px;border-radius:6px}', NULL, 1, 'draft', 0, 0, 0, '2026-02-03 05:08:34', '2026-02-03 05:08:34'),
(12, NULL, 'Footer Principal', '<footer class=\"footer\"><div class=\"container\"><div class=\"footer__grid\"><div><h3>Eduardo De Sul</h3><p>Conseiller immobilier</p></div><div><h4>Contact</h4><p>Bordeaux</p><p>06 XX XX XX XX</p></div></div><div class=\"footer__bottom\"><p>© 2025 Tous droits reserves</p></div></div></footer>', 'footer', NULL, NULL, 'page', 'general', NULL, NULL, 0, 'right', NULL, NULL, NULL, NULL, 'boxed', '#ffffff', '.footer{background:#1e3a5f;color:#fff;padding:60px 0 0}.footer .container{max-width:1200px;margin:0 auto;padding:0 20px}.footer__grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;padding-bottom:40px}.footer__bottom{padding:25px 0;text-align:center;border-top:1px solid rgba(255,255,255,.1)}', NULL, 1, 'draft', 0, 0, 0, '2026-02-03 05:08:53', '2026-02-03 05:08:53'),
(13, NULL, 'Header Principal', '<div class=\"top-bar\"><div class=\"container\"><span>Bordeaux Metropole - Conseiller immobilier eXp France</span><span>Tel: 06 XX XX XX XX</span></div></div><header class=\"header\"><div class=\"container\"><a href=\"/\" class=\"header__logo\">Eduardo De Sul</a><nav class=\"header__nav\"><a href=\"/\">Accueil</a><a href=\"/a-propos\">A propos</a><a href=\"/estimation-gratuite\" class=\"header__btn\">Estimation gratuite</a><a href=\"/contact\">Contact</a></nav></div></header>', 'header-principal', NULL, NULL, 'header', 'general', NULL, NULL, 0, 'right', NULL, NULL, NULL, NULL, 'boxed', '#ffffff', '.top-bar{background:#1e3a5f;color:#fff;padding:8px 0}.top-bar .container{display:flex;justify-content:space-between;max-width:1200px;margin:0 auto;padding:0 20px}.header{background:#1e3a5f;padding:15px 0;position:sticky;top:0;z-index:1000}.header .container{display:flex;justify-content:space-between;align-items:center;max-width:1200px;margin:0 auto;padding:0 20px}.header__logo{color:#fff;text-decoration:none;font-size:1.5rem;font-weight:700}.header__nav{display:flex;gap:30px}.header__nav a{color:#fff;text-decoration:none}.header__btn{background:#d35400;padding:10px 20px;border-radius:6px}', NULL, 1, 'active', 0, 0, 0, '2026-02-03 05:10:22', '2026-02-03 05:10:22'),
(14, NULL, 'Footer Principal', '<footer class=\"footer\"><div class=\"container\"><div class=\"footer__grid\"><div><h3>Eduardo De Sul</h3><p>Conseiller immobilier independant</p></div><div><h4>Contact</h4><p>Bordeaux Metropole</p><p>06 XX XX XX XX</p></div></div><div class=\"footer__bottom\"><p>© 2025 Tous droits reserves</p></div></div></footer>', 'footer-principal', NULL, NULL, 'footer', 'general', NULL, NULL, 0, 'right', NULL, NULL, NULL, NULL, 'boxed', '#ffffff', '.footer{background:#1e3a5f;color:#fff;padding:60px 0 0}.footer .container{max-width:1200px;margin:0 auto;padding:0 20px}.footer__grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;padding-bottom:40px}.footer__bottom{padding:25px 0;text-align:center;border-top:1px solid rgba(255,255,255,.1)}', NULL, 1, 'active', 0, 0, 0, '2026-02-03 05:10:38', '2026-02-03 05:10:38');


