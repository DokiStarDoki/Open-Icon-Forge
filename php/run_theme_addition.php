<?php
header('Content-Type: application/json');

$iconsDir = realpath(__DIR__ . '/../icons');
$inputFile = realpath(__DIR__ . '/../input/theme.json');
$ignored = ['generated', 'temp', 'vectorized'];

if (!$iconsDir || !is_dir($iconsDir)) {
  echo json_encode([
    'error' => 'Icons folder not found',
    'added' => [],
    'allThemes' => []
  ]);
  exit;
}

// Load current themes
$existingThemes = array_values(array_filter(
  scandir($iconsDir),
  function ($item) use ($iconsDir, $ignored) {
    return $item[0] !== '.' &&
           is_dir("$iconsDir/$item") &&
           !in_array($item, $ignored);
  }
));

sort($existingThemes);

// Load input themes
if (!file_exists($inputFile)) {
  echo json_encode([
    'error' => 'theme.json not found',
    'added' => [],
    'allThemes' => $existingThemes
  ]);
  exit;
}

$input = json_decode(file_get_contents($inputFile), true);
$inputThemes = $input['themes'] ?? [];
$added = [];
$remaining = [];

foreach ($inputThemes as $theme) {
  if (!in_array($theme, $existingThemes)) {
    $themePath = "$iconsDir/$theme";
    if (!file_exists($themePath)) {
      mkdir($themePath, 0777, true);
      $added[] = $theme;
      $existingThemes[] = $theme;
    }
  }
  // In both cases (added or already existed), do NOT include it in remaining list
}

// Rewrite theme.json with remaining themes (those not added or already present)
file_put_contents($inputFile, json_encode(['themes' => $remaining], JSON_PRETTY_PRINT));

sort($existingThemes);

echo json_encode([
  'added' => $added,
  'allThemes' => $existingThemes
]);
