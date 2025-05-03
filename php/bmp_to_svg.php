<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Get JSON input
$body = json_decode(file_get_contents("php://input"), true);
$bmpRelPath = $body['bmp_path'] ?? null;

if (!$bmpRelPath) {
  http_response_code(400);
  echo json_encode(["error" => "Missing bmp_path"]);
  exit;
}

// Resolve absolute file path
$webRoot = realpath(__DIR__ . '/../');
$bmpPath = realpath($webRoot . '/' . $bmpRelPath);
if (!$bmpPath || !file_exists($bmpPath)) {
  http_response_code(404);
  echo json_encode(["error" => "BMP file not found: $bmpPath"]);
  exit;
}

// Prepare output SVG path
$filename = pathinfo($bmpPath, PATHINFO_FILENAME);
$svgDir = $webRoot . '/input/';
$svgPath = $svgDir . $filename . '.svg';

if (!is_dir($svgDir) && !mkdir($svgDir, 0755, true)) {
  http_response_code(500);
  echo json_encode(["error" => "Failed to create vectorized folder: $svgDir"]);
  exit;
}

// Run Potrace
$command = "potrace " . escapeshellarg($bmpPath) . " -s -o " . escapeshellarg($svgPath);
exec($command . " 2>&1", $output, $exitCode);

if (!file_exists($svgPath) || $exitCode !== 0) {
  http_response_code(500);
  echo json_encode([
    "error" => "Potrace failed",
    "command" => $command,
    "output" => implode("\n", $output),
    "code" => $exitCode
  ]);
  exit;
}

// Clean up BMP
@unlink($bmpPath);

// Return success
echo json_encode([
  "success" => true,
  "svg_path" => 'icons/vectorized/' . basename($svgPath)
]);
