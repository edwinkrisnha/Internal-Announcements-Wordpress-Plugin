# Internal Announcements

A WordPress plugin for company intranet announcements / news feeds.

## Features

- **Custom post type** `announcement` with full WordPress admin UI and Gutenberg editor.
- **Categories** — built-in `announcement_category` taxonomy, pre-seeded with **HR**, **IT**, and **General**.
- **Pinning** — pin any announcement to the top of the feed via a sidebar checkbox; pinned posts always appear first, regardless of date.
- **"New" badge** — posts published within the last N days (configurable) are automatically labelled **New**; no database tracking required.
- **Category colors** — each category is automatically assigned a distinct color from a built-in palette; consistent and automatic, no configuration needed.
- **Settings page** — control feed display (fixed count or last X days) and badge duration from **Announcements → Settings**.
- **Shortcode** `[announcements]` — drop it on any page to render the feed; shortcode attributes override settings per placement.
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

## Settings

Go to **Announcements → Settings** to configure:

| Setting | Default | Description |
|---------|---------|-------------|
| Display mode | Fixed | Show a fixed number of announcements, or all from the last X days. |
| Number to show | `10` | Max non-pinned posts (fixed mode only). Pinned posts are always shown. |
| Days to look back | `30` | Date range for "last X days" mode. |
| "New" badge duration | `7` | Posts newer than this many days show a **New** badge. `0` disables it. |

## Usage

### Shortcode

```
[announcements]
```

Shortcode attributes are optional — they override the saved settings for that specific placement only.

| Attribute  | Default          | Description |
|------------|------------------|-------------|
| `mode`     | _(from settings)_ | `fixed` or `days` — overrides the display mode. |
| `limit`    | _(from settings)_ | Max non-pinned posts (used when mode = `fixed`). |
| `days`     | _(from settings)_ | How many days back to show posts (used when mode = `days`). |
| `new_days` | _(from settings)_ | "New" badge duration in days. Set to `0` to disable. |
| `category` | _(all)_           | Filter by taxonomy slug, e.g. `hr`, `it`, `general`. |

Examples:

```
[announcements]
[announcements limit="5"]
[announcements mode="days" days="14"]
[announcements category="hr" limit="5"]
[announcements new_days="0"]
```

### Publishing an Announcement

1. Go to **Announcements → Add New** in the WordPress admin.
2. Write your title and body using the block editor.
3. Assign one or more **Announcement Categories** in the right-hand panel.
4. To pin the announcement, check **Pin to top of feed** in the **Announcement Options** meta box.
5. Publish.

### Managing Categories

Go to **Announcements → Categories** to add, edit, or remove categories. Colors are assigned automatically — the **Settings** page shows a preview of each category's color.

## Database

This plugin creates **no custom database tables**. All data is stored in standard WordPress tables (`wp_posts`, `wp_postmeta`, `wp_terms`).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
