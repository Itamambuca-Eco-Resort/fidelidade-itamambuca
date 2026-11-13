<?php
/**
 * API para validar QR Code e fazer check-in verificado
 * POST /api/scan-qr.php
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

if (!isset($data['qr_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'qr_code é obrigatório']);
    exit;
}

$qrCode = trim($data['qr_code'] ?? '');
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

// Robustez: Se o código recebido for uma URL completa (ex: vinda de um scanner externo),
// extraímos apenas o valor do parâmetro 'qr' para a busca no banco.
if (filter_var($qrCode, FILTER_VALIDATE_URL)) {
    $parsedUrl = parse_url($qrCode);
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
        if (isset($query['qr'])) {
            $qrCode = $query['qr'];
        }
    }
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar parceiro pelo QR Code diretamente na tabela partners
    $stmt = $db->prepare("
        SELECT id as partner_id, name as partner_name
        FROM partners
        WHERE LOWER(TRIM(qr_code)) = LOWER(?) AND is_active = 1
    ");
    $stmt->execute([$qrCode]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        // Fallback: Verificar se é um token dinâmico (para tratamentos/serviços)
        $stmtT = $db->prepare("
            SELECT ct.*, p.name AS partner_name, p.id AS real_partner_id
            FROM checkin_tokens ct
            JOIN partners p ON ct.partner_id = p.id
            WHERE ct.token = ? AND ct.used_at IS NULL AND ct.expires_at > NOW()
        ");
        $stmtT->execute([$qrCode]);
        $tokenRow = $stmtT->fetch(PDO::FETCH_ASSOC);

        if ($tokenRow) {
            // É um token válido! Registrar check-in de serviço (50 pts)
            $result = $gamification->registerCheckin(
                $_SESSION['user_id'],
                $tokenRow['real_partner_id'],
                true,
                $latitude,
                $longitude,
                50 // Serviços valem 50 pontos
            );

            if ($result['success']) {
                // Marcar token como usado
                $db->prepare("UPDATE checkin_tokens SET used_at = NOW(), used_by = ? WHERE token = ?")
                   ->execute([$_SESSION['user_id'], $qrCode]);

                $result['partner_name'] = $tokenRow['partner_name'];
                echo json_encode($result);
                exit;
            } else {
                echo json_encode($result);
                exit;
            }
        }

        // Se não for nem código fixo nem token válido
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "QR Code inválido ou expirado."
        ]);
        exit;
    }
    
    // Registrar check-in verificado
    $gamification = new Gamification();
    $result = $gamification->registerCheckin(
        $_SESSION['user_id'],
        $partner['partner_id'],
        true, // verificado = true
        $latitude,
        $longitude,
        100 // 100 pontos para visita/check-in fixo
    );
    
    if ($result['success']) {
        $result['partner_name'] = $partner['partner_name'];
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
        'message' => 'Erro ao validar QR Code: ' . $e->getMessage()
    ]);
}
?>
