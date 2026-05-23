<?php

namespace WpPublicRevisions\Admin;

class Metabox {
    public function init() {
        add_action( 'add_meta_boxes', [$this, 'wppr_add_meta_box'] );
    }

    function wppr_add_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $pt ) {
            add_meta_box(
                'wppr_info',
                __( 'Public Revisions', 'wp-public-revisions' ),
                [$this, 'wppr_meta_box_html'],
                $pt,
                'side',
                'low'
            );
        }
    }

    function wppr_meta_box_html( $post ) {
        $revisions = wp_get_post_revisions( $post->ID );
        $revisions = array_filter( $revisions, function ( $r ) {
            return false === wp_is_post_autosave( $r );
        } );
        $count = count( $revisions );

        echo '<p>';
        printf(
            esc_html__( 'This post has %d revision(s) that visitors can see when you add the shortcode below.', 'wp-public-revisions' ),
            $count
        );
        echo '</p>';
        echo '<code style="display:block;padding:6px 8px;background:#f0f0f0;border-radius:3px;user-select:all">[revision_history]</code>';
        echo '<p class="description" style="margin-top:8px">';
        esc_html_e( 'Paste this shortcode anywhere in your content to show the public revision list.', 'wp-public-revisions' );
        echo '</p>';
    }

}
