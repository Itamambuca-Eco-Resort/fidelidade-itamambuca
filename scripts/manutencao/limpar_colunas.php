<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance()->getConnection();

$colsToDrop = [
    'address', 'city', 'state', 'zip_code', 
    'phone', 'whatsapp', 'email', 'website',
    'latitude', 'longitude'
];

echo "<h1>Limpando Colunas Obsoletas...</h1><ul>";

foreach ($colsToDrop as $col) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM partners LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "<li>Removendo <b>$col</b>... ";
            $db->exec("ALTER TABLE partners DROP COLUMN $col");
            echo "<span style='color:green;'>Sucesso!</span></li>";
        } else {
            echo "<li>Coluna <b>$col</b> não existe. <span style='color:orange;'>Pulado.</span></li>";
        }
    } catch (Exception $e) {
        echo "<li>Erro ao remover <b>$col</b>: " . $e->getMessage() . "</li>";
    }
}

echo "</ul><p><b>Limpeza concluída!</b></p>";
echo "<p><a href='index.php'>Voltar</a></p>";
