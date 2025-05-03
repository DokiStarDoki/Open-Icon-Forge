<?php
$file = urldecode($_GET['file']);
$svgPath = "input/" . $file;

if (!file_exists($svgPath)) {
    echo json_encode(["error" => "SVG not found at $svgPath"]);
    exit;
}

// Load metadata
$meta = json_decode(file_get_contents("input/input.json"), true);
$baseName = pathinfo($file, PATHINFO_FILENAME);

file_put_contents("php://stderr", "Looking for metadata for $baseName\n");


$entry = null;
foreach ($meta as $item) {
    if (pathinfo($item['name'], PATHINFO_FILENAME) === $baseName) {
        $entry = $item;
        break;
    }
}

if (!$entry || !isset($entry['theme'])) {
    echo json_encode(["error" => "Metadata entry not found for $baseName"]);
    exit;
}

$themeFolder = "icons/" . $entry['theme'];
if (!is_dir($themeFolder)) {
    mkdir($themeFolder, 0777, true);
}

// Move SVG
$destSvg = "$themeFolder/$file";
rename($svgPath, $destSvg);

// Save metadata
file_put_contents("$themeFolder/{$baseName}.json", json_encode($entry, JSON_PRETTY_PRINT));

echo json_encode(["success" => true, "moved_to" => $destSvg]);
