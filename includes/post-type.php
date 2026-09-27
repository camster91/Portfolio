<?php
/**
 * Portfolio content model and editor fields.
 *
 * The production site may already register the `portfolio` post type through
 * Secure Custom Fields. This module preserves that configuration when present
 * and supplies a complete fallback when it is not.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'motomotus_register_portfolio_post_type', 20 );
function motomotus_register_portfolio_post_type() {
    if ( ! post_type_exists( 'portfolio' ) ) {
        $labels = array(
            'name'                  => __( 'Portfolios', 'motomotus' ),
            'singular_name'         => __( 'Portfolio Project', 'motomotus' ),
            'menu_name'             => __( 'Portfolios', 'motomotus' ),
            'name_admin_bar'        => __( 'Portfolio Project', 'motomotus' ),
            'add_new'               => __( 'Add Project', 'motomotus' ),
            'add_new_item'          => __( 'Add Portfolio Project', 'motomotus' ),
            'new_item'              => __( 'New Portfolio Project', 'motomotus' ),
            'edit_item'             => __( 'Edit Portfolio Project', 'motomotus' ),
            'view_item'             => __( 'View Portfolio Project', 'motomotus' ),
            'all_items'             => __( 'All Portfolio Projects', 'motomotus' ),
            'search_items'          => __( 'Search Portfolio Projects', 'motomotus' ),
            'not_found'             => __( 'No portfolio projects found.', 'motomotus' ),
            'not_found_in_trash'    => __( 'No portfolio projects found in Trash.', 'motomotus' ),
            'featured_image'        => __( 'Project Thumbnail', 'motomotus' ),
            'set_featured_image'    => __( 'Set project thumbnail', 'motomotus' ),
            'remove_featured_image' => __( 'Remove project thumbnail', 'motomotus' ),
        );

        register_post_type(
            'portfolio',
            array(
                'labels'             => $labels,
                'public'             => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_admin_bar'  => true,
                'show_in_rest'       => true,
                'publicly_queryable' => false,
                'has_archive'        => false,
                'rewrite'            => false,
                'menu_icon'          => 'dashicons-format-video',
                'menu_position'      => 20,
                'supports'           => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
                'taxonomies'         => array( 'post_tag' ),
                'capability_type'    => 'post',
                'map_meta_cap'       => true,
                'delete_with_user'   => false,
                'exclude_from_search' => true,
            )
        );
    }

    register_taxonomy_for_object_type( 'post_tag', 'portfolio' );
    motomotus_register_portfolio_taxonomies();
    motomotus_register_portfolio_meta();
}

/**
 * The host site's legacy portfolio registration keeps ordinary VFX single
 * URLs available. Colour projects are modal-first records with no authored
 * single-page content, so prevent WordPress from exposing its empty fallback
 * template for them.
 */
add_action( 'template_redirect', 'motomotus_hide_colour_portfolio_single', 0 );
function motomotus_hide_colour_portfolio_single() {
    if ( ! is_singular( 'portfolio' ) || ! has_term( 'colour', 'portfolio_collection', get_queried_object_id() ) ) {
        return;
    }

    global $wp_query;
    if ( $wp_query ) {
        $wp_query->set_404();
    }

    status_header( 404 );
    nocache_headers();
}

/**
 * Keep modal-only Colour projects out of the WordPress post sitemap. Their
 * canonical presentation is the artist page, not a generated blank single.
 */
add_filter( 'wp_sitemaps_posts_query_args', 'motomotus_exclude_colour_projects_from_sitemap', 10, 2 );
function motomotus_exclude_colour_projects_from_sitemap( $args, $post_type ) {
    if ( 'portfolio' !== $post_type ) {
        return $args;
    }

    $args['tax_query'] = isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ? $args['tax_query'] : array();
    $args['tax_query'][] = array(
        'taxonomy' => 'portfolio_collection',
        'field'    => 'slug',
        'terms'    => array( 'colour' ),
        'operator' => 'NOT IN',
    );

    return $args;
}

/**
 * Register the portfolio groupings used by the VFX and Colour page system.
 *
 * Collections separate disciplines such as VFX and Colour. Artists provide a
 * reusable relationship between an artist page and its portfolio projects.
 * Keeping both as taxonomies means editors can add artists and assign work in
 * the normal Portfolio editor without duplicating page-builder layouts.
 */
function motomotus_register_portfolio_taxonomies() {
    if ( ! taxonomy_exists( 'portfolio_collection' ) ) {
        register_taxonomy(
            'portfolio_collection',
            array( 'portfolio' ),
            array(
                'labels'            => array(
                    'name'          => __( 'Collections', 'motomotus' ),
                    'singular_name' => __( 'Collection', 'motomotus' ),
                    'menu_name'     => __( 'Collections', 'motomotus' ),
                    'all_items'     => __( 'All Collections', 'motomotus' ),
                    'edit_item'     => __( 'Edit Collection', 'motomotus' ),
                    'add_new_item'  => __( 'Add Collection', 'motomotus' ),
                    'search_items'  => __( 'Search Collections', 'motomotus' ),
                ),
                'public'            => false,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'hierarchical'      => true,
                'rewrite'           => false,
            )
        );
    } else {
        register_taxonomy_for_object_type( 'portfolio_collection', 'portfolio' );
    }

    if ( ! taxonomy_exists( 'portfolio_artist' ) ) {
        register_taxonomy(
            'portfolio_artist',
            array( 'portfolio' ),
            array(
                'labels'            => array(
                    'name'          => __( 'Artists', 'motomotus' ),
                    'singular_name' => __( 'Artist', 'motomotus' ),
                    'menu_name'     => __( 'Artists', 'motomotus' ),
                    'all_items'     => __( 'All Artists', 'motomotus' ),
                    'edit_item'     => __( 'Edit Artist', 'motomotus' ),
                    'add_new_item'  => __( 'Add Artist', 'motomotus' ),
                    'search_items'  => __( 'Search Artists', 'motomotus' ),
                ),
                'public'            => false,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'hierarchical'      => false,
                'rewrite'           => false,
            )
        );
    } else {
        register_taxonomy_for_object_type( 'portfolio_artist', 'portfolio' );
    }
}

function motomotus_register_portfolio_meta() {
    $fields = array(
        'subtitle'       => 'string',
        'video_link'     => 'string',
        'client'         => 'string',
        'agency'         => 'string',
        'director'       => 'string',
        'editor'         => 'string',
        'vfx_finishing'  => 'string',
    );

    foreach ( $fields as $key => $type ) {
        register_post_meta(
            'portfolio',
            $key,
            array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'video_link' === $key ? 'esc_url_raw' : 'sanitize_text_field',
                'auth_callback'     => 'motomotus_can_edit_portfolio_meta',
            )
        );
    }
}

function motomotus_can_edit_portfolio_meta( $allowed, $meta_key, $post_id ) {
    return $post_id
        ? current_user_can( 'edit_post', (int) $post_id )
        : current_user_can( 'edit_posts' );
}

add_action( 'add_meta_boxes_portfolio', 'motomotus_add_portfolio_meta_box' );
function motomotus_add_portfolio_meta_box() {
    add_meta_box(
        'motomotus-project-details',
        __( 'Motomotus Project Details', 'motomotus' ),
        'motomotus_render_portfolio_meta_box',
        'portfolio',
        'normal',
        'high'
    );
}

function motomotus_render_portfolio_meta_box( $post ) {
    wp_nonce_field( 'motomotus_save_project_details', 'motomotus_project_details_nonce' );

    $fields = array(
        'subtitle'      => array( __( 'Grid subtitle / agency', 'motomotus' ), 'text', __( 'Shown directly beneath the project title.', 'motomotus' ) ),
        'video_link'    => array( __( 'Main video URL', 'motomotus' ), 'url', __( 'Vimeo, YouTube, or a direct MP4 URL.', 'motomotus' ) ),
        'client'        => array( __( 'Client credit', 'motomotus' ), 'text', '' ),
        'agency'        => array( __( 'Agency credit', 'motomotus' ), 'text', '' ),
        'director'      => array( __( 'Director credit', 'motomotus' ), 'text', '' ),
        'editor'        => array( __( 'Editor credit', 'motomotus' ), 'text', '' ),
        'vfx_finishing' => array( __( 'VFX & Finishing credit', 'motomotus' ), 'text', '' ),
    );

    echo '<table class="form-table" role="presentation"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr>';
        echo '<th scope="row"><label for="motomotus-' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th>';
        echo '<td><input class="regular-text" id="motomotus-' . esc_attr( $key ) . '" name="motomotus_project[' . esc_attr( $key ) . ']" type="' . esc_attr( $field[1] ) . '" value="' . esc_attr( $value ) . '">';
        if ( $field[2] ) {
            echo '<p class="description">' . esc_html( $field[2] ) . '</p>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">' . esc_html__( 'Use the Featured Image panel for the project thumbnail. Assign its Collection and Artist in the editor sidebar, then publish it when it is ready to appear on the associated portfolio page.', 'motomotus' ) . '</p>';
}

add_action( 'save_post_portfolio', 'motomotus_save_portfolio_meta', 10, 2 );
function motomotus_save_portfolio_meta( $post_id, $post ) {
    if ( ! isset( $_POST['motomotus_project_details_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['motomotus_project_details_nonce'] ) );
    if ( ! wp_verify_nonce( $nonce, 'motomotus_save_project_details' ) ) {
        return;
    }

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! $post || 'portfolio' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $submitted = isset( $_POST['motomotus_project'] ) && is_array( $_POST['motomotus_project'] )
        ? wp_unslash( $_POST['motomotus_project'] )
        : array();

    $fields = array( 'subtitle', 'video_link', 'client', 'agency', 'director', 'editor', 'vfx_finishing' );
    foreach ( $fields as $key ) {
        $raw_value = isset( $submitted[ $key ] ) && is_scalar( $submitted[ $key ] ) ? (string) $submitted[ $key ] : '';
        $value     = 'video_link' === $key ? esc_url_raw( $raw_value ) : sanitize_text_field( $raw_value );

        if ( '' === $value ) {
            delete_post_meta( $post_id, $key );
        } else {
            update_post_meta( $post_id, $key, $value );
        }
    }
}

add_filter( 'manage_portfolio_posts_columns', 'motomotus_portfolio_columns' );
function motomotus_portfolio_columns( $columns ) {
    $updated = array();
    foreach ( $columns as $key => $label ) {
        $updated[ $key ] = $label;
        if ( 'title' === $key ) {
            $updated['motomotus_thumbnail'] = __( 'Thumbnail', 'motomotus' );
            $updated['motomotus_video']     = __( 'Video', 'motomotus' );
        }
    }
    return $updated;
}

add_action( 'manage_portfolio_posts_custom_column', 'motomotus_render_portfolio_column', 10, 2 );
function motomotus_render_portfolio_column( $column, $post_id ) {
    if ( 'motomotus_thumbnail' === $column ) {
        echo get_the_post_thumbnail( $post_id, array( 80, 45 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    if ( 'motomotus_video' === $column ) {
        $video_url = get_post_meta( $post_id, 'video_link', true );
        echo $video_url
            ? '<span class="dashicons dashicons-yes-alt" aria-label="' . esc_attr__( 'Video configured', 'motomotus' ) . '"></span>'
            : '<span class="dashicons dashicons-warning" aria-label="' . esc_attr__( 'Video missing', 'motomotus' ) . '"></span>';
    }
}
