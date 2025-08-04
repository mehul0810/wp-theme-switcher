<?php
/**
 * Main Plugin Class
 *
 * @package WPThemeSwitcher
 * @since 1.0.0
 */

namespace WPThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Register the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register() {
		// Setup plugin constants.
		$this->setup();

		// Register services used throughout the plugin.
		add_action( 'plugins_loaded', array( $this, 'register_services' ) );

		// Register rest API routes.
		add_action( 'rest_api_init', function() {
			(new \WPThemeSwitcher\Includes\Endpoints())->register_rest_routes();
		} );

		// Load text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup() {
		// Register activation hook.
		register_activation_hook( WPTS_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook.
		register_deactivation_hook( WPTS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_services() {
		// Initialize Core Theme Switcher module.
		new ThemeSwitcher();

		// Load Admin Pages only.
		if ( is_admin() ) {	
			new Admin\Filters();
			new Admin\Settings();	
		}

		// Load Frontend Actions (includes theme preview functionality).
		new Includes\Actions();
		new Includes\Endpoints();
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
			'enable_preview'        => 'yes',
			'preview_query_param'   => WPTS_DEFAULT_QUERY_PARAM,
			'advanced'              => array(
				'preview_enabled'     => true,
				'debug_enabled'       => false,
			),
		);

		// Add options if they don't exist.
		add_option( 'wpts_theme_switcher_settings', $default_options );

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
			'wpts-theme-switcher',
			false,
			dirname( plugin_basename( WPTS_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}