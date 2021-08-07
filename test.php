<?php

use connector\libs\Debug;
use connector\libs\Files;
use connector\libs\Strings;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../../wp-load.php';
include_once(__DIR__ . '/../../../wp-admin/includes/image.php' );

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}


class Test
{
   	
    function __construct(){
        
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


    function addImagesToPost($pid, Array $image_ids){
        $image_ids = implode(",", $image_ids);
        update_post_meta($pid, '_product_image_gallery', $image_ids);
    }

    // /wp-content/plugins/connector/test.php
    function create(){
        $arr = include(__DIR__ . '/logs/product_dump.php');

        $attach_id = $this->uploadImage('http://woo2.lan/wp-content/uploads/2021/07/pantalonELBA_HUPIT1-scaled-700x1050-1.jpg');
        #dd($attach_id); // 49, 58

        #exit;

        //dd($arr);
        $product_id = create_product($arr);
        dd($product_id, 'product_id');

        $this->addImagesToPost($product_id, [$attach_id, 49,58]);  // --ok
        $this->setDefaultImage($product_id, $attach_id); // -- ok
    }

   
}


$test = new Test();

$test->create();
//$test->test_set_images();

//