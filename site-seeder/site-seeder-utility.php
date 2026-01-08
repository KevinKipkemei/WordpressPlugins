<?php
/**
 * Plugin Name: Site Seeder Utility
 * Description: One-time utility to seed the site with learning content. Deactivate and delete after use.
 */

if (!defined('ABSPATH'))
    exit;

function sw_run_site_seeder()
{
    // 1. Create Categories
    $categories = ['Technology', 'Lifestyle', 'Education', 'Reviews'];
    foreach ($categories as $cat) {
        if (!get_term_by('name', $cat, 'category')) {
            wp_insert_term($cat, 'category');
        }
    }

    // 2. Create Tags
    $tags = ['Tutorial', 'Opinion', 'News', 'Guide'];
    foreach ($tags as $tag) {
        if (!get_term_by('name', $tag, 'post_tag')) {
            wp_insert_term($tag, 'post_tag');
        }
    }

    // 3. Create Hierarchical Pages
    $services_page_check = get_page_by_title('Services');
    if (!$services_page_check) {
        $services_page = wp_insert_post([
            'post_title' => 'Services',
            'post_content' => 'We offer a wide range of web development and consulting services.',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);

        if ($services_page) {
            wp_insert_post([
                'post_title' => 'Web Development',
                'post_content' => 'Custom WordPress themes and plugins.',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $services_page,
            ]);
            wp_insert_post([
                'post_title' => 'Consulting',
                'post_content' => 'Expert advice for your next project.',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $services_page,
            ]);
        }
    }

    // 4. Create Long-form Post
    if (!get_page_by_title('The Ultimate Guide to WordPress', OBJECT, 'post')) {
        $long_content = "This is a long article about the history of the web. " . str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ", 20);
        $education_cat = get_term_by('name', 'Education', 'category');
        wp_insert_post([
            'post_title' => 'The Ultimate Guide to WordPress',
            'post_content' => $long_content,
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => $education_cat ? [$education_cat->term_id] : [],
            'tags_input' => 'Guide, Tutorial',
        ]);
    }

    // 5. Create Short-form Post
    if (!get_page_by_title('Quick Tip: Hello World', OBJECT, 'post')) {
        $tech_cat = get_term_by('name', 'Technology', 'category');
        wp_insert_post([
            'post_title' => 'Quick Tip: Hello World',
            'post_content' => 'This is just a quick tip about saying hello to the world in code.',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => $tech_cat ? [$tech_cat->term_id] : [],
        ]);
    }

    // 6. Create Simple Comment
    $sample_posts = get_posts(['numberposts' => 1]);
    if (!empty($sample_posts)) {
        wp_insert_comment([
            'comment_post_ID' => $sample_posts[0]->ID,
            'comment_author' => 'Jane Doe',
            'comment_content' => 'This is such a helpful post! Thanks for sharing.',
            'comment_approved' => 1,
        ]);
    }

    update_option('sw_seeder_run_complete', true);
}

register_activation_hook(__FILE__, 'sw_run_site_seeder');

// Add a notice so user knows it worked
add_action('admin_notices', function () {
    if (get_option('sw_seeder_run_complete')) {
        echo '<div class="notice notice-success is-dismissible"><p>Site Seeding Complete! You can now deactivate and delete this plugin.</p></div>';
        delete_option('sw_seeder_run_complete');
    }
});
