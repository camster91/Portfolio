<?php
/**
 * Motomotus portfolio administration screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'motomotus_register_admin_page', 30 );
function motomotus_register_admin_page() {
    add_submenu_page(
        'edit.php?post_type=portfolio',
        __( 'Motomotus Portfolio', 'motomotus' ),
        __( 'Overview', 'motomotus' ),
        'edit_posts',
        'motomotus-portfolio',
        'motomotus_render_admin_page'
    );
}

function motomotus_render_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to manage portfolio projects.', 'motomotus' ) );
    }

    $counts    = wp_count_posts( 'portfolio' );
    $published = isset( $counts->publish ) ? (int) $counts->publish : 0;
    $drafts    = isset( $counts->draft ) ? (int) $counts->draft : 0;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Motomotus Portfolio', 'motomotus' ); ?></h1>
        <p><?php esc_html_e( 'Add, review, publish, and order projects for the Motomotus VFX and Colour artist pages.', 'motomotus' ); ?></p>

        <div class="notice notice-info inline">
            <p>
                <?php
                printf(
                    esc_html__( '%1$d published project(s) and %2$d draft project(s).', 'motomotus' ),
                    $published,
                    $drafts
                );
                ?>
            </p>
        </div>

        <p>
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Add Portfolio Project', 'motomotus' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Projects', 'motomotus' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=portfolio_collection&post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Collections', 'motomotus' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=portfolio_artist&post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Artists', 'motomotus' ); ?></a>
            <?php if ( current_user_can( 'edit_others_posts' ) ) : ?>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio&page=motomotus-sort' ) ); ?>"><?php esc_html_e( 'Set Display Order', 'motomotus' ); ?></a>
            <?php endif; ?>
        </p>

        <h2><?php esc_html_e( 'Project checklist', 'motomotus' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Add a project title and featured image.', 'motomotus' ); ?></li>
            <li><?php esc_html_e( 'Enter the grid subtitle, main video URL, and available credits in Motomotus Project Details.', 'motomotus' ); ?></li>
            <li><?php esc_html_e( 'Assign a Collection (VFX or Colour) and, for Colour work, an Artist.', 'motomotus' ); ?></li>
            <li><?php esc_html_e( 'Publish the project, then use Set Display Order to place it in its grid.', 'motomotus' ); ?></li>
            <li><?php esc_html_e( 'Verify the project modal and responsive grid on the intended page.', 'motomotus' ); ?></li>
        </ol>

        <h2><?php esc_html_e( 'Embed', 'motomotus' ); ?></h2>
        <p><code>[motomotus_work]</code></p>
        <p class="description"><?php esc_html_e( 'VFX example: [motomotus_work collection="vfx"]. Artist example: [motomotus_work collection="colour" artist="chuck" heading="CHUCK" variant="artist" posts_per_page="6"].', 'motomotus' ); ?></p>
    </div>
    <?php
}

add_filter( 'plugin_action_links_' . plugin_basename( MOTOMOTUS_PATH . 'motomotus-portfolio.php' ), 'motomotus_plugin_action_links' );
function motomotus_plugin_action_links( $links ) {
    $manage_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=portfolio&page=motomotus-portfolio' ) ) . '">' . esc_html__( 'Manage Portfolio', 'motomotus' ) . '</a>';
    array_unshift( $links, $manage_link );
    return $links;
}
