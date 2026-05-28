<?php

namespace WpPublicRevisions\Frontend;

use WpPublicRevisions\Core\RevisionStore;

class Shortcode {

    function init() {
        add_shortcode( 'revision_history', [$this, 'wppr_revision_history_shortcode'] );
        add_action( 'wp_enqueue_scripts', [$this, 'wppr_enqueue_assets'] );
    }

    function wppr_enqueue_assets() {
        wp_register_style( 'wppr-public-css', WPR_FRONTEND_PATH . 'assets/css/frontend.css' );
        wp_register_script( 'wppr-public-js', WPR_FRONTEND_PATH . 'assets/js/frontend.js', [], '1.0', true );
        
        wp_enqueue_style( 'wppr-public-css' );
        wp_enqueue_script('wppr-public-js');
    }

    /**
     * ── SHORTCODE: [revision_history] ───────────────────────────────────
     *
     * Optional attributes:
     *   post_id  – show revisions for a specific post (defaults to current)
     *   limit    – max revisions to display (default 20, use -1 for all)
     *   date_fmt – PHP date format string (default "F j, Y \a\t g:i A")
     *
     * Example: [revision_history limit="10" date_fmt="Y-m-d"]
     */
    public function wppr_revision_history_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post_id'  => 0,
            'limit'    => 20,
            'date_fmt' => 'F j, Y \a\t g:i A',
        ), $atts, 'revision_history' );

        $post_id = absint( $atts['post_id'] ) ?: get_the_ID();

        if ( ! $post_id ) {
            return '<!-- WP Public Revisions: no post ID found -->';
        }

        // Only show for published posts
        $parent = get_post( $post_id );
        if ( ! $parent || 'publish' !== $parent->post_status ) {
            return '<!-- WP Public Revisions: post not published -->';
        }

        $revision_store = new RevisionStore( $post_id);
        $revisions = $revision_store->fetch_revisions();

        if ( is_wp_error( $revisions ) ) {
            return 'Error.';
        }

        if ( empty( $revisions ) ) {
            return '<p class="wppr-no-revisions">' . esc_html__( 'No revisions yet.', 'wp-public-revisions' ) . '</p>';
        }

        // Build output
        ob_start();
        ?>
        <div class="wppr-revision-list">
            <h3 class="wppr-heading"><?php esc_html_e( 'Revision History', WPR_SLUG ); ?></h3>
            <p class="wppr-subheading">
                <?php
                printf(
                    /* translators: %d = number of revisions */
                    esc_html__( '%d previous version(s).', 'wp-public-revisions' ),
                    count( $revisions )
                );
                ?>

                <button class="wppr-previous-button">Previous</button>
            </p>
            
            <div class="wppr-history-box">
                <ol class="wppr-list" reverse>
                <?php
                $index = count( $revisions );
                foreach ( $revisions as $rev ) :
                    $author_name = get_the_author_meta( 'display_name', $rev->author );
                    $date        = date($atts['date_fmt'], strtotime($rev->timestamp) );
                    $view_url    = add_query_arg( array(
                        'wppr_view_revision' => $rev->id,
                    ), get_permalink( $post_id ) );
                    ?>
                    <li class="wppr-item">
                        <a class="wppr-link" href="<?php echo esc_url( $view_url ); ?>">
                            <span class="wppr-label">
                                <?php
                                printf(
                                    /* translators: 1 = version number, 2 = date */
                                    esc_html__( 'Version %1$d — %2$s', 'wp-public-revisions' ),
                                    $index,
                                    $date
                                );
                                ?>
                            </span>
                            <?php if ( $author_name ) : ?>
                                <span class="wppr-author"><?php echo esc_html( $author_name ); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php
                    $index--;
                endforeach;
                ?>
                </ol>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

}