<?php

namespace connector;

use connector\libs\Sync;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/libs/Sync.php';

if (php_sapi_name() != "cli") {
    return;
}

Sync::getDataFromWooCommerce();






