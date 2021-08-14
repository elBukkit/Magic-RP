<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 2) {
    die("Usage: check_models.php <model folder>\n");
}

$srcFolder = $argv[1];

$dir = new DirectoryIterator($srcFolder);

$modelFiles = array();
foreach ($dir as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $fileName = $fileInfo->getFilename();
    $modelFile = json_decode(file_get_contents($srcFolder . '/' . $fileName), true);
    if (!$modelFile) {
        echo "! Couldn't parse " . $fileName . "\n";
        continue;
    }
    if (!isset($modelFile['textures']['texture'])) {
        echo "! No textures in " . $fileName . "\n";
        continue;
    }

    $fileKey = basename($fileName, '.json');
    $texture = $modelFile['textures']['texture'];
    $texturePath = explode('/', $texture);
    $key = $texturePath[count($texturePath) - 1];
    if ($key !== $fileKey) {
        echo "Model points to different texture: $fileKey => $key\n";
    }
}