<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 4) {
    die("Usage: check_spell_icons.php <configs folder> <rp folder> <example>\n");
}

require "spyc.php";

$configRoot = $argv[1];
$rpFolder = $argv[2];
$example = $argv[3];

// Prepare path to models, to output player skull
$minecraftModelsFolder = "$rpFolder/src/default/assets/minecraft/models/item";

// Prepare icons folder
$iconsFolder =  "$configRoot/examples/$example/icons";
if (!file_exists($iconsFolder)) {
    die("No icons folder found at $iconsFolder\n");
}


function my_json_decode($s, $asArray) {
    $s = str_replace(
        array('"',  "'"),
        array('\"', '"'),
        $s
    );
    $s = preg_replace('/(\w+):/i', '"\1":', $s);
    $s = preg_replace('/:(\w+)/i', ':"\1"', $s);

    return json_decode($s, $asArray);
}

function getItemName($itemKey) {
    $pieces = explode('{', $itemKey, 2);
    $itemKey = $pieces[0];
    $pieces = explode(':', $itemKey, 2);
    $itemKey = $pieces[0];
    return $itemKey;
}

// Update all icon configs, tracking CustomModelData values
$iconFiles = new DirectoryIterator($iconsFolder);
$itemsUsed = array();
foreach ($iconFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'yml') continue;
    $fileName = $fileInfo->getFilename();
    $spellName = basename($fileName, '.yml');
    $iconConfig = Spyc::YAMLLoad($iconsFolder . '/' . $fileName);
    if (!$iconConfig) {
        echo "! Could not parse $fileName";
        continue;
    }
    if (count($iconConfig) != 1) {
        echo "! Unexpected icon config $fileName\n";
        continue;
    }
    $spellKey = array_key_first($iconConfig);
    $iconConfig = $iconConfig[$spellKey];
    if (!isset($iconConfig['type']) || $iconConfig['type'] !== 'spell') {
        echo "Skipping non-spell icon: $fileName\n";
        continue;
    }
    if (!isset($iconConfig['item'])) {
        echo "! Icon has no item, skipping: $fileName\n";
        continue;
    }
    $item = $iconConfig['item'];
    $itemName = getItemName($iconConfig['item']);
    if ($itemName) {
        $itemsUsed[$itemName] = true;
    }
    if (isset($iconConfig['item_disabled'])) {
        $itemName = getItemName($iconConfig['item_disabled']);
        if ($itemName) {
            $itemsUsed[$itemName] = true;
        }
    }
}

$itemsUsed = array_keys($itemsUsed);
sort($itemsUsed);

foreach ($itemsUsed as $itemUsed) {
    echo "In use: $itemUsed\n";
}