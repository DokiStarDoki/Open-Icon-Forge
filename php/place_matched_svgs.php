<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

$rootDir = realpath(__DIR__ . '/../');
$inputDir = $rootDir . '/input/';

$body = json_decode(file_get_contents("php://input"), true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input format.']);
    exit;
}

$moved = [];
$failed = [];

foreach ($body as $pair) {
    $svgFile = $pair['svg'] ?? null;
    $jsonPath = $pair['json'] ?? null;

    if (!$svgFile || !$jsonPath) {
        $failed[] = ['svg' => $svgFile, 'reason' => 'Missing SVG or JSON path'];
        continue;
    }

    $srcPath = $inputDir . $svgFile;

    $svgPathRaw = preg_replace('/\.json$/', '.svg', $jsonPath);

// Determine if jsonPath is absolute
if (preg_match('/^[A-Za-z]:\\\\/', $svgPathRaw)) {
    // Already an absolute path (Windows-style)
    $destPath = $svgPathRaw;
} else {
    // Relative path — prepend project root
    $destPath = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $svgPathRaw);
}


    // Ensure the destination directory exists
    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0777, true)) {
            $failed[] = ['svg' => $svgFile, 'reason' => 'Failed to create destination directory', 'dest' => $destDir];
            continue;
        }
    }

    // Attempt the move
    if (!file_exists($srcPath)) {
        $failed[] = ['svg' => $svgFile, 'reason' => 'Source SVG not found', 'src' => $srcPath];
        continue;
    }

    if (!rename($srcPath, $destPath)) {
        $failed[] = ['svg' => $svgFile, 'reason' => 'Failed to move file', 'src' => $srcPath, 'dest' => $destPath];
        continue;
    }

    $moved[] = ['from' => $srcPath, 'to' => $destPath];
}

echo json_encode([
    'success' => true,
    'moved' => $moved,
    'failed' => $failed
]);
