<?php

require __DIR__ . '/libs/Url.php';


function send($message, $status = 200){
	http_response_code($status);
	echo json_encode($message);
	exit;
}

function get_products(){
	$id = $_GET['id'] ?? null;

	if (empty($id) || !is_numeric($id) || $id <0){
		send("ID inválido para $id");
	}
	

	$product = wc_get_product($id);

	if (empty($product)){
		send("Producto no encontrado para ID=$id");
	}

	$obj = [];

	$get_src = function($html) {
		$parsed_img = json_decode(json_encode(simplexml_load_string($html)), true);
		$src = $parsed_img['@attributes']['src']; 
		return $src;
	};

	// Get Product General Info
  
	$obj['type'] = $product->get_type();
	$obj['name'] = $product->get_name();
	$obj['slug'] = $product->get_slug();
	$obj['status'] = $product->get_status();
	$obj['featured'] = $product->get_featured();
	$obj['catalog_visibility'] = $product->get_catalog_visibility();
	$obj['description'] = $product->get_description();
	$obj['short_description'] = $product->get_short_description();
	$obj['sku'] = $product->get_sku();
	#$obj['virtual'] = $product->get_virtual();
	#$obj['permalink'] = get_permalink( $product->get_id() );
	#$obj['menu_order'] = $product->get_menu_order(
	#$obj['date_created'] = $product->get_date_created();
	#$obj['date_modified'] = $product->get_date_modified();
	
	// Get Product Prices
	
	$obj['price'] = $product->get_price();
	$obj['regular_price'] = $product->get_regular_price();
	$obj['sale_price'] = $product->get_sale_price();
	#$obj['date_on_sale_from'] = $product->get_date_on_sale_from();
	#$obj['date_on_sale_to'] = $product->get_date_on_sale_to();
	#$obj['total_sales'] = $product->get_total_sales();
	
	// Get Product Tax, Shipping & Stock
	
	#$obj['tax_status'] = $product->get_tax_status();
	#$obj['tax_class'] = $product->get_tax_class();
	$obj['manage_stock'] = $product->get_manage_stock();
	$obj['stock_quantity'] = $product->get_stock_quantity();
	$obj['stock_status'] = $product->get_stock_status();
	#$obj['backorders'] = $product->get_backorders();
	$obj['sold_individually'] = $product->get_sold_individually();
	#$obj['purchase_note'] = $product->get_purchase_note();
	#$obj['shipping_class_id'] = $product->get_shipping_class_id();
	
	// Get Product Dimensions
	
	$obj['weight'] = $product->get_weight();
	$obj['length'] = $product->get_length();
	$obj['width'] = $product->get_width();
	$obj['height'] = $product->get_height();
	$obj['dimensions'] = $product->get_dimensions();
	
	// Get Linked Products
	
	#$obj['upsell_ids'] = $product->get_upsell_ids();
	#$obj['cross_sell_id'] = $product->get_cross_sell_ids();
	$obj['parent_id'] = $product->get_parent_id();
	
	// Get Product Taxonomies
	
	$terms = get_terms( 'product_tag' );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
		foreach ( $terms as $term ) {
			$obj['tags'][] = $term->name;
		}
	}

	$obj['categories'] = [];
	$category_ids = $product->get_category_ids();

	foreach ($category_ids as $cat_id){
		$terms = get_term_by( 'id', $cat_id, 'product_cat' );
		$obj['categories'][] = [
			'name' => $terms->name,
			'slug' => $terms->slug,
			'description' => $terms->description
		];
	}
		
	
	// Get Product Downloads
	
	#$obj['downloads'] = $product->get_downloads();
	#$obj['download_expiry'] = $product->get_download_expiry();
	#$obj['downloadable'] = $product->get_downloadable();
	#$obj['download_limit'] = $product->get_download_limit();
	
	// Get Product Images
	
	#$obj['image_id'] = $product->get_image_id();
	$obj['image'] = $get_src($product->get_image());

	$gallery_image_ids = $product->get_gallery_image_ids();
		
	$obj['gallery_images'] = [];
	foreach ($gallery_image_ids as $giid){
		$obj['gallery_images'][] = wp_get_attachment_image_src($giid, 'large');
	}	

	// Get Product Reviews
	
	#$obj['reviews_allowed'] = $product->get_reviews_allowed();
	#$obj['rating_counts'] = $product->get_rating_counts();
	#$obj['average_rating'] = $product->get_average_rating();
	#$obj['review_count'] = $product->get_review_count();


	// Get Product Variations and Attributes

	$variation_ids = $product->get_children(); // get variations

	$obj['variations'] = $product->get_available_variations();
	$obj['default_attributes'] = $product->get_default_attributes();
		

	dd($obj);
	/////send($obj);
}

function create_prod($req)
{
    $data = $req->get_body();

    if ($data === null){
        throw new \Exception("Body está vacio");
    }

    $data = json_decode($data, true);

    if ($data === null){
        throw new \Exception("Invalid JSON");
    }

	dd($data);

	//$product_id = create_product($args);
}


/*
	/wp-json/connector/v1/xxxxx
*/

#	GET /wp-json/connector/v1/products
add_action( 'rest_api_init', function () {
	register_rest_route( 'connector/v1', '/products', array(
		'methods' => 'GET',
		'callback' => 'get_products',
        'permission_callback' => '__return_true'
	) );
	
	#	POST /wp-json/connector/v1/products
	register_rest_route( 'connector/v1', '/products', array(
		'methods' => 'POST',
		'callback' => 'create_prod',
        'permission_callback' => '__return_true'
	) );

	register_rest_route( 'connector/v1', '/products', array(
		'methods' => 'DELETE',
		'callback' => 'delete_product',
        'permission_callback' => '__return_true'
	) );

	// idealmente hacer un PATCH en vez de un PUT
	register_rest_route( 'connector/v1', '/products', array(
		'methods' => 'PATCH',
		'callback' => 'update_product',
        'permission_callback' => '__return_true'
	) );
} );





