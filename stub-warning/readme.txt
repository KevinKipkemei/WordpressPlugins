=== Stub Warning ===
Contributors: kkipkemei
Tags: thin content, warning, stub, seo
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

== Description ==

Stub Warning addresses the issue of "thin content" on websites. Many sites have short, unfinished articles (stubs) that might lead to a poor user experience or SEO penalties. This plugin alerts readers when they are viewing a short article and provides a customizable warning message.

**Problem Solved:**
- Notifies readers that an article is brief or incomplete.
- Allows administrators to set a custom word count threshold.
- Provides a centralized settings page to manage the warning look and feel.

== Installation ==

1. Upload the `stub-warning` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to `Settings > Stub Warning` to configure your threshold and message.

== Testing for Collaboration ==

Follow these steps to test the plugin functionality across collaborative environments:

1. **Activation:** Activate the plugin and ensure the "Stub Warning" link appears under the "Settings" menu.
2. **Setup Threshold:** Go to `Settings > Stub Warning` and set the word count threshold to `50`. Save changes.
3. **Test Short Post:** Create a post with only 20-30 words. Visit the post on the frontend; you should see the red warning box at the top.
4. **Test Long Post:** Create a post with over 60 words. The warning should **not** appear.
5. **Update Message:** Back in settings, change the "Warning Message" text. Verify that the frontend reflects the new message on short posts immediately.
6. **Security Check:** Try to save settings as a non-administrator user (if possible) to ensure the `manage_options` capability requirement is respected.
