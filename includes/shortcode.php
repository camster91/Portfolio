<?php
/**
 * Shortcode to display the Motomotus portfolio work grid.
 *
 * The shortcode owns the page markup while the portfolio post type remains
 * the source of truth for titles, media and credits. This keeps the visual
 * interaction reusable for Work and private review pages without hard-coding
 * client project data into the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'motomotus_work', 'motomotus_work_shortcode' );
add_shortcode( 'motomotus_colour_landing', 'motomotus_colour_landing_shortcode' );

/**
 * Render the two-artist Colour landing page.
 *
 * URLs and copy remain page configuration while the shared, shift-resistant
 * visual treatment stays in the plugin.
 */
function motomotus_colour_landing_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'heading'      => 'Representing Arketype Colourists',
            'chuck_label'  => 'Chuck',
            'chuck_url'    => '/chuck/',
            'beatrice_label' => 'Beatrice Tremblay',
            'beatrice_url' => '/beatrice-tremblay/',
            'beatrice_page_id' => 0,
        ),
        $atts,
        'motomotus_colour_landing'
    );

    $heading        = sanitize_text_field( $atts['heading'] );
    $chuck_label    = sanitize_text_field( $atts['chuck_label'] );
    $chuck_url      = esc_url( $atts['chuck_url'] );
    $beatrice_label = sanitize_text_field( $atts['beatrice_label'] );
    $beatrice_url   = esc_url( $atts['beatrice_url'] );

    // Draft artist pages need their authenticated preview URL during review.
    // The configured public URL remains the destination after publication.
    $beatrice_page_id = absint( $atts['beatrice_page_id'] );
    if ( is_preview() && $beatrice_page_id && current_user_can( 'edit_post', $beatrice_page_id ) && 'page' === get_post_type( $beatrice_page_id ) && 'publish' !== get_post_status( $beatrice_page_id ) ) {
        $preview_url = get_preview_post_link( $beatrice_page_id );
        if ( $preview_url ) {
            $beatrice_url = esc_url( $preview_url );
        }
    }

    ob_start();
    ?>
    <section class="motomotus-colour-landing" aria-labelledby="motomotus-colour-heading">
        <div class="motomotus-colour-landing__inner">
            <h1 class="motomotus-colour-landing__heading" id="motomotus-colour-heading"><?php echo esc_html( $heading ); ?></h1>
            <nav class="motomotus-colour-artists" aria-label="<?php esc_attr_e( 'Colour artists', 'motomotus' ); ?>">
                <a href="<?php echo esc_url( $chuck_url ); ?>"><?php echo esc_html( $chuck_label ); ?></a>
                <a href="<?php echo esc_url( $beatrice_url ); ?>"><?php echo esc_html( $beatrice_label ); ?></a>
            </nav>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Render the portfolio grid and its per-instance video dialog.
 *
 * Supported attributes:
 * - posts_per_page: number of published portfolio posts to show.
 * - category: optional post_tag slug to scope the query.
 * - collection: optional Collection slug (for example vfx or colour).
 * - artist: optional Artist slug (for example chuck).
 * - heading: optional visible page heading.
 * - variant: work|artist. Artist matches the supplied six-spot treatment.
 * - filters: show|hide the post_tag filter controls (hidden by default to
 *   preserve the current Work-page treatment).
 */
function motomotus_work_shortcode( $atts ) {
    static $instance = 0;
    $instance++;

    $instance_id      = 'motomotus-work-' . $instance;
    $modal_id         = $instance_id . '-modal';
    $modal_title_id   = $modal_id . '-title';
    $modal_caption_id = $modal_id . '-caption';

    // Prevent loading scripts and breaking layout if we are inside the
    // Elementor editor. The front-end preview remains available on the page
    // preview route.
    if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        return '<div style="padding: 40px; background: #f0f0f0; text-align: center; border: 2px dashed #999;"><strong>' . esc_html__( 'Motomotus Portfolio Grid', 'motomotus' ) . '</strong><br><em>' . esc_html__( 'Shortcode preview is disabled in the Elementor editor to prevent layout conflicts. View the page preview to see the grid.', 'motomotus' ) . '</em></div>';
    }

    $atts = shortcode_atts(
        array(
            'posts_per_page' => -1,
            'category'       => '',
            'collection'     => '',
            'artist'         => '',
            'heading'        => '',
            'variant'        => 'work',
            'filters'        => 'hide',
        ),
        $atts,
        'motomotus_work'
    );

    $category       = sanitize_title( $atts['category'] );
    $collection     = sanitize_title( $atts['collection'] );
    $artist         = sanitize_title( $atts['artist'] );
    $heading        = sanitize_text_field( $atts['heading'] );
    $variant        = 'artist' === sanitize_title( $atts['variant'] ) ? 'artist' : 'work';
    $posts_per_page = (int) $atts['posts_per_page'];
    if ( -1 !== $posts_per_page ) {
        $posts_per_page = max( 1, min( 100, $posts_per_page ) );
    }
    $show_filters = in_array(
        strtolower( sanitize_text_field( $atts['filters'] ) ),
        array( 'show', 'true', 'yes' ),
        true
    );

    $post_status = array( 'publish' );
    if ( is_preview() && current_user_can( 'edit_posts' ) ) {
        $post_status = array( 'publish', 'draft', 'pending', 'future', 'private' );
    }

    $args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => $posts_per_page,
        'orderby'        => 'menu_order',
        'order'          => 'DESC',
        'post_status'    => $post_status,
        'no_found_rows'  => true,
    );

    $tax_query = array();
    if ( $category ) {
        $tax_query[] = array(
            'taxonomy' => 'post_tag',
            'field'    => 'slug',
            'terms'    => $category,
        );
    }
    if ( $collection ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_collection',
            'field'    => 'slug',
            'terms'    => $collection,
        );
    }
    if ( $artist ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_artist',
            'field'    => 'slug',
            'terms'    => $artist,
        );
    }
    if ( $tax_query ) {
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );
    $items = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id       = get_the_ID();
            $item_status   = get_post_status( $post_id );
            if ( 'publish' !== $item_status && ! current_user_can( 'edit_post', $post_id ) ) {
                continue;
            }
            $title         = get_the_title();
            $agency        = get_post_meta( $post_id, 'subtitle', true );
            $main_video    = esc_url_raw( trim( (string) get_post_meta( $post_id, 'video_link', true ) ) );
            $thumbnail     = get_the_post_thumbnail_url( $post_id, 'motomotus-thumb' );

            // Thumbnail: fall back to the legacy ACF `image` field when no
            // featured image is set.
            if ( ! $thumbnail ) {
                $image_id = get_post_meta( $post_id, 'image', true );
                if ( $image_id ) {
                    $thumbnail = wp_get_attachment_image_url( (int) $image_id, 'motomotus-thumb' );
                }
            }

            $categories     = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) );
            $category_slugs = array();
            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                $category_slugs = array_values( array_filter( array_map( 'sanitize_html_class', $categories ) ) );
            }

            $items[] = array(
                'id'             => $post_id,
                'title'          => $title ? $title : __( 'Untitled project', 'motomotus' ),
                'agency'         => is_scalar( $agency ) ? (string) $agency : '',
                'main_video'     => $main_video,
                'thumbnail'      => $thumbnail ? esc_url( $thumbnail ) : '',
                'caption_lines'  => motomotus_get_caption_lines( $post_id ),
                'grid_subtitle'  => 'artist' === $variant ? motomotus_get_artist_grid_subtitle( $post_id ) : '',
                'category_slugs' => $category_slugs,
            );
        }
        wp_reset_postdata();
    }

    $filter_terms = array();
    if ( $show_filters && ! empty( $items ) ) {
        $item_ids     = wp_list_pluck( $items, 'id' );
        $filter_terms = get_terms(
            array(
                'taxonomy'   => 'post_tag',
                'hide_empty' => true,
                'object_ids' => array_map( 'intval', $item_ids ),
            )
        );
        if ( is_wp_error( $filter_terms ) ) {
            $filter_terms = array();
        }
    }

    ob_start();
    ?>
    <section class="motomotus-container motomotus-container--<?php echo esc_attr( $variant ); ?><?php echo $show_filters ? '' : ' motomotus-container--filters-hidden'; ?>" data-motomotus-instance="<?php echo esc_attr( $instance_id ); ?>" aria-label="<?php esc_attr_e( 'Motomotus portfolio', 'motomotus' ); ?>">
        <?php if ( $heading ) : ?>
            <h1 class="motomotus-page-title"><?php echo esc_html( $heading ); ?></h1>
        <?php endif; ?>
        <?php if ( $show_filters && ! empty( $filter_terms ) ) : ?>
            <nav class="motomotus-filters" aria-label="<?php esc_attr_e( 'Filter portfolio projects', 'motomotus' ); ?>">
                <button class="motomotus-filter-btn active" type="button" data-filter="all" aria-pressed="true">
                    <?php esc_html_e( 'All', 'motomotus' ); ?>
                </button>
                <?php foreach ( $filter_terms as $term ) : ?>
                    <button class="motomotus-filter-btn" type="button" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false">
                        <?php echo esc_html( $term->name ); ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="motomotus-grid" aria-label="<?php esc_attr_e( 'Portfolio projects', 'motomotus' ); ?>">
            <?php if ( ! empty( $items ) ) : ?>
                <?php foreach ( $items as $item ) :
                    $item_classes = implode( ' ', $item['category_slugs'] );
                    $caption       = implode( "\n", $item['caption_lines'] );
                    $aria_label    = sprintf( __( 'View %s project video', 'motomotus' ), $item['title'] );
                    ?>
                    <button
                        class="motomotus-item <?php echo esc_attr( $item_classes ); ?>"
                        type="button"
                        data-video="<?php echo esc_url( $item['main_video'] ); ?>"
                        data-caption="<?php echo esc_attr( $caption ); ?>"
                        data-title="<?php echo esc_attr( $item['title'] ); ?>"
                        data-modal="<?php echo esc_attr( $modal_id ); ?>"
                        aria-label="<?php echo esc_attr( $aria_label ); ?>"
                        <?php disabled( ! $item['main_video'] ); ?>
                    >
                        <span class="motomotus-item-inner">
                            <span class="motomotus-media">
                                <?php if ( $item['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" class="motomotus-thumb" loading="lazy" decoding="async">
                                <?php endif; ?>
                            </span>
                            <span class="motomotus-info">
                                <<?php echo $heading ? 'h2' : 'h3'; ?> class="motomotus-title"><?php echo esc_html( $item['title'] ); ?></<?php echo $heading ? 'h2' : 'h3'; ?>>
                                <?php if ( 'artist' === $variant && $item['grid_subtitle'] ) : ?>
                                    <p class="motomotus-agency"><?php echo esc_html( $item['grid_subtitle'] ); ?></p>
                                <?php elseif ( $item['agency'] ) : ?>
                                    <p class="motomotus-agency"><?php echo esc_html( $item['agency'] ); ?></p>
                                <?php endif; ?>
                            </span>
                        </span>
                    </button>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="motomotus-empty"><?php esc_html_e( 'No work items found.', 'motomotus' ); ?></p>
            <?php endif; ?>
        </div>

        <dialog class="motomotus-modal" id="<?php echo esc_attr( $modal_id ); ?>" aria-labelledby="<?php echo esc_attr( $modal_title_id ); ?>" aria-describedby="<?php echo esc_attr( $modal_caption_id ); ?>">
            <div class="motomotus-modal-overlay" data-motomotus-dismiss="true"></div>
            <div class="motomotus-modal-content">
                <button class="motomotus-modal-close" type="button" aria-label="<?php esc_attr_e( 'Close project video', 'motomotus' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <h2 class="motomotus-modal-title" id="<?php echo esc_attr( $modal_title_id ); ?>"><?php esc_html_e( 'Portfolio project', 'motomotus' ); ?></h2>
                <div class="motomotus-video-container"></div>
                <div class="motomotus-modal-caption" id="<?php echo esc_attr( $modal_caption_id ); ?>"></div>
            </div>
        </dialog>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Build plain-text caption lines from structured credits or the legacy field.
 * The browser turns these lines into DOM nodes with textContent, so credits
 * cannot become executable markup through a post meta value.
 */
function motomotus_get_caption_lines( $post_id ) {
    $credit_fields = array(
        'client'        => 'Client',
        'agency'        => 'Agency',
        'director'      => 'Director',
        'editor'        => 'Editor',
        'vfx_finishing' => 'VFX & Finishing',
    );

    $lines = array();
    foreach ( $credit_fields as $meta_name => $label ) {
        $value = get_post_meta( $post_id, $meta_name, true );
        if ( ! is_scalar( $value ) ) {
            continue;
        }
        $value = trim( wp_strip_all_tags( (string) $value ) );
        if ( $value ) {
            $lines[] = $label . ': ' . $value;
        }
    }

    if ( ! empty( $lines ) ) {
        return $lines;
    }

    // Legacy fallback: parse portfolio_text lines such as "Client: Example".
    $caption_raw       = wp_kses_post( (string) get_post_meta( $post_id, 'portfolio_text', true ) );
    $caption_processed = str_replace( '\\n', "\n", $caption_raw );
    $caption_lines     = preg_split( '/<br\s*\/?>|\r\n|\n|\r/', $caption_processed );

    foreach ( $caption_lines as $line ) {
        $line_text = html_entity_decode( trim( wp_strip_all_tags( $line ) ), ENT_QUOTES, 'UTF-8' );
        if ( preg_match( '/^([^:]+):\s*(.+)$/u', $line_text, $matches ) ) {
            $lines[] = trim( $matches[1] ) . ': ' . trim( $matches[2], " \t\n\r\0\x0B\xA0" );
        } elseif ( $line_text ) {
            $lines[] = trim( $line_text );
        }
    }

    return $lines;
}

/**
 * Return the single subtitle used beneath a Colour artist project title.
 *
 * The project title is the client name. Morgan's approved grid treatment has
 * exactly one subscript line: the confirmed agency value, or the temporary
 * label "Agency" until that value is supplied. Full structured credits remain
 * available to the video modal through motomotus_get_caption_lines().
 */
function motomotus_get_artist_grid_subtitle( $post_id ) {
    $agency = get_post_meta( $post_id, 'agency', true );
    if ( ! is_scalar( $agency ) || ! trim( (string) $agency ) ) {
        $agency = get_post_meta( $post_id, 'subtitle', true );
    }

    if ( is_scalar( $agency ) ) {
        $agency = trim( wp_strip_all_tags( (string) $agency ) );
    } else {
        $agency = '';
    }

    return $agency ? $agency : __( 'Agency', 'motomotus' );
}
