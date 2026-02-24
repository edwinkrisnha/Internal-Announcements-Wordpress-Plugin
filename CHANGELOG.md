# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [1.4.0] — 2026-02-24

### Added

- **CSS-only category filter tabs** — when the feed contains 2 or more categories, pill-shaped tab buttons appear above the list. Selecting a category hides non-matching cards instantly with no JavaScript or extra DB queries. Implemented via hidden `<input type="radio">` elements and CSS general sibling combinators. Per-category CSS rules are generated inline by PHP from the terms present in the current feed.
- **Author attribution** — optional display of the post author's name and Gravatar avatar at the bottom of each card. Controlled by a new **Show author** checkbox in **Announcements → Settings**. Avatar size is 24 px. No extra DB queries (WP caches user data per request).
- **`show_author` setting** — new boolean in plugin options, default `false`.

### Changed

- **`Announcement_Settings::get()` is now cached** — a `private static array $cache` property stores the merged settings for the duration of each request. `get_option()` is called at most once per request instead of once per `get()` invocation. Cache is cleared at the start of `sanitize()` so a settings save within the same request sees fresh data.
- **Expiry meta clause extracted to `Announcement_Settings::build_expiry_meta_clause()`** — the shared meta_query array that excludes expired posts is now a single static method, removing the duplication between the pinned and non-pinned queries in the shortcode. Any future query for announcements can call the same helper.
- **Pre-pass term collection in template** — `get_the_terms()` is now called once per post in a pre-loop pass before rendering, and results are stored in `$post_term_map`. The render loop reads from that map instead of issuing a second `get_the_terms()` call per card.

---

## [1.3.0] — 2026-02-24

### Added

- **Expiry date** meta field in the **Announcement Options** sidebar box.
  - Input type `date`, stored as `Y-m-d` in `_expiry_date` post meta.
  - Date is validated with `DateTimeImmutable::createFromFormat` before saving.
  - Expired posts are excluded from the feed via a nested `meta_query` (OR: key NOT EXISTS / key is blank / key >= today). Post stays published — no content is deleted.
  - Admin list table **Expires** column shows the expiry date; expired rows display in red with an "Expired" label. Column is sortable.
- **Layout setting** — choose between **List** (single column), **Grid — 2 columns**, or **Grid — 3 columns** in **Announcements → Settings**.
  - Stored as `layout` in plugin options (`list` | `grid-2` | `grid-3`).
  - Applied as a modifier class (`ia-layout--list`, `ia-layout--grid-2`, `ia-layout--grid-3`) on the feed container.
  - Grid collapses to 2 columns below 1024 px and to 1 column below 768 px.
  - `layout` shortcode attribute overrides the setting per placement.

### Changed

- Container `max-width` raised from `800px` to `1200px` to accommodate grid layouts; list layout retains its own `800px` constraint via `.ia-layout--list`.
- Admin meta box nonce key renamed from `ia_pin_meta_box` / `ia_pin_nonce` to `ia_announcement_meta_box` / `ia_meta_nonce` to reflect the expanded scope of the meta box.

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
