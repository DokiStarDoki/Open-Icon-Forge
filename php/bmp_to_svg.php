<?php
// Enable error display for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Parse incoming JSON
$body = json_decode(file_get_contents("php://input"), true);
$bmpRelPath = $body['bmp_path'] ?? null;

if (!$bmpRelPath) {
  http_response_code(400);
  echo json_encode(["error" => "Missing bmp_path"]);
  exit;
}

// Resolve full filesystem path
$webRoot = realpath(__DIR__ . '/../');
$bmpPath = realpath($webRoot . '/' . $bmpRelPath);

if (!$bmpPath || !file_exists($bmpPath)) {
  http_response_code(404);
  echo json_encode(["error" => "BMP file not found: $bmpPath"]);
  exit;
}

// Prepare SVG output path
$filename = pathinfo($bmpPath, PATHINFO_FILENAME);
$svgDir = $webRoot . '/icons/vectorized/';
$svgPath = $svgDir . $filename . '.svg';

// Ensure output directory exists
if (!is_dir($svgDir) && !mkdir($svgDir, 0755, true)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to create vectorized folder: $svgDir"]);
  exit;
}

// Run Potrace
$command = "potrace " . escapeshellarg($bmpPath) . " -s -o " . escapeshellarg($svgPath);
exec($command . " 2>&1", $output, $resultCode);

// Handle failure
if (!file_exists($svgPath) || $resultCode !== 0) {
  http_response_code(500);
  echo json_encode([
    "error" => "Potrace failed",
    "cmd" => $command,
    "output" => implode("\n", $output),
    "code" => $resultCode
  ]);
  exit;
}

// Optionally delete the BMP file after success
@unlink($bmpPath);

// Return success + relative SVG path
echo json_encode([
  "success" => true,
  "svg_path" => 'icons/vectorized/' . basename($svgPath)
]);
