<?php
// Show all errors (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load environment variables from .env
$env = parse_ini_file(".env");
$replicate_token = $env["REPLICATE_API_TOKEN"] ?? '';

if (!$replicate_token) {
  http_response_code(500);
  echo json_encode(["error" => "Missing API token"]);
  exit;
}

// Read incoming POST data
$data = json_decode(file_get_contents("php://input"), true);
$theme_backlog = $data['theme_backlog'] ?? [];
$themes_used = $data['themes_used'] ?? [];

$all_themes = array_merge($theme_backlog, $themes_used);
$joined = implode(", ", $all_themes);

// Build Claude-friendly prompt
$prompt = "Generate a single, unique, one-word icon theme that has never been commonly used before. The theme should be conceptually rich enough to inspire a cohesive set of icons.
Avoid the following themes: [$joined]
Return ONLY the theme name as a single word with no additional text, explanation, or formatting.
Examples of good responses:
Example 1: Space.
Example 2: Farm.
Example 3: Backyard.
Example 4: Kitchen.
Example 5: Pets.";

// Prepare cURL to Replicate Claude 3.5 Haiku (no polling, Prefer: wait)
$ch = curl_init("https://api.replicate.com/v1/models/anthropic/claude-3.5-haiku/predictions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $replicate_token",
  "Content-Type: application/json",
  "Prefer: wait"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
  "input" => [
    "prompt" => $prompt,
    "max_tokens" => 500,
    "system_prompt" => ""
  ]
]));

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 🔥 disables SSL validation

// Run the request
$init_response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Save raw response for debugging
file_put_contents("../json/replicate-debug.json", $init_response ?: $curl_error);

// Handle request failure
if (!$init_response) {
  http_response_code(500);
  echo json_encode(["error" => "cURL failed", "details" => $curl_error]);
  exit;
}

// Parse API output
$response_data = json_decode($init_response, true);
$output = $response_data["output"] ?? null;

if (!$output) {
  http_response_code(500);
  echo json_encode(["error" => "No output from Replicate", "response" => $response_data]);
  exit;
}

// Clean and extract theme
$theme = is_array($output) ? trim(implode("", $output)) : trim($output);
$is_new = !in_array($theme, $theme_backlog) && !in_array($theme, $themes_used);

// Return result to frontend
echo json_encode([
  "theme" => $theme,
  "is_new" => $is_new
]);
?>
