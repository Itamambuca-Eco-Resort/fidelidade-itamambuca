<?php
require_once 'config/config.php';
require_once 'models/Partner.php';
require_once 'models/PartnerPhoto.php';
require_once 'models/Coupon.php';
require_once 'models/Gamification.php';

// Restrito a usuários logados (opcional, mas mantido conforme original)
if (!isLoggedIn()) {
    header('Location: auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$slug = $_GET['slug'] ?? '';
if (empty($slug)) redirect('/');

require_once 'config/database.php';

$partnerModel = new Partner();
$photoModel   = new PartnerPhoto();
$couponModel  = new Coupon();
$gamification = new Gamification();
$db           = Database::getInstance()->getConnection();

$partner = $partnerModel->findBySlug($slug);
if (!$partner) redirect('/');

$userId    = $_SESSION['user_id'] ?? null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$referrer  = $_SERVER['HTTP_REFERER'] ?? null;
$partnerModel->recordView($partner['id'], $userId, $ipAddress, $referrer);

$photos  = $photoModel->findByPartner($partner['id']);
$coupons = $couponModel->findActiveByPartner($partner['id']);
$rewards = $gamification->getPartnerRewards($partner['id']);

$pageTitle       = $partner['name'];
$pageDescription = $partner['short_description'] ?? 'Atividade oficial do Itamambuca Eco Resort';

include 'includes/header.php';
?>

<style>
    .partner-hero {
        position: relative;
        background: #f8f9fa;
    }
    .swiper-container {
        width: 100%;
        height: 350px;
        border-radius: 0 0 24px 24px;
        overflow: hidden;
    }
    @media (min-width: 768px) {
        .swiper-container {
            height: 500px;
            border-radius: 24px;
            margin-top: 1rem;
        }
    }
    .swiper-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .partner-content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 992px) {
        .partner-content-grid {
            grid-template-columns: 1fr 360px;
        }
    }
    .sticky-sidebar {
        position: sticky;
        top: 90px;
    }
    .section-title {
        font-family: 'Barlow Semi Condensed', sans-serif;
        font-size: 1.5rem;
        color: #1e4344;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .info-card {
        background: #fff;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        border: 1px solid #f0f0f0;
    }
    .coupon-card {
        background: linear-gradient(135deg, #1e8b8b, #146c6c);
        color: #fff;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
    }
    .coupon-card::before, .coupon-card::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: #f8f9fa;
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
    }
    .coupon-card::before { left: -10px; }
    .coupon-card::after { right: -10px; }
    
    .reward-card {
        border: 1px solid #e0e0e0;
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s;
        height: 100%;
    }
    .reward-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .reward-img {
        height: 120px;
        width: 100%;
        object-fit: cover;
    }
    .points-badge {
        background: #f59e0b;
        color: #fff;
        padding: 4px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .checkin-btn {
        background: linear-gradient(135deg, #c9a961, #b19149);
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 50px;
        width: 100%;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(201,169,97,0.3);
        margin-top: 1rem;
    }
</style>

<div class="partner-hero mb-4">
    <div class="container-fluid p-0 container-lg">
        <?php if (!empty($photos)): ?>
        <div class="swiper swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($photos as $photo): ?>
                <div class="swiper-slide">
                    <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($partner['name']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next" style="color: #fff;"></div>
            <div class="swiper-button-prev" style="color: #fff;"></div>
        </div>
        <?php else: ?>
        <div class="swiper-container">
            <img src="https://via.placeholder.com/1200x600?text=<?= urlencode($partner['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container pb-5">
    <div class="partner-content-grid">
        <!-- Coluna Principal -->
        <div class="main-column">
            <!-- Cabeçalho Informativo -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="fw-bold mb-2" style="font-family: 'Barlow Semi Condensed', sans-serif; color: #1e4344;"><?= htmlspecialchars($partner['name']) ?></h1>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background: #1e8b8b;"><?= htmlspecialchars($partner['category_name'] ?? 'Categoria') ?></span>
                        <?php if (isset($partner['subscription_status']) && $partner['subscription_status'] === 'active'): ?>
                            <span class="badge bg-success"><i class="bi bi-patch-check-fill"></i> Oficial</span>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="favorite-btn btn btn-outline-light rounded-circle shadow-sm" data-partner-id="<?= $partner['id'] ?>" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #eee;">
                    <i class="bi bi-heart text-muted" style="font-size: 1.2rem;"></i>
                </button>
            </div>

            <!-- ── Seção Check-in com QR Fixo ── -->
            <?php if (!empty($partner['qr_code'])): ?>
            <?php
              // Verificar check-ins do usuário hoje nesta atividade
              $checkinsTodayCount = 0;
              if ($userId) {
                try {
                  $stmtCi = $db->prepare("SELECT COUNT(*) FROM check_ins WHERE user_id = ? AND partner_id = ? AND DATE(created_at) = CURDATE()");
                  $stmtCi->execute([$userId, $partner['id']]);
                  $checkinsTodayCount = (int)$stmtCi->fetchColumn();
                } catch (Exception $e) {}
              }
              $checkinUrl = SITE_URL . '/checkin.php?qr=' . urlencode($partner['qr_code']);
            ?>
            <div class="info-card" style="border: 2px solid #c9a961; background: linear-gradient(135deg, #fff9ee, #fff); text-align:center;">
              <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; text-align:left;">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#c9a961,#b19149);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="bi bi-qr-code-scan" style="color:#fff;font-size:1.3rem;"></i>
                </div>
                <div>
                  <h3 style="font-family:'Barlow Semi Condensed',sans-serif;color:#1e4344;font-size:1.1rem;font-weight:700;margin:0;">Ganhe Pontos Aqui!</h3>
                  <p style="color:#888;font-size:.8rem;margin:0;">+100 pontos por check-in &middot; Máx. 2x por dia</p>
                </div>
              </div>

              <?php if ($checkinsTodayCount >= 2): ?>
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:12px;padding:.75rem;margin-bottom:1rem;font-size:.85rem;color:#856404;">
                  <i class="bi bi-clock-history"></i> Você já usou os 2 check-ins de hoje nesta atividade. Volte amanhã!
                </div>
              <?php else: ?>
                <!-- QR Code Oculto na Página Pública (Apenas para o Staff) -->
                <div style="background: rgba(201, 169, 97, 0.05); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; border: 1px dashed #c9a961;">
                    <p style="font-size: .85rem; color: #b19149; margin: 0;">
                        <i class="bi bi-info-circle"></i> O check-in deve ser feito escaneando o QR Code físico disponível no local da atividade.
                    </p>
                </div>
              <?php endif; ?>

              <a href="/scan.php" class="checkin-btn d-flex align-items-center justify-content-center gap-2" style="margin-top:0;<?= $checkinsTodayCount >= 2 ? 'opacity:.4;pointer-events:none;' : '' ?>">
                <i class="bi bi-qr-code-scan"></i> Escanear QR Code do Local
              </a>
            </div>
            <?php endif; ?>

            <!-- Cupons Ativos -->
            <?php if (!empty($coupons)): ?>
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-ticket-perforated"></i> Cupons de Desconto</h3>
                <div class="row g-3">
                    <?php foreach ($coupons as $coupon): ?>
                    <div class="col-md-6">
                        <div class="coupon-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold"><?= htmlspecialchars($coupon['title']) ?></span>
                                <span class="badge bg-white text-dark"><?= $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'].'%' : 'R$ '.$coupon['discount_value'] ?> OFF</span>
                            </div>
                            <p class="small mb-2 opacity-75"><?= htmlspecialchars($coupon['description'] ?? '') ?></p>
                            <div class="mt-2 p-2 bg-white text-dark rounded-3 text-center fw-bold font-monospace" style="letter-spacing: 2px;">
                                <?= htmlspecialchars($coupon['code']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prêmios e Ofertas (Recompensas) -->
            <?php if (!empty($rewards)): ?>
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-gift"></i> Troque seus Pontos aqui</h3>
                <div class="row g-3">
                    <?php foreach ($rewards as $reward): ?>
                    <div class="col-6 col-md-4">
                        <div class="reward-card">
                            <img src="<?= $reward['image_url'] ?: '/assets/images/placeholder-gift.jpg' ?>" class="reward-img" alt="<?= htmlspecialchars($reward['title']) ?>">
                            <div class="p-3 text-center">
                                <h6 class="mb-2 fw-bold small"><?= htmlspecialchars($reward['title']) ?></h6>
                                <span class="points-badge"><?= $reward['points_cost'] ?> pts</span>
                                <a href="recompensas.php?redeem=<?= $reward['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mt-2 rounded-pill">Resgatar</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($partner['slug'] === 'room-service'): ?>
            <div class="mb-4">
                <a href="#" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm" style="background:#1e4344;">
                    <i class="bi bi-book"></i> Ver Cardápio Digital
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Lateral (Informações) -->
        <div class="sidebar-column">
            <div class="sticky-sidebar">
                <div class="info-card">
                    <h5 class="fw-bold mb-3" style="color: #1e4344;"><i class="bi bi-info-circle"></i> Informações</h5>
                    <p class="small text-muted mb-0">Esta é uma atividade oficial do Itamambuca Eco Resort. Para mais informações, consulte a recepção.</p>
                </div>

                <?php if (!empty($partner['business_hours'])): ?>
                <?php $hours = json_decode($partner['business_hours'], true); ?>
                <?php if (!empty($hours)): ?>
                <div class="info-card">
                    <h5 class="fw-bold mb-3" style="color: #1e4344;"><i class="bi bi-clock"></i> Funcionamento</h5>
                    <div class="small">
                        <?php foreach ($hours as $day => $time): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><?= ucfirst($day) ?></span>
                            <span class="fw-semibold"><?= htmlspecialchars($time) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const swiper = new Swiper('.swiper-container', {
            loop: true,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            autoplay: { delay: 4000 }
        });

        // Toggle Favorito
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const partnerId = this.dataset.partnerId;
                const icon = this.querySelector('i');
                
                fetch('/api/toggle-favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ partner_id: partnerId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'added') {
                            icon.classList.replace('bi-heart', 'bi-heart-fill');
                            icon.classList.add('text-danger');
                        } else {
                            icon.classList.replace('bi-heart-fill', 'bi-heart');
                            icon.classList.remove('text-danger');
                        }
                    }
                });
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
