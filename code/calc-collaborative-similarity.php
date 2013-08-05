<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

if ($argc !== 2) die('usage: ' . $argv[0] . ' bucket');
if (!is_numeric($argv[1])) die('usage: ' . $argv[0] . ' bucket');

try
{
    $infile = sprintf('%s/data/bucket-%03d.txt', __DIR__, $argv[1]);
    $outfile = sprintf('%s/data/bucket-%03d.txt', __DIR__, $argv[1]);
    $links = unserialize(file_get_contents($infile));
    printf("N = %d\n", count($links['users']));
    printf("M = %d\n", count($links['objects']));
    $edges = 0;
    foreach ($links['users'] as $object) {
        $edges += count($object);
    }
    printf("E = %d\n", $edges);
    printf("<k> = %f\n", average_degree($links, 'users'));
    printf("<d> = %f\n", average_degree($links, 'objects'));
}
catch (PDOException $e)
{
    exit(1);
}
catch (Exception $e)
{
    exit(1);
}

printf("C_u = %s\n", network_collaborative_similarity($links, 'users', 'objects', false));
printf("s_o = %s\n", average_jaccard_similarity($links, 'objects', 'users', false));
printf("C_o = %s\n", network_collaborative_similarity($links, 'objects', 'users', false));
printf("s_u = %s\n", average_jaccard_similarity($links, 'users', 'objects', false));
