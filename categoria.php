<?php
require_once 'config/config.php';
require_once 'models/Category.php';
require_once 'models/Partner.php';

// Restrito a usuários logados
if (!isLoggedIn()) {
    header('Location: /?acesso=restrito');
    exit;
}

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect('/');
}

$categoryModel = new Category();
$partnerModel = new Partner();

$category = $categoryModel->findBySlug($slug);

if (!$category) {
    redirect('/');
}

$partners = $partnerModel->findAll(['category_id' => $category['id']]);

$pageTitle = $category['name'] . ' - Santour Guide';

include 'includes/header.php';
?>

<?php
// Fallback image based on slug
$heroImage = !empty($category['image']) ? $category['image'] : "/assets/images/categories/{$category['slug']}.jpg";
// Check if file exists or use default
$imagePath = ROOT_PATH . $heroImage;
if (!file_exists($imagePath)) {
    $heroImage = "/assets/images/categories/natureza.jpg"; // Default fallback
}
?>

<section class="category-hero" style="background-image: url('<?= $heroImage ?>');">
    <div class="container text-center">
        <div class="category-hero-content">
            <nav class="breadcrumb justify-content-center">
                <a href="<?= SITE_URL ?>/">Início</a>
                <span class="breadcrumb-sep mx-2">/</span>
                <span><?= htmlspecialchars($category['name']) ?></span>
            </nav>
            <h1 class="category-hero-title fw-bold"><?= htmlspecialchars($category['name']) ?></h1>
            <p class="lead mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><?= htmlspecialchars($category['description']) ?></p>
        </div>
    </div>
</section>

<section class="py-4 bg-white border-bottom shadow-sm sticky-top" style="top: 70px; z-index: 999;">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="filter-group">
                <button class="filter-btn active" data-filter="todos">Todos</button>
                <button class="filter-btn" data-filter="destaque">Destaques</button>
                <button class="filter-btn" data-filter="desconto">Maiores Descontos</button>
                <button class="filter-btn" data-filter="favoritos">Favoritos</button>
            </div>
            <div class="text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                <span id="partnerCount"><?= count($partners) ?></span> parceiros nesta categoria
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
        
        <?php if (empty($partners)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: var(--text-muted);"></i>
                <h3 class="mt-3">Nenhum parceiro encontrado</h3>
                <p class="text-muted">Ainda não temos parceiros nesta categoria.</p>
                <a href="<?= SITE_URL ?>/" class="btn btn-primary">Voltar para Início</a>
            </div>
        <?php else: ?>
            <div class="row g-4" id="partnersGrid">
                <?php foreach ($partners as $partner): 
                    $activeCoupons = $partner['active_coupons'] ?? 0;
                    $discountPct = $partner['discount_percentage'] ?? 0;
                ?>
                    <div class="col-md-6 col-lg-4 partner-item" 
                         data-name="<?= strtolower(htmlspecialchars($partner['name'])) ?>"
                         data-featured="<?= $partner['is_featured'] ? '1' : '0' ?>"
                         data-discount="<?= ($activeCoupons > 0 || $discountPct > 0) ? '1' : '0' ?>"
                         data-favorite="0" 
                         data-id="<?= $partner['id'] ?>">
                        <div class="partner-card">
                            <?php if ($partner['main_photo']): ?>
                                <img src="<?= htmlspecialchars($partner['main_photo']) ?>" class="partner-image" alt="<?= htmlspecialchars($partner['name']) ?>">
                            <?php else: ?>
                                <div class="partner-image bg-secondary d-flex align-items-center justify-content-center">
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
                                    <?php if (isLoggedIn()): ?>
                                        <button class="favorite-btn" data-partner-id="<?= $partner['id'] ?>">
                                            <i class="bi bi-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
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
// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const items = document.querySelectorAll('.partner-item');
        let count = 0;
        
        items.forEach(item => {
            let show = false;
            if (filter === 'todos') {
                show = true;
            } else if (filter === 'destaque') {
                show = item.dataset.featured === '1';
            } else if (filter === 'desconto') {
                show = item.dataset.discount === '1';
            } else if (filter === 'favoritos') {
                show = item.dataset.favorite === '1';
            }
            
            if (show) {
                item.style.display = '';
                count++;
            } else {
                item.style.display = 'none';
            }
        });
        
        document.getElementById('partnerCount').textContent = count;
    });
});

// Favorite functionality
document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const partnerId = this.dataset.partnerId;
        const itemWrap = this.closest('.partner-item');
        
        fetch('api/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ partner_id: partnerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                if (this.classList.contains('active')) {
                    icon.classList.replace('bi-heart', 'bi-heart-fill');
                    itemWrap.dataset.favorite = '1';
                } else {
                    icon.classList.replace('bi-heart-fill', 'bi-heart');
                    itemWrap.dataset.favorite = '0';
                }
            }
        });
    });
});

// Initial check for favorites (client-side simplicity)
document.addEventListener('DOMContentLoaded', function() {
    // If you had the favorites list from PHP, you'd mark them here.
    // For now we assume favorite toggle is manual until page reload.
});
</script>

<?php include 'includes/footer.php'; ?>
