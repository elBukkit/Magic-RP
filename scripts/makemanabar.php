<?php

if (PHP_SAPI !== 'cli') {
    die('Nope.');
}

if (count($argv) < 2) {
    die("Usage: makeicons.php <template.png>\n");
}

$templateFilename = $argv[1];

if (!file_exists($templateFilename)) {
    die("File does not exist: $templateFilename\n");
}

$barWidth = 128;
$barHeight = 5;
$sliceCount = 33;
$template = imagecreatefrompng($templateFilename);
$manabar = imagecreatetruecolor(128, $sliceCount * $barHeight);
imagealphablending($manabar, false);

$transparent = imagecolorallocatealpha($manabar, 255, 255, 255, 128);
imagefilledrectangle($manabar, 0, 0, $barWidth, $sliceCount * $barHeight, $transparent);

for ($slice = 0; $slice < $sliceCount; $slice++) {
    imagealphablending($manabar, false);

    imagecopy($manabar, $template, 0,  $barHeight * $slice, 0, 0, $barWidth, $barHeight);

    $endX = $slice * $barWidth / ($sliceCount - 1);
    imagecopy($manabar, $template, 0,  $barHeight * $slice, 0, $barHeight, $endX, $barHeight);

    imagesavealpha($manabar, true);


}

$outputFile = 'mana.png';

echo "Wrote to $outputFile\n";
imagepng($manabar, $outputFile);

imagedestroy($template);
imagedestroy($manabar);