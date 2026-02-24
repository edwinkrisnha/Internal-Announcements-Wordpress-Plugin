# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [1.2.0] — 2026-02-24

### Added

- **Settings page** under **Announcements → Settings** (WP Settings API).
  - **Display mode**: choose between "Fixed number" (max N posts) or "Last X days" (all posts in a date window).
  - **"New" badge duration**: control the badge threshold globally without editing shortcodes.
  - **Category color preview**: the settings page shows each category with its auto-assigned color swatch.
- **Automatic category colors**: each `announcement_category` term is assigned a distinct color from an 8-color palette, derived from its term ID. Colors are output as inline styles — no extra CSS or database storage required.
- **`mode` and `days` shortcode attributes**: shortcode attributes now override saved settings per placement; `mode="fixed"` or `mode="days"` selects the display mode, `days="30"` sets the date window.
- Date-based display query (`date_query`) for the "last X days" feed mode.

### Changed

- Shortcode defaults now read from **Announcements → Settings** instead of hardcoded values; explicit shortcode attributes still override them.

---

## [1.1.0] — 2026-02-24

### Changed

- Replaced per-user read tracking (custom DB table + AJAX) with a date-based **"New" badge**.
  Posts published within the last `new_days` days (default: 7) are automatically labelled **New** — no database writes, no JavaScript required.
- Removed `wp_announcement_reads` table, `Announcement_Read_Tracker` class, `Announcement_Ajax` class, and the mark-as-read JS.
- Added `new_days` shortcode attribute to control the badge threshold (set to `0` to disable).
- CSS: removed read/unread state styles; added `.ia-new-badge` styles.
- Plugin no longer loads any JavaScript on the frontend.

---

## [1.0.0] — 2026-02-24

### Added

- Custom post type `announcement` with Gutenberg editor support and full WordPress admin UI.
- Custom taxonomy `announcement_category`, pre-seeded with three default terms: **General**, **HR**, **IT**.
- Per-user read tracking via a dedicated `wp_announcement_reads` database table (created with `dbDelta` on activation).
- Bulk read-ID fetch (`get_read_post_ids`) to eliminate N+1 queries in the feed loop.
- Post meta `_is_pinned` — pinned announcements always sort above non-pinned ones.
- `[announcements]` shortcode with `limit` and `category` attributes.
- Two-query feed strategy (pinned query + recent query merged in PHP) for reliable sort ordering.
- CSRF-protected AJAX endpoint `ia_mark_read` (`check_ajax_referer` + `is_user_logged_in`).
- Vanilla-JS mark-as-read with event delegation; updates card state and unread summary without page reload.
- Admin list table **Pinned** column with sortable support.
- **Announcement Options** meta box (side, high priority) with pin checkbox.
- Conditional CSS/JS asset loading via `has_shortcode()` — assets only load on pages using the shortcode.
