<?php

//namespace connector;

use connector\Connector;
use connector\libs\Url;
use connector\libs\Products;
use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Request;
use connector\libs\Strings;
use connector\Sync;

require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/Products.php';
require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Request.php';
require_once __DIR__ . '/sync.php';


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
				$term = 'titulo';
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
    if (isset($a['image'])){            
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

    $data     = file_get_contents('php://input');    
   
    $headers  = Request::apache_request_headers();

    $shop_url = $headers['X-SHOPIFY-SHOP-DOMAIN'] ?? null;

    if (!Strings::endsWith('.myshopify.com', $shop_url)){
        Files::logger("WebHook reporta url de tienda de Shopify inválida $shop_url");
        return;
    }

    $shop = substr($shop_url, 0, strlen($shop_url) - strlen('.myshopify.com'));
    
    $api  = Connector::getApiKeys(null, $shop);

    $arr  = json_decode($data, true);

    $rows = adaptToShopify($arr, $api['shop'], $api['api_key'], $api['api_secret'], $api['api_ver']);

    if (empty($rows)){
        Files::logger("Error al recibir datos para shop $shop");
    }

    if ($config['test_mode']){
        Files::dump($arr, $arr['handle'] . '.txt'); 
        Files::dump($rows, $arr['handle'] . '_adaptado.txt'); 
    }


    foreach ($rows as $row){
        $sku = $row['sku'];

        $pid = wc_get_product_id_by_sku($sku);
    
        if (!empty($pid)){
            Products::updateProductBySku($row);
        } else {
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

add_action( 'rest_api_init', function () {
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


