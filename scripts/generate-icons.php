<?php
/**
 * Script para gerar ícones PWA em múltiplos tamanhos
 * Execute: php generate-icons.php
 */

// Tamanhos necessários para PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Cor de fundo e texto do ícone
$bgColor = '#2563eb'; // Azul primário
$textColor = '#ffffff';

foreach ($sizes as $size) {
    // Criar imagem
    $image = imagecreatetruecolor($size, $size);
    
    // Alocar cores
    $bg = imagecolorallocate($image, 37, 99, 235); // #2563eb
    $text = imagecolorallocate($image, 255, 255, 255);
    
    // Preencher fundo
    imagefilledrectangle($image, 0, 0, $size, $size, $bg);
    
    // Adicionar texto "S" (Santour)
    $fontSize = $size * 0.5;
    $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    
    if (file_exists($fontFile)) {
        $textBox = imagettfbbox($fontSize, 0, $fontFile, 'S');
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        
        $x = ($size - $textWidth) / 2;
        $y = ($size + $textHeight) / 2;
        
        imagettftext($image, $fontSize, 0, $x, $y, $text, $fontFile, 'S');
    } else {
        // Fallback: desenhar círculo simples
        $centerX = $size / 2;
        $centerY = $size / 2;
        $radius = $size * 0.3;
        imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $text);
    }
    
    // Salvar imagem
    $filename = __DIR__ . "/../assets/icons/icon-{$size}x{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "✓ Gerado: {$filename}\n";
}

echo "\n✅ Todos os ícones foram gerados com sucesso!\n";
echo "📁 Localização: /assets/icons/\n";
?>
