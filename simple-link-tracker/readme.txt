=== Simple Link Tracker ===
Contributors: Kevin Kipkemei
Tags: database, redirect, tracker, admin-ui
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

A custom URL shortener that masks links and tracks click counts using a custom database table.

== Description ==

This plugin demonstrates how to build a plugin that lives entirely "outside" the standard WordPress Post loop. It uses a custom database table to store link data and a custom Admin Dashboard page for management.

**Problem Solved:**
*   Allows tracking outbound clicks (affiliates, social media) without using a third-party service.
*   Creates clean, branded internal links (e.g., `yoursite.com/twitter`).

**Features:**
*   **Custom Database Table:** Creates `wp_slt_links` on activation to store data efficiently.
*   **Custom Admin UI:** A branded Dashboard page (not a standard Custom Post Type) with a handmade form and data table.
*   **Request Interception:** Hooks into WordPress to detect custom slugs and redirect visitors automatically.
*   **Click Counting:** Updates the database in real-time for every visit.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/simple-link-tracker` directory.
2.  **Activate** the plugin through the 'Plugins' menu.
    *   *Note:* This triggers the database table creation.
3.  Look for the **"Link Tracker"** menu item in your sidebar.

== How to Test (For Collaborators) ==

1.  **Activate:** Ensure "Simple Link Tracker" is active.
2.  **Create:** Go to **Link Tracker** in the admin menu.
    *   Enter Name: `Test Link`
    *   Enter Slug: `test` (keep it simple)
    *   Enter Target URL: `https://example.com`
    *   Click **Add Link**.
3.  **Verify UI:** Ensure the link appears in the table below with **0** clicks.
4.  **Test Redirect:** Open a new browser tab and go to:
    *   `http://yoursite.local/test` (Replace `http://yoursite.local` with your actual dev URL).
    *   You should be immediately redirected to `example.com`.
5.  **Verify Stats:** Go back to the **Link Tracker** admin page and refresh.
    *   The **Clicks** column for that link should now show **1**.
