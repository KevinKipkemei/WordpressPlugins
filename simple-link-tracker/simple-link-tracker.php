<?php
/**
 * Plugin Name: Simple Link Tracker
 * Description: A custom URL shortener that tracks clicks. Demonstrates Custom Database Tables and Admin Pages.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Database Setup (On Activation)
 */
function slt_install_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'slt_links';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the table
    // id: Auto-incrementing Primary Key
    // name: Human readable title
    // slug: The URL masking part
    // target_url: Where it goes
    // clicks: The counter
    // created_at: Timestamp
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        slug varchar(100) NOT NULL,
        target_url varchar(255) NOT NULL,
        clicks mediumint(9) DEFAULT 0 NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // We must include this file to use dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // dbDelta examines the current table structure and ONLY adds what is missing.
    dbDelta($sql);
}

// Register the activation hook
register_activation_hook(__FILE__, 'slt_install_table');

/**
 * 2. Admin Menu & UI
 */
function slt_add_admin_menu() {
    add_menu_page(
        'Link Tracker',        // Page Title
        'Link Tracker',        // Menu Title
        'manage_options',      // Capability (Admins only)
        'slt-link-tracker',    // Menu Slug
        'slt_render_admin_page', // Callback function
        'dashicons-admin-links', // Icon
        6                        // Position
    );
}
add_action('admin_menu', 'slt_add_admin_menu');

function slt_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'slt_links';

    // 3. Handle Form Submission
    if (isset($_POST['slt_submit_link'])) {
        // Verify Nonce
        if (!isset($_POST['slt_nonce_field']) || !wp_verify_nonce($_POST['slt_nonce_field'], 'slt_save_new_link')) {
             echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
        } else {
            // Sanitize Input
            $name   = sanitize_text_field($_POST['slt_name']);
            $slug   = sanitize_title($_POST['slt_slug']); // Ensures URL-friendly slug
            $target = esc_url_raw($_POST['slt_target']);

            // Insert into DB
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'target_url' => $target,
                    'created_at' => current_time('mysql')
                )
            );

            echo '<div class="notice notice-success"><p>Link created successfully!</p></div>';
        }
    }

    // Fetch all links
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    ?>
    <div class="wrap">
        <h1>Simple Link Tracker</h1>
        
        <!-- Add New Link Form -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; max-width: 800px;">
            <h2>Add New Link</h2>
            <form method="post" action="">
                <?php wp_nonce_field('slt_save_new_link', 'slt_nonce_field'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="slt_name">Link Name</label></th>
                        <td><input name="slt_name" type="text" id="slt_name" value="" class="regular-text" required placeholder="e.g. My Twitter"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slt_slug">URL Slug</label></th>
                        <td>
                            <code><?php echo home_url('/'); ?></code>
                            <input name="slt_slug" type="text" id="slt_slug" value="" class="regular-text" required placeholder="twitter">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slt_target">Target URL</label></th>
                        <td><input name="slt_target" type="url" id="slt_target" value="" class="regular-text" required placeholder="https://twitter.com/myprofile"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="slt_submit_link" id="submit" class="button button-primary" value="Add Link">
                </p>
            </form>
        </div>

        <!-- Existing Links Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th>Name</th>
                    <th>Slug (Mask)</th>
                    <th>Target URL</th>
                    <th width="100">Clicks</th>
                    <th width="150">Date Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><strong><?php echo esc_html($row->name); ?></strong></td>
                            <td>
                                <a href="<?php echo home_url('/' . esc_attr($row->slug)); ?>" target="_blank">
                                    /<?php echo esc_html($row->slug); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($row->target_url); ?>" target="_blank">
                                    <?php echo esc_html($row->target_url); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($row->clicks); ?></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">No links found. Create one above!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * 4. Redirect Logic (Frontend)
 */
function slt_handle_redirects() {
    // Only run on frontend
    if (is_admin()) {
        return;
    }

    global $wpdb;

    // Get the requested path
    // trim path steps:
    // 1. parse_url to ignore query strings (?foo=bar)
    // 2. trim leading/trailing slashes
    $request_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // If WP is in a subdirectory (e.g. /wp/), we might need to strip that too.
    $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
    if ($home_path && strpos($request_path, $home_path) === 0) {
        $request_path = trim(substr($request_path, strlen($home_path)), '/');
    }

    // Prepare query
    $table_name = $wpdb->prefix . 'slt_links';
    
    // We use $wpdb->prepare for security even though $request_path comes from server
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $request_path));

    if ($link) {
        // 1. Increment Clicks
        $wpdb->update(
            $table_name,
            array('clicks' => $link->clicks + 1), // Data
            array('id' => $link->id)              // Where
        );

        // 2. Redirect
        // 301 = Permanent, 302 = Temporary (Use 302 for tracking usually, or 307)
        wp_redirect($link->target_url, 302);
        exit;
    }
}
add_action('template_redirect', 'slt_handle_redirects');
