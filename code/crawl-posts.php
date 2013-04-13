<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

if ($argc !== 3) die('usage: ' . $argv[0] . ' limit offset');

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    $stmt = $db->prepare('SELECT * FROM ' . TABLE_TOPICS . ' ORDER BY forum_id, id ASC LIMIT ' . intval($argv[1]) . ' OFFSET ' . intval($argv[2]) . ';');
    $result = $stmt->execute();

    if ($result === false) throw new Exception('Empty resultset.');

    $b = new Crawler();
    $row_counter = 0;
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        echo $row_counter++;
        $url = sprintf('%s/t/%d/', BASE_URL, $row->id);
        $b->get($url);
        while (!$b->responseIsError())
        {
            $urlinfo = $b->getUrlInfo();
            echo $urlinfo['path'] . PHP_EOL;

            $d = $b->getResponseSimpleDom();
            $a = $d->find('div#reading-pane div.single-post');

            foreach ($a as $p)
            {
                preg_match('#post_(\d+)#', $p->id, $matches);
                $id = isset($matches[1]) ? $matches[1] : null;
                if ($id === null) continue;

                $date = $p->find('.forum-post-date', 0);
                date_default_timezone_set('PST8PDT');
                $time = strtotime(str_replace(' at ', ', ', trim($date->innertext)));
                date_default_timezone_set('UTC');
                $date = date('Y-m-d H:i:s', $time);

                $user = $p->find('li.post-username span', 0);
                preg_match('#pMenu-(\d+)#', $user->id, $matches);
                $user_id = isset($matches[1]) ? $matches[1] : null;
                if ($user_id === null || !isset($user->innertext)) continue;

                $user = $user->find('a', 0);
                $user_name = trim($user->innertext);

                $stmt2 = $db->prepare('INSERT INTO ' . TABLE_USERS . ' VALUES(:id, :name);');
                if (!$stmt2->execute(array(
                    ':id' => $user_id,
                    ':name' => $user_name,
                )))
                {
                    //var_dump($db->errorCode());
                }

                $content = $p->find('div.post-content-area div.wiki_markup', 0);
                preg_match('#content_(\d+)#', $content->id, $matches);
                $post_id = isset($matches[1]) ? $matches[1] : null;
                if ($post_id === null) continue;

                $quote = $content->find('div.quote-container');
                $in_reply_to = array();
                foreach ($quote as $q)
                {
                    $links = $q->find('a');
                    foreach ($links as $l)
                    {
                        if (preg_match('/#post_(\d+)/', $l->href, $matches))
                        {
                            $in_reply_to[] = $matches[1];
                        }
                    }
                }
                $in_reply_to = implode(',', $in_reply_to);

                $body = array();
                if (count($content->children()) > 0)
                {
                    foreach ($content->children() as $child)
                    {
                        if ($child->tag !== 'p') continue;

                        $body[] = strip_tags(utf8_entities_decode(html_entity_decode($child->innertext)));
                    }
                }
                if (count($body) == 0 && count($content->nodes) > 0)
                {
                    foreach ($content->nodes as $child)
                    {
                        if ($child->tag === 'text')
                        {
                            $body[] = strip_tags(utf8_entities_decode(html_entity_decode($child->innertext)));
                        }
                    }
                }
                $body = implode("\n", $body);

                $stmt2 = $db->prepare('INSERT INTO ' . TABLE_POSTS . ' VALUES(:id, :in_reply_to, :topic_id, :user_id, :date, :body);');
                if (!$stmt2->execute(array(
                    ':id' => $id,
                    ':in_reply_to' => $in_reply_to,
                    ':topic_id' => $row->id,
                    ':user_id' => $user_id,
                    ':date' => $date,
                    ':body' => $body,
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
                break;
            }
        }
        continue;
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
