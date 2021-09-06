<?php

namespace connector\libs;

use connector\Connector;
use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;
use connector\libs\WCPV_utils;


require_once __DIR__ . '/Url.php';
require_once __DIR__ . '/Debug.php';
require_once __DIR__ . '/Products.php';
require_once __DIR__ . '/WCPV_utils.php';
require_once __DIR__ . '/WCFM_utils.php';

include_once __DIR__ . '/../../../../wp-load.php';


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

class Sync
{   
    static protected $config;

    static function getConfig(){
        if (self::$config != null){
            return self::$config;
        }

		self::$config = include __DIR__ . '/../config/config.php';
		return self::$config;
	}

    /*
        Asumo solo puede estar activo
    */
    static function getVendorPlugin(){
        if (WCFM_utils::is_active()){
            return 'WCFM';
        }

        if (WCPV_utils::is_active()){
            return 'WCPV';
        } 

        return null;
    }

    static function getCurrentVendor($post_id){
        if (self::getVendorPlugin() == 'WCFM'){
            return WCFM_utils::getCurrentVendor($post_id);
        }

        if (self::getVendorPlugin() == 'WCPV'){
            return WCPV_utils::getCurrentVendor($post_id);
        }

        return false;
    }

    static function updateVendor($vendor_slug, $pid){
        if (WCFM_utils::is_active()){
            return WCFM_utils::updateVendor($vendor_slug, $pid);
        } 

        if (WCPV_utils::is_active()){
            return WCPV_utils::updateVendor($vendor_slug, $pid);
        } 

        throw new \Exception("No hay plugin multi-vendor vendor definido");
    }


    static function getApiKeys($vendor_slug = null, $shop = null){
        $list  = file_get_contents(__DIR__ . '/../config/api_keys.txt');
        $lines = explode(PHP_EOL, $list);
    
        $arr = [];
        foreach ($lines as $line){
            $line = trim($line);
    
            if (empty($line) || $line[0] == '#' || $line[0] == ';'){
                continue;
            }
    
            $line   = str_replace("\t", " ", $line);
            $line   = preg_replace('!\s+!', ' ', $line);
            $fields = explode(' ', $line);
    
            $row = [
                'slug'           => $fields[0],
                'api_key'        => $fields[1],
                'api_secret'     => $fields[2] ?? null,
                'api_ver'        => $fields[3] ?? null,
                'shop'           => $fields[4] ?? null
            ];

            if ($vendor_slug != null){
                if ($row['slug'] == $vendor_slug){
                    return $row;
                }
            }

            if ($shop != null){
                if ($row['shop'] == $shop){
                    return $row;
                }
            }

            $arr[] = $row;
        }
    
        return $arr;
    }

    static function getVendorFromApiKey(string $api_key){
        $apis = self::getApiKeys();

        foreach ($apis as $api){
            if ($api['api_key'] == $api_key){
                return $api['slug'];
            }
        }
    }

    /*
        array (
            0 => 
            array (
                'url' => 'http://woo2.lan',
                'slug' => 'act-and-be',
                'cms' => 'wc',
                'enabled' => true,
            ),
            1 => 
            array (
                'url' => 'http://otrovendor.com',
                'slug' => 'otro-vendor',
                'cms' => 'shopi',
                'enabled' => true,
            ),
            2 => 
            array (
                'url' => 'http://woo3.lan',
                'slug' => 'azul-marino-casi-negro',
                'cms' => 'wc',
                'enabled' => true,xt
            ),
        )
    */
    static function getVendors($only_active = false, $cms = null, $slug = null){
        $list  = file_get_contents(__DIR__ . '/../config/vendors.txt');
        $lines = explode(PHP_EOL, $list);
    
        $arr = [];
        foreach ($lines as $line){
            $line = trim($line);
    
            if (empty($line) || $line[0] == '#' || $line[0] == ';'){
                continue;
            }
    
            $line   = str_replace("\t", " ", $line);
            $line   = preg_replace('!\s+!', ' ', $line);
            $fields = explode(' ', $line);
    
            $enabled = !(isset($fields[3]) && $fields[3] == 'no');
    
            if ($only_active && !$enabled){
                continue;
            }

            if ($cms != null){
                if (is_array($cms)){
                    $_cms = strtolower($fields[2]);

                    $found = false;
                    foreach ($cms as $item){
                        if (strtolower($item) == $_cms){
                            $found = true;
                            break;
                        }
                    }   
                    if (!$found){
                        continue;
                    }                 
                } else {
                    if (strtolower($fields[2]) != strtolower($cms)){
                        continue;
                    }
                }
            }
    
            $row = [
                'url'       => $fields[0],
                'slug'      => $fields[1],
                'cms'       => $fields[2],
                'enabled'   => $enabled
            ];

            if ($fields[1] == $slug){
                return [ $row ];
            }

            $arr[] = $row;
        }
    
        return $arr;
    }

    //check if it exists
    static function is_vendor($only_active = false, $cms = null, $slug = null){
        $list  = file_get_contents(__DIR__ . '/../config/vendors.txt');
        $lines = explode(PHP_EOL, $list);

        if (empty($cms) && empty($slug)){
            throw new \Exception("CMS y SLUG no pueden ser ambos nulos");
        }
    
        foreach ($lines as $line){
            $line = trim($line);
    
            if (empty($line) || $line[0] == '#' || $line[0] == ';'){
                continue;
            }
    
            $line   = str_replace("\t", " ", $line);
            $line   = preg_replace('!\s+!', ' ', $line);
            $fields = explode(' ', $line);
    
            $enabled = !(isset($fields[3]) && $fields[3] == 'no');
    
            if ($only_active && !$enabled){
                continue;
            }

            if ($cms != null){
                if (is_array($cms)){
                    $_cms = strtolower($fields[2]);

                    foreach ($cms as $item){
                        if (strtolower($item) == $_cms){
                            return true;
                        }
                    }                  
                } else {
                    if (strtolower($fields[2]) != strtolower($cms)){
                        continue;
                    }
                }
            }

            if ($fields[1] == $slug){
                return true;
            }
        }
    
        return false;
    }



    /*
        Agregar o borrar variantes dispara el WebHook para UPDATE -- ok

        El problema está en si se borra el producto como tal

        Buscar solo los 'handle' con ?fields=handle para verificar si algún handle ya no existe,
        tiene el mismo costo en tiempo que traerse todos los registros (paginados claro)
    */


    /*
        Asumo el vendor si es de una tienda de Shopify
    */
    static function getInitialDataFromShopify($vendor_slug){
        set_time_limit(0);
        
        $config   = self::getConfig();
        $api_info = self::getApiKeys($vendor_slug);

        $api_key     = $api_info['api_key'];
        $api_secret  = $api_info['api_secret'];
        $api_ver     = $api_info['api_ver'];
        $shop        = $api_info['shop'];

        // usar since_id para "paginar"
        $endpoint = "https://$api_key:$api_secret@$shop.myshopify.com/admin/api/$api_ver/products.json";

        $limit    = 100;  // 100
        $last_id  = 0;
        $max      = null;  // null
        $query_fn = function($limit, $last_id){ return "limit=$limit&since_id=$last_id"; };

        $regs  = [];

        // número de páginas
        $count   = 0; 
        while(true){
            if (isset($max) && !empty($max) && ($count >= $max/$limit)){
                break;
            }
    
            //dd("Q=" . $query_fn($limit, $last_id));

            $res = Url::consume_api("$endpoint?".$query_fn($limit, $last_id), 'GET', null, null, null, false);

    
            if ($res['http_code'] != 200){
                //dd($res['error'], 'ERROR', function(){ die; });
                return [
                    'error' => $res['error']
                ];
            }
    
            $data     = json_decode($res['data'], true); 

            $products = $data["products"];


            file_put_contents(__DIR__ . '/../downloads/shopify.' . $vendor_slug . '.' . $limit . '.' . $last_id . '.json', $res['data']);
            
            $last_id  = max(array_column($products, 'id'));

            if (count($products) != $limit){
                break;
            }

            $count++;            
        }    

        return [
            'count' => $count
        ];
    }


    static function getInitialDataFromWooCommerce($vendor_slug){
        set_time_limit(0);
        
        $config   = self::getConfig();
        $api_info = self::getApiKeys($vendor_slug);

        $api_key     = $api_info['api_key'];
        $api_secret  = $api_info['api_secret'];


        $vendor = Sync::getVendors(null, null, $vendor_slug)[0];
        $vendor_url = $vendor['url'];

        // usar since_id para "paginar"
        $endpoint = $vendor_url . '/index.php/wp-json/connector/v1/products/all?api_key=' . $api_key;

        $limit    = 3;  // 100
        $last_id  = 0;
        $max      = null;  // null
        $query_fn = function($limit, $last_id){ return "limit=$limit&since_id=$last_id"; };

        $regs  = [];

        // número de páginas
        $count   = 0; 
        while(true){
            if (isset($max) && !empty($max) && ($count >= $max/$limit)){
                break;
            }
    
            //dd("Q=" . $query_fn($limit, $last_id));

            $url = "$endpoint&".$query_fn($limit, $last_id);
            $res = Url::consume_api($url, 'GET', null, null, null, false);

    
            if ($res['http_code'] != 200){
                //dd($res['error'], 'ERROR', function(){ die; });
                return [
                    'error' => $res['error']
                ];
            }

    
            $data     = json_decode($res['data'], true);

            if (empty($data)){
                break;
            }    

            $products = $data;

            file_put_contents(__DIR__ . '/../downloads/wc.' . $vendor_slug . '.' . $limit . '.' . $last_id . '.json', $res['data']);
            
            $last_id  = max(array_column($products, 'id'));

            if (count($products) != $limit){
                break;
            }

            $count++;            
        }    

        return [
            'count' => $count
        ];
    }

    static function processInitialDataFromShopify(){
        set_time_limit(0);

        $config = self::getConfig();

        try {
            $sse = new SSE('shopify_sync');

            foreach (new \DirectoryIterator(__DIR__ . '/../downloads') as $fileInfo) {
                if($fileInfo->isDot()){
                    continue;
                } 
    
                $filename =  $fileInfo->getFilename();
    
                if ($filename === null || $fileInfo->getExtension() != 'json' || !Strings::startsWith('shopify.', $filename)){
                    continue;
                }
    
                // proceso
    
                $path = $fileInfo->getPathname();
                $file = file_get_contents($path);
                    
                $data = json_decode($file, true);
    
                if ($data === null){
                    $sse->sendError("Error al decodificar $path");
                }
    
       
                $products = $data['products'];
    
                $f = explode('.', $filename);
                
                if (!isset($f[1])){
                    $sse->sendError("El archivo $filename no tiene un nombre acorde.");
                    continue;
                } else {
                    $vendor_slug = $f[1];
                }
    
                $api_info = self::getApiKeys($vendor_slug);
    
                $api_key     = $api_info['api_key'];
                $api_secret  = $api_info['api_secret'];
                $api_ver     = $api_info['api_ver'];
                $shop        = $api_info['shop'];
    
                $created = 0;
                foreach ($products as $product){
                    //dd($product);
    
                    $sku_arr    = array_column($product["variants"], 'sku');
                    $product_id = $product['id'];
                    $slug       = $product['handle'];

                    /*
                    if ($product['status'] != 'active'  && !$config['insert_unpublished']){
                        continue;
                    }
                    */

                    $rows = adaptToShopify($product, $shop, $api_key, $api_secret, $api_ver);
    
                    if (empty($rows)){
                        $msg = "Error al decodificar para shop $shop para producto con hanlde '$slug'";
                        $sse->sendError($msg);
                        //Files::logger($msg");
                    }                        
    
                    foreach ($rows as $row){
                        if (empty($row['sku'])){
                            continue;
                        }                        
                      
                        $sku = $row['sku'];
    
                        $pid = \wc_get_product_id_by_sku($sku);
    
                        //dd("HANDLE $slug | SKU $sku  | PID $pid "); continue;
                                        
                        if (empty($pid)){   
                            
                            if (isset($config['status_at_creation']) && $config['status_at_creation'] != null){
                                $row['status'] = Products::convertStatusFromShopifyToWooCommerce($config['status_at_creation']);
                            }
    
                            $pid = Products::createProduct($row);
                            
                            if ($pid != null){
                                $sse->send("Producto para shop $shop con SKU = $sku creado");
                                $created++;
                            }
                        }
                
                        Sync::updateVendor($vendor_slug, $pid);
                    }                     
                }
    
                unlink($path);
            }

        } catch (\Exception $e) {
            Files::logger($e->getMessage());
        }        
    }


    static function processInitialDataFromWooCommerce(){
        set_time_limit(0);

        $config = self::getConfig();

        try {
            $sse = new SSE('wc_sync');

            foreach (new \DirectoryIterator(__DIR__ . '/../downloads') as $fileInfo) {
                if($fileInfo->isDot()){
                    continue;
                } 
    
                $filename =  $fileInfo->getFilename();
    
                if ($filename === null || $fileInfo->getExtension() != 'json' || !Strings::startsWith('wc.', $filename)){
                    continue;
                }
    
                // proceso
    
                $path = $fileInfo->getPathname();
                $file = file_get_contents($path);
                    
                $data = json_decode($file, true);
    
                if ($data === null){
                    $sse->sendError("Error al decodificar $path");
                }
    
                $products = $data;
    
                $f = explode('.', $filename);
                
                if (!isset($f[1])){
                    $sse->sendError("El archivo $filename no tiene un nombre acorde.");
                    continue;
                } else {
                    $vendor_slug = $f[1];
                }
    
                $api_info = self::getApiKeys($vendor_slug);
    
                $api_key     = $api_info['api_key'];
                $api_secret  = $api_info['api_secret'];

                $created = 0;
                foreach ($products as $product){
                    //dd($product);

                    if (empty($product['sku'])){
                        continue;
                    }

                    if ($product['status'] != 'publish'  && !$config['insert_unpublished']){
                        continue;
                    }

                    $sku = $product['sku'];

                   
                    $pid = \wc_get_product_id_by_sku($sku);
                                    
                    if (empty($pid)){          
                        if (isset($config['status_at_creation']) && $config['status_at_creation'] != null){
                            $product['status'] = $config['status_at_creation'];
                        }

                        $pid = Products::createProduct($product);
                        
                        if ($pid != null){
                            $sse->send("Producto para $vendor_slug con SKU = $sku creado");
                            $created++;
                        }
                    }
                        
                    Sync::updateVendor($vendor_slug, $pid);
                }
    
                unlink($path);
            }

        } catch (\Exception $e) {
            Files::logger($e->getMessage());
        }        
    }


    static function getUpdatedDataFromWooCommerce(){
        $vendors = Sync::getVendors(true, ['wc', 'woocommerce']);

        $config = self::getConfig();

        foreach ($vendors as $vendor)
        {           
            if (!isset($vendor['url'])){
                $msg = "Error: la url no está presente para un vendor!";

                Files::logger($msg);
                dd($msg);
            }

            if (!isset($vendor['slug'])){
                $msg = "Error: el vendor_slug no está presente para un vendor!";

                Files::logger($msg);
                dd($msg);
            }

            $vendor_url  = $vendor['url'];
            $vendor_slug = $vendor['slug'];

            $api_info = self::getApiKeys($vendor_slug);
            $api_key  = $api_info['api_key'];

            $full_url = $vendor_url . '/index.php/wp-json/connector/v1/products?api_key=' . $api_key;
		    $res = Url::consume_api($full_url, 'GET');

            if ($res['http_code'] != 200){
                $msg = "vendor=$vendor_slug http_code={$res['http_code']} error={$res['error']}";
               
                Files::logger($msg);
                dd($msg);

                return;
            }

            if (! isset($res['data']) || empty($res['data'])){
                $msg = "No hay datos";

                continue;
            }
                        

            $data = $res['data'];
            
            foreach ($data as $row){
                #dd($row, 'ROW'); ///
               
                if (empty($row['sku'])){
                    continue;
                }

                if ($row['status'] != 'publish' && !$config['insert_unpublished']){
                    continue;
                }

                $sku       = $row['sku'];
                $operation = $row['operation'];
               

                if ($config['test_mode']){
                    Files::dump($data, "$vendor_slug.$sku.txt");
                }
                
                $pid = \wc_get_product_id_by_sku($sku);

                dd($operation, "VENDOR: $vendor_slug - SKU $sku");

                // Si ya existe,...
                if (!empty($pid)){
                    switch ($operation){
                        case 'DELETE':
                            Products::deleteProduct($pid, true);
                            break;
                        case 'CREATE':                                             
                        case 'RESTORE':                           
                        case 'UPDATE':
                            Products::updateProductBySku($row);                            
                            Sync::updateVendor($vendor_slug, $pid);

                            break;
                        default:
                            $msg = "Operación $operation desconocida";
                            dd($msg);
                            Files::logger($msg);
                    }
                } else {
                    switch ($operation){
                        case 'DELETE':
                            // nada que hacer en este caso                        
                            break;                        
                        case 'UPDATE':                           
                        case 'RESTORE':                            
                        case 'CREATE':
                            if (isset($config['status_at_creation']) && $config['status_at_creation'] != null){
                                $row['status'] = $config['status_at_creation'];
                            }
                            
                            $pid = Products::createProduct($row);
                            Sync::updateVendor($vendor_slug, $pid);

                            break;
                        default:
                            $msg = "Operación $operation desconocida";
                            dd($msg);
                            Files::logger($msg);
                    }

                }
            }

            //Files::dump($res, 'response.txt');
        }

        dd("La sincronización de WooCommerce ha terminado");
    }
}

