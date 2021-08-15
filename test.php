<?php

use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require __DIR__ . '/libs/Url.php';
require __DIR__ . '/libs/Products.php';

include __DIR__ . '/../../../wp-load.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

