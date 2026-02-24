# Internal Announcements

A WordPress plugin for company intranet announcements / news feeds.

## Features

- **Custom post type** `announcement` with full WordPress admin UI and Gutenberg editor.
- **Categories** — built-in `announcement_category` taxonomy, pre-seeded with **HR**, **IT**, and **General**.
- **Pinning** — pin any announcement to the top of the feed via a sidebar checkbox; pinned posts always appear first, regardless of date.
- **"New" badge** — posts published within the last N days (configurable) are automatically labelled **New**; no database tracking required.
- **Category colors** — each category is automatically assigned a distinct color from a built-in palette; consistent and automatic, no configuration needed.
- **Category filter tabs** — when 2+ categories appear in the feed, pill-shaped tabs let readers filter by category instantly with no JavaScript or extra queries (CSS-only via radio buttons).
- **Author attribution** — optionally show the post author's name and avatar on each card (toggleable in Settings).
- **Expiry date** — optional per-post expiry date; expired announcements are automatically hidden from the feed but remain accessible in the admin.
- **Flexible layout** — display the feed as a single-column list, 2-column grid, or 3-column grid; configurable globally in settings or per shortcode placement.
- **Settings page** — configure display mode, post count, date range, badge duration, and layout from **Announcements → Settings**.
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
| Layout | List | Single-column list, 2-column grid, or 3-column grid. |
| Show author | Off | Display the post author's name and avatar on each card. |
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
| `layout`   | _(from settings)_ | `list`, `grid-2`, or `grid-3`. |
| `new_days` | _(from settings)_ | "New" badge duration in days. Set to `0` to disable. |
| `category` | _(all)_           | Filter by taxonomy slug, e.g. `hr`, `it`, `general`. |

Examples:

```
[announcements]
[announcements limit="5"]
[announcements mode="days" days="14"]
[announcements layout="grid-2"]
[announcements layout="grid-3" category="it"]
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

### Setting an Expiry Date

In the **Announcement Options** meta box (right sidebar when editing an announcement), use the **Expiry date** picker to set an optional end date. Once that date passes, the post is automatically excluded from the feed. It remains published and accessible in the admin — no data is deleted.

## Database

This plugin creates **no custom database tables**. All data is stored in standard WordPress tables (`wp_posts`, `wp_postmeta`, `wp_terms`).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
