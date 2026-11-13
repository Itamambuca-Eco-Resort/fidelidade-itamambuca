<?php
// Script de Reparo Automático - Acesse via navegador para rodar
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Partner.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Diagnosticando Banco de Dados...</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p style='color:green;'>✅ Conexão com o banco de dados OK!</p>";
    
    // 0. Sincronizar Esquema da Tabela Partners
    echo "<h3>Sincronizando esquema da tabela 'partners'...</h3><ul>";
    $neededColumns = [
        'short_description' => "VARCHAR(200) DEFAULT ''",
        'long_description'  => "TEXT DEFAULT NULL",
        'city'              => "VARCHAR(100) DEFAULT NULL",
        'state'             => "VARCHAR(50) DEFAULT NULL",
        'zip_code'          => "VARCHAR(20) DEFAULT NULL",
        'email'             => "VARCHAR(100) DEFAULT NULL",
        'business_hours'    => "TEXT DEFAULT NULL",
        'qr_code'           => "VARCHAR(100) DEFAULT NULL"
    ];

    foreach ($neededColumns as $col => $def) {
        $stmtCol = $db->query("SHOW COLUMNS FROM partners LIKE '$col'");
        if ($stmtCol->rowCount() == 0) {
            echo "<li>Adicionando coluna faltante em 'partners': <b>$col</b>... ";
            $db->exec("ALTER TABLE partners ADD COLUMN $col $def");
            echo "Feito!</li>";
        }
    }
    echo "</ul>";

    // 0.1 Garantir Tabelas Auxiliares
    echo "<h3>Verificando Tabelas Auxiliares...</h3><ul>";
    $tables = [
        "partner_photos" => "CREATE TABLE partner_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            caption VARCHAR(255) DEFAULT NULL,
            is_main_photo TINYINT(1) DEFAULT 0,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "partner_views" => "CREATE TABLE partner_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            referrer VARCHAR(255) DEFAULT NULL,
            view_date DATE DEFAULT NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (partner_id, user_id, view_date)
        )",
        "coupons" => "CREATE TABLE coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            discount_type ENUM('percentage', 'fixed') NOT NULL,
            discount_value DECIMAL(10,2) NOT NULL,
            valid_from DATE NOT NULL,
            valid_until DATE NOT NULL,
            max_uses INT DEFAULT NULL,
            current_uses INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "coupon_redemptions" => "CREATE TABLE coupon_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coupon_id INT NOT NULL,
            user_id INT NOT NULL,
            partner_id INT NOT NULL,
            redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "partner_rewards" => "CREATE TABLE partner_rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            partner_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            points_cost INT NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "reward_redemptions" => "CREATE TABLE reward_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reward_id INT NOT NULL,
            redemption_code VARCHAR(20) NOT NULL UNIQUE,
            points_spent INT NOT NULL,
            status ENUM('pending', 'used', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL DEFAULT NULL
        )",
        "user_missions" => "CREATE TABLE user_missions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            mission_type VARCHAR(50) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            points_earned INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_mission_idx (user_id, mission_type)
        )"
    ];

    foreach ($tables as $name => $sql) {
        $stmt = $db->query("SHOW TABLES LIKE '$name'");
        if ($stmt->rowCount() == 0) {
            echo "<li>Criando tabela faltante: <b>$name</b>... ";
            $db->exec($sql);
            echo "Feito!</li>";
        }
    }
    echo "</ul>";

    // 0.2 Reset Opcional
    if (isset($_GET['reset']) && $_GET['reset'] == '1') {
        echo "<h3>Resetando Atividades...</h3>";
        $db->exec("DELETE FROM partners");
        echo "<p style='color:orange;'>⚠️ Tabela de parceiros limpa com sucesso.</p>";
    }

    // 1. Verificar Categorias
    $stmt = $db->query("SELECT id, name, slug FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Categorias encontradas:</h3><ul>";
    $catBySlug = [];
    foreach ($categories as $c) {
        echo "<li>{$c['name']} (slug: <b>{$c['slug']}</b>)</li>";
        $catBySlug[$c['slug']] = $c['id'];
    }
    echo "</ul>";

    // 2. Garantir Categorias Essenciais
    $essentialCats = [
        ['name' => 'SPA', 'slug' => 'spa', 'icon' => 'bi-heart-pulse'],
        ['name' => 'Gastronomia', 'slug' => 'gastronomia', 'icon' => 'bi-cup-hot'],
        ['name' => 'Hospedagem', 'slug' => 'hospedagem', 'icon' => 'bi-house-door'],
        ['name' => 'Lazer', 'slug' => 'lazer', 'icon' => 'bi-tree']
    ];

    foreach ($essentialCats as $ec) {
        if (!isset($catBySlug[$ec['slug']])) {
            echo "<li>Criando categoria ausente: <b>{$ec['name']}</b>... ";
            $stmt = $db->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            $stmt->execute([$ec['name'], $ec['slug'], $ec['icon']]);
            $catBySlug[$ec['slug']] = $db->lastInsertId();
            echo "Feito!</li>";
        }
    }

    // 3. Verificar Parceiros
    $partnerModel = new Partner();
    $partnersToCreate = [
        [
            'name' => 'Armazém SPA',
            'slug' => 'spa',
            'cat' => 'spa',
            'short_description' => 'Tratamentos Premium e Bem-estar.',
            'long_description' => 'Relaxe e renove suas energias em meio à natureza com nossos tratamentos exclusivos.',
            'qr' => 'armazem-spa-001'
        ],
        [
            'name' => 'Room Service',
            'slug' => 'room-service',
            'cat' => 'hospedagem',
            'short_description' => 'Café da manhã e refeições na sua suíte.',
            'long_description' => 'Aproveite o melhor da nossa gastronomia no conforto do seu quarto.',
            'qr' => 'room-service-001'
        ],
        [
            'name' => 'Standup Paddle',
            'slug' => 'standup',
            'cat' => 'lazer',
            'short_description' => 'Aventuras na Água.',
            'long_description' => 'Explore o Rio e o Mar com nossas pranchas de Standup Paddle.',
            'qr' => 'standup-paddle-001'
        ]
    ];

    // Obter um ID de administrador válido para vincular os parceiros padrão
    $stmtAdmin = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $adminUser = $stmtAdmin->fetch();
    $adminId = $adminUser ? $adminUser['id'] : null;

    echo "<h3>Verificando Atividades:</h3><ul>";
    foreach ($partnersToCreate as $p) {
        $stmt = $db->prepare("SELECT id, qr_code FROM partners WHERE slug = ?");
        $stmt->execute([$p['slug']]);
        $row = $stmt->fetch();

        if (!$row) {
            echo "<li>Atividade <b>{$p['name']}</b> não existe. Criando... ";
            $catId = $catBySlug[$p['cat']] ?? null;
            if (!$catId) {
                echo "<span style='color:red;'>Erro: Categoria não mapeada.</span></li>";
                continue;
            }
            
            $partnerId = $partnerModel->create([
                'user_id' => $adminId,
                'category_id' => $catId,
                'name' => $p['name'],
                'slug' => $p['slug'],
                'short_description' => $p['short_description'],
                'long_description' => $p['long_description'],
                'qr_code' => $p['qr'],
                'is_featured' => 1,
                'is_active' => 1
            ]);
            echo "<span style='color:green;'>Sucesso!</span></li>";
        } else {
            echo "<li>Atividade <b>{$p['name']}</b> já existe. Atualizando dados... ";
            $catId = $catBySlug[$p['cat']] ?? null;
            $stmtUp = $db->prepare("UPDATE partners SET 
                category_id = ?, 
                short_description = ?, 
                long_description = ?, 
                qr_code = ?,
                is_active = 1
                WHERE id = ?");
            $stmtUp->execute([
                $catId, 
                $p['short_description'], 
                $p['long_description'], 
                $p['qr'], 
                $row['id']
            ]);
            echo "<span style='color:green;'>Feito!</span></li>";
        }
    }
    echo "</ul>";

    echo "<h2>🎉 Tudo pronto! Tente acessar os links agora.</h2>";
    echo "<p><a href='index.php'>Voltar para Home</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Erro fatal:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
