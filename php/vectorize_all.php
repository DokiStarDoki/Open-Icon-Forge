<?php
$inputDir = "../input/";
$iconBaseDir = "../icons/";
$converted = 0;

$pngs = glob($inputDir . "*.png");

foreach ($pngs as $pngPath) {
  $filename = pathinfo($pngPath, PATHINFO_FILENAME);

  // Step 1: Convert PNG to BMP
  file_get_contents("../icon_to_bmp.php?file=" . urlencode($filename . ".png"));

  // Step 2: Convert BMP to SVG
  file_get_contents("../bmp_to_svg.php?file=" . urlencode($filename . ".bmp"));

  // Step 3: Find matching JSON in icons/*/
  $svgPath = "../" . $filename . ".svg";
  $found = false;
  foreach (glob($iconBaseDir . "*", GLOB_ONLYDIR) as $themeDir) {
    $jsonPath = $themeDir . "/" . $filename . ".json";
    if (file_exists($jsonPath) && file_exists($svgPath)) {
      rename($svgPath, $themeDir . "/" . $filename . ".svg");
      $found = true;
      break;
    }
  }

  if ($found) {
    $converted++;
    @unlink($pngPath);                       // Delete PNG
    @unlink("../" . $filename . ".bmp");     // Delete BMP
  }
}

echo json_encode(["total" => $converted]);
