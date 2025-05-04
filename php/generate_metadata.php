<?php
header('Content-Type: application/json');

$baseDir = realpath(__DIR__ . '/../icons');
$outputFile = realpath(__DIR__ . '/../json') . '/metadata.json';
$ignored = ['generated', 'temp', 'vectorized'];
$metadata = [];

if (!is_dir($baseDir)) {
  echo json_encode(['error' => 'Icons directory not found'], JSON_UNESCAPED_SLASHES);
  exit;
}

$themeFolders = scandir($baseDir);

foreach ($themeFolders as $theme) {
  if ($theme === '.' || $theme === '..' || in_array($theme, $ignored)) {
    continue;
  }

  $themePath = $baseDir . '/' . $theme;
  if (!is_dir($themePath)) continue;

  $files = scandir($themePath);
  $svgs = [];
  $jsons = [];

  foreach ($files as $file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $name = pathinfo($file, PATHINFO_FILENAME);

    if ($ext === 'svg') {
      $svgs[$name] = "$theme/$file";
    } elseif ($ext === 'json') {
      $jsons[$name] = "$themePath/$file";
    }
  }

  foreach ($jsons as $name => $jsonPath) {
    if (isset($svgs[$name])) {
      $jsonData = json_decode(file_get_contents($jsonPath), true);
      if (!$jsonData || !isset($jsonData['name'])) continue;

      $metadata[] = [
        'name' => $jsonData['name'],
        'theme' => $jsonData['theme'] ?? $theme,
        'tags' => $jsonData['tags'] ?? [],
        'short_description' => $jsonData['short_description'] ?? '',
        'file' => "icons/" . $svgs[$name],
        'json_path' => "icons/$theme/$name.json"
      ];
    }
  }
}

if (!is_dir(dirname($outputFile))) {
  mkdir(dirname($outputFile), 0777, true);
}

file_put_contents($outputFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode([
  'success' => true,
  'count' => count($metadata),
  'output' => 'json/metadata.json'
], JSON_UNESCAPED_SLASHES);
