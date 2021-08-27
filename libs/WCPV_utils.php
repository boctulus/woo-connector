<?php

namespace connector\libs;

use connector\Connector;
use connector\libs\Debug;

require_once __DIR__ . '/Debug.php';
include_once __DIR__ . '/../../../../wp-load.php';

if (!defined('MY_PRODUCT_VENDORS_TAXONOMY')){
    define( 'MY_PRODUCT_VENDORS_TAXONOMY', 'wcpv_product_vendors' );
}

class WCPV_utils
{
    static function is_active(){
        include_once (ABSPATH.'wp-admin/includes/plugin.php');

        // WC_Product_Vendors
        return is_plugin_active('woocommerce-product-vendors/woocommerce-product-vendors.php');
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
    
        $sql = "SELECT COUNT(*) as count FROM `{$wpdb->prefix}term_relationships` as TR 
        INNER JOIN `{$wpdb->prefix}term_taxonomy` as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id 
        INNER JOIN `{$wpdb->prefix}terms` as T ON T.term_id = TT.term_id
        WHERE slug='$vendor';";
    
        $count = $wpdb->get_var($sql);
    
        $sql = "UPDATE  `{$wpdb->prefix}term_taxonomy` SET count = $count WHERE term_id = $vendor_id";
        return $wpdb->query($sql);
    }

}