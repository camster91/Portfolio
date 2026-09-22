<?php
/**
 * Contract tests for the artist-filtered portfolio renderer.
 */

define( 'ABSPATH', __DIR__ );

$motomotus_test_shortcodes = array();
$motomotus_test_query_args = array();
$motomotus_test_preview    = false;
$motomotus_test_meta       = array();

function add_shortcode( $tag, $callback ) {
    global $motomotus_test_shortcodes;
    $motomotus_test_shortcodes[ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts ) {
    return array_merge( $pairs, array_intersect_key( $atts, $pairs ) );
}

function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return preg_replace( '/[^a-z0-9-]+/', '-', $value );
}

function sanitize_text_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}

function is_preview() {
    global $motomotus_test_preview;
    return $motomotus_test_preview;
}

function current_user_can() {
    return true;
}

function __( $text ) {
    return $text;
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr_e( $text ) {
    echo esc_attr( $text );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html_e( $text ) {
    echo esc_html( $text );
}

function esc_url( $url ) {
    return filter_var( (string) $url, FILTER_SANITIZE_URL );
}

function get_post_meta( $post_id, $key ) {
    global $motomotus_test_meta;
    return isset( $motomotus_test_meta[ $post_id ][ $key ] ) ? $motomotus_test_meta[ $post_id ][ $key ] : '';
}

function wp_kses_post( $text ) {
    return $text;
}

function wp_strip_all_tags( $text ) {
    return strip_tags( $text );
}

class WP_Query {
    public function __construct( $args ) {
        global $motomotus_test_query_args;
        $motomotus_test_query_args = $args;
    }

    public function have_posts() {
        return false;
    }
}

require dirname( __DIR__ ) . '/includes/shortcode.php';

if ( empty( $motomotus_test_shortcodes['motomotus_work'] ) ) {
    fwrite( STDERR, "Artist portfolio shortcode was not registered.\n" );
    exit( 1 );
}

if ( empty( $motomotus_test_shortcodes['motomotus_colour_landing'] ) ) {
    fwrite( STDERR, "Colour landing shortcode was not registered.\n" );
    exit( 1 );
}

$landing_markup = motomotus_colour_landing_shortcode(
    array(
        'heading'      => 'Representing <script>Arketype</script> Colourists',
        'chuck_url'    => '/chuck/',
        'beatrice_url' => '/beatrice-tremblay/',
    )
);

if ( false === strpos( $landing_markup, 'motomotus-colour-landing' ) || false === strpos( $landing_markup, 'href="/chuck/"' ) || false === strpos( $landing_markup, 'href="/beatrice-tremblay/"' ) || false !== strpos( $landing_markup, '<script>' ) ) {
    fwrite( STDERR, "Colour landing markup did not preserve safe, accessible artist links.\n" );
    exit( 1 );
}

$motomotus_test_preview = true;
$markup = motomotus_work_shortcode(
    array(
        'collection'     => 'Colour',
        'artist'         => 'Chuck',
        'heading'        => 'CHUCK<script>',
        'variant'        => 'artist',
        'posts_per_page' => '6',
    )
);

if ( array( 'publish', 'draft', 'pending', 'future', 'private' ) !== $motomotus_test_query_args['post_status'] ) {
    fwrite( STDERR, "Authenticated previews must include editable draft portfolio records.\n" );
    exit( 1 );
}

$tax_query = $motomotus_test_query_args['tax_query'];
if ( 'AND' !== $tax_query['relation'] || 'colour' !== $tax_query[0]['terms'] || 'chuck' !== $tax_query[1]['terms'] ) {
    fwrite( STDERR, "Artist portfolio query was not scoped to the requested collection and artist.\n" );
    exit( 1 );
}

if ( false === strpos( $markup, 'motomotus-container--artist' ) || false === strpos( $markup, '>CHUCK<' ) || false !== strpos( $markup, '<script>' ) ) {
    fwrite( STDERR, "Artist portfolio markup did not preserve the safe visual contract.\n" );
    exit( 1 );
}

if ( array() !== motomotus_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Temporary grid labels must not leak into the modal credits.\n" );
    exit( 1 );
}

if ( 'Agency' !== motomotus_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Artist items without a confirmed agency must show one temporary Agency subtitle.\n" );
    exit( 1 );
}

$motomotus_test_meta[3729] = array(
    'agency'   => '<strong>Confirmed Agency</strong>',
    'client'   => 'CAN-AM',
    'director' => 'Director Name',
);

if ( 'Confirmed Agency' !== motomotus_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Artist grid subtitles must prefer the sanitized confirmed agency value.\n" );
    exit( 1 );
}

if ( array( 'Client: CAN-AM', 'Agency: Confirmed Agency', 'Director: Director Name' ) !== motomotus_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Full structured credits must remain available to the project modal.\n" );
    exit( 1 );
}

$shortcode_source = file_get_contents( dirname( __DIR__ ) . '/includes/shortcode.php' );
$style_source     = file_get_contents( dirname( __DIR__ ) . '/assets/css/style.css' );
if ( false === strpos( $shortcode_source, 'class="motomotus-agency"' ) || false !== strpos( $shortcode_source, 'foreach ( $item[\'caption_lines\'] as $credit_line )' ) ) {
    fwrite( STDERR, "Artist cards must render exactly one agency subtitle instead of every modal credit line.\n" );
    exit( 1 );
}

if ( false !== strpos( $style_source, '.motomotus-container--artist .motomotus-info {' . "\n" . '    position: absolute;' ) ) {
    fwrite( STDERR, "Artist project information must not be visually hidden.\n" );
    exit( 1 );
}

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/motomotus-portfolio.php' );
$global_script = file_get_contents( dirname( __DIR__ ) . '/assets/js/global-fixes.js' );
$logo_asset    = dirname( __DIR__ ) . '/assets/images/arketype-logo.png';
if ( false === strpos( $plugin_source, "'arketypeLogoUrl'" ) || false === strpos( $global_script, 'a[href*="/home/"]' ) || false === strpos( $global_script, 'MOTOMOTUS × ARKETYPE home' ) || false === strpos( $global_script, 'motomotus-lockup-x' ) || false === strpos( $global_script, 'motomotus-arketype-logo' ) || ! is_file( $logo_asset ) || filesize( $logo_asset ) < 100 ) {
    fwrite( STDERR, "The shared Colour-page ARKETYPE logo treatment is incomplete.\n" );
    exit( 1 );
}

$motomotus_test_preview = false;
motomotus_work_shortcode( array( 'artist' => 'chuck' ) );
if ( array( 'publish' ) !== $motomotus_test_query_args['post_status'] ) {
    fwrite( STDERR, "Public portfolio renders must not include draft records.\n" );
    exit( 1 );
}

echo "Artist portfolio shortcode contract passed.\n";
