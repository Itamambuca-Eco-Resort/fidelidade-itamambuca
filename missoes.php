<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar missões ativas
$stmt = $db->prepare("
    SELECT m.*, 
           COALESCE(um.progress, 0) as user_progress,
           COALESCE(um.is_completed, 0) as is_completed
    FROM missions m
    LEFT JOIN user_missions um ON m.id = um.mission_id 
        AND um.user_id = ? 
        AND um.period_start <= CURDATE() 
        AND um.period_end >= CURDATE()
    WHERE m.is_active = 1
    ORDER BY 
        FIELD(m.mission_type, 'daily', 'weekly', 'monthly', 'special'),
        m.points_reward DESC
");
$stmt->execute([$_SESSION['user_id']]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por tipo
$missionsByType = [
    'daily' => [],
    'weekly' => [],
    'monthly' => [],
    'special' => []
];

foreach ($missions as $mission) {
    $missionsByType[$mission['mission_type']][] = $mission;
}

$pageTitle = 'Missões';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold mb-2">🎯 Missões</h1>
        <p class="text-muted">Complete missões e ganhe pontos extras!</p>
    </div>

    <!-- Missões Diárias -->
    <?php if (!empty($missionsByType['daily'])): ?>
        <div class="mb-4">
            <h3 class="mb-3">📅 Missões Diárias</h3>
            <p class="text-muted small mb-3">Renovam todo dia às 00:00</p>
            <div class="row g-3">
                <?php foreach ($missionsByType['daily'] as $mission): 
                    $progress = ($mission['user_progress'] / $mission['requirement_count']) * 100;
                    $isCompleted = $mission['is_completed'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm <?= $isCompleted ? 'bg-success bg-opacity-10' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($mission['title']) ?></h5>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge bg-success">✓ Completa</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text text-muted small"><?= htmlspecialchars($mission['description']) ?></p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progresso</span>
                                        <span><?= $mission['user_progress'] ?> / <?= $mission['requirement_count'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar <?= $isCompleted ? 'bg-success' : '' ?>" style="width: <?= min($progress, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">+<?= $mission['points_reward'] ?> pts</span>
                                    <span class="text-muted small">
                                        <i class="bi bi-<?= 
                                            $mission['requirement_type'] === 'checkin' ? 'geo-alt' :
                                            ($mission['requirement_type'] === 'coupon' ? 'ticket-perforated' :
                                            ($mission['requirement_type'] === 'review' ? 'star' : 'share'))
                                        ?>"></i>
                                        <?= ucfirst($mission['requirement_type']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Missões Semanais -->
    <?php if (!empty($missionsByType['weekly'])): ?>
        <div class="mb-4">
            <h3 class="mb-3">📆 Missões Semanais</h3>
            <p class="text-muted small mb-3">Renovam toda segunda-feira</p>
            <div class="row g-3">
                <?php foreach ($missionsByType['weekly'] as $mission): 
                    $progress = ($mission['user_progress'] / $mission['requirement_count']) * 100;
                    $isCompleted = $mission['is_completed'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm <?= $isCompleted ? 'bg-success bg-opacity-10' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($mission['title']) ?></h5>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge bg-success">✓ Completa</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text text-muted small"><?= htmlspecialchars($mission['description']) ?></p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progresso</span>
                                        <span><?= $mission['user_progress'] ?> / <?= $mission['requirement_count'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info <?= $isCompleted ? 'bg-success' : '' ?>" style="width: <?= min($progress, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-info">+<?= $mission['points_reward'] ?> pts</span>
                                    <span class="text-muted small">
                                        <i class="bi bi-<?= 
                                            $mission['requirement_type'] === 'checkin' ? 'geo-alt' :
                                            ($mission['requirement_type'] === 'coupon' ? 'ticket-perforated' :
                                            ($mission['requirement_type'] === 'review' ? 'star' : 'share'))
                                        ?>"></i>
                                        <?= ucfirst($mission['requirement_type']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Missões Mensais -->
    <?php if (!empty($missionsByType['monthly'])): ?>
        <div class="mb-4">
            <h3 class="mb-3">📊 Missões Mensais</h3>
            <p class="text-muted small mb-3">Renovam no primeiro dia do mês</p>
            <div class="row g-3">
                <?php foreach ($missionsByType['monthly'] as $mission): 
                    $progress = ($mission['user_progress'] / $mission['requirement_count']) * 100;
                    $isCompleted = $mission['is_completed'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm <?= $isCompleted ? 'bg-success bg-opacity-10' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($mission['title']) ?></h5>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge bg-success">✓ Completa</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text text-muted small"><?= htmlspecialchars($mission['description']) ?></p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progresso</span>
                                        <span><?= $mission['user_progress'] ?> / <?= $mission['requirement_count'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning <?= $isCompleted ? 'bg-success' : '' ?>" style="width: <?= min($progress, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-warning text-dark">+<?= $mission['points_reward'] ?> pts</span>
                                    <span class="text-muted small">
                                        <i class="bi bi-<?= 
                                            $mission['requirement_type'] === 'checkin' ? 'geo-alt' :
                                            ($mission['requirement_type'] === 'coupon' ? 'ticket-perforated' :
                                            ($mission['requirement_type'] === 'review' ? 'star' : 'share'))
                                        ?>"></i>
                                        <?= ucfirst($mission['requirement_type']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Missões Especiais -->
    <?php if (!empty($missionsByType['special'])): ?>
        <div class="mb-4">
            <h3 class="mb-3">⭐ Missões Especiais</h3>
            <p class="text-muted small mb-3">Missões por tempo limitado</p>
            <div class="row g-3">
                <?php foreach ($missionsByType['special'] as $mission): 
                    $progress = ($mission['user_progress'] / $mission['requirement_count']) * 100;
                    $isCompleted = $mission['is_completed'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm" style="border: 2px solid #fbbf24 !important; <?= $isCompleted ? 'background: rgba(251, 191, 36, 0.1);' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($mission['title']) ?></h5>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge bg-success">✓ Completa</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text text-muted small"><?= htmlspecialchars($mission['description']) ?></p>
                                
                                <?php if ($mission['end_date']): ?>
                                    <div class="alert alert-warning py-2 mb-3 small">
                                        <i class="bi bi-clock"></i> Termina em <?= date('d/m/Y', strtotime($mission['end_date'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progresso</span>
                                        <span><?= $mission['user_progress'] ?> / <?= $mission['requirement_count'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar" style="background: #fbbf24; width: <?= min($progress, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge" style="background: #fbbf24; color: #000;">+<?= $mission['points_reward'] ?> pts</span>
                                    <span class="text-muted small">
                                        <i class="bi bi-<?= 
                                            $mission['requirement_type'] === 'checkin' ? 'geo-alt' :
                                            ($mission['requirement_type'] === 'coupon' ? 'ticket-perforated' :
                                            ($mission['requirement_type'] === 'review' ? 'star' : 'share'))
                                        ?>"></i>
                                        <?= ucfirst($mission['requirement_type']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($missions)): ?>
        <div class="text-center py-5">
            <div style="font-size: 4rem; opacity: 0.3;">🎯</div>
            <p class="text-muted">Nenhuma missão disponível no momento.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
