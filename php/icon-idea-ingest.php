<?php
header('Content-Type: application/json');

$inputFile = realpath(__DIR__ . '/../input/icons.json');
$iconsDir = realpath(__DIR__ . '/../icons');
$ignored = ['generated', 'temp', 'vectorized'];
$date = date('Y-m-d');

if (!file_exists($inputFile)) {
  echo json_encode(['error' => 'icons.json not found']);
  exit;
}

$iconData = json_decode(file_get_contents($inputFile), true);
if (!is_array($iconData)) {
  echo json_encode(['error' => 'Invalid icons.json format']);
  exit;
}

$created = [];
$remaining = [];

foreach ($iconData as $icon) {
  if (!isset($icon['name']) || !isset($icon['theme'])) {
    continue;
  }

  $theme = $icon['theme'];
  $name = $icon['name'];
  $safeName = strtolower(str_replace(' ', '-', $name));
  $themePath = "$iconsDir/$theme";

  if (in_array($theme, $ignored)) continue;

  if (!file_exists($themePath)) {
    mkdir($themePath, 0777, true);
  }

  $jsonPath = "$themePath/$safeName.json";
  if (!file_exists($jsonPath)) {
    $icon['date_created'] = $icon['date_created'] ?? $date;
    file_put_contents($jsonPath, json_encode($icon, JSON_PRETTY_PRINT));
    $created[] = $name;
  } else {
    // Already exists, skip
  }
}

// Clear input after processing
file_put_contents($inputFile, json_encode($remaining, JSON_PRETTY_PRINT));

echo json_encode(['created' => $created]);
