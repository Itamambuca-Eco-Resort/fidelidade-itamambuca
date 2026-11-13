<?php
/**
 * Google Sign-In Callback
 * Recebe o credential JWT do Google Identity Services,
 * valida e cria/autentica o usuário.
 */
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['credential'])) {
    redirect('/auth/login.php');
}

$credential = $_POST['credential'];

// Decodificar o JWT sem verificar assinatura (verificação via Google API)
// Para produção, verificar com a chave pública do Google ou usar a biblioteca google/apiclient
$parts = explode('.', $credential);
if (count($parts) !== 3) {
    redirect('/auth/login.php?error=google_invalid');
}

$payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 === 0 ? strlen($parts[1]) : strlen($parts[1]) + (4 - strlen($parts[1]) % 4), '=', STR_PAD_RIGHT)), true);

if (!$payload || empty($payload['email']) || empty($payload['sub'])) {
    redirect('/auth/login.php?error=google_invalid');
}

// Verificar se o token não está expirado
if (isset($payload['exp']) && $payload['exp'] < time()) {
    redirect('/auth/login.php?error=google_expired');
}

$googleId   = $payload['sub'];
$email      = $payload['email'];
$name       = $payload['name'] ?? $payload['given_name'] ?? explode('@', $email)[0];
$avatar     = $payload['picture'] ?? null;

require_once '../models/User.php';
$userModel = new User();

// Verificar se já existe usuário com esse e-mail
$user = $userModel->findByEmail($email);

if ($user) {
    // Usuário existente — atualizar google_id se necessário e logar
    if (empty($user['google_id'])) {
        $userModel->updateGoogleId($user['id'], $googleId);
    }
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'] ?? $avatar;
} else {
    // Novo usuário — criar conta como turista
    $userId = $userModel->create([
        'name'      => $name,
        'email'     => $email,
        'password'  => bin2hex(random_bytes(16)), // senha aleatória (plain)
        'role'      => 'tourist',
        'google_id' => $googleId,
        'avatar'    => $avatar,
    ]);

    if (!$userId) {
        redirect('/auth/login.php?error=google_create_failed');
    }

    // Ganho de pontos iniciais
    require_once '../models/Gamification.php';
    $gamification = new Gamification();

    // Guard: só concede se o usuário ainda NÃO recebeu o bônus
    if (!$gamification->hasWelcomeBonus($userId)) {
        $pointsOk = $gamification->addPoints($userId, 100, 'welcome_bonus', 'Bônus de Boas-Vindas pelo Google');
        if (!$pointsOk) {
            error_log("ERRO: addPoints falhou para userId={$userId} (welcome_bonus google)");
        }
    }

    $_SESSION['show_welcome_modal'] = 100;

    $_SESSION['user_id']     = $userId;
    $_SESSION['user_name']   = $name;
    $_SESSION['user_email']  = $email;
    $_SESSION['user_role']   = 'tourist';
    $_SESSION['user_avatar'] = $avatar;
}

if ($_SESSION['user_role'] === 'partner' || $_SESSION['user_role'] === 'admin') {
    redirect('/dashboard/');
} else {
    redirect('/');
}
