<?php

# INSTALL

global $wpdb;

$table_name = $wpdb->prefix . "product_updates";
$my_products_db_version = '1.0.0';
$charset_collate = $wpdb->get_charset_collate();

if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
{
    $sql = "CREATE TABLE $table_name (
        `id` int(11) NOT NULL,
        `vendor_slug` varchar(60) NOT NULL,
        `sku` varchar(60) NOT NULL,
        `operation` varchar(10) NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $ok = dbDelta($sql);

    if (!$ok){
        return;
    }

    $ok = $wpdb->query("ALTER TABLE `$table_name`
    ADD PRIMARY KEY (`id`);");

    if (!$ok){
        return;
    }

    $ok = $wpdb->query("ALTER TABLE `$table_name`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");     

    add_option('reactor_db_version', $my_products_db_version);
}