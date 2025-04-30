<?php
$input = file_get_contents('php://input');
if (!$input) {
  http_response_code(400);
  echo "No input data.";
  exit;
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo "Invalid JSON input.";
  exit;
}

$file = 'metadata.json';
if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
  http_response_code(200);
  echo "Metadata saved.";
} else {
  http_response_code(500);
  echo "Failed to save metadata.";
}
?>
