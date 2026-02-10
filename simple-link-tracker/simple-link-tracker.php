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

global $kk_slt_db_version;
$kk_slt_db_version = '1.0';

/**
 * 1. Database Setup (On Activation)
 */
function kk_slt_load_textdomain() {
    load_plugin_textdomain('kk-simple-link-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'kk_slt_load_textdomain');

function kk_slt_install_table() {
    global $wpdb, $kk_slt_db_version;

    $table_name = $wpdb->prefix . 'kk_slt_links';
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

    // Save the DB version
    add_option('kk_slt_db_version', $kk_slt_db_version);
}

// Register the activation hook
register_activation_hook(__FILE__, 'kk_slt_install_table');

/**
 * Check for DB updates
 */
function kk_slt_update_db_check() {
    global $kk_slt_db_version;
    if (get_site_option('kk_slt_db_version') != $kk_slt_db_version) {
        kk_slt_install_table();
    }
}
add_action('plugins_loaded', 'kk_slt_update_db_check');

/**
 * 2. Admin Menu & UI
 */
function kk_slt_enqueue_styles($hook) {
    // Only load on our specific admin page
    if ($hook !== 'toplevel_page_kk-slt-link-tracker') {
        return;
    }
    
    wp_enqueue_style(
        'kk-slt-admin-style', 
        plugin_dir_url(__FILE__) . 'css/admin.css', 
        array(), 
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'kk_slt_enqueue_styles');

function kk_slt_add_admin_menu() {
    add_menu_page(
        __('Link Tracker', 'kk-simple-link-tracker'),        // Page Title
        __('Link Tracker', 'kk-simple-link-tracker'),        // Menu Title
        'manage_options',      // Capability (Admins only)
        'kk-slt-link-tracker',    // Menu Slug
        'kk_slt_render_admin_page', // Callback function
        'dashicons-admin-links', // Icon
        6                        // Position
    );
}
add_action('admin_menu', 'kk_slt_add_admin_menu');

function kk_slt_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kk_slt_links';

    // 3. Handle Form Submission
    if (isset($_POST['kk_slt_submit_link'])) {
        // Verify Nonce
        if (!isset($_POST['kk_slt_nonce_field']) || !wp_verify_nonce($_POST['kk_slt_nonce_field'], 'kk_slt_save_new_link')) {
             echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed.', 'kk-simple-link-tracker') . '</p></div>';
        } else {
            // Sanitize Input
            $name   = sanitize_text_field($_POST['kk_slt_name']);
            $slug   = sanitize_title($_POST['kk_slt_slug']); // Ensures URL-friendly slug
            $target = esc_url_raw($_POST['kk_slt_target']);

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

            echo '<div class="notice notice-success"><p>' . esc_html__('Link created successfully!', 'kk-simple-link-tracker') . '</p></div>';
        }
    }

    // Fetch all links
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i ORDER BY created_at DESC",
            $table_name
        )
    );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Simple Link Tracker', 'kk-simple-link-tracker'); ?></h1>
        
        <!-- Add New Link Form -->
        <div class="kk-slt-admin-container">
            <h2><?php esc_html_e('Add New Link', 'kk-simple-link-tracker'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('kk_slt_save_new_link', 'kk_slt_nonce_field'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="kk_slt_name"><?php esc_html_e('Link Name', 'kk-simple-link-tracker'); ?></label></th>
                        <td><input name="kk_slt_name" type="text" id="kk_slt_name" value="" class="regular-text" required placeholder="e.g. My Twitter"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slt_slug"><?php esc_html_e('URL Slug', 'kk-simple-link-tracker'); ?></label></th>
                        <td>
                            <code><?php echo home_url('/'); ?></code>
                            <input name="kk_slt_slug" type="text" id="kk_slt_slug" value="" class="regular-text" required placeholder="twitter">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kk_slt_target"><?php esc_html_e('Target URL', 'kk-simple-link-tracker'); ?></label></th>
                        <td><input name="kk_slt_target" type="url" id="kk_slt_target" value="" class="regular-text" required placeholder="https://twitter.com/myprofile"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="kk_slt_submit_link" id="submit" class="button button-primary" value="<?php esc_attr_e('Add Link', 'kk-simple-link-tracker'); ?>">
                </p>
            </form>
        </div>

        <!-- Existing Links Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50"><?php esc_html_e('ID', 'kk-simple-link-tracker'); ?></th>
                    <th><?php esc_html_e('Name', 'kk-simple-link-tracker'); ?></th>
                    <th><?php esc_html_e('Slug (Mask)', 'kk-simple-link-tracker'); ?></th>
                    <th><?php esc_html_e('Target URL', 'kk-simple-link-tracker'); ?></th>
                    <th width="100"><?php esc_html_e('Clicks', 'kk-simple-link-tracker'); ?></th>
                    <th width="150"><?php esc_html_e('Date Created', 'kk-simple-link-tracker'); ?></th>
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
                        <td colspan="6"><?php esc_html_e('No links found. Create one above!', 'kk-simple-link-tracker'); ?></td>
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
function kk_slt_handle_redirects() {
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
    $table_name = $wpdb->prefix . 'kk_slt_links';
    
    // We use $wpdb->prepare for security even though $request_path comes from server
    $link = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE slug = %s", 
            $table_name, 
            $request_path
        )
    );

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
add_action('template_redirect', 'kk_slt_handle_redirects');
