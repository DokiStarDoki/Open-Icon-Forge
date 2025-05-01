<?php
// Start output buffering
ob_start();

// Enforce JSON output + error reporting
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure only POST is accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  ob_end_clean();
  echo json_encode(["error" => "Method Not Allowed. POST required."]);
  exit;
}

// Log input for debugging
$input_raw = file_get_contents("php://input");
file_put_contents("../json/request-log.json", $input_raw); // Save input data to check incoming payload

// Load .env
$env_path = realpath(__DIR__ . "/../.env");
if (!$env_path || !file_exists($env_path)) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "Missing or unreadable .env file"]);
  exit;
}

$env = parse_ini_file($env_path);
$replicate_token = $env["REPLICATE_API_TOKEN"] ?? '';

if (!$replicate_token) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "Missing API token in .env"]);
  exit;
}

// Decode the incoming JSON input
$data = json_decode($input_raw, true);
$theme = $data["theme"] ?? '';
$idea = $data["idea"] ?? '';

if (!$theme || !$idea) {
  http_response_code(400);
  ob_end_clean();
  echo json_encode(["error" => "Missing theme or idea"]);
  exit;
}

// Generate prompt and file slug
$prompt = "black and white icon, line art only, white background, no color. simple. concept: $idea. Theme: $theme";
$slug = strtolower(preg_replace("/[^a-z0-9]+/", "-", $idea));
$output_path = "../icons/generated/$slug.webp";

// Prepare payload for Replicate API
$payload = json_encode([
  "input" => [
    "prompt" => $prompt,
    "go_fast" => true,
    "megapixels" => "1",
    "num_outputs" => 1,
    "aspect_ratio" => "1:1",
    "output_format" => "webp",
    "output_quality" => 80,
    "num_inference_steps" => 4
  ]
]);

// Log payload
file_put_contents("../json/payload-log.json", $payload);

// Set up cURL for Replicate API request
$ch = curl_init("https://api.replicate.com/v1/models/black-forest-labs/flux-schnell/predictions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $replicate_token",
  "Content-Type: application/json",
  "Prefer: wait"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL for local use

$response_raw = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Log raw response
file_put_contents("../json/generate-icon-debug.json", $response_raw ?: $curl_error);

// Handle cURL failure
if (!$response_raw) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "cURL failed", "details" => $curl_error]);
  exit;
}

$response_data = json_decode($response_raw, true);
$output_urls = $response_data["output"] ?? [];

if (!is_array($output_urls) || count($output_urls) === 0) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "Missing output", "response" => $response_data]);
  exit;
}

$image_url = $output_urls[0];
$ch = curl_init($image_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Local testing only
$image_data = curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if (!$image_data) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "Failed to download image via cURL", "url" => $image_url, "details" => $curl_err]);
  exit;
}

if (!$image_data) {
  http_response_code(500);
  ob_end_clean();
  echo json_encode(["error" => "Failed to download image", "url" => $image_url]);
  exit;
}

// Save image to the specified path
file_put_contents($output_path, $image_data);

// ✅ All good — clean buffer and return JSON
ob_end_clean();
echo json_encode([
  "png_path" => "../icons/generated/$slug.webp",
  "quality_check_passed" => false
]);
?>
