<?php
$input = $_GET['file'] ?? 'icons/generated/example.webp';
$ext = strtolower(pathinfo($input, PATHINFO_EXTENSION));
$filename = pathinfo($input, PATHINFO_FILENAME);

$bmpDir = 'icons/temp/';
$svgDir = 'icons/vectorized/';
$bmpPath = $bmpDir . $filename . '.bmp';
$svgPath = $svgDir . $filename . '.svg';

if (!file_exists($input)) {
  http_response_code(404);
  exit("File not found: $input");
}

// Load image with GD
switch ($ext) {
  case 'webp':
    $image = imagecreatefromwebp($input);
    break;
  case 'png':
    $image = imagecreatefrompng($input);
    break;
  default:
    http_response_code(415);
    exit("Unsupported format: $ext");
}

if (!$image) {
  http_response_code(500);
  exit("Failed to load image.");
}

// Create directories if needed
if (!is_dir($bmpDir)) mkdir($bmpDir, 0755, true);
if (!is_dir($svgDir)) mkdir($svgDir, 0755, true);

// Save BMP
if (!imagebmp($image, $bmpPath)) {
  http_response_code(500);
  exit("Failed to save BMP.");
}
imagedestroy($image);

// Run Potrace
exec("potrace $bmpPath -s -o $svgPath");

// Confirm and return
if (!file_exists($svgPath)) {
  http_response_code(500);
  exit("Potrace failed.");
}

header("Content-Type: application/json");
echo json_encode([
  "success" => true,
  "svg_path" => $svgPath
]);
