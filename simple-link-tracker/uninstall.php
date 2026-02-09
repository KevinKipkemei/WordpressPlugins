<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SimpleLinkTracker
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the custom table
$table_name = $wpdb->prefix . 'kk_slt_links';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
