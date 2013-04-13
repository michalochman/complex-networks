<?php 

require __DIR__ . '/vendor/html_convert_entities.php';

function __autoload($classname)
{
	if (file_exists($file = sprintf("%s/%s.class.php", __DIR__, $classname)))
	{
		include_once $file;
	}
}

function randf($min, $max)
{
	return floatval(mt_rand($min, $max));
}

function mt_randf($min = 0, $max = 1, $precision = 2) {
	return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $precision);
}

function utf8_entities_decode($str)
{
	//decode decimal HTML entities added by web browser
	$str = preg_replace('/&#\d{2,5};/ue', "utf8_entity_decode('$0')", $str);
	//decode hex HTML entities added by web browser
	$str = preg_replace('/&#x([a-fA-F0-7]{2,8});/ue', "utf8_entity_decode('&#'.hexdec('$1').';')", $str);

	return $str;
}

$cst = 'cst';
function cst($constant){
	return $constant;
}

//callback function for the regex
function utf8_entity_decode($entity){
	$convmap = array(0x0, 0x10000, 0, 0xfffff);
	return mb_decode_numericentity($entity, $convmap, 'UTF-8');
}

function prepareWord($word)
{
	// skip words shorter than 2 characters
	//if (strlen($word) < 2) return false;
	// skip links
	if (strpos($word, '://') !== false) return false;
	// transliterate word to ascii charset
	$word = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
	// remove all characters that are not letters or digits
	//$word = preg_replace('#[\W_]#', '', $word);
	// user lowercase version of the word
	$word = strtolower($word);

	return $word;
}

// COLOURS ---

$black = new ColorRGB(0.0, 0.0, 0.0);
$gray = new ColorRGB(177.0, 177.0, 177.0);
$red = new ColorRGB(255.0, 0.0, 0.0);
$blue = new ColorRGB(0.0, 0.0, 255.0);
$green = new ColorRGB(0.0, 255.0, 0.0);
$magenta = new ColorRGB(255.0, 0.0, 255.0);
$cyan = new ColorRGB(0.0, 255.0, 255.0);
$orange = new ColorRGB(255.0, 127.0, 0.0);

// POINTS POSITIONING ---
$pnn = array(-2.5, 7.5);
$pnw = array(-10.0, 5.0);
$pww = array(-12.5, -2.5);
$pne = array(5.0, 5.0);
$pss = array(-2.5, -12.5);
$psw = array(-10.0, -10.0);
$pse = array(5.0, -10.0);
$pee = array(7.5, -2.5);