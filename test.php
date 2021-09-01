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

//delete_all_webhooks();

//Products::deleteAllProducts();


//dd(Sync::getInitialDataFromWooCommerce('actandbe'));
//Sync::processInitialDataFromWooCommerce();