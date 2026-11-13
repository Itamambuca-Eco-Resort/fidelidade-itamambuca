<?php
require_once '../config/config.php';

if (isLoggedIn()) redirect('/');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        require_once '../models/User.php';
        $userModel = new User();

        if ($userModel->findByEmail($email)) {
            $error = 'Este e-mail já está cadastrado. <a href="login.php" style="color:#1e8b8b;">Entrar</a>';
        } else {
            $userId = $userModel->create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $password,
                'role'     => 'tourist',
            ]);

            if ($userId) {
                // Ganho de pontos iniciais (Bônus de Boas-Vindas)
                require_once '../models/Gamification.php';
                $gamification = new Gamification();

                // Guard: só concede se o usuário ainda NÃO recebeu o bônus
                if (!$gamification->hasWelcomeBonus($userId)) {
                    $pointsOk = $gamification->addPoints($userId, 100, 'welcome_bonus', 'Bônus de Boas-Vindas');
                    if (!$pointsOk) {
                        error_log("ERRO: addPoints falhou para userId={$userId} (welcome_bonus)");
                    }
                }

                $_SESSION['show_welcome_modal'] = 100;
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role']  = 'tourist';
                redirect('/');
            } else {
                $error = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Hóspede | Fidelidade Itamambuca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<div class="login-page">
  <div class="login-box" style="max-width:440px;">

    <!-- Logo -->
    <div class="logo-wrap">
      <img src="/assets/images/icon.png" alt="Itamambuca Eco Resort" style="width: 80px; height: 80px; margin-bottom: 1rem; border-radius: 50%; object-fit: cover;">
      <h2>Criar Conta Grátis</h2>
      <p class="subtitle">Cadastre-se para acumular pontos em nosso Resort</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- Botão Google -->
    <div id="google-btn-wrap" style="margin-bottom:1.25rem;">
      <div id="g_id_onload"
           data-client_id="<?= defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'SEU_GOOGLE_CLIENT_ID' ?>"
           data-callback="handleGoogleSignIn"
           data-auto_prompt="false">
      </div>
      <div class="g_id_signin"
           data-type="standard"
           data-size="large"
           data-theme="outline"
           data-text="signup_with"
           data-shape="pill"
           data-logo_alignment="left"
           data-width="100%">
      </div>
    </div>

    <!-- Divisor -->
    <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem;">
      <div style="flex:1; height:1px; background:#e0e0e0;"></div>
      <span style="color:#aaa; font-size:.8rem; font-weight:600; white-space:nowrap;">OU CADASTRE COM E-MAIL</span>
      <div style="flex:1; height:1px; background:#e0e0e0;"></div>
    </div>

    <!-- Formulário -->
    <form method="POST" id="registerForm">
      <div class="form-group">
        <label class="form-label" for="name"><i class="bi bi-person"></i> Nome completo *</label>
        <input type="text" id="name" name="name" class="form-control"
               placeholder="Seu nome completo"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               autocomplete="name" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email"><i class="bi bi-envelope"></i> E-mail *</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="seu@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               autocomplete="email" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="phone"><i class="bi bi-whatsapp"></i> WhatsApp</label>
        <input type="tel" id="phone" name="phone" class="form-control"
               placeholder="(12) 99999-9999"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               autocomplete="tel">
      </div>

      <div class="form-group" style="position:relative;">
        <label class="form-label" for="password"><i class="bi bi-lock"></i> Senha *</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="Mínimo 6 caracteres"
               autocomplete="new-password" required>
        <button type="button" onclick="togglePwd()" tabindex="-1"
                style="position:absolute; right:.75rem; bottom:.7rem; background:none; border:none; cursor:pointer; color:#aaa; font-size:1rem; padding:0;">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>

      <button type="submit" class="btn btn-primary w-100" style="justify-content:center; margin-top:.25rem;">
        <i class="bi bi-person-check"></i> Criar Conta Grátis
      </button>
    </form>

    <!-- Rodapé -->
    <div style="text-align:center; margin-top:1.5rem; font-size:.875rem; color:#888;">
      Já tem conta?
      <a href="login.php" style="color:#2C5F2D; font-weight:600;">Entrar</a>
    </div>
    <div style="text-align:center; margin-top:.75rem; font-size:.8rem; color:#bbb;">
    </div>
    <div style="text-align:center; margin-top:1rem;">
      <a href="/" style="color:#aaa; font-size:.82rem;"><i class="bi bi-arrow-left"></i> Voltar ao Início</a>
    </div>

  </div>
</div>

<!-- Hidden form para Google Sign-In -->
<form id="googleForm" method="POST" action="/auth/google-callback.php" style="display:none;">
  <input type="hidden" id="googleCredential" name="credential">
</form>

<script>
// Toggle senha visível/oculta
function togglePwd() {
  var input = document.getElementById('password');
  var icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

// Máscara de telefone
document.getElementById('phone').addEventListener('input', function(e) {
  var v = e.target.value.replace(/\D/g, '').substring(0, 11);
  if (v.length > 10) {
    v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
  } else if (v.length > 6) {
    v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
  } else if (v.length > 2) {
    v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
  } else {
    v = v.replace(/^(\d*)/, '($1');
  }
  e.target.value = v;
});

// Callback do Google Sign-In
function handleGoogleSignIn(response) {
  document.getElementById('googleCredential').value = response.credential;
  document.getElementById('googleForm').submit();
}
</script>

</body>
</html>
