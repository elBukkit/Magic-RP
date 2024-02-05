#!/usr/local/bin/php
<?php

if (count($argv) < 3) {
    die("Usage: merge_folder.php <from> <to> [skip]\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$fromFolder = $argv[1];
if (!file_exists($fromFolder)) {
    die("Folder not found: $fromFolder\n");
}

$toFolder = $argv[2];
if (!file_exists($toFolder)) {
    mkdir($toFolder, 0777, true);
}

$skip = array();
if (count($argv) > 3) {
    $skip = explode(';', $argv[3]);
    $skip = array_fill_keys($skip, true);
}

function startsWith($haystack, $needle){
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    return $length === 0 || (substr($haystack, -$length) === $needle);
}

function overrideSorter($a, $b) {
    if (isset($a['predicate'])) {
        if (!isset($b['predicate'])) return -1;
        $ap = $a['predicate'];
        $bp = $b['predicate'];

        // We need to first separate CMD from non-CMD/damage
        // Non-CMD always needs to come first
        // But after that we need to group by bow pull or shield blocking first.

        // CMD
        if (isset($ap['custom_model_data'])) {
            if (!isset($bp['custom_model_data'])) return 1;
        } else if (isset($bp['custom_model_data'])) return -1;

        // damaged
        if (isset($ap['damaged'])) {
            if (!isset($bp['damaged'])) return 1;
        } else if (isset($bp['damaged'])) return -1;

        // damage
        if (isset($ap['damage'])) {
            if (!isset($bp['damage'])) return 1;
        } else if (isset($bp['damage'])) return -1;

        // Bow pulling
        if (isset($ap['pulling'])) {
            if (!isset($bp['pulling'])) return 1;
            if ($ap['pulling'] < $bp['pulling']) return -1;
            if ($ap['pulling'] > $bp['pulling']) return 1;
        } else if (isset($bp['pulling'])) return -1;

        // Bow pull
        if (isset($ap['pull'])) {
            if (!isset($bp['pull'])) return 1;
            if ($ap['pull'] < $bp['pull']) return -1;
            if ($ap['pull'] > $bp['pull']) return 1;
        } else if (isset($bp['pull'])) return -1;

        // Blocking
        if (isset($ap['blocking'])) {
            if (!isset($bp['blocking'])) return 1;
            if ($ap['blocking'] < $bp['blocking']) return -1;
            if ($ap['blocking'] > $bp['blocking']) return 1;
        } else if (isset($bp['blocking'])) return -1;

        // Finally sort by CMD and/or damage values if present
        // At this point if one has CMD the other should as well,
        // or else we would've returned above.

        // CMD
        if (isset($ap['custom_model_data'])) {
            if ($ap['custom_model_data'] < $bp['custom_model_data']) return -1;
            if ($ap['custom_model_data'] > $bp['custom_model_data']) return 1;
        }

        // damaged
        if (isset($ap['damaged'])) {
            if ($ap['damaged'] < $bp['damaged']) return -1;
            if ($ap['damaged'] > $bp['damaged']) return 1;
        }

        // damage
        if (isset($ap['damage'])) {
            if ($ap['damage'] < $bp['damage']) return -1;
            if ($ap['damage'] > $bp['damage']) return 1;
        }

    } else if (isset($b['predicate'])) return 1;
    return 0;
}

function copyFile($fromFile, $toFile) {
    $path = pathinfo($toFile);
    if (!file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0777, true);
    }
    copy($fromFile, $toFile);
}

function mergeFolder($fromFolder, $toFolder) {
    global $skip;
    if (isset($skip[$fromFolder])) {
        echo "Skipping $fromFolder\n";
        return;
    }
    foreach (new DirectoryIterator($fromFolder) as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        $fileName = $fileInfo->getFilename();
        if (startsWith($fileName, '.')) continue;
        $fromFile = $fromFolder . DIRECTORY_SEPARATOR . $fileName;
        $toFile = $toFolder . DIRECTORY_SEPARATOR . $fileName;
        if ($fileInfo->isDir()) {
            mergeFolder($fromFile, $toFile);
            continue;
        }

        if (!file_exists($toFile)) {
            copyFile($fromFile, $toFile);
            continue;
        }

        if ($fileInfo->getExtension() != 'json') {
            if ($fileName !== 'pack.mcmeta' && $fileName !== 'pack.png') {
                echo " Non-json file exists in both files: $fileName, ignoring second file.\n";
            }
            continue;
        }

        $fromJSON = json_decode(file_get_contents($fromFile), true);
        $toJSON = json_decode(file_get_contents($toFile), true);

        if (!$toJSON) {
            copyFile($fromFile, $toFile);
            continue;
        }

        if (!$fromJSON) {
            continue;
        }
        
        if (endsWith($fromFile, 'assets/minecraft/atlases/blocks.json')) {
            foreach ($fromJSON as $key => $value) {
                if ($key == "sources" ) {
                    foreach($value as $atlas => $atlasDefinition) {
                        if(!in_array($atlasDefinition, $toJSON["sources"])){
                            array_push($toJSON["sources"],$atlasDefinition);
                        }
                    }
                }
            }
            file_put_contents($toFile, json_encode($toJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            continue;
        }

        if (endsWith($fromFile, 'assets/minecraft/sounds.json')) {
            foreach ($fromJSON as $key => $sound) {
                if (!isset($toJSON[$key])) {
                    $toJSON[$key] = $sound;
                }
            }
            file_put_contents($toFile, json_encode($toJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            continue;
        }

        if (!isset($fromJSON['overrides'])) {
            continue;
        }

        $fromOverrides = $fromJSON['overrides'];
        if (!isset($toJSON['overrides'])) {
            $toJSON['overrides'] = $fromOverrides;
            file_put_contents($toFile, json_encode($toJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return;
        }

        $toOverrides = $toJSON['overrides'];
        $hasCustomModelData = array();
        $hasDamage = array();
        $hasPredicate = array();
        for ($i = 0; $i < count($toOverrides); $i++) {
            $override = $toOverrides[$i];
            if (isset($override['predicate'])) {
                $predicate = $override['predicate'];
                if (isset($predicate['custom_model_data'])) {
                    $hasCustomModelData[$predicate['custom_model_data']] = true;
                } else if (isset($predicate['damage'])) {
                    $hasDamage['' . $predicate['damage']] = true;
                } else {
                    ksort($predicate);
                    $hasPredicate[json_encode($predicate)] = true;
                }
            }
        }

        for ($i = 0; $i < count($fromOverrides); $i++) {
            $override = $fromOverrides[$i];
            if (isset($override['predicate'])) {
                $predicate = $override['predicate'];
                ksort($predicate);
                if (isset($predicate['custom_model_data'])) {
                    if (isset($hasCustomModelData[$predicate['custom_model_data']])) {
                        echo("    Skipping duplicate override from: " . $fromFile . "{CustomModelData:" . $predicate['custom_model_data'] . "}\n");
                    } else {
                        array_push($toOverrides, $override);
                    }
                } else if (isset($predicate['damage'])) {
                    if (isset($hasDamage['' . $predicate['damage']])) {
                        echo("    Skipping duplicate override from: " . $fromFile . ":" . $predicate['damage'] . "\n");
                    } else {
                        array_push($toOverrides, $override);
                    }
                } else if ($hasPredicate[json_encode($predicate)]) {
                    echo "    Skipping override with duplicate predicate: " . json_encode($predicate) . "\n";
                } else {
                    if (!isset($predicate['pulling']) && !isset($predicate['pull']) && !isset($predicate['blocking'])) {
                        echo("    Adding unknown override predicate type from: " . $fromFile . ": " . json_encode($predicate) . "}\n");
                    }
                    array_push($toOverrides, $override);
                }
            } else {
                echo("Adding unknown override type from: " . $fromFile . ": " . json_encode($override) . "}\n");
                array_push($toOverrides, $override);
            }
        }

        usort($toOverrides, 'overrideSorter');

        $toJSON['overrides'] = $toOverrides;
        file_put_contents($toFile, json_encode($toJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

echo "  Merging $fromFolder into $toFolder\n";
mergeFolder($fromFolder, $toFolder);
