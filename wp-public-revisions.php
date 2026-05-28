<?php
/**
 * Plugin Name: WP Public Revisions
 * Plugin URI: https://github.com/joschavz/wp-public-revisions
 * Description: Display a public revision history on any post or page via the [revision_history] shortcode. Visitors can click through to read any past version of the content.
 * Version: 1.1.0
 * Requires PHP: 8.3
 * Author: Jose Manuel Chavez
 * Author URI: https://hozay.tech/
 * License: GPL-2.0+
 * Text Domain: wp-public-revisions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

define("WPR_SLUG", "wp-public-revisions");
define("WPR_ROOT_PATH", plugin_dir_path( __FILE__ ) );
define("WPR_FRONTEND_PATH", plugin_dir_url( __FILE__ ) );

register_activation_hook(__FILE__, ["\WpPublicRevisions\Core\Installer", "install"]);

\WpPublicRevisions\Initialize::init();