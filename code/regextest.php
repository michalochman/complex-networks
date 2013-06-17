<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

$string = <<<EOS
I saw these on Woot and went web crawling for reviews. There were many out there but i found the ones here the most honest and sincere so grabbed them for an out the door price of $82(includes tax and shipping). I am now anxsiously waiting for their arrival and will leave my impressions after using them. I am an all around user of headphones and earbuds,( music ,movies and gaming), mostly flac files only music. I have gone through too many sets of gaming headsets to mention, they just arent comfortable or don't hold up so who knows perhaps these may end up being used primarily for something hardley ever mentioned in ads or reviews.
EOS;
$regex = '/\s+|-{2,}|(?!-)\p{P}+/';

$brands = array();
if (file_exists(RESOURCES_DIR . '/brands.txt')) {
    $brands = file(RESOURCES_DIR . '/brands.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$models = array();
if (file_exists(RESOURCES_DIR . '/models.txt')) {
    $models = file(RESOURCES_DIR . '/models.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$keywords = array_merge($brands, $models);
$positive = array();
if (file_exists(RESOURCES_DIR . '/positive.txt')) {
    $positive = file(RESOURCES_DIR . '/positive.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$negative = array();
if (file_exists(RESOURCES_DIR . '/negative.txt')) {
    $negative = file(RESOURCES_DIR . '/negative.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$stopwords = array();
if (file_exists(RESOURCES_DIR . '/stopwords.txt')) {
    $stopwords = file(RESOURCES_DIR . '/stopwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$functionwords = array();
if (file_exists(RESOURCES_DIR . '/functionwords.txt')) {
    $functionwords = file(RESOURCES_DIR . '/functionwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

$tokens = preg_split($regex, $string, -1, PREG_SPLIT_NO_EMPTY);
$tokens = array_filter($tokens);

foreach ($tokens as $token) {
    $word = prepareWord($token);
    if ($word === false) continue;
    if (in_array($word, $stopwords)) continue;

    $keyword = null;
    if (in_array($word, $positive)) $keyword = '+';
    if (in_array($word, $negative)) $keyword = '-';
    if (in_array($word, $functionwords)) $keyword = 'x';
    if (strpos($word, '$') !== false) $keyword = '$';
    // check for strings like MODEL-123s
    if (in_array(rtrim($word, 's'), $keywords)) $keyword = '.';

    var_dump(sprintf('%s%s', $keyword, $word));
}
