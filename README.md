# Internal Announcements

A WordPress plugin for company intranet announcements / news feeds.

## Features

- **Custom post type** `announcement` with full WordPress admin UI and Gutenberg editor.
- **Categories** — built-in `announcement_category` taxonomy, pre-seeded with **HR**, **IT**, and **General**.
- **Pinning** — pin any announcement to the top of the feed via a sidebar checkbox; pinned posts always appear first, regardless of date.
- **"New" badge** — posts published within the last 7 days (configurable) are automatically labelled **New**; no database tracking required.
- **Shortcode** `[announcements]` — drop it on any page to render the feed.
- **Lean asset loading** — CSS is only enqueued on pages that contain the shortcode.

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

| Attribute  | Default | Description                                                           |
|------------|---------|-----------------------------------------------------------------------|
| `limit`    | `10`    | Maximum number of non-pinned posts to display.                        |
| `category` | _(all)_ | Filter by category slug, e.g. `hr`, `it`, `general`.                 |
| `new_days` | `7`     | Posts published within this many days receive a **New** badge. Set to `0` to disable. |

Examples:

```
[announcements limit="5"]
[announcements category="hr"]
[announcements limit="20" category="it"]
[announcements new_days="14"]
[announcements new_days="0"]
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

This plugin creates **no custom database tables**. All data is stored in standard WordPress tables (`wp_posts`, `wp_postmeta`, `wp_terms`).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
