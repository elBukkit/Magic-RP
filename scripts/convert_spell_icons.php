<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 3) {
    die("Usage: convert_spell_icons.php <configs folder> <example>\n");
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

require "spyc.php";

$configRoot = $argv[1];
$example = $argv[2];

$spellFolder = "$configRoot/examples/$example/spells";
$iconsFolder = "$configRoot/examples/$example/icons";

// Parse all icons, map by item
$icons = array();
$iconItems = array();
$iconFiles = new DirectoryIterator($iconsFolder);
foreach ($iconFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'yml') continue;
    $fileName = $fileInfo->getFilename();
    $iconFile = spyc_load_file($iconsFolder . '/' . $fileName);
    if (!$iconFile) {
        echo "! Could not parse icon file: $fileName\n";
        continue;
    }
    foreach ($iconFile as $iconKey => $iconConfig) {
        $icons[$iconKey] = $iconConfig;

        if (isset($iconConfig['item'])) {
            $item = $iconConfig['item'];
            if (isset($iconItems[$item])) {
                echo "Duplicate icon item: $item\n";
            } else {
                $iconItems[$item] = $iconKey;
            }
        }
    }
}

// Iterate over all spells and possibly modify them
$spellFiles = new DirectoryIterator($spellFolder);
foreach ($spellFiles as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    if ($fileInfo->isDot()) continue;
    $extension = $fileInfo->getExtension();
    if ($extension != 'yml') continue;
    $fileName = $fileInfo->getFilename();
    $filePath = $spellFolder . '/' . $fileName;
    $spellFile = file_get_contents($filePath);
    if (!$spellFile) {
        die("Could not read: $fileName\n");
    }
    $lines = explode("\n", $spellFile);

    $indent = 0;
    $indentSpace = "";
    $modified = false;
    $outlines = array();
    $icon = null;
    $modified = false;
    while ($lines) {
        $line = array_shift($lines);
        $lineIndent = strlen($line) - strlen(ltrim($line));
        if ($lineIndent > $indent) {
            if ($indent != 0) {
                // We are going to assume we're done, there are not usually any sections
                // before the icons are done
                array_push($outlines, $line);
                break;
            } else {
                $indent = $lineIndent;
                $indentSpace = str_repeat(' ', $indent);
            }
        }
        if ($indent == 0) {
            array_push($outlines, $line);
            continue;
        }
        $parsed = explode(':', trim($line), 2);
        $key = $parsed[0];
        $finished = false;
        switch ($key) {
            case 'icon':
                $originalKey = trim($parsed[1]);
                $iconKey = convertIcon($originalKey);
                if (isset($iconItems[$iconKey])) {
                    $iconKey = $iconItems[$iconKey];
                    $icon = $icons[$iconKey];
                    array_push($outlines, "$indentSpace# This refers to an icon defined in the icons folder/config");
                    array_push($outlines, "$indentSpace$key: $iconKey");
                    $modified = true;
                } else {
                    // may as well convert these while we're here
                    if ($iconKey != $originalKey) {
                        array_push($outlines, "$indentSpace$key: $iconKey");
                        $modified = true;
                    }
                    $finished = true;
                }
                break;
            case 'icon_disabled':
            case 'legacy_icon':
            case 'legacy_icon_disabled':
            case 'icon_url':
                if ($key == 'icon_url') {
                    $iconKey = 'url';
                } else {
                    $iconKey = str_replace('icon_', 'item_', $key);
                    $iconKey = str_replace('_icon', '_item', $iconKey);
                }
                if ($icon && isset($icon[$iconKey])) {
                    $overrideKey = trim($parsed[1]);
                    $overrideKey = convertIcon($overrideKey);
                    if ($overrideKey !== $icon[$iconKey]) {
                        array_push($outlines, $line);
                    }
                } else {
                    array_push($outlines, $line);
                }
                break;
            default:
                array_push($outlines, $line);
                break;
        }
        if ($finished) {
            break;
        }
    }

    if (!$modified) {
        echo "  Skipped $filePath\n";
        continue;
    }
    $output = array();
    if ($outlines) {
        array_push($output, implode("\n", $outlines));
    }
    if ($lines) {
        array_push($output, implode("\n", $lines));
    }
    file_put_contents($filePath, implode("\n", $output));
    echo "Updated $filePath\n";
}
