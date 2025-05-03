<?php
$filename = $_GET['file'] ?? '';
if (!$filename) {
  http_response_code(400);
  echo json_encode(["error" => "Missing filename"]);
  exit;
}

$name = pathinfo($filename, PATHINFO_FILENAME);
$svgPath = "../$name.svg";
$baseDir = "../icons/";

$found = false;
foreach (glob($baseDir . "*", GLOB_ONLYDIR) as $themeDir) {
  if (file_exists("$themeDir/$name.json") && file_exists($svgPath)) {
    rename($svgPath, "$themeDir/$name.svg");
    @unlink("../input/$name.png");
    @unlink("../$name.bmp");
    $found = true;
    break;
  }
}

echo json_encode(["moved" => $found]);
