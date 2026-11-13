<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create Levels Table
    $db->exec("
    CREATE TABLE IF NOT EXISTS `levels` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL,
      `level_number` int(11) NOT NULL,
      `min_points` int(11) NOT NULL,
      `max_points` int(11) NOT NULL,
      `icon` varchar(50) DEFAULT NULL,
      `color` varchar(20) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Insert Default Levels
    $db->exec("
    INSERT IGNORE INTO `levels` (`id`, `name`, `level_number`, `min_points`, `max_points`, `icon`, `color`) VALUES
    (1, 'Iniciante', 1, 0, 499, 'bi-star', '#cd7f32'),
    (2, 'Aventureiro', 2, 500, 1999, 'bi-star-half', '#c0c0c0'),
    (3, 'Fiel Escudeiro', 3, 2000, 4999, 'bi-star-fill', '#ffd700'),
    (4, 'VIP', 4, 5000, 9999, 'bi-gem', '#1e8b8b'),
    (5, 'Embaixador', 5, 10000, 999999, 'bi-trophy-fill', '#c9a961');
    ");

    // Create Badges Table
    $db->exec("
    CREATE TABLE IF NOT EXISTS `badges` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `description` text NOT NULL,
      `icon` varchar(50) NOT NULL,
      `category` varchar(50) NOT NULL,
      `requirement_type` varchar(50) NOT NULL,
      `requirement_value` int(11) NOT NULL,
      `points_reward` int(11) DEFAULT 0,
      `is_active` tinyint(1) DEFAULT 1,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create User Badges
    $db->exec("
    CREATE TABLE IF NOT EXISTS `user_badges` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `badge_id` int(11) NOT NULL,
      `awarded_at` timestamp DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_badge_unique` (`user_id`,`badge_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Tables created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
