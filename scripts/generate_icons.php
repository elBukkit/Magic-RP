<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 4) {
    die("Usage: generate_icons.php <configs folder> <rp folder> <example>\n");
}

require "spyc.php";

$configRoot = $argv[1];
$rpFolder = $argv[2];
$example = $argv[3];

// Prepare path to spell modes
$spellModelsFolder = "$rpFolder/src/default/assets/magic/models/icons/spells";
$minecraftModelsFolder = "$rpFolder/src/default/assets/minecraft/models/item";

// Prepare output folder
$iconsOutputFolder =  "$configRoot/examples/$example/icons";
if (!file_exists($iconsOutputFolder)) {
    mkdir($iconsOutputFolder);
}

// Get spell glyph messages
// Anything not in this list does not have a named png
$guiMessagesFilename = "$configRoot/defaults/messages/gui.yml";
$guiMessages = spyc_load_file($guiMessagesFilename);
if (!$guiMessages) {
    die("Could not parse: $guiMessagesFilename");
}

$guiMessages = $guiMessages['gui'];
$iconMessages = $guiMessages['icons'];
$spellMessages = $iconMessages['spells'];

// Map all spell models to their texture
$modelTextures = array();
$modelFiles = new DirectoryIterator($spellModelsFolder);
foreach ($modelFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $fileName = $fileInfo->getFilename();
    $baseModel = basename($fileName, '.json');
    $model = json_decode(file_get_contents($spellModelsFolder . '/' . $fileName), true);
    if (!$model) {
        echo "! Could not parse $fileName";
        continue;
    }

    if (!isset($model['textures']) || !isset($model['textures']['texture'])) {
        echo "! Model has no textures: $fileName";
        continue;
    }
    $texture = $model['textures']['texture'];
    $texture = substr($texture, strlen('magic:icons/spells/'));
    $modelTextures[$baseModel] = $texture;
}

// Find all RP spell icons in use
$customItems = array();
$modelFiles = new DirectoryIterator($minecraftModelsFolder);
foreach ($modelFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'json') continue;
    $fileName = $fileInfo->getFilename();
    $baseModel = basename($fileName, '.json');
    $model = json_decode(file_get_contents($minecraftModelsFolder . '/' . $fileName), true);
    if (!$model) {
        echo "! Could not parse $fileName";
        continue;
    }

    if (!isset($model['overrides'])) {
        echo "! Model has no overrides: $fileName";
        continue;
    }
    $overrides = $model['overrides'];
    foreach ($overrides as $override) {
        if (!isset($override['predicate']) || !isset($override['model'])) continue;
        $model = $override['model'];
        if (strpos($model, 'magic:icons/spells/') !== 0) continue;
        $model = str_replace('magic:icons/spells/', '', $model);
        $predicate = $override['predicate'];
        if (!isset($predicate['custom_model_data'])) continue;
        $customModelData = $predicate['custom_model_data'];
        $modelKey = $baseModel . '{' . $customModelData . '}';
        $customItems[$modelKey] = $model;
    }
}

// Map all spell icons
$spells = array();
$spellItems = array();

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

function convertIcon($icon) {
    $startJson = strpos($icon, '{');
    if (!$startJson) return $icon;
    $jsonPart = substr($icon, $startJson);
    $json = my_json_decode($jsonPart, true);
    if (!$json) return $icon;
    if (count($json) != 1 || !isset($json['CustomModelData'])) return $icon;
    $customModelData = $json['CustomModelData'];
    return substr($icon, 0, $startJson) . '{' . $customModelData . '}';
}

function getBaseIcon($icon) {
    $startJson = strpos($icon, '{');
    if (!$startJson) return $icon;
    $jsonPart = substr($icon, $startJson);
    $json = my_json_decode($jsonPart, true);
    if (!$json) return $icon;
    if (!isset($json['CustomModelData'])) return $icon;
    $customModelData = $json['CustomModelData'];
    return substr($icon, 0, $startJson) . '{' . $customModelData . '}';
}

$allConfigFiles = array();
function gatherFiles($path) {
    global $allConfigFiles;

    $spellFolder = new DirectoryIterator($path);
    foreach ($spellFolder as $fileInfo) {
        if ($fileInfo->isDir()) continue;
        if ($fileInfo->isDot()) continue;
        $extension = $fileInfo->getExtension();
        if ($extension != 'yml') continue;
        $fileName = $fileInfo->getFilename();
        array_push($allConfigFiles, "$path/$fileName");
    }
}

gatherFiles("$configRoot/examples/$example/spells");

foreach ($allConfigFiles as $filePath) {
    $filePieces = explode('/', $filePath);
    $fileName = end($filePieces);
    $spellConfigFile = spyc_load_file($filePath);
    if (!$spellConfigFile) {
        echo "! Could not parse $filePath\n";
        continue;
    }
    foreach ($spellConfigFile as $spellKey => $spellConfig) {
        if (!isset($spellConfig['icon'])) {
            # echo "No icon in $filePath\n";
            continue;
        }

        $iconItem = convertIcon($spellConfig['icon']);
        $icon = array('item' => $iconItem);
        if (isset($spellConfig['icon_disabled'])) {
            $icon['item_disabled'] = convertIcon($spellConfig['icon_disabled']);
        }
        if (isset($spellConfig['legacy_icon'])) {
            $icon['legacy_item'] = $spellConfig['legacy_icon'];
        }
        if (isset($spellConfig['legacy_icon_disabled'])) {
            $icon['legacy_item_disabled'] = $spellConfig['legacy_icon_disabled'];
        }
        if (isset($spellConfig['icon_url'])) {
            $icon['url'] = $spellConfig['icon_url'];
        }
        if (isset($spellConfig['icon_disabled_url'])) {
            $icon['url_disabled'] = $spellConfig['icon_disabled_url'];
        }

        # echo "Found spell $spellKey with icon $iconItem\n";
        $spellItems[$iconItem] = $spellKey;
        $spells[$spellKey] = $icon;
    }
}

// Check for any unused glyphs
foreach ($spellMessages as $key => $spellMessage) {
    if (isset($icons[$key])) continue;

    # echo "! Unused glyph: $key\n";
}

// Create one icon for every model used in this configuration
$usedModels = array();
foreach ($spells as $spellKey => $spellIcon) {
    $baseIcon = getBaseIcon($spellIcon['item']);
    if (!isset($customItems[$baseIcon])) {
        // echo "! Spell $spellKey uses unknown icon: " . $spellIcon['item'] . " ($baseIcon)\n";
        continue;
    }

    $modelKey = $customItems[$baseIcon];
    $iconKey = isset($spellMessages[$spellKey]) ? $spellKey : $modelKey;

    if (isset($usedModels[$iconKey])) {
        $existing = $usedModels[$iconKey];
        if ($iconKey == $spellKey) {
            $spellIcon = array_merge(array(), $existing, $spellIcon);
        } else {
            $spellIcon = array_merge($spellIcon, $existing);
        }
        $usedModels[$iconKey] = $spellIcon;
    } else {
        $usedModels[$iconKey] = $spellIcon;
    }
}

$modelCount = count($usedModels);
$overwrite = true;
echo "Writing out $modelCount icons from RP models used by this example\n";
foreach ($usedModels as $key => $icon) {
    if ($key === 'default') continue;
    $icon['type'] = 'spell';
    $iconFilename = "$iconsOutputFolder/$key.yml";
    if (!$overwrite && file_exists($iconFilename)) {
        # echo "   Skipping $iconFilename\n";
        continue;
    }
    $modelTexture = $modelTextures[$key];
    if (!isset($spellMessages[$modelTexture])) {
        echo "! Un-glyphed texture: $modelTexture\n";
    } else {
        $icon['glyph'] = '"' . $spellMessages[$modelTexture] . '"';
    }
    $iconFile = array();
    $iconFile[$key] = $icon;
    file_put_contents($iconFilename, Spyc::YAMLDump($iconFile, false, 0, true));
    echo "Wrote to $iconFilename\n";
}
