<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

$body = json_decode(file_get_contents("php://input"), true);
$relPath = $body['image_path'] ?? null;

if (!$relPath) {
  http_response_code(400);
  echo json_encode(["error" => "Missing image_path"]);
  exit;
}

$webRoot = realpath(__DIR__ . '/../');
$inputPath = realpath($webRoot . '/' . $relPath);
if (!$inputPath || !file_exists($inputPath)) {
  http_response_code(404);
  echo json_encode(["error" => "Image not found"]);
  exit;
}

$ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
$filename = pathinfo($inputPath, PATHINFO_FILENAME);
$bmpDir = $webRoot . '/icons/temp/';
$bmpPath = $bmpDir . $filename . '.bmp';

if (!is_dir($bmpDir) && !mkdir($bmpDir, 0755, true)) {
  http_response_code(500);
  echo json_encode(["error" => "Could not create temp folder"]);
  exit;
}

// Load image
switch ($ext) {
  case 'webp':
    $src = imagecreatefromwebp($inputPath);
    break;
  case 'png':
    $src = imagecreatefrompng($inputPath);
    break;
  default:
    http_response_code(415);
    echo json_encode(["error" => "Unsupported file type"]);
    exit;
}

if (!$src) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to load image"]);
  exit;
}

$width = imagesx($src);
$height = imagesy($src);
$bw = imagecreatetruecolor($width, $height);

// Convert to black and white
$black = imagecolorallocate($bw, 0, 0, 0);
$white = imagecolorallocate($bw, 255, 255, 255);
$threshold = 127;

for ($y = 0; $y < $height; $y++) {
  for ($x = 0; $x < $width; $x++) {
    $rgb = imagecolorat($src, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    $brightness = 0.299 * $r + 0.587 * $g + 0.114 * $b;
    imagesetpixel($bw, $x, $y, $brightness < $threshold ? $black : $white);
  }
}

imagedestroy($src);

// Save to BMP
if (!imagebmp($bw, $bmpPath)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to write BMP"]);
  exit;
}
imagedestroy($bw);

echo json_encode([
  "success" => true,
  "bmp_path" => 'icons/temp/' . basename($bmpPath),
]);
