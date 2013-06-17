<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

try {
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt_min = $db->prepare('SELECT min(used) min FROM ' . TABLE_WORDS_RELATIONSHIPS);
    $stmt_min->execute();
    $min = strtotime($stmt_min->fetch(PDO::FETCH_OBJ)->min);
    $stmt_max = $db->prepare('SELECT max(used) max FROM ' . TABLE_WORDS_RELATIONSHIPS);
    $stmt_max->execute();
    $max = strtotime($stmt_max->fetch(PDO::FETCH_OBJ)->max);
    $dates = array();
    $stmt_words = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE skip="n" and meta="." ORDER BY count DESC limit 40');
    $result = $stmt_words->execute();
    if ($result === false) throw new Exception('Empty resultset.');
    if ($result !== false) {
        while ($row = $stmt_words->fetch(PDO::FETCH_OBJ)) {
            if (!in_array($row->id, array('116123230', '116149676', '116071586'))) continue;
            $stmt_usage = $db->prepare('SELECT used FROM ' . TABLE_WORDS_RELATIONSHIPS . ' WHERE word_id=:id ORDER BY used ASC');
            $result_usage = $stmt_usage->execute(array(
                ':id' => $row->id,
            ));
            if ($result === false) continue;
            echo sprintf("\n\nbucket %s\n", $row->word);
            $date = 0;
            $use_count = 0;
            $min_date = $min;
            while ($row_usage = $stmt_usage->fetch(PDO::FETCH_OBJ)) {
                $used = strtotime($row_usage->used);
                # increase time frame while current word usage was after previous time frame
                while ($min_date < $used) {
                    # print what was counted to time
                    echo sprintf("%d %.12f\n", $date, $use_count / floatval($row->count));
                    # reset counters
                    $use_count = 0;
                    $date++;
                    # increase time frame by 4 weeks
                    $min_date += 60*60*24*7*4;
                }
                $use_count++;
            }
            # print final time frame
            echo sprintf("%d %.12f\n", $date, $use_count / $row->count);
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit(1);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}