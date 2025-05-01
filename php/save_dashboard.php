<?php
$data = file_get_contents('php://input');
file_put_contents('../json/dashboard.json', $data);
echo json_encode(['status' => 'success']);
