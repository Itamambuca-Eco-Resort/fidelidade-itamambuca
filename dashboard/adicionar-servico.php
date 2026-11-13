<?php

// Habilita a exibição de todos os erros de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Inclua APENAS o config.php (ele já inicia a sessão e o banco de dados)
require_once '../config/config.php'; 

// 2. Verificar se está logado
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

// Buscar categorias
$stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $long_description = trim($_POST['long_description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $qr_code = trim($_POST['qr_code'] ?? '');
    
    // Gerar o slug
    $slug = generateSlug($name);

    // Horários
    $business_hours = json_encode([
        'monday' => ['open' => $_POST['mon_open'] ?? '', 'close' => $_POST['mon_close'] ?? ''],
        'tuesday' => ['open' => $_POST['tue_open'] ?? '', 'close' => $_POST['tue_close'] ?? ''],
        'wednesday' => ['open' => $_POST['wed_open'] ?? '', 'close' => $_POST['wed_close'] ?? ''],
        'thursday' => ['open' => $_POST['thu_open'] ?? '', 'close' => $_POST['thu_close'] ?? ''],
        'friday' => ['open' => $_POST['fri_open'] ?? '', 'close' => $_POST['fri_close'] ?? ''],
        'saturday' => ['open' => $_POST['sat_open'] ?? '', 'close' => $_POST['sat_close'] ?? ''],
        'sunday' => ['open' => $_POST['sun_open'] ?? '', 'close' => $_POST['sun_close'] ?? '']
    ]);

    if (empty($name) || empty($short_description) || empty($category_id)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO partners (
                    user_id, category_id, name, slug, short_description, long_description,
                    business_hours, qr_code, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $category_id, $name, $slug, $short_description, $long_description,
                $business_hours, $qr_code
            ]);
            
            // Se o usuário era apenas turista, sobe para admin/hotel (simplificado)
            if ($_SESSION['user_role'] === 'tourist') {
                $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $_SESSION['user_role'] = 'admin';
            }
            
            $success = 'Serviço cadastrado com sucesso! Redirecionando...';
            header('refresh:2;url=index.php');
            
        } catch (PDOException $e) {
            $error = 'Erro ao cadastrar serviço: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Adicionar Novo Serviço';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="index.php" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">Novo Serviço do Hotel</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Informações do Serviço</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Nome do Serviço *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Ex: Room Service, SPA, etc">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoria *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                        <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição Curta *</label>
                            <input type="text" name="short_description" class="form-control" required maxlength="200"
                                   value="<?= htmlspecialchars($_POST['short_description'] ?? '') ?>" placeholder="Breve frase de destaque...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição Completa</label>
                            <textarea name="long_description" class="form-control" rows="4" placeholder="Descreva os benefícios e o que o hóspede encontra neste serviço..."><?= htmlspecialchars($_POST['long_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Código QR Fixo (Identificador)</label>
                            <input type="text" name="qr_code" class="form-control" placeholder="Ex: room-service-001" 
                                   value="<?= htmlspecialchars($_POST['qr_code'] ?? '') ?>">
                            <div class="form-text">Este código será usado para gerar o QRCode de check-in.</div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="bi bi-clock"></i> Horário de Funcionamento</h5>
                        
                        <?php
                        $days = [
                            'mon' => 'Segunda-feira',
                            'tue' => 'Terça-feira',
                            'wed' => 'Quarta-feira',
                            'thu' => 'Quinta-feira',
                            'fri' => 'Sexta-feira',
                            'sat' => 'Sábado',
                            'sun' => 'Domingo'
                        ];

                        foreach ($days as $key => $label):
                        ?>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= $label ?></label>
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="<?= $key ?>_open" class="form-control form-control-sm" 
                                           value="<?= htmlspecialchars($_POST[$key . '_open'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="<?= $key ?>_close" class="form-control form-control-sm" 
                                           value="<?= htmlspecialchars($_POST[$key . '_close'] ?? '') ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr class="my-4">

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" style="background:#1e4344; border:none; border-radius:12px;">
                                <i class="bi bi-check-circle"></i> Cadastrar Serviço
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
