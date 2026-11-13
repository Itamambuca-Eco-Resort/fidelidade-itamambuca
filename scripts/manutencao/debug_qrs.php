<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance()->getConnection();

echo "<h1>Diagnosticando Códigos no Banco de Dados</h1>";
$stmt = $db->query("SELECT id, name, slug, qr_code, is_active FROM partners");
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%'>";
echo "<tr style='background:#eee;'><th>ID</th><th>Nome</th><th>Slug</th><th>QR Code no Banco</th><th>HEX (Debug)</th><th>Ativo?</th></tr>";

foreach ($partners as $p) {
    $hex = bin2hex($p['qr_code'] ?? '');
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['name']}</td>";
    echo "<td>{$p['slug']}</td>";
    echo "<td>'<b>{$p['qr_code']}</b>'</td>";
    echo "<td><code>$hex</code></td>";
    echo "<td>" . ($p['is_active'] ? 'Sim' : 'Não') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Sugestão:</h3>";
echo "Se você vir códigos repetidos ou IDs estranhos, por favor me avise. Vou preparar um script de 'Resete Total' para as atividades.";
