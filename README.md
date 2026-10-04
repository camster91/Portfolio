# Motomotus Portfolio

A custom WordPress plugin that manages and presents video portfolio work for Motomotus, a VFX and finishing studio.

## What it does

Editors add projects (title, thumbnail, video link and credits) in the WordPress admin, group them into collections and artists, and set their display order by drag and drop. A shortcode renders those projects as a responsive grid where each card opens an accessible video dialog with the project credits. The plugin keeps project content in WordPress, not in a page builder, so layouts stay consistent and content survives theme or plugin changes.

It was built as a client plugin for one site (it is not a WordPress.org listing), and it is designed for non-destructive upgrades: shortcode names, the `portfolio` post type and its meta keys stay stable between versions, and uninstalling the plugin never deletes portfolio content.

## Key features

- **Portfolio content model:** registers a `portfolio` post type only if the site does not already provide one (so it works alongside an existing Secure Custom Fields registration), plus two taxonomies: Collections (for example VFX and Colour) and Artists.
- **Structured project fields:** a "Project Details" meta box for grid subtitle, main video URL, and client, agency, director, editor and VFX/finishing credits. Fields are registered with `register_post_meta`, exposed to the REST API, sanitized on save, and protected by nonce and capability checks.
- **Admin workflow:** an Overview screen with published/draft counts, shortcuts to add and manage projects, collections and artists, and an editor checklist. The project list gains Thumbnail and Video status columns.
- **Drag-and-drop ordering:** a Sort Order screen (jQuery UI Sortable) saves `menu_order` over admin-ajax, scoped by status, collection and artist, and limited to users who can edit others' posts.
- **Responsive grid:** 1, 2 or 3 columns with CSS Grid, plus an "artist" variant for single-artist pages.
- **Accessible video dialog:** uses native `<dialog>` with a fallback, restores focus on close, closes on Escape or overlay click, and plays YouTube, Vimeo or direct MP4 links.
- **Optional filters:** post-tag filter buttons that update `aria-pressed` and filter cards without a page reload.
- **Safe captions:** credits (and a legacy `portfolio_text` field) are rendered as text, not trusted HTML.
- **Progressive enhancement:** GSAP (loaded from a CDN) only adds entrance animation and is skipped for `prefers-reduced-motion`. The grid, dialog and filters work without it.
- **Draft previews:** on an authenticated WordPress preview, editors see draft projects in the grid. Public pages only query published projects.
- **SEO touches:** sets the homepage title and description, and builds title, description and Open Graph tags for artist pages from the shortcode attributes (including Jetpack Open Graph output). Colour projects are kept out of the XML sitemap and their empty single pages return 404.
- **Elementor aware:** detects shortcodes inside Elementor page data to load assets, and shows a placeholder in the Elementor editor instead of the live grid.

## Shortcodes

`[motomotus_work]` renders the portfolio grid. Attributes:

| Attribute | Default | Purpose |
|---|---|---|
| `posts_per_page` | `-1` (all) | Number of projects to show (1 to 100) |
| `category` | empty | Limit to a post-tag slug |
| `collection` | empty | Limit to a Collection slug, for example `vfx` or `colour` |
| `artist` | empty | Limit to an Artist slug |
| `heading` | empty | Visible page heading |
| `variant` | `work` | `work` or `artist` layout |
| `filters` | `hide` | `show` to display post-tag filter buttons |

Examples:

```text
[motomotus_work collection="vfx"]
[motomotus_work collection="colour" artist="artist-slug" heading="ARTIST NAME" variant="artist" posts_per_page="6"]
```

`[motomotus_colour_landing]` renders a two-artist landing page with configurable labels and URLs (`heading`, `chuck_label`, `chuck_url`, `beatrice_label`, `beatrice_url`, `beatrice_page_id`). When the second artist page is still a draft, editors previewing the landing page are linked to its preview URL.

## Tech stack

- PHP (WordPress plugin APIs: custom post types, taxonomies, post meta, shortcodes, admin pages, admin-ajax)
- Vanilla JavaScript for the grid, dialog and filters; jQuery UI Sortable for the admin ordering screen
- CSS Grid
- GSAP 3.12 (optional, CDN)
- GitHub Actions for CI

## Installation

1. Build the plugin zip (see below), or zip the plugin files inside a folder named `motomotus-portfolio`. Keep that folder name so upgrades replace the existing install.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**, choose the zip and click **Install Now**.
3. Activate the plugin. Projects live under **Portfolios** in the admin menu.

## Building a release

```bash
bash scripts/build-plugin.sh
```

This writes `dist/motomotus-portfolio-<version>.zip` containing only `assets/`, `includes/`, `motomotus-portfolio.php`, `uninstall.php`, `README.md` and `LICENSE`. Design proofs and test files are left out.

## Testing

The tests are plain PHP contract tests with stubbed WordPress functions, so no WordPress install is needed:

```bash
php tests/post-type-test.php
php tests/sort-test.php
php tests/artist-shortcode-test.php
php tests/colour-navigation-test.php
```

CI (`.github/workflows/ci.yml`) runs on every push and pull request: PHP 8.2 syntax checks, `node --check` on the JavaScript, the four contract tests, a release build, and a check that the zip contains no proof files or raw video.

## Project structure

```text
motomotus-portfolio.php   Plugin bootstrap, asset loading, SEO hooks
includes/
  post-type.php           Post type, taxonomies, meta fields, meta box, list columns
  admin.php               Overview admin screen
  shortcode.php           [motomotus_work] and [motomotus_colour_landing]
  sort.php                Drag-and-drop Sort Order screen and ajax handlers
assets/                   Front-end and admin CSS, JS and brand images
scripts/build-plugin.sh   Release zip builder
tests/                    PHP contract tests and fixtures
proof/                    Static layout proof (not shipped in the plugin zip)
uninstall.php             Intentionally keeps all portfolio content
```

## Notes

- Project meta is stored under plain keys (`subtitle`, `video_link`, `client`, `agency`, `director`, `editor`, `vfx_finishing`) on the `portfolio` post type.
- Removing the plugin leaves portfolio posts, terms and metadata in place by design.
- Some front-end code (`assets/css/global-fixes.css`, `assets/js/global-fixes.js`, `assets/js/info-animation.js`) is specific to the Motomotus site's header, navigation and Info page rather than the portfolio grid itself.

## License

MIT, see [LICENSE](LICENSE).
