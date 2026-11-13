<?php
require_once '../config/config.php';

if (!isPartner()) {
    redirect('/auth/login.php');
}

require_once '../models/Partner.php';
require_once '../models/PartnerPhoto.php';

$partnerModel = new Partner();
$photoModel = new PartnerPhoto();

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
$photos = $photoModel->findByPartner($partner['id']);

$error = '';
$success = '';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload' && isset($_FILES['photo'])) {
        $photoUrl = uploadImage($_FILES['photo'], 'partners');
        
        if ($photoUrl) {
            $data = [
                'partner_id' => $partner['id'],
                'url' => $photoUrl,
                'caption' => sanitize($_POST['caption'] ?? ''),
                'is_main_photo' => empty($photos) ? 1 : 0,
                'display_order' => count($photos)
            ];
            
            if ($photoModel->create($data)) {
                $success = 'Foto enviada com sucesso!';
                $photos = $photoModel->findByPartner($partner['id']);
            } else {
                $error = 'Erro ao salvar foto no banco de dados.';
            }
        } else {
            $error = 'Erro ao fazer upload da foto. Verifique o tamanho e formato.';
        }
    } elseif ($action === 'set_main') {
        $photoId = intval($_POST['photo_id']);
        if ($photoModel->setMainPhoto($photoId, $partner['id'])) {
            $success = 'Foto principal atualizada!';
            $photos = $photoModel->findByPartner($partner['id']);
        } else {
            $error = 'Erro ao definir foto principal.';
        }
    } elseif ($action === 'delete') {
        $photoId = intval($_POST['photo_id']);
        if ($photoModel->delete($photoId)) {
            $success = 'Foto excluída com sucesso!';
            $photos = $photoModel->findByPartner($partner['id']);
        } else {
            $error = 'Erro ao excluir foto.';
        }
    }
}

$pageTitle = 'Gerenciar Fotos - Dashboard';

include '../includes/header.php';
?>

<div class="bg-light py-4">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="fw-bold mb-0">Gerenciar Fotos</h2>
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
        
        <!-- Upload Form -->
        <div class="card dashboard-card mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-cloud-upload"></i> Enviar Nova Foto
                </h5>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Selecionar Foto *</label>
                            <input type="file" class="form-control" name="photo" accept="image/*" required>
                            <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 5MB</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Legenda (opcional)</label>
                            <input type="text" class="form-control" name="caption" placeholder="Descrição da foto">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Enviar Foto
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Photos Grid -->
        <?php if (empty($photos)): ?>
            <div class="card dashboard-card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-images" style="font-size: 4rem; color: var(--text-muted);"></i>
                    <h4 class="mt-3">Nenhuma foto cadastrada</h4>
                <p class="text-muted">Adicione fotos deste serviço para apresentar aos hóspedes!</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> A primeira foto marcada como principal será exibida nos cards de listagem.
            </div>
            
            <div class="row g-4">
                <?php foreach ($photos as $photo): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($photo['url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($photo['caption'] ?? 'Foto') ?>"
                                     style="height: 250px; object-fit: cover;">
                                
                                <?php if ($photo['is_main_photo']): ?>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-primary">
                                        <i class="bi bi-star-fill"></i> Principal
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <?php if ($photo['caption']): ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($photo['caption']) ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <?php if (!$photo['is_main_photo']): ?>
                                        <form method="POST" class="flex-fill">
                                            <input type="hidden" name="action" value="set_main">
                                            <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-star"></i> Definir como Principal
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="<?= $photo['is_main_photo'] ? 'w-100' : 'flex-fill' ?>" onsubmit="return confirmDelete('Tem certeza que deseja excluir esta foto?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
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

<?php include '../includes/footer.php'; ?>
