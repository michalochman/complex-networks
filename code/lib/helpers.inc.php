<?php 

require __DIR__ . '/vendor/html_convert_entities.php';

function __autoload($classname) {
    if (file_exists($file = sprintf("%s/%s.class.php", __DIR__, $classname))) {
        include_once $file;
    }
}

function randf($min, $max) {
    return floatval(mt_rand($min, $max));
}

function mt_randf($min = 0, $max = 1, $precision = 2) {
    return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $precision);
}

function utf8_entities_decode($str) {
    //decode decimal HTML entities added by web browser
    $str = preg_replace('/&#\d{2,5};/ue', "utf8_entity_decode('$0')", $str);
    //decode hex HTML entities added by web browser
    $str = preg_replace('/&#x([a-fA-F0-7]{2,8});/ue', "utf8_entity_decode('&#'.hexdec('$1').';')", $str);

    return $str;
}

$cst = 'cst';
function cst($constant) {
    return $constant;
}

//callback function for the regex
function utf8_entity_decode($entity) {
    $convmap = array(0x0, 0x10000, 0, 0xfffff);
    return mb_decode_numericentity($entity, $convmap, 'UTF-8');
}

function prepareWord($word) {
    // skip words shorter than 3 characters
    if (strlen($word) < 3) return false;
    // skip links
//    if (strpos($word, '://') !== false) return false;
    // transliterate word to ascii charset
    $word = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
    // remove all characters that are not letters or digits
    //$word = preg_replace('#[\W_]#', '', $word);
    // user lowercase version of the word
    $word = strtolower($word);

    return $word;
}

function gcd($a, $b) {
    while ($b != 0) {
        $m = $a % $b;
        $a = $b;
        $b = $m;
    }
    return $a;
}

// ----
// collaborative similarity
// ----

function print_nn_degree($links, $type, $n_type, $key) {
    printf("%s\n", nn_degree($links, $type, $n_type, $key));
}

function print_degree($links, $users, $objects) {
    foreach ($users as $user) {
        printf("k_%s = %d\n", $user, degree($links, 'users', $user));
    }
    echo PHP_EOL;
    foreach ($objects as $object) {
        printf("d_%s = %d\n", $object, degree($links, 'objects', $object));
    }
}

function print_jaccard_similarity($links, $type, $n_type, $key_a, $key_b) {
    printf("s_%s_%s = %s\n", $key_a, $key_b, jaccard_similarity($links, $type, $n_type, $key_a, $key_b));
}

function degree($links, $type, $key) {
    return count($links[$type][$key]);
}

function average_degree($links, $type) {
    $degree = 0;
    foreach ($links[$type] as $link) {
        $degree += count($link);
    }
    return floatval($degree) / floatval(count($links[$type]));
}

function nn_degree($links, $type, $n_type, $key) {
    $degree = degree($links, $type, $key);
    $nn_degree = 0;
    foreach ($links[$type][$key] as $n_key) {
        $nn_degree += degree($links, $n_type, $n_key);
    }
    return sprintf('%d/%d', $nn_degree, $degree);
}

function jaccard_similarity($links, $type, $key_a, $key_b, $string=false) {
    $intersection = count(array_intersect($links[$type][$key_a], $links[$type][$key_b]));
    $union = count(array_merge($links[$type][$key_a], array_diff($links[$type][$key_b], $links[$type][$key_a])));
    $gcd = gcd($intersection, $union);
    if ($string) {
        return sprintf('%d/%d', $intersection/$gcd, $union/$gcd);
    }
    return floatval($intersection)/floatval($union);
}

function collaborative_similarity($links, $type, $n_type, $key, $string=false) {
    $degree = degree($links, $type, $key);
    if ($degree <= 1) return 0;
    $similarity_sum = 0;
    foreach ($links[$type][$key] as $n_key_1) {
        foreach ($links[$type][$key] as $n_key_2) {
            if ($n_key_1 === $n_key_2) continue;
            $similarity_sum += jaccard_similarity($links, $n_type, $n_key_1, $n_key_2);
        }
    }
    if ($string) {
        $precision = 1e3;
        $new_similarity_sum = round($similarity_sum * $degree*($degree-1) * $precision);
        $gcd = gcd($new_similarity_sum, $degree*($degree-1) * $precision);
        $new_similarity_sum /= $gcd;
        return sprintf('%d/%d', $new_similarity_sum, $degree*($degree-1)*round($new_similarity_sum/$similarity_sum));
    }
    return $similarity_sum / ($degree*($degree-1));
}

function average_jaccard_similarity($links, $type, $n_type, $string=false) {
    $nodes = 0;
    $similarity_sum = 0;
    $total_count = count($links[$type]);
    $current_count = 0;
    foreach ($links[$type] as $key => $key_links) {
        foreach ($key_links as $n_key_1) {
            foreach ($key_links as $n_key_2) {
                $total_count++;
            }
        }
    }
    foreach ($links[$type] as $key => $key_links) {
        foreach ($key_links as $n_key_1) {
            foreach ($key_links as $n_key_2) {
                $current_count++;
                if ($n_key_1 === $n_key_2) continue;
                $nodes++;
                $similarity_sum += jaccard_similarity($links, $n_type, $n_key_1, $n_key_2);
                // print progress
//                echo sprintf("\r%d/%d (%.4f%%)", $current_count, $total_count, $current_count/floatval($total_count));
            }
        }
    }
    // reset progress print
//    echo "\r";
    if ($nodes === 0) return 0;
    if ($string) {
        $precision = 1e3;
        $new_similarity_sum = round($similarity_sum * $nodes * $precision);
        $gcd = gcd($new_similarity_sum, $nodes * $precision);
        $new_similarity_sum /= $gcd;
        return sprintf('%d/%d', $new_similarity_sum, $nodes*round($new_similarity_sum/$similarity_sum));
    }
    return $similarity_sum / $nodes;
}

function network_collaborative_similarity($links, $type, $n_type, $string=false) {
    $nodes = 0;
    $similarity_sum = 0;
    $total_count = count($links[$type]);
    $current_count = 0;
    foreach ($links[$type] as $key => $key_links) {
        $current_count++;
        if (degree($links, $type, $key) <= 1) continue;
        $nodes++;
        $collaborative_similarity = collaborative_similarity($links, $type, $n_type, $key);
        // print progress
//        echo sprintf("\r%d/%d (%.4f%%)", $current_count, $total_count, $current_count/floatval($total_count));
        $similarity_sum += $collaborative_similarity;
    }
    // reset progress print
//    echo "\r";
    if ($nodes === 0) return 0;
    if ($string) {
        $precision = 1e3;
        $new_similarity_sum = round($similarity_sum * $nodes * $precision);
        $gcd = gcd($new_similarity_sum, $nodes * $precision);
        $new_similarity_sum /= $gcd;
        return sprintf('%d/%d', $new_similarity_sum, $nodes*($new_similarity_sum/$similarity_sum));
    }
    return $similarity_sum/$nodes;
}
