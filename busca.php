<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';

// Restrito a usuários logados
if (!isLoggedIn()) {
    header('Location: /?acesso=restrito');
    exit;
}

$db = Database::getInstance()->getConnection();

// Parâmetros de busca
$searchTerm = trim($_GET['q'] ?? '');
$categoryId = intval($_GET['category'] ?? 0);
$hasDiscount = isset($_GET['discount']);


// Buscar categorias para filtro
$stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir query de busca
$sql = "SELECT DISTINCT p.*, c.name as category_name, c.icon as category_icon,
        (SELECT COUNT(*) FROM coupons WHERE partner_id = p.id AND is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())) as active_coupons
        FROM partners p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1";

$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}


if ($hasDiscount) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM coupons 
        WHERE partner_id = p.id 
        AND is_active = 1 
        AND (valid_until IS NULL OR valid_until >= CURDATE())
    )";
}

$sql .= " ORDER BY p.name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Busca';
include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Formulário de Busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="busca.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control" 
                               placeholder="Buscar por nome, descrição..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Todas as Categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="discount" id="discount" 
                               <?= $hasDiscount ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discount">
                            Apenas com cupons de desconto ativos
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <?php if (!empty($searchTerm) || $categoryId > 0 || $hasDiscount): ?>
                <?= count($results) ?> resultado(s) encontrado(s)
            <?php else: ?>
                Todos os Parceiros (<?= count($results) ?>)
            <?php endif; ?>
        </h5>
        
        <?php if (!empty($searchTerm) || $categoryId > 0 || $hasDiscount): ?>
            <a href="busca.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle"></i> Limpar Filtros
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($results)): ?>
        <div class="text-center py-5">
            <div style="font-size: 4rem; opacity: 0.3;">🔍</div>
            <h4 class="mt-3 text-muted">Nenhum resultado encontrado</h4>
            <p class="text-muted">Tente ajustar os filtros de busca</p>
            <a href="busca.php" class="btn btn-primary mt-3">Ver Todos os Parceiros</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($results as $partner): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card partner-card h-100 border-0 shadow-sm">
                        <?php if ($partner['logo_url']): ?>
                            <img src="<?= htmlspecialchars($partner['logo_url']) ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($partner['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center" 
                                 style="height: 200px; background: linear-gradient(135deg, #667eea, #764ba2);">
                                <span style="font-size: 4rem;"><?= $partner['category_icon'] ?? '🏪' ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($partner['name']) ?></h5>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-sm btn-outline-danger favorite-btn" 
                                            data-partner-id="<?= $partner['id'] ?>">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-muted small mb-2">
                                <i class="bi bi-tag"></i> <?= htmlspecialchars($partner['category_name']) ?>
                            </p>
                            

                            
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars(mb_substr($partner['description'], 0, 100)) ?>...
                            </p>
                            
                            <?php if ($partner['active_coupons'] > 0): ?>
                                <div class="mb-3">
                                    <span class="badge bg-success">
                                        <i class="bi bi-ticket-perforated"></i> 
                                        <?= $partner['active_coupons'] ?> cupom(ns) disponível(is)
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <a href="servico.php?id=<?= $partner['id'] ?>" class="btn btn-primary w-100">
                                Ver Detalhes
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Favoritos
document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const partnerId = this.dataset.partnerId;
        
        try {
            const response = await fetch('/api/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ partner_id: partnerId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const icon = this.querySelector('i');
                if (result.is_favorite) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                    this.classList.remove('btn-outline-danger');
                    this.classList.add('btn-danger');
                } else {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                    this.classList.remove('btn-danger');
                    this.classList.add('btn-outline-danger');
                }
            }
        } catch (err) {
            console.error('Erro:', err);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
