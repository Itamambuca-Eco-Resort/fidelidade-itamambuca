<?php
session_start();
require_once 'config/database.php';
require_once 'models/Gamification.php';

$gamification = new Gamification();
$allBadges = $gamification->getAllBadges();

// Obter badges do usuário se estiver logado
$userBadges = [];
if (isset($_SESSION['user_id'])) {
    $userBadgesData = $gamification->getUserBadges($_SESSION['user_id']);
    foreach ($userBadgesData as $badge) {
        $userBadges[$badge['id']] = true;
    }
}

$pageTitle = 'Badges';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold mb-2">🎖️ Galeria de Badges</h1>
        <p class="text-muted">Conquiste todas as badges e mostre seu domínio!</p>
    </div>

    <?php
    $categories = [
        'checkin' => ['name' => 'Check-in', 'icon' => '📍'],
        'exploration' => ['name' => 'Exploração', 'icon' => '🧭'],
        'time' => ['name' => 'Tempo', 'icon' => '⏰'],
        'social' => ['name' => 'Social', 'icon' => '👥'],
        'special' => ['name' => 'Especial', 'icon' => '⭐']
    ];

    foreach ($categories as $catKey => $catData):
        $catBadges = array_filter($allBadges, fn($b) => $b['category'] === $catKey);
        if (empty($catBadges)) continue;
    ?>
        <div class="mb-5">
            <h3 class="mb-3"><?= $catData['icon'] ?> <?= $catData['name'] ?></h3>
            <div class="row g-3">
                <?php foreach ($catBadges as $badge): 
                    $earned = isset($userBadges[$badge['id']]);
                    $rarityColors = [
                        'legendary' => '#fbbf24',
                        'epic' => '#a855f7',
                        'rare' => '#3b82f6',
                        'common' => '#10b981'
                    ];
                    $borderColor = $rarityColors[$badge['rarity']] ?? '#10b981';
                ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="badge-card text-center p-3 rounded-3 h-100 <?= $earned ? '' : 'opacity-50' ?>" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); border: 2px solid <?= $borderColor ?>; position: relative;">
                            <?php if ($earned): ?>
                                <div class="badge-earned-mark" style="position: absolute; top: 10px; right: 10px; background: #10b981; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    ✓
                                </div>
                            <?php endif; ?>
                            <div class="badge-icon mb-2" style="font-size: 3rem; filter: <?= $earned ? 'none' : 'grayscale(1)' ?>;">
                                <?= $badge['icon'] ?>
                            </div>
                            <h6 class="mb-1"><?= htmlspecialchars($badge['name']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($badge['description']) ?></p>
                            <div class="mb-2">
                                <span class="badge" style="background: <?= $borderColor ?>;">
                                    <?= ucfirst($badge['rarity']) ?>
                                </span>
                            </div>
                            <div class="text-primary small fw-bold">
                                +<?= $badge['points_reward'] ?> pontos
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
