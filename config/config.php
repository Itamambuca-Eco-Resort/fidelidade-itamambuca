<?php
/**
 * Configurações Gerais
 * Fidelidade Itamambuca
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações do site
define('SITE_NAME', 'Fidelidade Itamambuca Eco Resort');
define('SITE_URL', 'https://fidelidade.itamambucaecoresort.com.br');
define('SITE_DESCRIPTION', 'Programa de Fidelidade e Recompensas do Itamambuca Eco Resort');

// Configurações Google (Geradas no Google Cloud Console)
define('GOOGLE_CLIENT_ID', '');

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');

// Criar diretório de uploads se não existir
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
    mkdir(UPLOAD_PATH . '/partners', 0755, true);
}

// Configurações de upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Funções auxiliares
function redirect($url)
{
    header("Location: " . SITE_URL . $url);
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUser()
{
    static $currentUser = null;
    
    if ($currentUser !== null) {
        return $currentUser;
    }

    if (!isLoggedIn()) {
        return null;
    }

    require_once ROOT_PATH . '/models/User.php';
    $userModel = new User();
    $currentUser = $userModel->findById($_SESSION['user_id']);
    return $currentUser;
}

function isPartner()
{
    $user = getCurrentUser();
    return $user && ($user['role'] === 'partner' || $user['role'] === 'admin');
}

function isAdmin()
{
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime)
{
    return date('d/m/Y H:i', strtotime($datetime));
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($text)
{
    // Remove acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    // Converte para minúsculas
    $text = strtolower($text);
    // Remove caracteres especiais
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    // Remove múltiplos hífens
    $text = preg_replace('/-+/', '-', $text);
    // Remove hífens do início e fim
    $text = trim($text, '-');

    return $text;
}

function uploadImage($file, $directory = 'partners')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Verificar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    // Verificar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }

    // Gerar nome único
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadDir = UPLOAD_PATH . '/' . $directory;

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return UPLOAD_URL . '/' . $directory . '/' . $filename;
    }

    return false;
}

// Incluir database
require_once ROOT_PATH . '/config/database.php';
