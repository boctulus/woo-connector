<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../../wp-load.php';

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}


class Test
{
   	
    function __construct(){
        
    }

    // /wp-content/plugins/connector/test.php
    function create(){
        $product_id = create_product($args);
    }
}


$test = new Test();
$test->create();