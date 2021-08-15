<?php

use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);


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
 	
//echo Files::file_get_contents_curl('https://miserastral.com/wp-content/uploads/2021/01/libros-digitales.jpeg');

$args = array (
	'id' => 504,
	'type' => 'simple',
	'name' => 'Numerologia para iniciantes',
	'slug' => 'numerologia-para-iniciantes',
	'status' => 'publish',
	'featured' => false,
	'catalog_visibility' => 'visible',
	'description' => 'La relación entre los números y los seres humanos ha existido desde siempre. Los números nos definen de muchas maneras y aportan sentido a incontables aspectos de nuestra vida, la cual no se puede concebir sin las nociones numéricas. Estas giran en torno a la ciencia, pero también a otros recursos y métodos menos tangibles, pero que tienen suma importancia para la existencia.',
	'short_description' => 'Mi ser astral',
	'sku' => 'ppq',
	'price' => '0',
	'regular_price' => '10',
	'sale_price' => '0',
	'manage_stock' => false,
	'stock_quantity' => NULL,
	'stock_status' => 'instock',
	'is_sold_individually' => false,
	'weight' => '',
	'length' => '',
	'width' => '',
	'height' => '',
	'parent_id' => 0,
	'tags' => 
	array (
	),
	'categories' => 
	array (
	  0 => 
	  array (
		'name' => 'Descargables',
		'slug' => 'descargables-libros',
		'description' => '',
	  ),
	  1 => 
	  array (
		'name' => 'Libros',
		'slug' => 'libros',
		'description' => '',
	  ),
	  2 => 
	  array (
		'name' => 'Libros Gratis',
		'slug' => 'libros-gratis',
		'description' => '',
	  ),
	),
	'image' => 'https://miserastral.com/wp-content/uploads/2021/02/WhatsApp-Image-2021-02-01-at-11.37.04-PM-300x300.jpeg',
	'gallery_images' => 
	array (
	),
	'operation' => 'UPDATE',
  );  


Products::updateProductBySku($args);