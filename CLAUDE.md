# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Collaboration Style

This plugin is a **learning project**. Prefer teaching over doing: explain concepts, point to relevant documentation, and ask before making large code changes. Only write code when the user explicitly asks for it or when fixing a specific, small bug they've identified.

## Plugin Overview

WP Public Revisions is a WordPress plugin that lets editors snapshot post content as named revisions stored in a custom DB table. Front-end visitors can browse those snapshots via the `[revision_history]` shortcode and read any past version.

## Development Setup

This plugin runs inside a local WordPress install managed by [LocalWP](https://localwp.com/).

**Composer** (autoloading only — no runtime dependencies):
```bash
composer install          # first-time setup
composer dump-autoload    # after adding new classes
```

There is no build step, no test suite, and no bundler. PHP files are served directly.

## Architecture

### Entry Point & Bootstrap

`wp-public-revisions.php` is the plugin header file. It:
1. Defines three constants: `WPR_SLUG`, `WPR_ROOT_PATH` (filesystem path), `WPR_FRONTEND_PATH` (URL path).
2. Requires `vendor/autoload.php` behind a `class_exists` guard.
3. Calls `\WpPublicRevisions\Initialize::init()` (static).
4. Registers the activation hook pointing to `\WpPublicRevisions\Core\Installer::install()`.

### Namespace & Autoloading

Composer maps `WpPublicRevisions\` → `src/`. All class files live under `src/`.

| File | Class | Purpose |
|------|-------|---------|
| `src/initialize.php` | `WpPublicRevisions\Initialize` | Bootstrap — wires up all feature classes |
| `src/Core/Installer.php` | `WpPublicRevisions\Core\Installer` | Activation hook: creates/migrates DB table, seeds default options |
| `src/Core/RevisionStore.php` | `WpPublicRevisions\Core\RevisionStore` | All DB reads/writes for the `wppr_revisions` table |
| `src/Lib/Diff.php` | `WpPublicRevisions\Lib\Diff` | gzcompress/gzuncompress wrappers (diff library imports are unused dead code) |
| `src/Admin/Metabox.php` | `WpPublicRevisions\Admin\Metabox` | Meta box UI + AJAX handlers for create/fetch/delete revisions |
| `src/Admin/Options.php` | `WpPublicRevisions\Admin\Options` | Settings page under Tools → WP Public Revisions; post-type checkboxes |
| `src/Frontend/Shortcode.php` | `WpPublicRevisions\Frontend\Shortcode` | `[revision_history]` shortcode + asset enqueueing |
| `src/Frontend/Viewer.php` | `WpPublicRevisions\Frontend\Viewer` | `the_content` filter — swaps content for a past revision on `?wppr_view_revision=ID` |

> **File casing note**: Files committed under `src/admin/` and `src/frontend/` (lowercase) but PSR-4 maps to `src/Admin/` and `src/Frontend/` (capital first letter). Works on macOS (case-insensitive FS) but will break on Linux servers.

### Custom DB Table

Table: `{prefix}wppr_revisions`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | auto-increment |
| `post_id` | bigint | parent post |
| `author` | bigint | user ID |
| `rev_no` | tinyint | sequential per post |
| `label` | varchar(255) | human-readable name |
| `content` | mediumblob | gzcompressed post content |
| `timestamp` | datetime | defaults to CURRENT_TIMESTAMP |

### Options

| Option key | Format | Purpose |
|------------|--------|---------|
| `wppr_db_version` | string | tracks schema version for migrations |
| `wppr_post_type_option` | `['post' => 1, 'page' => 1, ...]` | which post types show the meta box and shortcode |

`wppr_post_type_option` is seeded on activation via `add_option()` (no-op if already exists). It is sanitized on save via `sanitize_wppr_post_type_option()` which validates each key against `get_post_types()`.

### Request Flow

**Admin meta box** (post editor sidebar):
- `Metabox::wppr_add_meta_box()` reads `wppr_post_type_option` and registers the box only for enabled post types.
- Three AJAX actions handle create/fetch/delete: `wppr_create_revision`, `wppr_fetch_revisions`, `wppr_delete_revision`. All are nonce- and capability-checked.
- JS lives in `assets/js/admin-metabox.js`; styles in `assets/css/frontend-admin.css`.

**Shortcode list view** (`[revision_history]` in post content):
- `Shortcode::wppr_revision_history_shortcode()` fetches revisions via `RevisionStore::fetch_revisions()` and renders an `<ol>` of links.
- Each link points to `?wppr_view_revision={id}` on the same post permalink.

**Revision viewer** (`?wppr_view_revision=ID`):
- `Viewer::wppr_maybe_show_revision()` hooks onto `the_content` (priority 3).
- Fetches the revision via `RevisionStore::fetch_revision()`, decompresses content, and replaces `$content` with the historical content plus a notice banner.

### Known Issues

- **`viewer.php`**: `get_the_date()` is called with `$revision->timestamp` (a string) as the second argument — it expects a post ID or WP_Post. Should use `wp_date()` with `strtotime()`.
- **`Diff.php`**: Six `use Jfcherng\Diff\*` imports are unused dead code; the library is not in composer.json.
- **`RevisionStore::fetch_revision()`**: Return type declared as `Object` (capital O); should be `object|WP_Error`.

## CSS Classes (for theming)

Front-end output uses these classes, which can be overridden in a theme stylesheet:

- `.wppr-revision-list` — outer wrapper for the shortcode
- `.wppr-history-box` — collapsible list container (hidden by default)
- `.wppr-item`, `.wppr-link`, `.wppr-label`, `.wppr-author` — list items
- `.wppr-revision-notice` — banner shown on historical revision views
- `.wppr-revision-content` — wrapper for the historical post content
- `.wppr-no-revisions` — shown when there are no revisions

Dark mode overrides are handled via `@media (prefers-color-scheme: dark)` in `assets/css/frontend.css`.
