<?php
require_once '../config/config.php';

if (!isPartner()) {
    redirect('/auth/login.php');
}

require_once '../models/Partner.php';
require_once '../models/Coupon.php';

$partnerModel = new Partner();
$couponModel = new Coupon();

$userId = $_SESSION['user_id'];
$partnerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isAdmin() && $partnerId > 0) {
    $partner = $partnerModel->findById($partnerId);
} else {
    $userPartners = $partnerModel->findByUserId($userId);
    $partner = !empty($userPartners) ? $userPartners[0] : null;
}

if (!$partner) {
    redirect('/dashboard/index.php');
}
$coupons = $couponModel->findByPartner($partner['id']);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'partner_id' => $partner['id'],
            'code' => sanitize($_POST['code']),
            'title' => sanitize($_POST['title']),
            'description' => sanitize($_POST['description'] ?? ''),
            'discount_type' => sanitize($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'valid_from' => sanitize($_POST['valid_from']),
            'valid_until' => sanitize($_POST['valid_until']),
            'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        try {
            if ($couponModel->create($data)) {
                $success = 'Cupom criado com sucesso!';
                $coupons = $couponModel->findByPartner($partner['id']);
            } else {
                $error = 'Erro ao criar cupom.';
            }
        } catch (Exception $e) {
            $error = 'Erro no banco de dados: ' . $e->getMessage();
        }
    } elseif ($action === 'update') {
        $couponId = intval($_POST['coupon_id']);
        $data = [
            'code' => sanitize($_POST['code']),
            'title' => sanitize($_POST['title']),
            'description' => sanitize($_POST['description'] ?? ''),
            'discount_type' => sanitize($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'valid_from' => sanitize($_POST['valid_from']),
            'valid_until' => sanitize($_POST['valid_until']),
            'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($couponModel->update($couponId, $data)) {
            $success = 'Cupom atualizado com sucesso!';
            $coupons = $couponModel->findByPartner($partner['id']);
        } else {
            $error = 'Erro ao atualizar cupom.';
        }
    } elseif ($action === 'delete') {
        $couponId = intval($_POST['coupon_id']);
        if ($couponModel->delete($couponId)) {
            $success = 'Cupom excluído com sucesso!';
            $coupons = $couponModel->findByPartner($partner['id']);
        } else {
            $error = 'Erro ao excluir cupom.';
        }
    }
}

$pageTitle = 'Gerenciar Cupons - Dashboard';

include '../includes/header.php';
?>

<div class="bg-light py-4">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="fw-bold mb-0">Gerenciar Cupons</h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($partner['name']) ?></p>
            </div>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <!-- Create Coupon Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                <i class="bi bi-plus-circle"></i> Criar Novo Cupom
            </button>
        </div>
        
        <!-- Coupons List -->
        <?php if (empty($coupons)): ?>
            <div class="card dashboard-card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-ticket-perforated" style="font-size: 4rem; color: var(--text-muted);"></i>
                    <h4 class="mt-3">Nenhum cupom cadastrado</h4>
                    <p class="text-muted">Crie seu primeiro cupom para oferecer aos hóspedes!</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($coupon['title']) ?></h5>
                                    <span class="badge <?= $coupon['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $coupon['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($coupon['code']) ?></code>
                                    <span class="ms-2 text-primary fw-bold">
                                        <?= $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : 'R$ ' . number_format($coupon['discount_value'], 2, ',', '.') ?>
                                    </span>
                                </div>
                                
                                <?php if ($coupon['description']): ?>
                                    <p class="text-muted small"><?= htmlspecialchars($coupon['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?= formatDate($coupon['valid_from']) ?> até <?= formatDate($coupon['valid_until']) ?>
                                    </small>
                                </div>
                                
                                <?php if ($coupon['max_uses']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-graph-up"></i> 
                                            <?= $coupon['current_uses'] ?> / <?= $coupon['max_uses'] ?> usos
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                            onclick="editCoupon(<?= htmlspecialchars(json_encode($coupon)) ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <form method="POST" class="flex-fill" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Create Coupon Modal -->
<div class="modal fade" id="createCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Novo Cupom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código do Cupom *</label>
                            <input type="text" class="form-control" name="code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Desconto *</label>
                            <select class="form-select" name="discount_type" required>
                                <option value="percentage">Porcentagem (%)</option>
                                <option value="fixed">Valor Fixo (R$)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor do Desconto *</label>
                            <input type="number" step="0.01" class="form-control" name="discount_value" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Válido de *</label>
                            <input type="date" class="form-control" name="valid_from" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Válido até *</label>
                            <input type="date" class="form-control" name="valid_until" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Máximo de Usos</label>
                            <input type="number" class="form-control" name="max_uses" placeholder="Ilimitado">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active_create" checked>
                                <label class="form-check-label" for="is_active_create">
                                    Cupom ativo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Cupom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editCouponForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="coupon_id" id="edit_coupon_id">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Cupom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código do Cupom *</label>
                            <input type="text" class="form-control" name="code" id="edit_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Desconto *</label>
                            <select class="form-select" name="discount_type" id="edit_discount_type" required>
                                <option value="percentage">Porcentagem (%)</option>
                                <option value="fixed">Valor Fixo (R$)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor do Desconto *</label>
                            <input type="number" step="0.01" class="form-control" name="discount_value" id="edit_discount_value" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Válido de *</label>
                            <input type="date" class="form-control" name="valid_from" id="edit_valid_from" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Válido até *</label>
                            <input type="date" class="form-control" name="valid_until" id="edit_valid_until" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Máximo de Usos</label>
                            <input type="number" class="form-control" name="max_uses" id="edit_max_uses" placeholder="Ilimitado">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Cupom ativo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCoupon(coupon) {
    document.getElementById('edit_coupon_id').value = coupon.id;
    document.getElementById('edit_code').value = coupon.code;
    document.getElementById('edit_title').value = coupon.title;
    document.getElementById('edit_description').value = coupon.description || '';
    document.getElementById('edit_discount_type').value = coupon.discount_type;
    document.getElementById('edit_discount_value').value = coupon.discount_value;
    document.getElementById('edit_valid_from').value = coupon.valid_from;
    document.getElementById('edit_valid_until').value = coupon.valid_until;
    document.getElementById('edit_max_uses').value = coupon.max_uses || '';
    document.getElementById('edit_is_active').checked = coupon.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editCouponModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
