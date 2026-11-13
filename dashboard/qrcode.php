<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se está logado e é parceiro
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    header('Location: ../auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar ou criar QR Code do parceiro
$stmt = $db->prepare("
    SELECT pqr.*, p.name as partner_name
    FROM partner_qrcodes pqr
    JOIN partners p ON pqr.partner_id = p.id
    WHERE p.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$qrcode = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existe, criar
if (!$qrcode) {
    // Buscar partner_id
    $stmt = $db->prepare("SELECT id, name FROM partners WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($partner) {
        // Gerar código único
        $qrCode = 'SANTOUR_' . strtoupper(bin2hex(random_bytes(8)));
        
        // Inserir no banco
        $stmt = $db->prepare("
            INSERT INTO partner_qrcodes (partner_id, qr_code, is_active)
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$partner['id'], $qrCode]);
        
        // Buscar novamente
        $stmt = $db->prepare("
            SELECT pqr.*, p.name as partner_name
            FROM partner_qrcodes pqr
            JOIN partners p ON pqr.partner_id = p.id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $qrcode = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$pageTitle = 'QR Code';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-qr-code"></i> Meu QR Code</h2>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($qrcode): ?>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <!-- QR Code Visual -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <h4 class="mb-4"><?= htmlspecialchars($qrcode['partner_name']) ?></h4>
                        
                        <!-- QR Code gerado via API -->
                        <div id="qrcode-container" class="mb-4"></div>
                        
                        <p class="text-muted small mb-3">Código: <code><?= htmlspecialchars($qrcode['qr_code']) ?></code></p>
                        
                        <!-- Botões de Ação -->
                        <div class="d-grid gap-2">
                            <button onclick="downloadQRCode()" class="btn btn-primary btn-lg">
                                <i class="bi bi-download"></i> Baixar QR Code
                            </button>
                            <button onclick="printQRCode()" class="btn btn-outline-primary">
                                <i class="bi bi-printer"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Instruções -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> Como Usar</h5>
                        <ol class="mb-0">
                            <li class="mb-3">
                                <strong>Baixe ou imprima</strong> o QR Code acima
                            </li>
                            <li class="mb-3">
                                <strong>Coloque em local visível</strong> no seu estabelecimento (balcão, entrada, mesa)
                            </li>
                            <li class="mb-3">
                                <strong>Clientes escaneiam</strong> o código com o app Santour
                            </li>
                            <li class="mb-3">
                                <strong>Check-in verificado</strong> é registrado automaticamente
                            </li>
                            <li>
                                <strong>Você ganha visibilidade</strong> e os clientes ganham pontos extras!
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Benefícios -->
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <div class="card-body text-white">
                        <h5 class="card-title mb-3"><i class="bi bi-star"></i> Benefícios do QR Code</h5>
                        <ul class="mb-0">
                            <li class="mb-2">✅ Check-ins verificados valem <strong>50 pontos</strong> (vs 30 normais)</li>
                            <li class="mb-2">✅ Clientes são incentivados a visitar seu estabelecimento</li>
                            <li class="mb-2">✅ Você aparece mais no ranking de parceiros</li>
                            <li class="mb-2">✅ Dados precisos de visitação</li>
                            <li>✅ Maior engajamento e fidelização</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <?php
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_checkins,
                COUNT(CASE WHEN verified = 1 THEN 1 END) as verified_checkins,
                COUNT(DISTINCT user_id) as unique_users
            FROM check_ins
            WHERE partner_id = ?
        ");
        $stmt->execute([$qrcode['partner_id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="row g-3 mt-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <div class="text-white">
                        <div style="font-size: 2.5rem; font-weight: bold;"><?= $stats['total_checkins'] ?></div>
                        <div class="small opacity-75">Total de Check-ins</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <div class="text-white">
                        <div style="font-size: 2.5rem; font-weight: bold;"><?= $stats['verified_checkins'] ?></div>
                        <div class="small opacity-75">Check-ins Verificados</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <div class="text-white">
                        <div style="font-size: 2.5rem; font-weight: bold;"><?= $stats['unique_users'] ?></div>
                        <div class="small opacity-75">Clientes Únicos</div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Você precisa ter um perfil de parceiro cadastrado para gerar um QR Code.
        </div>
    <?php endif; ?>
</div>

<!-- QRCode.js Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Gerar QR Code
const qrcode = new QRCode(document.getElementById('qrcode-container'), {
    text: '<?= $qrcode['qr_code'] ?? '' ?>',
    width: 300,
    height: 300,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
});

// Download QR Code
function downloadQRCode() {
    const canvas = document.querySelector('#qrcode-container canvas');
    const url = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = 'santour-qrcode-<?= $qrcode['partner_id'] ?? 'parceiro' ?>.png';
    link.href = url;
    link.click();
}

// Imprimir QR Code
function printQRCode() {
    const printWindow = window.open('', '_blank');
    const canvas = document.querySelector('#qrcode-container canvas');
    const imgUrl = canvas.toDataURL('image/png');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code - <?= htmlspecialchars($qrcode['partner_name'] ?? 'Parceiro') ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 50px;
                }
                h1 {
                    margin-bottom: 30px;
                }
                img {
                    max-width: 400px;
                }
                .instructions {
                    margin-top: 30px;
                    font-size: 18px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <h1><?= htmlspecialchars($qrcode['partner_name'] ?? 'Parceiro') ?></h1>
            <img src="${imgUrl}" alt="QR Code">
            <div class="instructions">
                <p><strong>Escaneie este QR Code com o app Santour</strong></p>
                <p>e ganhe pontos extras no seu check-in!</p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>

<?php include '../includes/footer.php'; ?>
