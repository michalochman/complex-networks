<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

try
{
    $db = new PDO($dsn, $db_user, $db_pass);

    $b = new Crawler();
    $b->get('http://www.head-fi.org/f/');

    $d = $b->getResponseSimpleDom();
    $a = $d->find('td.forum-col h3 a');

    foreach ($a as $f)
    {
        $url = $f->href;
        $title = utf8_entities_decode(html_entity_decode($f->innertext));
        preg_match('#/f/(\d+)/#', $url, $matches);
        $id = isset($matches[1]) ? $matches[1] : null;

        if ($id === null) continue;

        $stmt = $db->prepare('INSERT INTO ' . TABLE_FORUMS . ' VALUES(:id, :title);');
        $stmt->execute(array(
            ':id' => $id,
            ':title' => $title,
        ));
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
