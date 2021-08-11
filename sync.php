<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;

#require __DIR__ . '/libs/Debug.php';
#require __DIR__ . '/libs/Files.php';
#require __DIR__ . '/libs/Strings.php';
#require __DIR__ . '/config/config.php';
include __DIR__ . '/../../../wp-load.php';


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

function getVendors(){
    $list  = file_get_contents(__DIR__ . '/config/vendors.txt');
    $lines = explode(PHP_EOL, $list);

    $arr = [];
    foreach ($lines as $line){
        $line = trim($line);

        $line   = str_replace("\t", " ", $line);
        $line   = preg_replace('!\s+!', ' ', $line);
        $fields = explode(' ', $line);

        $arr[] = [
            'slug' => $fields[0],
            'url'  => $fields[1]
        ];
    }

    return $arr;
}

// 
function hasVendor($vendor, $pid){
    global $wpdb;

    $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}term_taxonomy`  as TT
    INNER JOIN `{$wpdb->prefix}terms` as T ON TT.term_id = T.term_id
    INNER JOIN `{$wpdb->prefix}term_relationships` as TR ON TT.term_taxonomy_id = TR.term_taxonomy_id
    WHERE T.slug = '$vendor' AND object_id = $pid";

    return $wpdb->get_var($sql) != 0;
}

function removeVendor($vendor, $pid){
    global $wpdb;

    $sql = "SELECT TR.term_taxonomy_id as tt_id FROM `{$wpdb->prefix}term_relationships` as TR 
	INNER JOIN `{$wpdb->prefix}term_taxonomy` as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id 
	INNER JOIN `{$wpdb->prefix}terms` as T ON T.term_id = TT.term_id
	WHERE object_id = $pid AND slug='$vendor'";

    $tt_id = $wpdb->get_var($sql);

    $sql = "DELETE FROM `{$wpdb->prefix}term_relationships` WHERE `object_id` = $pid AND `term_taxonomy_id` = $tt_id";

    $ok = $wpdb->query($sql);

    if (!$ok){
        return false;
    }

    updateCount($vendor);

    return true;
}

function addVendor($vendor, $pid){
    global $wpdb;

    $sql = "SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = '$vendor'";
    $vendor_id = $wpdb->get_var($sql);

    $sql = "INSERT INTO `{$wpdb->prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($pid, $vendor_id, '0');";
   
    $ok = $wpdb->query($sql);
    
    if (!$ok){
        return false;
    }

    updateCount($vendor);

    return true;
}

function updateCount($vendor){
    global $wpdb;

    $sql = "SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = '$vendor'";
    $vendor_id = $wpdb->get_var($sql);

    $sql = "SELECT COUNT(*) as count FROM `wp_term_relationships` as TR 
    INNER JOIN `wp_term_taxonomy` as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id 
    INNER JOIN `wp_terms` as T ON T.term_id = TT.term_id
    WHERE slug='$vendor';";

    $count = $wpdb->get_var($sql);

    $sql = "UPDATE  `wp_term_taxonomy` SET count = $count WHERE term_id = $vendor_id";
    return $wpdb->query($sql);
}


#dd(hasVendor('act-and-be', 786));
#addVendor('act-and-be', 786);
#dd(hasVendor('act-and-be', 786));
#exit;
#

$ok = removeVendor('act-and-be', 786);
dd($ok);



#dd(getVendors());



