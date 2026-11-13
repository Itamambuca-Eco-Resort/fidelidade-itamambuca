<?php
require_once '../config/config.php';
require_once '../models/Favorite.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$partnerId = $data['partner_id'] ?? null;

if (!$partnerId) {
    echo json_encode(['success' => false, 'message' => 'ID do parceiro não fornecido']);
    exit;
}

$favoriteModel = new Favorite();
$userId = $_SESSION['user_id'];

if ($favoriteModel->isFavorite($userId, $partnerId)) {
    $favoriteModel->remove($userId, $partnerId);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    $favoriteModel->add($userId, $partnerId);
    echo json_encode(['success' => true, 'action' => 'added']);
}
