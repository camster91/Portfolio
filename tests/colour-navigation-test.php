<?php
/** Verify draft routes stay permission-bound and public links are usable. */
define( 'ABSPATH', __DIR__ );
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_basename( $file ) { return basename( $file ); }
function plugin_dir_url( $file ) { return '/plugin/'; }
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function get_post_type() { global $type; return $type; }
function get_post_status() { global $status; return $status; }
function current_user_can( $capability, $id ) {
    global $can_edit;
    if ( 'edit_post' !== $capability || 3632 !== $id ) throw new Exception( 'Incorrect capability scope' );
    return $can_edit;
}
function get_permalink() { return '/colour/'; }
function get_preview_post_link() { return '/?page_id=3632&preview=true'; }
require dirname( __DIR__ ) . '/motomotus-portfolio.php';
$type = 'page';
foreach ( array(
    array( 'draft', false, '' ),
    array( 'draft', true, '/?page_id=3632&preview=true' ),
    array( 'publish', false, '/colour/' ),
    array( 'publish', true, '/colour/' ),
    array( 'private', false, '' ),
    array( 'private', true, '' ),
    array( 'trash', true, '' ),
    array( 'pending', true, '' ),
) as $case ) {
    list( $status, $can_edit, $expected ) = $case;
    if ( $expected !== motomotus_get_colour_navigation_url() ) throw new Exception( 'Route failed for ' . $status );
}
$type = false;
if ( '' !== motomotus_get_colour_navigation_url() ) throw new Exception( 'Missing page exposed' );
echo "Colour navigation permission contracts passed.\n";
