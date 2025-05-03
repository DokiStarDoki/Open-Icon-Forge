<?php
header('Content-Type: application/json');

$iconDir = '../icons/';
$icons = [];

function scanIconMetadata($dir) {
    $results = [];

    $folders = scandir($dir);
    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..') continue;

        $path = $dir . $folder . '/';
        if (!is_dir($path)) continue;

        foreach (glob($path . '*.json') as $file) {
            $json = json_decode(file_get_contents($file), true);
            if (isset($json['name']) && isset($json['theme'])) {
                $results[] = [
                    'name' => $json['name'],
                    'theme' => $json['theme']
                ];
            }
        }
    }

    return $results;
}

$icons = scanIconMetadata($iconDir);
echo json_encode($icons);
