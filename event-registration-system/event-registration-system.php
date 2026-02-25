<?php
/**
 * Plugin Name: Event Registration System
 * Description: Allows admins to create events and users to register via frontend form. Includes email notifications and CSV export.
 * Version: 1.0.0
 * Author: Kevin Kipkemei
 * Text Domain: kk-event-registration
 */

if (!defined('ABSPATH')) {
    exit;
}

global $kk_ers_db_version;
$kk_ers_db_version = '1.0';

/**
 * Load text domain for translations
 */
function kk_ers_load_textdomain() {
    load_plugin_textdomain('kk-event-registration', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'kk_ers_load_textdomain');

/**
 * Database Setup (On Activation)
 */
function kk_ers_install_tables() {
    global $wpdb, $kk_ers_db_version;

    $charset_collate = $wpdb->get_charset_collate();

    // Table 1: Events
    $events_table = $wpdb->prefix . 'kk_ers_events';
    $sql_events = "CREATE TABLE $events_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        event_date datetime NOT NULL,
        location varchar(255) NOT NULL,
        max_attendees mediumint(9) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Table 2: Registrations
    $registrations_table = $wpdb->prefix . 'kk_ers_registrations';
    $sql_registrations = "CREATE TABLE $registrations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        attendee_name varchar(255) NOT NULL,
        attendee_email varchar(255) NOT NULL,
        registered_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_id (event_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_events);
    dbDelta($sql_registrations);

    // Save the DB version
    add_option('kk_ers_db_version', $kk_ers_db_version);
}

// Register the activation hook
register_activation_hook(__FILE__, 'kk_ers_install_tables');

/**
 * Check for DB updates
 */
function kk_ers_update_db_check() {
    global $kk_ers_db_version;
    if (get_site_option('kk_ers_db_version') != $kk_ers_db_version) {
        kk_ers_install_tables();
    }
}
add_action('plugins_loaded', 'kk_ers_update_db_check');

/**
 * 2. Admin Menu & UI
 */
function kk_ers_add_admin_menu() {
    // Top-level menu page
    add_menu_page(
        __('Event Registration', 'kk-event-registration'), // Page title
        __('Events', 'kk-event-registration'),              // Menu title
        'manage_options',                                   // Capability
        'kk-ers-events',                                    // Menu slug
        'kk_ers_render_events_page',                        // Callback
        'dashicons-calendar-alt',                           // Icon
        25                                                  // Position
    );

    // Submenu: All Events (same as parent)
    add_submenu_page(
        'kk-ers-events',
        __('All Events', 'kk-event-registration'),
        __('All Events', 'kk-event-registration'),
        'manage_options',
        'kk-ers-events',
        'kk_ers_render_events_page'
    );

    // Submenu: Add New Event
    add_submenu_page(
        'kk-ers-events',
        __('Add New Event', 'kk-event-registration'),
        __('Add New Event', 'kk-event-registration'),
        'manage_options',
        'kk-ers-add-event',
        'kk_ers_render_add_event_page'
    );
}
add_action('admin_menu', 'kk_ers_add_admin_menu');

/**
 * Enqueue admin styles (only on our pages)
 */
function kk_ers_enqueue_admin_styles($hook) {
    $our_pages = [
        'toplevel_page_kk-ers-events',
        'events_page_kk-ers-add-event',
    ];
    if (!in_array($hook, $our_pages)) {
        return;
    }
    wp_enqueue_style(
        'kk-ers-admin-style',
        plugin_dir_url(__FILE__) . 'css/admin.css',
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'kk_ers_enqueue_admin_styles');

/**
 * Handle event form submission (add & edit)
 */
function kk_ers_handle_event_submission() {
    // Only process if our form was submitted
    if (!isset($_POST['kk_ers_submit_event'])) {
        return;
    }

    // 1. Verify nonce
    if (!isset($_POST['kk_ers_event_nonce']) || !wp_verify_nonce($_POST['kk_ers_event_nonce'], 'kk_ers_save_event')) {
        wp_die(esc_html__('Security check failed.', 'kk-event-registration'));
    }

    // 2. Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to do this.', 'kk-event-registration'));
    }

    // 3. Sanitize inputs
    $name          = sanitize_text_field($_POST['kk_ers_name']);
    $description   = sanitize_textarea_field($_POST['kk_ers_description']);
    $event_date    = sanitize_text_field($_POST['kk_ers_event_date']);
    $location      = sanitize_text_field($_POST['kk_ers_location']);
    $max_attendees = absint($_POST['kk_ers_max_attendees']);
    $event_id      = isset($_POST['kk_ers_event_id']) ? absint($_POST['kk_ers_event_id']) : 0;

    // 4. Validate
    if (empty($name) || empty($event_date) || empty($location) || $max_attendees < 1) {
        // Redirect back with error
        wp_redirect(add_query_arg('kk_ers_error', '1', wp_get_referer()));
        exit;
    }

    global $wpdb;
    $events_table = $wpdb->prefix . 'kk_ers_events';

    $data = [
        'name'          => $name,
        'description'   => $description,
        'event_date'    => $event_date,
        'location'      => $location,
        'max_attendees' => $max_attendees,
    ];

    if ($event_id > 0) {
        // 5a. UPDATE existing event
        $wpdb->update($events_table, $data, ['id' => $event_id]);
        $redirect_url = add_query_arg(['page' => 'kk-ers-events', 'kk_ers_updated' => '1'], admin_url('admin.php'));
    } else {
        // 5b. INSERT new event
        $wpdb->insert($events_table, $data);
        $redirect_url = add_query_arg(['page' => 'kk-ers-events', 'kk_ers_added' => '1'], admin_url('admin.php'));
    }

    wp_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'kk_ers_handle_event_submission');

/**
 * Handle event deletion
 */
function kk_ers_handle_event_deletion() {
    if (!isset($_GET['kk_ers_action']) || $_GET['kk_ers_action'] !== 'delete') {
        return;
    }
    if (!isset($_GET['kk_ers_event_id']) || !isset($_GET['kk_ers_delete_nonce'])) {
        return;
    }

    $event_id = absint($_GET['kk_ers_event_id']);

    // Verify nonce
    if (!wp_verify_nonce($_GET['kk_ers_delete_nonce'], 'kk_ers_delete_event_' . $event_id)) {
        wp_die(esc_html__('Security check failed.', 'kk-event-registration'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to do this.', 'kk-event-registration'));
    }

    global $wpdb;
    // Delete registrations for this event first
    $wpdb->delete($wpdb->prefix . 'kk_ers_registrations', ['event_id' => $event_id]);
    // Then delete the event
    $wpdb->delete($wpdb->prefix . 'kk_ers_events', ['id' => $event_id]);

    wp_redirect(add_query_arg(['page' => 'kk-ers-events', 'kk_ers_deleted' => '1'], admin_url('admin.php')));
    exit;
}
add_action('admin_init', 'kk_ers_handle_event_deletion');

/**
 * Render: All Events page
 */
function kk_ers_render_events_page() {
    global $wpdb;
    $events_table        = $wpdb->prefix . 'kk_ers_events';
    $registrations_table = $wpdb->prefix . 'kk_ers_registrations';

    // Fetch all events with registration count
    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.*, COUNT(r.id) AS registration_count
             FROM %i e
             LEFT JOIN %i r ON e.id = r.event_id
             GROUP BY e.id
             ORDER BY e.event_date ASC",
            $events_table,
            $registrations_table
        )
    );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Events', 'kk-event-registration'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=kk-ers-add-event')); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'kk-event-registration'); ?>
        </a>
        <hr class="wp-header-end">

        <?php if (isset($_GET['kk_ers_added'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Event added successfully!', 'kk-event-registration'); ?></p></div>
        <?php elseif (isset($_GET['kk_ers_updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Event updated successfully!', 'kk-event-registration'); ?></p></div>
        <?php elseif (isset($_GET['kk_ers_deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Event deleted successfully!', 'kk-event-registration'); ?></p></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Event Name', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Date', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Location', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Registrations', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Actions', 'kk-event-registration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($events) : ?>
                    <?php foreach ($events as $event) :
                        $is_full     = $event->registration_count >= $event->max_attendees;
                        $edit_url    = add_query_arg(['page' => 'kk-ers-add-event', 'kk_ers_event_id' => $event->id], admin_url('admin.php'));
                        $delete_url  = add_query_arg([
                            'page'              => 'kk-ers-events',
                            'kk_ers_action'     => 'delete',
                            'kk_ers_event_id'   => $event->id,
                            'kk_ers_delete_nonce' => wp_create_nonce('kk_ers_delete_event_' . $event->id),
                        ], admin_url('admin.php'));
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($event->name); ?></strong></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->event_date))); ?></td>
                            <td><?php echo esc_html($event->location); ?></td>
                            <td>
                                <?php echo esc_html($event->registration_count); ?> / <?php echo esc_html($event->max_attendees); ?>
                                <?php if ($is_full) : ?>
                                    <span class="kk-ers-badge-full"><?php esc_html_e('Full', 'kk-event-registration'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'kk-event-registration'); ?></a>
                                &nbsp;|&nbsp;
                                <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this event and all its registrations?', 'kk-event-registration'); ?>')" style="color:red;">
                                    <?php esc_html_e('Delete', 'kk-event-registration'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No events found. Add your first event!', 'kk-event-registration'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Render: Add / Edit Event page
 */
function kk_ers_render_add_event_page() {
    global $wpdb;

    // Check if we're editing an existing event
    $event_id = isset($_GET['kk_ers_event_id']) ? absint($_GET['kk_ers_event_id']) : 0;
    $event    = null;

    if ($event_id > 0) {
        $event = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", $wpdb->prefix . 'kk_ers_events', $event_id)
        );
    }

    $page_title = $event ? __('Edit Event', 'kk-event-registration') : __('Add New Event', 'kk-event-registration');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($page_title); ?></h1>

        <?php if (isset($_GET['kk_ers_error'])) : ?>
            <div class="notice notice-error"><p><?php esc_html_e('Please fill in all required fields.', 'kk-event-registration'); ?></p></div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('kk_ers_save_event', 'kk_ers_event_nonce'); ?>

            <?php if ($event) : ?>
                <input type="hidden" name="kk_ers_event_id" value="<?php echo esc_attr($event->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="kk_ers_name"><?php esc_html_e('Event Name', 'kk-event-registration'); ?> <span style="color:red;">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="kk_ers_name" id="kk_ers_name" class="regular-text" required
                               value="<?php echo esc_attr($event->name ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="kk_ers_description"><?php esc_html_e('Description', 'kk-event-registration'); ?></label>
                    </th>
                    <td>
                        <textarea name="kk_ers_description" id="kk_ers_description" class="large-text" rows="4"><?php echo esc_textarea($event->description ?? ''); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="kk_ers_event_date"><?php esc_html_e('Event Date & Time', 'kk-event-registration'); ?> <span style="color:red;">*</span></label>
                    </th>
                    <td>
                        <input type="datetime-local" name="kk_ers_event_date" id="kk_ers_event_date" required
                               value="<?php echo esc_attr($event ? date('Y-m-d\TH:i', strtotime($event->event_date)) : ''); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="kk_ers_location"><?php esc_html_e('Location', 'kk-event-registration'); ?> <span style="color:red;">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="kk_ers_location" id="kk_ers_location" class="regular-text" required
                               value="<?php echo esc_attr($event->location ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="kk_ers_max_attendees"><?php esc_html_e('Max Attendees', 'kk-event-registration'); ?> <span style="color:red;">*</span></label>
                    </th>
                    <td>
                        <input type="number" name="kk_ers_max_attendees" id="kk_ers_max_attendees" class="small-text" min="1" required
                               value="<?php echo esc_attr($event->max_attendees ?? ''); ?>">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="kk_ers_submit_event" class="button button-primary"
                       value="<?php echo $event ? esc_attr__('Update Event', 'kk-event-registration') : esc_attr__('Add Event', 'kk-event-registration'); ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=kk-ers-events')); ?>" class="button">
                    <?php esc_html_e('Cancel', 'kk-event-registration'); ?>
                </a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * 3. Admin - Registration Management
 */

/**
 * Register the Registrations submenu (hooked into existing admin_menu)
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'kk-ers-events',
        __('Registrations', 'kk-event-registration'),
        __('Registrations', 'kk-event-registration'),
        'manage_options',
        'kk-ers-registrations',
        'kk_ers_render_registrations_page'
    );
});

/**
 * Handle CSV export (runs before any output)
 */
function kk_ers_handle_csv_export() {
    if (!isset($_GET['kk_ers_action']) || $_GET['kk_ers_action'] !== 'export_csv') {
        return;
    }
    if (!isset($_GET['kk_ers_csv_nonce']) || !wp_verify_nonce($_GET['kk_ers_csv_nonce'], 'kk_ers_export_csv')) {
        wp_die(esc_html__('Security check failed.', 'kk-event-registration'));
    }
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to do this.', 'kk-event-registration'));
    }

    global $wpdb;
    $events_table        = $wpdb->prefix . 'kk_ers_events';
    $registrations_table = $wpdb->prefix . 'kk_ers_registrations';

    $event_id = isset($_GET['kk_ers_event_id']) ? absint($_GET['kk_ers_event_id']) : 0;

    // Build query — filter by event if specified
    if ($event_id > 0) {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.attendee_name, r.attendee_email, r.registered_at, e.name AS event_name
                 FROM %i r
                 INNER JOIN %i e ON r.event_id = e.id
                 WHERE r.event_id = %d
                 ORDER BY r.registered_at ASC",
                $registrations_table,
                $events_table,
                $event_id
            ),
            ARRAY_A
        );
        $filename = 'registrations-event-' . $event_id . '.csv';
    } else {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.attendee_name, r.attendee_email, r.registered_at, e.name AS event_name
                 FROM %i r
                 INNER JOIN %i e ON r.event_id = e.id
                 ORDER BY e.event_date ASC, r.registered_at ASC",
                $registrations_table,
                $events_table
            ),
            ARRAY_A
        );
        $filename = 'all-registrations.csv';
    }

    // Send CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write column headers
    fputcsv($output, ['Event Name', 'Attendee Name', 'Attendee Email', 'Registered At']);

    // Write data rows
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['event_name'],
            $row['attendee_name'],
            $row['attendee_email'],
            $row['registered_at'],
        ]);
    }

    fclose($output);
    exit;
}
add_action('admin_init', 'kk_ers_handle_csv_export');

/**
 * Render: Registrations page
 */
function kk_ers_render_registrations_page() {
    global $wpdb;
    $events_table        = $wpdb->prefix . 'kk_ers_events';
    $registrations_table = $wpdb->prefix . 'kk_ers_registrations';

    // Get all events for the filter dropdown
    $events = $wpdb->get_results(
        $wpdb->prepare("SELECT id, name FROM %i ORDER BY event_date ASC", $events_table)
    );

    // Currently selected event filter
    $selected_event_id = isset($_GET['kk_ers_event_id']) ? absint($_GET['kk_ers_event_id']) : 0;

    // Fetch registrations — with optional event filter
    if ($selected_event_id > 0) {
        $registrations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, e.name AS event_name, e.max_attendees
                 FROM %i r
                 INNER JOIN %i e ON r.event_id = e.id
                 WHERE r.event_id = %d
                 ORDER BY r.registered_at DESC",
                $registrations_table,
                $events_table,
                $selected_event_id
            )
        );
    } else {
        $registrations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, e.name AS event_name, e.max_attendees
                 FROM %i r
                 INNER JOIN %i e ON r.event_id = e.id
                 ORDER BY e.event_date ASC, r.registered_at DESC",
                $registrations_table,
                $events_table
            )
        );
    }

    // Count registrations per event (for status display)
    $counts = [];
    foreach ($registrations as $reg) {
        $counts[$reg->event_id] = ($counts[$reg->event_id] ?? 0) + 1;
    }

    // Build CSV export URL
    $csv_args = [
        'page'             => 'kk-ers-registrations',
        'kk_ers_action'    => 'export_csv',
        'kk_ers_csv_nonce' => wp_create_nonce('kk_ers_export_csv'),
    ];
    if ($selected_event_id > 0) {
        $csv_args['kk_ers_event_id'] = $selected_event_id;
    }
    $csv_url = add_query_arg($csv_args, admin_url('admin.php'));
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Registrations', 'kk-event-registration'); ?></h1>
        <a href="<?php echo esc_url($csv_url); ?>" class="page-title-action kk-ers-export-btn">
            ⬇ <?php esc_html_e('Export CSV', 'kk-event-registration'); ?>
        </a>
        <hr class="wp-header-end">

        <!-- Event Filter -->
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="kk-ers-filter-form">
            <input type="hidden" name="page" value="kk-ers-registrations">
            <select name="kk_ers_event_id" onchange="this.form.submit()">
                <option value="0"><?php esc_html_e('— All Events —', 'kk-event-registration'); ?></option>
                <?php foreach ($events as $event) : ?>
                    <option value="<?php echo esc_attr($event->id); ?>"
                        <?php selected($selected_event_id, $event->id); ?>>
                        <?php echo esc_html($event->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Registrations Table -->
        <table class="wp-list-table widefat fixed striped kk-ers-registrations-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Event', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Attendee Name', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Email', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Registered At', 'kk-event-registration'); ?></th>
                    <th><?php esc_html_e('Event Status', 'kk-event-registration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registrations) : ?>
                    <?php foreach ($registrations as $reg) :
                        $count   = $counts[$reg->event_id] ?? 0;
                        $is_full = $count >= $reg->max_attendees;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($reg->event_name); ?></strong></td>
                            <td><?php echo esc_html($reg->attendee_name); ?></td>
                            <td><?php echo esc_html($reg->attendee_email); ?></td>
                            <td><?php echo esc_html(
                                date_i18n(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($reg->registered_at)
                                )
                            ); ?></td>
                            <td>
                                <?php echo esc_html($count . ' / ' . $reg->max_attendees); ?>
                                <?php if ($is_full) : ?>
                                    <span class="kk-ers-badge-full"><?php esc_html_e('Full', 'kk-event-registration'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No registrations found.', 'kk-event-registration'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p class="kk-ers-total">
            <?php
            printf(
                esc_html__('Total registrations shown: %d', 'kk-event-registration'),
                count($registrations)
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * 4. Frontend Registration Form (Shortcode)
 */

/**
 * Enqueue frontend styles
 */
function kk_ers_enqueue_frontend_styles() {
    wp_enqueue_style(
        'kk-ers-frontend-style',
        plugin_dir_url(__FILE__) . 'css/frontend.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'kk_ers_enqueue_frontend_styles');

/**
 * Helper: get available upcoming events
 */
function kk_ers_get_upcoming_events() {
    global $wpdb;
    $events_table = $wpdb->prefix . 'kk_ers_events';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE event_date > %s ORDER BY event_date ASC",
            $events_table,
            current_time('mysql')
        )
    );
}

/**
 * Helper: count registrations for a given event
 */
function kk_ers_get_registration_count($event_id) {
    global $wpdb;
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE event_id = %d",
            $wpdb->prefix . 'kk_ers_registrations',
            $event_id
        )
    );
}

/**
 * Shortcode: [event_registration]
 */
function kk_ers_registration_shortcode() {
    global $wpdb;

    $events_table        = $wpdb->prefix . 'kk_ers_events';
    $registrations_table = $wpdb->prefix . 'kk_ers_registrations';

    $errors   = [];
    $success  = false;

    // Sticky form values (preserved on errors)
    $form = [
        'event_id' => 0,
        'name'     => '',
        'email'    => '',
    ];

    // ── Process form submission ──────────────────────────────────────────────
    if (isset($_POST['kk_ers_register_submit'])) {

        // 1. Verify nonce
        if (!isset($_POST['kk_ers_register_nonce']) ||
            !wp_verify_nonce($_POST['kk_ers_register_nonce'], 'kk_ers_register')) {
            $errors[] = __('Security check failed. Please refresh the page and try again.', 'kk-event-registration');
        } else {

            // 2. Sanitize
            $form['event_id'] = absint($_POST['kk_ers_event_id'] ?? 0);
            $form['name']     = sanitize_text_field($_POST['kk_ers_name'] ?? '');
            $form['email']    = sanitize_email($_POST['kk_ers_email'] ?? '');

            // 3. Validate required fields
            if ($form['event_id'] < 1) {
                $errors[] = __('Please select an event.', 'kk-event-registration');
            }
            if (empty($form['name'])) {
                $errors[] = __('Please enter your name.', 'kk-event-registration');
            }
            if (empty($form['email']) || !is_email($form['email'])) {
                $errors[] = __('Please enter a valid email address.', 'kk-event-registration');
            }

            if (empty($errors)) {

                // 4. Verify the event exists
                $event = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM %i WHERE id = %d",
                        $events_table,
                        $form['event_id']
                    )
                );

                if (!$event) {
                    $errors[] = __('The selected event no longer exists.', 'kk-event-registration');
                } else {

                    // 5. Check seat availability
                    $reg_count = kk_ers_get_registration_count($event->id);
                    if ($reg_count >= $event->max_attendees) {
                        $errors[] = sprintf(
                            __('Sorry, "%s" is fully booked.', 'kk-event-registration'),
                            esc_html($event->name)
                        );
                    }

                    // 6. Check for duplicate email registration
                    if (empty($errors)) {
                        $existing = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM %i WHERE event_id = %d AND attendee_email = %s",
                                $registrations_table,
                                $event->id,
                                $form['email']
                            )
                        );

                        if ($existing > 0) {
                            $errors[] = sprintf(
                                __('You have already registered for "%s" with this email address.', 'kk-event-registration'),
                                esc_html($event->name)
                            );
                        }
                    }

                    // 7. All clear — insert registration
                    if (empty($errors)) {
                        $inserted = $wpdb->insert(
                            $registrations_table,
                            [
                                'event_id'       => $event->id,
                                'attendee_name'  => $form['name'],
                                'attendee_email' => $form['email'],
                            ],
                            ['%d', '%s', '%s']
                        );

                        if ($inserted) {
                            $success = true;
                            // Send emails
                            kk_ers_send_attendee_email($form['name'], $form['email'], $event);
                            kk_ers_send_admin_email($form['name'], $form['email'], $event);
                            $form    = ['event_id' => 0, 'name' => '', 'email' => '']; // clear form
                        } else {
                            $errors[] = __('There was a problem saving your registration. Please try again.', 'kk-event-registration');
                        }
                    }
                }
            }
        }
    }

    // ── Render ───────────────────────────────────────────────────────────────
    $upcoming_events = kk_ers_get_upcoming_events();

    ob_start(); // Use output buffering — shortcodes must RETURN, not echo
    ?>
    <div class="kk-ers-form-wrap">

        <?php if ($success) : ?>
            <div class="kk-ers-notice kk-ers-success">
                <strong><?php esc_html_e('Registration Confirmed!', 'kk-event-registration'); ?></strong>
                <p><?php esc_html_e('You have been successfully registered. Check your email for a confirmation.', 'kk-event-registration'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)) : ?>
            <div class="kk-ers-notice kk-ers-error">
                <strong><?php esc_html_e('Please fix the following errors:', 'kk-event-registration'); ?></strong>
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($upcoming_events)) : ?>
            <p class="kk-ers-no-events">
                <?php esc_html_e('There are no upcoming events at the moment. Please check back soon!', 'kk-event-registration'); ?>
            </p>
        <?php else : ?>

            <form class="kk-ers-form" method="post" action="">
                <?php wp_nonce_field('kk_ers_register', 'kk_ers_register_nonce'); ?>

                <!-- Event Selection -->
                <div class="kk-ers-field">
                    <label for="kk_ers_event_id">
                        <?php esc_html_e('Select Event', 'kk-event-registration'); ?>
                        <span class="kk-ers-required">*</span>
                    </label>
                    <select name="kk_ers_event_id" id="kk_ers_event_id" required>
                        <option value="0"><?php esc_html_e('— Choose an Event —', 'kk-event-registration'); ?></option>
                        <?php foreach ($upcoming_events as $event) :
                            $reg_count = kk_ers_get_registration_count($event->id);
                            $is_full   = $reg_count >= $event->max_attendees;
                            $seats_left = $event->max_attendees - $reg_count;
                        ?>
                            <option value="<?php echo esc_attr($event->id); ?>"
                                <?php selected($form['event_id'], $event->id); ?>
                                <?php disabled($is_full, true); ?>>
                                <?php echo esc_html($event->name); ?>
                                — <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event->event_date))); ?>
                                <?php if ($is_full) : ?>
                                    (<?php esc_html_e('Full', 'kk-event-registration'); ?>)
                                <?php else : ?>
                                    (<?php printf(esc_html__('%d seats left', 'kk-event-registration'), $seats_left); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Name -->
                <div class="kk-ers-field">
                    <label for="kk_ers_name">
                        <?php esc_html_e('Your Name', 'kk-event-registration'); ?>
                        <span class="kk-ers-required">*</span>
                    </label>
                    <input type="text"
                           name="kk_ers_name"
                           id="kk_ers_name"
                           value="<?php echo esc_attr($form['name']); ?>"
                           placeholder="<?php esc_attr_e('John Doe', 'kk-event-registration'); ?>"
                           required>
                </div>

                <!-- Email -->
                <div class="kk-ers-field">
                    <label for="kk_ers_email">
                        <?php esc_html_e('Email Address', 'kk-event-registration'); ?>
                        <span class="kk-ers-required">*</span>
                    </label>
                    <input type="email"
                           name="kk_ers_email"
                           id="kk_ers_email"
                           value="<?php echo esc_attr($form['email']); ?>"
                           placeholder="<?php esc_attr_e('you@example.com', 'kk-event-registration'); ?>"
                           required>
                </div>

                <div class="kk-ers-field">
                    <button type="submit" name="kk_ers_register_submit" class="kk-ers-submit-btn">
                        <?php esc_html_e('Register Now', 'kk-event-registration'); ?>
                    </button>
                </div>

            </form>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered output
}
add_shortcode('event_registration', 'kk_ers_registration_shortcode');

/**
 * 5. Email Notifications
 */

/**
 * Send confirmation email to the attendee
 */
function kk_ers_send_attendee_email($name, $email, $event) {
    $site_name  = get_bloginfo('name');
    $event_date = date_i18n(
        get_option('date_format') . ' ' . get_option('time_format'),
        strtotime($event->event_date)
    );

    $subject = sprintf(
        /* translators: %s = event name */
        __('[%s] Registration Confirmed: %s', 'kk-event-registration'),
        $site_name,
        $event->name
    );

    $message = kk_ers_attendee_email_template($name, $event->name, $event_date, $event->location, $site_name);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . get_bloginfo('admin_email') . '>',
    ];

    wp_mail($email, $subject, $message, $headers);
}

/**
 * Send notification email to the site admin
 */
function kk_ers_send_admin_email($attendee_name, $attendee_email, $event) {
    $site_name  = get_bloginfo('name');
    $admin_email = get_bloginfo('admin_email');
    $event_date = date_i18n(
        get_option('date_format') . ' ' . get_option('time_format'),
        strtotime($event->event_date)
    );

    $subject = sprintf(
        /* translators: %s = event name */
        __('[%s] New Registration: %s', 'kk-event-registration'),
        $site_name,
        $event->name
    );

    $registrations_url = admin_url('admin.php?page=kk-ers-registrations&kk_ers_event_id=' . $event->id);

    $message = kk_ers_admin_email_template($attendee_name, $attendee_email, $event->name, $event_date, $event->location, $registrations_url, $site_name);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $admin_email . '>',
    ];

    wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * HTML email template: Attendee confirmation
 */
function kk_ers_attendee_email_template($name, $event_name, $event_date, $location, $site_name) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#2271b1;padding:30px 40px;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;"><?php echo esc_html($site_name); ?></h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:35px 40px;color:#333333;">
                            <h2 style="margin:0 0 16px;color:#2271b1;font-size:20px;">✅ You're Registered!</h2>
                            <p style="margin:0 0 12px;font-size:15px;">Hi <?php echo esc_html($name); ?>,</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;">Your registration has been confirmed. Here are your event details:</p>

                            <table width="100%" cellpadding="12" cellspacing="0" style="background:#f8f9fa;border-radius:6px;margin-bottom:24px;">
                                <tr>
                                    <td style="font-weight:bold;width:110px;color:#555;">Event</td>
                                    <td><?php echo esc_html($event_name); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Date</td>
                                    <td><?php echo esc_html($event_date); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Location</td>
                                    <td><?php echo esc_html($location); ?></td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#666;">We look forward to seeing you there!</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa;padding:20px 40px;text-align:center;border-top:1px solid #eeeeee;">
                            <p style="margin:0;font-size:12px;color:#999;">&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * HTML email template: Admin notification
 */
function kk_ers_admin_email_template($attendee_name, $attendee_email, $event_name, $event_date, $location, $registrations_url, $site_name) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#2271b1;padding:30px 40px;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;"><?php echo esc_html($site_name); ?></h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:35px 40px;color:#333333;">
                            <h2 style="margin:0 0 16px;color:#2271b1;font-size:20px;">🆕 New Registration</h2>
                            <p style="margin:0 0 24px;font-size:15px;">A new attendee has registered for an event on your site.</p>

                            <table width="100%" cellpadding="12" cellspacing="0" style="background:#f8f9fa;border-radius:6px;margin-bottom:24px;">
                                <tr>
                                    <td style="font-weight:bold;width:130px;color:#555;">Attendee</td>
                                    <td><?php echo esc_html($attendee_name); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Email</td>
                                    <td><?php echo esc_html($attendee_email); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Event</td>
                                    <td><?php echo esc_html($event_name); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Date</td>
                                    <td><?php echo esc_html($event_date); ?></td>
                                </tr>
                                <tr style="border-top:1px solid #e5e5e5;">
                                    <td style="font-weight:bold;color:#555;">Location</td>
                                    <td><?php echo esc_html($location); ?></td>
                                </tr>
                            </table>

                            <a href="<?php echo esc_url($registrations_url); ?>"
                               style="display:inline-block;background:#2271b1;color:#fff;padding:12px 24px;border-radius:5px;text-decoration:none;font-weight:bold;font-size:14px;">
                                View All Registrations &rarr;
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa;padding:20px 40px;text-align:center;border-top:1px solid #eeeeee;">
                            <p style="margin:0;font-size:12px;color:#999;">This is an automated notification from <?php echo esc_html($site_name); ?>.</p>
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
