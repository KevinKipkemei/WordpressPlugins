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

/**
 * 1. Database Setup (On Activation)
 */
function scf_install_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'scf_messages';
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
}

register_activation_hook(__FILE__, 'scf_install_table');

/**
 * 2. Frontend Shortcode
 */
function scf_shortcode_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scf_messages';
    
    $output = '';
    $form_values = [
        'name' => '',
        'email' => '',
        'message' => ''
    ];

    // A. Handle Submission
    if (isset($_POST['scf_submit'])) {
        // 1. Verify Nonce
        if (!isset($_POST['scf_nonce_field']) || !wp_verify_nonce($_POST['scf_nonce_field'], 'scf_send_message')) {
            return '<p style="color:red;">Security check failed.</p>';
        }

        // 2. Sanitize & Capture Values (Sticky Inputs)
        // We capture them even if we fail later, so we can refill the form.
        $form_values['name']    = sanitize_text_field($_POST['scf_name']);
        $form_values['email']   = sanitize_email($_POST['scf_email']);
        $form_values['message'] = sanitize_textarea_field($_POST['scf_message']);

        // 3. Validate
        $errors = [];
        if (empty($form_values['name'])) {
            $errors[] = "Name is required.";
        }
        if (!is_email($form_values['email'])) {
            $errors[] = "Invalid email address.";
        }
        if (empty($form_values['message'])) {
            $errors[] = "Message cannot be empty.";
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
            return '<p style="color:green; border:1px solid green; padding:10px;">Thank you! Your message has been sent.</p>';
        } else {
            // If errors, build error HTML
            $output .= '<div style="color:red; border:1px solid red; padding:10px; margin-bottom:10px;">';
            foreach ($errors as $err) {
                $output .= "<p style='margin:0;'>$err</p>";
            }
            $output .= '</div>';
        }
    }

    // B. Render Form
    ob_start();
    ?>
    <div class="scf-form-container" style="max-width:400px; background:#f9f9f9; padding:20px; border:1px solid #ddd;">
        <form method="post" action="">
            <?php wp_nonce_field('scf_send_message', 'scf_nonce_field'); ?>
            
            <p>
                <label for="scf_name">Name:</label><br>
                <!-- Sticky Input: We echo the saved value back into the input -->
                <input type="text" name="scf_name" id="scf_name" value="<?php echo esc_attr($form_values['name']); ?>" required style="width:100%;">
            </p>
            <p>
                <label for="scf_email">Email:</label><br>
                <input type="email" name="scf_email" id="scf_email" value="<?php echo esc_attr($form_values['email']); ?>" required style="width:100%;">
            </p>
            <p>
                <label for="scf_message">Message:</label><br>
                <textarea name="scf_message" id="scf_message" rows="5" required style="width:100%;"><?php echo esc_textarea($form_values['message']); ?></textarea>
            </p>
            <p>
                <input type="submit" name="scf_submit" value="Send Message" style="background:#0073aa; color:white; padding:10px 20px; border:none; cursor:pointer;">
            </p>
        </form>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output; 
}
add_shortcode('simple_contact', 'scf_shortcode_handler');

/**
 * 3. Admin Menu & UI
 */
function scf_add_admin_menu() {
    add_menu_page(
        'Contact Messages',    // Page Title
        'Contact Msgs',        // Menu Title
        'manage_options',      // Capability
        'scf-contact-messsages', // Slug
        'scf_render_admin_page', // Callback
        'dashicons-email',     // Icon
        6                      // Position
    );
}
add_action('admin_menu', 'scf_add_admin_menu');

function scf_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scf_messages';

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>Contact Messages</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th width="150">Name</th>
                    <th width="200">Email</th>
                    <th>Message</th>
                    <th width="150">Date</th>
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
                        <td colspan="5">No messages yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
