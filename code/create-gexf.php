<?php

require_once __DIR__ . '/lib/helpers.inc.php';
require_once __DIR__ . '/lib/config.inc.php';

//if ($argc !== 3) die('usage: ' . $argv[0] . ' limit offset');

ini_set('memory_limit', '4G');

$add_spells = false;

try
{
    $db = new PDO($dsn, $db_user, $db_pass);
    //$stmt = $db->prepare('SELECT * FROM ' . TABLE_WORDS_RELATIONSHIPS . ' ORDER BY id ASC LIMIT ' . intval($argv[1]) . ' OFFSET ' . intval($argv[2]) . ';');
    $sql = <<<EOSQL
SELECT
    r.*,
    DATE_FORMAT(r.used, '%Y-%m-%d %H:00:00') as date,
    u.name as user_name,
    w.*
FROM {$cst(TABLE_WORDS_RELATIONSHIPS)} r
JOIN {$cst(TABLE_USERS)} u ON r.user_id = u.id
JOIN {$cst(TABLE_WORDS)} w ON r.word_id = w.id
LIMIT {$argv[2]},{$argv[1]};
EOSQL;
    $stmt = $db->prepare($sql);
    $result = $stmt->execute();

    if ($result === false) throw new Exception('Empty resultset.');

    $attributes = <<<ATTRS
        <attributes class="node">
            <attribute id="0" title="type" type="string">
                <default>word</default>
            </attribute>
        </attributes>
ATTRS;

    $user_nodes = array();
    $word_nodes = array();
    $edges_serial = array();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ))
    {
        $edge = sprintf('%d/%d', $row->user_id, $row->word_id);
        if (!array_key_exists($edge, $edges_serial))
        {
            $edges_serial[$edge] = 0;
        }
        $edges_serial[$edge] += 1;

        if (!array_key_exists($row->user_id, $user_nodes))
        {
            $user_nodes[$row->user_id] = $row->user_name;
        }

        if (!array_key_exists($row->word_id, $word_nodes))
        {
            $word_nodes[$row->word_id] = array(
                'used' => array(),
                'word' => $row->word,
            );
        }
        if (!array_key_exists($row->date, $word_nodes[$row->word_id]['used']))
        {
            $word_nodes[$row->word_id]['used'][$row->date] = 0;
        }
        $word_nodes[$row->word_id]['used'][$row->date] += 1;
    }

    $nodes = '';
    foreach ($user_nodes as $id => $label)
    {
        $nodes .= sprintf("\t\t\t<node id=\"%d\" label=\"%s\">\n\t\t\t\t<attvalues>\n\t\t\t\t\t<attvalue for=\"0\" value=\"user\"/>\n\t\t\t\t</attvalues>\n\t\t\t</node>\n", $id, html_convert_entities(htmlentities($label)));
    }
    foreach ($word_nodes as $id => $node)
    {
        $label = $node['word'];
        $spells = '';
        if ($add_spells)
        {
            foreach ($node['used'] as $date => $weight)
            {
                $spells .= sprintf("\t\t\t\t\t<spell start=\"%s\" end=\"%s\"/>\n", $date, $date);
            }
            $spells = sprintf("\n\t\t\t\t<spells>\n%s\n\t\t\t\t</spells>\n\t\t\t", rtrim($spells, "\n"));
        }
        $attvalues = <<<ATTVS

\t\t\t\t<attvalues>
\t\t\t\t\t<attvalue for="0" value="word"/>
\t\t\t\t</attvalues>
\t\t\t
ATTVS;
        $attvalues = '';
        $nodes .= sprintf("\t\t\t<node id=\"%d\" label=\"%s\">%s%s</node>\n", $id, html_convert_entities(htmlentities($label)), $attvalues, $spells);
    }
    $nodes = trim($nodes, "\n");

    $edges = '';
    foreach ($edges_serial as $edge => $weight)
    {
        $edge = explode('/', $edge);
        $edges .= sprintf("\t\t\t<edge source=\"%d\" target=\"%d\" weight=\"%d\"/>\n", $edge[0], $edge[1], $weight);
    }
    $edges = trim($edges, "\n");

    //var_dump(trim($nodes));exit;

    $gexf = file_get_contents(__DIR__ . '/templates/gexf.tpl');

    $gexf = str_replace(
        array(
            '$LastModifiedDate',
            '$Creator',
            '$Description',
            '$GraphMode',
            '$GraphDefaultEdgeType',
            '$GraphTimeFormat',
            '$Attributes',
            '$Nodes',
            '$Edges',
        ),
        array(
            date('Y-m-d'),
            'michoch',
            'A word/agent network changing over time',
            'dynamic',
            'undirected',
            'date',
            $attributes,
            $nodes,
            $edges,
        ),
        $gexf
    );
    echo $gexf;
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
