<?php

namespace connector;

use connector\libs\Sync;
use connector\libs\Files;
use connector\libs\Strings;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/libs/Sync.php';
require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Strings.php';

//if (php_sapi_name() != "cli") {
//    return;
//}

set_time_limit(0);

/*
    Si ya hay otra instancia corriendo abortar
*/

$ps    = shell_exec("ps ax -o pid,cmd | grep 'php sync.php' | cut -d' ' -f2-");
$cmds  = explode(PHP_EOL, $ps);

$instances = 0;
foreach ($cmds as $cmd){
    if ($cmd == "php sync.php" || Strings::endsWith('/php sync.php', $cmd)){
        $instances++;
    }
}

if ($instances > 1){
    exit;
}


global $wpdb;

$sql  = "SELECT * FROM `{$wpdb->prefix}initial_load`";
$data = $wpdb->get_results($sql);

foreach ($data as $dato){
    $cms    = $dato->cms;
    $vendor = $dato->vendor_slug;

    switch ($cms){
        case 'wc': 
            Sync::getInitialDataFromWooCommerce($vendor);            
        break;

        case 'shopi': 
            Sync::getInitialDataFromShopify($vendor);
        break;

        default:
            Files::logger("El cms '$cms' es desconocido");
            continue 2;
    }

    $wpdb->query("DELETE FROM `{$wpdb->prefix}initial_load` WHERE cms = '$cms' AND vendor_slug = '$vendor'");
}
    

Sync::processInitialDataFromShopify();
Sync::processInitialDataFromWooCommerce();


// Procesa periódicamente nuevos productos o actualizaciones
Sync::getUpdatedDataFromWooCommerce();



