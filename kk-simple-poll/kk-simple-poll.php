<?php
/**
 * Plugin Name: Simple Poll
 * Plugin URI: https://github.com/KevinKipkemei/WordpressPlugins
 * Description: A simple, AJAX-powered polling system using custom post types and the transients API.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 * Text Domain: kk-simple-poll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KK_SP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KK_SP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Activation Hook: Create custom table for votes
 */
function kk_sp_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kk_sp_votes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        poll_id mediumint(9) NOT NULL,
        option_index tinyint(4) NOT NULL,
        voter_ip varchar(45) NOT NULL,
        voted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY poll_id (poll_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'kk_sp_activate');

/**
 * Register Custom Post Type for Polls
 */
function kk_sp_register_post_type() {
    $labels = [
        'name'               => __('Polls', 'kk-simple-poll'),
        'singular_name'      => __('Poll', 'kk-simple-poll'),
        'add_new'            => __('Add New', 'kk-simple-poll'),
        'add_new_item'       => __('Add New Poll', 'kk-simple-poll'),
        'edit_item'          => __('Edit Poll', 'kk-simple-poll'),
        'new_item'           => __('New Poll', 'kk-simple-poll'),
        'view_item'          => __('View Poll', 'kk-simple-poll'),
        'search_items'       => __('Search Polls', 'kk-simple-poll'),
        'not_found'          => __('No polls found', 'kk-simple-poll'),
        'not_found_in_trash' => __('No polls found in trash', 'kk-simple-poll'),
    ];

    $args = [
        'labels'              => $labels,
        'public'              => false, // Not publicly queryable via URL
        'show_ui'             => true,  // Show in admin sidebar
        'show_in_menu'        => true,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-chart-bar',
        'capability_type'     => 'post',
        'supports'            => ['title'], // We only need the title for the question
        'has_archive'         => false,
        'rewrite'             => false,
    ];

    register_post_type('kk_poll', $args);
}
add_action('init', 'kk_sp_register_post_type');

/**
 * Register Meta Box for Poll Options
 */
function kk_sp_add_meta_boxes() {
    add_meta_box(
        'kk_sp_options_meta_box',           // ID
        __('Poll Options', 'kk-simple-poll'), // Title
        'kk_sp_render_options_meta_box',    // Callback
        'kk_poll',                          // Post type
        'normal',                           // Context
        'high'                              // Priority
    );
}
add_action('add_meta_boxes', 'kk_sp_add_meta_boxes');

/**
 * Render Meta Box Content
 */
function kk_sp_render_options_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('kk_sp_save_options', 'kk_sp_options_nonce');

    // Retrieve existing options
    $options = get_post_meta($post->ID, '_kk_sp_options', true);
    if (!is_array($options) || empty($options)) {
        // Default to two empty options
        $options = ['', ''];
    }

    echo '<div id="kk-sp-options-container">';
    foreach ($options as $index => $option) {
        echo '<div class="kk-sp-option-row" style="margin-bottom: 10px;">';
        echo '<input type="text" name="kk_sp_options[]" value="' . esc_attr($option) . '" class="regular-text" placeholder="' . esc_attr__('Enter option...', 'kk-simple-poll') . '" />';
        echo ' <button type="button" class="button kk-sp-remove-option">' . esc_html__('Remove', 'kk-simple-poll') . '</button>';
        echo '</div>';
    }
    echo '</div>';

    echo '<button type="button" class="button" id="kk-sp-add-option">' . esc_html__('Add Option', 'kk-simple-poll') . '</button>';
}

/**
 * Enqueue Admin Scripts and Styles for Meta Box
 */
function kk_sp_admin_scripts($hook) {
    global $post;
    
    // Only load on edit pages for our post type
    if (($hook === 'post-new.php' || $hook === 'post.php') && isset($post) && $post->post_type === 'kk_poll') {
        wp_enqueue_script(
            'kk-sp-admin-options',
            KK_SP_PLUGIN_URL . 'js/admin-options.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_enqueue_style(
            'kk-sp-admin-style',
            KK_SP_PLUGIN_URL . 'css/admin.css',
            [],
            '1.0.0'
        );
    }
}
add_action('admin_enqueue_scripts', 'kk_sp_admin_scripts');

/**
 * Save Post Meta (Poll Options)
 */
function kk_sp_save_post($post_id) {
    // 1. Check if our nonce is set.
    if (!isset($_POST['kk_sp_options_nonce'])) {
        return $post_id;
    }

    // 2. Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['kk_sp_options_nonce'], 'kk_sp_save_options')) {
        return $post_id;
    }

    // 3. If this is an autosave, our form has not been submitted.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // 4. Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // 5. Sanitize and save the data.
    if (isset($_POST['kk_sp_options']) && is_array($_POST['kk_sp_options'])) {
        $options = array_map('sanitize_text_field', wp_unslash($_POST['kk_sp_options']));
        
        // Remove empty options
        $options = array_filter($options, function($value) {
            return trim($value) !== '';
        });
        
        // Re-index array
        $options = array_values($options);

        update_post_meta($post_id, '_kk_sp_options', $options);
    } else {
        delete_post_meta($post_id, '_kk_sp_options');
    }
}
add_action('save_post', 'kk_sp_save_post');

/**
 * Phase 2: Settings API
 */

/**
 * Register Settings and Fields
 */
function kk_sp_register_settings() {
    // Register settings
    register_setting('kk_sp_settings_group', 'kk_sp_show_results_before_vote', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false,
    ]);
    register_setting('kk_sp_settings_group', 'kk_sp_results_style', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'bar',
    ]);
    register_setting('kk_sp_settings_group', 'kk_sp_prevention_method', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'cookie',
    ]);

    // Add Section
    add_settings_section(
        'kk_sp_general_section',
        __('General Settings', 'kk-simple-poll'),
        '__return_false', // No description needed
        'kk-sp-settings'
    );

    // Add Fields
    add_settings_field(
        'kk_sp_show_results_before_vote',
        __('Show results before voting?', 'kk-simple-poll'),
        'kk_sp_render_show_results_field',
        'kk-sp-settings',
        'kk_sp_general_section'
    );

    add_settings_field(
        'kk_sp_results_style',
        __('Results display style', 'kk-simple-poll'),
        'kk_sp_render_results_style_field',
        'kk-sp-settings',
        'kk_sp_general_section'
    );

    add_settings_field(
        'kk_sp_prevention_method',
        __('Vote prevention method', 'kk-simple-poll'),
        'kk_sp_render_prevention_method_field',
        'kk-sp-settings',
        'kk_sp_general_section'
    );
}
add_action('admin_init', 'kk_sp_register_settings');

/**
 * Add Options Page to Menu
 */
function kk_sp_add_options_page() {
    add_submenu_page(
        'edit.php?post_type=kk_poll', // Parent slug
        __('Poll Settings', 'kk-simple-poll'), // Page title
        __('Settings', 'kk-simple-poll'), // Menu title
        'manage_options', // Capability
        'kk-sp-settings', // Menu slug
        'kk_sp_render_options_page' // Callback
    );
}
add_action('admin_menu', 'kk_sp_add_options_page');

/**
 * Render Options Page
 */
function kk_sp_render_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('kk_sp_settings_group');
            do_settings_sections('kk-sp-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Render Fields
 */
function kk_sp_render_show_results_field() {
    $val = get_option('kk_sp_show_results_before_vote', false);
    ?>
    <label>
        <input type="checkbox" name="kk_sp_show_results_before_vote" value="1" <?php checked(1, $val, true); ?> />
        <?php esc_html_e('Allow users to see the current results without voting first.', 'kk-simple-poll'); ?>
    </label>
    <?php
}

function kk_sp_render_results_style_field() {
    $val = get_option('kk_sp_results_style', 'bar');
    ?>
    <select name="kk_sp_results_style">
        <option value="bar" <?php selected($val, 'bar'); ?>><?php esc_html_e('Bar Chart', 'kk-simple-poll'); ?></option>
        <option value="percentage" <?php selected($val, 'percentage'); ?>><?php esc_html_e('Percentage Text Only', 'kk-simple-poll'); ?></option>
    </select>
    <?php
}

function kk_sp_render_prevention_method_field() {
    $val = get_option('kk_sp_prevention_method', 'cookie');
    ?>
    <select name="kk_sp_prevention_method">
        <option value="cookie" <?php selected($val, 'cookie'); ?>><?php esc_html_e('Cookie Based', 'kk-simple-poll'); ?></option>
        <option value="ip" <?php selected($val, 'ip'); ?>><?php esc_html_e('IP Address Based', 'kk-simple-poll'); ?></option>
    </select>
    <?php
}

/**
 * Phase 3: Frontend Shortcode
 */

/**
 * Enqueue Frontend Scripts and Styles
 */
function kk_sp_enqueue_frontend_assets() {
    wp_register_style('kk-sp-frontend-style', KK_SP_PLUGIN_URL . 'css/frontend.css', [], '1.0.0');
    
    // Register and localize JS
    wp_register_script('kk-sp-frontend-script', KK_SP_PLUGIN_URL . 'js/frontend.js', [], '1.0.0', true);
    wp_localize_script('kk-sp-frontend-script', 'kkSpData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('kk_sp_vote'),
    ]);
}
add_action('wp_enqueue_scripts', 'kk_sp_enqueue_frontend_assets');

/**
 * Shortcode for rendering a poll: [kk_poll id="123"]
 */
function kk_sp_poll_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => 0
    ], $atts, 'kk_poll');

    $poll_id = absint($atts['id']);
    if (!$poll_id) {
        return '<p>' . esc_html__('Invalid poll ID.', 'kk-simple-poll') . '</p>';
    }

    $poll = get_post($poll_id);
    if (!$poll || $poll->post_type !== 'kk_poll' || $poll->post_status !== 'publish') {
        return '<p>' . esc_html__('Poll not found or not published.', 'kk-simple-poll') . '</p>';
    }

    $options = get_post_meta($poll_id, '_kk_sp_options', true);
    if (!is_array($options) || empty($options)) {
        return '<p>' . esc_html__('This poll has no options.', 'kk-simple-poll') . '</p>';
    }

    // We only enqueue styles and scripts when the shortcode is actually used on the page
    wp_enqueue_style('kk-sp-frontend-style');
    wp_enqueue_script('kk-sp-frontend-script');

    // Display mode
    $results_style = get_option('kk_sp_results_style', 'bar');
    $prevention_method = get_option('kk_sp_prevention_method', 'cookie');
    
    $has_voted = false;
    if ($prevention_method === 'cookie' && isset($_COOKIE['kk_sp_voted_' . $poll_id])) {
        $has_voted = true;
    } else if ($prevention_method === 'ip') {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kk_sp_votes WHERE poll_id = %d AND voter_ip = %s",
            $poll_id,
            $_SERVER['REMOTE_ADDR']
        ));
        if ($existing > 0) {
            $has_voted = true;
        }
    }

    ob_start();
    ?>
    <div class="kk-sp-container" id="kk-sp-poll-<?php echo esc_attr($poll_id); ?>" data-poll-id="<?php echo esc_attr($poll_id); ?>">
        <h3 class="kk-sp-title"><?php echo esc_html($poll->post_title); ?></h3>
        
        <?php if ($has_voted) : 
            // User already voted, render results
            $counts = kk_sp_get_vote_counts($poll_id);
            $total_votes = array_sum(array_column($counts, 'total'));
            ?>
            <div class="kk-sp-message success" style="display:block;">
                <?php esc_html_e('You have already voted on this poll.', 'kk-simple-poll'); ?>
            </div>
            
            <div class="kk-sp-results-container" style="display: block;">
                <?php foreach ($options as $index => $text) : 
                    // Find count
                    $count = 0;
                    foreach ($counts as $row) {
                        if ((int)$row['option_index'] === $index) {
                            $count = (int)$row['total'];
                            break;
                        }
                    }
                    $pct = $total_votes > 0 ? round(($count / $total_votes) * 100) : 0;
                ?>
                    <div class="kk-sp-result-row">
                        <div class="kk-sp-result-label"><?php echo esc_html($text . ' (' . $count . ' votes)'); ?></div>
                        <?php if ($results_style === 'bar') : ?>
                            <div class="kk-sp-bar-bg">
                                <div class="kk-sp-bar" style="width: <?php echo esc_attr($pct); ?>%;">
                                    <span class="kk-sp-pct"><?php echo esc_html($pct); ?>%</span>
                                </div>
                            </div>
                        <?php else : ?>
                            <div><?php echo esc_html($pct); ?>% of total votes</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            
            <form class="kk-sp-form" action="" method="post">
                <div class="kk-sp-options">
                    <?php foreach ($options as $index => $option) : ?>
                        <label class="kk-sp-option">
                            <input type="radio" name="kk_sp_option_index" value="<?php echo esc_attr($index); ?>" required />
                            <?php echo esc_html($option); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="kk-sp-submit-btn"><?php esc_html_e('Vote', 'kk-simple-poll'); ?></button>
                <div class="kk-sp-message"></div>
            </form>

            <div class="kk-sp-results-container" style="display: none;">
                <!-- Results will be injected here via JS after vote -->
            </div>

        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('kk_poll', 'kk_sp_poll_shortcode');


/**
 * Phase 4 & 5: AJAX Voting + Transients API + Double-Vote Prevention
 */

add_action('wp_ajax_kk_sp_submit_vote', 'kk_sp_handle_vote');
add_action('wp_ajax_nopriv_kk_sp_submit_vote', 'kk_sp_handle_vote');

function kk_sp_handle_vote() {
    global $wpdb;

    // 1. Verify Nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kk_sp_vote')) {
        wp_send_json_error(['message' => __('Security check failed.', 'kk-simple-poll')]);
    }

    // 2. Sanitize Inputs
    $poll_id = isset($_POST['poll_id']) ? absint($_POST['poll_id']) : 0;
    $option_index = isset($_POST['option_index']) ? absint($_POST['option_index']) : -1;

    if (!$poll_id || $option_index < 0) {
        wp_send_json_error(['message' => __('Invalid data.', 'kk-simple-poll')]);
    }

    // 3. Double-Vote Prevention (Phase 5)
    $prevention_method = get_option('kk_sp_prevention_method', 'cookie');
    
    if ($prevention_method === 'cookie') {
        if (isset($_COOKIE['kk_sp_voted_' . $poll_id])) {
            wp_send_json_error(['message' => __('You have already voted on this poll.', 'kk-simple-poll')]);
        }
    } else if ($prevention_method === 'ip') {
        $voter_ip = $_SERVER['REMOTE_ADDR'];
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kk_sp_votes WHERE poll_id = %d AND voter_ip = %s",
            $poll_id,
            $voter_ip
        ));
        if ($existing > 0) {
            wp_send_json_error(['message' => __('You have already voted on this poll from this IP address.', 'kk-simple-poll')]);
        }
    }

    // 4. Insert Vote
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'kk_sp_votes',
        [
            'poll_id' => $poll_id,
            'option_index' => $option_index,
            'voter_ip' => $_SERVER['REMOTE_ADDR']
        ],
        ['%d', '%d', '%s']
    );

    if (!$inserted) {
        wp_send_json_error(['message' => __('Database error.', 'kk-simple-poll')]);
    }

    // 5. Set Cookie if needed
    if ($prevention_method === 'cookie') {
        // Set cookie for 30 days
        setcookie('kk_sp_voted_' . $poll_id, '1', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
    }

    // 6. Delete Transient Cache (Phase 5)
    delete_transient('kk_sp_votes_' . $poll_id);

    // 7. Return Updated Results
    $counts = kk_sp_get_vote_counts($poll_id);
    
    // Also fetch options to get total
    $options = get_post_meta($poll_id, '_kk_sp_options', true);
    $total_votes = array_sum(array_column($counts, 'total'));
    
    // Format response data
    $results = [];
    foreach ($options as $index => $text) {
        // Find count for this index
        $count = 0;
        foreach ($counts as $row) {
            if ((int)$row['option_index'] === $index) {
                $count = (int)$row['total'];
                break;
            }
        }
        $results[] = [
            'index' => $index,
            'text' => $text,
            'total' => $count
        ];
    }

    wp_send_json_success([
        'message' => __('Vote recorded! Thank you.', 'kk-simple-poll'),
        'total_votes' => $total_votes,
        'results' => $results,
        'style' => get_option('kk_sp_results_style', 'bar')
    ]);
}

/**
 * Get vote counts (using Transients API)
 */
function kk_sp_get_vote_counts($poll_id) {
    global $wpdb;
    $cache_key = 'kk_sp_votes_' . $poll_id;

    $counts = get_transient($cache_key);
    if ($counts !== false) {
        return $counts; // Cache hit
    }

    // Cache miss - query DB
    $counts = $wpdb->get_results($wpdb->prepare(
        "SELECT option_index, COUNT(*) as total FROM {$wpdb->prefix}kk_sp_votes WHERE poll_id = %d GROUP BY option_index",
        $poll_id
    ), ARRAY_A);

    if (!is_array($counts)) {
        $counts = [];
    }

    // Store in cache for 5 minutes
    set_transient($cache_key, $counts, 5 * MINUTE_IN_SECONDS);
    
    return $counts;
}
