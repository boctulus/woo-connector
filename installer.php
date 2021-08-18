<?php

# INSTALL

global $wpdb;

$table_name = $wpdb->prefix . "shopi_webhooks";
$my_products_db_version = '1.0.0';
$charset_collate = $wpdb->get_charset_collate();

if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name ) {

    $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL,
            `shop` varchar(60) NOT NULL,
            `topic` varchar(30) NOT NULL,
            `api_version` varchar(20) NOT NULL,
            `address` varchar(255) NOT NULL,
            `remote_id` varchar(20) NOT NULL,
            `created_at` varchar(25) NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $ok = dbDelta($sql);

    if (!$ok){
        return;
    }

    $ok = $wpdb->query("ALTER TABLE `$table_name`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `shop` (`shop`,`topic`);");

    if (!$ok){
        return;
    }

    $ok = $wpdb->query("ALTER TABLE `$table_name`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");     

    add_option('connector_db_version', $my_products_db_version);
}