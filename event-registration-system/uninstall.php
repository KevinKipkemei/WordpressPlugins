<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package EventRegistrationSystem
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop the custom tables
$events_table = $wpdb->prefix . 'kk_ers_events';
$registrations_table = $wpdb->prefix . 'kk_ers_registrations';

$wpdb->query("DROP TABLE IF EXISTS $registrations_table");
$wpdb->query("DROP TABLE IF EXISTS $events_table");

// Delete the database version option
delete_option('kk_ers_db_version');
