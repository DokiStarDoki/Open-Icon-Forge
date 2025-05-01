<?php
// Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load .env
$env = parse_ini_file(".env");
$replicate_token = $env["REPLICATE_API_TOKEN"] ?? '';

if (!$replicate_token) {
  http_response_code(500);
  echo json_encode(["error" => "Missing API token"]);
  exit;
}

// Read POST input
$data = json_decode(file_get_contents("php://input"), true);
$selected_theme = $data["selected_theme"] ?? '';
$existing_ideas = $data["existing_ideas"] ?? [];

$joined = implode(", ", array_map(fn($idea) => "\"$idea\"", $existing_ideas));

// Prompt to LLM
$prompt = "Generate a new simple icon idea for the theme \"$selected_theme\".
Avoid using any of the following ideas: [$joined]
Return ONLY the icon idea as a short phrase. No formatting or explanation.";

// Send to Replicate (Claude 3.5 Haiku)
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
    "max_tokens" => 100,
    "system_prompt" => ""
  ]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$raw = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);
file_put_contents("../json/replicate-icon-debug.json", $raw ?: $error);

// Error handling
if (!$raw) {
  http_response_code(500);
  echo json_encode(["error" => "cURL failed", "details" => $error]);
  exit;
}

$response = json_decode($raw, true);
$output = $response["output"] ?? null;
$idea = is_array($output) ? trim(implode("", $output)) : trim($output);
$is_new = $idea && !in_array($idea, $existing_ideas);

// Return
echo json_encode([
  "idea" => $idea,
  "is_new" => $is_new
]);
?>
