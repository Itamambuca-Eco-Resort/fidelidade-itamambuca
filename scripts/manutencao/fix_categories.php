<?php
/**
 * Script de correção de categorias
 * Santour Guide
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "<h1>Resultados da Atualização de Categorias</h1>";

try {
    $db = Database::getInstance()->getConnection();

    $categoriesToAdd = [
        ['name' => 'Experiências', 'slug' => 'experiencias'],
        ['name' => 'Lazer', 'slug' => 'lazer'],
        ['name' => 'Saúde', 'slug' => 'saude'],
        ['name' => 'Serviços', 'slug' => 'servicos'],
    ];

    foreach ($categoriesToAdd as $cat) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$cat['slug']]);
        if ($stmt->fetch()) {
            echo "<p style='color: blue;'>A categoria <strong>{$cat['name']}</strong> já existe.</p>";
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$cat['name'], $cat['slug']]);
            echo "<p style='color: green;'>A categoria <strong>{$cat['name']}</strong> foi adicionada com sucesso!</p>";
        }
    }

    echo "<hr><p>Você já pode deletar este arquivo: <code>fix_categories.php</code></p>";
    echo "<a href='/'>Voltar para o Início</a>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>