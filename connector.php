<?php
/*
Plugin Name: Woo connector
Description: API Rest alternativa
Version: 1.125
Author: boctul.us@gmail.com <Pablo>
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
require_once __DIR__ . '/ajax_woo.php';


/**
 * Check if WooCommerce is active
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}	


/*
    Debug del vendor para WCFM

*/
add_action('admin_init', 'connector\in_admin_header_product_page_edit');

function in_admin_header_product_page_edit() {
    $config = include (__DIR__ . '/config/config.php');

    if (!$config['debug']){
        return;
    }

    $action = $_GET['action'] ?? null;

    if ($_SERVER['SCRIPT_NAME'] == '/wp-admin/post.php' && $action == 'edit'){
        ?>
            <script>
                console.log('Aca mostrar vendor_slug');
            </script>
        <?php
    }    
}

/*
    Installer
*/

function connector_installer(){
    include('installer.php');
}

register_activation_hook(__file__, 'connector\connector_installer');


/*  
    Enqueues
*/

add_action( 'admin_enqueue_scripts', function($hook) {
    /*
        Restrinjo la carga solo a este plugin
    */

    if ($hook != 'product_page_woo-connector'){
        return;
    }

	wp_register_script('bootstrap', Files::get_rel_path(). 'assets/js/bootstrap/bootstrap.bundle.min.js');
    wp_enqueue_script('bootstrap');

	wp_register_style('bootstrap', Files::get_rel_path() . 'assets/css/bootstrap/bootstrap.min.css');
    wp_enqueue_style('bootstrap');

	#wp_register_script('fontawesome', Files::get_rel_path(). 'assets/js/fontawesome-5.js');
    #wp_enqueue_script('fontawesome');

	wp_register_script('connector_js', Files::get_rel_path(). 'assets/js/main.js');
    wp_enqueue_script('connector_js');

    wp_register_style('loading', Files::get_rel_path() . 'assets/css/ajax.css');
    wp_enqueue_style('loading');
} );

add_action( 'admin_enqueue_scripts', function($hook) {
    wp_register_script('notices_js', Files::get_rel_path(). 'assets/js/notices.js');
    wp_enqueue_script('notices_js');
});


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
        'woo-connector',
        'connector\connector_admin_panel'
    );
}

function connector_admin_panel() {
    if (!current_user_can('administrator'))  {
        wp_die( __('Su usuario no tiene permitido acceder') );
    }

    ?>
        <div class="container-fluid mt-3">
            <h3>Connector</h3>
            
            <div class="row">
                <div class="col-xs-12 col-sm-9 col-md-6">

                    <div class="alert alert-secondary mt-3" role="alert">
                        Es necesario actualizar luego de agregar una nueva tienda de Shopify para registrar los WebHooks correspondientes y así habilitar los eventos.
                    </div>

                    <form id="wh_connector_form" class="mt-3 mb-3">
                        <button type="submit" class="btn btn-primary mt-1">Actualizar</button>
                    </form>

                    <div id="overlay">
                        <div class="cv-spinner">
                            <span class="spinner"></span>
                        </div>
                    </div>  

                    <div id="alert_container"></div>                    
                </div>
            </div>

            
        </div>
       
    <?php
}


