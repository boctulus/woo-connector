<?php
/*
Plugin Name: Woo connector
Description: API Rest alternativa
Version: 1.0.0
Author: boctulus@gmail.com <Pablo>
*/

use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/shopify.php';


/**
 * Check if WooCommerce is active
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}	


function connector_installer(){
    include('installer.php');
}

register_activation_hook(__file__, 'connector_installer');


class Connector
{
    static function getApiKeys($vendor_slug = null){
        $list  = file_get_contents(__DIR__ . '/config/api_keys.txt');
        $lines = explode(PHP_EOL, $list);
    
        $arr = [];
        foreach ($lines as $line){
            $line = trim($line);
    
            if (empty($line) || $line[0] == '#' || $line[0] == ';'){
                continue;
            }
    
            $line   = str_replace("\t", " ", $line);
            $line   = preg_replace('!\s+!', ' ', $line);
            $fields = explode(' ', $line);
    
            $row = [
                'slug'           => $fields[0],
                'api_key'        => $fields[1],
                'api_secret'     => $fields[2],
                'api_ver'        => $fields[3] ?? null,
                'shop'           => $fields[4] ?? null
            ];

            if ($vendor_slug != null){
                if ($row['slug'] == $vendor_slug){
                    return $row;
                }
            }

            $arr[] = $row;
        }
    
        return $arr;
    }

}


