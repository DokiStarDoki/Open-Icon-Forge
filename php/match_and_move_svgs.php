<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$inputDir = realpath(__DIR__ . '/../input/');
$iconsBaseDir = realpath(__DIR__ . '/../icons/');
$moved = [];
$skipped = [];

function sanitize($str) {
    return strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $str)));
}

function findJsonFiles($baseDir) {
    $matches = [];

    foreach (glob($baseDir . '/*/*.json') as $jsonFile) {
        $json = json_decode(file_get_contents($jsonFile), true);
        if (!isset($json['name']) || !isset($json['theme'])) continue;

        $matches[] = [
            'name' => $json['name'],
            'theme' => $json['theme'],
            'jsonPath' => $jsonFile
        ];
    }

    return $matches;
}

$jsonEntries = findJsonFiles($iconsBaseDir);

foreach ($jsonEntries as $entry) {
    $filename = sanitize($entry['name']) . '.svg';
    $sourcePath = $inputDir . DIRECTORY_SEPARATOR . $filename;

    if (!file_exists($sourcePath)) {
        $skipped[] = $filename;
        continue;
    }

    $targetFolder = dirname($entry['jsonPath']);
    $targetPath = $targetFolder . DIRECTORY_SEPARATOR . $filename;

    if (rename($sourcePath, $targetPath)) {
        $moved[] = [
            'file' => $filename,
            'to' => str_replace(realpath(__DIR__ . '/..') . '/', '', $targetPath)
        ];
    } else {
        $skipped[] = $filename;
    }
}

echo json_encode([
    "moved" => $moved,
    "skipped" => $skipped
], JSON_PRETTY_PRINT);
