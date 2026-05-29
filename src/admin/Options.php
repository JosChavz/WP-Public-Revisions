<?php

namespace WpPublicRevisions\Admin;

class Options {
 
	public function init(): void
	{
		add_action( 'admin_menu', [ $this, 'wppr_options_page' ] );
		add_action( 'admin_init', [$this, 'register_post_option_types'] );
	}
	
	public function wppr_options_page(): void {
		add_menu_page('wppr_options', 'WP Public Revisions Settings', 'manage_options', 'wppr_options', [$this, 'wppr_options_page_hook']);
	}
	
	public function wppr_options_page_hook() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'wppr_messages', 'wppr_message', __( 'Settings Saved', 'wp-public-revisions' ), 'updated' );
		}
		
		settings_errors( 'wppr_messages' );
		?>

		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wppr_options' ); ?>
				<?php do_settings_sections( 'wppr_options' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	public function register_post_option_types(): void
	{
		register_setting('wppr_options', 'wppr_post_type_option', [
			'label'             => __( 'Allowed Post Type', 'wp-public-revisions' ),
		]);
		
		add_settings_section('wppr_post_type_option_section', __('Post Type Options', 'wp-public-revisions'), [$this, 'render_post_option_section'], 'wppr_options');
		
		add_settings_field('wppr_field_post_type', __('Allowed Post Types', 'wp-public-revisions'), [$this, 'render_field_post_type'], 'wppr_options', 'wppr_post_type_option_section', [
			'class' => 'wppr_post_type_option',
		]);
	}
	
	public function render_field_post_type( $args ) : void {
		$options = get_option( 'wppr_post_type_option', [] );
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		
		?>
			<fieldset>
				<legend>Select which post-type to show revisions:</legend>
				<div id="wppr_options-container">
					<?php foreach ( $post_types as $post_type ) : ?>
						<?php if ('attachment' == $post_type) : continue; endif;  ?>
						<label>
							<input type="checkbox" name="wppr_post_type_option[<?php echo esc_attr($post_type) ?>]" id="<?php echo esc_attr($post_type) ?>" value="1" <?php checked( ! empty( $options[ $post_type ] ) ); ?>>
							<?php echo esc_html( $post_type ); ?>
						</label>
						
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php
	}
	
	public function render_post_option_section(): void
	{
		echo "The settings for post types! Here we will be using the options!";
	}
}