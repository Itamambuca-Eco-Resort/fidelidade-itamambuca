<?php
require_once 'config/config.php';
require_once __DIR__ . '/db_recreation.php';

// ── Conexão dedicada ao banco de dados de recreação (externo) ──
$pdoRec = getRecreationDb();

// ── Busca atividades dos próximos 7 dias ──
$currentDate   = date('Y-m-d');
$endDate       = date('Y-m-d', strtotime('+7 days'));
$recreations   = [];

if ($pdoRec) {
    try {
        $stmt = $pdoRec->prepare(
            "SELECT * FROM recreations WHERE date BETWEEN ? AND ? ORDER BY date, time"
        );
        $stmt->execute([$currentDate, $endDate]);
        $recreations = $stmt->fetchAll();
    } catch (PDOException $e) {
        $recreations = [];
    }
}

// ── Agrupa por data → categoria ──
$activities_by_date = [];
foreach ($recreations as $rec) {
    $activities_by_date[$rec['date']][$rec['category']][] = $rec;
}

// ── Helper: traduz datas para pt-BR ──
function translateDate(string $date): string {
    $daysOfWeek = [
        'Sunday'    => 'Domingo',
        'Monday'    => 'Segunda-feira',
        'Tuesday'   => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday'  => 'Quinta-feira',
        'Friday'    => 'Sexta-feira',
        'Saturday'  => 'Sábado',
    ];
    $months = [
        'January'   => 'Janeiro',
        'February'  => 'Fevereiro',
        'March'     => 'Março',
        'April'     => 'Abril',
        'May'       => 'Maio',
        'June'      => 'Junho',
        'July'      => 'Julho',
        'August'    => 'Agosto',
        'September' => 'Setembro',
        'October'   => 'Outubro',
        'November'  => 'Novembro',
        'December'  => 'Dezembro',
    ];
    $formatted = date('l, d F', strtotime($date));
    $formatted = str_replace(array_keys($daysOfWeek), array_values($daysOfWeek), $formatted);
    $formatted = str_replace(array_keys($months), array_values($months), $formatted);
    return $formatted;
}

$pageTitle       = 'Recreação';
$pageDescription = 'Programação de recreação para crianças e adultos no Itamambuca Eco Resort.';
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="category-hero" style="background-image: url('/assets/images/banners/banner1.jpg'); height: 220px;">
    <div class="container relative z-10 text-center">
        <h1 class="category-hero-title mb-2">
            <i class="bi bi-person-arms-up me-2"></i>Programação de Recreação
        </h1>
        <p class="text-white opacity-75" style="font-size: 1rem; max-width: 520px; margin: 0 auto;">
            Atividades para crianças e adultos nos próximos 7 dias no Resort.
        </p>
    </div>
</div>

<div class="container py-4">

    <?php if (empty($recreations)): ?>
    <!-- Estado vazio -->
    <div class="text-center py-5">
        <div style="font-size: 3.5rem; color: #c9a961; margin-bottom: 1rem;">
            <i class="bi bi-calendar2-x"></i>
        </div>
        <h3 style="color: #1e4344; font-family: 'Barlow Semi Condensed', sans-serif;">
            Sem programação no momento
        </h3>
        <p class="text-muted" style="max-width: 400px; margin: .5rem auto 0;">
            Ainda não há atividades cadastradas para os próximos 7 dias. Verifique mais tarde!
        </p>
    </div>

    <?php else: ?>

    <!-- Abas Crianças / Adultos -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <!-- Nav tabs -->
            <ul class="nav nav-tabs justify-content-center border-0 pt-3 px-3" id="recTab" role="tablist"
                style="gap: .5rem;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-4 py-2" id="child-tab"
                        data-bs-toggle="tab" data-bs-target="#child"
                        type="button" role="tab" aria-controls="child" aria-selected="true"
                        style="border-radius: 50px; font-weight: 600; border: 2px solid #1e4344;">
                        <i class="bi bi-person-arms-up me-1"></i> Crianças
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-2" id="adult-tab"
                        data-bs-toggle="tab" data-bs-target="#adult"
                        type="button" role="tab" aria-controls="adult" aria-selected="false"
                        style="border-radius: 50px; font-weight: 600; border: 2px solid transparent;">
                        <i class="bi bi-person me-1"></i> Adultos
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="recTabContent">

                <!-- ── ABA CRIANÇAS ── -->
                <div class="tab-pane fade show active p-3" id="child" role="tabpanel" aria-labelledby="child-tab">
                    <?php
                    $childDates = array_filter($activities_by_date, fn($cats) => isset($cats['Child']));
                    if (empty($childDates)):
                    ?>
                        <p class="text-center text-muted py-4">Nenhuma atividade infantil cadastrada para esta semana.</p>
                    <?php else: ?>

                    <div id="carouselChild" class="carousel slide">
                        <div class="carousel-inner">
                            <?php $idx = 0; foreach ($childDates as $date => $categories): ?>
                            <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">

                                <!-- Navegação de data inline -->
                                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle"
                                        type="button" data-bs-target="#carouselChild" data-bs-slide="prev"
                                        style="width:36px; height:36px; padding:0;">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <h5 class="mb-0 text-center"
                                        style="font-family:'Barlow Semi Condensed',sans-serif; font-size:1.1rem; color:#1e4344;">
                                        <?= translateDate($date) ?>
                                    </h5>
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle"
                                        type="button" data-bs-target="#carouselChild" data-bs-slide="next"
                                        style="width:36px; height:36px; padding:0;">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead style="background:#f0f7f4;">
                                            <tr>
                                                <th class="text-center" style="width:70px; color:#1e4344;">Hora</th>
                                                <th style="color:#1e4344;">Atividade</th>
                                                <th class="text-center" style="color:#1e4344;">Local</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories['Child'] as $rec): ?>
                                            <tr>
                                                <td class="text-center fw-bold" style="color:#c9a961;">
                                                    <?= date('H:i', strtotime($rec['time'])) ?>
                                                </td>
                                                <td><?= htmlspecialchars($rec['activity_name']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill"
                                                        style="background:#e8f4f4; color:#1e4344; font-weight:500;">
                                                        <?= htmlspecialchars($rec['location']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php $idx++; endforeach; ?>
                        </div>

                        <?php if ($idx > 1): ?>
                        <div class="carousel-indicators position-static mt-3 mb-0" style="gap:.35rem;">
                            <?php for ($i = 0; $i < $idx; $i++): ?>
                            <button type="button" data-bs-target="#carouselChild" data-bs-slide-to="<?= $i ?>"
                                class="<?= $i === 0 ? 'active' : '' ?>"
                                aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                                aria-label="Dia <?= $i + 1 ?>"
                                style="background:#1e4344; border-radius:3px; width:20px; height:4px; border:none; opacity:.3; transition:opacity .2s;">
                            </button>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php endif; ?>
                </div><!-- /tab Crianças -->


                <!-- ── ABA ADULTOS ── -->
                <div class="tab-pane fade p-3" id="adult" role="tabpanel" aria-labelledby="adult-tab">
                    <?php
                    $adultDates = array_filter($activities_by_date, fn($cats) => isset($cats['Adult']));
                    if (empty($adultDates)):
                    ?>
                        <p class="text-center text-muted py-4">Nenhuma atividade para adultos cadastrada para esta semana.</p>
                    <?php else: ?>

                    <div id="carouselAdult" class="carousel slide">
                        <div class="carousel-inner">
                            <?php $idx = 0; foreach ($adultDates as $date => $categories): ?>
                            <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">

                                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle"
                                        type="button" data-bs-target="#carouselAdult" data-bs-slide="prev"
                                        style="width:36px; height:36px; padding:0;">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <h5 class="mb-0 text-center"
                                        style="font-family:'Barlow Semi Condensed',sans-serif; font-size:1.1rem; color:#1e4344;">
                                        <?= translateDate($date) ?>
                                    </h5>
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle"
                                        type="button" data-bs-target="#carouselAdult" data-bs-slide="next"
                                        style="width:36px; height:36px; padding:0;">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead style="background:#f0f7f4;">
                                            <tr>
                                                <th class="text-center" style="width:70px; color:#1e4344;">Hora</th>
                                                <th style="color:#1e4344;">Atividade</th>
                                                <th class="text-center" style="color:#1e4344;">Local</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories['Adult'] as $rec): ?>
                                            <tr>
                                                <td class="text-center fw-bold" style="color:#c9a961;">
                                                    <?= date('H:i', strtotime($rec['time'])) ?>
                                                </td>
                                                <td><?= htmlspecialchars($rec['activity_name']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill"
                                                        style="background:#e8f4f4; color:#1e4344; font-weight:500;">
                                                        <?= htmlspecialchars($rec['location']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php $idx++; endforeach; ?>
                        </div>

                        <?php if ($idx > 1): ?>
                        <div class="carousel-indicators position-static mt-3 mb-0" style="gap:.35rem;">
                            <?php for ($i = 0; $i < $idx; $i++): ?>
                            <button type="button" data-bs-target="#carouselAdult" data-bs-slide-to="<?= $i ?>"
                                class="<?= $i === 0 ? 'active' : '' ?>"
                                aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                                aria-label="Dia <?= $i + 1 ?>"
                                style="background:#1e4344; border-radius:3px; width:20px; height:4px; border:none; opacity:.3; transition:opacity .2s;">
                            </button>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php endif; ?>
                </div><!-- /tab Adultos -->

            </div><!-- /tab-content -->
        </div><!-- /card-body -->
    </div><!-- /card -->

    <!-- Aviso de alteração -->
    <p class="text-center text-muted fst-italic mt-4" style="font-size:.85rem;">
        <i class="bi bi-info-circle me-1"></i>
        Esta programação pode sofrer alterações sem aviso prévio.
    </p>

    <?php endif; ?>

</div><!-- /container -->

<style>
  /* ── Estilo customizado dos tabs de recreação ── */
  #recTab .nav-link {
    color: #1e4344;
    background: transparent;
    border: 2px solid transparent !important;
    transition: all .2s;
  }
  #recTab .nav-link.active {
    background: #1e4344 !important;
    color: #fff !important;
    border-color: #1e4344 !important;
  }
  #recTab .nav-link:hover:not(.active) {
    border-color: #1e4344 !important;
  }
  /* indicadores do carousel no estilo do app */
  .carousel-indicators [data-bs-target] {
    opacity: .3;
  }
  .carousel-indicators .active {
    opacity: 1 !important;
    width: 28px !important;
  }
</style>

<?php include 'includes/footer.php'; ?>