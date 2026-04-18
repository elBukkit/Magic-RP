<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 2) {
    die("Usage: generate_enabled_icons.php <rp folder>\n");
}

$rpFolder = $argv[1];
$iconFolder = $rpFolder . "/assets/magic/models/icons";
$enabledFolder = $rpFolder . "/assets/magic/models/icons_enabled";

// Prepare output folder
if (!file_exists($enabledFolder)) {
    mkdir($enabledFolder);
}

// Create copies of all files
$modelFolders = new DirectoryIterator($iconFolder);
foreach ($modelFolders as $folderInfo) {
    echo $folderInfo->getFilename() . "\n";
    if (!$folderInfo->isDir()) continue;
    if ($folderInfo->isDot()) continue;
    $folderName = $folderInfo->getFilename();
    $enabledIconFolder = $enabledFolder . '/' . $folderName;
    if (!file_exists($enabledIconFolder)) {
        mkdir($enabledIconFolder);
    }

    $modelFiles = new DirectoryIterator($folderInfo->getPathname());
    foreach ($modelFiles as $fileInfo) {
        if ($fileInfo->isDir()) continue;
        if ($fileInfo->isDot()) continue;
        $extension = $fileInfo->getExtension();
        if ($extension != 'json') continue;
        $iconPath = $fileInfo->getPathname();
        $iconFilename = $fileInfo->getFilename();
        $enabledIconFilename = $enabledIconFolder . '/' . $iconFilename;

        echo "Creating enabled icon $enabledIconFilename from $iconFilename\n";
        $fileContents = file_get_contents($iconPath);
        $fileContents = str_replace("\"parent\" : \"magic:base/icon_full\",", "\"parent\": \"magic:base/icon_full_enabled\",", $fileContents);
        $fileContents = str_replace("\"parent\" : \"magic:base/icon\",", "\"parent\": \"magic:base/icon_enabled\",", $fileContents);
        file_put_contents($enabledIconFilename, $fileContents);
    }
}

echo "Done!\n";
