<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

require_once 'models/Favorite.php';

$favoriteModel = new Favorite();
$favorites = $favoriteModel->findByUser($_SESSION['user_id']);

$pageTitle = 'Meus Favoritos - Santour Guide';

include 'includes/header.php';
?>

<div class="bg-light py-4">
    <div class="container">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-heart-fill text-danger"></i> Meus Favoritos
        </h2>
        <p class="text-muted mb-0">Parceiros que você salvou</p>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <?php if (empty($favorites)): ?>
            <div class="card dashboard-card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-heart" style="font-size: 4rem; color: var(--text-muted);"></i>
                    <h4 class="mt-3">Nenhum favorito ainda</h4>
                    <p class="text-muted">Explore nossos parceiros e salve seus favoritos!</p>
                    <a href="<?= SITE_URL ?>/" class="btn btn-primary mt-3">
                        Explorar Parceiros
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Você tem <?= count($favorites) ?> parceiro(s) favorito(s)
            </div>
            
            <div class="row g-4">
                <?php foreach ($favorites as $partner): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="partner-card position-relative">
                            <?php if ($partner['main_photo']): ?>
                                <img src="<?= htmlspecialchars($partner['main_photo']) ?>" class="partner-image" alt="<?= htmlspecialchars($partner['name']) ?>">
                            <?php else: ?>
                                <div class="partner-image bg-secondary d-flex align-items-center justify-center">
                                    <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($partner['is_featured']): ?>
                                <span class="partner-badge">
                                    <i class="bi bi-star-fill"></i> Destaque
                                </span>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($partner['name']) ?></h5>
                                    <button class="favorite-btn active" data-partner-id="<?= $partner['id'] ?>">
                                        <i class="bi bi-heart-fill"></i>
                                    </button>
                                </div>
                                
                                <p class="text-muted mb-2">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($partner['category_name']) ?>
                                </p>
                                
                                <?php if ($partner['city']): ?>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partner['city']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($partner['short_description']): ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($partner['short_description']) ?></p>
                                <?php endif; ?>
                                
                                <a href="servico.php?slug=<?= $partner['slug'] ?>" class="btn btn-primary w-100">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Favorite functionality
document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const partnerId = this.dataset.partnerId;
        
        fetch('api/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ partner_id: partnerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.action === 'removed') {
                // Remover o card da página
                this.closest('.col-md-6').remove();
                
                // Verificar se não há mais favoritos
                if (document.querySelectorAll('.partner-card').length === 0) {
                    location.reload();
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
