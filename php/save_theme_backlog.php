<?php
$input = file_get_contents('php://input');
if ($input === false) {
  http_response_code(400);
  echo json_encode(['error' => 'No input received']);
  exit;
}

$data = json_decode($input, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

file_put_contents('../theme-backlog.json', json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(['status' => 'ok']);
