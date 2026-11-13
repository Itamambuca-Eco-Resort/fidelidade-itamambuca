<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'models/Gamification.php';

// Restrito a usuários logados
if (!isLoggedIn()) {
    header('Location: /?acesso=restrito');
    exit;
}

$gamification = new Gamification();
$leaderboard = $gamification->getLeaderboard(100);

// Obter posição do usuário se estiver logado
$userRanking = null;
if (isset($_SESSION['user_id'])) {
    $userRanking = $gamification->getUserRanking($_SESSION['user_id']);
}

$pageTitle = 'Ranking';
include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Cabeçalho -->
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold mb-2">🏆 Ranking</h1>
        <p class="text-muted">Os maiores exploradores da comunidade Santour</p>
    </div>

    <?php if ($userRanking && isset($_SESSION['user_id'])): ?>
        <!-- Posição do Usuário -->
        <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div class="card-body text-white">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="ranking-position" style="font-size: 3rem; font-weight: bold; opacity: 0.9;">
                            #<?= $userRanking ?>
                        </div>
                    </div>
                    <div class="col">
                        <h5 class="mb-1">Sua Posição</h5>
                        <p class="mb-0 opacity-75">Continue explorando para subir no ranking!</p>
                    </div>
                    <div class="col-auto">
                        <a href="perfil.php" class="btn btn-light">Ver Perfil</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtros (futuro) -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-primary active">Geral</button>
                <button type="button" class="btn btn-outline-primary" disabled>Semanal</button>
                <button type="button" class="btn btn-outline-primary" disabled>Mensal</button>
            </div>
        </div>
    </div>

    <!-- Top 3 Pódio -->
    <?php if (count($leaderboard) >= 3): ?>
        <div class="row g-3 mb-4">
            <!-- 2º Lugar -->
            <div class="col-md-4 order-md-1">
                <div class="podium-card text-center p-4 rounded-3 h-100" style="background: linear-gradient(135deg, rgba(192, 192, 192, 0.2), rgba(169, 169, 169, 0.2)); border: 2px solid #c0c0c0;">
                    <div class="podium-medal mb-3" style="font-size: 3rem;">🥈</div>
                    <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #c0c0c0, #a9a9a9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; font-weight: bold;">
                        <?= strtoupper(substr($leaderboard[1]['name'], 0, 1)) ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($leaderboard[1]['name']) ?></h5>
                    <div class="mb-2">
                        <span class="badge" style="background: <?= $leaderboard[1]['level_icon'] ? '#6366f1' : '#94a3b8' ?>;">
                            <?= $leaderboard[1]['level_icon'] ?? '🌱' ?> <?= $leaderboard[1]['level_name'] ?? 'Explorador' ?>
                        </span>
                    </div>
                    <div class="points-display fw-bold" style="font-size: 1.5rem; color: #c0c0c0;">
                        <?= number_format($leaderboard[1]['total_points']) ?> pts
                    </div>
                    <div class="stats-row mt-3 small text-muted">
                        <span class="me-3"><i class="bi bi-geo-alt"></i> <?= $leaderboard[1]['total_checkins'] ?></span>
                        <span><i class="bi bi-award"></i> <?= $leaderboard[1]['total_badges'] ?></span>
                    </div>
                </div>
            </div>

            <!-- 1º Lugar -->
            <div class="col-md-4 order-md-0">
                <div class="podium-card text-center p-4 rounded-3 h-100" style="background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 193, 7, 0.3)); border: 3px solid #ffd700; transform: scale(1.05);">
                    <div class="podium-medal mb-3" style="font-size: 4rem;">🏆</div>
                    <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, #ffd700, #ffb700); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; font-weight: bold; box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);">
                        <?= strtoupper(substr($leaderboard[0]['name'], 0, 1)) ?>
                    </div>
                    <h4 class="mb-1 fw-bold"><?= htmlspecialchars($leaderboard[0]['name']) ?></h4>
                    <div class="mb-2">
                        <span class="badge" style="background: <?= $leaderboard[0]['level_icon'] ? '#6366f1' : '#94a3b8' ?>; font-size: 1.1rem;">
                            <?= $leaderboard[0]['level_icon'] ?? '🌱' ?> <?= $leaderboard[0]['level_name'] ?? 'Explorador' ?>
                        </span>
                    </div>
                    <div class="points-display fw-bold" style="font-size: 2rem; color: #ffd700;">
                        <?= number_format($leaderboard[0]['total_points']) ?> pts
                    </div>
                    <div class="stats-row mt-3 text-muted">
                        <span class="me-3"><i class="bi bi-geo-alt"></i> <?= $leaderboard[0]['total_checkins'] ?></span>
                        <span><i class="bi bi-award"></i> <?= $leaderboard[0]['total_badges'] ?></span>
                    </div>
                </div>
            </div>

            <!-- 3º Lugar -->
            <div class="col-md-4 order-md-2">
                <div class="podium-card text-center p-4 rounded-3 h-100" style="background: linear-gradient(135deg, rgba(205, 127, 50, 0.2), rgba(184, 115, 51, 0.2)); border: 2px solid #cd7f32;">
                    <div class="podium-medal mb-3" style="font-size: 3rem;">🥉</div>
                    <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #cd7f32, #b87333); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; font-weight: bold;">
                        <?= strtoupper(substr($leaderboard[2]['name'], 0, 1)) ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($leaderboard[2]['name']) ?></h5>
                    <div class="mb-2">
                        <span class="badge" style="background: <?= $leaderboard[2]['level_icon'] ? '#6366f1' : '#94a3b8' ?>;">
                            <?= $leaderboard[2]['level_icon'] ?? '🌱' ?> <?= $leaderboard[2]['level_name'] ?? 'Explorador' ?>
                        </span>
                    </div>
                    <div class="points-display fw-bold" style="font-size: 1.5rem; color: #cd7f32;">
                        <?= number_format($leaderboard[2]['total_points']) ?> pts
                    </div>
                    <div class="stats-row mt-3 small text-muted">
                        <span class="me-3"><i class="bi bi-geo-alt"></i> <?= $leaderboard[2]['total_checkins'] ?></span>
                        <span><i class="bi bi-award"></i> <?= $leaderboard[2]['total_badges'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Ranking Completo -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-list-ol"></i> Ranking Completo</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php 
                $position = 1;
                foreach ($leaderboard as $user): 
                    $isCurrentUser = isset($_SESSION['user_id']) && $user['id'] == $_SESSION['user_id'];
                ?>
                    <div class="list-group-item <?= $isCurrentUser ? 'bg-light' : '' ?>">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="ranking-number fw-bold" style="font-size: 1.5rem; width: 40px; text-align: center; color: <?= 
                                    $position === 1 ? '#ffd700' : 
                                    ($position === 2 ? '#c0c0c0' : 
                                    ($position === 3 ? '#cd7f32' : '#64748b')) 
                                ?>;">
                                    <?= $position ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="avatar-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; font-weight: bold;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                            </div>
                            <div class="col">
                                <h6 class="mb-1">
                                    <?= htmlspecialchars($user['name']) ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="badge bg-primary ms-2">Você</span>
                                    <?php endif; ?>
                                </h6>
                                <div class="small text-muted">
                                    <span class="me-3"><?= $user['level_icon'] ?? '🌱' ?> <?= $user['level_name'] ?? 'Explorador' ?></span>
                                    <span class="me-3"><i class="bi bi-geo-alt"></i> <?= $user['total_checkins'] ?></span>
                                    <span><i class="bi bi-award"></i> <?= $user['total_badges'] ?></span>
                                </div>
                            </div>
                            <div class="col-auto text-end">
                                <div class="points-display fw-bold" style="font-size: 1.2rem; color: #6366f1;">
                                    <?= number_format($user['total_points']) ?>
                                </div>
                                <div class="small text-muted">pontos</div>
                            </div>
                        </div>
                    </div>
                <?php 
                    $position++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- CTA para Login -->
        <div class="text-center mt-4">
            <p class="text-muted">Faça login para aparecer no ranking!</p>
            <a href="auth/login.php" class="btn btn-primary btn-lg">Entrar</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
