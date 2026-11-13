<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/config.php';
require_once 'models/Gamification.php';
require_once 'models/Partner.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

$gamification = new Gamification();
$partnerModel = new Partner();

$userId = $_SESSION['user_id'];
$userPoints = $gamification->getUserPoints($userId);

// Para o protótipo, vamos pegar recompensas de um parceiro específico ou todos
$partners = $partnerModel->findAll(['featured' => 1]); // Exemplo: parceiros em destaque
?>

<?php 
$pageTitle = 'Boutique do Resort';
include 'includes/header.php'; 
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">🎁 Boutique & Recompensas</h1>
            <p class="text-muted">Troque seus pontos de fidelidade na Boutique do Itamambuca!</p>
        </div>
        <div class="text-end">
            <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                <small class="d-block opacity-75">Saldo Atual</small>
                <span class="fs-3 fw-bold"><?= $userPoints['total_points'] ?></span>
                <small>pts</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($partners as $partner): 
            $rewards = $gamification->getPartnerRewards($partner['id']);
            if (empty($rewards)) continue;
        ?>
            <div class="col-12">
                <h4 class="mb-3 d-flex align-items-center">
                    <i class="bi bi-shop me-2 text-primary"></i>
                    <?= htmlspecialchars($partner['name']) ?>
                </h4>
                <div class="row g-3">
                    <?php foreach ($rewards as $reward): 
                        $canRedeem = $userPoints['total_points'] >= $reward['points_cost'];
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="row g-0">
                                    <div class="col-4">
                                        <img src="<?= $reward['image_url'] ?: 'https://via.placeholder.com/150' ?>" class="img-fluid h-100 w-100" style="object-fit: cover;" alt="<?= htmlspecialchars($reward['title']) ?>">
                                    </div>
                                    <div class="col-8">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($reward['title']) ?></h6>
                                            <p class="text-muted small mb-2 line-clamp-2"><?= htmlspecialchars($reward['description']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                                <span class="badge bg-light text-primary fw-bold">
                                                    <?= $reward['points_cost'] ?> pts
                                                </span>
                                                <button class="btn btn-sm <?= $canRedeem ? 'btn-primary' : 'btn-outline-secondary disabled' ?> rounded-pill px-3" 
                                                        onclick="redeemReward(<?= $reward['id'] ?>, '<?= addslashes($reward['title']) ?>', <?= $reward['points_cost'] ?>)">
                                                    Resgatar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade" id="redemptionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                <h3 class="fw-bold mb-2">Voucher Gerado!</h3>
                <p class="text-muted mb-4">Apresente este código na nossa Boutique para retirar seu prêmio exclusivo.</p>
                <div class="bg-light p-4 rounded-4 mb-4">
                    <small class="d-block text-uppercase fw-bold text-muted mb-1">Código de Resgate</small>
                    <span class="fs-1 fw-bold text-primary tracking-widest" id="redemptionCode">------</span>
                </div>
                <button type="button" class="btn btn-primary w-100 rounded-pill py-3" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
function redeemReward(id, title, cost) {
    if (!confirm(`Deseja trocar ${cost} pontos por "${title}"?`)) return;

    fetch('api/redeem-reward.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reward_id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('redemptionCode').textContent = data.code;
            new bootstrap.Modal(document.getElementById('redemptionModal')).show();
            // Recarregar pontos na tela ou a página
        } else {
            alert(data.message || 'Erro ao realizar resgate.');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
