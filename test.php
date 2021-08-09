<?php

use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../../wp-load.php';
include_once(__DIR__ . '/../../../wp-admin/includes/image.php' );

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}


class Test
{
   	
    // /wp-content/plugins/connector/test.php
    function create(){
        $arr = include(__DIR__ . '/logs/product_dump.php');

        $product_id = Products::createProduct($arr);
        dd($product_id, 'product_id');
    }
}


$test = new Test();

//$test->delete_all_products();
$test->create();
//$test->test_set_images();


//