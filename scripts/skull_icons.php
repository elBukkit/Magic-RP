<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 4) {
    die("Usage: skull_icons.php <configs folder> <rp folder> <example>\n");
}

require "spyc.php";

$configRoot = $argv[1];
$rpFolder = $argv[2];
$example = $argv[3];

// Prepare path to models, to output player skull
$minecraftModelsFolder = "$rpFolder/src/default/assets/minecraft/models/item";
$vanillaModelsFolder = "$rpFolder/src/vanilla/assets/minecraft/models/item";

// Load player_head file
$headFilename = "$minecraftModelsFolder/player_head.json";
if (!file_exists($headFilename)) {
    die("Could not find: $headFilename\n");
}
$playerHead = file_get_contents($headFilename) . "\n";
$playerHead = json_decode($playerHead, true, 512, JSON_THROW_ON_ERROR);
if (!$playerHead) {
    die("Could not load: $headFilename\n");
}

// Prepare output folder
$iconsOutputFolder =  "$configRoot/examples/$example/icons";
if (!file_exists($iconsOutputFolder)) {
    die("No icons folder found at $iconsOutputFolder\n");
}

// Find next customModelData value to use, track existing
$spells = array();
$disabledSpells = array();
$customModelData = 18000;

$overrides = $playerHead['overrides'];
foreach ($overrides as $override) {
    $predicate = $override['predicate'];
    $dataValue = $predicate['custom_model_data'];
    $customModelData = max($customModelData, $dataValue);

    $model = $override['model'];
    $pieces = explode('/', $model);
    $spell = $pieces[count($pieces) - 1];
    if (strpos($model, '/spells/') !== FALSE) {
        $spells[$spell] = $dataValue;
    } else if (strpos($model, '/spells_disabled/') !== FALSE) {
        $disabledSpells[$spell] = $dataValue;
    } else {
        echo "Skipping unknown model $model for CMD $customModelData\n";
    }
}
$customModelData++;

$modelCache = array();

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

function loadItemModel($itemKey) {
    $pieces = explode('{', $itemKey, 2);
    if (count($pieces) != 2) {
        echo ("Unknown item icon type: $itemKey\n");
        return null;
    }
    $maybeJson = $pieces[1];
    if (strpos($maybeJson, ':') !== FALSE) {
        $data = my_json_decode('{' . $pieces[1], true);
    } else {
        $data = substr($pieces[1], 0, -1);
        $data = array('CustomModelData' => $data);
    }
    if (!$data) {
        echo ("Could not parse model data from $itemKey\n");
        return null;
    }
    if (!isset($data['CustomModelData'])) {
        echo ("Model data missing CustomModelData in $itemKey\n");
        return null;
    }
    $data = intval($data['CustomModelData']);
    return loadModel($pieces[0], $data);
}

function loadModel($item, $data) {
    global $modelCache;
    global $minecraftModelsFolder;
    global $vanillaModelsFolder;

    if (!isset($modelCache[$item])) {
        $filename = "$minecraftModelsFolder/$item.json";
        if (!file_exists($filename)) {
            $filename = "$vanillaModelsFolder/$item.json";
            if (!file_exists($filename)) {
                die("Can't load model file: $filename\n");
            }
        }
        $modelCache[$item] = json_decode(file_get_contents($filename), true);
    }

    $model = $modelCache[$item];
    if (!isset($model['overrides'])) {
        die ("Model has no overrides: $item\n");
    }

    foreach ($model['overrides'] as $override) {
        if (isset($override['predicate']) && isset($override['predicate']['custom_model_data']) && $override['predicate']['custom_model_data'] == $data) {
            return $override['model'];
        }
    }

    echo("Could not find CMD value $data in model $item\n");
    return null;
}

// Update all icon configs, tracking CustomModelData values
$iconFiles = new DirectoryIterator($iconsOutputFolder);
foreach ($iconFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'yml') continue;
    $fileName = $fileInfo->getFilename();
    $spellName = basename($fileName, '.yml');
    $iconConfig = Spyc::YAMLLoad($iconsOutputFolder . '/' . $fileName);
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
    if (!isset($iconConfig['type']) || ($iconConfig['type'] !== 'spell' && $iconConfig['type'] !== 'brush')) {
        echo "Skipping non-spell/brush icon: $fileName\n";
        continue;
    }
    if (!isset($iconConfig['item'])) {
        echo "! Icon has no item, skipping: $fileName\n";
        continue;
    }
    if (!isset($iconConfig['url'])) {
        echo "Icon has no skull URL, skipping: $fileName\n";
        continue;
    }
    $url = $iconConfig['url'];
    $item = $iconConfig['item'];
    if (isset($spells[$spellName])) {
        echo "Skipping $fileName, already have spell for $spellName\n";
        continue;
    }
    if (strpos($item, 'skull') !== FALSE) {
        echo "Skipping $fileName, is already using a skull\n";
        continue;
    }
    $newModel = loadItemModel($iconConfig['item']);
    if (!$newModel) {
        echo "Skipping $spellName\n";
        continue;
    }
    echo "Assigning $customModelData to spell $spellName\n";
    $iconConfig['vanilla_item'] = $iconConfig['item'];
    $iconConfig['item'] = "skull:" . $url . '{' . $customModelData . '}';
    $playerHead['overrides'][] = array(
        'predicate' => array('custom_model_data' => $customModelData),
        'model' => $newModel
    );
    $customModelData++;

    if (isset($iconConfig['item_disabled'])) {
        $disabledModel = loadItemModel($iconConfig['item_disabled']);
        if ($disabledModel) {
            $iconConfig['vanilla_item_disabled'] = $iconConfig['item_disabled'];
            $iconConfig['item_disabled'] = "skull:" . $url . '{' . $customModelData . '}';
            echo "Assigning $customModelData to disabled spell $spellName\n";
            $playerHead['overrides'][] = array(
                'predicate' => array('custom_model_data' => $customModelData),
                'model' => $disabledModel
            );

            $customModelData++;
        }
    }
    $iconOutput = array();
    $iconOutput[$spellKey] = $iconConfig;
    $fileName = $fileInfo->getPathname();
    file_put_contents($fileName, Spyc::YAMLDump($iconOutput, false, 0, true));
    echo "Wrote to $fileName\n";
}

// Write out skull
file_put_contents($headFilename, json_encode($playerHead, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Wrote to $headFilename\n";
