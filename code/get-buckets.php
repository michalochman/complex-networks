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
//    $sql = <<<EOL
//SELECT w.word, u.name, concat(r.user_id, '->', r.word_id) edge
//FROM $table_usage r
//JOIN $table_users u ON u.id = r.user_id
//JOIN $table_words w ON w.id = r.word_id
//WHERE bucket=:bucket
//EOL;
    $sql = <<<EOL
SELECT r.word_id word, r.user_id user, concat(r.user_id, '->', r.word_id) edge
FROM headfi_words_relationships r
WHERE bucket=:bucket AND skip=:skip;
EOL;

    $file_format = 'gexf'; // or 'nwb'


    $stmt_usage = $db->prepare($sql);
    $bucket = 0;
    while ($bucket < $max) {
        $nodes_words = array();
        $nodes_users = array();
        $edges = array();
        $stmt_usage->execute(array(
            ':bucket' => ++$bucket,
            ':skip' => 'n',
        ));
        $file = sprintf('%s/results/%s/kwns/bucket-%03d.%s', __DIR__, $file_format, $bucket, $file_format);
        while ($usage = $stmt_usage->fetch(PDO::FETCH_OBJ)) {
            $edges[$usage->edge] += 1;
            $nodes_users[$usage->user] += 1;
            $nodes_words[$usage->word] += 1;
        }
        if ($file_format === 'nwb') {
            file_put_contents($file, sprintf("*Nodes %d\n", count($nodes_users) + count($nodes_words)), FILE_APPEND);
            file_put_contents($file, sprintf("id*int label*string\n"), FILE_APPEND);
        } elseif ($file_format === 'gexf') {
            file_put_contents($file, sprintf('<gexf xmlns="http://www.gexf.net/1.2draft" version="1.2">'), FILE_APPEND);
            file_put_contents($file, sprintf('<meta lastmodifieddate="2013-06-09">'), FILE_APPEND);
            file_put_contents($file, sprintf('<creator>Michał Ochman</creator>'), FILE_APPEND);
            file_put_contents($file, sprintf('<description>bucket %s</description>', $bucket), FILE_APPEND);
            file_put_contents($file, sprintf('</meta>'), FILE_APPEND);
            file_put_contents($file, sprintf('<graph mode="static" defaultedgetype="undirected">'), FILE_APPEND);
            file_put_contents($file, sprintf('<nodes>'), FILE_APPEND);
        }
        $max_count = 1;
        foreach ($nodes_users as $user => $count) {
            $max_count = $max_count < $count ? $count : $max_count;
        }
        foreach ($nodes_users as $user => $count) {
            $weight = floatval($count)/floatval($max_count);
            if ($file_format === 'nwb') {
                file_put_contents($file, sprintf("%d \"%d\"\n", $user, $user), FILE_APPEND);
            } elseif ($file_format === 'gexf') {
                file_put_contents($file, sprintf('<node id="%d" label="%d"/>', $user, $user), FILE_APPEND);
            }
        }
        if ($file_format === 'gexf') {
            file_put_contents($file, sprintf('</nodes>'), FILE_APPEND);
            file_put_contents($file, sprintf('<edges>'), FILE_APPEND);
        }
        $max_count = 1;
        foreach ($nodes_words as $word => $count) {
            $max_count = $max_count < $count ? $count : $max_count;
        }
        foreach ($nodes_words as $word => $count) {
            $weight = floatval($count)/floatval($max_count);
            if ($file_format === 'nwb') {
                file_put_contents($file, sprintf("%d \"%d\"\n", $word, $word), FILE_APPEND);
            } elseif ($file_format === 'gexf') {
                file_put_contents($file, sprintf('<node id="%d" label="%d"/>', $word, $word), FILE_APPEND);
            }
        }
        if ($file_format === 'nwb') {
            file_put_contents($file, sprintf("*UndirectedEdges %d\n", count($edges)), FILE_APPEND);
            file_put_contents($file, sprintf("source*int target*int\n"), FILE_APPEND);
        }
        $max_count = 1;
        foreach ($edges as $edge => $count) {
            $max_count = $max_count < $count ? $count : $max_count;
        }
        foreach ($edges as $edge => $count) {
            $edge = explode('->', $edge);
            $weight = floatval($count)/floatval($max_count);
            if ($file_format === 'nwb') {
                file_put_contents($file, sprintf("%d %d\n", $edge[0], $edge[1]), FILE_APPEND);
            } elseif ($file_format === 'gexf') {
                file_put_contents($file, sprintf('<edge source="%d" target="%d" weight="%.6f"/>', $edge[0], $edge[1], $weight), FILE_APPEND);
            }
        }
        if ($file_format === 'gexf') {
            file_put_contents($file, sprintf('</edges>'), FILE_APPEND);
            file_put_contents($file, sprintf('</graph>'), FILE_APPEND);
            file_put_contents($file, sprintf('</gexf>'), FILE_APPEND);
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
