<?php
/**
 * Shortcode to display the Motomotus Work Grid
 * Adapted to use existing 'portfolio' post type + ACF fields
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

add_shortcode( 'motomotus_work', 'motomotus_work_shortcode' );
function motomotus_work_shortcode( $atts ) {
    // Prevent loading scripts and breaking layout if we are inside the Elementor editor
    if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        return '<div style="padding: 40px; background: #f0f0f0; text-align: center; border: 2px dashed #999;"><strong>Motomotus Portfolio Grid</strong><br><em>Shortcode preview is disabled in the Elementor editor to prevent layout conflicts. Please view the live page to see the grid.</em></div>';
    }

    $atts = shortcode_atts( array(
        'posts_per_page' => -1,
        'category'       => '',
    ), $atts );

    $args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => (int) $atts['posts_per_page'],
        'orderby'        => 'menu_order',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    );

    if ( ! empty( $atts['category'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ),
        );
    }

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <div class="motomotus-container">
        <!-- Filter Menu -->
        <div class="motomotus-filters">
            <button class="motomotus-filter-btn active" data-filter="all"><?php _e( 'All', 'motomotus' ); ?></button>
            <?php
            $terms = get_terms( array(
                'taxonomy'    => 'post_tag',
                'hide_empty'  => true,
            ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    echo '<button class="motomotus-filter-btn" data-filter="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</button>';   
                }
            }
            ?>
        </div>

        <!-- Grid -->
        <div class="motomotus-grid">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    /* Map ACF fields from existing portfolio posts */
                    $agency        = get_post_meta( get_the_ID(), 'subtitle', true );
                    $preview_video = get_post_meta( get_the_ID(), '_motomotus_preview_video', true );
                    $main_video    = esc_url( get_post_meta( get_the_ID(), 'video_link', true ) );
                    /* Parse portfolio_text into structured caption with label/value spans */
                    $caption_raw = wp_kses_post( get_post_meta( get_the_ID(), 'portfolio_text', true ) );
                    // Handle both literal \n (from WP-CLI) and actual newlines
                    $caption_processed = str_replace( '\n', "\n", $caption_raw );
                    $caption_lines = preg_split( '/<br\s*\/?>|\r\n|\n|\r/', $caption_processed );
                    $caption_blocks = array();
                    foreach ( $caption_lines as $line ) {
                        $line_text = html_entity_decode( trim( strip_tags( $line ) ), ENT_QUOTES, 'UTF-8' );
                        if ( preg_match( '/^([^:]+):\s*(.+)$/u', $line_text, $matches ) ) {
                            $label = esc_html( trim( $matches[1] ) );
                            $value = esc_html( trim( $matches[2], " \t\n\r\0\x0B\xA0" ) );
                            $caption_blocks[] = '<div class="caption-block"><span class="caption-label">' . $label . ':</span> <span class="caption-value">' . $value . '</span></div>';
                        } elseif ( ! empty( $line_text ) ) {
                            $caption_blocks[] = '<div class="caption-block">' . esc_html( trim( $line_text ) ) . '</div>';
                        }
                    }
                    $caption = implode( '', $caption_blocks );

                    /* Thumbnail: fallback to ACF 'image' field if no featured image */
                    $thumbnail = get_the_post_thumbnail_url( get_the_ID(), 'motomotus-thumb' );
                    if ( ! $thumbnail ) {
                        $image_id = get_post_meta( get_the_ID(), 'image', true );
                        if ( $image_id ) {
                            $thumbnail = wp_get_attachment_image_url( (int) $image_id, 'motomotus-thumb' );
                        }
                    }

                    $categories = wp_get_post_terms( get_the_ID(), 'post_tag', array( 'fields' => 'slugs' ) );
                    $cat_class = '';
                    if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                        $cat_class = implode( ' ', array_map( 'sanitize_html_class', $categories ) );
                    }
                ?>
                    <div class="motomotus-item <?php echo esc_attr( $cat_class ); ?>"
                         data-video="<?php echo esc_url( $main_video ); ?>"
                         data-caption="<?php echo esc_attr( $caption ); ?>">
                        <div class="motomotus-item-inner">
                            <div class="motomotus-media">
                                <?php if ( $thumbnail ) : ?>
                                    <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title_attribute(); ?>" class="motomotus-thumb">
                                <?php endif; ?>
                                <?php if ( $preview_video ) : ?>
                                    <video class="motomotus-preview-video" muted loop playsinline preload="none">
                                        <source src="<?php echo esc_url( $preview_video ); ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>
                            </div>
                            <div class="motomotus-info">
                                <h3 class="motomotus-title"><?php the_title(); ?></h3>
                                <?php if ( $agency ) : ?>
                                    <p class="motomotus-agency"><?php echo esc_html( $agency ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php _e( 'No work items found.', 'motomotus' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="motomotus-modal" id="motomotus-video-modal">
        <div class="motomotus-modal-overlay"></div>
        <div class="motomotus-modal-content">
            <button class="motomotus-modal-close" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="motomotus-video-container"></div>
            <div class="motomotus-modal-caption"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
