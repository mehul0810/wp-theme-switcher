<?php
/**
 * Main Plugin Class
 *
 * @package WpThemeSwitcher
 * @since 1.0.0
 */

namespace WpThemeSwitcher;

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

	public function register() {
		// Setup plugin constants.
		$this->setup();

		// Register services used throughout the plugin.
		add_action( 'plugins_loaded', array( $this, 'register_services' ) );

		add_action( 'rest_api_init', function() {
			// Register REST API routes.
			(new Settings())->register_rest_routes();
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
		register_activation_hook( WTS_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook.
		register_deactivation_hook( WTS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_services() {
		// Initialize core components.
		new ThemeSwitcher();
		// new Settings();
		
		// Initialize admin components if in admin area.
		if ( is_admin() ) {
			new Admin\Admin();
			new Admin\SettingsPage();
		}

		// Initialize frontend components.
		new Frontend\Frontend();
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
			'preview_query_param'   => WTS_DEFAULT_QUERY_PARAM,
			'advanced'              => array(
				'preview_enabled'     => true,
				'debug_enabled'       => false,
			),
		);

		// Add options if they don't exist.
		add_option( 'wts_theme_switcher_settings', $default_options );

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
			'wts-theme-switcher',
			false,
			dirname( plugin_basename( WTS_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}