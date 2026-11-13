<?php
/**
 * Página do Colaborador — Gerar QR Code de Check-in por Atividade
 * Acesso: admin ou partner
 */
require_once 'config/config.php';

if (!isLoggedIn() || !in_array($_SESSION['user_role'] ?? '', ['admin', 'partner'])) {
    redirect('/auth/login.php');
}

require_once 'config/database.php';

// Buscar todas as atividades ativas
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT p.id, p.name, p.slug, p.main_photo, c.name AS category_name
    FROM partners p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY c.name, p.name
");
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gerar Check-in | Colaborador';
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
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #0f2b3c;
      min-height: 100vh;
      color: #fff;
    }
    .staff-header {
      background: linear-gradient(135deg, #0f2b3c, #1e4344);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .staff-header h1 {
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      color: #c9a961;
    }
    .staff-header a {
      color: rgba(255,255,255,.6);
      text-decoration: none;
      font-size: .85rem;
    }
    .staff-body { padding: 1.5rem; max-width: 540px; margin: 0 auto; }

    /* Seletor de atividade */
    .act-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .75rem;
      margin-bottom: 1.5rem;
    }
    .act-card {
      background: rgba(255,255,255,.07);
      border: 2px solid rgba(255,255,255,.1);
      border-radius: 16px;
      padding: 1rem .75rem;
      text-align: center;
      cursor: pointer;
      transition: all .2s;
      color: rgba(255,255,255,.85);
    }
    .act-card:hover { border-color: #c9a961; background: rgba(201,169,97,.1); }
    .act-card.selected { border-color: #c9a961; background: rgba(201,169,97,.18); color: #fff; }
    .act-card img {
      width: 56px; height: 56px; border-radius: 12px;
      object-fit: cover; margin-bottom: .5rem;
    }
    .act-card .act-placeholder {
      width: 56px; height: 56px; border-radius: 12px;
      background: linear-gradient(135deg,#1e4344,#1e8b8b);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto .5rem; font-size: 1.5rem;
    }
    .act-card strong { display: block; font-size: .85rem; font-weight: 600; }
    .act-card small { color: rgba(255,255,255,.5); font-size: .72rem; }

    .btn-generate {
      width: 100%;
      background: linear-gradient(135deg, #c9a961, #b19149);
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 1rem;
      font-size: 1.05rem;
      font-weight: 700;
      font-family: 'Barlow Semi Condensed', sans-serif;
      letter-spacing: .05em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      transition: opacity .2s;
    }
    .btn-generate:disabled { opacity: .4; cursor: not-allowed; }

    /* QR Panel */
    #qr-panel {
      display: none;
      margin-top: 1.5rem;
    }
    .qr-box {
      background: #fff;
      border-radius: 24px;
      padding: 2rem;
      text-align: center;
      position: relative;
    }
    .qr-box h2 {
      font-family: 'Barlow Semi Condensed', sans-serif;
      color: #1e4344;
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: .25rem;
    }
    .qr-box .qr-sub { color: #888; font-size: .85rem; margin-bottom: 1.25rem; }
    #qrcode-display { display: flex; justify-content: center; margin-bottom: 1.25rem; }
    #qrcode-display canvas { border-radius: 12px !important; }

    /* Countdown */
    .countdown-wrap {
      background: #f5f5f5;
      border-radius: 12px;
      padding: .75rem;
      margin-top: .75rem;
    }
    .countdown-label { font-size: .8rem; color: #888; margin-bottom: .25rem; }
    .countdown-bar-bg {
      background: #e0e0e0;
      border-radius: 99px;
      height: 8px;
      margin-bottom: .4rem;
      overflow: hidden;
    }
    .countdown-bar { height: 100%; border-radius: 99px; background: #1e4344; transition: width .5s linear, background .5s; }
    .countdown-time { font-size: 1.1rem; font-weight: 700; color: #1e4344; }
    .countdown-expired .countdown-bar { background: #e53e3e; }
    .countdown-expired .countdown-time { color: #e53e3e; }

    .badge-pts {
      display: inline-flex; align-items: center; gap: .35rem;
      background: #e8f4f4; color: #1e4344;
      padding: .35rem .9rem; border-radius: 50px;
      font-size: .85rem; font-weight: 700; margin-top: .5rem;
    }

    /* New QR button */
    .btn-new {
      width: 100%;
      background: transparent;
      color: #1e4344;
      border: 2px solid #1e4344;
      border-radius: 50px;
      padding: .75rem;
      font-size: .9rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: .75rem;
      display: flex; align-items: center; justify-content: center; gap: .4rem;
    }

    .alert-err {
      background: rgba(229,62,62,.15); border: 1px solid rgba(229,62,62,.4);
      color: #feb2b2; border-radius: 12px; padding: .75rem 1rem;
      margin-bottom: 1rem; font-size: .9rem; display: none;
    }
  </style>
</head>
<body>

<div class="staff-header">
  <h1><i class="bi bi-qr-code me-2"></i>Check-in Colaborador</h1>
  <a href="/"><i class="bi bi-house"></i> Início</a>
</div>

<div class="staff-body">

  <p style="color:rgba(255,255,255,.55); font-size:.88rem; margin-bottom:1.25rem; line-height:1.5;">
    Selecione a atividade concluída pelo hóspede e gere o QR Code para ele escanear e ganhar pontos.
  </p>

  <!-- Seletor de Atividade -->
  <div class="act-grid" id="actGrid">
    <?php foreach ($activities as $act): ?>
    <div class="act-card" data-id="<?= $act['id'] ?>" data-name="<?= htmlspecialchars($act['name']) ?>" onclick="selectActivity(this)">
      <?php if (!empty($act['main_photo'])): ?>
        <img src="<?= htmlspecialchars($act['main_photo']) ?>" alt="<?= htmlspecialchars($act['name']) ?>">
      <?php else: ?>
        <div class="act-placeholder"><i class="bi bi-building"></i></div>
      <?php endif; ?>
      <strong><?= htmlspecialchars($act['name']) ?></strong>
      <small><?= htmlspecialchars($act['category_name'] ?? '') ?></small>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Erro -->
  <div class="alert-err" id="errMsg"></div>

  <!-- Botão Gerar -->
  <button class="btn-generate" id="btnGenerate" onclick="generateQR()" disabled>
    <i class="bi bi-qr-code-scan"></i> Gerar QR Code de Check-in
  </button>

  <!-- Painel QR -->
  <div id="qr-panel">
    <div class="qr-box">
      <h2 id="qr-title">SPA Check-in</h2>
      <p class="qr-sub">Mostre este QR ao hóspede para escanear</p>
      <div id="qrcode-display"></div>
      <div class="badge-pts"><i class="bi bi-star-fill"></i> +50 pontos para o hóspede</div>
      <div class="countdown-wrap" id="countdownWrap">
        <div class="countdown-label">Expira em</div>
        <div class="countdown-bar-bg"><div class="countdown-bar" id="cBar" style="width:100%"></div></div>
        <div class="countdown-time" id="cTime">5:00</div>
      </div>
      <button class="btn-new" onclick="resetPanel()">
        <i class="bi bi-arrow-counterclockwise"></i> Gerar Novo QR
      </button>
    </div>
  </div>

</div>

<script>
let selectedId   = null;
let selectedName = '';
let countdownInterval = null;
let qrInstance   = null;

function selectActivity(el) {
  document.querySelectorAll('.act-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedId   = el.dataset.id;
  selectedName = el.dataset.name;
  document.getElementById('btnGenerate').disabled = false;
}

async function generateQR() {
  if (!selectedId) return;

  const btn = document.getElementById('btnGenerate');
  const err = document.getElementById('errMsg');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Gerando...';
  err.style.display = 'none';

  try {
    const resp = await fetch('/api/generate-checkin-token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ partner_id: parseInt(selectedId) })
    });
    const data = await resp.json();

    if (!data.success) {
      err.textContent = data.message || 'Erro ao gerar QR Code.';
      err.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-qr-code-scan"></i> Gerar QR Code de Check-in';
      return;
    }

    // Montar QR
    document.getElementById('qr-title').textContent = selectedName + ' — Check-in';
    document.getElementById('qr-panel').style.display = 'block';
    btn.style.display = 'none';
    document.getElementById('actGrid').style.pointerEvents = 'none';
    document.getElementById('actGrid').style.opacity = '.4';

    // Limpar QR anterior
    const container = document.getElementById('qrcode-display');
    container.innerHTML = '';

    qrInstance = new QRCode(container, {
      text: data.checkin_url,
      width: 240, height: 240,
      colorDark: '#0f2b3c',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });

    startCountdown(data.expires_in || 300);

  } catch (e) {
    err.textContent = 'Falha de conexão. Tente novamente.';
    err.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-qr-code-scan"></i> Gerar QR Code de Check-in';
  }
}

function startCountdown(seconds) {
  const total = seconds;
  let remaining = seconds;
  const bar   = document.getElementById('cBar');
  const time  = document.getElementById('cTime');
  const wrap  = document.getElementById('countdownWrap');

  if (countdownInterval) clearInterval(countdownInterval);

  function update() {
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    time.textContent = `${m}:${s.toString().padStart(2, '0')}`;
    bar.style.width  = ((remaining / total) * 100) + '%';

    if (remaining <= 60) {
      wrap.classList.add('countdown-expired');
    }

    if (remaining <= 0) {
      clearInterval(countdownInterval);
      time.textContent = 'Expirado';
      bar.style.width  = '0%';
    }
    remaining--;
  }

  update();
  countdownInterval = setInterval(update, 1000);
}

function resetPanel() {
  clearInterval(countdownInterval);
  document.getElementById('qr-panel').style.display = 'none';
  document.getElementById('qrcode-display').innerHTML = '';
  document.getElementById('actGrid').style.pointerEvents = '';
  document.getElementById('actGrid').style.opacity = '';
  document.getElementById('countdownWrap').classList.remove('countdown-expired');
  document.getElementById('btnGenerate').style.display = '';
  document.getElementById('btnGenerate').disabled = !selectedId;
  document.getElementById('btnGenerate').innerHTML = '<i class="bi bi-qr-code-scan"></i> Gerar QR Code de Check-in';
}
</script>
</body>
</html>
