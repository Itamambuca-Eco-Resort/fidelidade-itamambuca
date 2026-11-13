<?php
/**
 * Script de Migração: Adicionar campos para Login com Google
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "<h1>Migração de Banco de Dados</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar/Adicionar google_id
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER role");
        echo "<p style='color:green;'>✅ Coluna 'google_id' adicionada com sucesso.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Coluna 'google_id' já existe.</p>";
    }
    
    // 2. Verificar/Adicionar avatar
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER google_id");
        echo "<p style='color:green;'>✅ Coluna 'avatar' adicionada com sucesso.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Coluna 'avatar' já existe.</p>";
    }

    // 3. Verificar/Adicionar campo 'image' em categories
    $stmt = $db->query("SHOW COLUMNS FROM categories LIKE 'image'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER icon");
        echo "<p style='color:green;'>✅ Coluna 'image' adicionada à tabela 'categories'.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Coluna 'image' já existe em 'categories'.</p>";
    }

    // 4. Adicionar campo 'qr_code' aos parceiros para check-in
    $stmt = $db->query("SHOW COLUMNS FROM partners LIKE 'qr_code'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE partners ADD COLUMN qr_code VARCHAR(255) DEFAULT NULL AFTER slug");
        echo "<p style='color:green;'>✅ Coluna 'qr_code' adicionada aos parceiros.</p>";
    }

    // 5. Criar tabela de prêmios (Recompensas)
    $db->exec("CREATE TABLE IF NOT EXISTS partner_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        points_cost INT NOT NULL,
        quantity_available INT DEFAULT -1, -- -1 para ilimitado
        image_url VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
    )");
    echo "<p style='color:green;'>✅ Tabela 'partner_rewards' criada/verificada.</p>";

    // 6. Criar tabela de resgates de prêmios
    $db->exec("CREATE TABLE IF NOT EXISTS reward_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NOT NULL,
        redemption_code VARCHAR(50) NOT NULL UNIQUE,
        status ENUM('pending', 'used', 'expired') DEFAULT 'pending',
        points_spent INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (reward_id) REFERENCES partner_rewards(id)
    )");
    echo "<p style='color:green;'>✅ Tabela 'reward_redemptions' criada/verificada.</p>";
    
    // 7. Criar tabela de cupons
    $db->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        code VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        discount_type ENUM('percentage', 'fixed') NOT NULL,
        discount_value DECIMAL(10,2) NOT NULL,
        valid_from DATE NOT NULL,
        valid_until DATE NOT NULL,
        max_uses INT DEFAULT NULL,
        current_uses INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
    )");
    echo "<p style='color:green;'>✅ Tabela 'coupons' criada/verificada.</p>";

    // 8. Criar tabela de resgates de cupons
    $db->exec("CREATE TABLE IF NOT EXISTS coupon_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        partner_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<p style='color:green;'>✅ Tabela 'coupon_redemptions' criada/verificada.</p>";

    // 9. Tabelas de Gamificação
    $db->exec("CREATE TABLE IF NOT EXISTS levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_number INT NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        min_points INT NOT NULL,
        max_points INT NOT NULL,
        icon VARCHAR(50),
        color VARCHAR(20)
    )");

    // Inserir níveis padrão se não existirem
    $stmt = $db->query("SELECT COUNT(*) FROM levels");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO levels (level_number, name, min_points, max_points, icon, color) VALUES 
            (1, 'Explorador', 0, 1000, '🌱', '#6366f1'),
            (2, 'Aventureiro', 1001, 3000, '🧭', '#8b5cf6'),
            (3, 'Viajante', 3001, 7000, '🎒', '#f59e0b'),
            (4, 'Guia', 7001, 15000, '🗺️', '#10b981'),
            (5, 'Lenda', 15001, 999999, '👑', '#ef4444')");
    }
    echo "<p style='color:green;'>✅ Tabela 'levels' criada e níveis iniciais inseridos.</p>";

    $db->exec("CREATE TABLE IF NOT EXISTS point_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        points INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT,
        reference_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS user_points (
        user_id INT PRIMARY KEY,
        total_points INT DEFAULT 0,
        current_level INT DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (current_level) REFERENCES levels(level_number)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS user_badges (
        user_id INT NOT NULL,
        badge_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, badge_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS check_ins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        partner_id INT NOT NULL,
        points_earned INT NOT NULL,
        comment TEXT,
        photo_url VARCHAR(255),
        verified TINYINT(1) DEFAULT 0,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS partner_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        user_id INT,
        view_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (partner_id, user_id, view_date),
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Criar View para Ranking se não existir
    $db->exec("CREATE OR REPLACE VIEW ranking_geral AS 
        SELECT u.id, u.name, up.total_points, up.current_level, l.name as level_name,
        (SELECT COUNT(*) + 1 FROM user_points up2 WHERE up2.total_points > up.total_points) as ranking_position
        FROM users u
        JOIN user_points up ON u.id = up.user_id
        JOIN levels l ON up.current_level = l.level_number");
    echo "<p style='color:green;'>✅ Tabelas extras de gamificação e View de Ranking criadas.</p>";

    // Criar View para Estatísticas se não existir
    $db->exec("CREATE OR REPLACE VIEW user_stats AS 
        SELECT user_id, 
        COUNT(*) as total_checkins,
        SUM(points_earned) as total_points_earned
        FROM check_ins
        GROUP BY user_id");
    echo "<p style='color:green;'>✅ View 'user_stats' criada.</p>";

    // Initialize user_points for existing users
    $db->exec("INSERT IGNORE INTO user_points (user_id, total_points, current_level) 
               SELECT id, 0, 1 FROM users");
    echo "<p style='color:green;'>✅ Pontos inicializados para usuários existentes.</p>";
    
    echo "<h3>Migração concluída!</h3>";
    echo "<a href='/auth/login.php'>Voltar para o Login</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro na migração: " . $e->getMessage() . "</p>";
}
