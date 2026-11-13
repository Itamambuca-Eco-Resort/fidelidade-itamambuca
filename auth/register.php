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
    $confirm  = $_POST['confirm_password'] ?? '';
    $type     = $_POST['account_type'] ?? 'tourist';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif ($password !== $confirm) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        require_once '../models/User.php';
        $userModel = new User();

        if ($userModel->findByEmail($email)) {
            $error = 'Este e-mail já está cadastrado.';
        } else {
            $userId = $userModel->create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $password,
                'role'     => $type === 'partner' ? 'partner' : 'tourist',
            ]);

            if ($userId) {
                $_SESSION['user_id']   = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $type === 'partner' ? 'partner' : 'tourist';
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
  <title>Cadastro | Santour Guide</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<div class="login-page" style="align-items:flex-start; padding-top:3rem;">
  <div class="login-box" style="max-width:480px;">
    <div class="logo-wrap">
      <img src="/assets/logo.png" alt="Santour">
      <h2>Criar Conta</h2>
      <p class="subtitle">Acesse descontos exclusivos nos parceiros Santour</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <!-- Tipo de conta -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
        <label style="cursor:pointer;">
          <input type="radio" name="account_type" value="tourist" checked style="display:none;" id="type_tourist">
          <div id="card_tourist" onclick="selectType('tourist')" style="border:2px solid #1e8b8b; border-radius:12px; padding:1rem; text-align:center; background:rgba(30,139,139,.08);">
            <i class="bi bi-person" style="font-size:1.5rem; color:#1e8b8b; display:block; margin-bottom:.35rem;"></i>
            <strong style="font-size:.9rem; color:#0f2b3c;">Turista</strong>
            <p style="font-size:.75rem; color:#888; margin:0;">Quero aproveitar descontos</p>
          </div>
        </label>
        <label style="cursor:pointer;">
          <input type="radio" name="account_type" value="partner" style="display:none;" id="type_partner">
          <div id="card_partner" onclick="selectType('partner')" style="border:2px solid #e0e0e0; border-radius:12px; padding:1rem; text-align:center;">
            <i class="bi bi-shop" style="font-size:1.5rem; color:#888; display:block; margin-bottom:.35rem;"></i>
            <strong style="font-size:.9rem; color:#0f2b3c;">Parceiro</strong>
            <p style="font-size:.75rem; color:#888; margin:0;">Quero cadastrar meu negócio</p>
          </div>
        </label>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="bi bi-person"></i> Nome completo *</label>
        <input type="text" name="name" class="form-control" placeholder="Seu nome" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="bi bi-envelope"></i> E-mail *</label>
        <input type="email" name="email" class="form-control" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="bi bi-telephone"></i> Telefone / WhatsApp</label>
        <input type="tel" name="phone" class="form-control" placeholder="(12) 99999-9999" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label class="form-label"><i class="bi bi-lock"></i> Senha *</label>
          <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="bi bi-lock-fill"></i> Confirmar *</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repita a senha" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100" style="justify-content:center; margin-top:.5rem;">
        <i class="bi bi-person-plus"></i> Criar Conta
      </button>
    </form>

    <div style="text-align:center; margin-top:1.5rem; font-size:.9rem; color:#888;">
      Já tem conta?
      <a href="login.php" style="color:#1e8b8b; font-weight:600;">Entrar</a>
    </div>
    <div style="text-align:center; margin-top:.75rem;">
      <a href="/" style="color:#aaa; font-size:.85rem;"><i class="bi bi-arrow-left"></i> Voltar ao Guia</a>
    </div>
  </div>
</div>

<script>
function selectType(type) {
  const tourist = document.getElementById('card_tourist');
  const partner = document.getElementById('card_partner');
  const tInput  = document.getElementById('type_tourist');
  const pInput  = document.getElementById('type_partner');
  if (type === 'tourist') {
    tourist.style.border = '2px solid #1e8b8b';
    tourist.style.background = 'rgba(30,139,139,.08)';
    tourist.querySelector('i').style.color = '#1e8b8b';
    partner.style.border = '2px solid #e0e0e0';
    partner.style.background = '#fff';
    partner.querySelector('i').style.color = '#888';
    tInput.checked = true;
  } else {
    partner.style.border = '2px solid #1e8b8b';
    partner.style.background = 'rgba(30,139,139,.08)';
    partner.querySelector('i').style.color = '#1e8b8b';
    tourist.style.border = '2px solid #e0e0e0';
    tourist.style.background = '#fff';
    tourist.querySelector('i').style.color = '#888';
    pInput.checked = true;
  }
}
</script>
</body>
</html>
