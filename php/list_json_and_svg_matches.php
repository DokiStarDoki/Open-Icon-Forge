<?php
header("Content-Type: application/json");

$iconsDir = realpath(__DIR__ . '/../icons/');
$inputDir = realpath(__DIR__ . '/../input/');

// STEP 1: Get .json file names (from filename only, not contents)
$jsonFiles = [];
foreach (glob($iconsDir . '/*/*.json') as $jsonPath) {
    $filename = pathinfo($jsonPath, PATHINFO_FILENAME);
    $jsonFiles[] = [
        'name' => $filename, // just the filename (no extension)
        'path' => str_replace(realpath(__DIR__ . '/..') . '/', '', $jsonPath) // relative path
    ];
}

// STEP 2: Get all .svg filenames (with extension)
$svgFilenames = array_values(array_filter(scandir($inputDir), function ($f) use ($inputDir) {
    return is_file($inputDir . '/' . $f) && str_ends_with(strtolower($f), '.svg');
}));

echo json_encode([
    'json_files' => $jsonFiles,
    'svg_files' => $svgFilenames
]);
