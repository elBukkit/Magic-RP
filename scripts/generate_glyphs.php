<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 3) {
    die("Usage: generate_glyphs.php <image folder> <start code> <message key>\n");
}

$srcFolder = $argv[1];
$startCode = $argv[2];
$messageKey = $argv[3];

$template = file_get_contents(dirname(__FILE__) . '/glyph_template.json');
if (!$template) {
    die("Could not open glyph_template.json");
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

$currentCode = hexdec($startCode);
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
    $hexcode = '\u' . strtoupper(dechex($currentCode));
    echo "Adding $filename as $messageKey.$basename ($hexcode)\n";
    $currentCode++;

    // Add to font.json
    $newTemplate = $template;
    $newTemplate = str_replace('$file', 'magic:icons/spells/' . $basename . '.png', $newTemplate);
    $newTemplate = str_replace('$char', "$hexcode", $newTemplate);
    array_push($fonts, json_decode($newTemplate));

    // Add to messages.yml
    $messages .= str_repeat(' ', $indent) . $basename . ": \"$hexcode\"\n";
}

$fonts = array('providers' => $fonts);

file_put_contents('messages_output.yml', $messages);
file_put_contents('font_output.json', json_encode($fonts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Wrote fonts to font_output.json\n";
echo "Wrote messages to messages_output.yml\n";