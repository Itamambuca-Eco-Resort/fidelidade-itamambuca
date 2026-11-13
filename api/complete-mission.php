<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Gamification.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data && !isset($_POST['mission_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$missionType = $data['mission_type'] ?? $_POST['mission_type'] ?? '';
$userId = $_SESSION['user_id'];

if ($missionType !== 'follow_instagram') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de missão desconhecido.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verifica se já completou
    $stmt = $db->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_type = ?");
    $stmt->execute([$userId, $missionType]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Você já completou esta missão.']);
        exit;
    }

    $pointsReward = 50; // Pontos por seguir (conforme nova regra)

    $db->beginTransaction();

    // Registra missão
    $stmt = $db->prepare("
        INSERT INTO user_missions (user_id, mission_type, status, points_earned)
        VALUES (?, ?, 'approved', ?)
    ");
    $stmt->execute([$userId, $missionType, $pointsReward]);

    // Concede pontos
    $gamification = new Gamification();
    $gamification->addPoints($userId, $pointsReward, 'social_mission', 'Missão: Seguir Instagram');

    $db->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Missão concluída com sucesso!', 
        'points_earned' => $pointsReward
    ]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    error_log("Erro na missão social: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao processar missão.']);
}
