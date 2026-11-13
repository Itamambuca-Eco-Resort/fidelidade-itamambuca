<?php
require_once 'config/config.php';

$pageTitle = 'Informações Úteis';
$pageDescription = 'Guia prático para sua estadia no Itamambuca Eco Resort.';
include 'includes/header.php';
?>

<!-- Hero Section (Mini Heder para a Página Interna) -->
<div class="category-hero" style="background-image: url('/assets/images/resort-bg.jpg'); height: 250px;">
    <div class="container relative z-10 text-center">
        <h1 class="category-hero-title mb-2">Informações Úteis</h1>
        <p class="text-white opacity-75" style="font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Tudo o que você precisa saber para uma estadia perfeita no Itamambuca Eco Resort.</p>
    </div>
</div>

<div class="container py-5">
    
    <!-- Alerta de Boas Vindas -->
    <div class="alert alert-success d-flex align-items-center mb-5" role="alert" style="background-color: #2C5F2D; color: white; border: none; font-size: 1.1rem; justify-content: center; text-align: center; border-radius: 12px;">
        <div>
            <i class="bi bi-stars fs-4 me-2" style="color: #D4B483;"></i>
            <span><strong>Prezado hóspede</strong>, temos imenso prazer em recebê-lo no Itamambuca Eco Resort!</span>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Coluna Esquerda: Kit Check-in -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="category-icon mb-0 me-3" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="bi bi-box2-heart"></i>
                        </div>
                        <h3 class="mb-0" style="color: #1f4220; font-family: 'Barlow Semi Condensed', sans-serif;">Kit de Boas-Vindas</h3>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <p class="text-muted mb-3">No check-in entregamos um kit composto pelos seguintes itens essenciais para sua estadia:</p>
                    <ul class="list-unstyled mb-0" style="color: #555;">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-teal me-2 mt-1"></i>
                            <div><strong>Cartões para Toalhas de Praia:</strong><br><small class="text-muted">Devem ser retiradas na Recepção do Hotel.</small></div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-teal me-2 mt-1"></i>
                            <div><strong>Cartões para Cadeiras de Praia:</strong><br><small class="text-muted">Podem ser retiradas diretamente na praia ou com o barqueiro na travessia do rio.</small></div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-teal me-2 mt-1"></i>
                            <div><strong>Fita de Identificação:</strong><br><small class="text-muted">Uso imprescindível para sua segurança no decorrer de toda a estadia.</small></div>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-teal me-2 mt-1"></i>
                            <div><strong>01 Cartão de Consumo:</strong><br><small class="text-muted">Imprescindível para registrar qualquer consumo dentro do resort.</small></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Regras e Avisos -->
        <div class="col-lg-6">
            <div class="row g-4">
                
                <!-- Card Segurança e Horários -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid #D4B483 !important;">
                        <div class="card-body p-4">
                            <h4 class="mb-3 text-navy d-flex align-items-center">
                                <i class="bi bi-shield-exclamation me-2 text-gold"></i> Horários e Segurança
                            </h4>
                            <p class="text-muted mb-0">Visando a sua segurança, sugerimos que <strong>não sejam utilizadas as piscinas a partir das 20h</strong>, assim como a <strong>travessia para praia</strong> neste mesmo horário.</p>
                        </div>
                    </div>
                </div>

                <!-- Card Proibido Fumar -->
                <div class="col-12">
                     <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid #dc3545 !important;">
                        <div class="card-body p-4">
                            <h4 class="mb-2 text-danger d-flex align-items-center" style="font-family: 'Barlow Semi Condensed', sans-serif;">
                                <i class="bi bi-sign-stop me-2"></i> Não Fumantes
                            </h4>
                            <p class="text-muted mb-0"><strong>Atenção:</strong> Todas as nossas acomodações são estritamente para não fumantes. Pedimos a gentileza de <strong>não fumar no interior dos quartos</strong>.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card Voltagem -->
                <div class="col-12">
                     <div class="card border-0 shadow-sm" style="border-radius: 16px; background-color: #f8f9fa;">
                        <div class="card-body p-4 d-flex align-items-center">
                            <i class="bi bi-lightning-charge fs-2 me-3 text-warning"></i>
                            <div>
                                <h5 class="mb-1 text-navy border-0">Voltagem das Tomadas</h5>
                                <p class="text-muted mb-0">Atenção ao ligar seus aparelhos. Nossas tomadas são <strong>220v</strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Contato Recepção -->
    <div class="mt-5 text-center">
        <div class="d-inline-block px-5 py-4" style="background-color: rgba(212,180,131,.1); border-radius: 20px;">
            <i class="bi bi-telephone text-gold fs-1 mb-2"></i>
            <h3 class="text-navy mb-2" style="font-family: 'Barlow Semi Condensed', sans-serif;">Precisa de algo mais?</h3>
            <p class="text-muted mb-3">Qualquer solicitação, por gentileza de entrar em contato conosco.</p>
            <span class="badge bg-navy py-2 px-4 fs-5 w-auto" style="border-radius: 50px;">
                Ligue <strong class="text-gold ms-1">Ramal 900</strong> (Recepção)
            </span>
        </div>
    </div>

    <!-- Despedida -->
    <div class="mt-5 text-center">
        <p class="text-muted fst-italic fs-5">"Desejamos que sua estada no Itamambuca Eco Resort seja um momento inesquecível."</p>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
