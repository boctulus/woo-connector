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

/*
function connector_installer(){
    include('installer.php');
}

register_activation_hook(__file__, 'connector_installer');
*/

/*  
    Enqueues
*/

add_action( 'admin_enqueue_scripts', function($hook) {
	wp_register_script('bootstrap', Files::get_rel_path(). 'assets/js/bootstrap/bootstrap.bundle.min.js');
    wp_enqueue_script('bootstrap');

	wp_register_style('bootstrap', Files::get_rel_path() . 'assets/css/bootstrap/bootstrap.min.css');
    wp_enqueue_style('bootstrap');

	wp_register_script('fontawesome', Files::get_rel_path(). 'assets/js/fontawesome-5.js');
    wp_enqueue_script('fontawesome');

	wp_register_script('connector_js', Files::get_rel_path(). 'assets/js/main.js');
    wp_enqueue_script('connector_js');
} );



/*
    Panel administraitivo
*/
if ( is_admin() ) {
    add_action( 'admin_menu', 'connector\add_products_menu_entry', 100 );
}


function add_products_menu_entry() {
    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Product syncronizer' ),
        __( 'Connector' ),
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
        <div class="container-fluid mt-3">
            <h3>WebHooks</h3>
            
            <div class="row">
                <div class="col-xs-12 col-sm-9 col-md-6 col-lg-4">

                    <div class="alert alert-secondary mt-3" role="alert">
                        Es necesario actualizar luego de agregar una nueva tienda de Shopify para registrar los WebHooks correspondientes y así habilitar los eventos.
                    </div>

                    <form id="wh_connector_form" class="mt-3 mb-3">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </form>

                    <div id="connector_webhooks"></div>                    
                </div>
            </div>

            
        </div>
       
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


