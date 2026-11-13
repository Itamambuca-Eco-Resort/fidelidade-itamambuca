<?php
/**
 * Script de Seed: Popular dados para o Protótipo de Gamificação
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "<h1>Populando Dados de Protótipo</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Obter todos os parceiros em destaque
    $stmt = $db->query("SELECT id, name FROM partners WHERE is_featured = 1");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($partners)) {
        die("<p style='color:red;'>Nenhum parceiro em destaque encontrado. Cadastre um parceiro em destaque primeiro.</p>");
    }
    
    foreach ($partners as $partner) {
        $pId = $partner['id'];
        $pName = $partner['name'];
        
        // 2. Gerar QR Code para o parceiro (Simulado)
        $qrCode = "SANTOUR_" . strtoupper(substr(md5($pId), 0, 8));
        $db->prepare("UPDATE partners SET qr_code = ? WHERE id = ?")->execute([$qrCode, $pId]);
        echo "<p>✅ QR Code gerado para <strong>$pName</strong>: <code>$qrCode</code></p>";
        
        // 3. Cadastrar Prêmios Exemplo
        $rewards = [
            [
                'title' => 'Café Espresso Cortesia',
                'description' => 'Aproveite um café fresquinho por nossa conta ao visitar o local.',
                'points_cost' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1510970174576-7152882dfc71?auto=format&fit=crop&q=80&w=150'
            ],
            [
                'title' => 'Sobremesa do Dia',
                'description' => 'Ganhe uma sobremesa artesanal gratuita no seu pedido principal.',
                'points_cost' => 350,
                'image_url' => 'https://images.unsplash.com/photo-1563729784404-d560bb54b722?auto=format&fit=crop&q=80&w=150'
            ],
            [
                'title' => 'Desconto Extra de 10%',
                'description' => 'Acumule com outros descontos do guia em sua próxima compra.',
                'points_cost' => 500,
                'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&q=80&w=150'
            ]
        ];
        
        foreach ($rewards as $r) {
            // Verificar se já existe
            $check = $db->prepare("SELECT id FROM partner_rewards WHERE partner_id = ? AND title = ?");
            $check->execute([$pId, $r['title']]);
            if (!$check->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO partner_rewards (partner_id, title, description, points_cost, image_url)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$pId, $r['title'], $r['description'], $r['points_cost'], $r['image_url']]);
                echo "<span>- Prêmio adicionado: {$r['title']}</span><br>";
            }
        }
    }
    
    echo "<h3>Dados de protótipo inseridos com sucesso!</h3>";
    echo "<a href='/recompensas.php'>Ir para página de Recompensas</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro no seed: " . $e->getMessage() . "</p>";
}
