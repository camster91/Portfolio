<?php
/**
 * Motomotus Work Order Sync Script
 * 
 * This script ensures the Motomotus Work items are ordered exactly as requested.
 * It identifies existing items by title and updates their menu_order.
 */

// Load WordPress
require_once( 'wp-load.php' );

if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Unauthorized access.' );
}

$requested_order = array(
    'Boston Pizza',
    'Bauer',
    'Adidas',
    'RBC',
    'CRA',
    'Agency of the year',
    'Dairy Farmers of Ontario',
    'Mitsubishi',
    'Gay Lea'
);

echo "<h2>Syncing Motomotus Work Order</h2>";

foreach ( $requested_order as $index => $title ) {
    $order_value = ( $index + 1 ) * 10; // Use increments of 10 for flexibility
    
    $posts = get_posts( array(
        'post_type'  => 'motomotus_work',
        'title'      => $title,
        'post_status' => 'any',
        'numberposts' => 1
    ) );

    if ( ! empty( $posts ) ) {
        $post = $posts[0];
        wp_update_post( array(
            'ID'         => $post->ID,
            'menu_order' => $order_value
        ) );
        echo "✅ Updated <strong>{$title}</strong> to order {$order_value}<br>";
    } else {
        echo "❌ Could not find item with title: <strong>{$title}</strong>. Please ensure it is created first.<br>";
    }
}

echo "<p><strong>Sync Complete.</strong> Please delete this file from your server.</p>";
