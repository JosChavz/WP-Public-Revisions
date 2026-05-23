=== WP Public Revisions ===
Contributors: custom
Tags: revisions, changelog, history, transparency
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later

Display a public revision history on any post or page. Visitors can click through to read any past version of the content.

== Description ==

WP Public Revisions lets you show your readers a transparent edit history for your posts and pages. Drop the `[revision_history]` shortcode into any content area and visitors will see a numbered list of every saved version, with dates and author names. Clicking any entry loads the full content of that past version, with a notice banner and a link back to the current version.

**Features:**

* `[revision_history]` shortcode — place it wherever you like
* Clickable links to view the full text of any past revision
* Clean, theme-neutral styling with dark-mode support
* Admin meta box reminder showing the shortcode and revision count
* Filters out autosaves — only shows real editorial saves
* Only works on published posts (drafts stay private)
* Security checks ensure revisions can't be viewed across posts

== Installation ==

1. Upload the `wp-public-revisions` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu.
3. Edit any post or page and paste `[revision_history]` where you want the list to appear.

== Shortcode Attributes ==

* `post_id` — Show revisions for a specific post (defaults to the current post).
* `limit` — Maximum number of revisions to display (default: 20, use -1 for all).
* `date_fmt` — PHP date format string (default: `F j, Y at g:i A`).

Example: `[revision_history limit="10" date_fmt="Y-m-d"]`

== Frequently Asked Questions ==

= Does this expose draft revisions? =
No. Only revisions of published posts are visible. Autosaves are also excluded.

= Can I style it to match my theme? =
Yes. The plugin uses minimal CSS with class names like `.wppr-revision-list`, `.wppr-item`, and `.wppr-revision-notice` that you can override in your theme's stylesheet.

== Changelog ==

= 1.0.0 =
* Initial release.
