<?php
/**
 * Plugin Name: Key Highlights Callout
 * Description: Adds a "Key Highlights" box to the top of posts. Demonstrates checkboxes and textareas in Meta Boxes.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Register the Meta Box
 */
function kk_khc_load_textdomain() {
    load_plugin_textdomain('kk-key-highlights', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'kk_khc_load_textdomain');

function kk_khc_add_meta_box() {
    add_meta_box(
        'kk_khc_highlights_box',          // Unique ID
        __('Key Highlights', 'kk-key-highlights'),              // Title
        'kk_khc_render_meta_box',         // Callback
        'post',                        // Screen (Post Type)
        'normal',                      // Context (below editor)
        'high'                         // Priority
    );
}

add_action('add_meta_boxes', 'kk_khc_add_meta_box');

function kk_khc_enqueue_admin_scripts() {
    global $pagenow;
    if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
        wp_enqueue_style(
            'kk-khc-admin-style', 
            plugin_dir_url(__FILE__) . 'css/admin.css', 
            array(), 
            '1.0.0'
        );
    }
}
add_action('admin_enqueue_scripts', 'kk_khc_enqueue_admin_scripts');

function kk_khc_enqueue_scripts() {
    if (is_single()) {
        wp_enqueue_style(
            'kk-khc-style', 
            plugin_dir_url(__FILE__) . 'css/callout.css', 
            array(), 
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'kk_khc_enqueue_scripts');

/**
 * 2. Render the Meta Box HTML
 */
function kk_khc_render_meta_box($post) {
    // Security Nonce
    wp_nonce_field('kk_khc_save_highlights', 'kk_khc_nonce_field');

    // Retrieve existing values
    $is_enabled = get_post_meta($post->ID, '_kk_khc_is_enabled', true);
    $message    = get_post_meta($post->ID, '_kk_khc_message', true);
    ?>
    <div style="margin-top: 10px;">
        <!-- Checkbox Field -->
        <p>
            <label for="kk_khc_is_enabled">
                <input type="checkbox" id="kk_khc_is_enabled" name="kk_khc_is_enabled" value="1" <?php checked($is_enabled, '1'); ?> />
                <strong><?php esc_html_e('Enable Key Highlight for this post', 'kk-key-highlights'); ?></strong>
            </label>
        </p>

        <!-- Textarea Field -->
        <p>
            <label for="kk_khc_message" class="kk-khc-label"><?php esc_html_e('Highlight Message:', 'kk-key-highlights'); ?></label>
            <textarea id="kk_khc_message" name="kk_khc_message" rows="4" class="kk-khc-textarea"><?php echo esc_textarea($message); ?></textarea>
        </p>
        <p class="description"><?php esc_html_e('This message will appear in a styled box at the top of the post.', 'kk-key-highlights'); ?></p>
    </div>
    <?php
}

/**
 * 3. Save the Data
 */
function kk_khc_save_meta_box_data($post_id) {
    // A. Verify Nonce
    if (!isset($_POST['kk_khc_nonce_field']) || !wp_verify_nonce($_POST['kk_khc_nonce_field'], 'kk_khc_save_highlights')) {
        return;
    }

    // B. Check Autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // C. Check Permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // D. Optimize & Save Data

    // 1. Handle Checkbox (Logic: If set, save '1'. If not set, delete meta or save empty)
    // In WP, unchecked checkboxes don't send anything in $_POST.
    if (isset($_POST['kk_khc_is_enabled'])) {
        update_post_meta($post_id, '_kk_khc_is_enabled', '1');
    } else {
        // Optimally, we can delete the key if it's not enabled to keep DB clean
        delete_post_meta($post_id, '_kk_khc_is_enabled');
    }

    // 2. Handle Textarea
    if (isset($_POST['kk_khc_message'])) {
        // Sanitize multi-line text
        $sanitized_message = sanitize_textarea_field($_POST['kk_khc_message']);
        update_post_meta($post_id, '_kk_khc_message', $sanitized_message);
    }
}
add_action('save_post', 'kk_khc_save_meta_box_data');

/**
 * 4. Display on Frontend
 */
function kk_khc_display_callout($content) {
    // Only show on single post pages and inside the main loop
    if (!is_singular('post') || !is_main_query()) {
        return $content;
    }

    // Check if enabled
    $is_enabled = get_post_meta(get_the_ID(), '_kk_khc_is_enabled', true);
    if ($is_enabled !== '1') {
        return $content;
    }

    // Get the message
    $message = get_post_meta(get_the_ID(), '_kk_khc_message', true);
    if (empty($message)) {
        return $content;
    }

    // Build HTML (Sanitize output with esc_html for text, or wp_kses_post if we allowed HTML)
    // Using esc_html() here since we used sanitize_textarea_field() which strips tags.
    $escaped_message = nl2br(esc_html($message)); // nl2br preserves line breaks
    
    $html = '
    <div class="khc-callout-box">
        <h4 class="khc-callout-title">' . esc_html__('Key Highlight', 'kk-key-highlights') . '</h4>
        <div class="khc-callout-content">' . $escaped_message . '</div>
    </div>';

    return $html . $content;
}
add_filter('the_content', 'kk_khc_display_callout');
