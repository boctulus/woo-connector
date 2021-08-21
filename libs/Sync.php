<?php

namespace connector\libs;

use connector\Connector;
use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;


require_once __DIR__ . '/Url.php';
require_once __DIR__ . '/Debug.php';
require_once __DIR__ . '/Products.php';

include_once __DIR__ . '/../../../../wp-load.php';


if (!defined('MY_PRODUCT_VENDORS_TAXONOMY')){
    define( 'MY_PRODUCT_VENDORS_TAXONOMY', 'wcpv_product_vendors' );
}

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
    static function getVendors($only_active = false){
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
    
            $arr[] = [
                'url'       => $fields[0],
                'slug'      => $fields[1],
                'cms'       => $fields[2],
                'enabled'   => $enabled
            ];
        }
    
        return $arr;
    }

    /*
        Devuelve el vendor_slug del post o NULL en caso contrario
    */
    static function getCurrentVendor($post_id){
        return wp_get_object_terms( [$post_id], MY_PRODUCT_VENDORS_TAXONOMY );
    }
    
    static function updateVendor($vendor_slug, $pid){
        if (!isset($pid)){
            return;
        }

        if (class_exists(\WC_Product_Vendors_Utils::class)){
            if (! \WC_Product_Vendors_Utils::is_valid_vendor($vendor_slug)){
                dd("[ Advertencia ] El vendor $vendor_slug no existe.");
                return;
            }
        }

        wp_set_object_terms( $pid, $vendor_slug, MY_PRODUCT_VENDORS_TAXONOMY, false );
    }
    
    static function updateCount($vendor){
        global $wpdb;
    
        $sql = "SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = '$vendor'";
        $vendor_id = $wpdb->get_var($sql);
    
        $sql = "SELECT COUNT(*) as count FROM `wp_term_relationships` as TR 
        INNER JOIN `wp_term_taxonomy` as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id 
        INNER JOIN `wp_terms` as T ON T.term_id = TT.term_id
        WHERE slug='$vendor';";
    
        $count = $wpdb->get_var($sql);
    
        $sql = "UPDATE  `wp_term_taxonomy` SET count = $count WHERE term_id = $vendor_id";
        return $wpdb->query($sql);
    }


    static function getData(){
        $vendors = Sync::getVendors(true);

        foreach ($vendors as $vendor){
            $vendor_cms  = strtolower($vendor['cms']);

            // Para otros CMS se trabaja con Webhooks así que es innecesario pedir datos
            if ($vendor_cms != 'wc' && $vendor_cms != 'woocommerce'){
                continue;
            }

            $vendor_url  = $vendor['url'];
            $vendor_slug = $vendor['slug'];

            $config = self::getConfig();

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
                        

            // cache ---------- solo pruebas
            //Files::dump($res, 'response_x.php'); 
            //dd($res);
            //include __DIR__ . '/logs/response.php';

            $data = $res['data'];
            
            
            foreach ($data as $row){
                #dd($row, 'ROW'); ///

                $sku = $row['sku'];
                $operation = $row['operation'];
                
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

        dd("La sincronización ha terminado");
    }
}
