<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = $pageTitle ?? 'Fidelidade Itamambuca Eco Resort';
$pageDescription = $pageDescription ?? 'Programa de Fidelidade e Recompensas do Itamambuca Eco Resort.';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// User data for header
$isLoggedIn = isset($_SESSION['user_id']);
$firstName = 'Visitante';
if (!empty($_SESSION['user_name'])) {
    $firstName = htmlspecialchars(explode(' ', trim($_SESSION['user_name']))[0]);
}
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '');
$userAvatar = strtoupper(substr($firstName, 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Itamambuca Fidelidade</title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Swiper.js -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  
  <!-- QRCode.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  
  <!-- Styles -->
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  
  <style>
    /* ── Global Header Styles ── */
    .topbar {
      background: #1e4344;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .75rem 1rem;
      position: sticky;
      top: 0;
      z-index: 100;
      width: 100%;
    }
    .topbar-logo {
      display: flex;
      align-items: center;
      gap: .5rem;
      text-decoration: none;
    }
    .topbar-logo img {
      height: 40px;
      width: auto;
      object-fit: contain;
    }
    .topbar-logo-text {
      display: none;
    }
    .topbar-right {
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    .topbar-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #c9a961;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .9rem;
    }
    .topbar-menu-btn {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.4rem;
      cursor: pointer;
      padding: 0;
      display: flex;
      align-items: center;
    }

    /* ── Drawer menu ── */
    .drawer-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      z-index: 200;
    }
    .drawer-overlay.open {
      display: block;
    }
    .drawer {
      position: fixed;
      top: 0;
      right: -320px;
      width: 300px;
      height: 100%;
      background: #fff;
      z-index: 201;
      transition: right .3s ease;
      display: flex;
      flex-direction: column;
      box-shadow: -4px 0 20px rgba(0, 0, 0, .15);
    }
    .drawer.open {
      right: 0;
    }
    .drawer-header {
      background: #1e4344;
      padding: 1.5rem 1rem 1rem;
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    .drawer-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: #c9a961;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .drawer-user-name {
      color: #fff;
      font-weight: 600;
      font-size: 1rem;
    }
    .drawer-user-email {
      color: rgba(255, 255, 255, .7);
      font-size: .8rem;
      margin-top: 2px;
    }
    .drawer-close {
      margin-left: auto;
      background: none;
      border: none;
      color: rgba(255, 255, 255, .8);
      font-size: 1.4rem;
      cursor: pointer;
    }
    .drawer-nav {
      flex: 1;
      overflow-y: auto;
      padding: .5rem 0;
    }
    .drawer-nav a {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .85rem 1.25rem;
      color: #333;
      text-decoration: none;
      font-size: .95rem;
      border-bottom: 1px solid #f0f0f0;
      transition: background .15s;
    }
    .drawer-nav a:hover {
      background: #f8f8f8;
    }
    .drawer-nav a i {
      font-size: 1.1rem;
      color: #1e4344;
      width: 22px;
    }
    .drawer-nav a.danger {
      color: #e53e3e;
    }
    .drawer-nav a.danger i {
      color: #e53e3e;
    }
    .drawer-divider {
      height: 8px;
      background: #f5f5f5;
      margin: .25rem 0;
    }
  </style>
</head>
<body>

<!-- ── Topbar ── -->
<div class="topbar">
  <a href="/" class="topbar-logo">
    <img src="/assets/images/logo_itamambuca.png" alt="Itamambuca Eco Resort" onerror="this.src='/assets/logo.png'">
  </a>
  <div class="topbar-right">
    <?php if ($isLoggedIn): ?>
        <div class="topbar-avatar"><?= $userAvatar ?></div>
    <?php endif; ?>
    <button class="topbar-menu-btn" id="menuBtn" aria-label="Menu">
      <i class="bi bi-list"></i>
    </button>
  </div>
</div>

<!-- ── Drawer overlay ── -->
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- ── Drawer menu ── -->
<div class="drawer" id="drawer">
  <div class="drawer-header">
    <div class="drawer-avatar"><?= $userAvatar ?></div>
    <div>
      <div class="drawer-user-name"><?= $firstName ?></div>
      <?php if ($isLoggedIn): ?>
        <div class="drawer-user-email"><?= $userEmail ?></div>
      <?php endif; ?>
    </div>
    <button class="drawer-close" id="drawerClose"><i class="bi bi-x-lg"></i></button>
  </div>
  <nav class="drawer-nav">
    <a href="/"><i class="bi bi-house"></i> Início</a>
    <a href="/busca.php"><i class="bi bi-search"></i> Buscar</a>
    
    <?php if ($isLoggedIn): ?>
        <?php if (isAdmin()): ?>
            <a href="/dashboard/" style="background: rgba(201, 169, 97, 0.1); border-left: 4px solid #c9a961;">
                <i class="bi bi-speedometer2" style="color: #c9a961;"></i> <strong>Administração do Hotel</strong>
            </a>
            <a href="/dashboard/qrcodes.php"><i class="bi bi-qr-code"></i> Gerenciar QRCodes</a>
        <?php endif; ?>
        <a href="/perfil.php"><i class="bi bi-person-circle"></i> Meu Perfil</a>
        <a href="/cupons.php"><i class="bi bi-ticket-perforated"></i> Meus Cupons</a>
        <a href="/favoritos.php"><i class="bi bi-heart"></i> Favoritos</a>
    <?php endif; ?>
    
    <a href="/ranking.php"><i class="bi bi-trophy"></i> Ranking</a>
    <div class="drawer-divider"></div>
    <a href="/sobre-hotel.php"><i class="bi bi-info-circle"></i> Sobre o Resort</a>
    <a href="/informacoes-uteis.php"><i class="bi bi-journal-text"></i> Informações Úteis</a>
    <a href="/recreation.php"><i class="bi bi-person-arms-up"></i> Recreação</a>
    <a href="https://itamambuca.com.br" target="_blank"><i class="bi bi-globe"></i> Site Oficial do Hotel</a>
    <div class="drawer-divider"></div>
    
    <?php if ($isLoggedIn): ?>
        <a href="/logout.php" class="danger"><i class="bi bi-box-arrow-right"></i> Sair</a>
    <?php else: ?>
        <a href="/auth/login.php"><i class="bi bi-box-arrow-in-right"></i> Entrar</a>
        <a href="/auth/register-turista.php"><i class="bi bi-person-plus"></i> Cadastre-se</a>
    <?php endif; ?>
  </nav>
</div>

<script>
  (function () {
    // ── Drawer menu ──
    var menuBtn = document.getElementById('menuBtn');
    var drawer = document.getElementById('drawer');
    var overlay = document.getElementById('drawerOverlay');
    var closeBtn = document.getElementById('drawerClose');

    function openDrawer() {
      if (!drawer || !overlay) return;
      drawer.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
      if (!drawer || !overlay) return;
      drawer.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    if (menuBtn) menuBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);
  })();
</script>
