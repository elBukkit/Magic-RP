<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 7) {
    die("Usage: generate_glyphs.php <image folder> <start code> <message key> <glyph path> <output json> <output yml>\n");
}

$srcFolder = $argv[1];
$startCode = hexdec($argv[2]);
$messageKey = $argv[3];
$glyphPath = $argv[4];
$outputFile = $argv[5];
$outputYaml = $argv[6];

$exclude = array();
if (file_exists($outputFile)) {
    $existing = json_decode(file_get_contents($outputFile), true);
    if (!$existing) {
        die("Could not parse $existing\n");
    }
    if (isset($existing['providers'])) {
        $existing = $existing['providers'];
        foreach ($existing as $provider) {
            if (isset($provider['file'])) {
                $exclude[$provider['file']] = true;
            }
            if (isset($provider['chars'])) {
                foreach ($provider['chars'] as $char) {
                    $char = mb_ord($char, 'utf8');
                    $startCode = max($startCode, $char + 1);
                }
            }
        }
        echo "Starting at \\u" . dechex($startCode) . "\n";
    } else {
        $existing = array();
    }
} else {
    $existing = array();
}

$template = file_get_contents(dirname(__FILE__) . '/glyph_template.json');
if (!$template) {
    die("Could not open glyph_template.json\n");
}

$dir = new DirectoryIterator($srcFolder);

$messages = '';
$path = explode('.', $messageKey);
$indent = 0;
foreach ($path as $piece) {
    $messages .= str_repeat(' ', $indent) . $piece . ":\n";
    $indent += 2;
}
$fonts = array();

$currentCode = $startCode;
$imageFiles = array();
foreach ($dir as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'png') continue;
    array_push($imageFiles, $fileInfo->getFilename());
}

sort($imageFiles);
foreach ($imageFiles as $filename) {
    $basename = substr($filename, 0, -4);
    $fileKey = $glyphPath . $basename . '.png';
    if (isset($exclude[$fileKey])) continue;
    // Check for existing


    // Add to font.json
    $hexcode = '\u' . strtoupper(dechex($currentCode));
    echo "Adding $filename as $messageKey.$basename ($hexcode)\n";
    $currentCode++;

    $newTemplate = $template;
    $newTemplate = str_replace('$file', $fileKey, $newTemplate);
    $newTemplate = str_replace('$char', "$hexcode", $newTemplate);
    array_push($fonts, json_decode($newTemplate, true));

    // Add to messages.yml
    $messages .= str_repeat(' ', $indent) . $basename . ": \"$hexcode\"\n";
}

$fonts = array_merge($existing, $fonts);
$fonts = array('providers' => $fonts);

file_put_contents($outputYaml, $messages);
file_put_contents($outputFile, json_encode($fonts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Wrote fonts to $outputFile\n";
echo "Wrote messages to $outputYaml\n";