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
    exit; // Exit if accessed directly
}

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

require __DIR__ . '/libs/Debug.php';
require __DIR__ . '/libs/Files.php';
require __DIR__ . '/libs/Strings.php';


/**
 * Check if WooCommerce is active
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}	

/*
function connector_installer(){
    include('installer.php');
}

register_activation_hook(__file__, 'connector_installer');
*/


