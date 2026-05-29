<?php

namespace WpPublicRevisions\Admin;

use WpPublicRevisions\Core\RevisionStore;

class Metabox {
    public function init(): void
    {
        add_action( 'add_meta_boxes', [$this, 'wppr_add_meta_box'] );
        add_action('admin_enqueue_scripts', [$this, 'enqueue_ajax_script']);
        add_action('wp_ajax_wppr_create_revision', [$this,'handle_create_revision'] );
        add_action('wp_ajax_wppr_fetch_revisions', [$this,'handle_fetch_revisions'] );
        add_action('wp_ajax_wppr_delete_revision', [$this,'handle_delete_revision'] );
    }

    public function enqueue_ajax_script(): void
    {
        wp_enqueue_style('wppr-metabox-css', WPR_FRONTEND_PATH .'assets/css/frontend-admin.css');

        wp_enqueue_script(
            'wppr-metabox-ajax',
            WPR_FRONTEND_PATH . 'assets/js/admin-metabox.js',
            ['jquery', 'wp-data', 'wp-editor'],
            null,
            true
        );
        wp_localize_script('wppr-metabox-ajax', 'wppr_ajax_obj', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce( 'wppr_create_revision' ),
            'fetch_nonce' => wp_create_nonce( 'wppr_fetch_revisions'),
            'delete_nonce' => wp_create_nonce( 'wppr_delete_revision'),
        ]);
    }

    public function handle_create_revision(): void
    {
        check_ajax_referer('wppr_create_revision', 'nonce');

        $post_id = intval( $_POST['post_id'] );
        $post = get_post($post_id);

        if ( ! $post ) {
            wp_send_json_error('Post cannot be found', 404);
        }

        $post_content = $post->post_content;

        if ( empty( $post_content ) ) {
            wp_send_json_error('No content', 400);
        }

        $revision_store = new RevisionStore($post_id);
        $revision = $revision_store->post_revision($post_content, "Revision");

        if ( is_wp_error($revision) ) {
            wp_send_json_error($revision->get_error_message());
        }

        wp_send_json_success( $revision, 201 );
    }

    public function handle_fetch_revisions(): void
    {
        check_ajax_referer("wppr_fetch_revisions", "fetch_nonce");

        $post_id = intval( $_GET['post_id'] );
        $post = get_post($post_id);

        if ( ! current_user_can('edit_post', $post_id) ) {
            wp_send_json_error('User cannot perform this action.', 403);
        }

        if ( ! $post ) {
            wp_send_json_error('Post cannot be found', 404);
        }

        $revision_store = new RevisionStore($post_id);
        $revisions = $revision_store->fetch_revisions();

        if ( is_wp_error($revisions) ) {
            wp_send_json_error($revisions->get_error_message());
        }

        wp_send_json_success($revisions, 200);
    }

    public function handle_delete_revision(): void
    {
        check_ajax_referer('wppr_delete_revision', 'delete_nonce');

        $rev_id = intval( $_POST['rev_id'] );
        $post_id = intval( $_POST['post_id'] );

        if ( ! current_user_can('edit_post', $post_id) ) {
            wp_send_json_error('User cannot perform this action.', 403);
        }

        $post = get_post($post_id);
        if ( ! $post ) {
            wp_send_json_error('Post does not exist',404);
        }

        $revision_store = new RevisionStore($post_id);
        $result = $revision_store->delete_revision($rev_id);
        if ( is_wp_error($result) ) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result, 200);
    }

    /**
     * Adds the revision store meta box to every single post type
     * TODO: Use an option where you add the metabox to specific post types
     * @return void
     */
    public function wppr_add_meta_box(): void
    {
        $options = get_option( 'wppr_post_type_option', [] );

        foreach ( $options as $pt => $enabled ) {
            if ( ! $enabled ) continue;
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

    public function wppr_meta_box_html( $post ) {
        $revision_store = new RevisionStore($post->ID);
        $revisions = $revision_store->fetch_revisions();
        $count = count( $revisions );

        ob_start();
        ?>
        <div id="wppr_metabox_container">
            <p>
                <?php echo esc_html__( 'Revision count:', 'wp-public-revisions' ) ?>
                <span id="wppr_revision-count">
                    <?php echo intval($count) ?>
                </span>
            </p>

            <div id="wppr_metabox-buttons">
                <button data-post-id="<?php echo esc_attr($post->ID) ?>" class="wppr_metabox-btn" id="wppr_create-revision-btn">Create New Revision</button>
                <p id="wppr_btn-disabled-text">Publish/Save before creating a new revision.</p>

                <button 
                id="wppr_history-revisions-btn" 
                class="wppr_metabox-btn"
                data-post-id="<?php echo esc_attr($post->ID) ?>"
                command="show-modal"
                commandfor="wppr_history-revisions-dialog">View Past Revisions</button>
                
                <dialog id="wppr_history-revisions-dialog">
                    <div id="wppr_history-revision-dialog-content">
                        <div class="wppr-dialog-header">
                            <h3><?php esc_html_e('Past Revisions', 'wp-public-revisions'); ?></h3>
                                            <button 
                    class="wppr_metabox-btn"
                    id="wppr_history-revision-close-btn" commandfor="wppr_history-revisions-dialog" command="close">Close</button>
                    </div>
    
                        <div id="wppr_dialog-results">
                            <div class="wppr-spinner">
                                <div    class="wppr-spinner-circle"></div>
                                <p><?php esc_html_e('Loading revisions…', 'wp-public-revisions'); ?></p>
                            </div>  
                        </div>
                    </div>
                </dialog>
            </div>
        </div>
        <?php

        echo ob_get_clean();
    }

}
