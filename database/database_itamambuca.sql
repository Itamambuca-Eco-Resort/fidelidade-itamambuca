-- Estrutura de Banco de Dados: Fidelidade Itamambuca Eco Resort
-- Adaptado a partir da base do Santour Guide

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-03:00";

-- --------------------------------------------------------
-- Tabela de Usuários (Administradores, Hóspedes e Staff)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','partner','tourist') NOT NULL DEFAULT 'tourist',
  `google_id` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Categorias (Áreas do Resort: SPA, Lazer, Gastronomia)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserções Iniciais: Áreas do Resort
INSERT INTO `categories` (`name`, `slug`, `icon`, `description`) VALUES
('Acomodações', 'hospedagem', 'bi-house-door', 'Vilas e Chalezinhos do resort.'),
('Restaurantes', 'gastronomia', 'bi-cup-hot', 'Restaurante Taioba e Bares.'),
('Boutique', 'boutique', 'bi-bag', 'Lojinha e Boutique Itamambuca.'),
('SPA', 'spa', 'bi-heart-pulse', 'Armazém SPA e Bem Estar.'),
('Lazer & Esporte', 'lazer', 'bi-tree', 'Tênis, Quadras, Caiaque e Standup Paddle.'),
('Experiências', 'experiencias', 'bi-camera', 'Trilhas e Guias Locais.');

-- --------------------------------------------------------
-- Tabela de Atividades/Atrações (Antigos Parceiros)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `short_description` varchar(200) NOT NULL,
  `long_description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `discount_percentage` int(11) DEFAULT 0,
  `discount_rules` text DEFAULT NULL,
  `main_photo` varchar(255) DEFAULT NULL,
  `qr_code` varchar(100) NOT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `qr_code` (`qr_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sistema de Gamificação: Pontos e Missões
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_points` (
  `user_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `current_level` int(11) DEFAULT 1,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `point_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL, -- ex: checkin, mission_complete, welcome_bonus, reward_redeem
  `description` varchar(255) NOT NULL,
  `reference_id` int(11) DEFAULT NULL, -- ID do parceiro ou da missão
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `check_ins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `points_earned` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0, -- Se o QR Code foi usado
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Boutique / Recompensas do Resort
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partner_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL, -- pode apontar para a "Boutique" (parceiro específico)
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `points_cost` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reward_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `redemption_code` varchar(20) NOT NULL,
  `points_spent` int(11) NOT NULL,
  `status` enum('pending','used','cancelled') DEFAULT 'pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `redemption_code` (`redemption_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tokens de Check-in Dinâmico (gerado pelo colaborador após a atividade)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checkin_tokens` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `token`      varchar(64) NOT NULL,
  `created_by` int(11) NOT NULL,        -- user_id do colaborador/admin
  `expires_at` datetime NOT NULL,       -- válido por 5 minutos
  `used_at`    datetime DEFAULT NULL,
  `used_by`    int(11) DEFAULT NULL,    -- user_id do hóspede que usou
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  KEY `partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

