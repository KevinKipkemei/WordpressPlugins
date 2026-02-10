<?php
/**
 * Plugin Name: Simple Contact Form
 * Description: A basic contact form that saves messages to the database. Demonstrates Frontend Form Handling and Shortcodes.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if (!defined('ABSPATH')) {
    exit;
}

global $kk_scf_db_version;
$kk_scf_db_version = '1.0';

/**
 * 1. Database Setup (On Activation)
 */
function kk_scf_load_textdomain() {
    load_plugin_textdomain('kk-simple-contact-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'kk_scf_load_textdomain');

function kk_scf_install_table() {
    global $wpdb, $kk_scf_db_version;

    $table_name = $wpdb->prefix . 'kk_scf_messages';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the table
    // id: Auto-increment
    // name: Sender's name
    // email: Sender's email
    // message: The content
    // created_at: Timestamp of submission
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Save the DB version
    add_option('kk_scf_db_version', $kk_scf_db_version);
}

register_activation_hook(__FILE__, 'kk_scf_install_table');

/**
 * Check for DB updates
 */
function kk_scf_update_db_check() {
    global $kk_scf_db_version;
    if (get_site_option('kk_scf_db_version') != $kk_scf_db_version) {
        kk_scf_install_table();
    }
}
add_action('plugins_loaded', 'kk_scf_update_db_check');

/**
 * 2. Frontend Shortcode
 */
function kk_scf_enqueue_styles() {
    wp_enqueue_style(
        'kk-scf-form-style', 
        plugin_dir_url(__FILE__) . 'css/form.css', 
        array(), 
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'kk_scf_enqueue_styles');

function kk_scf_shortcode_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kk_scf_messages';
    
    $output = '';
    $form_values = [
        'name' => '',
        'email' => '',
        'message' => ''
    ];

    // A. Handle Submission
    if (isset($_POST['kk_scf_submit'])) {
        // 1. Verify Nonce
        if (!isset($_POST['kk_scf_nonce_field']) || !wp_verify_nonce($_POST['kk_scf_nonce_field'], 'kk_scf_send_message')) {
            return '<p class="kk-scf-error-box">' . esc_html__('Security check failed.', 'kk-simple-contact-form') . '</p>';
        }

        // 2. Sanitize & Capture Values (Sticky Inputs)
        // We capture them even if we fail later, so we can refill the form.
        $form_values['name']    = sanitize_text_field($_POST['kk_scf_name']);
        $form_values['email']   = sanitize_email($_POST['kk_scf_email']);
        $form_values['message'] = sanitize_textarea_field($_POST['kk_scf_message']);

        // 3. Validate
        $errors = [];
        if (empty($form_values['name'])) {
            $errors[] = __('Name is required.', 'kk-simple-contact-form');
        }
        if (!is_email($form_values['email'])) {
            $errors[] = __('Invalid email address.', 'kk-simple-contact-form');
        }
        if (empty($form_values['message'])) {
            $errors[] = __('Message cannot be empty.', 'kk-simple-contact-form');
        }

        // 4. Process
        if (empty($errors)) {
            $wpdb->insert(
                $table_name,
                array(
                    'name'    => $form_values['name'],
                    'email'   => $form_values['email'],
                    'message' => $form_values['message'],
                    'created_at' => current_time('mysql')
                )
            );
            return '<p class="kk-scf-success-box">' . esc_html__('Thank you! Your message has been sent.', 'kk-simple-contact-form') . '</p>';
        } else {
            // If errors, build error HTML
            $output .= '<div class="kk-scf-error-box">';
            foreach ($errors as $err) {
                $output .= "<p class='kk-scf-error-msg'>$err</p>";
            }
            $output .= '</div>';
        }
    }

    // B. Render Form
    ob_start();
    ?>
    <div class="scf-form-container kk-scf-container">
        <form method="post" action="">
            <?php wp_nonce_field('kk_scf_send_message', 'kk_scf_nonce_field'); ?>
            
            <p>
                <label for="kk_scf_name"><?php esc_html_e('Name:', 'kk-simple-contact-form'); ?></label><br>
                <!-- Sticky Input: We echo the saved value back into the input -->
                <input type="text" name="kk_scf_name" id="kk_scf_name" value="<?php echo esc_attr($form_values['name']); ?>" required class="kk-scf-input">
            </p>
            <p>
                <label for="kk_scf_email"><?php esc_html_e('Email:', 'kk-simple-contact-form'); ?></label><br>
                <input type="email" name="kk_scf_email" id="kk_scf_email" value="<?php echo esc_attr($form_values['email']); ?>" required class="kk-scf-input">
            </p>
            <p>
                <label for="kk_scf_message"><?php esc_html_e('Message:', 'kk-simple-contact-form'); ?></label><br>
                <textarea name="kk_scf_message" id="kk_scf_message" rows="5" required class="kk-scf-input"><?php echo esc_textarea($form_values['message']); ?></textarea>
            </p>
            <p>
                <input type="submit" name="kk_scf_submit" value="<?php esc_attr_e('Send Message', 'kk-simple-contact-form'); ?>" class="kk-scf-submit">
            </p>
        </form>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output; 
}
add_shortcode('simple_contact', 'kk_scf_shortcode_handler');

/**
 * 3. Admin Menu & UI
 */
function kk_scf_add_admin_menu() {
    add_menu_page(
        __('Contact Messages', 'kk-simple-contact-form'),    // Page Title
        __('Contact Msgs', 'kk-simple-contact-form'),        // Menu Title
        'manage_options',      // Capability
        'kk-scf-contact-messages', // Slug
        'kk_scf_render_admin_page', // Callback
        'dashicons-email',     // Icon
        6                      // Position
    );
}
add_action('admin_menu', 'kk_scf_add_admin_menu');

function kk_scf_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kk_scf_messages';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i ORDER BY created_at DESC",
            $table_name
        )
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Contact Messages', 'kk-simple-contact-form'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50"><?php esc_html_e('ID', 'kk-simple-contact-form'); ?></th>
                    <th width="150"><?php esc_html_e('Name', 'kk-simple-contact-form'); ?></th>
                    <th width="200"><?php esc_html_e('Email', 'kk-simple-contact-form'); ?></th>
                    <th><?php esc_html_e('Message', 'kk-simple-contact-form'); ?></th>
                    <th width="150"><?php esc_html_e('Date', 'kk-simple-contact-form'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->name); ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($row->email); ?>">
                                    <?php echo esc_html($row->email); ?>
                                </a>
                            </td>
                            <td><?php echo nl2br(esc_html($row->message)); ?></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No messages yet.', 'kk-simple-contact-form'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
