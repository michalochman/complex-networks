<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
//    $stmt_words = $db->prepare('SELECT id, word FROM ' . TABLE_WORDS . ' WHERE meta IS NOT NULL');
    $stmt_words = $db->prepare('SELECT id, word FROM ' . TABLE_WORDS . ' WHERE skip=:skip');
    $stmt_words->execute(array(
        ':skip' => 'y',
    ));
    $stmt_rels = $db->prepare('UPDATE ' . TABLE_WORDS_RELATIONSHIPS . ' SET skip=:skip WHERE word_id=:word_id');
    $i = 0;
    while ($word = $stmt_words->fetch(PDO::FETCH_OBJ)) {
        echo $word->word . PHP_EOL;
        $stmt_rels->execute(array(
//            ':skip' => 'n',
            ':skip' => 'NULL',
            ':word_id' => $word->id,
        ));
        if (++$i % 1000 == 0) {
            echo $i.PHP_EOL;
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
