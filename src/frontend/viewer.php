<?php

namespace WpPublicRevisions\Frontend;

class Viewer {
    public function init() {
        add_filter( 'the_content', [$this, 'wppr_maybe_show_revision'], 3 );
    }

    function wppr_maybe_show_revision( $content ) {
        if ( ! isset( $_GET['wppr_view_revision'] ) ) {
            return $content;
        }

        if ( ! is_singular() || ! is_main_query() ) {
            return $content;
        }

        $revision_id = absint( $_GET['wppr_view_revision'] );
        $revision    = get_post( $revision_id );

        if ( ! $revision || 'revision' !== $revision->post_type ) {
            return $content;
        }

        // Security: make sure the revision belongs to a published parent
        $parent = get_post( $revision->post_parent );
        if ( ! $parent || 'publish' !== $parent->post_status ) {
            return $content;
        }

        // Make sure the revision belongs to the current post
        if ( $parent->ID !== get_the_ID() ) {
            return $content;
        }

        $date = get_the_date( 'F j, Y \a\t g:i A', $revision );

        ob_start();
        ?>
        <div class="wppr-revision-notice">
            <p>
                <strong><?php esc_html_e( 'You are viewing a past version of this article', 'wp-public-revisions' ); ?></strong><br>
                <?php
                printf(
                    /* translators: %s = date of the revision */
                    esc_html__( 'Saved on %s.', 'wp-public-revisions' ),
                    esc_html( $date )
                );
                ?>
            </p>
            <a class="wppr-back-link" href="<?php echo esc_url( get_permalink( $parent ) ); ?>">
                &larr; <?php esc_html_e( 'Return to the current version', 'wp-public-revisions' ); ?>
            </a>
        </div>

        <div class="wppr-revision-content">
            <?php echo wp_kses_post( $revision->post_content ); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}