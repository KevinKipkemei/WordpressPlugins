<?php

/**
 * Plugin Name: Author Twitter
 * Description: A plugin that allows the author to add in their twitter handle to a post
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if (!defined('ABSPATH')) {
    exit;
}

function ath_register_meta_box()
{
    add_meta_box(
        'ath_author_meta_box',
        'Author Twitter Handle',
        'ath_render_meta_box',
        'post',
        'side',
    );
}

add_action('add_meta_boxes', "ath_register_meta_box");

//the HTML callback function

function ath_render_meta_box($post)
{
    wp_nonce_field('ath_save_twitter_handle', 'ath_twitter_nonce');

    $twitter_handle = get_post_meta($post->ID, 'ath_twitter_handle', true);

    echo '<label for="ath_twitter_handle">Twitter Handle without @</label>';
    echo '<input type="text" id="ath_twitter_handle" name="ath_twitter_handle" value="' . esc_attr($twitter_handle) . '">';
}

//saving our data
function ath_save_data($post_id)
{
    //nonce verification
    if (!isset($_POST['ath_twitter_nonce']) || !wp_verify_nonce($_POST['ath_twitter_nonce'], 'ath_save_twitter_handle')) {
        return;
    }

    //user permission verification
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    //autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    //sanitize and update/create data entry
    if (isset($_POST['ath_twitter_handle'])) {
        update_post_meta($post_id, 'ath_twitter_handle', sanitize_text_field($_POST['ath_twitter_handle']));
    }
}
add_action("save_post", "ath_save_data");

//frontend display twitter link
function ath_frontend_link($content)
{
    if (!is_single() || !is_main_query()) {
        return $content;
    }

    $twitter_handle = get_post_meta(get_the_ID(), 'ath_twitter_handle', true);

    if (!empty($twitter_handle)) {
        $link_html = sprintf(
            '<p>
            Follow the author on Twitter: <a href="https://twitter.com/%s" target="_blank">@%s</a>
        </p>',
            esc_attr($twitter_handle),
            esc_html($twitter_handle)
        );
        $content = $content . $link_html;
    }

    return $content;
}

add_filter('the_content', 'ath_frontend_link');
