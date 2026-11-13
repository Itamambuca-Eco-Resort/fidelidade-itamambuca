<?php
/**
 * Script de Migração 2: Tabelas de Gamificação
 * Executar: https://guia.santour.com.br/auth/migrate2.php
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Migração 2: Tabelas de Gamificação</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. levels
    $db->exec("CREATE TABLE IF NOT EXISTS levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_number INT NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        min_points INT NOT NULL,
        max_points INT NOT NULL,
        icon VARCHAR(50),
        color VARCHAR(20)
    )");
    echo "<p style='color:green;'>✅ Tabela 'levels' criada/verificada.</p>";

    // Garantir que as colunas icon e color existem (caso a tabela já existia com schema antigo)
    $cols = $db->query("SHOW COLUMNS FROM levels LIKE 'icon'")->rowCount();
    if ($cols == 0) {
        $db->exec("ALTER TABLE levels ADD COLUMN icon VARCHAR(50) DEFAULT NULL");
        echo "<p style='color:green;'>✅ Coluna 'icon' adicionada à tabela levels.</p>";
    }
    $cols = $db->query("SHOW COLUMNS FROM levels LIKE 'color'")->rowCount();
    if ($cols == 0) {
        $db->exec("ALTER TABLE levels ADD COLUMN color VARCHAR(20) DEFAULT NULL");
        echo "<p style='color:green;'>✅ Coluna 'color' adicionada à tabela levels.</p>";
    }

    // Inserir níveis padrão se não existirem
    $stmt = $db->query("SELECT COUNT(*) FROM levels");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO levels (level_number, name, min_points, max_points, icon, color) VALUES 
            (1, 'Explorador', 0, 1000, '🌱', '#6366f1'),
            (2, 'Aventureiro', 1001, 3000, '🧭', '#8b5cf6'),
            (3, 'Viajante', 3001, 7000, '🎒', '#f59e0b'),
            (4, 'Guia', 7001, 15000, '🗺️', '#10b981'),
            (5, 'Lenda', 15001, 999999, '👑', '#ef4444')");
        echo "<p style='color:green;'>✅ Níveis padrão inseridos.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Níveis já existem.</p>";
    }

    // 2. point_transactions
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
    echo "<p style='color:green;'>✅ Tabela 'point_transactions' criada/verificada.</p>";

    // 3. user_points
    $db->exec("CREATE TABLE IF NOT EXISTS user_points (
        user_id INT PRIMARY KEY,
        total_points INT DEFAULT 0,
        current_level INT DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (current_level) REFERENCES levels(level_number)
    )");
    echo "<p style='color:green;'>✅ Tabela 'user_points' criada/verificada.</p>";

    // 4. badges
    $db->exec("CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        category VARCHAR(50),
        rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',
        points_reward INT DEFAULT 0,
        requirement_type VARCHAR(50),
        requirement_value INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green;'>✅ Tabela 'badges' criada/verificada.</p>";

    // 5. user_badges
    $db->exec("CREATE TABLE IF NOT EXISTS user_badges (
        user_id INT NOT NULL,
        badge_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, badge_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
    )");
    echo "<p style='color:green;'>✅ Tabela 'user_badges' criada/verificada.</p>";

    // 6. check_ins
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
    echo "<p style='color:green;'>✅ Tabela 'check_ins' criada/verificada.</p>";

    // 7. partner_views
    $db->exec("CREATE TABLE IF NOT EXISTS partner_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        user_id INT,
        view_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<p style='color:green;'>✅ Tabela 'partner_views' criada/verificada.</p>";

    // 8. View de Ranking (MySQL 5.7 compatible - sem RANK() OVER)
    $db->exec("CREATE OR REPLACE VIEW ranking_geral AS 
        SELECT u.id, u.name, up.total_points, up.current_level, l.name as level_name,
        (SELECT COUNT(*) + 1 FROM user_points up2 WHERE up2.total_points > up.total_points) as ranking_position
        FROM users u
        JOIN user_points up ON u.id = up.user_id
        JOIN levels l ON up.current_level = l.level_number");
    echo "<p style='color:green;'>✅ View 'ranking_geral' criada/atualizada.</p>";

    // 9. View user_stats
    $db->exec("CREATE OR REPLACE VIEW user_stats AS 
        SELECT user_id, 
        COUNT(*) as total_checkins,
        SUM(points_earned) as total_points_earned
        FROM check_ins
        GROUP BY user_id");
    echo "<p style='color:green;'>✅ View 'user_stats' criada/atualizada.</p>";

    // 10. Inicializar user_points para usuários existentes
    $affected = $db->exec("INSERT IGNORE INTO user_points (user_id, total_points, current_level) 
               SELECT id, 0, 1 FROM users");
    echo "<p style='color:green;'>✅ user_points inicializado para $affected usuário(s).</p>";
    
    echo "<br><h3 style='color:darkgreen;'>✅ Migração de Gamificação concluída!</h3>";
    echo "<a href='/auth/login.php'>Voltar para o Login</a>";

} catch (Exception $e) {
    echo "<p style='color:red; font-size:1.2rem;'><strong>❌ ERRO:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
