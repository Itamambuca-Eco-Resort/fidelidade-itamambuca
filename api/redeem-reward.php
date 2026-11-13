<?php
/**
 * API para resgate de prêmios
 * POST /api/redeem-reward.php
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Gamification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reward_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'reward_id é obrigatório']);
    exit;
}

$gamification = new Gamification();
$result = $gamification->redeemReward($_SESSION['user_id'], $data['reward_id']);

echo json_encode($result);
