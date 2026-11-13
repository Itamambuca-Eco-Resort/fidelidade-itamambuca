<?php
/**
 * API: Validar e Consumir Token de Check-in
 * POST /api/validate-checkin-token.php
 * Body: { "token": "abc..." }
 * Requer: hóspede logado (role tourist)
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Gamification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Faça login para fazer check-in.', 'redirect' => '/auth/login.php']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token inválido.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Buscar token válido (não expirado e não usado)
    $stmt = $db->prepare("
        SELECT ct.*, p.name AS partner_name, p.id AS real_partner_id
        FROM checkin_tokens ct
        JOIN partners p ON ct.partner_id = p.id
        WHERE ct.token = ?
    ");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenRow) {
        echo json_encode(['success' => false, 'message' => 'QR Code inválido ou não encontrado.']);
        exit;
    }

    if ($tokenRow['used_at'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Este QR Code já foi utilizado.']);
        exit;
    }

    if (strtotime($tokenRow['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'QR Code expirado. Peça ao colaborador para gerar um novo.']);
        exit;
    }

    $userId    = (int) $_SESSION['user_id'];
    $partnerId = (int) $tokenRow['real_partner_id'];

    // Verificar cooldown: mesmo hóspede, mesmo parceiro, nas últimas 4h via token
    $stmt = $db->prepare("
        SELECT id FROM checkin_tokens
        WHERE used_by = ? AND partner_id = ? AND used_at > DATE_SUB(NOW(), INTERVAL 4 HOUR)
    ");
    $stmt->execute([$userId, $partnerId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você já fez check-in nesta atividade recentemente. Aguarde 4 horas.']);
        exit;
    }

    // Registrar check-in verificado
    $gamification = new Gamification();
    $result = $gamification->registerCheckin($userId, $partnerId, true, null, null, 50);

    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    // Marcar token como usado
    $stmt = $db->prepare("
        UPDATE checkin_tokens SET used_at = NOW(), used_by = ? WHERE token = ?
    ");
    $stmt->execute([$userId, $token]);

    echo json_encode([
        'success'      => true,
        'partner_name' => $tokenRow['partner_name'],
        'points_earned'=> $result['points_earned'],
        'message'      => 'Check-in realizado com sucesso!',
    ]);

} catch (Exception $e) {
    error_log('validate-checkin-token error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar check-in.']);
}
