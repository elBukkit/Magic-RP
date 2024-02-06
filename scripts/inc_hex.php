<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 2) {
    die("Usage: inc_hex.php <font.json>\n");
}

$fontFilename = $argv[1];
$fontFile = file_get_contents($fontFilename);

$lines = explode("\n", $fontFile);
$newLines = array();
foreach ($lines as $line) {
    if (strpos($line, '\u') === FALSE) {
        $newLines[] = $line;
        continue;
    }
    $pieces = explode('"', $line);
    $hex = $pieces[1];
    if (substr($hex, 0, 2) !== '\u') die("Uh? $hex\n");

    $hex = substr($hex, 2);
    $value = hexdec($hex);
    $value++;
    $hex = dechex($value);
    $pieces[1] = '\u' . strtoupper($hex);
    $newLines[] = implode('"', $pieces);
}

$contents = implode("\n", $newLines) . "\n";
file_put_contents($fontFilename, $contents);
