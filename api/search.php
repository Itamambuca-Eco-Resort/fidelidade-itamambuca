<?php
/**
 * API: Autocomplete de busca de parceiros
 * GET /api/search.php?q=termo
 * Retorna JSON com lista de parceiros que correspondem ao termo
 */
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Apenas usuários logados podem usar a API
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Ícones por categoria slug
    $icons = [
        'hospedagem' => 'bi-house-door',
        'gastronomia' => 'bi-cup-hot',
        'compras' => 'bi-bag',
        'servicos' => 'bi-briefcase',
        'lazer' => 'bi-tree',
        'saude' => 'bi-heart-pulse',
        'experiencias' => 'bi-camera',
    ];

    $search = "%{$q}%";
    $stmt = $db->prepare("
        SELECT
            p.id,
            p.name,
            p.slug,
            (SELECT url FROM partner_photos WHERE partner_id = p.id AND is_main_photo = 1 LIMIT 1) as main_photo,
            c.name  AS category,
            c.slug  AS category_slug
        FROM partners p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
          AND (
            p.name        LIKE :q1
            OR p.short_description LIKE :q2
            OR c.name     LIKE :q4
          )
        ORDER BY
            p.is_featured DESC,
            p.name ASC
        LIMIT 10
    ");
    $stmt->execute([':q1' => $search, ':q2' => $search, ':q4' => $search]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function ($row) use ($icons) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'category' => $row['category'] ?? '',
            'city' => $row['city'] ?? '',
            'photo' => $row['main_photo'] ?? '',
            'icon' => $icons[$row['category_slug'] ?? ''] ?? 'bi-shop',
        ];
    }, $rows);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
}
