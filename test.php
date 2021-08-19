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


function getCollectionsByProductId($shop, $product_id, $api_key, $api_secret, $api_ver)
{
	$endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/collects.json?product_id=$product_id";

	$res = Url::consume_api($endpoint, 'GET');

	if (!isset($res['data']["collects"])){
		return;
	}
	
	$col_ids = array_column($res['data']["collects"], "collection_id");

	$coll_names = [];
	foreach ($col_ids as $col_id){
		$endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/custom_collections/$col_id.json";

		$res = Url::consume_api($endpoint, 'GET');
		$obj = $res['data']["custom_collection"];

		if ($obj['handle'] != "frontpage"){
			$coll_names[] = $obj['title'];
		}
	}

	return $coll_names;
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

function test(){
	// Solo para testing:
	global $base_url;
	global $shop;
	global $api_key;
	global $api_secret;
	global $api_ver;

	include 'logs/dump_2.php';

	$pid = $a['id'];

	$a['sku'] = $a['handle'];

	$a['name'] = $a['title'];
	unset($a['title']);

	$a['description'] = $a['body_html'];

	// Visibility ('hidden', 'visible', 'search' or 'catalog')
	if ($a['published_scope'] == 'web'){
		$a['visibility'] = 'visible';
	} else {
		$a['visibility'] = 'hidden';
	}	

	/*
		status

		En WooCommerce puede ser publish, draft, pending
		En Shopify serían active, draft, archived
	*/

	if ($a['status'] == 'pending'){
		$a['status'] = 'draft';
	} elseif ($a['status'] == 'publish'){
		$a['status'] = 'active';
	} elseif ($a['status'] == 'archived'){
		$a['status'] = 'draft';
	}
	
	/*
		tags

		En WooCommerce es un array
		En Shopify están separados por ", "
	*/

	$tags = explode(',', $a['tags']);
	
	foreach ($tags as $k => $tag){
		$tags[$k] = trim($tag);
	}

	$a['tags'] = $tags;


	// Categories

	$a['categories'] = [];

	if (isset($a["product_type"]) && !empty($a["product_type"])){
		$a['categories'] = 	$a["product_type"];
	}

	$a['categories'] = array_merge($a['categories'], getCollectionsByProductId($shop, $pid, $api_key, $api_secret, $api_ver));

	dd($a);
 
	//$pid = Products::createProduct($row);
}


//test();


include __DIR__ . '/logs/response.php';

$data = $res['data'];

$row = $data[0];
$sku = $row['sku'];

$pid = wc_get_product_id_by_sku($sku);


if (!empty($pid)){
	Products::updateProductBySku($row);
} else {
	$pid = Products::createProduct($row);
}