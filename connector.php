<?php
/*
Plugin Name: Woo connector
Description: API Rest alternativa
Version: 1.0.0
Author: boctulus@gmail.com <Pablo>
*/

use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Strings;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

require __DIR__ . '/libs/Debug.php';
require __DIR__ . '/libs/Files.php';
require __DIR__ . '/libs/Strings.php';
require __DIR__ . '/config.php';
require __DIR__ . '/ajax.php';



if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

/**
 * Check if WooCommerce is active
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}	


/*
    $product es el objeto producto
    $taxonomy es opcional y es algo como 'pa_talla'
*/
function get_variation_attributes($product, $taxonomy = null){
    $attr = [];

    if ( $product->get_type() == 'variable' ) {
        foreach ($product->get_available_variations() as $values) {
            foreach ( $values['attributes'] as $attr_variation => $term_slug ) {
                if (!isset($attr[$attr_variation])){
                    $attr[$attr_variation] = [];
                }

                if ($taxonomy != null){
                    if( $attr_variation === 'attribute_' . $taxonomy ){
                        if (!in_array($term_slug, $attr[$attr_variation])){
                            $attr[$attr_variation][] = $term_slug;
                        }                        
                    }
                } else {
                    if (!in_array($term_slug, $attr[$attr_variation])){
                        $attr[$attr_variation][] = $term_slug;
                    } 
                }

            }
        }
    }

    $arr = [];
    foreach ($attr as $name => $a){
        $key = substr($name, 13);
        foreach ($a as $e){
            $arr[$key][] = $e;
        }
    }

    return $arr;
}


function get_product_id_by_sku( $sku ) {
    global $wpdb;
  
    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );
  
    return $product_id;
  }
  
  
  function get_product_by_sku( $sku ) {
    global $wpdb;
  
    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );
  
    if ( $product_id ) return new WC_Product( $product_id );
  
    return null;
  }
  
  // ok
  function update_product_type_by_product_id($product_id, $new_type){
      $types = ['simple', 'variable'];
  
      if (!in_array($new_type, $types)){
          throw new Exception("Invalid product type $new_type");
      }
  
      // Get the correct product classname from the new product type
      $product_classname = WC_Product_Factory::get_product_classname( $product_id, $new_type );
  
      // Get the new product object from the correct classname
      $new_product       = new $product_classname( $product_id );
  
      // Save product to database and sync caches
      $new_product->save();
  
      return $new_product;
  }
  
  function update_stock_status_by_product_id ($product_id, $stock_status){
      if ($stock_status == 'instock'){
          $qty = 99999;
      }	
  
      // 1. Updating the stock quantity
      update_post_meta($product_id, '_stock', $qty);
  
      // 2. Updating the stock quantity
      update_post_meta( $product_id, '_stock_status', wc_clean( $stock_status ) );
  
      // 3. Updating post term relationship
      wp_set_post_terms( $product_id, $stock_status, 'product_visibility', true );
  }
  
  function update_tags_by_product_id($product_id, $tags){
      wp_set_object_terms($product_id, $tags, 'product_tag');
  }


/**
 * Method to delete Woo Product
 * 
 * $force true to permanently delete product, false to move to trash.
 * 
 */
function delete_product($id, $force = false)
{
    $product = wc_get_product($id);

    if(empty($product))
        return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

    // If we're forcing, then delete permanently.
    if ($force)
    {
        if ($product->is_type('variable'))
        {
            foreach ($product->get_children() as $child_id)
            {
                $child = wc_get_product($child_id);
                $child->delete(true);
            }
        }
        elseif ($product->is_type('grouped'))
        {
            foreach ($product->get_children() as $child_id)
            {
                $child = wc_get_product($child_id);
                $child->set_parent_id(0);
                $child->save();
            }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
    }
    else
    {
        $product->delete();
        $result = 'trash' === $product->get_status();
    }

    if (!$result)
    {
        return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
    }

    // Delete parent product transients.
    if ($parent_id = wp_get_post_parent_id($id))
    {
        wc_delete_product_transients($parent_id);
    }
    return true;
}


function uploadImage($imageurl){
    $size = getimagesize($imageurl)['mime'];
    $f_sz = explode('/', $size);
    $imagetype = end($f_sz);
    $uniq_name = date('dmY').''.(int) microtime(true); 
    $filename = $uniq_name.'.'.$imagetype;

    $uploaddir = wp_upload_dir();
    $uploadfile = $uploaddir['path'] . '/' . $filename;
    $contents= file_get_contents($imageurl);
    $savefile = fopen($uploadfile, 'w');
    fwrite($savefile, $contents);
    fclose($savefile);

    $wp_filetype = wp_check_filetype(basename($filename), null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $filename,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $uploadfile );
    $imagenew = get_post( $attach_id );
    $fullsizepath = get_attached_file( $imagenew->ID );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
    wp_update_attachment_metadata( $attach_id, $attach_data ); 

    return $attach_id;
}

function setDefaultImage($product_id, $image_id){
    update_post_meta( $product_id, '_thumbnail_id', $image_id );
}


function addImagesToPost($product_id, Array $image_ids){
    $image_ids = implode(",", $image_ids);
    update_post_meta($product_id, '_product_image_gallery', $image_ids);
}

function setProductCategoryNames($product_id, Array $categos){
    wp_set_object_terms($product_id, $categos, 'product_cat');
}

function setProductTagNames($product_id, Array $categos){
    wp_set_object_terms($product_id, $categos, 'product_tag');
}


// Utility function that prepare product attributes before saving
function wc_prepare_product_attributes( $attributes, bool $for_variation){

    $data = array();
    $position = 0;

    foreach( $attributes as $_taxonomy => $values ){
        $taxonomy = str_replace('pa_', '', $_taxonomy);
        $taxonomy = 'pa_'. $taxonomy;

        if( ! taxonomy_exists( $taxonomy ) )
            continue;

        // Get an instance of the WC_Product_Attribute Object
        $attribute = new WC_Product_Attribute();

        $term_ids = array();

        if (isset($values['is_visible'])){
            $visibility = $values['is_visible'];
        }

        if (isset($values['term_names'])){
            $values = $values['term_names'];
        }

        // Loop through the term names
        foreach( $values as $term_name ){
            if( term_exists( $term_name, $taxonomy ) ){
                // Get and set the term ID in the array from the term name
                $term_ids[] = get_term_by( 'name', $term_name, $taxonomy )->term_id;
            }else{
                $term_data = wp_insert_term( $term_name, $taxonomy );
                $term_ids[]   = $term_data['term_id'];
                //continue;
            }    
        }

        $taxonomy_id = wc_attribute_taxonomy_id_by_name( $taxonomy ); // Get taxonomy ID

        $attribute->set_id( $taxonomy_id );
        $attribute->set_name( $taxonomy );
        $attribute->set_options( $term_ids );
        $attribute->set_position( $position );
        $attribute->set_visible( $visibility );
        $attribute->set_variation($for_variation);

        $data[$taxonomy] = $attribute; // Set in an array

        $position++; // Increase position
    }
    return $data;
}


// Custom function for product creation (For Woocommerce 3+ only)
function create_product( $args ){

    // Get an empty instance of the product object (defining it's type)
    $product = wc_get_product_object_type( $args['type'] );
    if( ! $product )
        return false;

    // Product name (Title) and slug
    $product->set_name( $args['name'] ); // Name (title).
 
    // Description and short description:
    $product->set_description( $args['description'] ?? '' );
    $product->set_short_description( $args['short_description'] ?? '');

    // Status ('publish', 'pending', 'draft' or 'trash')
    $product->set_status( isset($args['status']) ? $args['status'] : 'publish' );

    // Featured (boolean)
    $product->set_featured(  isset($args['featured']) ? $args['featured'] : false );

    // Visibility ('hidden', 'visible', 'search' or 'catalog')
    $product->set_catalog_visibility( isset($args['visibility']) ? $args['visibility'] : 'visible' );

    // Virtual (boolean)
    $product->set_virtual( isset($args['virtual']) ? $args['virtual'] : false );

    // Prices

    if (isset($args['regular_price'])){
        $product->set_regular_price( $args['regular_price'] );
    }elseif (isset($args['price'])){
        $product->set_regular_price( $args['regular_price'] );
    }

    if (isset($args['sale_price'])){
        $product->set_sale_price($args['sale_price']);
    }
    
    if( isset($args['sale_from'])){
        $product->set_date_on_sale_from($args['sale_from']);
    }

    if( isset($args['sale_to'])){
        $product->set_date_on_sale_to($args['sale_to']);
    }
    
    // Downloadable (boolean)
    $product->set_downloadable(  isset($args['downloadable']) ? $args['downloadable'] : false );
    if( isset($args['downloadable']) && $args['downloadable'] ) {
        $product->set_downloads(  isset($args['downloads']) ? $args['downloads'] : array() );
        $product->set_download_limit(  isset($args['download_limit']) ? $args['download_limit'] : '-1' );
        $product->set_download_expiry(  isset($args['download_expiry']) ? $args['download_expiry'] : '-1' );
    }

    // Taxes
    if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
        $product->set_tax_status(  isset($args['tax_status']) ? $args['tax_status'] : 'taxable' );
        $product->set_tax_class(  isset($args['tax_class']) ? $args['tax_class'] : '' );
    }

    $args['virtual'] = $args['virtual'] ?? false;

    // SKU and Stock (Not a virtual product)
    if( ! $args['virtual'] ) {
        ###$product->set_sku( isset( $args['sku'] ) ? $args['sku'] : '' );
        $product->set_manage_stock( isset( $args['manage_stock'] ) ? $args['manage_stock'] : false );

        if (isset($args['stock_status'])){
            $product->set_stock_status($args['stock_status']);
        } elseif (isset($args['is_in_stock'])){
            $product->set_stock_status($args['is_in_stock']);
        } else {
            $product->set_stock_status('instock');        
        }
        
        if( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
            $product->set_stock_status( $args['stock_qty'] );
            $product->set_backorders( isset( $args['backorders'] ) ? $args['backorders'] : 'no' ); // 'yes', 'no' or 'notify'
        }
    }

    // Sold Individually
    if (isset($args['sold_individually'])){
        $product->set_sold_individually($args['is_sold_individually'] != 'no');
    }

    // Weight, dimensions and shipping class
    $product->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );
    $product->set_length( isset( $args['length'] ) ? $args['length'] : '' );
    $product->set_width( isset(  $args['width'] ) ?  $args['width']  : '' );
    $product->set_height( isset( $args['height'] ) ? $args['height'] : '' );

    /*
    if( isset( $args['shipping_class_id'] ) ){
        $product->set_shipping_class_id( $args['shipping_class_id'] );
    }
    */        

    // Upsell and Cross sell (IDs)
    $product->set_upsell_ids( isset( $args['upsells'] ) ? $args['upsells'] : '' );
    $product->set_cross_sell_ids( isset( $args['cross_sells'] ) ? $args['upsells'] : '' );


    // Attributes et default attributes
    
    if( isset( $args['attributes'] ) ){
        $attr = wc_prepare_product_attributes($args['attributes'], true);
        $product->set_attributes($attr);
    }
        
    if( isset( $args['default_attributes'] ) )
        $product->set_default_attributes( $args['default_attributes'] ); // Needs a special formatting


    // Reviews, purchase note and menu order
    $product->set_reviews_allowed( isset( $args['reviews'] ) ? $args['reviews'] : false );
    $product->set_purchase_note( isset( $args['note'] ) ? $args['note'] : '' );
    if( isset( $args['menu_order'] ) )
        $product->set_menu_order( $args['menu_order'] );

        
    ## --- SAVE PRODUCT --- ##
    $product_id = $product->save();

    if (isset($args['stock_status'])){
        //$product->set_stock_status($args['stock_status']);
        update_post_meta( $product_id, '_stock_status', wc_clean( $args['stock_status'] ) );
    } 


    // Product categories and Tags
    if( isset( $args['categories'] ) ){
        setProductCategoryNames($product_id, array_column($args['categories'], 'name'));
    }        

    if( isset( $args['tags'] ) ){
        setProductTagNames($product_id, $args['tags'] );
    }
        

    // Images and Gallery
  
    if (isset($args['image'])){
        $attach_id = uploadImage($args['image']);
        setDefaultImage($product_id, $attach_id);
    }

    if (isset($args['gallery_images']) && count($args['gallery_images'])){
        $ids = [];
        foreach ($args['gallery_images'] as $img){
            $attach_id = uploadImage($img[0]);
        }

        addImagesToPost($product_id, $ids);         
    }

    if ($args['type'] == 'variable' && isset($args['variations'])){
        foreach ($args['variations'] as $variation){
            add_variation($product_id, $variation);
        }        
    }

    return $product_id;
}

function add_variation( $product_id, Array $args ){
    
    // Get the Variable product object (parent)
    $product = wc_get_product($product_id);

    $variation_post = array(
        'post_title'  => $product->get_name(),
        'post_description' => $args['variation_description'] ?? '',
        'post_name'   => 'product-'.$product_id.'-variation',
        'post_status' => isset($args['status']) ? $args['status'] : 'publish',
        'post_parent' => $product_id,
        'post_type'   => 'product_variation',
        'guid'        => $product->get_permalink()
    );

    // Creating the product variation
    $variation_id = wp_insert_post( $variation_post );

    // Get an instance of the WC_Product_Variation object
    $variation = new WC_Product_Variation( $variation_id );

    
    // Description and short description:
    $variation->set_description($args['variation_description']);

  
    dd("Product_id = $product_id"); //

    if( isset( $args['attributes'] ) ){
        // Iterating through the variations attributes
        foreach ($args['attributes'] as $attribute => $term_name )
        {
            $taxonomy = str_replace('attribute_pa_', '', $attribute);
            $taxonomy = str_replace('pa_', '', $taxonomy);
            $taxonomy = 'pa_'.$taxonomy; // The attribute taxonomy


            // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
            if( ! taxonomy_exists( $taxonomy ) ){
                register_taxonomy(
                    $taxonomy,
                'product_variation',
                    array(
                        'hierarchical' => false,
                        'label' => ucfirst( $attribute ),
                        'query_var' => true,
                        'rewrite' => array( 'slug' => sanitize_title($attribute) ), // The base slug
                    ),
                );
            }

            // Check if the Term name exist and if not we create it.
            if( ! term_exists( $term_name, $taxonomy ) )
                wp_insert_term( $term_name, $taxonomy ); // Create the term

            $term_slug = get_term_by('name', $term_name, $taxonomy )->slug; // Get the term slug

            // Get the post Terms names from the parent variable product.
            $post_term_names =  wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );

            // Check if the post term exist and if not we set it in the parent variable product.
            if( ! in_array( $term_name, $post_term_names ) )
                wp_set_post_terms( $product_id, $term_name, $taxonomy, true );

            // Set/save the attribute data in the product variation
            update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
        }
    }


    if (isset($args['sku'])){
        #######$variation->set_sku($args['sku']);
    }

    // Prices
    if (isset($args['display_regular_price'])){
        $variation->set_regular_price( $args['display_regular_price'] );
    }

    if (isset($args['display_price'])){
        $variation->set_sale_price($args['display_price']);
    }
    
    if( isset($args['sale_from'])){
        $variation->set_date_on_sale_from($args['sale_from']);
    }

    if( isset($args['sale_to'])){
        $variation->set_date_on_sale_to($args['sale_to']);
    }

    // Sold Individually
    if (isset($args['sold_individually'])){
        $variation->set_sold_individually($args['is_sold_individually'] != 'no');
    }

    // Weight, dimensions and shipping class
    $variation->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );

    // Stock    
    if (isset($args['stock_status'])){
        $variation->set_stock_status($args['stock_status']);
    } elseif (isset($args['is_in_stock'])){
        $variation->set_stock_status($args['is_in_stock']);
    } else {
        $variation->set_stock_status('instock');        
    }

    if( ! empty($args['stock_quantity']) ){
        $variation->set_stock_quantity( $args['stock_quantity'] );
        $variation->set_manage_stock(true);
    } else {
        $variation->set_manage_stock(false);
    }

    
    $variation->save();

    // agrega la variación al producto
    $product = wc_get_product($product_id);
    $product->save();
}

// Utility function that returns the correct product object instance
function wc_get_product_object_type( $type = 'simple') {
    // Get an instance of the WC_Product object (depending on his type)
    if($type === 'variable' ){
        $product = new WC_Product_Variable();
    } elseif($type === 'grouped' ){
        $product = new WC_Product_Grouped();
    } elseif($type === 'external' ){
        $product = new WC_Product_External();
    } elseif($type === 'simple' )  {
        $product = new WC_Product_Simple(); 
    } 
    
    if( ! is_a( $product, 'WC_Product' ) )
        return false;
    else
        return $product;
}

