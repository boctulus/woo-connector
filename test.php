<?php

namespace connector;

use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Products;
use connector\libs\Strings;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Products.php';

include_once __DIR__ . '/../../../wp-load.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function getWebHook($shop, $entity, $operation, $api_key, $api_secret, $api_ver){
	static $webhooks = [];

	if (empty($webhooks)){
		$endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/webhooks.json";

		$res = Url::consume_api($endpoint, 'GET');

		if (empty($res)){
			return;
		}

		if (!isset($res["data"]["webhooks"])){
			return;
		}

		$webhooks = $res["data"]["webhooks"];
	}

	$topic    = "$entity/$operation";

	foreach ($webhooks as $wh){
		if ($wh["topic"] == $topic){
			return $wh;
		}
	}

	return false;
}

function webHookExists($shop, $entity, $operation, $api_key, $api_secret, $api_ver){
	$wh = getWebHook($shop, $entity, $operation, $api_key, $api_secret, $api_ver);
	return !empty($wh);
}

function createWebhook($shop, $entity, $operation, $api_key, $api_secret, $api_ver, $check_before = true){
	global $wpdb;

	if ($check_before && webHookExists($shop, $entity, $operation, $api_key, $api_secret, $api_ver)){
		return;
	}

	$topic    = "$entity/$operation";
	$endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/webhooks.json";


	$body = [
			"webhook" => [
			"topic"   => $topic,
			"address" => home_url() . "/index.php/wp-json/connector/v1/webhooks/{$entity}_{$operation}",
			"format"  => "json"
			]
	];

	$res = Url::consume_api($endpoint, 'POST', $body, [
		$api_key => $api_secret
	]);

	if (empty($res)){
		dd("Error al crear WebHook para $topic");
		return;
	}

	if (!isset($res['data']['webhook']) || isset($data['id'])){
		dd($res, 'response');
		dd("Error en la respuesta al crear WebHook para $topic");
		return;
	}

	$data = $res['data']['webhook'];

	$sql = "INSERT INTO `{$wpdb->prefix}shopi_webhooks` (`shop`, `topic`, `api_version`, `address`, `remote_id`, `created_at`) 
	VALUES ('$shop', '{$data['topic']}', '{$data['api_version']}', '{$data['address']}' , '{$data['id']}', '{$data['created_at']}')";

	$ok = $wpdb->query($sql);

	if (!$ok){
		dd("Error al almacenar WebHook");
		return;
	}

	return true;
}



$base_url   = 'https://f920c96f987d.ngrok.io'; // Ojo: cambia

$shop       = 'act-and-beee';
$api_key    = 'f2eefde7fca44c9f26abf7f913dba303';
$api_secret = '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';
$api_ver    = '2021-07';


function test_create_wh($shop, $api_key, $api_secret, $api_ver){
	$ok = createWebhook($shop, 'products', 'create', $api_key, $api_secret, $api_ver);
	dd($ok);

	$ok = createWebhook($shop, 'products', 'update', $api_key, $api_secret, $api_ver);
	dd($ok);

	$ok = createWebhook($shop, 'products', 'delete', $api_key, $api_secret, $api_ver);
	dd($ok);
}

test_create_wh($shop, $api_key, $api_secret, $api_ver);


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
