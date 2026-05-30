<?php

    namespace WpPublicRevisions\Admin;

    class Options
    {

        public function init(): void
        {
            add_action('admin_menu', [$this, 'wppr_options_page']);
            add_action('admin_init', [$this, 'register_post_option_types']);
            add_action('admin_notices', [$this, 'wppr_option_notices']);
        }

        /**
         * @return void
         */
        public function wppr_option_notices(): void
        {
            if (
                    isset($_GET['page'])
                    && 'wppr_options' == $_GET['page']
                    && isset($_GET['settings-updated'])
                    && $_GET['settings-updated']
            ) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>Allowed types were saved.</strong>
                    </p>
                </div>
                <?php
            }
        }

        public function wppr_options_page(): void
        {
            add_submenu_page(
                    'tools.php',
                    'WPPR Settings',
                    'WP Public Revisions',
                    'manage_options',
                    'wppr_options',
                    [$this, 'wppr_options_page_hook']
            );
        }

        public function wppr_options_page_hook(): void
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            ?>

            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                    <?php settings_fields('wppr_options'); ?>
                    <?php do_settings_sections('wppr_options'); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function register_post_option_types(): void
        {
            register_setting(
                    'wppr_options',
                    'wppr_post_type_option',
                    [
                            'label' => __('Allowed Post Type', 'wp-public-revisions'),
                            'sanitize_callback' => [$this, 'sanitize_wppr_post_type_option'],
                    ]
            );
            register_setting(
                    'wppr_options',
                    'wppr_max_revisions',
                    [
                            'label' => __('Maximum Revisions', 'wp-public-revisions'),
                            'sanitize_callback' => [$this, 'sanitize_wppr_max_revisions'],
                    ]
            );

            add_settings_section(
                    'wppr_post_type_option_section',
                    __('Post Type Options', 'wp-public-revisions'),
                    '',
                    'wppr_options'
            );

            add_settings_field(
                    'wppr_field_post_type',
                    __('Allowed Post Types', 'wp-public-revisions'),
                    [$this, 'render_field_post_type'],
                    'wppr_options',
                    'wppr_post_type_option_section',
                    [
                            'class' => 'wppr_post_type_option',
                    ]
            );

            add_settings_field(
                    'wppr_max_revisions',
                    __('Max Revisions', 'wp-public-revisions'),
                    [$this, 'render_field_max_revisions'],
                    'wppr_options',
                    'wppr_post_type_option_section',
            );
        }

        public function sanitize_wppr_max_revisions($input): int
        {
            if (!is_numeric($input)) {
                return WPPR_MAX_REVISIONS;
            }

            if ($input < 1 || $input > WPPR_MAX_REVISIONS) {
                return WPPR_MAX_REVISIONS;
            }

            return $input;
        }

        public function sanitize_wppr_post_type_option($input): array
        {
            if (!is_array($input)) {
                return [];
            }

            $valid = get_post_types(['public' => true], 'names');

            return array_filter($input, function ($post_type) use ($valid) {
                return isset($valid[$post_type]) && 'attachment' !== $post_type;
            }, ARRAY_FILTER_USE_KEY);
        }

        public function render_field_max_revisions(): void
        {
            $max_revision_option = get_option('wppr_max_revisions');
            printf('<input min="1" max="%d" type="number" id="wppr_max_revisions" value="%d" name="wppr_max_revisions" />', WPPR_MAX_REVISIONS, $max_revision_option);
        }

        public function render_field_post_type(): void
        {
            $options = get_option('wppr_post_type_option', []);
            $post_types = get_post_types(['public' => true], 'names');

            ?>
            <fieldset>
                <legend>Select which post-type to show revisions:</legend>
                <div id="wppr_options-container">
                    <?php foreach ($post_types as $post_type) : ?>
                        <?php if ('attachment' == $post_type) : continue; endif; ?>
                        <label>
                            <input type="checkbox" name="wppr_post_type_option[<?php echo esc_attr($post_type) ?>]"
                                   id="<?php echo esc_attr($post_type) ?>"
                                   value="1" <?php checked(!empty($options[$post_type])); ?>>
                            <?php echo esc_html($post_type); ?>
                        </label>

                    <?php endforeach; ?>
                </div>
            </fieldset>
            <?php
        }
    }