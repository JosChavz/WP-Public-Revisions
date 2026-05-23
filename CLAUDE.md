# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

WP Public Revisions is a WordPress plugin that exposes a post's revision history to front-end visitors via the `[revision_history]` shortcode. Visitors can click any past version to read the full content of that revision.

## Development Setup

This plugin runs inside a local WordPress install managed by [LocalWP](https://localwp.com/). The site root is at `/Users/hozayx/Local Sites/blog-de-jose/app/public/`.

**Composer** (for autoloading only — no runtime dependencies):
```bash
composer install          # first-time setup
composer dump-autoload    # after adding new classes
```

There is no build step, no test suite, and no bundler. PHP files are served directly.

## Architecture

### Entry Point & Bootstrap

`wp-public-revisions.php` is the plugin header file. It:
1. Defines three constants: `WPR_SLUG`, `WPR_ROOT_PATH` (filesystem path), `WPR_FRONTEND_PATH` (URL path).
2. Requires `vendor/autoload.php` (Composer PSR-4).
3. Instantiates `\WpPublicRevisions\Initialize` and calls `init()`.
4. Registers an admin meta box directly (procedural, not through a class).

### Namespace & Autoloading

Composer maps `WpPublicRevisions\` → `src/`. All class files live under `src/`.

| File | Class | Purpose |
|------|-------|---------|
| `src/initialize.php` | `WpPublicRevisions\Initialize` | Bootstrap — wires up frontend classes |
| `src/frontend/shortcode.php` | `WpPublicRevisions\Frontend\Shortcode` | `[revision_history]` shortcode + asset enqueueing |
| `src/frontend/viewer.php` | `WPPublicRevisions\Frontend\Viewer` | `the_content` filter that swaps content for a past revision when `?wppr_view_revision=ID` is present |
| `src/admin/settings.php` | `WpPublicRevisions\Options` | Placeholder — not yet implemented |

### Request Flow

**Shortcode list view** (`[revision_history]` in post content):
- `Shortcode::wppr_revision_history_shortcode()` runs, fetches revisions with `wp_get_post_revisions()`, filters out autosaves, and renders an `<ol>` of links.
- Each link points to `?wppr_view_revision={revision_ID}` on the same post permalink.
- The history box is hidden by default; `assets/js/frontend.js` toggles it via the "Previous" button.

**Revision viewer** (`?wppr_view_revision=ID`):
- `Viewer::wppr_maybe_show_revision()` hooks onto `the_content` (priority 5).
- It validates: revision exists, is of type `revision`, its parent is published, and the parent matches the current post — then replaces `$content` with the historical content plus a notice banner.

### Known Issues / Incomplete Work

- **`Viewer` is not wired up**: `Initialize::init()` only instantiates `Shortcode`. `Viewer` must be added there to make the revision viewer functional.
- **Namespace inconsistency**: `Viewer` uses `WPPublicRevisions\Frontend` (all-caps WP) while everything else uses `WpPublicRevisions\`. This will cause an autoload miss.
- **Debug artifact**: `error_log("here")` at `src/frontend/viewer.php:23` should be removed.
- **`Options` class** (`src/admin/settings.php`) is an empty placeholder.
- **`assets/js/admin.js`** exists but is never enqueued.

## CSS Classes (for theming)

Front-end output uses these classes, which can be overridden in a theme stylesheet:

- `.wppr-revision-list` — outer wrapper for the shortcode
- `.wppr-history-box` — collapsible list container (hidden by default)
- `.wppr-item`, `.wppr-link`, `.wppr-label`, `.wppr-author` — list items
- `.wppr-revision-notice` — banner shown on historical revision views
- `.wppr-revision-content` — wrapper for the historical post content
- `.wppr-no-revisions` — shown when there are no revisions

Dark mode overrides are handled via `@media (prefers-color-scheme: dark)` in `assets/css/frontend.css`.