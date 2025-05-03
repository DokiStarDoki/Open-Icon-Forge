<?php
$bmpFile = urldecode($_GET['file']);
$outputSvg = str_replace('.bmp', '.svg', $bmpFile);
$bmpPath = "input/$bmpFile";
$svgPath = "input/$outputSvg";

if (!file_exists($bmpPath)) {
    echo json_encode(["error" => "BMP not found at $bmpPath"]);
    exit;
}

$command = "potrace \"$bmpPath\" -s -o \"$svgPath\"";
exec($command, $output, $code);

if ($code !== 0 || !file_exists($svgPath)) {
    echo json_encode([
        "error" => "Potrace failed or SVG not created.",
        "command" => $command,
        "code" => $code
    ]);
    exit;
}

echo json_encode(["success" => true, "svg" => $svgPath]);
