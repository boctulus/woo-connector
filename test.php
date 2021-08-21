<?php

namespace connector;

use connector\libs\Debug;
use connector\libs\Url;
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

function createWebhook($shop, $entity, $operation, $api_key, $api_secret, $api_ver, $base_url, $check_before = true){
	global $wpdb;

	if ($check_before && webHookExists($shop, $entity, $operation, $api_key, $api_secret, $api_ver)){
		return;
	}

	$topic    = "$entity/$operation";
	$endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/webhooks.json";

	$body = [
			"webhook" => [
			"topic"   => $topic,
			"address" => $base_url . "/index.php/wp-json/connector/v1/webhooks/{$entity}_{$operation}",
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


function test_create_wh(){
	// Solo para testing:
	global $base_url;
	global $shop;
	global $api_key;
	global $api_secret;
	global $api_ver;

	$ok = createWebhook($shop, 'products', 'create', $api_key, $api_secret, $api_ver, $base_url);
	dd($ok);

	$ok = createWebhook($shop, 'products', 'update', $api_key, $api_secret, $api_ver, $base_url);
	dd($ok);

	$ok = createWebhook($shop, 'products', 'delete', $api_key, $api_secret, $api_ver, $base_url);
	dd($ok);
}



/*
	array (
		'slug' => 'hupit',
		'api_key' => 'f2eefde7fca44c9f26abf7f913dba303',
		'api_secret' => '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',
		'api_ver' => '2021-07',
		'shop' => 'act-and-beee',
	)
*/
$api = Connector::getApiKeys('hupit');


include 'logs/response2.php';

$rows = adaptToShopify($a, $api['shop'], $api['api_key'], $api['api_secret'], $api['api_ver']);

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

