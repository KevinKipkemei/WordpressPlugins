<?php

/**
 * Plugin Name: Stub Warning
 * Description: Adds a warning message to posts and pages that are marked as stubs.
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

function sw_stub_warning($content)
{
    if (!is_single() || !is_main_query()) {
        return $content;
    }

    $clean_content = strip_tags($content);
    $word_count = str_word_count($clean_content);

    $threshold = get_option('sw_word_count_threshold', 100);

    $stub_warning_html = '<div style="background: #ffe6e6; border: 1px solid red; padding: 10px; margin-bottom: 20px; color: red;">'
        . get_option('sw_warning_message', 'This is a short stub article.')
        . '</div>';

    if ($word_count < $threshold) {
        return $stub_warning_html . $content;
    }

    return $content;
}

add_filter('the_content', 'sw_stub_warning');



// Adding the "Stub Warning" option under the "Settings" menu in the dashboard
function sw_settings_menu()
{
    add_options_page(
        'Stub Warning Settings',
        'Stub Warning',
        'manage_options',
        'stub-warning-settings',
        'sw_render_settings_page'
    );
}
add_action('admin_menu', 'sw_settings_menu');

// The "Drawer/Template" function for the settings page.
function sw_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Stub Warning Settings</h1>
        <form method="post" action="options.php">
            <?php
            // Outputting hidden security fields
            settings_fields('sw_settings_group');

            do_settings_sections('stub-warning-settings');

            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registering the settings
function sw_register_settings()
{

    // Register the settings in wp_options table.
    register_setting('sw_settings_group', 'sw_word_count_threshold', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 100,
    ]);

    register_setting('sw_settings_group', 'sw_warning_message', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'This is a short stub article.',
    ]);

    // Add a Section: A group of fields with a title and an optional description.
    add_settings_section(
        'sw_main_section',
        'Main Configuration',
        'sw_section_callback',
        'stub-warning-settings'
    );

    add_settings_section(
        'sw_message_section',
        'Message Configuration',
        'sw_message_section_callback',
        'stub-warning-settings'
    );

    // Add the Field: One specific setting row (Label + Input)
    add_settings_field(
        'sw_word_count_threshold',
        'Word Count Threshold',
        'sw_field_render_callback',
        'stub-warning-settings',
        'sw_main_section'
    );

    add_settings_field(
        'sw_warning_message',
        'Warning Message',
        'sw_message_field_render_callback',
        'stub-warning-settings',
        'sw_message_section'
    );
}
add_action('admin_init', 'sw_register_settings');

// Helper callback functions to render specific parts of the page.

// This echoes the text just below the section header
function sw_section_callback()
{
    echo '<p>Adjust the threshold for when a post is considered a short "stub".</p>';
}

function sw_message_section_callback()
{
    echo '<p>Adjust the message that will be displayed when a post is considered a short "stub".</p>';
}


// This echoes the actual <input> tag.
function sw_field_render_callback()
{
    $value = get_option('sw_word_count_threshold', 100);
    echo '<input type="number" name="sw_word_count_threshold" value="' . esc_attr($value) . '" />';
}

function sw_message_field_render_callback()
{
    $value = get_option('sw_warning_message', 'This is a short stub article.');
    echo '<input type="text" name="sw_warning_message" value="' . esc_attr($value) . '" />';
}
