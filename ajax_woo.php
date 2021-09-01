<?php

use connector\libs\Sync;

require_once __DIR__ . '/libs/Sync.php';


function wc_init_product_load(){
    global $wpdb;

    $api_key = $_GET['api_key'] ?? null;

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
});