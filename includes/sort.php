<?php
/**
 * Motomotus Portfolio — Drag-and-Drop Sort Page
 *
 * Adds a top-level "Sort" submenu under Portfolio (CPT) where content managers can
 * drag-and-drop portfolio items to set menu_order. Saves on drop via AJAX.
 *
 * Hooks:
 *   - admin_menu                    : register page
 *   - admin_enqueue_scripts         : enqueue jQuery UI Sortable + our CSS
 *   - wp_ajax_motomotus_save_order  : persist new menu_order values
 *   - wp_ajax_motomotus_get_order   : re-fetch list (for re-render after drop)
 *
 * Capability: edit_others_posts (global ordering affects every published item).
 * Nonce: motomotus_sort_nonce (per-page scoped action).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'motomotus_sort_register_page' );
function motomotus_sort_register_page() {
    add_submenu_page(
        'edit.php?post_type=portfolio',
        __( 'Sort Order', 'motomotus' ),
        __( 'Sort Order', 'motomotus' ),
        'edit_others_posts',
        'motomotus-sort',
        'motomotus_sort_render_page'
    );
}

add_action( 'admin_enqueue_scripts', 'motomotus_sort_enqueue' );
function motomotus_sort_enqueue( $hook ) {
    // Only enqueue on our page. Do not hardcode the hook name because
    // WordPress.com / Atomic can pass different values.
    if ( ! is_admin() ) {
        return;
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'motomotus-sort' !== $page ) {
        return;
    }
    $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
    if ( 'portfolio' !== $post_type ) {
        return;
    }

    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style(
        'motomotus-sort',
        MOTOMOTUS_URL . 'assets/css/sort.css',
        array(),
        MOTOMOTUS_VERSION
    );
    wp_enqueue_script(
        'motomotus-sort',
        MOTOMOTUS_URL . 'assets/js/sort.js',
        array( 'jquery', 'jquery-ui-sortable' ),
        MOTOMOTUS_VERSION,
        true
    );
    $context = motomotus_sort_context_from_request( $_GET );
    wp_localize_script( 'motomotus-sort', 'MotomotusSort', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'motomotus_sort_nonce' ),
        'context' => array(
            'postStatus' => $context['post_status'],
            'collection' => $context['collection'],
            'artist'     => $context['artist'],
        ),
        'i18n'    => array(
            'saving'   => __( 'Saving…', 'motomotus' ),
            'saved'    => __( 'Saved', 'motomotus' ),
            'error'    => __( 'Could not save order. Refresh and try again.', 'motomotus' ),
            'untitled' => __( '(untitled)', 'motomotus' ),
        ),
    ) );
}

/**
 * Normalize the independently sortable portfolio set selected by an editor.
 */
function motomotus_sort_context_from_request( $source ) {
    $allowed_statuses = array( 'publish', 'draft', 'pending', 'private', 'future' );
    $raw_status       = isset( $source['post_status'] ) && is_scalar( $source['post_status'] ) ? wp_unslash( $source['post_status'] ) : 'publish';
    $raw_collection   = isset( $source['collection'] ) && is_scalar( $source['collection'] ) ? wp_unslash( $source['collection'] ) : '';
    $raw_artist       = isset( $source['artist'] ) && is_scalar( $source['artist'] ) ? wp_unslash( $source['artist'] ) : '';
    $post_status      = sanitize_key( $raw_status );
    if ( ! in_array( $post_status, $allowed_statuses, true ) ) {
        $post_status = 'publish';
    }

    return array(
        'post_status' => $post_status,
        'collection'  => sanitize_title( $raw_collection ),
        'artist'      => sanitize_title( $raw_artist ),
    );
}

/**
 * Build the query for one sortable collection/artist/status set.
 */
function motomotus_sort_query_args( $context, $fields = '' ) {
    $args = array(
        'post_type'      => 'portfolio',
        'post_status'    => $context['post_status'],
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    );

    if ( $fields ) {
        $args['fields'] = $fields;
    }

    $tax_query = array();
    if ( $context['collection'] ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_collection',
            'field'    => 'slug',
            'terms'    => $context['collection'],
        );
    }
    if ( $context['artist'] ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_artist',
            'field'    => 'slug',
            'terms'    => $context['artist'],
        );
    }
    if ( $tax_query ) {
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }

    return $args;
}

function motomotus_sort_render_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to reorder portfolio items.', 'motomotus' ) );
    }

    $context     = motomotus_sort_context_from_request( $_GET );
    $items       = get_posts( motomotus_sort_query_args( $context ) );
    $collections = get_terms(
        array(
            'taxonomy'   => 'portfolio_collection',
            'hide_empty' => false,
        )
    );
    $artists     = get_terms(
        array(
            'taxonomy'   => 'portfolio_artist',
            'hide_empty' => false,
        )
    );
    if ( is_wp_error( $collections ) ) {
        $collections = array();
    }
    if ( is_wp_error( $artists ) ) {
        $artists = array();
    }

    ?>
    <div class="wrap motomotus-sort-wrap">
        <h1><?php esc_html_e( 'Sort Portfolio Order', 'motomotus' ); ?></h1>
        <p class="description">
            <?php esc_html_e( 'Choose one portfolio set, then drag its items into display order. Top of the list = first in that grid. Saves automatically.', 'motomotus' ); ?>
        </p>

        <form class="motomotus-sort-filters" method="get">
            <input type="hidden" name="post_type" value="portfolio">
            <input type="hidden" name="page" value="motomotus-sort">
            <label>
                <span><?php esc_html_e( 'Status', 'motomotus' ); ?></span>
                <select name="post_status">
                    <?php foreach ( array( 'publish' => __( 'Published', 'motomotus' ), 'draft' => __( 'Draft', 'motomotus' ), 'pending' => __( 'Pending', 'motomotus' ), 'private' => __( 'Private', 'motomotus' ), 'future' => __( 'Scheduled', 'motomotus' ) ) as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $context['post_status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e( 'Collection', 'motomotus' ); ?></span>
                <select name="collection">
                    <option value=""><?php esc_html_e( 'All collections', 'motomotus' ); ?></option>
                    <?php foreach ( $collections as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $context['collection'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e( 'Artist', 'motomotus' ); ?></span>
                <select name="artist">
                    <option value=""><?php esc_html_e( 'All artists', 'motomotus' ); ?></option>
                    <?php foreach ( $artists as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $context['artist'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit"><?php esc_html_e( 'Load set', 'motomotus' ); ?></button>
        </form>

        <?php if ( empty( $items ) ) : ?>
            <p><?php esc_html_e( 'No portfolio items found in this set.', 'motomotus' ); ?></p>
        <?php else : ?>
            <ul id="motomotus-sort-list" class="motomotus-sort-list" aria-label="<?php esc_attr_e( 'Sortable portfolio items', 'motomotus' ); ?>">
                <?php foreach ( $items as $item ) :
                    $thumb     = get_the_post_thumbnail_url( $item->ID, 'thumbnail' );
                    $edit_link = get_edit_post_link( $item->ID, 'raw' );
                    ?>
                    <li class="motomotus-sort-item" data-id="<?php echo esc_attr( $item->ID ); ?>">
                        <span class="motomotus-sort-handle" aria-hidden="true">⋮⋮</span>
                        <span class="motomotus-sort-thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="" />
                            <?php else : ?>
                                <span class="motomotus-sort-thumb-placeholder"></span>
                            <?php endif; ?>
                        </span>
                        <span class="motomotus-sort-title">
                            <?php
                            $title = get_the_title( $item->ID );
                            echo $title ? esc_html( $title ) : '<em>' . esc_html__( '(untitled)', 'motomotus' ) . '</em>';
                            ?>
                        </span>
                        <span class="motomotus-sort-meta">#<?php echo (int) $item->menu_order; ?></span>
                        <span class="motomotus-sort-actions">
                            <?php if ( $edit_link ) : ?>
                                <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'motomotus' ); ?></a>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div id="motomotus-sort-status" class="motomotus-sort-status" role="status" aria-live="polite"></div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * AJAX: persist new menu_order values.
 *
 * Expects: $_POST['nonce'], $_POST['order'] = "12,11,10,..."
 */
add_action( 'wp_ajax_motomotus_save_order', 'motomotus_sort_save_order' );
function motomotus_sort_save_order() {
    check_ajax_referer( 'motomotus_sort_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }

    $raw = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
    if ( $raw === '' ) {
        wp_send_json_error( array( 'message' => 'empty' ), 400 );
    }

    $ids = array_values( array_unique( wp_parse_id_list( $raw ) ) );
    if ( empty( $ids ) ) {
        wp_send_json_error( array( 'message' => 'invalid' ), 400 );
    }

    $context = motomotus_sort_context_from_request( $_POST );

    // Require the complete current scoped set. This prevents a stale or
    // tampered request from creating duplicate menu_order values or silently
    // omitting a project from that collection/artist/status view.
    $expected_ids = get_posts( motomotus_sort_query_args( $context, 'ids' ) );
    $expected_ids = array_map( 'intval', $expected_ids );

    $submitted_set = $ids;
    sort( $submitted_set, SORT_NUMERIC );
    sort( $expected_ids, SORT_NUMERIC );
    if ( $submitted_set !== $expected_ids ) {
        wp_send_json_error( array( 'message' => 'stale_or_invalid_order' ), 409 );
    }

    foreach ( $ids as $id ) {
        if ( ! current_user_can( 'edit_post', $id ) ) {
            wp_send_json_error( array( 'message' => 'forbidden_post' ), 403 );
        }
    }

    // First item = highest menu_order. With N items, top gets N, bottom gets 1.
    $count   = count( $ids );
    $updated = 0;
    foreach ( $ids as $index => $id ) {
        $result = wp_update_post( array(
            'ID'         => $id,
            'menu_order' => $count - $index,
        ), true );
        if ( ! is_wp_error( $result ) ) {
            $updated++;
        }
    }

    wp_send_json_success( array(
        'updated' => $updated,
        'total'   => $count,
    ) );
}
