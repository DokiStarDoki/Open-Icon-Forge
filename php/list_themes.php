<?php
header('Content-Type: application/json');

$iconsDir = realpath(__DIR__ . '/../icons');
$ignored = ['generated', 'temp', 'vectorized'];

if (!$iconsDir || !is_dir($iconsDir)) {
  echo json_encode([]);
  exit;
}

$themes = array_values(array_filter(
  scandir($iconsDir),
  function ($item) use ($iconsDir, $ignored) {
    return $item[0] !== '.' &&
           is_dir("$iconsDir/$item") &&
           !in_array($item, $ignored);
  }
));

sort($themes);
echo json_encode($themes);
