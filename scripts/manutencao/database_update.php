<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Iniciando atualização do banco de dados...\n";

    // 1. Criar tabela point_expirations
    $db->exec("CREATE TABLE IF NOT EXISTS point_expirations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        transaction_id INT NOT NULL,
        points_amount INT NOT NULL,
        points_remaining INT NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id, expires_at)
    )");
    echo "Tabela point_expirations criada.\n";

    // 2. Criar tabela point_campaigns
    $db->exec("CREATE TABLE IF NOT EXISTS point_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela point_campaigns criada.\n";

    // 3. Criar tabela levels se não existir e popular
    $db->exec("CREATE TABLE IF NOT EXISTS levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_number INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        min_points INT NOT NULL,
        max_points INT NOT NULL,
        icon VARCHAR(50),
        color VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela levels pronta.\n";

    $db->exec("DELETE FROM levels");
    
    $levels = [
        [1, 'Essencial', 'Acesso a promoções exclusivas, Sobremesa no restaurante (aniversário)', 0, 499, '🌱', '#6c757d'],
        [2, 'Especial', 'Essencial + Acesso antecipado Black Friday, Prioridade de quarto, Check-in antecipado', 500, 999, '⭐', '#17a2b8'],
        [3, 'Reserva', 'Especial + Late Check-out, Cashback 10%, Cupom Instagram', 1000, 8000000, '👑', '#ffc107'],
        [4, 'Exclusivo (VIP)', 'Anuidade paga, Pontos que não expiram, Clube VIP', 9000000, 9999999, '💎', '#000000']
    ];

    $stmt = $db->prepare("INSERT INTO levels (level_number, name, description, min_points, max_points, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($levels as $l) {
        $stmt->execute($l);
    }
    echo "Tabela levels repopulada.\n";

    // 4. Limpar recompensas e criar as novas (vamos linkar a um parceiro ID fixo = 0 se for global, ou ao partner do hotel)
    // No sistema atual 'partner_rewards' tem 'partner_id'. Descobrindo o partner ID do Admin/Hotel:
    // Pega o id do primeiro parceiro cadastrado ou seta 1
    $partnerId = 1;
    $db->exec("DELETE FROM partner_rewards");
    
    $rewards = [
        [$partnerId, 'Drink Exclusivo no Deck Maria Mulata', 'Vale 1 drink exclusivo no bar Maria Mulata.', 1000, ''],
        [$partnerId, 'Escalda Pés', 'Momento de relaxamento no SPA do Itamambuca.', 2000, ''],
        [$partnerId, '20% Desconto na Boutique', 'Desconto válido para compras acima de 500 reais.', 3000, ''],
        [$partnerId, 'Voucher 1 Diária Apto Luxo', 'Diária para duas pessoas no apartamento luxo.', 8000, '']
    ];
    $stmtR = $db->prepare("INSERT INTO partner_rewards (partner_id, title, description, points_cost, image_url, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($rewards as $r) {
        $stmtR->execute($r);
    }
    echo "Recompensas cadastradas.\n";

    echo "Atualização concluída com sucesso.\n";

} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
