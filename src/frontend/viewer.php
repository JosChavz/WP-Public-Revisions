<?php

    namespace WpPublicRevisions\Frontend;

    use WpPublicRevisions\Core\RevisionStore;

    class Viewer
    {
        public function init(): void
        {
            add_filter('the_content', [$this, 'wppr_maybe_show_revision'], 3);
        }

        public function wppr_maybe_show_revision($content)
        {
            global $post;
            if (!isset($_GET['wppr_view_revision'])) {
                return $content;
            }

            if (!is_singular() || !is_main_query() || !$post) {
                return $content;
            }

            $revision_store = new RevisionStore($post->ID);
            $revision_id = absint($_GET['wppr_view_revision']);
            $revision = $revision_store->fetch_revision($revision_id);

            if (is_wp_error($revision) || (int)$revision->post_id !== $post->ID) {
                return $content;
            }

            $date = wp_date('F j, Y \a\t g:i A', strtotime($revision->timestamp));

            ob_start();
            ?>
            <div class="wppr-revision-notice">
                <p>
                    <strong><?php esc_html_e('You are viewing a past version of this article', 'wp-public-revisions'); ?></strong><br>
                    <?php
                        printf(
                        /* translators: %s = date of the revision */
                                esc_html__('Saved on %s.', 'wp-public-revisions'),
                                esc_html($date)
                        );
                    ?>
                </p>
                <a class="wppr-back-link" href="<?php echo esc_url(get_permalink($revision->post_id)); ?>">
                    &larr; <?php esc_html_e('Return to the current version', 'wp-public-revisions'); ?>
                </a>
            </div>

            <div class="wppr-revision-content">
                <?php echo wp_kses_post($revision->content); ?>
            </div>
            <?php
            return ob_get_clean();
        }
    }