<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_FORUMS . ' ORDER BY id ASC LIMIT 1;');
    $result = $stmt->execute();

    if ($result === false) throw new Exception('Empty resultset.');

    $b = new Crawler();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        $url = sprintf('%s/f/%d/', BASE_URL, $row->id);
        $b->get($url);
        while (!$b->responseIsError())
        {
            var_dump($b->getUrlInfo());
            $d = $b->getResponseSimpleDom();
            $a = $d->find('td.thread-col a.forum-list-main-post');

            foreach ($a as $t)
            {
                $id = $t->{'data-thread-id'};
                $title = strip_tags(utf8_entities_decode(html_entity_decode($t->innertext)));

                $stmt = $db->prepare('INSERT INTO ' . TABLE_TOPICS . ' VALUES(:id, :forum_id, :title);');
                if (!$stmt->execute(array(
                    ':id' => $id,
                    ':forum_id' => $row->id,
                    ':title' => $title,
                )))
                {
                    //var_dump($db->errorCode());
                }
            }

            try
            {
                $b->click('Next »');
            }
            catch (Exception $e)
            {
                continue 2;
            }
        }
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
