<?php

ini_set('memory_limit', '8G');

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

try {
    $db = new PDO($dsn, $db_user, $db_pass);
    $words = array();
//    $stmt_words = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE word REGEXP "[0-9][^a-zA-Z0-9]$"');
    $stmt_words = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE word LIKE :word');
    $result = $stmt_words->execute(array(
        ':word' => '%->%',
    ));
    if ($result === false || $stmt_words->rowCount() === 0) throw new Exception('Empty resultset.');
    while ($row = $stmt_words->fetch(PDO::FETCH_OBJ)) {
        $words = explode('=>', $row->word);
        foreach ($words as $word) {
            $stmt_stem = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE word=:word');
            $result = $stmt_stem->execute(array(
                ':word' => $word,
            ));
            if ($result === false || $stmt_stem->rowCount() === 0) {
                // add new word if found
                $stmt_new_word = $db->prepare('INSERT INTO ' . TABLE_WORDS . ' VALUES(NULL, :word, 1, NULL, "n")');
                $stmt_new_word->execute(array(
                    ':word' => $word,
                ));
                $stmt_stem = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE word=:word');
                $result = $stmt_stem->execute(array(
                    ':word' => $word,
                ));
            }
            $stem = $stmt_stem->fetch(PDO::FETCH_OBJ);
//            $splfa = new SplFixedArray(2);
//            $splfa = array();
//            $splfa[0] = $row->id;
//            $splfa[1] = $row->count;
//            $words[$row->word] = $splfa;

            $stmt = $db->prepare('UPDATE ' . TABLE_WORDS . ' SET count=count+:count WHERE word=:word');
            $stmt->execute(array(
                ':count' => $row->count,
                ':word' => $stem->word,
            ));
        }
//        $stmt = $db->prepare('UPDATE ' . TABLE_WORDS_RELATIONSHIPS . ' SET word_id=:stem_id WHERE word_id=:word_id');
//        $stmt->execute(array(
//            ':stem_id' => $stem->id,
//            ':word_id' => $row->id,
//        ));
        $stmt = $db->prepare('SELECT * FROM ' . TABLE_WORDS_RELATIONSHIPS . ' WHERE word_id=:word_id');
        $result = $stmt->execute(array(
            ':word_id' => $row->id,
        ));
        $relationship = $stmt->fetch(PDO::FETCH_OBJ);
        foreach ($words as $word) {
            $stmt_stem = $db->prepare('SELECT id, word, count FROM ' . TABLE_WORDS . ' WHERE word=:word');
            $result = $stmt_stem->execute(array(
                ':word' => $word,
            ));
            $stem = $stmt_stem->fetch(PDO::FETCH_OBJ);
            $stmt = $db->prepare('INSERT INTO ' . TABLE_WORDS_RELATIONSHIPS . ' VALUES(:word_id, :user_id, :post_id, :used, :bucket)');
            $stmt->execute(array(
                ':word_id' => $stem->id,
                ':user_id' => $relationship->user_id,
                ':post_id' => $relationship->post_id,
                ':used' => $relationship->used,
                ':bucket' => $relationship->bucket,
            ));
        }
        $stmt = $db->prepare('DELETE FROM ' . TABLE_WORDS_RELATIONSHIPS . ' WHERE word_id=:word_id AND user_id=:user_id');
        $stmt->execute(array(
            ':word_id' => $relationship->word_id,
            ':user_id' => $relationship->user_id,
        ));
        $stmt = $db->prepare('DELETE FROM ' . TABLE_WORDS . ' WHERE id=:id');
        $stmt->execute(array(
            ':id' => $row->id,
        ));
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit(1);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
