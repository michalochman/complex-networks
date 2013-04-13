<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';
require_once __DIR__ . '/lib/Crawler.class.php';

try
{
//    $db = new PDO($dsn, $db_user, $db_pass);

    $keywords = array();

    $b = new Crawler();
//    $b->get('http://www.epinions.com/Headphones--~all/sec_~product_list/pp_~1');
    $b->get('http://www.epinions.com/Headphones/skp_~1/dl_~/search_vertical_~all/search_string_~headphones');
    while (!$b->responseIsError())
    {
        var_dump($b->getUrlInfo());
        $d = $b->getResponseSimpleDom();
        $a = $d->find('.productInfo h2 a');

        foreach ($a as $t)
        {
            $text = strip_tags(utf8_entities_decode(html_entity_decode($t->innertext)));
            file_put_contents('test.txt', $text . PHP_EOL, FILE_APPEND);
            $tokens = array();
//            $tokens = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_NO_EMPTY);
//            $tokens = array_filter($tokens);

            foreach ($tokens as $token)
            {
                if (($word = prepareWord($token)) === false) continue;
                if (strlen($word) < 2) continue;
                if (in_array($word, $keywords)) continue;

                $keywords[] = $word;

//                $stmt = $db->prepare('INSERT INTO ' . TABLE_KEYWORDS . ' VALUES(:id, :word);');
//                $stmt->execute(array(
//                    ':id' => null,
//                    ':word' => $word,
//                ));
                file_put_contents('test.txt', $word . PHP_EOL, FILE_APPEND);
            }
        }

        try
        {
            $b->click('Next >>');
        }
        catch (Exception $e)
        {
            break;
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

