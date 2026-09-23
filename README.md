# Motomotus Portfolio

A Motomotus WordPress portfolio system for premium project showcases. It provides a restrained, responsive grid, an accessible project-review modal, and a complete administration workflow without requiring a page builder to own the interaction.

The current Motomotus integration is the reference implementation. The stable `motomotus-portfolio` folder, `motomotus_work` shortcode, internal function names, and `_motomotus_` metadata keys are intentionally preserved so the product can be upgraded without moving existing content.

## Product principles

- **Quiet by default:** the visual system supports the work instead of competing with it.
- **Review-ready:** video, credits, keyboard navigation, focus management, and reduced-motion behavior are treated as one experience.
- **Portable core:** the portfolio renderer is reusable; site-specific navigation, contact, SEO, and page content remain outside the core grid contract.
- **Safe content rendering:** project credits are rendered as text, not trusted HTML.

## Key Features
- **Managed Portfolio Content:** Registers a safe fallback `portfolio` post type when the site does not already provide one, while remaining compatible with existing SCF registrations. Collection and Artist taxonomies keep VFX and Colour work reusable across pages.
- **Portfolio Admin:** An overview screen, add/manage shortcuts, structured project-detail fields, featured-image workflow, status counts, and drag-and-drop display ordering.
- **Dynamic Portfolio Grid:** A responsive 1/2/3-column grid driven by published `portfolio` posts, with a quiet static-card treatment.
- **Accessible Video Modal:** Uses a native `<dialog>` where supported, with a safe fallback, focus restoration, Escape/overlay close, and YouTube, Vimeo, or MP4 support.
- **Live Filtering:** Optional post-tag filters update `aria-pressed` state and hide filtered cards without a page reload.
- **Safe captions:** Structured credit fields and legacy `portfolio_text` are rendered as text nodes rather than injected HTML.
- **Graceful enhancement:** Core interactions do not depend on the GSAP CDN; GSAP only adds entrance animation when available.

## Installation
1. Zip the `motomotus-portfolio` folder. The folder name is retained for safe upgrades to existing installations.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**.
3. Select the zip file and click **Install Now**.
4. **Activate** the plugin.

## How to Use
### 1. Adding Work Items
Go to **Portfolios > Overview** and choose **Add Portfolio Project**:
- **Title:** The name of the project (e.g., ADIDAS).
- **Featured Image:** The static thumbnail displayed in the grid.
- **Agency/Client:** The single agency subtitle displayed beneath the client/project title on Colour artist grids.
- **Main Video:** The full project video URL (Vimeo/YouTube/MP4).
- **Credits:** Structured client, agency, director, editor, and VFX/finishing fields shown beneath the video.
- **Order (Page Attributes):** Use **Sort Order** to set the display position; the top of that list appears first in the grid.

### 2. Displaying the Grid
Place the following shortcode on any page or post:
`[motomotus_work]`

Optional attributes:
- `[motomotus_work filters="show"]` displays post-tag filter controls.
- `[motomotus_work category="vfx"]` scopes the grid to a post-tag slug.
- `[motomotus_work collection="vfx"]` scopes the grid to the VFX collection.
- `[motomotus_work collection="colour" artist="chuck" heading="CHUCK" variant="artist" posts_per_page="6"]` renders the supplied Colour artist-page treatment from Chuck's managed portfolio records.
- `[motomotus_work posts_per_page="6"]` limits the number of published items.

Colour page configuration:
- `[motomotus_colour_landing chuck_url="/chuck/" beatrice_url="/beatrice-tremblay/"]` renders the two approved artist links inside the shared Colour frame.
- `[motomotus_work collection="colour" artist="beatrice-tremblay" heading="BEATRICE TREMBLAY" variant="artist"]` creates the matching Beatrice grid when her portfolio records are available.

On an authenticated WordPress preview, the grid may include draft portfolio
records that the current user can edit. Public renders continue to query only
published records.

## Technical Details
- **Libraries:** [GSAP 3.12](https://greensock.com/gsap/) is an optional animation enhancement.
- **CSS:** Modern CSS Grid with responsive breakpoints (1, 2, and 3 columns).
- **Admin:** Portfolio items include an overview/add workflow, structured meta box, list completeness indicators, drag-and-drop Sort Order screen, and nonce/capability checks.
- **Compatibility:** The Motomotus-compatible shortcode, post type, metadata, and asset handles are preserved for non-destructive upgrades.
- **License:** Released under the MIT License.

## Developer Notes
- CSS is located in `assets/css/style.css`.
- JS logic is located in `assets/js/main.js`.
- Meta fields are prefixed with `_motomotus_` in the database.
- Site-specific homepage, navigation, contact, and SEO fixes remain isolated from the portfolio renderer.
- Artist pages use confirmed WordPress portfolio records and never package client media or one-off proof data inside the plugin.

## Release identity

**Motomotus Portfolio 1.4.9** is the product identity for the current release candidate. It adds the reusable VFX/Colour collection and artist content model required by the supplied page designs, the approved Colour artist captions, and the fixed MOTOMOTUS × ARKETYPE frame shared by the landing and artist pages. This package is for controlled Motomotus WordPress installations; it is not presented as a WordPress.org listing. The package contains code plus the supplied ARKETYPE brand asset and no client portfolio media. Removing or replacing the plugin preserves portfolio posts, terms, and metadata by design.

## Colour pages — September 2026 implementation contract

The private Colour experience consists of a landing page plus Chuck and Beatrice Tremblay artist pages. At an equivalent breakpoint, each uses the same header dimensions, horizontal padding, portfolio grid columns, gutters, media ratio, and grid top rhythm. The supplied MOTOMOTUS and ARKETYPE artwork renders at equal width with a small brand-font `x` centred between them. Colour must remain unpublished until the client announcement/release is separately approved.
