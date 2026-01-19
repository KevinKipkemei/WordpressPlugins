=== Key Highlights Callout ===
Contributors: Kevin Kipkemei
Tags: meta-box, highlights, callout
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

Adds a structured "Key Highlights" box to the top of your posts.

== Description ==

This plugin solves the problem of important takeaways getting lost in long-form content. It adds a custom Meta Box to the post editor, allowing authors to manually highlight key information without needing complex block configurations.

**Problem Solved:**
*   Readers often skim content; this plugin ensures the most important point is seen first.
*   Enforces consistent styling across all posts.

**Features:**
*   **Checkbox Toggle:** Distinct control to enable/disable the box per post.
*   **Custom Message:** A simple textarea to write specific takeaways.
*   **Frontend Styling:** Automatically prepends a styled blue box to the post content.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/key-highlights-callout` directory.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Edit any post to see the new "Key Highlights" meta box.

== How to Test (For Collaborators) ==

1.  **Activate:** Ensure "Key Highlights Callout" is active in the Plugins menu.
2.  **Edit:** Open a Post (e.g., "Hello World") in the WordPress Editor.
3.  **Input:**
    *   Scroll to the "Key Highlights" box (below the main editor).
    *   Check **"Enable Key Highlight for this post"**.
    *   Type a multi-line message in the **"Highlight Message"** box.
4.  **Save:** Click "Update" or "Publish".
5.  **Verify:** View the post on the website. You should see a light blue box at the top containing your message.
    *   *Check:* Ensure newlines in your message are respected (displayed as line breaks).
    *   *Check:* Uncheck the box and save; ensure the highlight disappears.
