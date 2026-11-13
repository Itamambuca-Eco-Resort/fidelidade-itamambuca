<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=' . urlencode('/cupons.php'));
}

$pageTitle = 'Meus Cupons e Recompensas';
include 'includes/header.php';

$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Buscar cupons/resgates do usuário
$stmt = $db->prepare("
    SELECT rr.*, pr.title, pr.description, pr.image_url, p.name as partner_name
    FROM reward_redemptions rr
    JOIN partner_rewards pr ON rr.reward_id = pr.id
    JOIN partners p ON pr.partner_id = p.id
    WHERE rr.user_id = ?
    ORDER BY rr.created_at DESC
");
$stmt->execute([$userId]);
$redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending = [];
$used = [];

foreach ($redemptions as $r) {
    if ($r['status'] === 'pending') {
        $pending[] = $r;
    } else {
        $used[] = $r;
    }
}
?>

<div class="bg-light py-4 border-bottom">
    <div class="container">
        <h2 class="fw-bold mb-1" style="color: #1a1a2e; font-family: 'Barlow Semi Condensed', sans-serif;">Meus Cupons</h2>
        <p class="text-muted mb-0">Apresente o código na recepção ou no local correspondente para usar.</p>
    </div>
</div>

<section class="py-5" style="min-height: 50vh;">
    <div class="container">
        
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold px-4" id="pills-active-tab" data-bs-toggle="pill" data-bs-target="#pills-active" type="button" role="tab" aria-controls="pills-active" aria-selected="true" style="color: #1e4344;">
                    Disponíveis <span class="badge bg-secondary ms-1 rounded-pill"><?= count($pending) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold px-4" id="pills-used-tab" data-bs-toggle="pill" data-bs-target="#pills-used" type="button" role="tab" aria-controls="pills-used" aria-selected="false" style="color: #1e4344;">
                    Utilizados <span class="badge bg-secondary ms-1 rounded-pill"><?= count($used) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- Disponíveis -->
            <div class="tab-pane fade show active" id="pills-active" role="tabpanel" aria-labelledby="pills-active-tab">
                <?php if (empty($pending)): ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                        <i class="bi bi-ticket text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 fw-bold text-dark">Nenhum cupom disponível</h5>
                        <p class="text-muted">Você ainda não trocou seus pontos por recompensas.</p>
                        <a href="/recompensas.php" class="btn btn-outline-primary rounded-pill fw-bold px-4 mt-2" style="border-color:#1e4344; color:#1e4344;">Ver Recompensas</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($pending as $coupon): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden" style="background:#fff;">
                                    <div style="background: linear-gradient(135deg, #1e4344, #1e8b8b); padding: 1.5rem; color: #fff; text-align: center; border-bottom: 2px dashed rgba(255,255,255,0.3);">
                                        <div class="mb-2"><i class="bi bi-gift" style="font-size: 2rem; color: #c9a961;"></i></div>
                                        <h5 class="fw-bold mb-0 text-white"><?= htmlspecialchars($coupon['title']) ?></h5>
                                        <small class="opacity-75"><?= htmlspecialchars($coupon['partner_name']) ?></small>
                                    </div>
                                    <div class="card-body text-center p-4">
                                        <p class="small text-muted mb-3"><?= htmlspecialchars($coupon['description']) ?></p>
                                        <div class="bg-light rounded-3 py-3 mb-3 border">
                                            <p class="text-muted small mb-1">Código de Resgate</p>
                                            <h3 class="fw-bold mb-0" style="color: #1a1a2e; letter-spacing: 2px;"><?= htmlspecialchars($coupon['redemption_code']) ?></h3>
                                        </div>
                                        <button class="btn w-100 fw-bold rounded-pill" style="background: #c9a961; color: #fff;" onclick="mostrarApresentacao('<?= htmlspecialchars($coupon['redemption_code']) ?>', '<?= htmlspecialchars(addslashes($coupon['title'])) ?>')">
                                            Apresentar na Recepção
                                        </button>
                                    </div>
                                    <div class="card-footer bg-white border-0 text-center pb-3 pt-0">
                                        <small class="text-muted">Resgatado em: <?= date('d/m/Y', strtotime($coupon['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Utilizados -->
            <div class="tab-pane fade" id="pills-used" role="tabpanel" aria-labelledby="pills-used-tab">
                <?php if (empty($used)): ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                        <p class="text-muted mb-0">Você ainda não tem cupons utilizados.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($used as $coupon): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden" style="background:#f8f9fa;">
                                    <div class="card-body p-4 text-center opacity-50">
                                        <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($coupon['title']) ?></h5>
                                        <small class="text-muted d-block mb-3"><?= htmlspecialchars($coupon['partner_name']) ?></small>
                                        <div class="badge bg-secondary mb-3 px-3 py-2 rounded-pill">Utilizado / Cancelado</div>
                                        <p class="text-muted mb-0 small">Código: <s><?= htmlspecialchars($coupon['redemption_code']) ?></s></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</section>

<!-- Modal de Apresentação -->
<div class="modal fade" id="apresentacaoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:24px; border:none; text-align:center;">
      <div class="modal-header border-0 pb-0 justify-content-end">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <h4 class="fw-bold mb-1" id="modalTitle">Recompensa</h4>
        <p class="text-muted mb-4">Mostre este código para o atendente responsável.</p>
        
        <div style="background: #f8f9fa; border: 2px dashed #c9a961; border-radius: 16px; padding: 2rem 1rem; margin-bottom: 2rem;">
            <h1 class="display-4 fw-bold mb-0" id="modalCode" style="color: #1e4344; letter-spacing: 4px;">CODE</h1>
        </div>
        
        <button type="button" class="btn w-100 fw-bold py-3 rounded-pill" style="background: #1e4344; color: #fff;" data-bs-dismiss="modal">
          Fechar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function mostrarApresentacao(codigo, titulo) {
    document.getElementById('modalCode').innerText = codigo;
    document.getElementById('modalTitle').innerText = titulo;
    var myModal = new bootstrap.Modal(document.getElementById('apresentacaoModal'));
    myModal.show();
}

// Customizar cores das tabs
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-link');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.style.background = 'transparent');
            this.style.background = 'rgba(201,169,97,0.1)';
        });
    });
    // Setar a primeira tab ativa
    document.querySelector('.nav-link.active').style.background = 'rgba(201,169,97,0.1)';
});
</script>

<?php include 'includes/footer.php'; ?>
