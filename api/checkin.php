<?php
/**
 * API para registrar check-in em parceiro
 * POST /api/checkin.php
 */

session_start();
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

if (!isset($data['partner_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'partner_id é obrigatório']);
    exit;
}

$userId = $_SESSION['user_id'];
$partnerId = (int)$data['partner_id'];
$verified = isset($data['verified']) ? (bool)$data['verified'] : false;
$latitude = isset($data['latitude']) ? $data['latitude'] : null;
$longitude = isset($data['longitude']) ? $data['longitude'] : null;
$photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
$comment = isset($data['comment']) ? $data['comment'] : null;

try {
    $gamification = new Gamification();
    $result = $gamification->registerCheckin(
        $userId,
        $partnerId,
        $verified,
        $latitude,
        $longitude,
        $photoUrl,
        $comment
    );
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar check-in: ' . $e->getMessage()
    ]);
}
?>
