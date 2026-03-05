-- ============================================================
-- MODULE : Design du site (design)
-- Fichier : design.sql
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
-- Table : site_design
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `site_design`
--

CREATE TABLE IF NOT EXISTS `site_design` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `header_html` longtext DEFAULT NULL,
  `footer_html` longtext DEFAULT NULL,
  `global_css` longtext DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT '#1e3a5f',
  `secondary_color` varchar(20) DEFAULT '#2d4a6f',
  `font_family` varchar(100) DEFAULT 'Inter',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `site_design`
--

INSERT INTO `site_design` (`id`, `website_id`, `header_html`, `footer_html`, `global_css`, `primary_color`, `secondary_color`, `font_family`, `created_at`, `updated_at`) VALUES
(1, NULL, '<header style=\"background: linear-gradient(135deg, #1e3a5f 0%, #2d4a6f 100%); font-family: \'Inter\', system-ui, sans-serif;\">\r\n    <div style=\"background: rgba(0,0,0,0.2); padding: 8px 0; font-size: 13px;\">\r\n        <div style=\"max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; color: rgba(255,255,255,0.85);\">\r\n            <span>Bordeaux Metropole - Conseiller immobilier independant eXp France</span>\r\n            <span>Tel: 06 XX XX XX XX</span>\r\n        </div>\r\n    </div>\r\n    <div style=\"padding: 15px 0;\">\r\n        <div style=\"max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;\">\r\n            <a href=\"/\" style=\"color: white; text-decoration: none; font-size: 1.5rem; font-weight: 700;\">Eduardo De Sul</a>\r\n            <nav style=\"display: flex; gap: 25px; align-items: center;\">\r\n                <a href=\"/\" style=\"color: white; text-decoration: none; font-weight: 500;\">Accueil</a>\r\n                <a href=\"/a-propos\" style=\"color: white; text-decoration: none; font-weight: 500;\">A propos</a>\r\n                <a href=\"/secteurs\" style=\"color: white; text-decoration: none; font-weight: 500;\">Secteurs</a>\r\n                <a href=\"/estimation\" style=\"background: #f59e0b; color: white; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-weight: 600;\">Estimation gratuite</a>\r\n                <a href=\"/contact\" style=\"color: white; text-decoration: none; font-weight: 500;\">Contact</a>\r\n            </nav>\r\n        </div>\r\n    </div>\r\n</header>', '<footer style=\"background: #1e293b; color: white; padding: 60px 20px 30px; font-family: \'Inter\', system-ui, sans-serif;\">\r\n    <div style=\"max-width: 1100px; margin: 0 auto;\">\r\n        \r\n        <div style=\"display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 40px;\">\r\n            \r\n            <!-- Colonne 1 -->\r\n            <div style=\"flex: 1; min-width: 250px;\">\r\n                <h4 style=\"font-size: 1.2rem; margin: 0 0 15px; color: white;\">Eduardo De Sul</h4>\r\n                <p style=\"opacity: 0.75; line-height: 1.8; font-size: 14px; margin: 0;\">\r\n                    Conseiller immobilier indépendant eXp France.<br>\r\n                    Accompagnement personnalisé pour vendre ou acheter à Bordeaux Métropole.\r\n                </p>\r\n                <div style=\"margin-top: 20px; display: flex; gap: 10px;\">\r\n                    <a href=\"https://www.facebook.com/eduardodesulimmobilieriadfrance\" target=\"_blank\" style=\"display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.1); border-radius: 50%; color: white; text-decoration: none; font-size: 14px; font-weight: bold;\">f</a>\r\n                    <a href=\"https://maps.app.goo.gl/1a3nKTQdK6eSLpzL6\" target=\"_blank\" style=\"display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.1); border-radius: 50%; color: white; text-decoration: none; font-size: 14px; font-weight: bold;\">G</a>\r\n                    <a href=\"https://www.immodvisor.com/professionnels/Mandataire-immobilier/pro/exp-france-eduardo-de-sul-76256\" target=\"_blank\" style=\"display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.1); border-radius: 50%; color: white; text-decoration: none; font-size: 14px; font-weight: bold;\">*</a>\r\n                </div>\r\n            </div>\r\n            \r\n            <!-- Colonne 2 -->\r\n            <div style=\"flex: 1; min-width: 180px;\">\r\n                <h4 style=\"font-size: 1rem; margin: 0 0 15px; color: rgba(255,255,255,0.9);\">Navigation</h4>\r\n                <div style=\"display: flex; flex-direction: column; gap: 10px;\">\r\n                    <a href=\"/\" style=\"color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;\">Accueil</a>\r\n                    <a href=\"/a-propos\" style=\"color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;\">A propos</a>\r\n                    <a href=\"/secteurs\" style=\"color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;\">Secteurs Bordeaux</a>\r\n                    <a href=\"/estimation\" style=\"color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;\">Estimation gratuite</a>\r\n                    <a href=\"/contact\" style=\"color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;\">Contact</a>\r\n                </div>\r\n            </div>\r\n            \r\n            <!-- Colonne 3 -->\r\n            <div style=\"flex: 1; min-width: 220px;\">\r\n                <h4 style=\"font-size: 1rem; margin: 0 0 15px; color: rgba(255,255,255,0.9);\">Contact</h4>\r\n                <p style=\"opacity: 0.75; font-size: 14px; line-height: 2.2; margin: 0 0 15px;\">\r\n                    Bordeaux Metropole, France<br>\r\n                    Tel: 06 XX XX XX XX<br>\r\n                    Email: contact@eduardo-desul.fr\r\n                </p>\r\n                <a href=\"/estimation\" style=\"display: inline-block; padding: 10px 20px; background: #f59e0b; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;\">Demander une estimation</a>\r\n            </div>\r\n            \r\n        </div>\r\n        \r\n        <div style=\"border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px; text-align: center;\">\r\n            <p style=\"opacity: 0.5; font-size: 13px; margin: 0;\">\r\n                2026 Eduardo De Sul - Conseiller immobilier independant eXp France | \r\n                <a href=\"/mentions-legales\" style=\"color: rgba(255,255,255,0.5); text-decoration: none;\">Mentions legales</a> | \r\n                <a href=\"/politique-confidentialite\" style=\"color: rgba(255,255,255,0.5); text-decoration: none;\">Confidentialite</a>\r\n            </p>\r\n        </div>\r\n        \r\n    </div>\r\n</footer>', '', '#1e3a5f', '#2d4a6f', 'Inter', '2026-01-31 04:10:29', '2026-01-31 04:35:21');


