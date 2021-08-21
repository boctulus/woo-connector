<?php
/*
Plugin Name: Woo connector
Description: API Rest alternativa
Version: 1.0.0
Author: boctulus@gmail.com <Pablo>
*/

namespace connector;

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


/*
    Installer
*/
function connector_installer(){
    include('installer.php');
}

register_activation_hook(__file__, 'connector_installer');


/*
    Panel administraitivo
*/
if ( is_admin() ) {
    add_action( 'admin_menu', 'connector\add_products_menu_entry', 100 );
}


function add_products_menu_entry() {
    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Product Grabber' ),
        __( 'Grab New' ),
        'manage_woocommerce', // Required user capability
        'ddg-product',
        'connector\generate_grab_product_page'
    );
}

function generate_grab_product_page() {
    if (!current_user_can('administrator'))  {
        wp_die( __('Su usuario no tiene permitido acceder') );
    }

    ?>
        <h3>WebHooks</h3>

        <button>Actualizar</button>
        <p></p>

        <div id="connector_webhooks"></div>
    <?php
}



class Connector
{
    static function getApiKeys($vendor_slug = null, $shop = null){
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
                'api_secret'     => $fields[2] ?? null,
                'api_ver'        => $fields[3] ?? null,
                'shop'           => $fields[4] ?? null
            ];

            if ($vendor_slug != null){
                if ($row['slug'] == $vendor_slug){
                    return $row;
                }
            }

            if ($shop != null){
                if ($row['shop'] == $shop){
                    return $row;
                }
            }

            $arr[] = $row;
        }
    
        return $arr;
    }

}


