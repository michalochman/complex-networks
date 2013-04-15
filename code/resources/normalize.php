<?php

if ($argc < 2 || $argc > 3) {
    die(sprintf("Usage:\n%s infile [outfile]\n", $argv[0]));
}

$pos = file('positive.txt', FILE_IGNORE_NEW_LINES);
$neg = file('negative.txt', FILE_IGNORE_NEW_LINES);
$stop = file('functionwords.txt', FILE_IGNORE_NEW_LINES);
$func = file('stopwords.txt', FILE_IGNORE_NEW_LINES);
$opi = array_merge($pos, $neg, $stop, $func);

$infile = $argv[1];
if ($argc == 2) {
    $info = pathinfo($infile);
    $outfile = rtrim(sprintf('%s-normalized.%s', $info['filename'], $info['extension']), '.');
} else {
    $outfile = $argv[2];
}

$file = file($infile);
$words = array();

foreach ($file as $word) {
    $word = trim($word);
    if (strpos($word, ' ') !== false) continue;
    if (strlen($word) < 3) continue;
    $words[] = strtolower($word);
}

natsort($words);
$words = array_unique($words);
$words = array_diff($words, $opi);
$data = '';
foreach ($words as $word) {
    $data .= $word . PHP_EOL;
}

file_put_contents($outfile, $data);
