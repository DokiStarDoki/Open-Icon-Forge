<?php
$dir = "../input/";
$files = array_filter(scandir($dir), function($file) {
  return preg_match('/\.png$/i', $file);
});
echo json_encode(array_values($files));
