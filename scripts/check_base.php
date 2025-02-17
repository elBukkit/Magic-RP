<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 3) {
    die("Usage: check_base.php <items folder> <mc items folder>\n");
}

$srcFolder = $argv[1];
$mcFolder = $argv[2];
$dir = new DirectoryIterator($srcFolder);

$modelFiles = array();
foreach ($dir as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $fileName = $fileInfo->getFilename();
    $itemFilename = $srcFolder . '/' . $fileName;
    $itemFile = json_decode(file_get_contents($itemFilename), true);
    if (!$itemFile) {
        echo "! Couldn't parse " . $fileName . "\n";
        continue;
    }
    if (!isset($itemFile['model']['fallback'])) {
        echo "! No model fallback in " . $fileName . "\n";
        continue;
    }
    $fallback = $itemFile['model']['fallback'];
    $mcItemFilename = $mcFolder . '/' . $fileName;
    if (!file_exists($mcItemFilename)) {
        echo "! no mc file: $mcItemFilename\n";
        continue;
    }

    $mcItemFile = json_decode(file_get_contents($mcItemFilename), true);
    if (!$mcItemFile) {
        echo "! Couldn't parse mc file " . $mcItemFilename . "\n";
        continue;
    }

    if (!isset($mcItemFile['model']['model'])) {
        echo "! No model in " . $mcItemFilename . "\n";
        continue;
    }

    $mcItemModel = $mcItemFile['model'];
    if ($mcItemModel != $fallback) {
        $fallbackString = json_encode($fallback);
        $mcItemModelString = json_encode($mcItemModel);
        echo "CHANGE FALLBACK in $fileName from $fallbackString to $mcItemModelString\n";

        $itemFile['model']['fallback'] = $mcItemModel;
        file_put_contents($itemFilename, json_encode($itemFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}