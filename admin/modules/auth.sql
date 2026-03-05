-- ============================================================
-- MODULE : Authentification (auth)
-- Fichier : auth.sql
-- Généré le : 2026-02-12
-- Tables existantes : 4
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : admins
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor') NOT NULL DEFAULT 'editor',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs administration';

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `avatar`, `last_login`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@eduardo-desul-immobilier.fr', '$argon2id$v=19$m=65536,t=4,p=1$Z2dUS0ZPSGxQUjJMLzh1Qw$91zJLtWyhvxqMnmc+vfOAtTZefWEUe9iYL60Q+ip660', 'admin', NULL, NULL, NULL, '2026-02-12 01:07:17', 1, '2026-01-13 02:57:47', '2026-02-12 01:07:17');


-- ------------------------------------------------------------
-- Table : users
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `role` varchar(50) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `created_at`, `role`) VALUES
(1, 'admin@eduardo-desul-immobilier.fr', 'a68abad94e648ac015672ac6be57a0d8', '2025-11-08 16:03:07', 'admin');


-- ------------------------------------------------------------
-- Table : login_codes
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `login_codes`
--

CREATE TABLE IF NOT EXISTS `login_codes` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(1) UNSIGNED DEFAULT 0,
  `used` tinyint(1) UNSIGNED DEFAULT 0,
  `created_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : rate_limits
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `rate_limits`
--

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL,
  `scope` varchar(50) NOT NULL,
  `key_value` varchar(191) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` datetime NOT NULL DEFAULT current_timestamp(),
  `blocked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `scope`, `key_value`, `attempts`, `last_attempt`, `blocked_until`) VALUES
(1, 'login_ip', '92.184.100.84', 1, '2025-11-13 00:30:40', NULL),
(2, 'login_ip', '92.184.112.141', 5, '2025-11-14 03:50:49', '2025-11-14 04:20:49');


