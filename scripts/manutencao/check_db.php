<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Conexão OK\n";

    $tables = ['partners', 'categories'];
    foreach ($tables as $table) {
        echo "\nEstrutura da tabela: $table\n";
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
