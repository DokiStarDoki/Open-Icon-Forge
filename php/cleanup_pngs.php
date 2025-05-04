<?php
header("Content-Type: application/json");

$inputDir = realpath(__DIR__ . '/../input');

if (!$inputDir || !is_dir($inputDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Input directory not found.']);
    exit;
}

$deleted = [];
$skipped = [];

foreach (glob($inputDir . '/*.png') as $pngFile) {
    if (@unlink($pngFile)) {
        $deleted[] = basename($pngFile);
    } else {
        $skipped[] = basename($pngFile);
    }
}

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'skipped' => $skipped
]);
