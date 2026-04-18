<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 2) {
    die("Usage: generate_enabled_icons.php <rp folder>\n");
}

$rpFolder = $argv[1];
$itemsFolder = $rpFolder . "/assets/minecraft/items";

$itemFiles = new DirectoryIterator($itemsFolder);
foreach ($itemFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $itemPath = $fileInfo->getPathname();
    $itemFile = json_decode(file_get_contents($itemPath), true);
    if (!isset($itemFile['model']['entries'])) continue;

    $entries = $itemFile['model']['entries'];
    $enabledEntries = array();
    foreach ($entries as $entry) {
        if (!isset($entry['model']['model'])) continue;
        if (strpos($entry['model']['model'], '_disabled') !== false) continue;
        $enabledEntry = $entry;
        $enabledEntry['threshold'] += 1000;
        $enabledEntry['model']['model'] = str_replace('magic:icons/', 'magic:icons_enabled/', $entry['model']['model']);
        $enabledEntries[] = $enabledEntry;
    }
    foreach ($enabledEntries as $enabledEntry) {
        $itemFile['model']['entries'][] = $enabledEntry;
    }
    echo "Modifying $itemPath\n";
    file_put_contents($itemPath, json_encode($itemFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

echo "Done!\n";
