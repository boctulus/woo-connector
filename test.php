<?php

namespace connector;

use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Products;
use connector\libs\Strings;
use connector\libs\Sync;
use connector\libs\WCFM_utils;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Products.php';
require_once __DIR__ . '/libs/Sync.php';
require_once __DIR__ . '/libs/WCFM_utils.php';

include_once __DIR__ . '/../../../wp-load.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$base_url   = 'https://f920c96f987d.ngrok.io'; // Ojo: cambia

$shop       = 'act-and-beee';
$api_key    = 'f2eefde7fca44c9f26abf7f913dba303';
$api_secret = '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';
$api_ver    = '2021-07';



//test_create_webooks($shop, $api_key, $api_secret, $api_ver);


/*
$rows = array (
    0 => 
    array (
      'id' => 63,
      'type' => 'simple',
      'name' => 'Ooooo 212',
      'slug' => 'ooooo',
      'status' => 'publish',
      'featured' => false,
      'catalog_visibility' => 'visible',
      'description' => 'oooooo',
      'short_description' => 'short',
      'sku' => 'ooo-212',
      'price' => '888',
      'regular_price' => '',
	  'attributes' => [],
      'image' => 
      array (
        0 => 'http://woo2.lan/wp-content/uploads/2021/07/pocketpantswhite_mariamalo4-683x1024.jpg',
        1 => 683, 
        2 => 1024,
        3 => true,
	  )
    ),
  );  


$row = $rows[0];
$sku = $row['sku'];

$pid = wc_get_product_id_by_sku($sku);

if (!empty($pid)){
	dd("Actualizando...");
	Products::updateProductBySku($row);
} else {
	$pid = Products::createProduct($row);
}

dd($pid);
*/

delete_all_webhooks();

//dd(Sync::getVendors(true, 'WC'));

/*
$sku = '102064';
$pid = wc_get_product_id_by_sku($sku);
dd($pid);
*/


//dd(WCFM_utils::is_active());
//dd(WCFM_utils::is_wcfm_active());

#dd(Sync::getVendorPlugin());
#dd(Sync::getCurrentVendor(77));



//$res = Sync::getDataFromShopify('vendorxyz');
//dd($res);

//dd(Sync::processDataFromShopify());


//Products::deleteAllProducts();


//dd(Sync::getInitialDataFromWooCommerce('actandbe'));
//Sync::processInitialDataFromWooCommerce();