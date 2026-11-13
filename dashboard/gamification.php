<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se está logado e é parceiro
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    header('Location: ../auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar partner_id
$stmt = $db->prepare("SELECT id, name FROM partners WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    header('Location: index.php');
    exit;
}

$partnerId = $partner['id'];

// Estatísticas de Check-ins
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_checkins,
        COUNT(CASE WHEN verified = 1 THEN 1 END) as verified_checkins,
        COUNT(DISTINCT user_id) as unique_users,
        SUM(points_earned) as total_points_distributed
    FROM check_ins
    WHERE partner_id = ?
");
$stmt->execute([$partnerId]);
$checkinStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check-ins por dia (últimos 30 dias)
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM check_ins
    WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$partnerId]);
$dailyCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 clientes mais frequentes
$stmt = $db->prepare("
    SELECT 
        u.name,
        u.email,
        COUNT(*) as visit_count,
        MAX(ci.created_at) as last_visit
    FROM check_ins ci
    JOIN users u ON ci.user_id = u.id
    WHERE ci.partner_id = ?
    GROUP BY ci.user_id
    ORDER BY visit_count DESC
    LIMIT 10
");
$stmt->execute([$partnerId]);
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Avaliações
$stmt = $db->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews
    FROM reviews
    WHERE partner_id = ? AND is_approved = 1
");
$stmt->execute([$partnerId]);
$reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Compartilhamentos
$stmt = $db->prepare("
    SELECT COUNT(*) as total_shares
    FROM shares
    WHERE partner_id = ?
");
$stmt->execute([$partnerId]);
$shareStats = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Estatísticas de Gamificação';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up"></i> Estatísticas de Gamificação</h2>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <div class="card-body text-white text-center">
                    <div class="mb-2" style="font-size: 2rem;">📍</div>
                    <div style="font-size: 2.5rem; font-weight: bold;"><?= number_format($checkinStats['total_checkins']) ?></div>
                    <div class="small opacity-75">Total de Check-ins</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body text-white text-center">
                    <div class="mb-2" style="font-size: 2rem;">✅</div>
                    <div style="font-size: 2.5rem; font-weight: bold;"><?= number_format($checkinStats['verified_checkins']) ?></div>
                    <div class="small opacity-75">Check-ins Verificados</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body text-white text-center">
                    <div class="mb-2" style="font-size: 2rem;">👥</div>
                    <div style="font-size: 2.5rem; font-weight: bold;"><?= number_format($checkinStats['unique_users']) ?></div>
                    <div class="small opacity-75">Clientes Únicos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <div class="card-body text-white text-center">
                    <div class="mb-2" style="font-size: 2rem;">🏆</div>
                    <div style="font-size: 2.5rem; font-weight: bold;"><?= number_format($checkinStats['total_points_distributed'] ?? 0) ?></div>
                    <div class="small opacity-75">Pontos Distribuídos</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <!-- Gráfico de Check-ins -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Check-ins nos Últimos 30 Dias</h5>
                </div>
                <div class="card-body">
                    <canvas id="checkinChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <!-- Outras Métricas -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size: 3rem;">⭐</div>
                    <h3 class="mb-1"><?= number_format($reviewStats['avg_rating'] ?? 0, 1) ?> / 5.0</h3>
                    <p class="text-muted small mb-0"><?= $reviewStats['total_reviews'] ?? 0 ?> avaliações</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size: 3rem;">📱</div>
                    <h3 class="mb-1"><?= number_format($shareStats['total_shares'] ?? 0) ?></h3>
                    <p class="text-muted small mb-0">Compartilhamentos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-people"></i> Top 10 Clientes Mais Frequentes</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($topCustomers)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">Nenhum check-in registrado ainda</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th class="text-center">Visitas</th>
                                <th>Última Visita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $position = 1;
                            foreach ($topCustomers as $customer): 
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= 
                                            $position === 1 ? 'bg-warning text-dark' : 
                                            ($position === 2 ? 'bg-secondary' : 
                                            ($position === 3 ? 'bg-danger' : 'bg-light text-dark'))
                                        ?>">
                                            <?= $position ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($customer['name']) ?></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $customer['visit_count'] ?>x</span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($customer['last_visit'])) ?></td>
                                </tr>
                            <?php 
                                $position++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dicas -->
    <div class="card border-0 shadow-sm mt-4" style="background: linear-gradient(135deg, #667eea, #764ba2);">
        <div class="card-body text-white">
            <h5 class="card-title mb-3"><i class="bi bi-lightbulb"></i> Dicas para Aumentar o Engajamento</h5>
            <ul class="mb-0">
                <li class="mb-2">💡 Coloque o QR Code em local visível para facilitar check-ins verificados</li>
                <li class="mb-2">💡 Crie cupons atrativos para incentivar visitas</li>
                <li class="mb-2">💡 Responda às avaliações dos clientes</li>
                <li class="mb-2">💡 Ofereça recompensas especiais para clientes frequentes</li>
                <li>💡 Compartilhe seu perfil nas redes sociais</li>
            </ul>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Preparar dados do gráfico
const dailyData = <?= json_encode($dailyCheckins) ?>;
const labels = dailyData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
});
const data = dailyData.map(d => d.count);

// Criar gráfico
const ctx = document.getElementById('checkinChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Check-ins',
            data: data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
