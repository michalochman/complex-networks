<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

if ($argc !== 3) die('usage: ' . $argv[0] . ' limit offset'.PHP_EOL);

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

    $row_counter = 0;
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        if (++$row_counter % 1000 === 0) {
            $memory_used = floatval(memory_get_usage(true));
            $memory_used = $memory_used / 1024 / 1024;
            echo sprintf('%010d : %04dM', $row_counter, $memory_used) . PHP_EOL;
        }

        $text = $row->body;
        //$tokens = preg_split('/((^\p{P}+)|(\.{2,})|(/(?!/))|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $text, -1, PREG_SPLIT_NO_EMPTY);
        // split spaces or by punctuation characters other than single hyphen
        $tokens = preg_split('/\s+|-{2,}|(?!-)\p{P}+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_filter($tokens);

        foreach ($tokens as $token) {
            $word = prepareWord($token);
            if ($word === false) continue;
            if (in_array($word, $stopwords)) continue;
//            if (in_array($word, $functionwords)) continue;

            if (!array_key_exists($word, $words)) {
                $keyword = null;
                if (in_array($word, $positive)) $keyword = '+';
                if (in_array($word, $negative)) $keyword = '-';
                if (in_array($word, $functionwords)) $keyword = 'x';
                if (strpos($word, '$') !== false) $keyword = '$';
                // check for strings like MODEL-123s
                if (in_array(rtrim($word, 's'), $keywords)) $keyword = '.';

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
