=== Simple Contact Form ===
Contributors: Kevin Kipkemei
Tags: contact form, database, shortcode, frontend
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

A lightweight contact form that saves messages directly to a custom database table.

== Description ==

This plugin provides a simple, secure way to collect user messages without relying on email delivery services. Using a shortcode, you can place a contact form anywhere on your site. Submissions are sanitized and stored in a custom database table (`wp_scf_messages`), viewable from the WordPress Dashboard.

**Problem Solved:**
*   **Reliability:** Replaces flaky email delivery with permanent database storage.
*   **Privacy/Sovereignty:** Keeps user data on your own server instead of passing it to third-party form SaaS providers.

**Features:**
*   **Database First:** Messages are saved instantly to the database, ensuring no lost emails.
*   **Simple Shortcode:** Use `[simple_contact]` to render the form.
*   **Admin Dashboard:** View all inquiries in a clean, read-only list.
*   **Secure:** Implements nonce verification and strict sanitization.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/simple-contact-form` directory.
2.  **Activate** the plugin through the 'Plugins' menu.
3.  Create a new Page (e.g., "Contact") and add the shortcode `[simple_contact]`.
4.  View messages under the **"Contact Msgs"** menu.

== How to Test (For Collaborators) ==

1.  **Setup:** Create a page with `[simple_contact]`.
2.  **Submit:** Fill out the form as a visitor.
    *   *Note:* The form page will reload and show a success message upon submission.
3.  **Verify:** Go to **Contact Msgs** in the request dashboard.
    *   Ensure your new message appears at the top of the list.
