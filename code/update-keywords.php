<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_KEYWORDS . ';');
    $result = $stmt->execute();
    if ($result === false) throw new Exception('Empty resultset.');

    $updated = 0;
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        $stmt_rows = $db->prepare('UPDATE ' . TABLE_WORDS . ' SET keyword=1 WHERE word = :word LIMIT 1;');
        $stmt_rows->execute(array(
            ':word' => $row->word,
        ));
        $updated += $stmt_rows->rowCount();
    }

    echo sprintf("%s rows updated\n", $updated);
}
catch (PDOException $e)
{
    exit(1);
}
catch (Exception $e)
{
    exit(1);
}
