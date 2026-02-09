# WordPress Plugin Development Quiz
*Concepts: Custom DB Tables, Admin UI, Shortcodes, Security*

### 1. The Database
**Q:** Which specific WordPress function should you use to create or modify database tables during plugin activation to ensure you don't break existing data?
*   A) `$wpdb->query("CREATE TABLE...")`
*   B) `dbDelta()`
*   C) `wp_create_table()`

### 2. The Hook
**Q:** You want to check if a user is visiting a specific URL (like `/my-slug`) and redirect them *before* the theme loads. Which hook is best?
*   A) `init`
*   B) `wp_footer`
*   C) `template_redirect`

### 3. Shortcodes
**Q:** A shortcode function must always \_\_\_\_\_\_ its content.
*   A) Echo
*   B) Return
*   C) Print

### 4. Output Buffering
**Q:** Why do we use `ob_start()` and `ob_get_clean()` in our shortcode?
*   A) To make the HTML load faster.
*   B) To capture HTML output into a string so we can `return` it.
*   C) To sanitize the HTML automatically.

### 5. Security (Database)
**Q:** When inserting user input into the database with SQL, how do we prevent SQL Injection?
*   A) Use `strip_tags()`
*   B) Use `$wpdb->prepare()`
*   C) It's unnecessary if we trust the user.

### 6. Security (Forms)
**Q:** What hidden field do we include in forms to prevent CSRF (Cross-Site Request Forgery) attacks?
*   A) The CAPTCHA
*   B) The Nonce (`wp_nonce_field`)
*   C) The Admin Password

### 7. Redirects
**Q:** After calling `wp_redirect($url)`, what function must you call immediately to stop the script from continuing to execute?
*   A) `stop()`
*   B) `return;`
*   C) `exit;`

### 8. Admin UI
**Q:** Which function adds a new item to the main WordPress dashboard sidebar?
*   A) `add_menu_page()`
*   B) `register_admin_menu()`
*   C) `wp_add_dashboard_widget()`

### 9. Sanitization vs. Escaping
**Q:** When **saving** data, we use `sanitize_*`. When **outputting** data to HTML, we use:
*   A) `clean_*`
*   B) `esc_*` (e.g., `esc_html`, `esc_attr`)
*   C) `validate_*`

### 10. The Lifecycle
**Q:** `register_activation_hook` runs:
*   A) Every time a page loads.
*   B) Only when the user clicks "Activate" on the plugin.
*   C) When the user saves settings.

---

## Answers
1. **B** (dbDelta handles updates safely)
2. **C** (template_redirect handles requests before headers are sent)
3. **B** (Shortcodes replace content, echoing prints it at the top of the page)
4. **B** (To capture HTML into a variable)
5. **B** ($wpdb->prepare uses placeholders like %s)
6. **B** (Nonces verify the request intent)
7. **C** (exit stops WordPress from loading the rest of the page)
8. **A** (add_menu_page)
9. **B** (Escaping makes data safe for display)
10. **B** (It's a one-time setup trigger)
