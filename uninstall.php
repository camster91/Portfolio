<?php
/**
 * Uninstall Script for Motomotus Portfolio
 * 
 * This file is automatically run by WordPress when the user deletes the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete all 'Motomotus Work' posts and their metadata efficiently
$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'motomotus_work' ) );

if ( ! empty( $post_ids ) ) {
    foreach ( $post_ids as $post_id ) {
        wp_delete_post( $post_id, true );
    }
}

// 2. Delete the custom taxonomy 'work_category' terms
$terms = get_terms( array( 'taxonomy' => 'work_category', 'hide_empty' => false ) );
if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
    foreach ( $terms as $term ) {
        wp_delete_term( $term->term_id, 'work_category' );
    }
}

// 3. Clear any leftover meta data with the motomotus prefix
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_motomotus_%'" );
