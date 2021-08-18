<?php

use connector\libs\Url;
use connector\libs\Debug;
use connector\libs\Files;

require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/Debug.php';

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

function webhook_products_create(){
    $data = file_get_contents('php://input');
    Files::dump($data, 'product_' . $_SERVER['REQUEST_METHOD'] .'.txt');  

    // acá ocurre el procesamiento
}

function webhook_products_update(){
    $data = file_get_contents('php://input');
    Files::dump($data, 'product_' . $_SERVER['REQUEST_METHOD'] .'.txt');  
}

/*
    No funcionan

    https://community.shopify.com/c/Shopify-Apps/product-delete-webhook-not-working/td-p/574094/highlight/false
*/
function webhook_products_delete(){
    $data = file_get_contents('php://input');
    Files::dump($data, 'product_' . $_SERVER['REQUEST_METHOD'] .'.txt');  
}

add_action( 'rest_api_init', function () {
    #	GET /index.php/wp-json/connector/v1/webhooks/products_create
	register_rest_route( 'connector/v1', '/webhooks/products_create', array(
		'methods' => 'POST',
		'callback' => 'webhook_products_create',
        'permission_callback' => '__return_true'
	) );

    register_rest_route( 'connector/v1', '/webhooks/products_update', array(
		'methods' => 'POST,UPDATE',  // POST
		'callback' => 'webhook_products_update',
        'permission_callback' => '__return_true'
	) );

    register_rest_route( 'connector/v1', '/webhooks/products_delete', array(
		'methods' => 'GET,POST,DELETE',
		'callback' => 'webhook_products_delete',
        'permission_callback' => '__return_true'
	) );
} );


