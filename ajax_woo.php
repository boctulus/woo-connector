<?php

use connector\libs\Sync;
use connector\libs\Files;

require_once __DIR__ . '/libs/Sync.php';
require_once __DIR__ . '/libs/Files.php';


function wc_init_product_load(){
    global $wpdb;

    $api_key = $_GET['api_key'] ?? null;

    if (empty($api_key)){
        return [
            'error' => "Falta API KEY"
        ];
    }

    $vendor_slug = Sync::getVendorFromApiKey($api_key);

    if (empty($vendor_slug)){
        return [
            'error' => "API KEY inválida"
        ];
    }

    if (!Sync::is_vendor(true, 'wc', $vendor_slug)){
        return [
            'error' => "Vendor $vendor_slug es inválido"
        ];
    }

    $sql = "INSERT IGNORE INTO `{$wpdb->prefix}initial_load`(`vendor_slug`, `cms`) VALUES ('$vendor_slug', 'wc')";
    $ok = $wpdb->query($sql);

    return [
        'data' => 'ok'
    ];
}

function wc_post_product(){
    global $wpdb;

    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (empty($data)){
        return [
            'error' => "No hay datos"
        ];
    }

    $headers = apache_request_headers();

    $api_key = $_GET['api_key'] ?? null;

    if (empty($api_key)){
        return [
            'error' => "Falta API KEY"
        ];
    }

    $vendor_slug = Sync::getVendorFromApiKey($api_key);

    if (empty($vendor_slug)){
        return [
            'error' => "API KEY inválida"
        ];
    }

    if (!Sync::is_vendor(true, 'wc', $vendor_slug)){
        return [
            'error' => "Vendor $vendor_slug es inválido"
        ];
    }


    //Files::dump($data);

    try {
        Sync::process($data, $vendor_slug);
    } catch (\Exception $e){
        $err = "Error al sincronizar producto automáticamente: " + $e->getMessage();
        Files::logger($err);

        return [
            'error' => $err
        ];
    }    

    return [
        'data' => 'ok'
    ];
}

 /*
    WooCommerce
*/
add_action( 'rest_api_init', function () {
   
	# GET /index.php/wp-json/connector/v1/woocommerce/products/init_load
	register_rest_route( 'connector/v1', '/woocommerce/products/init_load', array(
		'methods' => 'GET',
		'callback' => 'wc_init_product_load',
        'permission_callback' => '__return_true'
	) );

    # POST /index.php/wp-json/connector/v1/woocommerce/products
	register_rest_route( 'connector/v1', '/woocommerce/products', array(
		'methods' => 'POST',
		'callback' => 'wc_post_product',
        'permission_callback' => '__return_true'
	) );
});