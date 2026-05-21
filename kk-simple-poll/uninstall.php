<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Drop the custom table
$table_name = $wpdb->prefix . 'kk_sp_votes';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// 2. Delete all options/settings related to the plugin
delete_option('kk_sp_show_results_before_vote');
delete_option('kk_sp_results_style');
delete_option('kk_sp_prevention_method');

// 3. Delete all post meta for polls
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_kk_sp_options'");

// 4. Delete all polls (the posts themselves)
$polls = get_posts([
    'post_type'      => 'kk_poll',
    'post_status'    => 'any',
    'numberposts'    => -1,
    'fields'         => 'ids' // Only get post IDs to save memory
]);

foreach ($polls as $poll_id) {
    wp_delete_post($poll_id, true); // true forces deletion bypassing trash
}
