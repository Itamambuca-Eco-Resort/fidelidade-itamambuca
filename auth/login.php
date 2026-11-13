<?php
require_once '../config/config.php';

if (isLoggedIn())
  redirect('/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($email) || empty($password)) {
    $error = 'Preencha todos os campos.';
  } else {
    require_once '../models/User.php';
    $userModel = new User();
    $user = $userModel->findByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id']    = $user['id'];
      $_SESSION['user_name']  = $user['name'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_role']  = $user['role'];
      
      if ($user['role'] === 'partner' || $user['role'] === 'admin') {
        redirect('/dashboard/');
      } else {
        redirect('/');
      }
    } else {
      $error = 'E-mail ou senha incorretos.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar | Itamambuca Fidelidade</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>

<body>

  <div class="login-page">
    <div class="login-box">

      <!-- Logo -->
      <div class="logo-wrap">
        <img src="/assets/images/logo_itamambuca.png" alt="Itamambuca Eco Resort"
          style="width: 120px; margin-bottom: 1rem; object-fit: contain;">
        <p class="subtitle">Entre para acessar descontos exclusivos</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($_GET['error'])): ?>
        <?php $errMap = ['google_invalid' => 'Falha na autenticação com Google.', 'google_expired' => 'Sessão Google expirada. Tente novamente.', 'google_create_failed' => 'Erro ao criar conta com Google.']; ?>
        <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i>
          <?= htmlspecialchars($errMap[$_GET['error']] ?? 'Erro desconhecido.') ?></div>
      <?php endif; ?>

      <!-- Botão Google -->
      <div id="g_id_onload"
        data-client_id="<?= defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'SEU_GOOGLE_CLIENT_ID' ?>"
        data-callback="handleGoogleSignIn" data-auto_prompt="false">
      </div>
      <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with"
        data-shape="pill" data-logo_alignment="left" data-width="100%" style="margin-bottom:1.25rem;">
      </div>

      <!-- Divisor -->
      <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem;">
        <div style="flex:1; height:1px; background:#e0e0e0;"></div>
        <span style="color:#aaa; font-size:.78rem; font-weight:600; white-space:nowrap;">OU ENTRE COM E-MAIL</span>
        <div style="flex:1; height:1px; background:#e0e0e0;"></div>
      </div>

      <!-- Formulário -->
      <form method="POST">
        <div class="form-group">
          <label class="form-label" for="email"><i class="bi bi-envelope"></i> E-mail</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required>
        </div>
        <div class="form-group" style="position:relative;">
          <label class="form-label" for="password"><i class="bi bi-lock"></i> Senha</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
            autocomplete="current-password" required>
          <button type="button" onclick="togglePwd()" tabindex="-1"
            style="position:absolute;right:.75rem;bottom:.7rem;background:none;border:none;cursor:pointer;color:#aaa;font-size:1rem;padding:0;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="justify-content:center; margin-top:.25rem;">
          <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
      </form>

      <!-- Divisor cadastro -->
      <div style="border-top:1px solid #f0f0f0; margin:1.5rem 0 1.25rem;"></div>

      <!-- Opções de cadastro -->
      <p style="text-align:center; font-size:.82rem; color:#aaa; margin-bottom:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">
        Não tem conta? Cadastre-se
      </p>
      
      <a href="register-turista.php"
        style="display:flex; flex-direction:column; align-items:center; gap:.4rem; padding:.9rem .5rem; border:2px solid #e0e0e0; border-radius:14px; text-decoration:none; color:inherit; transition:all .2s;"
        onmouseover="this.style.borderColor='#1e8b8b';this.style.background='rgba(30,139,139,.05)'"
        onmouseout="this.style.borderColor='#e0e0e0';this.style.background=''">
        <i class="bi bi-person-walking" style="font-size:1.6rem; color:#1e8b8b;"></i>
        <strong style="font-size:.85rem; color:#0f2b3c;">Sou Hóspede / Turista</strong>
        <span style="font-size:.75rem; color:#aaa; text-align:center;">Quero me cadastrar para ganhar pontos</span>
      </a>

      <div style="text-align:center; margin-top:1.25rem;">
        <a href="/" style="color:#aaa; font-size:.82rem;"><i class="bi bi-arrow-left"></i> Voltar ao Início</a>
      </div>

    </div>
  </div>

  <!-- Hidden form para Google Sign-In -->
  <form id="googleForm" method="POST" action="/auth/google-callback.php" style="display:none;">
    <input type="hidden" id="googleCredential" name="credential">
  </form>

  <script>
    function togglePwd() {
      var input = document.getElementById('password');
      var icon = document.getElementById('eyeIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }

    function handleGoogleSignIn(response) {
      document.getElementById('googleCredential').value = response.credential;
      document.getElementById('googleForm').submit();
    }
  </script>

</body>

</html> com