<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

$interval = 60*60*24*7*4;

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt_min = $db->prepare('SELECT min(used) min FROM ' . TABLE_WORDS_RELATIONSHIPS);
    $stmt_min->execute();
    $min = strtotime($stmt_min->fetch(PDO::FETCH_OBJ)->min);
    $stmt_max = $db->prepare('SELECT max(used) max FROM ' . TABLE_WORDS_RELATIONSHIPS);
    $stmt_max->execute();
    $max = strtotime($stmt_max->fetch(PDO::FETCH_OBJ)->max);

    $stmt_usage = $db->prepare('UPDATE ' . TABLE_WORDS_RELATIONSHIPS . ' SET bucket=:bucket WHERE used BETWEEN :start AND :end');
    $start = strtotime(date('Y-m-d 00:00:00', $min));
    $bucket = 0;
    while ($start < $max) {
        $end = $start + $interval;
        echo sprintf("Bucket %d between %s and %s.\n", $bucket + 1, date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end-1));
        $stmt_usage->execute(array(
            ':bucket' => ++$bucket,
            ':start' => date('Y-m-d H:i:s', $start),
            ':end' => date('Y-m-d H:i:s', $end-1),
        ));
        $start = $end;
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
