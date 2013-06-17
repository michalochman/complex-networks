<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

$interval = 60*60*24*7*4;

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt_max = $db->prepare('SELECT max(bucket) max FROM ' . TABLE_WORDS_RELATIONSHIPS);
    $stmt_max->execute();
    $max = 141;
//    $max = $stmt_max->fetch(PDO::FETCH_OBJ)->max;

    $table_usage = TABLE_WORDS_RELATIONSHIPS;
    $table_users = TABLE_USERS;
    $table_words = TABLE_WORDS;
    $sql = <<<EOL
SELECT r.word_id word, r.user_id user
FROM headfi_words_relationships r
WHERE bucket=:bucket AND skip=:skip;
EOL;

    $stmt_usage = $db->prepare($sql);
    $bucket = 0;
    while ($bucket < $max) {
        $max_degree = 0;
        echo ($bucket+1).PHP_EOL;
        $nodes = array();
        $degrees = array();
        $stmt_usage->execute(array(
            ':bucket' => ++$bucket,
            ':skip' => 'n',
        ));
        $file = sprintf('%s/results/degdist/deg5plus/bucket-%03d.txt', __DIR__, $bucket);
        file_put_contents($file, "# degree P(degree)\n", FILE_APPEND);
        while ($usage = $stmt_usage->fetch(PDO::FETCH_OBJ)) {
            $nodes[$usage->word][] = $usage->user;
            $nodes[$usage->user][] = $usage->word;
        }
        foreach ($nodes as $node_id => $subnodes) {
            $degree = count(array_unique($subnodes));
            $max_degree = $max_degree < $degree ? $degree : $max_degree;
            $degrees[$degree][] = $node_id;
        }
        ksort($degrees);
        $removed_nodes = 0;
        foreach ($degrees as $degree => $subnodes) {
            if ($degree < 5) {
                $removed_nodes += count($subnodes);
                continue;
            }
            $probability = floatval(count($subnodes))/floatval(count($nodes)-$removed_nodes);
            file_put_contents($file, sprintf("%d %0.6f\n", $degree, $probability), FILE_APPEND);
        }
    }
}
catch (PDOException $e)
{
    exit(1);
}
catch (Exception $e)
{
    exit(1);
}
