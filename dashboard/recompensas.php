<?php
require_once '../config/config.php';
require_once '../models/Gamification.php';

if (!isPartner()) {
    header('Location: ../auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$partnerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar serviço do hotel
if (isAdmin() && $partnerId > 0) {
    $stmt = $db->prepare("SELECT id, name FROM partners WHERE id = ?");
    $stmt->execute([$partnerId]);
} else {
    $stmt = $db->prepare("SELECT id, name FROM partners WHERE user_id = ?");
    $stmt->execute([$userId]);
}
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    header('Location: index.php');
    exit;
}

$gamification = new Gamification();
$rewards = $gamification->getPartnerRewards($partner['id']);

$error = '';
$success = '';

// Processar exclusão
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE partner_rewards SET is_active = 0 WHERE id = ? AND partner_id = ?");
    $stmt->execute([$_GET['delete'], $partner['id']]);
    header('Location: recompensas.php?success=Prêmio removido');
    exit;
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reward'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $points_cost = intval($_POST['points_cost'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    if (empty($title) || $points_cost <= 0) {
        $error = 'Título e custo em pontos são obrigatórios.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO partner_rewards (partner_id, title, description, points_cost, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$partner['id'], $title, $description, $points_cost, $image_url]);
            $success = 'Novo prêmio cadastrado com sucesso!';
            $rewards = $gamification->getPartnerRewards($partner['id']);
        } catch (Exception $e) {
            $error = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Gerenciar Prêmios e Ofertas';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h2 class="fw-bold mb-0">Prêmios e Ofertas</h2>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addRewardModal">
            <i class="bi bi-plus-lg"></i> Novo Prêmio
        </button>
    </div>

    <?php if ($success || isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success ?: $_GET['success']) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($rewards)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-gift text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                <p class="text-muted mt-3">Você ainda não tem prêmios cadastrados para este serviço.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rewards as $reward): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <img src="<?= $reward['image_url'] ?: 'https://via.placeholder.com/400x200' ?>" class="card-img-top" style="height: 160px; object-fit: cover;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($reward['title']) ?></h5>
                                <span class="badge bg-primary"><?= $reward['points_cost'] ?> pts</span>
                            </div>
                            <p class="text-muted small line-clamp-2"><?= htmlspecialchars($reward['description']) ?></p>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <a href="?delete=<?= $reward['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Excluir este prêmio?')">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Prêmio -->
<div class="modal fade" id="addRewardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Novo Prêmio/Oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_reward" value="1">
                    <div class="mb-3">
                        <label class="form-label">Título do Prêmio *</label>
                        <input type="text" name="title" class="form-control" placeholder="Ex: Sobremesa Grátis" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custo em Pontos *</label>
                        <input type="number" name="points_cost" class="form-control" placeholder="Ex: 200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Detalhes de como usar o prêmio"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL da Imagem</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://exemplo.com/imagem.jpg">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
