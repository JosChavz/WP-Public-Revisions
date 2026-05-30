<?php

    namespace WpPublicRevisions\Frontend;

    use WpPublicRevisions\Core\RevisionStore;

    class Shortcode
    {

        function init(): void
        {
            add_action('wp_ajax_wppr_fetch_revisions', [$this, 'wppr_handle_fetch_revisions']);
            add_action('wp_ajax_nopriv_wppr_fetch_revisions', [$this, 'wppr_handle_fetch_revisions']);
            add_action('wp_enqueue_scripts', [$this, 'wppr_enqueue_assets']);

            add_shortcode('revision_history', [$this, 'wppr_revision_history_shortcode']);
        }

        function wppr_enqueue_assets(): void
        {
            wp_register_style('wppr-public-css', WPPR_FRONTEND_PATH . 'assets/css/frontend.css');
            wp_register_script('wppr-public-js', WPPR_FRONTEND_PATH . 'assets/js/frontend.js', ['jquery'], '1.0', true);
            wp_set_script_translations('wppr-public-js', 'wp-public-revisions');

            wp_enqueue_style('wppr-public-css');
            wp_enqueue_script('wppr-public-js');

            wp_localize_script('wppr-public-js', 'wppr_frontend_ajax_obj', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wppr_fetch_revisions'),
            ]);
        }

        public function wppr_handle_fetch_revisions(): void
        {
            check_ajax_referer('wppr_fetch_revisions', 'nonce');

            $post_id = intval($_GET['post_id']);
            $post = get_post($post_id);

            if (!$post) {
                wp_send_json_error('Post cannot be found', 404);
            }

            $date_format = isset($_GET['date_format'])
                    ? stripslashes(sanitize_text_field($_GET['date_format']))
                    : get_option('date_format');

            $revision_store = new RevisionStore($post_id);
            $revisions = $revision_store->fetch_revisions();

            if (is_wp_error($revisions)) {
                wp_send_json_error($revisions->get_error_message());
            }

            // Modify results for JS
            foreach ($revisions as $revision) {
                error_log(sanitize_text_field($date_format));
                $revision->timestamp = date(sanitize_text_field($date_format), strtotime($revision->timestamp));
                $revision->author = get_the_author_meta('display_name', $revision->author);
            }

            wp_send_json_success($revisions, 200);
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
        public function wppr_revision_history_shortcode($atts): false|string
        {
            $atts = shortcode_atts(array(
                    'post_id' => 0,
                    'limit' => 20,
                    'date_fmt' => 'F j, Y \a\t g:i A',
            ), $atts, 'revision_history');

            $post_id = absint($atts['post_id']) ?: get_the_ID();

            if (!$post_id) {
                return '<!-- WP Public Revisions: no post ID found -->';
            }

            // Only show for published posts
            $parent = get_post($post_id);
            if (!$parent || 'publish' !== $parent->post_status) {
                return '<!-- WP Public Revisions: post not published -->';
            }

            $revision_store = new RevisionStore($post_id);
            $revision_count = $revision_store->fetch_revisions_count();

            if (is_wp_error($revision_count)) {
                return $revision_count->get_error_message();
            }

            if (0 === $revision_count) {
                return '<p class="wppr-no-revisions">' . esc_html__('No revisions yet.', 'wp-public-revisions') . '</p>';
            }

            // Build output
            ob_start();
            ?>
            <div class="wppr-revision-list">
                <h3 class="wppr-heading"><?php esc_html_e('Revision History', 'wp-public-revisions'); ?></h3>
                <p class="wppr-subheading">
                    <?php
                        printf(
                                esc_html(_n(
                                        '%d previous version on file.',
                                        '%d previous versions on file.',
                                        $revision_count,
                                        'wp-public-revisions'
                                )),
                                $revision_count
                        );
                    ?>
                </p>

                <button data-date-format="<?php echo esc_attr($atts['date_fmt']) ?>"
                        data-post-id="<?php echo esc_attr($post_id) ?>"
                        class="wppr-previous-button">
                    <?php esc_html_e('Show revision history', 'wp-public-revisions'); ?>
                </button>

                <div class="wppr-history-box">
                    <div class="wppr-spinner">
                        <div class="wppr-spinner-circle"></div>
                        <p><?php esc_html_e('Loading revisions…', 'wp-public-revisions'); ?></p>
                    </div>
                </div>

            </div>
            <?php
            return ob_get_clean();
        }

    }