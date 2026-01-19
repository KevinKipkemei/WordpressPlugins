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
function khc_add_meta_box() {
    add_meta_box(
        'khc_highlights_box',          // Unique ID
        'Key Highlights',              // Title
        'khc_render_meta_box',         // Callback
        'post',                        // Screen (Post Type)
        'normal',                      // Context (below editor)
        'high'                         // Priority
    );
}
add_action('add_meta_boxes', 'khc_add_meta_box');

/**
 * 2. Render the Meta Box HTML
 */
function khc_render_meta_box($post) {
    // Security Nonce
    wp_nonce_field('khc_save_highlights', 'khc_nonce_field');

    // Retrieve existing values
    $is_enabled = get_post_meta($post->ID, '_khc_is_enabled', true);
    $message    = get_post_meta($post->ID, '_khc_message', true);
    ?>
    <div style="margin-top: 10px;">
        <!-- Checkbox Field -->
        <p>
            <label for="khc_is_enabled">
                <input type="checkbox" id="khc_is_enabled" name="khc_is_enabled" value="1" <?php checked($is_enabled, '1'); ?> />
                <strong>Enable Key Highlight for this post</strong>
            </label>
        </p>

        <!-- Textarea Field -->
        <p>
            <label for="khc_message" style="display:block; margin-bottom:5px;">Highlight Message:</label>
            <textarea id="khc_message" name="khc_message" rows="4" style="width:100%;"><?php echo esc_textarea($message); ?></textarea>
        </p>
        <p class="description">This message will appear in a styled box at the top of the post.</p>
    </div>
    <?php
}

/**
 * 3. Save the Data
 */
function khc_save_meta_box_data($post_id) {
    // A. Verify Nonce
    if (!isset($_POST['khc_nonce_field']) || !wp_verify_nonce($_POST['khc_nonce_field'], 'khc_save_highlights')) {
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
    if (isset($_POST['khc_is_enabled'])) {
        update_post_meta($post_id, '_khc_is_enabled', '1');
    } else {
        // Optimally, we can delete the key if it's not enabled to keep DB clean
        delete_post_meta($post_id, '_khc_is_enabled');
    }

    // 2. Handle Textarea
    if (isset($_POST['khc_message'])) {
        // Sanitize multi-line text
        $sanitized_message = sanitize_textarea_field($_POST['khc_message']);
        update_post_meta($post_id, '_khc_message', $sanitized_message);
    }
}
add_action('save_post', 'khc_save_meta_box_data');

/**
 * 4. Display on Frontend
 */
function khc_display_callout($content) {
    // Only show on single post pages and inside the main loop
    if (!is_singular('post') || !is_main_query()) {
        return $content;
    }

    // Check if enabled
    $is_enabled = get_post_meta(get_the_ID(), '_khc_is_enabled', true);
    if ($is_enabled !== '1') {
        return $content;
    }

    // Get the message
    $message = get_post_meta(get_the_ID(), '_khc_message', true);
    if (empty($message)) {
        return $content;
    }

    // Build HTML (Sanitize output with esc_html for text, or wp_kses_post if we allowed HTML)
    // Using esc_html() here since we used sanitize_textarea_field() which strips tags.
    $escaped_message = nl2br(esc_html($message)); // nl2br preserves line breaks
    
    $html = '
    <div class="khc-callout-box" style="background-color: #e0f7fa; border-left: 5px solid #006064; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <h4 style="margin-top: 0; color: #006064;">Key Highlight</h4>
        <div style="color: #333;">' . $escaped_message . '</div>
    </div>';

    return $html . $content;
}
add_filter('the_content', 'khc_display_callout');
