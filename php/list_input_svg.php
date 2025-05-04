<?php
header("Content-Type: application/json");

$inputDir = __DIR__ . '/../input/';
$files = scandir($inputDir);

$svgs = array_values(array_filter($files, function ($file) use ($inputDir) {
    return is_file($inputDir . $file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg';
}));

echo json_encode(array_map(function ($file) {
    return "input/" . $file;
}, $svgs));
