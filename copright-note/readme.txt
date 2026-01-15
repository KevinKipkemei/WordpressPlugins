=== Copyright Note ===
Contributors: kkipkemei
Tags: footer, copyright, automation
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

== Description ==

The Copyright Note plugin is designed to solve the manual task of adding and updating copyright notices on every post and page. It automatically appends a professional, dynamic copyright notice to the bottom of your content, ensuring your branding and legal rights are consistently visible.

**Problem Solved:**
- Eliminates the need to manually type a copyright notice for every post.
- Automatically updates the current year.
- Provides a consistent look across all single posts and pages.

== Installation ==

1. Upload the `copyright-note` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Testing for Collaboration ==

To verify the plugin is working correctly across different development environments:

1. **Activation:** Ensure the plugin activates without any PHP errors or warnings.
2. **Post Verification:** Navigate to any single post on the frontend of the site.
3. **Check Output:** Scroll to the bottom of the post. You should see a line that says "© [Current Year] Kevin Kipkemei. All rights reserved." with a top border.
4. **Conditional Check:** Navigate to the homepage or a category archive. The copyright notice should **not** appear there, as it is limited to single posts and pages (`is_singular`).
5. **Styling:** Confirm the notice has a 30px top margin and a subtle top border as defined in the code.
