-- Script para criar a tabela checkin_tokens no banco existente
-- Execute este SQL no seu banco de dados MySQL/MariaDB

CREATE TABLE IF NOT EXISTS `checkin_tokens` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `token`      varchar(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at`    datetime DEFAULT NULL,
  `used_by`    int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  KEY `partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar se foi criada
DESCRIBE checkin_tokens;
