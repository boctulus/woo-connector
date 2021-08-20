<?php

// Solo dev

$json = file_get_contents('logs/product_update.txt');
$json = trim(trim($json), "'");
$json = str_replace("\/", "/", $json);
$json = str_replace('\\'. '\\', '\\', $json);

$arr  = json_decode($json, true);
$str  = '<?php'. PHP_EOL. PHP_EOL . '$a = ' . var_export($arr, true) . ';';

file_put_contents('logs/response2.php', $str);
