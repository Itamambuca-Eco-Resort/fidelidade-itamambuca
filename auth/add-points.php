<?php
/**
 * Script admin: adicionar pontos a um usuário (contorna triggers)
 * APAGAR APÓS O USO!
 */
require_once '../config/config.php';
require_once '../config/database.php';

$userId  = 2;
$points  = 10000;

echo "<h2>Adicionando $points pontos ao usuário #$userId</h2>";

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ---------- 1. Mostrar triggers existentes ----------
    echo "<h3>🔍 Triggers na tabela user_points:</h3><ul>";
    $triggers = $db->query("SHOW TRIGGERS LIKE 'user_points'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($triggers)) {
        echo "<li>Nenhum trigger encontrado.</li>";
    } else {
        foreach ($triggers as $t) {
            echo "<li><strong>" . htmlspecialchars($t['Trigger']) . "</strong> — " . htmlspecialchars($t['Event']) . " " . htmlspecialchars($t['Timing']) . "</li>";
        }
    }
    echo "</ul>";

    // ---------- 2. Remover triggers problemáticos ----------
    foreach ($triggers as $t) {
        $name = $t['Trigger'];
        $db->exec("DROP TRIGGER IF EXISTS `$name`");
        echo "<p style='color:orange;'>⚠️ Trigger <strong>$name</strong> removido temporariamente.</p>";
    }

    // ---------- 3. Verificar usuário ----------
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) die("<p style='color:red;'>❌ Usuário #$userId não encontrado.</p>");
    echo "<p>👤 <strong>" . htmlspecialchars($user['name']) . "</strong> (" . htmlspecialchars($user['email']) . ")</p>";

    // ---------- 4. Upsert de pontos ----------
    $stmt = $db->prepare("
        INSERT INTO user_points (user_id, total_points, current_level)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE total_points = total_points + VALUES(total_points)
    ");
    $stmt->execute([$userId, $points]);

    // ---------- 5. Registrar transação ----------
    try {
        $stmt = $db->prepare("INSERT INTO point_transactions (user_id, points, action_type, description) VALUES (?, ?, 'admin', 'Bônus de pontos pelo administrador')");
        $stmt->execute([$userId, $points]);
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠️ Transação não registrada (tabela pode não existir): " . $e->getMessage() . "</p>";
    }

    // ---------- 6. Mostrar total ----------
    $stmt = $db->prepare("SELECT total_points FROM user_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetchColumn();

    echo "<p style='color:green; font-size:1.3rem;'>✅ <strong>$points pontos</strong> adicionados!</p>";
    echo "<p>📊 Total atual: <strong>$total pontos</strong></p>";
    echo "<hr><p style='color:red;'><strong>⚠️ APAGUE este arquivo após o uso!</strong></p>";
    echo "<a href='/perfil.php'>Ver perfil</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
