<?php
/**
 * API para registrar compartilhamento de parceiro
 * POST /api/share.php
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Gamification.php';

header('Content-Type: application/json');

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['partner_id']) || !isset($data['platform'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'partner_id e platform são obrigatórios']);
    exit;
}

$userId = $_SESSION['user_id'];
$partnerId = (int)$data['partner_id'];
$platform = $data['platform'];

// Validar plataforma
$validPlatforms = ['facebook', 'twitter', 'whatsapp', 'instagram', 'other'];
if (!in_array($platform, $validPlatforms)) {
    $platform = 'other';
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Registrar compartilhamento
    $stmt = $db->prepare("
        INSERT INTO shares (user_id, partner_id, platform, points_earned)
        VALUES (?, ?, ?, 10)
    ");
    $stmt->execute([$userId, $partnerId, $platform]);
    
    // Adicionar pontos
    $gamification = new Gamification();
    $gamification->addPoints($userId, 10, 'share', "Compartilhou parceiro em {$platform}", $partnerId);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Compartilhamento registrado!',
        'points_earned' => 10
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar compartilhamento: ' . $e->getMessage()
    ]);
}
?>
