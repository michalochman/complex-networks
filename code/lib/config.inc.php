<?php

date_default_timezone_set('UTC');

$db_host = 'localhost';
$db_name = 'scur_mgr';
$db_user = 'complex';
$db_pass = 'networks';
$dsn = sprintf('mysql:dbname=%s;host=%s', $db_name, $db_host);

define('BASE_URL', 'http://www.head-fi.org');
define('TABLE_FORUMS', 'headfi_forums');
define('TABLE_TOPICS', 'headfi_topics');
define('TABLE_POSTS', 'headfi_posts');
define('TABLE_USERS', 'headfi_users');
define('TABLE_WORDS', 'headfi_words');
define('TABLE_WORDS_RELATIONSHIPS', 'headfi_words_relationships');
define('TABLE_KEYWORDS', 'keywords');
define('TABLE_STOPWORDS', 'stopwords');
