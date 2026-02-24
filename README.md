# Internal Announcements

A WordPress plugin for company intranet announcements / news feeds.

## Features

- **Custom post type** `announcement` with full WordPress admin UI and Gutenberg editor.
- **Categories** — built-in `announcement_category` taxonomy, pre-seeded with **HR**, **IT**, and **General**.
- **Pinning** — pin any announcement to the top of the feed via a sidebar checkbox; pinned posts always appear first, regardless of date.
- **Per-user read tracking** — stored in a dedicated `wp_announcement_reads` table; a single bulk query prevents N+1 on every page load.
- **Shortcode** `[announcements]` — drop it on any page to render the feed.
- **Security** — every AJAX call is protected by both a WP nonce (`check_ajax_referer`) and a logged-in check.
- **Lean asset loading** — CSS and JS are only enqueued on pages that contain the shortcode.

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress   | 6.0+    |
| PHP         | 8.0+    |

## Installation

1. Upload the `internal-announcements` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins**.
3. Default categories (General, HR, IT) are created automatically on activation.

## Usage

### Shortcode

```
[announcements]
```

Optional attributes:

| Attribute  | Default | Description                                         |
|------------|---------|-----------------------------------------------------|
| `limit`    | `10`    | Maximum number of non-pinned posts to display.      |
| `category` | _(all)_ | Filter by category slug, e.g. `hr`, `it`, `general`.|

Examples:

```
[announcements limit="5"]
[announcements category="hr"]
[announcements limit="20" category="it"]
```

### Publishing an Announcement

1. Go to **Announcements → Add New** in the WordPress admin.
2. Write your title and body using the block editor.
3. Assign one or more **Announcement Categories** in the right-hand panel.
4. To pin the announcement, check **Pin to top of feed** in the **Announcement Options** meta box.
5. Publish.

### Managing Categories

Go to **Announcements → Categories** to add, edit, or remove categories.

## Database

One custom table is created on activation:

```sql
wp_announcement_reads (
    id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_post (user_id, post_id)
)
```

> **Note:** The table is **not dropped on deactivation** — read history is preserved. To fully remove the data, delete the table manually after uninstalling the plugin.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
