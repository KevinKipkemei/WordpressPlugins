=== Author Twitter Handle ===
Contributors: kkipkemei
Tags: twitter, author, social media, post meta
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple WordPress plugin that allows authors to add their Twitter handle to individual posts, displaying a follow link on the frontend.

== Description ==

Authors often want to promote their social media presence directly on the content they create. **Author Twitter Handle** solves this by providing a dedicated field in the post editor to store a Twitter (X) handle.

The plugin automatically appends a "Follow the author on Twitter" link to the end of the post content if a handle is provided.

=== Key Features ===
*   Adds a custom meta box to the post editor.
*   Securely saves and sanitizes author Twitter handles.
*   Automatically displays a follow link on single posts.
*   Lightweight and performance-focused.

== Installation ==

1. Upload the `author-twitter-handle` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit any post to see the "Author Twitter Handle" meta box in the sidebar.

== Frequently Asked Questions ==

= Does this work with the Block Editor (Gutenberg)? =
Yes, the meta box will appear in the post settings sidebar.

= Where does the link appear? =
The link is automatically appended to the end of the post content on single post pages.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added meta box for Twitter handle.
* Added automated frontend display link.
* Implemented security checks (nonces, permissions).

== Testing for Developers ==

To test the plugin during development or collaboration:

1.  **Meta Box Visibility**: Create or edit a post. Look for the "Author Twitter Handle" box in the sidebar.
2.  **Data Persistence**: Enter a handle (e.g., `wordpress`), save the post, and refresh. The handle should remain in the field.
3.  **Security**:
    *   Verify that only users with `edit_post` capabilities can save the data.
    *   Ensure the nonce field is present in the HTML and verified on save.
4.  **Frontend Layout**: View the post on the frontend. Ensure a link formatted as `Follow the author on Twitter: @handle` appears below the content.
5.  **Sanitization**: Try entering HTML tags in the field. They should be stripped out upon saving.
