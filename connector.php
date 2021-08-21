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
    Includes
*/

function enqueues() 
{  
     #wp_register_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js');
	wp_register_script('bootstrap', Files::get_rel_path(). 'assets/js/bootstrap/bootstrap.bundle.min.js');
    wp_enqueue_script('bootstrap');

	wp_register_style('bootstrap', Files::get_rel_path() . 'assets/css/bootstrap/bootstrap.min.css');
    #wp_register_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap');

	#wp_register_script('fontawesome', 'https://kit.fontawesome.com/813f54acc9.js');
	wp_register_script('fontawesome', Files::get_rel_path(). 'assets/js/fontawesome-5.js');
    wp_enqueue_script('fontawesome');

	wp_register_style('cotizo', Files::get_rel_path() . 'assets/css/cotizo.css');
    wp_enqueue_style('cotizo');


	wp_register_script('connector_js', Files::get_rel_path(). 'assets/js/connector.js');
    wp_enqueue_script('connector_js');
}

add_action( '\admin_enqueue_scripts', 'enqueues');




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
    <script>
        //  Mover de acá 

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById("wh_connector_form").addEventListener('submit', function(event){
				register_webhooks();
				event.preventDefault();
				return;
			});
        });

        function register_webhooks(){
            /*
			jQuery(document).ajaxSend(function() {
				jQuery("#overlay").fadeIn(300);　
			});		
            */			
					
			let url = '/index.php/wp-json/connector/v1/shops'; 

			var settings = {
                "url": url,
                "method": "GET",
                "timeout": 0,
                "headers": {
                    "Content-Type": "text/plain"
                }
			};

			jQuery.ajax(settings)
			.done(function (response) {

                //console.log(response);

                /*
                    [
                        {
                            "vendor": {
                                "url": "https://f920c96f987d.ngrok.io",
                                "slug": "hupit",
                                "cms": "shopi",
                                "enabled": true
                            },
                            "shop": "act-and-beee"
                        }
                    ]
                */

                for (var i=0; i<response.length; i++){
                    let shop   = response[i]['shop']; // "act-and-beee"
                    let vendor = response[i]['vendor']['slug']; 

                    let url = '/index.php/wp-json/connector/v1/webhooks/register'; 

                    let data = JSON.stringify({ shop : shop });

                    var settings = {
                        "url": url,
                        "method": "POST",
                        "timeout": 0,
                        "headers": {
                            "Content-Type": "text/plain"
                        },
				        "data": data
                    };

                    jQuery.ajax(settings)
                    .done(function (response) {
                        console.log(response);

                        /*
                            {
                                "weboook_product_create": null,
                                "weboook_product_update": null,
                                "weboook_product_delete": null
                            }
                        */

                        if (response["weboook_product_create"] != null){
                            console.log("WebHook para 'product create' listo para " + vendor);
                        } 

                        if (response["weboook_product_update"] != null){
                            console.log("WebHook para 'product update' listo para " + vendor);
                        } 

                        if (response["weboook_product_delete"] != null){
                            console.log("WebHook para 'product delete' listo para " + vendor);
                        } 

                    })
                    .fail(function (jqXHR, textStatus) {
                        console.log(jqXHR);
                        console.log(textStatus);
                        //addNotice('Error desconocido', 'danger', 'warning', 'alert_container', true);
                    });

                }


                /*
				setTimeout(function(){
					jQuery("#overlay").fadeOut(300);
				},500);
                */			
			})
			.fail(function (jqXHR, textStatus) {
				console.log(jqXHR);
				console.log(textStatus);
				//addNotice('Error desconocido', 'danger', 'warning', 'alert_container', true);
			});
		}
    </script>


        <h3>WebHooks</h3>

        <form id="wh_connector_form">
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>

        <p></p>

        <div id="connector_webhooks">
            <?php
                

                //dd(, 'VENDORS');
                //dd();
            ?>

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


