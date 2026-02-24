# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
