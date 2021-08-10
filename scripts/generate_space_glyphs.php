<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 4) {
    die("Usage: generate_space_glyphs.php <input json> <message key> <output yml>\n");
}

$inputFile = $argv[1];
$messageKey = $argv[2];
$outputYaml = $argv[3];

if (!file_exists($inputFile)) {
    die("Could not find file: $inputFile\n");
}

$fonts = json_decode(file_get_contents($inputFile), true);
if (!$fonts) {
    die("Could not find file: $inputFile\n");
}
if (!isset($fonts['providers'])) {
    die("Could not find providers in file: $inputFile\n");
}

$messages = '';
$path = explode('.', $messageKey);
$indent = 0;
foreach ($path as $piece) {
    $messages .= str_repeat(' ', $indent) . $piece . ":\n";
    $indent += 2;
}

$providers = $fonts['providers'];
$nosplit = array();
foreach ($providers as $provider) {
    if (!isset($provider['height'])) {
        continue;
    }
    if (isset($provider['file']) && strpos($provider['file'], 'nosplit')) {
        array_push($nosplit, $provider);
        continue;
    }
    $height = $provider['height'];
    if (isset($provider['chars'])) {
        foreach ($provider['chars'] as $char) {
            $char = mb_ord($char, 'utf8');
            $hexcode = '\u' . strtoupper(dechex($char));
            $messages .= str_repeat(' ', $indent) . $height . ": \"$hexcode\"\n";
        }
    }
}

$messages .= str_repeat(' ', $indent) . "nosplit:\n";
$indent += 2;

foreach ($nosplit as $provider) {
    if (!isset($provider['height'])) {
        continue;
    }
    $height = $provider['height'];
    if (isset($provider['chars'])) {
        foreach ($provider['chars'] as $char) {
            $char = mb_ord($char, 'utf8');
            $hexcode = '\u' . strtoupper(dechex($char));
            $messages .= str_repeat(' ', $indent) . $height . ": \"$hexcode\"\n";
        }
    }
}

file_put_contents($outputYaml, $messages);
echo "Wrote messages to $outputYaml\n";