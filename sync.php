#!/usr/bin/env php
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use connector\libs\Debug;
use connector\libs\Url;
use connector\libs\Files;
use connector\libs\Strings;
use connector\libs\Products;


require __DIR__ . '/libs/Url.php';
require __DIR__ . '/libs/Products.php';

include __DIR__ . '/../../../wp-load.php';


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

if (php_sapi_name() != "cli") {
    echo "Error: acceso solo desde la terminal<p/>";
}

class Sync
{   
    static protected $config;

    static function getConfig(){
        if (self::$config != null){
            return self::$config;
        }

		self::$config = include __DIR__ . '/config/config.php';
		return self::$config;
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
                'enabled' => true,
            ),
        )
    */
    static function getVendors($only_active = false){
        $list  = file_get_contents(__DIR__ . '/config/vendors.txt');
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
    
    // 
    static function hasVendor($vendor, $pid){
        global $wpdb;

        if (empty($pid)){
            throw new \InvalidArgumentException("product_id no puede ser nulo");
        }
    
        $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}term_taxonomy`  as TT
        INNER JOIN `{$wpdb->prefix}terms` as T ON TT.term_id = T.term_id
        INNER JOIN `{$wpdb->prefix}term_relationships` as TR ON TT.term_taxonomy_id = TR.term_taxonomy_id
        WHERE T.slug = '$vendor' AND object_id = $pid";

        return $wpdb->get_var($sql) != 0;
    }
    
    static function removeVendor($vendor, $pid){
        global $wpdb;

        if (empty($pid)){
            throw new \InvalidArgumentException("product_id no puede ser nulo");
        }
    
        $sql = "SELECT TR.term_taxonomy_id as tt_id FROM `{$wpdb->prefix}term_relationships` as TR 
        INNER JOIN `{$wpdb->prefix}term_taxonomy` as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id 
        INNER JOIN `{$wpdb->prefix}terms` as T ON T.term_id = TT.term_id
        WHERE object_id = $pid AND slug='$vendor'";
    
        $tt_id = $wpdb->get_var($sql);
    
        $sql = "DELETE FROM `{$wpdb->prefix}term_relationships` WHERE `object_id` = $pid AND `term_taxonomy_id` = $tt_id";
    
        $ok = $wpdb->query($sql);
    
        if (!$ok){
            return false;
        }
    
        self::updateCount($vendor);
    
        return true;
    }
    
    static function addVendor($vendor, $pid){
        global $wpdb;

        if (empty($pid)){
            throw new \InvalidArgumentException("product_id no puede ser nulo");
        }
    
        $sql = "SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = '$vendor'";
        $vendor_id = $wpdb->get_var($sql);
    
        $sql = "INSERT INTO `{$wpdb->prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($pid, $vendor_id, '0');";
    
        $ok = $wpdb->query($sql);
        
        if (!$ok){
            return false;
        }
    
        self::updateCount($vendor);
    
        return true;
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
            $vendor_url  = $vendor['url'];
            $vendor_slug = $vendor['slug'];

            
            $config = self::getConfig();

            $full_url = $vendor_url . '/index.php/wp-json/connector/v1/products?api_key=' . $config['API_KEY'];
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
            //include __DIR__ . '/logs/response.php';

            $data = $res['data'];
            
            foreach ($data as $row){
                $sku = $row['sku'];
                $operation = $row['operation'];
                
                // específico para WooCommerce
                if (Strings::endsWith('__trashed', $row['slug'])){
                    //$operation == 'DELETE';
                }
                
                $pid = wc_get_product_id_by_sku($sku);

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

                            if (!self::hasVendor($vendor_slug, $pid)){
                                self::addVendor($vendor_slug, $pid);
                            }

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
                            $pid = Products::createProduct($row);

                            if (!self::hasVendor($vendor_slug, $pid)){
                                self::addVendor($vendor_slug, $pid);
                            }

                            break;
                        default:
                            $msg = "Operación $operation desconocida";
                            dd($msg);
                            Files::logger($msg);
                    }

                }
            }

            Files::dump($res, 'response.txt');
        }
    }

}




Sync::getData();





