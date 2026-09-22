# Motomotus Portfolio Plugin

A high-performance portfolio showcase plugin for WordPress that replicates the minimalist aesthetic and smooth interactions of the Motomotus "Work" page.

## Key Features
- **Dynamic Portfolio Grid:** GSAP-powered responsive grid with entrance animations and magnetic hover effects.
- **Hover Video Previews:** Silently plays an MP4 preview when a user mouses over a project thumbnail.
- **Interactive Video Modal:** Opens a cinematic popup for YouTube, Vimeo, or MP4 videos with custom captions. Supports various URL formats (shorts, timestamps, etc.).
- **Live Filtering:** Instant, category-based filtering without page reloads.
- **Robust Architecture:** Graceful fallbacks if GSAP is missing, and thorough cleanup on uninstall.

## Installation
1. Zip the `motomotus-portfolio` folder.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**.
3. Select the zip file and click **Install Now**.
4. **Activate** the plugin.

## How to Use
### 1. Adding Work Items
Go to **Motomotus Work > Add New**:
- **Title:** The name of the project (e.g., ADIDAS).
- **Featured Image:** This is your static thumbnail.
- **Agency/Client:** The subtitle displayed in the grid.
- **Hover Video:** URL to a small MP4 file for the hover effect.
- **Main Video:** The full project video URL (Vimeo/YouTube/MP4).
- **Popup Caption:** Text that appears beneath the video in the modal. Supports basic HTML.
- **Order (Page Attributes):** Assign a number to control sorting (lower numbers appear first).

### 2. Displaying the Grid
Place the following shortcode on any page or post:
`[motomotus_work]`

## Technical Details
- **Libraries:** Powered by [GSAP 3.12](https://greensock.com/gsap/) for smooth animations.
- **CSS:** Modern CSS Grid with responsive breakpoints (1, 2, and 3 columns).
- **License:** Released under the MIT License.

## Developer Notes
- CSS is located in `assets/css/style.css`.
- JS logic is located in `assets/js/main.js`.
- Meta fields are prefixed with `_motomotus_` in the database.


## Colour Pages — September 2026 implementation contract

The Colour work is a three-page experience that must remain unpublished until the client announcement/release is approved:

1. **Colour landing page** — MOTOMOTUS × ARKETYPE lockup, VFX / COLOUR navigation, and two artist links: **CHUCK** and **BEATRICE TREMBLAY**.
2. **Chuck artist page** — Chuck's dedicated portfolio/video grid.
3. **Beatrice Tremblay artist page** — Beatrice's dedicated portfolio/video grid using the same underlying grid system as Chuck.

### Shared visual frame

The Colour landing page and both artist pages must feel like one fixed visual system. When navigating Colour → Chuck → Colour → Beatrice, the following elements must not visibly jump, resize, reflow, or change their X/Y position at the same viewport size:

- MOTOMOTUS × ARKETYPE lockup
- VFX / COLOUR navigation
- shared page margins and container width
- artist portfolio grid origin, columns, gutters, and top offset

The lockup is **two supplied logo assets**, with MOTOMOTUS above ARKETYPE and a small centred **×** between them. Do not approximate or recreate either logo with text. Preserve the supplied artwork's proportions and spacing.

Media must use reserved aspect-ratio containers so image/video loading does not move the grid. Header/logo/nav dimensions must be consistent across templates. Browser scrollbar appearance/disappearance must not create horizontal page-to-page movement.

### Browser/Codex-local finishing pass

Do the final alignment against the actual WordPress/Elementor pages in-browser. Before changing shared CSS, inspect computed dimensions and identify the real source of any movement (theme/Elementor container differences, page-specific margins/padding, header height, scrollbar width, font loading, image/video intrinsic sizing, or page-specific overrides).

Acceptance test at representative desktop, tablet, and mobile widths:

- Rapidly navigate between all three Colour pages.
- The logo lockup and navigation appear stationary.
- Artist grids share the same visual origin and geometry.
- Only the artist/project content changes.
- No layout shift occurs as fonts, images, or videos load.
- Existing VFX and Info pages are regression-tested after shared CSS changes.

Do not publish Colour pages or expose confidential Colour work as part of plugin development.
