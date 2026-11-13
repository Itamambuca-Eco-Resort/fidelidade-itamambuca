<?php
require_once '../config/config.php';

// Apenas Admin ou Parceiro podem acessar
if (!isPartner()) {
    redirect('/auth/login.php');
}

require_once '../models/Partner.php';
$partnerModel = new Partner();

$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, c.name as category_name 
        FROM partners p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.name ASC";
$stmt = $db->query($sql);
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gerenciador de QRCodes';
include '../includes/header.php';
?>

<div class="bg-light py-4">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="fw-bold mb-0">QRCodes de Check-in</h2>
                <p class="text-muted mb-0">Visualize e imprima os códigos fixos para os serviços do hotel.</p>
            </div>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php if (empty($partners)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-qr-code" style="font-size: 4rem; color: #ddd;"></i>
                    <p class="mt-3 text-muted">Nenhum serviço cadastrado com QRCode.</p>
                </div>
            <?php else: ?>
                <?php foreach ($partners as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                            <div class="card-body p-4 text-center">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['name']) ?></h5>
                                <span class="badge bg-light text-dark mb-4"><?= htmlspecialchars($p['category_name'] ?? 'Serviço') ?></span>
                                
                                <div class="qr-container bg-white p-3 rounded-4 shadow-sm mb-4 mx-auto" style="width: fit-content; border: 1px solid #eee;">
                                    <?php if (!empty($p['qr_code'])): ?>
                                        <div id="qr-<?= $p['id'] ?>"></div>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                new QRCode(document.getElementById('qr-<?= $p['id'] ?>'), {
                                                    text: '<?= SITE_URL ?>/checkin.php?qr=<?= urlencode($p['qr_code']) ?>',
                                                    width: 180,
                                                    height: 180,
                                                    colorDark: '#0f2b3c',
                                                    colorLight: '#ffffff',
                                                    correctLevel: QRCode.CorrectLevel.H
                                                });
                                            });
                                        </script>
                                    <?php else: ?>
                                        <div class="p-5 text-muted small">
                                            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                                            Sem código cadastrado
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="bg-light p-2 rounded-3 mb-4 text-break">
                                    <code class="small text-muted"><?= $p['qr_code'] ? htmlspecialchars($p['qr_code']) : '---' ?></code>
                                </div>

                                <div class="d-grid gap-2">
                                    <button onclick="window.print()" class="btn btn-primary rounded-pill">
                                        <i class="bi bi-printer"></i> Imprimir para totem
                                    </button>
                                    <a href="editar-servico.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary rounded-pill">
                                        <i class="bi bi-pencil"></i> Editar Serviço
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Script do QRCode.js (caso não esteja no footer) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    @media print {
        .topbar, .bg-light, .btn, .drawer-overlay, .drawer { display: none !important; }
        body { background: white !important; }
        .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .row { display: block !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; margin-bottom: 2rem !important; page-break-inside: avoid; }
        .qr-container { border: 2px solid #000 !important; box-shadow: none !important; }
    }
</style>

<?php include '../includes/footer.php'; ?>
