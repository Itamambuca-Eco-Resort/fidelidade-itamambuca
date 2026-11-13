<?php
/**
 * API: Gerar Token de Check-in Temporário
 * POST /api/generate-checkin-token.php
 * Body: { "partner_id": 3 }
 * Requer role: admin ou partner
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Autenticação — só admin ou partner
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'partner'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$partnerId = isset($data['partner_id']) ? (int) $data['partner_id'] : 0;

if (!$partnerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'partner_id é obrigatório.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Verificar se o parceiro existe e está ativo
    $stmt = $db->prepare("SELECT id, name FROM partners WHERE id = ? AND is_active = 1");
    $stmt->execute([$partnerId]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Atividade não encontrada ou inativa.']);
        exit;
    }

    // Gerar token único e seguro
    $token = bin2hex(random_bytes(24)); // 48 chars hex
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $stmt = $db->prepare("
        INSERT INTO checkin_tokens (partner_id, token, created_by, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$partnerId, $token, $_SESSION['user_id'], $expiresAt]);

    $checkinUrl = 'https://fidelidade.itamambucaecoresort.com.br/checkin?token=' . $token;

    echo json_encode([
        'success'     => true,
        'token'       => $token,
        'checkin_url' => $checkinUrl,
        'partner_id'  => $partnerId,
        'partner_name'=> $partner['name'],
        'expires_at'  => $expiresAt,
        'expires_in'  => 300, // segundos
    ]);

} catch (Exception $e) {
    error_log('generate-checkin-token error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno.']);
}
