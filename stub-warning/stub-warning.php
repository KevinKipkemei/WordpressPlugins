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

    // Getting the dynamic threshold from the database (default to 100)
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
        'Stub Warning Settings',      // Browser tab title
        'Stub Warning',               // Text shown in the sidebar menu
        'manage_options',             // User capability required (admin only)
        'stub-warning-settings',      // URL slug (admin.php?page=stub-warning-settings)
        'sw_render_settings_page'     // The function that draws the page
    );
}
add_action('admin_menu', 'sw_settings_menu');

// The "Drawer/Template" function for the settings page.
function sw_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Stub Warning Settings</h1>
        <!-- WordPress handles the form submission to options.php automatically -->
        <form method="post" action="options.php">
            <?php
            // This outputs hidden security fields (nonces) so WP knows the request is legit
            settings_fields('sw_settings_group');

            // This tells WP to "go find every section and field I registered for this page"
            do_settings_sections('stub-warning-settings');

            // Standard WP styled submit button
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registering the settings
function sw_register_settings()
{

    // Register the setting: This tells WP to allow saving 'sw_word_count_threshold' in wp_options table.
    register_setting('sw_settings_group', 'sw_word_count_threshold', [
        'type' => 'integer',
        'sanitize_callback' => 'absint', // Security: Force the input to be an Absolute Integer
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
