<?php
$svgPaths = [];
$baseDir = "../icons/";

$themes = array_filter(glob($baseDir . '*'), 'is_dir');

foreach ($themes as $themePath) {
  $svgFiles = glob($themePath . '/*.svg');
  foreach ($svgFiles as $svg) {
    $svgPaths[] = substr($svg, 3); // remove "../" from path
  }
}

echo json_encode($svgPaths);
