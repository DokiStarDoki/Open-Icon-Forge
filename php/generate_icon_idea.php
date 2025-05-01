<?php
header('Content-Type: application/json');
// Show all errors (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load environment variables from .env
$env = parse_ini_file("../.env");
$replicate_token = $env["REPLICATE_API_TOKEN"] ?? '';

if (!$replicate_token) {
  http_response_code(500);
  echo json_encode(["error" => "Missing API token"]);
  exit;
}

// Read incoming POST data
$data = json_decode(file_get_contents("php://input"), true);
$selected_theme = $data["selected_theme"] ?? '';
$existing_ideas = $data["existing_ideas"] ?? [];

$joined = implode(", ", array_map(fn($idea) => "\"$idea\"", $existing_ideas));

// Build Claude-friendly prompt
$prompt = "Generate a fresh, distinctive icon concept for the theme \"$selected_theme\".
Avoid using any of the following ideas: [$joined]
Respond with ONLY the icon idea as a brief, descriptive phrase (3-7 words). No additional text.

Your icon concept should:
- Be simple enough to work at small sizes
- Have a recognizable silhouette
- Use minimal detail while remaining identifiable
- Be visually distinct from the excluded concepts

Format as: [Icon Name] – [Brief visual description]";

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

$idea = is_array($output) ? trim(implode("", $output)) : trim($output);
$is_new = $idea && !in_array($idea, $existing_ideas);

// Return
echo json_encode([
  "idea" => $idea,
  "is_new" => $is_new
]);
?>
