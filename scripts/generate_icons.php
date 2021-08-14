<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 3) {
    die("Usage: generate_icons.php <configs folder> <rp folder>\n");
}

require "spyc.php";

$configRoot = $argv[1];
$rpFolder = $argv[2];

// Prepare path to spell modes
$spellModelsFolder = "$rpFolder/src/default/assets/magic/models/icons/spells";

// Prepare output folder
$iconsOutputFolder =  "$configRoot/defaults/icons";
if (!file_exists()) {
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

// Map all spell icons
$icons = array();
$spellIcons = array();
$unmappedSpellIcons = array();

$spellConfigFolder = "$configRoot/examples/survival/spells";

function convertIcon($icon) {
    $startJson = strpos($icon, '{');
    if (!$startJson) return $icon;
    $jsonPart = substr($icon, $startJson);
    $json = json_decode($jsonPart, true);
    if (!$json) return $icon;
    if (count($json) != 1 || !isset($json['CustomModelData'])) return $icon;
    $customModelData = $json['CustomModelData'];
    return substr($icon, 0, $startJson - 1) . "{$customModelData}";
}

$spellFolder = new DirectoryIterator($spellConfigFolder);
foreach ($spellFolder as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'yml') continue;
    $fileName = $fileInfo->getFilename();
    $spellKey = basename($fileName, '.yml');
    $filePath = "$spellConfigFolder/$fileName";
    $spellConfig = spyc_load_file($filePath);
    if (!$spellConfig) {
        echo "Could not parse $filePath\n";
        continue;
    }
    if (!isset($spellConfig[$spellKey])) {
        echo "No spell key in $filePath\n";
        continue;
    }
    $spellConfig = $spellConfig[$spellKey];
    if (!isset($spellConfig['icon'])) {
        echo "No icon in $filePath\n";
        continue;
    }
    if (isset($spellMessages[$spellKey])) {
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

        echo "Creating icon $spellKey with $iconItem\n";
        $spellIcons[$iconItem] = $spellKey;
        $icons[$spellKey] = $icon;
    } else {
        echo "Deferring $spellKey\n";
        $unmappedSpellIcons[$spellKey] = $spellConfig;
    }
}

// Check for any uniqueness we need to handle
$mappedSpellIcons = array();
foreach ($unmappedSpellIcons as $spellKey => $unmapped) {
    $iconItem = convertIcon($unmapped['icon']);
    // Check for models
    if (!isset($spellIcons[$iconItem]) && file_exists("$spellModelsFolder/$spellKey.json")) {
        $spellModel = json_decode(file_get_contents("$spellModelsFolder/$spellKey.json"), true);
        if ($spellModel) {
            $texture = $spellModel['textures']['texture'];
            $texture = substr($texture, strlen('magic:icons/spells/'));
            if (isset($icons[$texture])) {
                echo "** Mapping $spellKey to $texture from model\n";
            } else {
                echo "*** $spellKey has unknown texture in its model: $texture\n";
            }
        } else {
            echo "Couldn't parse model json at $spellModelsFolder/$spellKey.json\n";
        }
    }
    if (isset($spellIcons[$iconItem])) {
        $mappedIcon = $spellIcons[$iconItem];
        echo "Mapping $spellKey to icon of $mappedIcon\n";
        $mappedSpellIcons[$spellKey] = $mappedIcon;
    } else {
        echo "* Found unmapped icon: $spellKey as $iconItem\n";
    }
}

ksort($icons);
$iconCount = count($icons);
$overwrite = true;
echo "Writing out $iconCount icons\n";
foreach ($icons as $key => $icon) {
    $icon['type'] = 'spell';
    $iconFilename = "$iconsOutputFolder/$key.yml";
    if (!$overwrite && file_exists($iconFilename)) {
        echo "   Skipping $iconFilename\n";
        continue;
    }
    $icon['glyph'] = '"' . $spellMessages[$key] . '"';
    $iconFile = array();
    $iconFile[$key] = $icon;
    file_put_contents($iconFilename, Spyc::YAMLDump($iconFile, false, 0, true));
    echo "Wrote to $iconFilename\n";
}
