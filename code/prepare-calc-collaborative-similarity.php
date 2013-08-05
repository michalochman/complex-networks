<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

if ($argc !== 2) die('usage: ' . $argv[0] . ' bucket');
if (!is_numeric($argv[1])) die('usage: ' . $argv[0] . ' bucket');

$links = array(
    'users' => array(
        1 => array('b', 'c'),
        2 => array('a', 'd'),
        3 => array('b', 'e', 'f'),
        4 => array('c', 'd', 'f', 'h'),
        5 => array('b', 'f'),
        6 => array('g'),
    ),
    'objects' => array(
        'a' => array(2),
        'b' => array(1, 3, 5),
        'c' => array(1, 4),
        'd' => array(2, 4),
        'e' => array(3),
        'f' => array(3, 4, 5),
        'g' => array(6),
        'h' => array(4),
    ),
);

try
{
    $links = array(
        'users' => array(),
        'objects' => array(),
    );
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_WORDS_RELATIONSHIPS . ' WHERE bucket=:bucket and skip=:skip;');
    $result = $stmt->execute(array(
        ':bucket' => $argv[1],
        ':skip' => 'n',
    ));
    if ($result === false) throw new Exception('Empty resultset.');

    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        // populate users links
        if (!array_key_exists($row->user_id, $links['users'])) {
            $links['users'][$row->user_id] = array();
        }
        if (!in_array($row->word_id, $links['users'][$row->user_id])) {
            $links['users'][$row->user_id][] = $row->word_id;
        }

        // populate words links
        if (!array_key_exists($row->word_id, $links['objects'])) {
            $links['objects'][$row->word_id] = array();
        }
        if (!in_array($row->user_id, $links['objects'][$row->word_id])) {
            $links['objects'][$row->word_id][] = $row->user_id;
        }
    }
    echo serialize($links);
}
catch (PDOException $e)
{
    exit(1);
}
catch (Exception $e)
{
    exit(1);
}
