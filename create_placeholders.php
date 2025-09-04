<?php
// Script per creare immagini placeholder
$uploadsDir = __DIR__ . '/public/uploads/images';

for ($i = 1; $i <= 10; $i++) {
    $width = 300;
    $height = 300;
    
    // Crea un'immagine
    $image = imagecreate($width, $height);
    
    // Colori
    $gray = imagecolorallocate($image, 128, 128, 128);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Sfondo grigio
    imagefill($image, 0, 0, $gray);
    
    // Testo
    $text = "Placeholder $i";
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $text, $white);
    
    // Salva l'immagine
    $filename = $uploadsDir . "/placeholder-$i.jpg";
    imagejpeg($image, $filename, 80);
    imagedestroy($image);
    
    echo "Created: $filename\n";
}

echo "Placeholder images created successfully!\n";
?> 