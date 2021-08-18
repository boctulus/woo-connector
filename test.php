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


function createWebhook($shop, $entity, $operation){
	global $wpdb;

	$base_url = 'https://f920c96f987d.ngrok.io'; // Ojo: cambia

	// deben ir en el config
	$api_key    = 'f2eefde7fca44c9f26abf7f913dba303';
	$api_secret = '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';
	$api_ver    = '2021-07';

	$topic      = "$entity/$operation";

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
	//dd($data);

	$sql = "INSERT INTO `{$wpdb->prefix}shopi_webhooks` (`shop`, `topic`, `api_version`, `address`, `remote_id`, `created_at`) 
	VALUES ('$shop', '{$data['topic']}', '{$data['api_version']}', '{$data['address']}' , '{$data['id']}', '{$data['created_at']}')";

	$ok = $wpdb->query($sql);

	if (!$ok){
		dd("Error al almacenar WebHook");
		return;
	}

	return true;
}


#$ok = createWebhook('act-and-beee', 'products', 'create');
#$ok = createWebhook('act-and-beee', 'products', 'update');
#$ok = createWebhook('act-and-beee', 'products', 'delete');