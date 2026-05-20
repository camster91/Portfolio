<?php
/**
 * Plugin Name: Motomotus Portfolio
 * Description: A high-performance portfolio showcase plugin that replicates the minimalist aesthetic and smooth interactions of the Motomotus "Work" page.
 * Version: 1.1.47
 * Author: Gemini CLI
 * Text Domain: motomotus
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

// Define constants
if ( ! defined( 'MOTOMOTUS_VERSION' ) ) {
    define( "MOTOMOTUS_VERSION", "1.1.47" );
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
require_once MOTOMOTUS_PATH . 'includes/shortcode.php';

/**
 * Plugin Activation logic
 */
register_activation_hook( __FILE__, 'motomotus_plugin_activate' );
function motomotus_plugin_activate() {
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
function motomotus_global_fixes_enqueue() {
    $load_fixes      = false;
    $load_portfolio  = false;

    // Always load on home/front page (background video + wordmark animation)
    if ( is_front_page() || is_home() ) {
        $load_fixes = true;
    }

    // Load on pages using the motomotus shortcode or on Info/Contact pages
    if ( is_singular( 'page' ) ) {
        $post = get_post();
        if ( $post && ( has_shortcode( $post->post_content, 'motomotus_work' ) || has_shortcode( $post->post_content, 'motomotus_intro' ) || stripos( $post->post_content, '158 Sterling' ) !== false ) ) {
            $load_fixes     = true;
            $load_portfolio = true;
        }
        // Info (ID 9) and Contact pages
        if ( in_array( get_the_ID(), array( 9 ), true ) ) {
            $load_fixes = true;
        }
    }

    // Elementor-built pages: check for shortcode in Elementor meta
    if ( ! $load_fixes && is_singular( 'page' ) ) {
        $elementor_data = get_post_meta( get_the_ID(), '_elementor_data', true );
        if ( $elementor_data && ( stripos( $elementor_data, 'motomotus_work' ) !== false || stripos( $elementor_data, 'motomotus_intro' ) !== false ) ) {
            $load_fixes     = true;
            $load_portfolio = true;
        }
    }

    // Enqueue global fixes
    if ( $load_fixes ) {
        wp_enqueue_style( 'motomotus-global-fixes', MOTOMOTUS_URL . 'assets/css/global-fixes.css', array(), MOTOMOTUS_VERSION );
        wp_enqueue_script( 'motomotus-global-fixes', MOTOMOTUS_URL . 'assets/js/global-fixes.js', array(), MOTOMOTUS_VERSION, true );
    }

    // Enqueue main portfolio assets (grid, modal, GSAP) — must be in <head>
    if ( $load_portfolio ) {
        wp_enqueue_style( 'motomotus-style', MOTOMOTUS_URL . 'assets/css/style.css', array(), MOTOMOTUS_VERSION );
        wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
        wp_enqueue_script( 'motomotus-script', MOTOMOTUS_URL . 'assets/js/main.js', array( 'gsap' ), MOTOMOTUS_VERSION, true );
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
        wp_register_script( 'motomotus-script', MOTOMOTUS_URL . 'assets/js/main.js', array( 'gsap' ), MOTOMOTUS_VERSION, true );
}

/* =========================================================
   SEO — Merged from motomotus-seo-fix
   ========================================================= */

// 1. Override homepage meta description
add_action( 'wp_head', 'motomotus_seo_meta_desc', 1 );
function motomotus_seo_meta_desc() {
    if ( is_front_page() || is_home() ) {
        echo '<meta name="description" content="VFX & Finishing" />' . "\n";
    }
}

// 2. Override homepage title tag
add_filter( 'pre_get_document_title', 'motomotus_seo_homepage_title', 20 );
function motomotus_seo_homepage_title( $title ) {
    if ( is_front_page() || is_home() ) {
        return 'MOTOMOTUS — VFX & Finishing';
    }
    return $title;
}

// 3. Override Jetpack Open Graph description (if Jetpack is active)
add_filter( 'jetpack_open_graph_tags', 'motomotus_seo_jetpack_og_desc', 20 );
function motomotus_seo_jetpack_og_desc( $tags ) {
    if ( is_front_page() || is_home() ) {
        $tags['og:description'] = 'VFX & Finishing';
    }
    return $tags;
}
