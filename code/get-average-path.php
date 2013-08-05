<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

try
{
    for ($bucket = 1; $bucket < 142; $bucket++) {
        $content = file_get_contents(sprintf('%/results/avgp/%03d/report.html', __DIR__, $bucket));
        if (preg_match('#Average Path length: (\d+\.\d+)#', $content, $matches)) {
            printf("%03d %s\n", $bucket, $matches[1]);
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
