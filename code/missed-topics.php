<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_TOPICS . ' ORDER BY forum_id, id ASC;');
    $result = $stmt->execute();

    if ($result === false) throw new Exception('Empty resultset.');

    $arr1 = array();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        $arr1[] = $row->id;
    }

    $stmt = $db->prepare('SELECT DISTINCT topic_id FROM ' . TABLE_POSTS . ';');
    $result = $stmt->execute();

    if ($result === false) throw new Exception('Empty resultset.');

    $arr2 = array();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        $arr2[] = $row->topic_id;
    }

    $diff = array_diff($arr1, $arr2);

    var_dump(count($diff));
}
catch (PDOException $e)
{
    echo $e->getMessage();
    exit(1);
}
catch (Exception $e)
{
    echo $e->getMessage();
    exit(1);
}
