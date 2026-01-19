<?php

/**
 * Plugin Name: Copyright Note
 * Description: A simple plugin that appends a copyright note on a post
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 */

if (!defined('ABSPATH')) {
    exit;
}
;

/**
 * @param string $content
 * @return string
 */

function cn_append($content)
{

    if (!is_single() || !is_main_query()) {
        return $content;
    }

    $name = 'Kevin Kipkemei';
    $year = date('Y');
    $copyright_message_HTML = sprintf('<div style="margin-top: 30px; padding-top: 10px; color: #666; font-size: 12px">
    <p> %s %s. All rights reserved.</p>
    </div>', $name, $year);

    return $content . $copyright_message_HTML;
}

add_filter('the_content', 'cn_append');