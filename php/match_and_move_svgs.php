<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

$iconsDir = __DIR__ . '/../icons/';
$inputDir = __DIR__ . '/../input/';
$moved = [];
$skipped = [];

function sanitize($str) {
    return strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $str)));
}

function moveSvgIfExists($themePath, $jsonPath, $inputDir) {
    $json = json_decode(file_get_contents($jsonPath), true);
    if (!isset($json['name'], $json['theme'])) return null;

    $theme = sanitize($json['theme']);
    $name = sanitize($json['name']);
    $svgFilename = "$name.svg";
    $inputSvgPath = $inputDir . $svgFilename;
    $destSvgPath = "$themePath/$svgFilename";

    if (file_exists($inputSvgPath)) {
        rename($inputSvgPath, $destSvgPath);
        return ["name" => $name, "theme" => $theme, "moved_to" => $destSvgPath];
    } else {
        return ["name" => $name, "theme" => $theme, "skipped" => true];
    }
}

$themes = scandir($iconsDir);
foreach ($themes as $themeFolder) {
    if (in_array($themeFolder, ['.', '..'])) continue;

    $themePath = $iconsDir . $themeFolder;
    if (!is_dir($themePath)) continue;

    $files = glob("$themePath/*.json");
    foreach ($files as $jsonPath) {
        $result = moveSvgIfExists($themePath, $jsonPath, $inputDir);
        if ($result) {
            if (isset($result['skipped'])) $skipped[] = $result;
            else $moved[] = $result;
        }
    }
}

echo json_encode([
    "moved" => $moved,
    "skipped" => $skipped
], JSON_PRETTY_PRINT);
