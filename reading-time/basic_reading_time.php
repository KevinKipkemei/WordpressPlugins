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

function brt_reading_time($content)
{
    $wpm = 200;

    if (!is_single() || !is_main_query()) {
        return $content;
    }

    $clean_content = strip_tags($content);
    $word_count = str_word_count($clean_content);
    $reading_time = ceil($word_count / $wpm);
    $label = _n('minute', 'minutes', $reading_time, 'basic_reading_time');
    $reading_time_message = sprintf('<div>'
        . '<p>Estimated reading time : %s %s </p>'
        . '</div>', $reading_time, $label);

    return $reading_time_message . $content;
}

add_filter('the_content', 'brt_reading_time');