<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$pageTitle = 'Scanner QR Code';
require_once 'config/config.php';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold mb-2">📷 QR Code Check-in</h1>
        <p class="text-muted">Escaneie o QR Code das atrações do Resort para ganhar pontos</p>
    </div>

    <!-- Scanner Area -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div id="scanner-container" style="position: relative; width: 100%; max-width: 500px; margin: 0 auto;">
                <video id="scanner-video" style="width: 100%; border-radius: 0.5rem;" autoplay playsinline></video>
                <canvas id="scanner-canvas" style="display: none;"></canvas>
                
                <!-- Overlay de Scan -->
                <div id="scan-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                    <div style="width: 250px; height: 250px; border: 3px solid #fff; border-radius: 20px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status -->
    <div id="scan-status" class="alert alert-info text-center">
        <i class="bi bi-camera"></i> Posicione o QR Code dentro do quadrado
    </div>

    <!-- Resultado -->
    <div id="scan-result" class="card border-0 shadow-sm" style="display: none;">
        <div class="card-body text-center">
            <div id="result-icon" class="mb-3" style="font-size: 4rem;"></div>
            <h4 id="result-title" class="mb-2"></h4>
            <p id="result-message" class="text-muted mb-3"></p>
            <div id="result-points" class="mb-3"></div>
            <button onclick="location.reload()" class="btn btn-primary">Escanear Novamente</button>
        </div>
    </div>

    <!-- Instruções -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> Como Funciona</h5>
            <ol class="mb-0">
                <li class="mb-2">Permita o acesso à câmera quando solicitado</li>
                <li class="mb-2">Aponte a câmera para o QR Code disponível no local</li>
                <li class="mb-2">Aguarde a leitura automática</li>
                <li class="mb-2">Tarefas e Missões garantem pontos extras para sua estadia!</li>
            </ol>
        </div>
    </div>

    <!-- Botão Manual -->
    <div class="text-center mt-4">
        <p class="text-muted small">Não consegue escanear?</p>
        <a href="index.php" class="btn btn-outline-primary">Fazer Check-in Manual</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
const video = document.getElementById('scanner-video');
const canvas = document.getElementById('scanner-canvas');
const ctx = canvas.getContext('2d');
const statusDiv = document.getElementById('scan-status');
const resultDiv = document.getElementById('scan-result');
let scanning = true;

// Iniciar câmera
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        video.srcObject = stream;
        video.play();
        requestAnimationFrame(scan);
    } catch (err) {
        statusDiv.className = 'alert alert-danger text-center';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Erro ao acessar câmera: ' + err.message;
    }
}

// Escanear QR Code
function scan() {
    if (!scanning) return;
    
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        
        if (code) {
            scanning = false;
            processQRCode(code.data);
            return;
        }
    }
    
    requestAnimationFrame(scan);
}

// Processar QR Code
async function processQRCode(qrData) {
    statusDiv.className = 'alert alert-warning text-center';
    statusDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> Processando...';
    
    try {
        // Obter geolocalização
        let latitude = null;
        let longitude = null;
        
        if (navigator.geolocation) {
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 10000 });
                });
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
            } catch (geoErr) {
                console.warn('Geolocalização não disponível ou negada:', geoErr);
            }
        }
        
        // Payload para debug
        const payload = {
            qr_code: qrData,
            latitude: latitude,
            longitude: longitude
        };
        console.log("Enviando para API:", payload);

        // Enviar para API
        const response = await fetch('/api/scan-qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const resultText = await response.text();
        console.log("Resposta bruta da API:", resultText);
        
        let result;
        try {
            result = JSON.parse(resultText);
        } catch (e) {
            console.error("Erro ao fazer parse do JSON:", e);
            throw new Error("Resposta inválida da API. Verifique o console.");
        }
        
        // Parar vídeo
        const stream = video.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
        
        // Mostrar resultado
        statusDiv.style.display = 'none';
        document.getElementById('scanner-container').style.display = 'none';
        resultDiv.style.display = 'block';
        
        if (result.success) {
            document.getElementById('result-icon').textContent = '✅';
            document.getElementById('result-title').textContent = 'Check-in Realizado!';
            document.getElementById('result-message').textContent = result.partner_name || 'Atração / Missão Completa';
            document.getElementById('result-points').innerHTML = `
                <span class="badge bg-success" style="font-size: 1.5rem; padding: 0.75rem 1.5rem;">
                    +${result.points_earned} pontos
                </span>
            `;
        } else {
            document.getElementById('result-icon').textContent = '❌';
            document.getElementById('result-title').textContent = 'Erro';
            document.getElementById('result-message').textContent = result.message || 'Não foi possível fazer check-in';
            document.getElementById('result-points').innerHTML = '';
        }
        
    } catch (err) {
        statusDiv.className = 'alert alert-danger text-center';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Erro: ' + err.message;
        setTimeout(() => {
            statusDiv.className = 'alert alert-info text-center';
            statusDiv.innerHTML = '<i class="bi bi-camera"></i> Posicione o QR Code dentro do quadrado';
            scanning = true;
            requestAnimationFrame(scan);
        }, 3000);
    }
}

// Iniciar ao carregar
startCamera();
</script>

<?php include 'includes/footer.php'; ?>
