=== Basic Reading Time ===
Contributors: kkipkemei
Tags: reading time, productivity, engagement, user experience
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

== Description ==

Basic Reading Time enhances reader engagement by automatically calculating and displaying an estimated reading time for every post. By knowing the time commitment upfront, readers are more likely to engage with and finish your content.

**Problem Solved:**
- Helps readers manage their time by indicating article length.
- Improves user experience by providing a helpful "meta" detail.
- Handles plurals correctly (e.g., "1 minute" vs "2 minutes") through WordPress localization functions.

== Installation ==

1. Upload the `reading-time` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Testing for Collaboration ==

Use these steps to verify the plugin's logic and display:

1. **Activation:** Activate the plugin and ensure no errors are thrown.
2. **Frontend check:** View a single post. You should see "⏱️ Estimated reading time: X minute(s)" pre-pended to the post content.
3. **Plurality Test:** 
   - Create a post with ~100 words. Since the speed is 200 WPM, it should say "1 minute".
   - Create a post with ~300 words. It should say "2 minutes".
4. **HTML Filtering:** Ensure the reading time doesn't include HTML tags in its count (the plugin uses `strip_tags` to ensure accuracy).
5. **Main Query Check:** Verify that the reading time only appears on the main post content and doesn't leak into sidebar widgets or footer loops that might use `the_content`.
