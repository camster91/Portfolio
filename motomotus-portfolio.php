<?php
/**
 * Plugin Name: Motomotus Portfolio
 * Description: A robust, accessible portfolio system for managing and presenting Motomotus work.
 * Version: 1.4.7
 * Author: Motomotus
 * Text Domain: motomotus
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

// Define constants
if ( ! defined( 'MOTOMOTUS_VERSION' ) ) {
    define( 'MOTOMOTUS_VERSION', '1.4.7' );
}
if ( ! defined( 'MOTOMOTUS_PATH' ) ) {
    define( 'MOTOMOTUS_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MOTOMOTUS_URL' ) ) {
    define( 'MOTOMOTUS_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load Dependencies
 */
require_once MOTOMOTUS_PATH . 'includes/post-type.php';
require_once MOTOMOTUS_PATH . 'includes/admin.php';
require_once MOTOMOTUS_PATH . 'includes/shortcode.php';
if ( file_exists( MOTOMOTUS_PATH . 'includes/sort.php' ) ) {
    require_once MOTOMOTUS_PATH . 'includes/sort.php';
}

/**
 * Plugin Activation logic
 */
register_activation_hook( __FILE__, 'motomotus_plugin_activate' );
function motomotus_plugin_activate() {
    motomotus_register_portfolio_post_type();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'motomotus_plugin_deactivate' );
function motomotus_plugin_deactivate() {
    flush_rewrite_rules();
}

/**
 * Global Init Tasks
 */
add_action( 'init', 'motomotus_global_init' );
function motomotus_global_init() {
    add_image_size( 'motomotus-thumb', 800, 450, true );
}

/**
 * Global Fixes Enqueue — only when on pages that need them.
 * Also enqueues the main grid/mod styles when [motomotus_work] shortcode is used
 * (because wp_enqueue_style inside a shortcode doesn't work in <head>).
 */
add_action( 'wp_enqueue_scripts', 'motomotus_global_fixes_enqueue' );

/**
 * Return the classic and Elementor document source for the current page.
 */
function motomotus_get_current_page_source() {
    if ( ! is_singular( 'page' ) ) {
        return '';
    }

    $page_id = get_queried_object_id();
    $post    = $page_id ? get_post( $page_id ) : null;
    $source  = $post ? (string) $post->post_content : '';
    $source .= "\n" . str_replace( '\\"', '"', (string) get_post_meta( $page_id, '_elementor_data', true ) );

    return $source;
}

/**
 * Identify the reusable Colour frame without tying future artist pages to IDs.
 */
function motomotus_is_colour_context() {
    if ( ! is_singular( 'page' ) ) {
        return false;
    }

    // Existing private Colour landing page; retained as a migration bridge.
    if ( 3632 === (int) get_queried_object_id() ) {
        return true;
    }

    $source = motomotus_get_current_page_source();
    if ( has_shortcode( $source, 'motomotus_colour_landing' ) ) {
        return true;
    }

    return (bool) preg_match( '/\[motomotus_work\b[^\]]*(?:\bvariant\s*=\s*["\']?artist\b|\bartist\s*=)/i', $source );
}

add_filter( 'body_class', 'motomotus_colour_body_class' );
function motomotus_colour_body_class( $classes ) {
    if ( motomotus_is_colour_context() ) {
        $classes[] = 'motomotus-colour-context';
    }

    return $classes;
}

function motomotus_global_fixes_enqueue() {
    $load_fixes      = false;
    $load_portfolio  = false;

    // Always load on home/front page (background video + wordmark animation)
    if ( is_front_page() || is_home() ) {
        $load_fixes = true;
    }

    // Load on pages using the motomotus shortcode or on Info/Contact pages.
    // The Colour landing page and Chuck artist page also receive the approved
    // shared Motomotus/ARKETYPE header treatment.
    if ( is_singular( 'page' ) ) {
        $post = get_post();
        if ( $post && ( has_shortcode( $post->post_content, 'motomotus_work' ) || has_shortcode( $post->post_content, 'motomotus_intro' ) || has_shortcode( $post->post_content, 'motomotus_colour_landing' ) || stripos( $post->post_content, '158 Sterling' ) !== false ) ) {
            $load_fixes     = true;
            $load_portfolio = has_shortcode( $post->post_content, 'motomotus_work' );
        }
        // Info (ID 9) and Contact pages
        if ( in_array( get_the_ID(), array( 9, 3632, 3634 ), true ) ) {
            $load_fixes = true;
        }
        if ( motomotus_is_colour_context() ) {
            $load_fixes = true;
        }
    }

    // Elementor-built pages: check for shortcode in Elementor meta
    if ( is_singular( 'page' ) ) {
        $elementor_data = get_post_meta( get_the_ID(), '_elementor_data', true );
        if ( $elementor_data && ( stripos( $elementor_data, 'motomotus_work' ) !== false || stripos( $elementor_data, 'motomotus_intro' ) !== false || stripos( $elementor_data, 'motomotus_colour_landing' ) !== false ) ) {
            $load_fixes     = true;
            $load_portfolio = $load_portfolio || stripos( $elementor_data, 'motomotus_work' ) !== false;
        }
    }

    // Enqueue global fixes
    if ( $load_fixes ) {
        wp_enqueue_style( 'motomotus-global-fixes', MOTOMOTUS_URL . 'assets/css/global-fixes.css', array(), MOTOMOTUS_VERSION );
        wp_enqueue_script( 'motomotus-global-fixes', MOTOMOTUS_URL . 'assets/js/global-fixes.js', array(), MOTOMOTUS_VERSION, true );
        wp_localize_script(
            'motomotus-global-fixes',
            'motomotusSiteConfig',
            array(
                'arketypeLogoUrl' => MOTOMOTUS_URL . 'assets/images/arketype-logo.png',
            )
        );
    }

    // Enqueue main portfolio assets (grid, modal, GSAP) — must be in <head>
    if ( $load_portfolio ) {
        wp_enqueue_style( 'motomotus-style', MOTOMOTUS_URL . 'assets/css/style.css', array(), MOTOMOTUS_VERSION );
        wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
        // GSAP is optional enhancement only. The core modal and
        // filtering interactions must still run if the CDN is unavailable.
        wp_enqueue_script( 'motomotus-script', MOTOMOTUS_URL . 'assets/js/main.js', array(), MOTOMOTUS_VERSION, true );
    }

}

/**
 * Register Assets (Do not enqueue globally — used as fallback)
 */
add_action( 'wp_enqueue_scripts', 'motomotus_register_frontend_assets' );
function motomotus_register_frontend_assets() {
        wp_register_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
        wp_register_script( 'gsap-scroll-trigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array( 'gsap' ), '3.12.2', true );

        wp_register_style( 'motomotus-style', MOTOMOTUS_URL . 'assets/css/style.css', array(), MOTOMOTUS_VERSION );
        wp_register_script( 'motomotus-script', MOTOMOTUS_URL . 'assets/js/main.js', array(), MOTOMOTUS_VERSION, true );
}

/* =========================================================
   SEO — Merged from motomotus-seo-fix
   ========================================================= */

// 1. Override homepage and managed artist-page meta descriptions.
add_action( 'wp_head', 'motomotus_seo_meta_desc', 1 );
function motomotus_seo_meta_desc() {
    if ( is_front_page() || is_home() ) {
        echo '<meta name="description" content="VFX & Finishing" />' . "\n";
        return;
    }

    $artist_page = motomotus_get_artist_page_seo_context();
    if ( $artist_page ) {
        $title       = $artist_page['title'] . ' | MOTOMOTUS';
        $description = sprintf( '%s — selected Colour work by MOTOMOTUS.', $artist_page['title'] );
        $url         = get_permalink( $artist_page['page_id'] );

        echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
    }
}

/**
 * Return artist-page metadata when a page embeds an artist grid.
 *
 * The shortcode can live in classic page content or Elementor's serialized
 * document data. Keeping this detection content-based makes the SEO behavior
 * reusable for future artist pages instead of tying it to a numeric page ID.
 */
function motomotus_get_artist_page_seo_context() {
    if ( ! is_singular( 'page' ) ) {
        return false;
    }

    $page_id = get_queried_object_id();
    if ( ! $page_id ) {
        return false;
    }

    $page    = get_post( $page_id );
    $content = $page ? (string) $page->post_content : '';
    $content .= "\n" . str_replace( '\\\"', '"', (string) get_post_meta( $page_id, '_elementor_data', true ) );

    if ( ! preg_match( '/\\[motomotus_work\\b([^\\]]*)\\]/i', $content, $match ) ) {
        return false;
    }

    $atts   = shortcode_parse_atts( $match[1] );
    $artist = isset( $atts['artist'] ) ? sanitize_title( $atts['artist'] ) : '';
    if ( ! $artist ) {
        return false;
    }

    $title = isset( $atts['heading'] ) ? sanitize_text_field( $atts['heading'] ) : '';
    if ( ! $title ) {
        $term = get_term_by( 'slug', $artist, 'portfolio_artist' );
        $title = $term && ! is_wp_error( $term ) ? $term->name : $artist;
    }

    return array(
        'page_id' => (int) $page_id,
        'title'   => $title,
    );
}

// 2. Override homepage title tag
add_filter( 'pre_get_document_title', 'motomotus_seo_homepage_title', 20 );
function motomotus_seo_homepage_title( $title ) {
    if ( is_front_page() || is_home() ) {
        return 'MOTOMOTUS — VFX & Finishing';
    }

    $artist_page = motomotus_get_artist_page_seo_context();
    if ( $artist_page ) {
        return $artist_page['title'] . ' | MOTOMOTUS';
    }

    return $title;
}

// 3. Override Jetpack Open Graph description (if Jetpack is active)
add_filter( 'jetpack_open_graph_tags', 'motomotus_seo_jetpack_og_desc', 20 );
function motomotus_seo_jetpack_og_desc( $tags ) {
    if ( is_front_page() || is_home() ) {
        $tags['og:description'] = 'VFX & Finishing';
    }

    $artist_page = motomotus_get_artist_page_seo_context();
    if ( $artist_page ) {
        $tags['og:title']       = $artist_page['title'] . ' | MOTOMOTUS';
        $tags['og:description'] = sprintf( '%s — selected Colour work by MOTOMOTUS.', $artist_page['title'] );
        $tags['og:url']         = get_permalink( $artist_page['page_id'] );
    }

    return $tags;
}
