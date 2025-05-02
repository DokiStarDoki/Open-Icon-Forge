<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$body = json_decode(file_get_contents("php://input"), true);
$input = $body['image_path'] ?? null;

if (!$input) {
  http_response_code(400);
  echo json_encode(["error" => "Missing image_path"]);
  exit;
}

$ext = strtolower(pathinfo($input, PATHINFO_EXTENSION));
$filename = pathinfo($input, PATHINFO_FILENAME);

$bmpDir = '../icons/temp/';
$svgDir = '../icons/vectorized/';
$bmpPath = $bmpDir . $filename . '.bmp';
$svgPath = $svgDir . $filename . '.svg';

if (!file_exists($input)) {
  http_response_code(404);
  echo json_encode(["error" => "File not found: $input"]);
  exit;
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
    echo json_encode(["error" => "Unsupported format: $ext"]);
    exit;
}

if (!$image) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to load image."]);
  exit;
}

// Create directories if needed
if (!is_dir($bmpDir)) mkdir($bmpDir, 0755, true);
if (!is_dir($svgDir)) mkdir($svgDir, 0755, true);

// Save BMP
if (!imagebmp($image, $bmpPath)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to save BMP."]);
  exit;
}
imagedestroy($image);

// Run Potrace
exec("potrace $bmpPath -s -o $svgPath");

// Confirm and return
if (!file_exists($svgPath)) {
  http_response_code(500);
  echo json_encode(["error" => "Potrace failed."]);
  exit;
}

echo json_encode([
  "success" => true,
  "svg_path" => $svgPath
]);
