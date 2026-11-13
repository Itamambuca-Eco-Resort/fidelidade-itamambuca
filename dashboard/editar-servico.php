<?php
require_once '../config/config.php';
require_once '../models/Partner.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$db = Database::getInstance()->getConnection();
$partnerModel = new Partner();

$partnerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

// Se for admin, pode editar qualquer um. Se não, apenas o seu (embora agora todos sejam admin do hotel)
if (isAdmin() && $partnerId > 0) {
    $partner = $partnerModel->findById($partnerId);
} else {
    // Busca o primeiro parceiro vinculado ao usuário (legado ou se houver múltiplos)
    $partners = $partnerModel->findByUserId($userId);
    $partner = !empty($partners) ? $partners[0] : null;
}

if (!$partner) {
    redirect('/dashboard/');
}

$error = '';
$success = '';

// Buscar categorias
$stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'long_description' => trim($_POST['long_description'] ?? ''),
        'category_id' => intval($_POST['category_id'] ?? 0),
        'qr_code' => trim($_POST['qr_code'] ?? ''),
    ];

    // Horários
    $data['business_hours'] = json_encode([
        'monday' => ['open' => $_POST['mon_open'] ?? '', 'close' => $_POST['mon_close'] ?? ''],
        'tuesday' => ['open' => $_POST['tue_open'] ?? '', 'close' => $_POST['tue_close'] ?? ''],
        'wednesday' => ['open' => $_POST['wed_open'] ?? '', 'close' => $_POST['wed_close'] ?? ''],
        'thursday' => ['open' => $_POST['thu_open'] ?? '', 'close' => $_POST['thu_close'] ?? ''],
        'friday' => ['open' => $_POST['fri_open'] ?? '', 'close' => $_POST['fri_close'] ?? ''],
        'saturday' => ['open' => $_POST['sat_open'] ?? '', 'close' => $_POST['sat_close'] ?? ''],
        'sunday' => ['open' => $_POST['sun_open'] ?? '', 'close' => $_POST['sun_close'] ?? '']
    ]);

    if (empty($data['name']) || empty($data['short_description']) || empty($data['category_id'])) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        if ($partnerModel->update($partner['id'], $data)) {
            $success = 'Serviço atualizado com sucesso!';
            // Recarregar dados
            $partner = $partnerModel->findById($partner['id']);
        } else {
            $error = 'Erro ao atualizar serviço.';
        }
    }
}

$hours = json_decode($partner['business_hours'] ?? '{}', true);

$pageTitle = 'Editar Serviço';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">Editar Serviço</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Informações do Serviço</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Nome do Serviço *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($partner['name']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoria *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $partner['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição Curta *</label>
                            <input type="text" name="short_description" class="form-control" required maxlength="200"
                                   value="<?= htmlspecialchars($partner['short_description'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição Completa</label>
                            <textarea name="long_description" class="form-control" rows="4"><?= htmlspecialchars($partner['long_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Código QR Fixo (Identificador)</label>
                            <input type="text" name="qr_code" class="form-control" 
                                   value="<?= htmlspecialchars($partner['qr_code'] ?? '') ?>">
                            <div class="form-text">Este código identifica o serviço no sistema de check-in.</div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="bi bi-clock"></i> Horário de Funcionamento</h5>
                        
                        <?php
                        $days = [
                            'monday' => 'Segunda-feira',
                            'tuesday' => 'Terça-feira',
                            'wednesday' => 'Quarta-feira',
                            'thursday' => 'Quinta-feira',
                            'friday' => 'Sexta-feira',
                            'saturday' => 'Sábado',
                            'sunday' => 'Domingo'
                        ];

                        foreach ($days as $key => $label):
                            $open = $hours[$key]['open'] ?? '';
                            $close = $hours[$key]['close'] ?? '';
                        ?>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= $label ?></label>
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="<?= substr($key, 0, 3) ?>_open" class="form-control form-control-sm" 
                                           value="<?= htmlspecialchars($open) ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="<?= substr($key, 0, 3) ?>_close" class="form-control form-control-sm" 
                                           value="<?= htmlspecialchars($close) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr class="my-4">

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" style="background:#1e4344; border:none; border-radius:12px;">
                                <i class="bi bi-check-circle"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
