<?php

//namespace connector;

use connector\Connector;
use connector\libs\Url;
use connector\libs\Products;
use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Request;
use connector\libs\Strings;
use connector\libs\Sync;

require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/Products.php';
require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Request.php';
require_once __DIR__ . '/libs/Sync.php';


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
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


/*
	Convierte la estructura de productos de Shopify a la de WooCommerce
*/
function adaptToShopify(Array $a, $shop, $api_key, $api_secret, $api_ver){

	$pid = $a['id'];

	$a['name'] = $a['title'];
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
	} elseif ($a['status'] == 'active'){
		$a['status'] = 'publish';
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
		$a['categories'][] = 	$a["product_type"];
	}

	$a['categories'] = array_merge($a['categories'], getCollectionsByProductId($shop, $pid, $api_key, $api_secret, $api_ver));

	/*
		Variations as simple products
	*/

	$vars = [];
	foreach($a['variants'] as $k => $v){

		// atributos

		/*
			Deben seguir la estructura de los atributos para productos simples de WooCommerce:

			'attributes' => 
				array (
					'pa_talla' => 
					array (
					),
					'pa_color' => 
					array (
					),
				)
		*/		
		$attributes = [];

		foreach ($a['options'] as $i => $op){
			$name  = $op['name'];
			$value = $v['option' . ($i +1)];
			$term  = strtolower($name);

			if ($term == 'size'){
				$term = 'talla';
			} elseif ($term == 'style'){
				$term = 'estilo';
			} elseif ($term == 'title'){
				continue;
			}

			$term = 'pa_' . $term;

			$attributes[ $term ] = [ $value ];
		}

        $name = $a['name'];

        if ($v['title'] != 'Default Title'){
            $name = $name . ' - ' . $v['title'];
        }

		$vars[$k] = [
			'type'				=> 'simple',
			'name'       		=> $name,
			'description' 		=> $a['description'],
			'visibility'  		=> $a['visibility'],
			'status'      		=> $a['status'],
			'tags'        		=> $a['tags'],
			'categories'  		=> $a['categories'],
			'regular_price'		=> $v['price'],
			'sale_price'  		=> $v['compare_at_price'],
			'sku'         		=> $v['sku'],
			'weight'	  		=> $v['weight'],
			'stock_quantity' 	=> $v['inventory_quantity'],
            'manage_stock'      => $v['inventory_management'] !== NULL,
			//'tax_status'		=> $v['taxable'] ? 'taxable' : 'none',
			'attributes' 		=> $attributes
		];
       

        foreach ($a['images'] as $img){
            foreach ($img['variant_ids'] as $vid){
                if ($vid == $v['id']){
                    $vars[$k]['image'][0] = $img['src'];
                    $vars[$k]['image'][1] = $img['width'];
                    $vars[$k]['image'][2] = $img['height'];
                    break 2;
                }
            }
        }

	}

    // si es un producto "simple"
    if (isset($a['image']) && $a['variants'][0]['title'] == 'Default Title'){            
        $vars[0]['image'] = [
            $img['src'],
            $img['width'],
            $img['height']
        ];
    }
	
	return $vars;
}



/*
    When Shopify sends request to your webhook url. it will also send some extra details in HTTP header.

    You can find X-Shopify-Shop-Domain header which will contain domain name of store. From domain name you can extract name of store.

    it also contains X-Shopify-Hmac-SHA256 to verify aunthenticity of request.


    https://stackoverflow.com/questions/29567824/how-to-get-shopify-shop-id-inside-of-product-update-webhook
*/

function insert_or_update_products(){
    $config = include __DIR__ . '/config/config.php';

	if ($config['test_mode']){
		Files::logger("Shopify WebHook fired");
	}

    $data = file_get_contents('php://input');  
    $arr  = json_decode($data, true);  
   
    $headers  = Request::apache_request_headers();

    $shop_url = $headers['X-SHOPIFY-SHOP-DOMAIN'] ?? null;

    if (!Strings::endsWith('.myshopify.com', $shop_url)){
        Files::logger("WebHook reporta url de tienda de Shopify inválida $shop_url");
        return;
    }

    $shop = substr($shop_url, 0, strlen($shop_url) - strlen('.myshopify.com'));
    
    $api  = Sync::getApiKeys(null, $shop);

    $rows = adaptToShopify($arr, $api['shop'], $api['api_key'], $api['api_secret'], $api['api_ver']);

    if (empty($rows)){
        Files::logger("Error al recibir datos para shop $shop");
    }

    if ($config['test_mode']){
        Files::dump($arr, $arr['handle'] . '.txt'); 
        Files::dump($rows, $arr['handle'] . '_adaptado.txt'); 
    }


    foreach ($rows as $row){
		if (!isset($row['sku']) || empty($row['sku'])){
			continue;
		} 

        $sku = $row['sku'];

        $pid = wc_get_product_id_by_sku($sku);
    
        if (!empty($pid)){
            Products::updateProductBySku($row);
        } else {

			if (isset($config['status_at_creation']) && $config['status_at_creation'] != null){
				$row['status'] = $config['status_at_creation'];
			}
			
            $pid = Products::createProduct($row);
        }

        Sync::updateVendor($api['slug'], $pid);
    }   
}

function webhook_products_create(){
    insert_or_update_products();
}

function webhook_products_update(){
    insert_or_update_products();
}
    
/*
    No funcionan

    https://community.shopify.com/c/Shopify-Apps/product-delete-webhook-not-working/td-p/574094/highlight/false
*/
function webhook_products_delete(){
    $data = file_get_contents('php://input');  
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

    /*
	$data = $res['data']['webhook'];

	$sql = "INSERT INTO `{$wpdb->prefix}shopi_webhooks` (`shop`, `topic`, `api_version`, `address`, `remote_id`, `created_at`) 
	VALUES ('$shop', '{$data['topic']}', '{$data['api_version']}', '{$data['address']}' , '{$data['id']}', '{$data['created_at']}')";

	$ok = $wpdb->query($sql);

	if (!$ok){
		dd("Error al almacenar WebHook");
		return;
	}
    */

	return true;
}

function delete_all_webhooks(){
    $shops = get_shops();

    foreach ($shops as $vendors_obj){
        $shop = $vendors_obj['shop'];

        $api  = Sync::getApiKeys(null, $shop);
    
        $shop       = $api['shop'];
        $api_key    = $api['api_key'];
        $api_secret = $api['api_secret'];
        $api_ver    = $api['api_ver'];

        $operations = ['create', 'update', 'delete'];

        foreach ($operations as $operation){
            $wh = getWebHook($shop, 'products', $operation, $api_key, $api_secret, $api_ver);
            
            if ($wh != null && isset($wh['id'])){
                // DELETE /admin/api/2021-07/webhooks/1056452214977.json

                $id = $wh['id'];
                $endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/webhooks/{$id}.json";

	            $res = Url::consume_api($endpoint, 'DELETE');
                dd($res, $shop);
            }
        }
    }

    
}


function test_create_webooks(){
    $config = include __DIR__ . '/config/config.php';

    $data = file_get_contents('php://input');
    $arr  = json_decode($data, true);  

	if ($arr == null || $arr['shop'] == null){
		return [];
	}

	//dd($data);
	//dd($arr);

    $shop = $arr['shop'];

    $api  = Sync::getApiKeys(null, $shop);

	if (empty($api)){
		// si hay error y no se puede traer las keys aborto
		return [];
	}
    
    $shop       = $api['shop'];
    $api_key    = $api['api_key'];
    $api_secret = $api['api_secret'];
    $api_ver    = $api['api_ver'];


    $res = [];

	$ok = createWebhook($shop, 'products', 'create', $api_key, $api_secret, $api_ver);
	$res['weboook_product_create'] = $ok;

	$ok = createWebhook($shop, 'products', 'update', $api_key, $api_secret, $api_ver);
	$res['weboook_product_update'] = $ok;

	$ok = createWebhook($shop, 'products', 'delete', $api_key, $api_secret, $api_ver);
	$res['weboook_product_delete'] = $ok;

    return $res;
}


/*
    /index.php/wp-json/connector/v1/shops
*/
function get_shops(){
    $vendors = Sync::getVendors(true);

    $arr = [];

    foreach ($vendors as $vendor){
        // el único donde ocupo WebHooks
        if ($vendor['cms'] == 'shopi'){
            $vendor_slug = $vendor['slug'];

            $api = Sync::getApiKeys($vendor_slug);

			if (empty($api)){
				return [
					'error' => "No se encontró la api key para el vendor $vendor_slug"
				];
			}

			if (!isset($api['shop'])){
				return [
					'error' => "No se encontró el 'shop' para el vendor $vendor_slug"
				];
			}
            
            $arr[] = [ 
                'vendor' => $vendor,
                'shop'   => $api['shop']
            ];
        }
    }

    /*
        [
            {
                "vendor": {
                    "url": "https://f920c96f987d.ngrok.io",
                    "slug": "hupit",
                    "cms": "shopi",
                    "enabled": true
                },
                "shop": "act-and-beee"
            }
        ]
    */        

    return $arr;
}


add_action( 'rest_api_init', function () {
    register_rest_route( 'connector/v1', '/shops', array(
		'methods' => 'GET',
		'callback' => 'get_shops',
        'permission_callback' => '__return_true'
	) );


    register_rest_route( 'connector/v1', '/webhooks/register', array(
		'methods' => 'POST',
		'callback' => 'test_create_webooks',
        'permission_callback' => '__return_true'
	) );


    #	GET /index.php/wp-json/connector/v1/webhooks/products_create
	register_rest_route( 'connector/v1', '/webhooks/products_create', array(
		'methods' => 'POST',
		'callback' => 'webhook_products_create',
        'permission_callback' => '__return_true'
	) );

    register_rest_route( 'connector/v1', '/webhooks/products_update', array(
		'methods' => 'POST', 
		'callback' => 'webhook_products_update',
        'permission_callback' => '__return_true'
	) );

    register_rest_route( 'connector/v1', '/webhooks/products_delete', array(
		'methods' => 'POST',
		'callback' => 'webhook_products_delete',
        'permission_callback' => '__return_true'
	) );
} );


