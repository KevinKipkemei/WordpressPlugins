# Event Registration System - Learning Notes

This document contains key lessons and concepts learned during each phase of development.

---

## Phase 1: Database Setup ✅

### Key Concepts Learned

#### 1. **Multiple Table Creation with `dbDelta()`**
```php
function kk_ers_install_tables() {
    global $wpdb, $kk_ers_db_version;
    
    // Create multiple tables
    $sql_events = "CREATE TABLE {$wpdb->prefix}kk_ers_events (...)";
    $sql_registrations = "CREATE TABLE {$wpdb->prefix}kk_ers_registrations (...)";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_events);
    dbDelta($sql_registrations);
}
```
**Lesson:** You can create multiple tables in one activation function by calling `dbDelta()` multiple times. Each table gets its own SQL statement.

---

#### 2. **Foreign Key Relationships (Conceptual)**
```sql
CREATE TABLE wp_kk_ers_registrations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    event_id mediumint(9) NOT NULL,  -- Links to events table
    ...
    KEY event_id (event_id)  -- Index for faster lookups
)
```
**Lesson:** While WordPress doesn't enforce foreign key constraints, we use:
- `event_id` column to link registrations to events
- `KEY event_id (event_id)` creates an index for faster JOIN queries
- This is a **one-to-many** relationship (1 event → many registrations)

---

#### 3. **Database Versioning Pattern**
```php
global $kk_ers_db_version;
$kk_ers_db_version = '1.0';

function kk_ers_install_tables() {
    global $kk_ers_db_version;
    // ... create tables ...
    add_option('kk_ers_db_version', $kk_ers_db_version);
}

function kk_ers_update_db_check() {
    global $kk_ers_db_version;
    if (get_site_option('kk_ers_db_version') != $kk_ers_db_version) {
        kk_ers_install_tables();
    }
}
add_action('plugins_loaded', 'kk_ers_update_db_check');
```
**Lesson:** This pattern allows seamless schema updates:
- Change `$kk_ers_db_version` to `'1.1'`
- Modify the SQL (e.g., add a column)
- `dbDelta()` will automatically update existing tables
- No need for users to deactivate/reactivate

---

#### 4. **Proper Uninstall Cleanup**
```php
// uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_ers_registrations");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_ers_events");
delete_option('kk_ers_db_version');
```
**Lesson:** 
- Drop tables in **reverse order** of creation (registrations first, then events) to avoid foreign key issues
- Always delete associated options
- Use `DROP TABLE IF EXISTS` to prevent errors if tables don't exist

---

#### 5. **Global Variable Scope in Functions**
```php
global $kk_ers_db_version;  // Declare at file level
$kk_ers_db_version = '1.0';

function kk_ers_install_tables() {
    global $wpdb, $kk_ers_db_version;  // Must re-declare inside function
    // Now we can use $kk_ers_db_version here
}
```
**Lesson:** PHP functions can't access global variables unless you explicitly declare them with `global` keyword inside the function.

---

#### 6. **MySQL Data Types for WordPress**
| Column Type | MySQL Type | Use Case |
|-------------|------------|----------|
| ID | `mediumint(9)` | Auto-increment primary keys |
| Short text | `varchar(255)` | Names, emails, URLs |
| Long text | `text` | Descriptions, messages |
| Date/Time | `datetime` | Event dates, timestamps |
| Numbers | `mediumint(9)` | Counts, quantities |

**Lesson:** 
- `mediumint(9)` supports up to ~8 million records (sufficient for most plugins)
- `varchar(255)` is standard for short text fields
- `datetime` stores both date and time (vs `date` which is date-only)

---

#### 7. **Database Table Naming Convention**
```php
$wpdb->prefix . 'kk_ers_events'
// Results in: wp_kk_ers_events
```
**Lesson:** 
- Always use `$wpdb->prefix` (default: `wp_`)
- Add your author prefix (`kk_`)
- Add plugin prefix (`ers_`)
- Add table purpose (`events`, `registrations`)
- This prevents collisions with other plugins

---

### Common Mistakes to Avoid

❌ **Forgetting to declare global variables in functions**
```php
// Wrong
function my_function() {
    add_option('key', $kk_ers_db_version); // Undefined variable!
}

// Correct
function my_function() {
    global $kk_ers_db_version;
    add_option('key', $kk_ers_db_version);
}
```

❌ **Not using `require_once` for upgrade.php**
```php
// Wrong
dbDelta($sql); // Fatal error: dbDelta() not found

// Correct
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
```

❌ **Incorrect SQL formatting for `dbDelta()`**
```php
// Wrong - dbDelta is picky about formatting
$sql = "CREATE TABLE $table (id INT, name VARCHAR(255))";

// Correct - specific formatting required
$sql = "CREATE TABLE $table (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    PRIMARY KEY  (id)
) $charset_collate;";
```

---

### Testing Checklist for Phase 1

- [x] Plugin activates without errors
- [x] Tables `wp_kk_ers_events` and `wp_kk_ers_registrations` created in database
- [x] Option `kk_ers_db_version` set to `1.0`
- [x] Plugin deactivates cleanly
- [x] Plugin deletes cleanly (tables dropped, option removed)

---

### What's Next?

**Phase 2:** We'll build the admin interface to create and manage events. You'll learn:
- Creating admin menu pages with `add_menu_page()`
- Building HTML forms with nonce protection
- Processing form submissions securely
- Displaying data in WordPress-style tables

---

*End of Phase 1 Notes*

---

## Phase 2: Admin Event Management ✅

### Key Concepts Learned

#### 1. **Admin Menu Hierarchy (`add_menu_page` + `add_submenu_page`)**
```php
// Top-level menu
add_menu_page(
    'Event Registration', // Page title (browser tab)
    'Events',             // Menu label in sidebar
    'manage_options',     // Who can see it (Admins only)
    'kk-ers-events',      // Unique slug
    'kk_ers_render_events_page', // Callback function
    'dashicons-calendar-alt',    // Icon
    25                           // Position in sidebar
);

// Submenu item
add_submenu_page(
    'kk-ers-events',       // Parent slug
    'Add New Event',       // Page title
    'Add New Event',       // Submenu label
    'manage_options',
    'kk-ers-add-event',
    'kk_ers_render_add_event_page'
);
```
**Lesson:** `add_menu_page()` creates the top-level item. `add_submenu_page()` nests items under it. The first argument of `add_submenu_page()` must match the parent's slug.

---

#### 2. **The Admin Form Security Pattern (Nonce + Capability + Sanitize)**
```php
function kk_ers_handle_event_submission() {
    if (!isset($_POST['kk_ers_submit_event'])) return; // 0. Only run for our form

    // 1. Verify nonce (CSRF protection)
    if (!wp_verify_nonce($_POST['kk_ers_event_nonce'], 'kk_ers_save_event')) {
        wp_die('Security check failed.');
    }

    // 2. Check permissions (Authorization)
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission.');
    }

    // 3. Sanitize all inputs (Data cleaning)
    $name = sanitize_text_field($_POST['kk_ers_name']);
    $max  = absint($_POST['kk_ers_max_attendees']); // absint = absolute integer (no negatives)

    // 4. Validate (Business logic)
    if (empty($name) || $max < 1) {
        wp_redirect(add_query_arg('kk_ers_error', '1', wp_get_referer()));
        exit;
    }

    // 5. Insert or Update
    $wpdb->insert($table, $data);
    wp_redirect(add_query_arg('kk_ers_added', '1', admin_url('admin.php?page=kk-ers-events')));
    exit;
}
add_action('admin_init', 'kk_ers_handle_event_submission');
```
**Lesson:** Always follow this exact order: **Check → Verify → Authorize → Sanitize → Validate → Process → Redirect**. The `exit` after `wp_redirect()` is critical — without it, PHP continues executing.

---

#### 3. **`$wpdb->insert()` vs `$wpdb->update()`**
```php
// INSERT (new record)
$wpdb->insert(
    $table_name,          // Table
    ['name' => $name, 'location' => $location], // Data (column => value)
    ['%s', '%s']          // Format (optional but good practice)
);

// UPDATE (existing record)
$wpdb->update(
    $table_name,          // Table
    ['name' => $name],    // Data to change
    ['id' => $event_id]   // WHERE clause
);
```
**Lesson:** `$wpdb->insert()` and `$wpdb->update()` automatically escape values — no need for `prepare()`. Use them instead of raw SQL for simple INSERT/UPDATE/DELETE operations.

---

#### 4. **JOIN Queries to Combine Tables**
```php
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
```
**Lesson:**
- `LEFT JOIN` returns all events, even those with 0 registrations
- `COUNT(r.id)` counts registrations per event
- `GROUP BY e.id` is required when using aggregate functions like `COUNT()`
- `e.*` selects all columns from events, `r` is the alias for registrations

---

#### 5. **One-Click Delete with Nonce (GET-based action)**
```php
// Generate the delete URL with a unique nonce
$delete_url = add_query_arg([
    'kk_ers_action'       => 'delete',
    'kk_ers_event_id'     => $event->id,
    'kk_ers_delete_nonce' => wp_create_nonce('kk_ers_delete_event_' . $event->id),
], admin_url('admin.php'));

// Process it in admin_init
function kk_ers_handle_event_deletion() {
    if (!isset($_GET['kk_ers_action']) || $_GET['kk_ers_action'] !== 'delete') return;
    
    $event_id = absint($_GET['kk_ers_event_id']);
    if (!wp_verify_nonce($_GET['kk_ers_delete_nonce'], 'kk_ers_delete_event_' . $event_id)) {
        wp_die('Security check failed.');
    }
    // Delete registrations first, then the event
    $wpdb->delete($registrations_table, ['event_id' => $event_id]);
    $wpdb->delete($events_table, ['id' => $event_id]);
}
add_action('admin_init', 'kk_ers_handle_event_deletion');
```
**Lesson:** Include the record ID in the nonce action string (`'kk_ers_delete_event_' . $event->id`). This makes each nonce unique per record, preventing one nonce from being used to delete a different record.

---

#### 6. **Add/Edit Form Reuse Pattern**
```php
// Check if editing
$event_id = isset($_GET['kk_ers_event_id']) ? absint($_GET['kk_ers_event_id']) : 0;
$event    = $event_id > 0 ? $wpdb->get_row(...) : null;

// Populate form fields with existing data (or empty string for new)
<input value="<?php echo esc_attr($event->name ?? ''); ?>">

// Dynamic submit button label
<input type="submit" value="<?php echo $event ? 'Update Event' : 'Add Event'; ?>">
```
**Lesson:** The `??` (null coalescing operator) returns the left side if it exists, otherwise the right. This lets one form handle both adding and editing, reducing code duplication.

---

#### 7. **Admin Notices (Success/Error Feedback)**
```php
// After redirect, check for query params
if (isset($_GET['kk_ers_added'])) {
    echo '<div class="notice notice-success is-dismissible"><p>Event added!</p></div>';
}
```
**Lesson:** Never show a success message before redirecting. Instead:
1. Process the form
2. Redirect with a query param (`?kk_ers_added=1`)
3. On the next page load, check for that param and show the notice

This prevents the "resubmit form on refresh" browser warning.

---

#### 8. **Conditional Asset Loading**
```php
function kk_ers_enqueue_admin_styles($hook) {
    $our_pages = ['toplevel_page_kk-ers-events', 'events_page_kk-ers-add-event'];
    if (!in_array($hook, $our_pages)) return;
    wp_enqueue_style('kk-ers-admin-style', plugin_dir_url(__FILE__) . 'css/admin.css');
}
add_action('admin_enqueue_scripts', 'kk_ers_enqueue_admin_styles');
```
**Lesson:** The `$hook` parameter follows the pattern `{parent_slug}_page_{child_slug}`. The top-level page hook is `toplevel_page_{slug}`. Always check this to avoid loading your CSS on every admin page.

---

### Common Mistakes to Avoid

❌ **Forgetting `exit` after `wp_redirect()`**
```php
// Wrong - PHP keeps executing after redirect
wp_redirect($url);
echo 'This still runs!';

// Correct
wp_redirect($url);
exit;
```

❌ **Using `$_POST` data directly in the database**
```php
// Wrong - SQL injection risk
$wpdb->query("INSERT INTO $table (name) VALUES ('{$_POST['name']}')");

// Correct - use $wpdb->insert() which escapes automatically
$wpdb->insert($table, ['name' => sanitize_text_field($_POST['name'])]);
```

❌ **Deleting a parent record before child records**
```php
// Wrong - orphaned registrations remain in DB
$wpdb->delete($events_table, ['id' => $event_id]);
$wpdb->delete($registrations_table, ['event_id' => $event_id]);

// Correct - delete children first
$wpdb->delete($registrations_table, ['event_id' => $event_id]);
$wpdb->delete($events_table, ['id' => $event_id]);
```

---

### Testing Checklist for Phase 2

- [ ] "Events" menu appears in WordPress admin sidebar
- [ ] "Add New Event" form loads correctly
- [ ] Submitting the form with all fields creates an event
- [ ] Submitting with missing fields shows an error notice
- [ ] Events appear in the "All Events" table
- [ ] Edit link pre-populates the form with existing data
- [ ] Updating an event saves changes correctly
- [ ] Delete link shows a confirmation dialog
- [ ] Deleting an event removes it from the table

---

### What's Next?

**Phase 3:** We'll build the Registrations admin page. You'll learn:
- Submenu pages with filtered data
- JOIN queries across multiple tables
- Generating CSV file downloads with PHP

---

*End of Phase 2 Notes*

---

## Phase 3: Admin Registration Management ✅

### Key Concepts Learned

#### 1. **Adding Multiple Hooks to the Same Action**
```php
// Phase 2 already registered admin_menu with a named function
add_action('admin_menu', 'kk_ers_add_admin_menu');

// Phase 3 adds ANOTHER submenu using an anonymous function on the same hook
add_action('admin_menu', function () {
    add_submenu_page('kk-ers-events', 'Registrations', ...);
});
```
**Lesson:** WordPress allows multiple callbacks on the same action hook. Each `add_action()` call stacks — they all run when the hook fires. An optional 3rd argument sets priority (default: 10).

---

#### 2. **INNER JOIN vs LEFT JOIN**
```sql
-- LEFT JOIN: Returns ALL events even with 0 registrations
SELECT e.*, COUNT(r.id) FROM events e LEFT JOIN registrations r ON e.id = r.event_id

-- INNER JOIN: Returns ONLY registrations linked to an existing event
SELECT r.*, e.name FROM registrations r INNER JOIN events e ON r.event_id = e.id
```
**Lesson:**
- Use `LEFT JOIN` when you want **all records from the left table** (e.g., show all events even empty ones)
- Use `INNER JOIN` when you only want **matching records** (e.g., show registrations that have a valid event)
- Phase 3 uses `INNER JOIN` because we only care about registrations that have a matching event

---

#### 3. **Conditional WHERE Clause (Dynamic Filtering)**
```php
if ($selected_event_id > 0) {
    // Filtered: add WHERE clause
    $registrations = $wpdb->get_results(
        $wpdb->prepare("SELECT ... WHERE r.event_id = %d", $selected_event_id)
    );
} else {
    // All: no WHERE clause
    $registrations = $wpdb->get_results(
        $wpdb->prepare("SELECT ... ORDER BY e.event_date ASC")
    );
}
```
**Lesson:** Build two separate queries rather than trying to build a complex dynamic SQL string. It's cleaner, safer, and easier to read. Always validate and sanitize filter values with `absint()` before using in queries.

---

#### 4. **CSV Export with PHP (`fputcsv`)**
```php
function kk_ers_handle_csv_export() {
    // 1. Security: nonce + capability check
    if (!wp_verify_nonce(...)) wp_die(...);
    if (!current_user_can('manage_options')) wp_die(...);

    // 2. Fetch data as associative array (ARRAY_A)
    $rows = $wpdb->get_results($wpdb->prepare("SELECT ..."), ARRAY_A);

    // 3. Set HTTP headers to trigger file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registrations.csv"');
    header('Pragma: no-cache');

    // 4. Open PHP's output buffer as a file stream
    $output = fopen('php://output', 'w');

    // 5. Write column headers first
    fputcsv($output, ['Event Name', 'Attendee Name', 'Email', 'Date']);

    // 6. Write each data row
    foreach ($rows as $row) {
        fputcsv($output, [$row['event_name'], $row['attendee_name'], ...]);
    }

    fclose($output);
    exit; // CRITICAL: stop WordPress from outputting anything else
}
add_action('admin_init', 'kk_ers_handle_csv_export');
```
**Lesson Key Points:**
- `ARRAY_A` returns rows as associative arrays instead of objects (useful for `fputcsv()`)
- `php://output` is a virtual file stream that writes directly to the browser response
- The `Content-Disposition: attachment` header is what tells the browser to **download** the file instead of displaying it
- `exit` after `fclose()` is essential — without it, WordPress HTML output would corrupt the CSV

---

#### 5. **`selected()` Helper Function**
```php
<select name="kk_ers_event_id">
    <option value="0">— All Events —</option>
    <?php foreach ($events as $event) : ?>
        <option value="<?php echo esc_attr($event->id); ?>"
            <?php selected($selected_event_id, $event->id); ?>>
            <?php echo esc_html($event->name); ?>
        </option>
    <?php endforeach; ?>
</select>
```
**Lesson:** `selected($val1, $val2)` is a WordPress helper that echoes `selected="selected"` if the two values match. It's the clean, readable alternative to writing `<?php echo ($a == $b) ? 'selected' : ''; ?>`. WordPress has siblings: `checked()` for checkboxes and `disabled()` for disabled fields.

---

#### 6. **`printf()` for Translated Strings with Numbers**
```php
// Wrong - can't translate a concatenated string properly
echo 'Total: ' . count($registrations);

// Correct - use printf with a translatable format string
printf(
    esc_html__('Total registrations shown: %d', 'kk-event-registration'),
    count($registrations)
);
```
**Lesson:** `printf()` inserts the number into the format string at `%d`. This way the whole phrase (including "Total registrations shown:") is one translatable string, and translators can reorder the number relative to the text if their language requires it.

---

### Common Mistakes to Avoid

❌ **Using INNER JOIN when you need ALL records**
```sql
-- Wrong: Events with 0 registrations disappear
SELECT e.name, COUNT(r.id) FROM events e
INNER JOIN registrations r ON e.id = r.event_id
GROUP BY e.id

-- Correct: LEFT JOIN keeps all events
SELECT e.name, COUNT(r.id) FROM events e
LEFT JOIN registrations r ON e.id = r.event_id
GROUP BY e.id
```

❌ **Forgetting `ARRAY_A` for `fputcsv()`**
```php
// Wrong - returns objects, fputcsv() needs arrays
$rows = $wpdb->get_results($wpdb->prepare("SELECT ..."));
fputcsv($output, $rows[0]); // Error!

// Correct
$rows = $wpdb->get_results($wpdb->prepare("SELECT ..."), ARRAY_A);
fputcsv($output, $rows[0]); // Works!
```

❌ **Not calling `exit` after the CSV export**
```php
// Wrong - WordPress HTML output corrupts the CSV file
fclose($output);
// WordPress continues and outputs its HTML...

// Correct
fclose($output);
exit;
```

---

### Testing Checklist for Phase 3

- [ ] "Registrations" submenu appears under "Events" in admin sidebar
- [ ] Page loads showing all registrations across all events
- [ ] Event filter dropdown shows all events
- [ ] Selecting an event filters registrations correctly
- [ ] "Full" badge appears when registrations reach max
- [ ] "Export CSV" button downloads a file
- [ ] CSV file opens correctly in Excel/Sheets with correct columns
- [ ] Filtered export only includes registrations for selected event

---

### What's Next?

**Phase 4:** We'll build the frontend registration form with a shortcode. You'll learn:
- Shortcode registration and output buffering
- Seat availability checking before insert
- Duplicate registration prevention
- Sticky form inputs after validation errors

---

*End of Phase 3 Notes*

---

## Phase 4: Frontend Registration Form ✅

### Key Concepts Learned

#### 1. **Shortcodes Must RETURN, Not `echo`**
```php
function kk_ers_registration_shortcode() {
    // ❌ Wrong - echo outputs directly, breaks page layout
    echo '<div>...</div>';

    // ✅ Correct - buffer output, then return it
    ob_start();
    ?>
    <div class="kk-ers-form-wrap">...</div>
    <?php
    return ob_get_clean();
}
add_shortcode('event_registration', 'kk_ers_registration_shortcode');
```
**Lesson:** `ob_start()` starts output buffering — PHP captures everything instead of sending it to the browser. `ob_get_clean()` returns the captured content and clears the buffer. This is the standard pattern for shortcodes with large HTML blocks.

---

#### 2. **Sticky Form Inputs (Preserve Values on Error)**
```php
// Initialize defaults BEFORE processing
$form = ['event_id' => 0, 'name' => '', 'email' => ''];

if (isset($_POST['kk_ers_register_submit'])) {
    // Populate from POST on submission
    $form['name']  = sanitize_text_field($_POST['kk_ers_name'] ?? '');
    $form['email'] = sanitize_email($_POST['kk_ers_email'] ?? '');
}

// In the form — value always comes from $form array
<input type="text" name="kk_ers_name" value="<?php echo esc_attr($form['name']); ?>">
```
**Lesson:** Instead of reading `$_POST` directly in the form HTML, store sanitized values in a `$form` array. If validation fails, the form re-renders with the user's input still filled in. On success, reset `$form` to empty values so the form clears.

---

#### 3. **Layered Validation (Early Returns)**
```php
// Layer 1: Nonce (security)
if (!wp_verify_nonce(...)) { $errors[] = '...'; }
else {
    // Layer 2: Field validation (only if nonce passed)
    if (empty($form['name'])) { $errors[] = '...'; }

    if (empty($errors)) {
        // Layer 3: Business logic (only if fields are valid)
        $event = $wpdb->get_row(...); // Check event exists

        // Layer 4: Seat availability (only if event found)
        if ($reg_count >= $event->max_attendees) { $errors[] = '...'; }

        if (empty($errors)) {
            // Layer 5: Duplicate check (only if seats available)
            $existing = $wpdb->get_var(...);

            if (empty($errors)) {
                // Layer 6: Insert (only if no duplicates)
                $wpdb->insert(...);
            }
        }
    }
}
```
**Lesson:** Nest validations so you only run expensive checks (database queries) after cheaper ones (empty field checks) pass. This pattern prevents unnecessary DB calls and makes errors clear.

---

#### 4. **Seat Availability Check**
```php
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

// Usage
$reg_count = kk_ers_get_registration_count($event->id);
if ($reg_count >= $event->max_attendees) {
    $errors[] = 'This event is fully booked.';
}
```
**Lesson:** Extract reusable logic into helper functions. `$wpdb->get_var()` returns a single value (the COUNT). Cast to `(int)` to ensure it's always a number, never `null`.

---

#### 5. **Duplicate Registration Prevention**
```php
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
        __('You have already registered for "%s" with this email.', 'kk-event-registration'),
        esc_html($event->name)
    );
}
```
**Lesson:** Use `COUNT(*)` with a WHERE on both `event_id` AND `email` — this allows the same email to register for *different* events, but blocks duplicate registration for the *same* event. The check happens AFTER the seat check so the seat error shows first.

---

#### 6. **`disabled()` Helper for Full Events in Dropdowns**
```php
<option value="<?php echo esc_attr($event->id); ?>"
    <?php selected($form['event_id'], $event->id); ?>
    <?php disabled($is_full, true); ?>>
    <?php echo esc_html($event->name); ?>
    (<?php echo $is_full ? 'Full' : "$seats_left seats left"; ?>)
</option>
```
**Lesson:** `disabled($value, $compare)` outputs `disabled="disabled"` when the two values match — just like `selected()`. Disabled options still display in the dropdown but can't be chosen. This gives users visual feedback without hiding full events entirely.

---

#### 7. **`current_time('mysql')` for Date Comparisons**
```php
// Only fetch upcoming events
$wpdb->prepare(
    "SELECT * FROM %i WHERE event_date > %s ORDER BY event_date ASC",
    $events_table,
    current_time('mysql')  // Returns current time in MySQL datetime format
)
```
**Lesson:** Always use `current_time('mysql')` instead of PHP's `date()` for WordPress date comparisons. It respects the timezone set in **Settings → General** in WordPress admin, ensuring "upcoming" means upcoming in the site's local timezone.

---

### Common Mistakes to Avoid

❌ **Using `echo` in a shortcode**
```php
// Wrong - breaks the surrounding page layout
function my_shortcode() {
    echo '<p>Hello</p>'; // Output is placed at the top of the page!
}

// Correct
function my_shortcode() {
    ob_start();
    echo '<p>Hello</p>';
    return ob_get_clean();
}
```

❌ **Not sanitizing `sanitize_email()` — and not confirming with `is_email()`**
```php
// Wrong - sanitize_email() strips bad chars but doesn't validate format
$email = sanitize_email($_POST['email']);
// "notanemail" passes sanitize_email() just fine!

// Correct - sanitize first, then validate format
$email = sanitize_email($_POST['email']);
if (!is_email($email)) {
    $errors[] = 'Please enter a valid email address.';
}
```

❌ **Checking seat availability but not duplicate registration**
```php
// Incomplete - allows same person to register multiple times
if ($reg_count >= $event->max_attendees) {
    $errors[] = 'Event is full.';
}
// Missing: duplicate email check before insert
```

---

### Testing Checklist for Phase 4

- [ ] Create a WordPress page and add `[event_registration]` to its content
- [ ] Visit the page — form should appear with event dropdown
- [ ] Try submitting with empty fields — error messages should show
- [ ] Enter an invalid email — error message should show
- [ ] Register successfully for an event — success message appears, form clears
- [ ] Try registering again with same email for same event — duplicate error shows
- [ ] Check admin → Registrations — new registration appears
- [ ] Create an event with 1 max attendee, fill it, then try from frontend — "Full" shows

---

### What's Next?

**Phase 5:** Email Notifications with `wp_mail()`. You'll learn:
- Sending emails with `wp_mail()`
- Building HTML email templates
- Sending to multiple recipients (attendee + admin)
- Using `get_bloginfo()` for dynamic site/admin info

---

*End of Phase 4 Notes*
