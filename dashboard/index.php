<?php
require_once '../config/config.php';

if (!isPartner() && !isAdmin()) {
    if (isLoggedIn()) {
        redirect('/perfil.php');
    }
    redirect('/auth/login.php');
}

require_once '../models/Partner.php';
require_once '../models/Coupon.php';

$partnerModel = new Partner();
$couponModel = new Coupon();

$userPartners = [];
$isAdmin = isAdmin();

if ($isAdmin) {
    // Admin vê todos os serviços do hotel
    $userPartners = $partnerModel->findAll();
} else {
    // Gestor de parceiro vê só o seu
    $userPartners = $partnerModel->findByUserId($_SESSION['user_id']);
}

if (empty($userPartners) && !$isAdmin) {
    redirect('/dashboard/adicionar-servico.php');
}

$pageTitle = 'Painel do Hotel';
include '../includes/header.php';
?>

<div class="bg-light py-4 border-bottom">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Painel Administrativo</h2>
            <p class="text-muted mb-0">Visão corporativa de todos os serviços do Resort</p>
        </div>
        <div class="d-flex gap-2">
            <a href="qrcodes.php" class="btn fw-bold px-4" style="background:#fff; color:#1e4344; border:1px solid #1e4344; border-radius:50px;">
                <i class="bi bi-qr-code"></i> Todos os QRCodes
            </a>
            <a href="adicionar-servico.php" class="btn fw-bold px-4 text-white" style="background:#c9a961; border:none; border-radius:50px;">
                <i class="bi bi-plus-lg"></i> Novo Serviço
            </a>
        </div>
    </div>
</div>

<section class="py-5" style="min-height: 60vh;">
    <div class="container">
        
        <?php if (empty($userPartners)): ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 fw-bold text-dark">Nenhum Serviço Cadastrado</h5>
                <p class="text-muted">Comece cadastrando um novo serviço para gerar QRCodes.</p>
                <a href="adicionar-servico.php" class="btn btn-outline-primary rounded-pill fw-bold px-4 mt-2" style="border-color:#1e4344; color:#1e4344;">Criar Serviço</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($userPartners as $partner): 
                    // Carrega estatísticas do parceiro
                    $viewsCount = $partnerModel->getViewsCount($partner['id'], 30);
                    $redemptions = $couponModel->getRedemptions($partner['id'], 30);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden" style="background:#fff; display:flex; flex-direction:column;">
                        
                        <div style="background: linear-gradient(135deg, #1e4344, #1e8b8b); padding: 1.5rem; color: #fff;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge" style="background: rgba(255,255,255,0.2);"><?= htmlspecialchars($partner['category_name']) ?></span>
                                <?php if ($partner['is_active']): ?>
                                    <span class="badge bg-success bg-opacity-75"><i class="bi bi-circle-fill" style="font-size:.5rem; vertical-align:middle; margin-right:4px;"></i>Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-75">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold mb-0 text-white"><?= htmlspecialchars($partner['name']) ?></h4>
                        </div>
                        
                        <div class="card-body p-4 d-flex flex-column gap-3 flex-grow-1">
                            
                            <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 border">
                                <div>
                                    <p class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing:1px;">Visualizações</p>
                                    <h3 class="fw-bold mb-0" style="color: #1a1a2e;"><?= $viewsCount ?></h3>
                                </div>
                                <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-eye text-primary fs-5"></i>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 border">
                                <div>
                                    <p class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing:1px;">Cupons Resgatados</p>
                                    <h3 class="fw-bold mb-0" style="color: #1a1a2e;"><?= count($redemptions) ?></h3>
                                </div>
                                <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-ticket-perforated" style="color:#c9a961; font-size:1.25rem;"></i>
                                </div>
                            </div>
                            
                            <hr class="my-1 border-light">
                            
                            <div class="mt-auto d-flex flex-wrap gap-2 justify-content-center">
                                <a href="editar-servico.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Editar Serviço">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="fotos.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Fotos">
                                    <i class="bi bi-images"></i>
                                </a>
                                <a href="cupons.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Criar Cupons">
                                    <i class="bi bi-tags"></i>
                                </a>
                                <a href="recompensas.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Recompensas do Fidelidade">
                                    <i class="bi bi-gift"></i>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include '../includes/footer.php'; ?>
