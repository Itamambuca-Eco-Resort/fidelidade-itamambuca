<?php
/**
 * Página de Check-in para o Hóspede
 * URL: /checkin?token=abc... (dinâmico) ou /checkin?qr=code (fixo)
 */
require_once 'config/config.php';

$token     = trim($_GET['token'] ?? '');
$qrFixed   = trim($_GET['qr'] ?? '');
$pageTitle = 'Check-in | Fidelidade Itamambuca';

$redirectUrl = '/checkin';
if ($token) $redirectUrl .= '?token=' . urlencode($token);
elseif ($qrFixed) $redirectUrl .= '?qr=' . urlencode($qrFixed);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(160deg, #0f2b3c 0%, #1e4344 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .checkin-card {
      background: #fff;
      border-radius: 28px;
      width: 100%;
      max-width: 400px;
      padding: 2.5rem 2rem;
      text-align: center;
      box-shadow: 0 24px 60px rgba(0,0,0,.35);
    }

    /* States */
    .state { display: none; }
    .state.active { display: block; }

    /* Loading */
    .spinner {
      width: 56px; height: 56px;
      border: 5px solid #e0e0e0;
      border-top-color: #1e4344;
      border-radius: 50%;
      animation: spin .8s linear infinite;
      margin: 0 auto 1.25rem;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Success */
    .icon-success {
      width: 80px; height: 80px; border-radius: 50%;
      background: linear-gradient(135deg, #1e4344, #1e8b8b);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 2.2rem; color: #fff;
      animation: popIn .45s cubic-bezier(.34,1.56,.64,1) both;
    }
    @keyframes popIn {
      from { transform: scale(0); opacity: 0; }
      to   { transform: scale(1); opacity: 1; }
    }
    .points-badge {
      display: inline-flex; align-items: center; gap: .4rem;
      background: #c9a961; color: #fff;
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-size: 2rem; font-weight: 700;
      padding: .5rem 1.5rem; border-radius: 50px;
      margin: 1rem 0;
      animation: popIn .5s .15s cubic-bezier(.34,1.56,.64,1) both;
    }

    /* Confetti canvas */
    #confetti-canvas {
      position: fixed; inset: 0;
      pointer-events: none;
      z-index: 999;
    }

    /* Error */
    .icon-error {
      width: 80px; height: 80px; border-radius: 50%;
      background: linear-gradient(135deg, #e53e3e, #c53030);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 2.2rem; color: #fff;
    }

    /* Not logged in */
    .icon-lock {
      width: 80px; height: 80px; border-radius: 50%;
      background: linear-gradient(135deg, #c9a961, #b19149);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 2.2rem; color: #fff;
    }

    h2 {
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-size: 1.5rem; font-weight: 700; color: #1a1a2e;
      margin-bottom: .5rem;
    }
    p { color: #666; font-size: .92rem; line-height: 1.5; margin-bottom: 1rem; }

    .btn-main {
      display: block; width: 100%;
      background: linear-gradient(135deg, #1e4344, #1e8b8b);
      color: #fff; text-decoration: none;
      padding: .85rem; border-radius: 50px;
      font-weight: 700; font-size: .95rem;
      margin-top: .75rem; border: none; cursor: pointer;
    }
    .btn-ghost {
      display: block; width: 100%;
      background: transparent; color: #1e4344;
      border: 2px solid #1e4344;
      padding: .75rem; border-radius: 50px;
      font-weight: 600; font-size: .9rem;
      margin-top: .5rem; text-decoration: none;
    }

    .logo-small {
      width: 48px; height: 48px; border-radius: 50%;
      object-fit: cover; margin-bottom: 1.25rem;
    }
  </style>
</head>
<body>

<canvas id="confetti-canvas"></canvas>

<div class="checkin-card">
  <img src="/assets/images/icon.png" alt="Itamambuca" class="logo-small"
       onerror="this.style.display='none'">

  <!-- Estado: carregando -->
  <div class="state active" id="stateLoading">
    <div class="spinner"></div>
    <h2>Verificando...</h2>
    <p>Validando seu check-in na atividade</p>
  </div>

  <!-- Estado: sucesso -->
  <div class="state" id="stateSuccess">
    <div class="icon-success"><i class="bi bi-check-lg"></i></div>
    <h2>Check-in Confirmado! 🎉</h2>
    <p id="successActivityName" style="font-weight:600; color:#1e4344; font-size:1rem;"></p>
    <div class="points-badge" id="successPoints">
      <i class="bi bi-star-fill" style="font-size:1.1rem;"></i>
      <span>+50 pontos</span>
    </div>
    <p>Pontos adicionados ao seu perfil de fidelidade.</p>
    <a href="/" class="btn-main"><i class="bi bi-house"></i> Ver meu perfil</a>
    <a href="/perfil.php" class="btn-ghost"><i class="bi bi-trophy"></i> Ver pontuação</a>
  </div>

  <!-- Estado: não logado -->
  <div class="state" id="stateLogin">
    <div class="icon-lock"><i class="bi bi-lock-fill"></i></div>
    <h2>Faça Login</h2>
    <p>Para registrar seu check-in e ganhar pontos, você precisa estar logado no programa de fidelidade.</p>
    <a href="/auth/login.php?redirect=<?= urlencode($redirectUrl) ?>" class="btn-main">
      <i class="bi bi-box-arrow-in-right"></i> Entrar agora
    </a>
    <a href="/auth/register-turista.php" class="btn-ghost">
      <i class="bi bi-person-plus"></i> Criar conta grátis
    </a>
  </div>

  <!-- Estado: erro -->
  <div class="state" id="stateError">
    <div class="icon-error"><i class="bi bi-x-lg"></i></div>
    <h2 id="errTitle">QR Code inválido</h2>
    <p id="errMsg">Este QR Code não pôde ser validado.</p>
    <a href="/" class="btn-main"><i class="bi bi-house"></i> Voltar ao início</a>
  </div>
</div>

<script>
const TOKEN = '<?= addslashes(htmlspecialchars($token)) ?>';
const QR_FIXED = '<?= addslashes(htmlspecialchars($qrFixed)) ?>';
const LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;

function show(id) {
  document.querySelectorAll('.state').forEach(s => s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

async function doCheckin() {
  if (!LOGGED_IN) {
    show('stateLogin');
    return;
  }

  if (!TOKEN && !QR_FIXED) {
    document.getElementById('errTitle').textContent = 'Link Inválido';
    document.getElementById('errMsg').textContent = 'O link de check-in está incompleto.';
    show('stateError');
    return;
  }

  try {
    // Escolher API baseada no parâmetro
    const apiUrl = TOKEN ? '<?= SITE_URL ?>/api/validate-checkin-token.php' : '<?= SITE_URL ?>/api/scan-qr.php';
    const body   = TOKEN ? { token: TOKEN } : { qr_code: QR_FIXED };

    const resp = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await resp.json();

    if (data.success) {
      document.getElementById('successActivityName').textContent = data.partner_name || 'Atividade do Resort';
      document.getElementById('successPoints').innerHTML =
        `<i class="bi bi-star-fill" style="font-size:1.1rem;"></i> <span>+${data.points_earned} pontos</span>`;
      show('stateSuccess');
      launchConfetti();
    } else {
      document.getElementById('errTitle').textContent =
        data.message.includes('expirado') ? 'QR Code Expirado' :
        data.message.includes('limite') || data.message.includes('atingiu') ? 'Limite Diário' : 
        data.message.includes('recentemente') ? 'Check-in Recente' : 'Erro no Check-in';
      document.getElementById('errMsg').textContent = data.message;
      show('stateError');
    }
  } catch (e) {
    document.getElementById('errTitle').textContent = 'Falha de Conexão';
    document.getElementById('errMsg').textContent = 'Verifique sua internet e tente novamente.';
    show('stateError');
  }
}

// Confetti simples
function launchConfetti() {
  const canvas = document.getElementById('confetti-canvas');
  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const colors = ['#c9a961', '#1e4344', '#1e8b8b', '#fff', '#f59e0b'];
  const pieces = Array.from({ length: 80 }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height - canvas.height,
    r: Math.random() * 8 + 4,
    d: Math.random() * 80 + 20,
    color: colors[Math.floor(Math.random() * colors.length)],
    tilt: Math.random() * 10 - 10,
    tiltAngle: 0,
    tiltAngleInc: Math.random() * 0.07 + 0.05
  }));

  let angle = 0;
  function draw() {
    if (pieces.every(p => p.y > canvas.height)) {
      canvas.style.display = 'none';
      return;
    }
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    angle += 0.01;
    pieces.forEach(p => {
      p.tiltAngle += p.tiltAngleInc;
      p.y += (Math.cos(angle + p.d) + 2.5);
      p.x += Math.sin(angle);
      p.tilt = Math.sin(p.tiltAngle) * 12;
      ctx.beginPath();
      ctx.lineWidth = p.r;
      ctx.strokeStyle = p.color;
      ctx.moveTo(p.x + p.tilt + p.r / 4, p.y);
      ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 4);
      ctx.stroke();
    });
    requestAnimationFrame(draw);
  }
  draw();
}

// Iniciar ao carregar
window.addEventListener('load', doCheckin);
</script>
</body>
</html>
