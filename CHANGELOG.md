# Changelog — theme_citricityxund

All notable changes to the Citricity XUND theme are documented here.

## v2.0.0 — Moodle 5.2 support

Upgrade from Moodle 4.5 to **Moodle 5.2** (PHP 8.3+). Minimum supported Moodle version is now 5.2.

### Changed
- `version.php`: `requires` raised to `2026042000` (Moodle 5.2); `theme_boost` dependency raised to `2026042000`.
- Adopted the namespaced output API introduced in Moodle 5.0 (`\core\output\core_renderer`, `\core\output\html_writer`, `\core\url`, `\core\context\course`).
- `amd/src/expand-topics.js`: updated for the Moodle 5.x course index — section collapse elements are now keyed by section **id** (`#coursecontentcollapseid{id}` / `#collapsesectionid{id}`) rather than section number; the id is read from the course-index node's `data-id`. Added `aria-expanded` handling when expanding.
- Bootstrap 5 utility classes: `.no-gutters` → `.g-0` (My overview), `.border-left` → `.border-start` (navbar).

### Removed
- The deprecated `render_context_header()` renderer override (deprecated in core since 4.5). The theme now inherits core's `core/context_header.mustache` template; theme padding is preserved via SCSS.

### Added
- `templates/core/loginform.mustache`: `{{#maintenance}}`, `{{#info}}` and `{{#recaptcha}}` blocks so the login form supports maintenance/info messages and reCAPTCHA when enabled.
- A privacy provider (`null_provider`) — the theme stores no personal data.

### Internal
- Codebase brought up to the Moodle coding standard (`phpcs --standard=moodle` clean): PHPDoc added, language strings ordered, PHPUnit test modernised to attributes.
- Fixed a pre-existing PHPUnit failure in the course-image counting test (the test now runs as admin so `get_courses()` returns the created courses).

## v1.1.8 and earlier (Moodle 4.5)

Branded Boost child theme for Citricity XUND. See git history for prior changes.
