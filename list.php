<?php
?>
<html>
<head>
    <title>Magic Resource Packs</title>
    <style type="text/css">
        body {
            background-color: black;
            color: white;
        }
        a {
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
<h1>Magic Resource Packs</h1>

<?php
function endsWith($haystack, $needle){
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function startsWith($string, $startString) {
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

$files = array();
$dir = new DirectoryIterator(dirname(__FILE__));
foreach ($dir as $file) {
    if ($dir->isDot()) continue;
    if ($dir->isDir()) continue;
    $filename = $dir->getFilename();
    if (!endsWith($filename, '.zip')) continue;
    if (!startsWith($filename, 'Magic-')) continue;
    $basename = str_replace('.zip', '', $filename);
    $pieces = explode('-', $basename);
    $version = $pieces[count($pieces) - 1];
    $normal = count($pieces) >= 3 && $pieces[count($pieces) - 2] === 'RP' && startsWith($version, '1.');
    $type = '';
    if ($normal) {
        if (count($pieces) == 3) {
            $type = 'standard';
        } else {
            $typePieces = array_slice($pieces, 1, -2);
            $type = implode('-', $typePieces);
        }
    }

    $files[] = array('filename' => $filename, 'version' => $version, 'normal' => $normal, 'type' => $type);
}
usort($files, function($a, $b) {
    if ($a['normal'] !== $b['normal']) {
        if ($a['normal']) return -1;
        return 1;
    }
    if (!$a['normal']) {
        return strcmp($a['filename'], $b['filename']);
    }
    if ($a['type'] != $b['type']) {
        if ($a['type'] === 'standard') return -1;
        if ($b['type'] === 'standard') return 1;
        return strcmp($a['type'], $b['type']);
    }
    if ($a['version'] != $b['version']) {
        return -strcmp($a['version'], $b['version']);
    }
    return strcmp($a['filename'], $b['filename']);
});
$normal = true;
$type = '';
foreach ($files as $fileInfo) {
    $filename = $fileInfo['filename'];
    if ($fileInfo['normal'] !== $normal) {
        $normal = $fileInfo['normal'];
        echo "<h2>OTHER</h2>\n";
    } else if ($normal && $type !== $fileInfo['type']) {
        $type = $fileInfo['type'];
        echo "<h2>$type</h2>\n";
    }
    $descriptor = $filename;
    // $descriptor = htmlentities(json_encode($fileInfo), ENT_QUOTES, 'UTF-8');
    echo '<a href="https://rp.elmakers.com/' . $filename . '">' . $descriptor . '</a><br/>';
}
?>
<br/>
<br/>
<br/>
<br/>
</body>
</html>
