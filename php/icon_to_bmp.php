<?php
// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Get image_path from POST
$body = json_decode(file_get_contents("php://input"), true);
if (!isset($body['image_path'])) {
  http_response_code(400);
  echo json_encode(["error" => "Missing image_path"]);
  exit;
}

// Convert relative web path to filesystem path
$webRoot = realpath(__DIR__ . '/../'); // e.g. project root
$inputPath = realpath($webRoot . '/' . $body['image_path']);
if (!$inputPath || !file_exists($inputPath)) {
  http_response_code(404);
  echo json_encode(["error" => "Image file not found at path: $inputPath"]);
  exit;
}

$ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
$filename = pathinfo($inputPath, PATHINFO_FILENAME);
$bmpDir = $webRoot . '/icons/temp/';
$bmpPath = $bmpDir . $filename . '.bmp';

// Ensure temp directory exists
if (!is_dir($bmpDir) && !mkdir($bmpDir, 0755, true)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to create temp folder: $bmpDir"]);
  exit;
}

// Load image using GD
switch ($ext) {
  case 'webp':
    $image = imagecreatefromwebp($inputPath);
    break;
  case 'png':
    $image = imagecreatefrompng($inputPath);
    break;
  default:
    http_response_code(415);
    echo json_encode(["error" => "Unsupported file type: $ext"]);
    exit;
}

if (!$image) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to load image: $inputPath"]);
  exit;
}

// Save BMP
if (!function_exists('imagebmp')) {
  http_response_code(500);
  echo json_encode(["error" => "imagebmp() not available in this PHP build."]);
  exit;
}

if (!imagebmp($image, $bmpPath)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to save BMP to $bmpPath"]);
  exit;
}

imagedestroy($image);

// Respond with the .bmp path
echo json_encode([
  "success" => true,
  "bmp_path" => 'icons/temp/' . basename($bmpPath),
]);
