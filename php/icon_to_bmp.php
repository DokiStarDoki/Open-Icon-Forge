<?php
$file = urldecode($_GET['file']);
$sourcePath = "input/" . $file;
$baseName = pathinfo($file, PATHINFO_FILENAME);
$outputPath = "input/" . $baseName . ".bmp";

if (!file_exists($sourcePath)) {
    echo json_encode(["error" => "Source PNG not found."]);
    exit;
}

$image = imagecreatefrompng($sourcePath);
$width = imagesx($image);
$height = imagesy($image);

// Convert to high-contrast black-and-white
$bw = imagecreatetruecolor($width, $height);
imagefill($bw, 0, 0, imagecolorallocate($bw, 255, 255, 255)); // White background

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $gray = ($r + $g + $b) / 3;
        $color = ($gray < 128) ? 0 : 255;
        $pixelColor = imagecolorallocate($bw, $color, $color, $color);
        imagesetpixel($bw, $x, $y, $pixelColor);
    }
}

imagebmp($bw, $outputPath);
imagedestroy($bw);

echo json_encode(["success" => true, "bmp" => $outputPath]);
