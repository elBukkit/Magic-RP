#!/usr/bin/php
<?php

if (count($argv) < 3) {
    die("Usage: merge_fonts.php <from folder> <to json>\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$fromFolder = $argv[1];
if (!file_exists($fromFolder)) {
    die("Folder not found: $fromFolder\n");
}

$toFilename = $argv[2];
if (file_exists($toFilename)) {
    $existing = json_decode(file_get_contents($toFilename), true);
    if (!$existing) {
        die("Could not parse file: $toFilename\n");
    }
} else {
    $existing = array();
}
if (!isset($existing['providers'])) {
    $existing['providers'] = array();
}

function mergeFiles($fromFolder) {
    global $existing;
    global $toFilename;
    foreach (new DirectoryIterator($fromFolder) as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        if ($fileInfo->isDir()) continue;
        if ($fileInfo->getExtension() != 'json') continue;
        $fileName = $fileInfo->getFilename();
        $contents = json_decode(file_get_contents($fileInfo->getRealPath()), true);
        if (!$contents) {
            echo "Could not parse: $fileName\n";
            continue;
        }
        if (!isset($contents['providers'])) {
            echo "Has no providers list: $fileName\n";
            continue;
        }
        $existing['providers'] = array_merge($existing['providers'], $contents['providers']);
        $providerCount = count($contents['providers']);
        echo "   Merged $providerCount providers from $fileName into $toFilename\n";
    }
}

echo "  Merging $fromFolder into $toFilename\n";
mergeFiles($fromFolder);

file_put_contents($toFilename, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
