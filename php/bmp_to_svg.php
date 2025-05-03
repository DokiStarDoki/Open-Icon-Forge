<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

$webRoot = realpath(__DIR__ . '/../');
$tempDir = $webRoot . '/icons/temp/';
$inputDir = $webRoot . '/input/';

$filename = $_GET['file'] ?? null;
if (!$filename) {
  http_response_code(400);
  echo json_encode(["error" => "Missing ?file parameter"]);
  exit;
}

$bmpPath = realpath($tempDir . $filename);
if (!$bmpPath || !file_exists($bmpPath)) {
  http_response_code(404);
  echo json_encode(["error" => "BMP file not found", "path" => $bmpPath]);
  exit;
}

$name = pathinfo($bmpPath, PATHINFO_FILENAME);
$svgPath = $inputDir . $name . '.svg';

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

// Clean up
@unlink($bmpPath);

echo json_encode([
  "success" => true,
  "svg_path" => 'input/' . basename($svgPath)
]);
