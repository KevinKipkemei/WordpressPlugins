<?php

/**
 * Plugin Name: Basic Reading Time
 * Description: Adds a reading time estimate to posts and pages.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */


if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param string $content
 * @return string
 */

/**
 * 1. Load Text Domain
 */
function kk_brt_load_textdomain() {
    load_plugin_textdomain('kk-basic-reading-time', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'kk_brt_load_textdomain');

/**
 * @param string $content
 * @return string
 */
function kk_brt_reading_time($content)
{
    $wpm = 200;

    if (!is_single() || !is_main_query()) {
        return $content;
    }

    $clean_content = strip_tags($content);
    $word_count = str_word_count($clean_content);
    $reading_time = ceil($word_count / $wpm);
    
    // Use the correct text domain for translation
    // _n() handles singular/plural logic for languages with complex plural rules
    $label = sprintf(
        _n('%s minute', '%s minutes', $reading_time, 'kk-basic-reading-time'),
        $reading_time
    );

    // Make the whole message translatable
    $message = sprintf(
        esc_html__('Estimated reading time: %s', 'kk-basic-reading-time'), 
        $label
    );

    $reading_time_html = sprintf('<div class="kk-brt-time"><p>%s</p></div>', $message);

    return $reading_time_html . $content;
}

add_filter('the_content', 'kk_brt_reading_time');