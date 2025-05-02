<?php
$dir = "../icons/generated/";
$files = glob($dir . "*.{png,webp}", GLOB_BRACE);

$paths = array_map(function($f) {
  return str_replace("../", "", $f); // Return relative paths
}, $files);

echo json_encode($paths);
?>
