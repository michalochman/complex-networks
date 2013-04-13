<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

if ($argc !== 3) die('usage: ' . $argv[0] . ' limit offset');

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_POSTS . ' ORDER BY id ASC LIMIT ' . intval($argv[1]) . ' OFFSET ' . intval($argv[2]) . ';');
    $result = $stmt->execute();
    if ($result === false) throw new Exception('Empty resultset.');

    $words = array();
    $stmt_words = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS);
    $result = $stmt_words->execute();
    if ($result !== false)
    {
        while ($row = $stmt_words->fetch(PDO::FETCH_OBJ))
        {
            //$splfa = new SplFixedArray(2);
            $splfa = array();
            $splfa[0] = $row->id;
            $splfa[1] = $row->count;
            $words[$row->word] = $splfa;
        }
    }

    $keywords = array();
    $stmt_words = $db->prepare('SELECT word FROM ' . TABLE_KEYWORDS);
    $result = $stmt_words->execute();
    if ($result !== false)
    {
        while ($row = $stmt_words->fetch(PDO::FETCH_OBJ))
        {
            $keywords[] = $row->word;
        }
    }

    $stopwords = array();
    if (file_exists(__DIR__ . '/stopwords.txt'))
    {
        $stopwords = file(__DIR__ . '/stopwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
/*    $stmt_words = $db->prepare('SELECT word FROM ' . TABLE_STOPWORDS);
    $result = $stmt_words->execute();
    if ($result !== false)
    {
        while ($row = $stmt_words->fetch(PDO::FETCH_OBJ))
        {
            $stopwords[] = $row->word;
        }
    }*/

    $row_counter = 0;
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        if (++$row_counter % 1000 === 0)
        {
            $memory_used = floatval(memory_get_usage(true));
            $memory_used = $memory_used / 1024 / 1024;
            echo sprintf('%010d : %04dM', $row_counter, $memory_used) . PHP_EOL;
        }

        $text = $row->body;
        //$tokens = preg_split('/((^\p{P}+)|(\.{2,})|(/(?!/))|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = preg_split('/(\p{P}+)|(\s+)/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_filter($tokens);

        foreach ($tokens as $token)
        {
            if (($word = prepareWord($token)) === false) continue;
            if (in_array($word, $stopwords)) continue;

            if (!array_key_exists($word, $words))
            {
                $keyword = 0;
                if (in_array($word, $keywords)) $keyword = 1;

                $stmt_words = $db->prepare('INSERT INTO ' . TABLE_WORDS . ' VALUES(null, :word, :count, :keyword);');
                $stmt_words->execute(array(
                    ':word' => $word,
                    ':count' => 0,
                    ':keyword' => $keyword, 
                ));

                //$splfa = new SplFixedArray(2);
                $splfa = array();
                $splfa[0] = $db->lastInsertId();
                $splfa[1] = 0;
                $words[$word] = $splfa;
            }
            $words[$word][1] += 1;

            $stmt_words = $db->prepare('INSERT INTO ' . TABLE_WORDS_RELATIONSHIPS . ' VALUES(:word_id, :user_id, :post_id, :used);');
            $stmt_words->execute(array(
                ':word_id' => $words[$word][0],
                ':user_id' => $row->user_id,
                ':post_id' => $row->id,
                ':used' => $row->date,
            ));
        }
    }

    foreach ($words as $word => $splfa)
    {
        $stmt_words = $db->prepare('UPDATE ' . TABLE_WORDS . ' SET count = :count WHERE id = :id');
        $stmt_words->execute(array(
            ':id' => $splfa[0],
            ':count' => $splfa[1],
        ));
    }
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
