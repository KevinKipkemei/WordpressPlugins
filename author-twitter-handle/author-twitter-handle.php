<?php

/**
 * Plugin Name: Author Twitter,
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

add_action('add_meta_box', "ath_register_meta_box");

//the HTML callback function

function ath_render_meta_box($post)
{
    wp_nonce_field('ath_save_twitter_handle', 'ath_twitter_nonce');

    $twitter_handle = get_post_meta($post->ID, 'ath_twitter_handle', true);

    echo '<label for="ath_twitter_handle">Twitter Handle</label>';
    echo '<input type="text" id="ath_twitter_handle" name="ath_twitter_handle" value="' . esc_attr($twitter_handle) . '">';
}