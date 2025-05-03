<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Read and decode JSON input
$body = json_decode(file_get_contents("php://input"), true);
$svgPath = $body['svg_path'] ?? null;

if (!$svgPath) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing svg_path"]);
    exit;
}

$webRoot = realpath(__DIR__ . '/../');
$inputPath = realpath($webRoot . '/' . $svgPath);

if (!$inputPath || !file_exists($inputPath)) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "SVG not found"]);
    exit;
}

// Determine base name and matching JSON
$baseName = basename($svgPath, '.svg');
$jsonPath = $webRoot . "/input/$baseName.json";
if (!file_exists($jsonPath)) {
    echo json_encode(["success" => false, "error" => "Metadata JSON not found"]);
    exit;
}

$meta = json_decode(file_get_contents($jsonPath), true);
$theme = $meta['theme'] ?? null;
$name = $meta['name'] ?? null;

if (!$theme || !$name) {
    echo json_encode(["success" => false, "error" => "Invalid metadata"]);
    exit;
}

$destFolder = $webRoot . "/icons/$theme";
if (!is_dir($destFolder)) {
    mkdir($destFolder, 0777, true);
}

$destSvgPath = "$destFolder/$name.svg";
$destJsonPath = "$destFolder/$name.json";

// Move SVG
rename($inputPath, $destSvgPath);

// Move JSON
rename($jsonPath, $destJsonPath);

// Delete PNG and BMP
$pngPath = $webRoot . "/input/$baseName.png";
$bmpPath = $webRoot . "/input/$baseName.bmp";

if (file_exists($pngPath)) unlink($pngPath);
if (file_exists($bmpPath)) unlink($bmpPath);

echo json_encode(["success" => true, "moved_to" => $destSvgPath]);
