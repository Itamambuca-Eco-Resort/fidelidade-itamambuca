<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';
require_once 'models/Gamification.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$gamification = new Gamification();
$userPoints = $gamification->getUserPoints($_SESSION['user_id']);
$userBadges = $gamification->getUserBadges($_SESSION['user_id']);
$userStats = $gamification->getUserStats($_SESSION['user_id']);
$recentCheckins = $gamification->getUserCheckins($_SESSION['user_id'], 10);
$pointHistory = $gamification->getPointHistory($_SESSION['user_id'], 20);
$userRanking = $gamification->getUserRanking($_SESSION['user_id']);

$pageTitle = 'Meu Perfil';
include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho do Perfil -->
    <div class="profile-header text-center mb-4">
        <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, <?= $userPoints['level_color'] ?? '#6366f1' ?>, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
        </div>
        <h2 class="mb-1 fw-bold"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
        <p class="text-muted mb-3"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
        
        <div class="d-flex justify-content-center gap-2 mb-3">
            <span class="badge" style="background: <?= $userPoints['level_color'] ?? '#6366f1' ?>; font-size: 0.9rem; padding: 0.6rem 1.2rem; border-radius: 50px;">
                <?= $userPoints['level_icon'] ?? '🌱' ?> <?= $userPoints['level_name'] ?? 'Explorador' ?>
            </span>
            <a href="recompensas.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold shadow-sm" style="display: flex; align-items: center; gap: 5px;">
                <i class="bi bi-gift"></i> Trocar Pontos
            </a>
        </div>
        
        <a href="scan.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
            <i class="bi bi-qr-code-scan"></i> Scanner QR Check-in
        </a>
    </div>

    <!-- Estatísticas Rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center p-3 rounded-3" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <div class="stat-icon mb-2" style="font-size: 2rem;">🏆</div>
                <div class="stat-value text-white fw-bold" style="font-size: 1.5rem;"><?= $userPoints['total_points'] ?? 0 ?></div>
                <div class="stat-label text-white-50 small">Pontos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center p-3 rounded-3" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <div class="stat-icon mb-2" style="font-size: 2rem;">📍</div>
                <div class="stat-value text-white fw-bold" style="font-size: 1.5rem;"><?= $userStats['total_checkins'] ?? 0 ?></div>
                <div class="stat-label text-white-50 small">Check-ins</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center p-3 rounded-3" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="stat-icon mb-2" style="font-size: 2rem;">🎖️</div>
                <div class="stat-value text-white fw-bold" style="font-size: 1.5rem;"><?= count($userBadges) ?></div>
                <div class="stat-label text-white-50 small">Badges</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center p-3 rounded-3" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="stat-icon mb-2" style="font-size: 2rem;">#</div>
                <div class="stat-value text-white fw-bold" style="font-size: 1.5rem;"><?= $userRanking ?? '-' ?></div>
                <div class="stat-label text-white-50 small">Ranking</div>
            </div>
        </div>
    </div>

    <!-- Benefícios do Nível -->
    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: <?= $userPoints['level_color'] ?? '#1e4344' ?>; color: #fff;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-star-fill"></i> Benefícios: Nível <?= htmlspecialchars($userPoints['level_name'] ?? 'Essencial') ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($userPoints['level_description'])): ?>
                <ul class="list-unstyled mb-0">
                    <?php 
                    $benefits = explode(',', $userPoints['level_description']);
                    foreach ($benefits as $benefit): 
                        $benefit = trim($benefit);
                        if (empty($benefit)) continue;
                    ?>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <span class="small"><?= htmlspecialchars($benefit) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted small mb-0">Suba de nível para desbloquear benefícios exclusivos do Resort!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progresso para Próximo Nível -->
    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">Próximo Nível</h6>
                <span class="text-muted small"><?= $userPoints['total_points'] ?? 0 ?> / <?= ($userPoints['points_to_next_level'] ?? 0) + ($userPoints['total_points'] ?? 0) ?> pts</span>
            </div>
            <?php
            $currentPoints = $userPoints['total_points'] ?? 0;
            $nextLevelPoints = ($userPoints['points_to_next_level'] ?? 100) + $currentPoints;
            $progress = $nextLevelPoints > 0 ? min(100, ($currentPoints / $nextLevelPoints) * 100) : 0;
            ?>
            <div class="progress" style="height: 12px; border-radius: 10px; background: #eaedf0;">
                <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%; background: <?= $userPoints['level_color'] ?? '#1e4344' ?>; border-radius: 10px;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            <?php if ($userPoints['points_to_next_level'] > 0): ?>
                <p class="text-muted x-small mt-2 mb-0" style="font-size: 0.75rem;">Faltam <strong><?= $userPoints['points_to_next_level'] ?> pontos</strong> para você subir de nível!</p>
            <?php else: ?>
                <p class="text-success x-small mt-2 mb-0" style="font-size: 0.75rem;">Você atingiu o nível máximo! Parabéns!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs de Navegação -->
    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="badges-tab" data-bs-toggle="tab" data-bs-target="#badges" type="button">
                <i class="bi bi-award"></i> Badges
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="checkins-tab" data-bs-toggle="tab" data-bs-target="#checkins" type="button">
                <i class="bi bi-geo-alt"></i> Check-ins
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="bi bi-clock-history"></i> Histórico
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Tabs -->
    <div class="tab-content" id="profileTabsContent">
        <!-- Tab Badges -->
        <div class="tab-pane fade show active" id="badges" role="tabpanel">
            <?php if (empty($userBadges)): ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; opacity: 0.3;">🎖️</div>
                    <p class="text-muted">Você ainda não conquistou nenhuma badge.</p>
                    <a href="badges.php" class="btn btn-primary">Ver Badges Disponíveis</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($userBadges as $badge): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="badge-card text-center p-3 rounded-3 h-100" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); border: 2px solid <?= 
                                $badge['rarity'] === 'legendary' ? '#fbbf24' : 
                                ($badge['rarity'] === 'epic' ? '#a855f7' : 
                                ($badge['rarity'] === 'rare' ? '#3b82f6' : '#10b981')) 
                            ?>;">
                                <div class="badge-icon mb-2" style="font-size: 3rem;"><?= $badge['icon'] ?></div>
                                <h6 class="mb-1"><?= htmlspecialchars($badge['name']) ?></h6>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($badge['description']) ?></p>
                                <span class="badge bg-secondary small"><?= ucfirst($badge['rarity']) ?></span>
                                <div class="text-muted small mt-2">
                                    <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($badge['earned_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Check-ins -->
        <div class="tab-pane fade" id="checkins" role="tabpanel">
            <?php if (empty($recentCheckins)): ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; opacity: 0.3;">📍</div>
                    <p class="text-muted">Você ainda não fez nenhum check-in.</p>
                    <a href="index.php" class="btn btn-primary">Explorar Parceiros</a>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recentCheckins as $checkin): ?>
                        <a href="servico.php?slug=<?= $checkin['slug'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <?php if ($checkin['verified']): ?>
                                            <i class="bi bi-patch-check-fill text-success"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($checkin['partner_name']) ?>
                                    </h6>
                                    <p class="mb-1 small text-muted">
                                        <i class="bi bi-tag"></i> <?= htmlspecialchars($checkin['category_name']) ?>
                                    </p>
                                    <?php if ($checkin['comment']): ?>
                                        <p class="mb-0 small"><?= htmlspecialchars($checkin['comment']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">+<?= $checkin['points_earned'] ?> pts</span>
                                    <div class="text-muted small mt-1"><?= date('d/m/Y H:i', strtotime($checkin['created_at'])) ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Histórico -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <?php if (empty($pointHistory)): ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; opacity: 0.3;">📊</div>
                    <p class="text-muted">Nenhuma atividade registrada ainda.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($pointHistory as $transaction): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <?php
                                        $icons = [
                                            'checkin' => '📍',
                                            'coupon_redeem' => '🎟️',
                                            'review' => '⭐',
                                            'share' => '📱',
                                            'referral' => '🤝',
                                            'mission_complete' => '✅',
                                            'daily_bonus' => '🎁',
                                            'level_up' => '🆙',
                                            'badge_earned' => '🎖️',
                                            'other' => '💫'
                                        ];
                                        echo $icons[$transaction['action_type']] ?? '💫';
                                        ?>
                                        <?= htmlspecialchars($transaction['description']) ?>
                                    </h6>
                                    <p class="mb-0 small text-muted"><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></p>
                                </div>
                                <div>
                                    <span class="badge <?= $transaction['points'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $transaction['points'] > 0 ? '+' : '' ?><?= $transaction['points'] ?> pts
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
