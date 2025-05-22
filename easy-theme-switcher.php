<?php
/**
 * Plugin Name: Easy Theme Switcher
 * Plugin URI: https://github.com/mehul0810/easy-theme-switcher
 * Description: Preview, test, and assign any installed WordPress theme to individual pages, posts, or taxonomy archives—privately and instantly.
 * Version: 1.0.0
 * Author: Mehul Gohil
 * Author URI: https://mehulgohil.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-theme-switcher
 * Domain Path: /languages
 *
 * Easy Theme Switcher is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Easy Theme Switcher is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy Theme Switcher. If not, see <https://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
if ( ! defined( 'ETS_PLUGIN_FILE' ) ) {
	define( 'ETS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ETS_PLUGIN_DIR' ) ) {
	define( 'ETS_PLUGIN_DIR', plugin_dir_path( ETS_PLUGIN_FILE ) );
}

if ( ! defined( 'ETS_PLUGIN_URL' ) ) {
	define( 'ETS_PLUGIN_URL', plugin_dir_url( ETS_PLUGIN_FILE ) );
}

if ( ! defined( 'ETS_PLUGIN_VERSION' ) ) {
	define( 'ETS_PLUGIN_VERSION', '1.0.0' );
}

// Default query parameter name for theme previews.
if ( ! defined( 'ETS_DEFAULT_QUERY_PARAM' ) ) {
	define( 'ETS_DEFAULT_QUERY_PARAM', 'ets_theme' );
}

/**
 * Main Easy_Theme_Switcher Class.
 *
 * @since 1.0.0
 */
final class Easy_Theme_Switcher {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var Easy_Theme_Switcher
	 */
	private static $instance;

	/**
	 * Main Easy_Theme_Switcher Instance.
	 *
	 * Ensures only one instance of Easy_Theme_Switcher is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return Easy_Theme_Switcher - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Easy_Theme_Switcher ) ) {
			self::$instance = new Easy_Theme_Switcher();
			self::$instance->setup();
			self::$instance->includes();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup() {
		// Register activation hook.
		register_activation_hook( ETS_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook.
		register_deactivation_hook( ETS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
		// Include core files.
		require_once ETS_PLUGIN_DIR . 'includes/class-ets-theme-switcher.php';
		require_once ETS_PLUGIN_DIR . 'includes/class-ets-settings.php';
		
		// Include admin files.
		if ( is_admin() ) {
			require_once ETS_PLUGIN_DIR . 'includes/admin/class-ets-admin.php';
			require_once ETS_PLUGIN_DIR . 'includes/admin/class-ets-settings-page.php';
		}

		// Include frontend files.
		require_once ETS_PLUGIN_DIR . 'includes/frontend/class-ets-frontend.php';
		require_once ETS_PLUGIN_DIR . 'includes/frontend/class-ets-preview-banner.php';
	}

	/**
	 * Setup hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function hooks() {
		// Load text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Activation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		// Set default options.
		$default_options = array(
			'enable_preview_banner' => 'yes',
			'default_preview_theme' => '',
			'preview_query_param'   => ETS_DEFAULT_QUERY_PARAM,
		);

		// Add options if they don't exist.
		add_option( 'ets_settings', $default_options );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'easy-theme-switcher',
			false,
			dirname( plugin_basename( ETS_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}

/**
 * Returns the main instance of Easy_Theme_Switcher.
 *
 * @since 1.0.0
 * @return Easy_Theme_Switcher
 */
function ETS() {
	return Easy_Theme_Switcher::instance();
}

// Initialize the plugin.
ETS();