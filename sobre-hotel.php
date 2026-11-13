<?php
require_once 'config/config.php';

$pageTitle = 'O Resort';
$pageDescription = 'Conheça o Itamambuca Eco Resort, um paraíso de Mata Atlântica preservada e praias incríveis em Ubatuba.';
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="category-hero position-relative" style="background-image: url('https://itamambuca.com.br/templates/yootheme/cache/41/praia-01-4134a144.webp'); height: 400px; background-attachment: fixed; background-size: cover; background-position: center;">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(31,66,32,0.6), rgba(0,0,0,0.8));"></div>
    <div class="container h-100 d-flex flex-column justify-content-center position-relative z-10 text-center">
        <span class="badge bg-gold text-dark mb-3 mx-auto px-3 py-2" style="font-size: 0.9rem; letter-spacing: 2px; text-transform: uppercase;">Paraíso Natural</span>
        <h1 class="display-4 fw-bold text-white mb-3" style="font-family: 'Barlow Semi Condensed', sans-serif;">Sobre o Itamambuca Eco Resort</h1>
        <p class="text-white opacity-75 fs-5 mx-auto" style="max-width: 700px;">Harmonia perfeita entre o conforto, a exuberância da Mata Atlântica e as ondas perfeitas da Praia de Itamambuca.</p>
    </div>
</div>

<div class="container py-5">
    <!-- Bloco 1: Reserva -->
    <div class="row align-items-center mb-5 pb-4">
        <div class="col-lg-6 mb-4 mb-lg-0 pe-lg-5">
            <h2 class="section-title mb-4">Um refúgio na Mata Atlântica</h2>
            <div class="section-divider ms-0 mb-4"></div>
            <p class="lead text-muted mb-4">O Itamambuca Eco Resort está localizado em uma reserva particular de 100.000 m² de Mata Atlântica preservada, situada no encontro do Rio Itamambuca com o mar, em Ubatuba (SP).</p>
            <p class="text-muted mb-4">O resort destaca-se por sua harmonia com a natureza, permitindo aos hóspedes avistar animais silvestres como capivaras, tatus, esquilos e lagartos soltos e livres em seu habitat natural.</p>
            
            <div class="d-flex gap-3 mt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-light-teal rounded-circle d-flex align-items-center justify-content-center text-teal" style="width: 48px; height: 48px;">
                        <i class="bi bi-tree fs-4"></i>
                    </div>
                    <div>
                        <strong class="d-block text-navy">100.000 m²</strong>
                        <span class="small text-muted">de Área Verde</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-light-teal rounded-circle d-flex align-items-center justify-content-center text-teal" style="width: 48px; height: 48px;">
                        <i class="bi bi-water fs-4"></i>
                    </div>
                    <div>
                        <strong class="d-block text-navy">Rio & Mar</strong>
                        <span class="small text-muted">Encontro das Águas</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="position-relative">
                <img src="https://itamambuca.com.br/templates/yootheme/cache/0e/praia-03-0e384023.webp" class="img-fluid rounded-4 shadow-lg w-100" alt="Vista da Praia e do Rio Itamambuca" style="object-fit: cover; height: 450px;">
                <div class="position-absolute bottom-0 start-0 m-4 bg-white p-3 rounded-3 shadow-sm d-flex align-items-center gap-3" style="max-width: 250px;">
                    <i class="bi bi-geo-alt-fill fs-2 text-gold"></i>
                    <div>
                        <strong class="d-block text-dark mb-0">Localização Ideal</strong>
                        <small class="text-muted lh-1">Parque Estadual da Serra do Mar</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco 2: A Praia e Atividades -->
    <div class="row align-items-center flex-column-reverse flex-lg-row pt-3 mb-5 pb-5 border-bottom">
        <div class="col-lg-6">
            <div class="row g-3">
                <div class="col-6">
                    <img src="https://itamambuca.com.br/templates/yootheme/cache/15/praia-04-15139019.webp" class="img-fluid rounded-4 shadow-sm w-100 mb-3" alt="Ondas da Praia de Itamambuca" style="object-fit: cover; height: 280px;">
                </div>
                <div class="col-6 mt-4">
                    <img src="https://itamambuca.com.br/templates/yootheme/cache/df/praia-05-df2d638a.webp" class="img-fluid rounded-4 shadow-sm w-100" alt="Rio Itamambuca e Praia" style="object-fit: cover; height: 280px;">
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4 mb-lg-0 ps-lg-5">
            <h2 class="section-title mb-4">Para Surfar ou Relaxar</h2>
            <div class="section-divider ms-0 mb-4"></div>
            <p class="lead text-muted mb-4">A reserva ecológica do hotel faz divisa com o Parque Estadual da Serra do Mar, um importante ecossistema brasileiro preservado de forma exuberante.</p>
            <p class="text-muted mb-4">Além da praia, famosa internacionalmente por ser palco de campeonatos de surf com ondas regulares o ano todo, o resort oferece a tranquilidade das águas claras e mansas do Rio Itamambuca, sendo um local absolutamente ideal tanto para surfistas quanto para famílias com crianças.</p>
            
            <ul class="list-unstyled mt-4 d-flex flex-column gap-3">
                <li class="d-flex align-items-center gap-3">
                    <i class="bi bi-check2-circle text-teal fs-4"></i>
                    <span class="text-dark fw-medium">Ondas perfeitas para surf e esportes aquáticos</span>
                </li>
                <li class="d-flex align-items-center gap-3">
                    <i class="bi bi-check2-circle text-teal fs-4"></i>
                    <span class="text-dark fw-medium">Rio calmo para passeios de Standup Paddle</span>
                </li>
                <li class="d-flex align-items-center gap-3">
                    <i class="bi bi-check2-circle text-teal fs-4"></i>
                    <span class="text-dark fw-medium">Tranquilidade e segurança para as crianças brincarem</span>
                </li>
            </ul>
            
            <a href="/index.php" class="btn btn-primary mt-4 py-2 px-4 shadow-sm border-0" style="border-radius: 50px;">Ver Atividades e Missões</a>
        </div>
    </div>
    
    <!-- Chamada final -->
    <div class="text-center bg-light rounded-4 p-5 shadow-sm mt-4">
        <i class="bi bi-camera text-gold fs-1 mb-3 d-block"></i>
        <h3 class="mb-3" style="font-family: 'Barlow Semi Condensed', sans-serif; color: #1f4220;">Mostre-nos o seu paraíso</h3>
        <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">Hospede-se, relaxe e não esqueça de fazer check-in nas nossas atrações para ganhar pontos e trocar por recompensas exclusivas do resort.</p>
        <a href="/informacoes-uteis.php" class="btn btn-outline py-2 px-4" style="border-radius: 50px;">Ler Informações Úteis</a>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
