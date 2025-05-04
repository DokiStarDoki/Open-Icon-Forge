<?php
header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/../icons');
$baseUrl = 'icons'; // relative to web root
$iconData = [];

function findSVGs($dir, $relative = '') {
    global $iconData, $baseDir, $baseUrl;

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = "$dir/$item";
        $relPath = $relative ? "$relative/$item" : $item;

        if (is_dir($fullPath)) {
            findSVGs($fullPath, $relPath);
        } elseif (strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'svg') {
            $nameOnly = pathinfo($item, PATHINFO_FILENAME);
            $jsonPath = $dir . '/' . $nameOnly . '.json';

            $data = [
                'name' => $nameOnly,
                'file' => "$baseUrl/$relPath",
                'theme' => explode('/', $relPath)[0], // folder = theme fallback
                'tags' => [],
                'short_description' => '',
                'date_created' => ''
            ];

            if (file_exists($jsonPath)) {
                $json = json_decode(file_get_contents($jsonPath), true);
                if (is_array($json)) {
                    $data = array_merge($data, $json);
                    $data['file'] = "$baseUrl/$relPath"; // ensure SVG path still set
                }
            }

            $iconData[] = $data;
        }
    }
}

findSVGs($baseDir);
echo json_encode(['icons' => $iconData]);
