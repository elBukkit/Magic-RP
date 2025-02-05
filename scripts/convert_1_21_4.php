<?php

if (count($argv) < 3) {
    die("Usage: convert_1_21_4.php <input folder> <output folder>\n");
}

$inputFolder = $argv[1];
$outputFolder = $argv[2];

$inputFiles = new DirectoryIterator($inputFolder);
$itemsUsed = array();
foreach ($inputFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $inputFilename = $fileInfo->getPathname();
    $outputFilename = $outputFolder . '/' . $fileInfo->getFilename();
    $inputModelName = pathinfo($inputFilename, PATHINFO_FILENAME);
    $inputFile = json_decode(file_get_contents($inputFilename), true);
    $entries = array();

    if (!isset($inputFile['overrides'])) {
        die("$inputFilename doesn't have any overrides\n");
    }

    foreach ($inputFile['overrides'] as $override) {
        if (!isset($override['model']) || !isset($override['predicate']['custom_model_data'])) {
            continue;
        }
        $model = $override['model'];
        $cmd = $override['predicate']['custom_model_data'];
        $entries[] = array(
            'threshold' => $cmd,
            'model' => array(
                'type' => 'model',
                'model' => $model
            )
        );
    }

    $outputFile = array('model' => array(
        'type' => 'range_dispatch',
        'property' => 'custom_model_data',
        'entries' => $entries,
        'fallback' => array(
            'type' => 'model',
            'model' => 'item/' . $inputModelName
        )
    ));

    file_put_contents($outputFilename, json_encode($outputFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Converted input file $inputFilename to $outputFilename\n";
}